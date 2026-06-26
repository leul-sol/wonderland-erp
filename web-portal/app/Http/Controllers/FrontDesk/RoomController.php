<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        try {
            $status = $request->string('status')->toString() ?: null;
            $response = $this->s3->rooms($status);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('FrontDesk/Rooms/Index', [
            'rooms' => $response['data'] ?? [],
            'filters' => [
                'status' => $status ?? '',
            ],
            'canUpdateStatus' => $this->auth->hasAnyPermission(['S3.hotel.rooms.write']),
        ]);
    }

    public function updateStatus(Request $request, int $room): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:available,maintenance,cleaning'],
        ]);

        try {
            $this->s3->updateRoomStatus($room, $data['status']);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Room status updated.');
    }
}
