<?php

namespace App\Http\Controllers\Fb;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function pay(Request $request, int $bill): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'order_id' => ['required', 'integer'],
        ]);

        $payload = [
            'payment_method' => $data['payment_method'],
        ];

        if (isset($data['amount'])) {
            $payload['amount'] = (float) $data['amount'];
        }

        try {
            $this->s3->payBill($bill, $payload, (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('fb.orders.show', $data['order_id'])
            ->with('success', 'Bill payment recorded.');
    }
}
