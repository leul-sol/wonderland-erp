<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\LoadsGatewayDataInParallel;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use LoadsGatewayDataInParallel;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('FrontDesk/Settings/Index', [
            'pageLoad' => $this->deferPageLoad(function () {
                $results = $this->fetchGatewayInParallel($this->s3, [
                    'roomTypes' => ['path' => '/s3/api/v1/room-types', 'query' => ['active_only' => false]],
                    'rooms' => ['path' => '/s3/api/v1/rooms', 'query' => []],
                ]);
                $response = $this->requireParallelResult($results, 'roomTypes');
                $rooms = $results['rooms'] ?? ['data' => []];

                return [
                    'roomTypes' => $response['data'] ?? [],
                    'rooms' => $rooms['data'] ?? [],
                ];
            }),
        ]);
    }

    public function storeRoomType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['required', 'string', 'max:10'],
            'base_rate' => ['required', 'numeric', 'gte:0'],
            'max_occupancy' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->s3->createRoomType([
                'name' => $data['name'],
                'code' => $data['code'],
                'base_rate' => (float) $data['base_rate'],
                'max_occupancy' => (int) $data['max_occupancy'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Room type created.');
    }

    public function updateRoomType(Request $request, int $roomType): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'base_rate' => ['sometimes', 'numeric', 'gte:0'],
            'max_occupancy' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s3->updateRoomType($roomType, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Room type updated.');
    }

    public function rooms(): RedirectResponse
    {
        return redirect()->route('front-desk.settings.index');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:10'],
            'room_type_id' => ['required', 'integer'],
            'floor' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $this->s3->createRoom([
                'room_number' => $data['room_number'],
                'room_type_id' => (int) $data['room_type_id'],
                'floor' => $data['floor'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Room added.');
    }

    public function updateRoom(Request $request, int $room): RedirectResponse
    {
        $data = $request->validate([
            'room_number' => ['sometimes', 'string', 'max:10'],
            'room_type_id' => ['sometimes', 'integer'],
            'floor' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $this->s3->updateRoom($room, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Room updated.');
    }
}
