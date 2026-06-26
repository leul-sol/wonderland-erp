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

    public function index(Request $request): Response|RedirectResponse
    {
        $tab = $request->string('tab')->toString() ?: 'open';

        try {
            $status = match ($tab) {
                'open' => 'open',
                'finalized', 'billed' => 'finalized',
                default => null,
            };
            $response = $this->s3->orders($status);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $orders = collect($response['data'] ?? []);

        if ($tab === 'billed') {
            $orders = $orders->filter(function (array $order): bool {
                $bill = $order['bill'] ?? null;

                return $bill !== null && in_array($bill['status'] ?? '', ['paid', 'posted_to_folio', 'posted_to_event'], true);
            });
        } elseif ($tab === 'finalized') {
            $orders = $orders->filter(function (array $order): bool {
                $bill = $order['bill'] ?? null;

                return $bill === null || in_array($bill['status'] ?? '', ['unpaid', 'partial'], true);
            });
        }

        return Inertia::render('Fb/Orders/Index', [
            'orders' => $orders->values()->all(),
            'filters' => ['tab' => $tab],
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        try {
            $foliosResponse = $this->s3->folios('open');
            $tablesResponse = $this->s3->diningTables();
            $paginator = $foliosResponse['data'] ?? [];
            $folios = is_array($paginator['data'] ?? null) ? $paginator['data'] : [];
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $folioId = $request->integer('folio_id') ?: null;

        return Inertia::render('Fb/Orders/Create', [
            'folios' => $folios,
            'diningTables' => $tablesResponse['data'] ?? [],
            'selectedFolioId' => $folioId,
            'customerTypes' => $this->customerTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_type' => ['required', 'string', 'in:hotel_guest,outside_cash,outside_credit,event'],
            'folio_id' => ['nullable', 'integer', 'required_if:customer_type,hotel_guest'],
            'customer_ref_id' => ['nullable', 'integer', 'required_if:customer_type,event'],
            'dining_table_id' => ['nullable', 'integer'],
        ]);

        $payload = [
            'customer_type' => $data['customer_type'],
        ];

        if (! empty($data['folio_id'])) {
            $payload['folio_id'] = (int) $data['folio_id'];
        }

        if (! empty($data['customer_ref_id'])) {
            $payload['customer_ref_id'] = (int) $data['customer_ref_id'];
        }

        if (! empty($data['dining_table_id'])) {
            $payload['dining_table_id'] = (int) $data['dining_table_id'];
        }

        try {
            $response = $this->s3->createOrder($payload);
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
            return $this->redirectApiError($e, 'fb.orders.index');
        }

        return Inertia::render('Fb/Orders/Show', [
            'order' => $orderData,
            'menuItems' => $menuResponse['data'] ?? [],
            'folio' => $folio['data'] ?? null,
            'routingHint' => $this->routingHint($orderData['customer_type'] ?? 'outside_cash'),
            'canPayBill' => $this->canPayBill($orderData),
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
            $this->s3->finalizeOrder($order);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('fb.orders.show', $order)
            ->with('success', 'Order finalized. Record bill payment if required.');
    }

    public function cancel(int $order): RedirectResponse
    {
        try {
            $this->s3->cancelOrder($order);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('fb.orders.index')
            ->with('success', 'Order cancelled.');
    }

    public function removeLine(int $order, int $line): RedirectResponse
    {
        try {
            $this->s3->removeOrderLine($order, $line);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Line removed from order.');
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    private function customerTypes(): array
    {
        return [
            ['value' => 'hotel_guest', 'label' => 'Hotel guest (folio)', 'description' => 'Charge posts to an open guest folio on finalize'],
            ['value' => 'outside_cash', 'label' => 'Walk-in cash / card', 'description' => 'Direct collection — bill settled on finalize'],
            ['value' => 'outside_credit', 'label' => 'Walk-in credit', 'description' => 'Bill stays open until payment is recorded'],
            ['value' => 'event', 'label' => 'Event catering', 'description' => 'Posts event AR journal when bill is paid'],
        ];
    }

    private function routingHint(string $customerType): string
    {
        return match ($customerType) {
            'hotel_guest' => 'F&B revenue is posted to the guest folio (in-room dining).',
            'outside_cash' => 'Cash or card collection — bill is marked paid when the order is finalized.',
            'outside_credit' => 'Credit account — record bill payment after finalize.',
            'event' => 'Event billing — payment posts AR event revenue to finance.',
            'employee', 'family_member', 'management' => 'Staff meal — amount accumulates for payroll deduction.',
            default => 'Standard restaurant billing.',
        };
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function canPayBill(array $order): bool
    {
        $bill = $order['bill'] ?? null;

        if ($bill === null) {
            return false;
        }

        $outstanding = (float) ($bill['outstanding_balance'] ?? 0);

        return $outstanding > 0 && in_array($bill['status'] ?? '', ['unpaid', 'partial'], true);
    }
}
