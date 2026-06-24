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

class RoomController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
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
        ]);
    }
}
