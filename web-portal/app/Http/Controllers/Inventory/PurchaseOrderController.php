<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use App\Support\PurchaseOrderApprovalSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->purchaseOrders();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $orders = $response['data'] ?? [];
        if (isset($orders['data']) && is_array($orders['data'])) {
            $orders = $orders['data'];
        }

        return Inertia::render('Inventory/PurchaseOrders/Index', [
            'purchaseOrders' => is_array($orders) ? $orders : [],
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $items = $this->s3->inventoryItems();
            $suppliers = $this->s3->suppliers();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.purchase-orders.index');
        }

        return Inertia::render('Inventory/PurchaseOrders/Create', [
            'inventoryItems' => $items['data'] ?? [],
            'suppliers' => $suppliers['data'] ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:150'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $lines = collect($data['lines'])->map(fn (array $line) => [
            'inventory_item_id' => (int) $line['inventory_item_id'],
            'quantity' => (float) $line['quantity'],
            'unit_cost' => (float) $line['unit_cost'],
        ])->values()->all();

        try {
            $response = $this->s3->createPurchaseOrder([
                'vendor_name' => $data['vendor_name'],
                'lines' => $lines,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $poId = (int) ($response['data']['id'] ?? 0);

        if ($poId <= 0) {
            return back()->with('error', 'Purchase order was not created.');
        }

        return redirect()
            ->route('inventory.purchase-orders.show', $poId)
            ->with('success', 'Purchase order created as draft.');
    }

    public function show(int $purchaseOrder): Response|RedirectResponse
    {
        try {
            $response = $this->s3->purchaseOrder($purchaseOrder);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.purchase-orders.index');
        }

        $po = $response['data'] ?? [];
        $status = (string) ($po['status'] ?? '');

        return Inertia::render('Inventory/PurchaseOrders/Show', [
            'purchaseOrder' => $po,
            'approvalSteps' => PurchaseOrderApprovalSteps::forPo($po),
            'approvalCurrentStep' => PurchaseOrderApprovalSteps::currentStepKey($po),
            'canSubmit' => $status === 'draft',
            'canApprove' => in_array($status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true),
            'canReceive' => $status === 'approved',
        ]);
    }

    public function submit(int $purchaseOrder): RedirectResponse
    {
        try {
            $this->s3->submitPurchaseOrder($purchaseOrder);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Purchase order submitted for approval.');
    }

    public function approve(int $purchaseOrder): RedirectResponse
    {
        try {
            $this->s3->approvePurchaseOrder($purchaseOrder, (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Approval step recorded.');
    }

    public function receive(int $purchaseOrder): RedirectResponse
    {
        try {
            $this->s3->receivePurchaseOrder($purchaseOrder);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Goods received. Stock and payables updated.');
    }
}
