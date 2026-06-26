<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->roomTypes(false);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('FrontDesk/Settings/Index', [
            'roomTypes' => $response['data'] ?? [],
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
}
