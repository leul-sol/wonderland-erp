<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function __construct(private readonly PurchaseOrderService $purchaseOrders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()->with('lines.inventoryItem')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($po) => $this->purchaseOrderPayload($po))->values(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        try {
            $po = $this->purchaseOrders->create(
                $request->validated('vendor_name'),
                $request->validated('lines'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->purchaseOrderPayload($po)], 201);
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        return response()->json(['data' => $this->purchaseOrderPayload($purchaseOrder)]);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);
        $roles = $request->attributes->get('auth_roles', []);

        try {
            $po = $this->purchaseOrders->approve($purchaseOrder, $userId, is_array($roles) ? $roles : []);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->purchaseOrderPayload($po)]);
    }

    public function submit(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $po = $this->purchaseOrders->submit($purchaseOrder);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->purchaseOrderPayload($po)]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $receivedBy = (int) $request->attributes->get('auth_user_id', 0);

        $data = $request->validate([
            'lines' => ['nullable', 'array', 'min:1'],
            'lines.*.purchase_order_line_id' => ['required_with:lines', 'integer'],
            'lines.*.quantity_received' => ['required_with:lines', 'numeric', 'gt:0'],
        ]);

        $lineReceipts = isset($data['lines']) ? $data['lines'] : null;

        try {
            $po = $this->purchaseOrders->receive($purchaseOrder, $receivedBy, $lineReceipts);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->purchaseOrderPayload($po)]);
    }

    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $po = $this->purchaseOrders->cancel($purchaseOrder);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->purchaseOrderPayload($po)]);
    }
}
