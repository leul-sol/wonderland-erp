<?php

namespace App\Http\Controllers\FrontDesk;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\ProvidesCheckInModalData;
use App\Http\Controllers\Concerns\ResolvesCashierShiftPayments;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use App\Services\Auth\PortalAuthService;
use App\Services\FrontDesk\CashierShiftResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FolioController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use ProvidesCheckInModalData;
    use ResolvesCashierShiftPayments;

    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('FrontDesk/Folios/Index', [
            'folios' => $this->deferApi(function () {
                $response = $this->s3->folios('open');
                $paginator = $response['data'] ?? [];

                return is_array($paginator['data'] ?? null) ? $paginator['data'] : [];
            }),
            ...$this->checkInModalProps($request),
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
            'canAddCharge' => $this->auth->hasAnyPermission(['S3.hotel.folios.write']),
            'canSettle' => $this->auth->hasAnyPermission(['S3.hotel.folios.write']),
            'canCheckout' => $this->auth->hasAnyPermission(['S3.hotel.checkinout.write']),
            'openCashierShift' => app(CashierShiftResolver::class)->openShiftForDisplay(),
            'canViewCashierShifts' => $this->auth->hasAnyPermission(['S3.hotel.cashier.read']),
        ]);
    }

    public function addCharge(Request $request, int $folio): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'charge_category' => ['nullable', 'string', 'in:room,fb,minibar,laundry,event,other'],
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
            $payload = $this->folioPaymentPayload($data);
            $this->s3->recordFolioPayment($folio, $payload, (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Folio payment recorded.');
    }

    public function invoice(int $folio): StreamedResponse|RedirectResponse
    {
        try {
            $invoice = $this->s3->folioInvoice($folio);
        } catch (ApiException $e) {
            return redirect()
                ->route('front-desk.folios.show', $folio)
                ->with($this->flashApiError($e));
        }

        $payload = $invoice['data'] ?? [];
        $folioNumber = $payload['folio_number'] ?? "folio-{$folio}";
        $filename = "folio-invoice-{$folioNumber}.json";

        return response()->streamDownload(
            function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            ['Content-Type' => 'application/json'],
        );
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
