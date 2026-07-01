<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Http\Requests\AddOrderLineRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderLine;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = RestaurantOrder::query()->with(['lines.menuItem', 'folio'])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($order) => $this->orderPayload($order))->values(),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->create(
                $request->validated('folio_id'),
                $request->validated('employee_consumption_period_id'),
                $request->validated('customer_type'),
                $request->validated('customer_ref_id'),
                $request->validated('dining_table_id'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->orderPayload($order)], 201);
    }

    public function show(RestaurantOrder $order): JsonResponse
    {
        return response()->json(['data' => $this->orderPayload($order)]);
    }

    public function addLine(AddOrderLineRequest $request, RestaurantOrder $order): JsonResponse
    {
        try {
            $this->orders->addLine(
                $order,
                (int) $request->validated('menu_item_id'),
                (int) $request->validated('quantity'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->orderPayload($order->fresh(['lines.menuItem', 'folio']))], 201);
    }

    public function removeLine(RestaurantOrder $order, RestaurantOrderLine $line): JsonResponse
    {
        try {
            $order = $this->orders->removeLine($order, $line);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->orderPayload($order)]);
    }

    public function cancel(RestaurantOrder $order): JsonResponse
    {
        try {
            $order = $this->orders->cancel($order);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->orderPayload($order)]);
    }

    public function finalize(Request $request, RestaurantOrder $order): JsonResponse
    {
        $data = $request->validate([
            'cashier_shift_id' => ['nullable', 'integer', 'exists:cashier_shifts,id'],
        ]);

        $cashierId = (int) $request->attributes->get('auth_user_id', 0);
        $cashierShiftId = isset($data['cashier_shift_id']) ? (int) $data['cashier_shift_id'] : null;

        try {
            $order = $this->orders->finalize($order, $cashierId > 0 ? $cashierId : null, $cashierShiftId);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->orderPayload($order)]);
    }
}
