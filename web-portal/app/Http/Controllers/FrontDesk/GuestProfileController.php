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

class GuestProfileController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->guestProfiles();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $paginator = $response['data'] ?? [];
        $guests = is_array($paginator['data'] ?? null) ? $paginator['data'] : (is_array($paginator) ? $paginator : []);

        return Inertia::render('FrontDesk/Guests/Index', [
            'guests' => $guests,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('FrontDesk/Guests/Edit', [
            'guest' => null,
        ]);
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
            $guest = $this->s3->createGuestProfile($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $guestId = (int) ($guest['data']['id'] ?? 0);

        return redirect()
            ->route('front-desk.guests.edit', $guestId)
            ->with('success', 'Guest profile created.');
    }

    public function edit(int $guest): Response|RedirectResponse
    {
        try {
            $response = $this->s3->guestProfile($guest);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'front-desk.guests.index');
        }

        return Inertia::render('FrontDesk/Guests/Edit', [
            'guest' => $response['data'] ?? null,
        ]);
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

        return back()->with('success', 'Guest profile updated.');
    }
}
