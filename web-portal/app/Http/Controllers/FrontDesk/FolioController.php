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

class FolioController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->folios('open');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $paginator = $response['data'] ?? [];
        $folios = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];

        return Inertia::render('FrontDesk/Folios/Index', [
            'folios' => $folios,
        ]);
    }

    public function show(int $folio): Response|RedirectResponse
    {
        try {
            $folioResponse = $this->s3->folio($folio);
            $folioData = $folioResponse['data'] ?? [];
            $reservationId = (int) ($folioData['reservation_id'] ?? 0);
            $reservation = $reservationId > 0 ? $this->s3->reservation($reservationId) : ['data' => null];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'front-desk.folios.index');
        }

        return Inertia::render('FrontDesk/Folios/Show', [
            'folio' => $folioData,
            'reservation' => $reservation['data'] ?? null,
        ]);
    }

    public function addCharge(Request $request, int $folio): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'charge_category' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $this->s3->addFolioCharge($folio, [
                'description' => $data['description'],
                'amount' => (float) $data['amount'],
                'charge_category' => $data['charge_category'] ?? 'other',
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Charge posted to folio.');
    }

    public function settle(Request $request, int $folio): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
        ]);

        try {
            $this->s3->settleFolio($folio, [
                'amount' => (float) $data['amount'],
                'payment_method' => $data['payment_method'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Folio payment recorded.');
    }

    public function checkOut(int $folio): RedirectResponse
    {
        try {
            $folioResponse = $this->s3->folio($folio);
            $reservationId = (int) ($folioResponse['data']['reservation_id'] ?? 0);

            if ($reservationId <= 0) {
                return back()->with('error', 'No reservation linked to this folio.');
            }

            $this->s3->checkOut($reservationId);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('front-desk.rooms.index')
            ->with('success', 'Guest checked out successfully.');
    }
}
