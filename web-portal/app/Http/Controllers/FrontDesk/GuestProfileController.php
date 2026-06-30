<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\ProvidesCheckInModalData;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestProfileController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use ProvidesCheckInModalData;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('FrontDesk/Guests/Index', [
            'guests' => $this->deferApi(function () {
                $response = $this->s3->guestProfiles();
                $paginator = $response['data'] ?? [];

                return is_array($paginator['data'] ?? null) ? $paginator['data'] : (is_array($paginator) ? $paginator : []);
            }),
            ...$this->checkInModalProps($request),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('front-desk.guests.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:40'],
            'id_document_number' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s3->createGuestProfile($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('front-desk.guests.index')
            ->with('success', 'Guest profile created.');
    }

    public function edit(int $guest): RedirectResponse
    {
        return redirect()->route('front-desk.guests.index', ['open' => 'edit', 'id' => $guest]);
    }

    public function update(Request $request, int $guest): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:40'],
            'id_document_number' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s3->updateGuestProfile($guest, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('front-desk.guests.index')
            ->with('success', 'Guest profile updated.');
    }
}
