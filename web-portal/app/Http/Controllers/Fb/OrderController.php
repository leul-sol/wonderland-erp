<?php

namespace App\Http\Controllers\Fb;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function create(Request $request): Response|RedirectResponse
    {
        try {
            $foliosResponse = $this->s3->folios('open');
            $paginator = $foliosResponse['data'] ?? [];
            $folios = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $folioId = $request->integer('folio_id') ?: null;

        return Inertia::render('Fb/Orders/Create', [
            'folios' => $folios,
            'selectedFolioId' => $folioId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'folio_id' => ['required', 'integer'],
        ]);

        try {
            $response = $this->s3->createOrder(['folio_id' => (int) $data['folio_id']]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $orderId = (int) ($response['data']['id'] ?? 0);

        if ($orderId <= 0) {
            return back()->with('error', 'Order was not created.');
        }

        return redirect()
            ->route('fb.orders.show', $orderId)
            ->with('success', 'F&B order opened. Add menu items and finalize.');
    }

    public function show(int $order): Response|RedirectResponse
    {
        try {
            $orderResponse = $this->s3->order($order);
            $menuResponse = $this->s3->menuItems();
            $orderData = $orderResponse['data'] ?? [];
            $folioId = (int) ($orderData['folio_id'] ?? 0);
            $folio = $folioId > 0 ? $this->s3->folio($folioId) : ['data' => null];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'fb.orders.create');
        }

        return Inertia::render('Fb/Orders/Show', [
            'order' => $orderData,
            'menuItems' => $menuResponse['data'] ?? [],
            'folio' => $folio['data'] ?? null,
        ]);
    }

    public function addLine(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->s3->addOrderLine($order, [
                'menu_item_id' => (int) $data['menu_item_id'],
                'quantity' => (int) $data['quantity'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Item added to order.');
    }

    public function finalize(int $order): RedirectResponse
    {
        try {
            $response = $this->s3->finalizeOrder($order);
            $folioId = (int) ($response['data']['folio_id'] ?? 0);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        if ($folioId > 0) {
            return redirect()
                ->route('front-desk.folios.show', $folioId)
                ->with('success', 'F&B order finalized and posted to folio.');
        }

        return redirect()
            ->route('fb.menu.index')
            ->with('success', 'F&B order finalized.');
    }
}
