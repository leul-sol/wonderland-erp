<?php

namespace App\Http\Controllers\Procurement;

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

        return Inertia::render('Procurement/PurchaseOrders/Index', [
            'purchaseOrders' => is_array($orders) ? $orders : [],
        ]);
    }

    public function show(int $purchaseOrder): Response|RedirectResponse
    {
        try {
            $response = $this->s3->purchaseOrder($purchaseOrder);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'procurement.purchase-orders.index');
        }

        $po = $response['data'] ?? [];

        return Inertia::render('Procurement/PurchaseOrders/Show', [
            'purchaseOrder' => $po,
            'approvalSteps' => PurchaseOrderApprovalSteps::forPo($po),
            'approvalCurrentStep' => PurchaseOrderApprovalSteps::currentStepKey($po),
            'canApprove' => in_array($po['status'] ?? '', ['pending_dept_head', 'pending_finance', 'pending_gm'], true),
        ]);
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
}
