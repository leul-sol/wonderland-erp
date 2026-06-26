<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->suppliers();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Inventory/Suppliers/Index', [
            'suppliers' => $response['data'] ?? [],
        ]);
    }

    public function show(int $supplier): Response|RedirectResponse
    {
        try {
            $response = $this->s3->supplier($supplier);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.suppliers.index');
        }

        $data = $response['data'] ?? [];
        $outstanding = (float) ($data['outstanding_balance'] ?? 0);

        return Inertia::render('Inventory/Suppliers/Show', [
            'supplier' => $data,
            'canPay' => $outstanding > 0,
        ]);
    }

    public function pay(Request $request, int $supplier): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:80'],
        ]);

        $payload = [
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'],
        ];

        if (! empty($data['reference_number'])) {
            $payload['reference_number'] = $data['reference_number'];
        }

        try {
            $this->s3->paySupplier($supplier, $payload, (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('inventory.suppliers.show', $supplier)
            ->with('success', 'Supplier payment recorded.');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inventory.suppliers.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $response = $this->s3->createSupplier($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $supplierId = (int) ($response['data']['id'] ?? 0);

        return redirect()
            ->route('inventory.suppliers.show', $supplierId > 0 ? $supplierId : 0)
            ->with('success', 'Supplier created.');
    }

    public function edit(int $supplier): Response|RedirectResponse
    {
        try {
            $response = $this->s3->supplier($supplier);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.suppliers.index');
        }

        return Inertia::render('Inventory/Suppliers/Edit', [
            'supplier' => $response['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'payment_terms' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s3->updateSupplier($supplier, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('inventory.suppliers.show', $supplier)
            ->with('success', 'Supplier updated.');
    }
}
