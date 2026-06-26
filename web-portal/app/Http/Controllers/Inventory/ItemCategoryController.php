<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ItemCategoryController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->itemCategories();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.items.index');
        }

        return Inertia::render('Inventory/ItemCategories/Index', [
            'categories' => $response['data'] ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s3->createItemCategory($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Item category created.');
    }

    public function update(Request $request, int $itemCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s3->updateItemCategory($itemCategory, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Item category updated.');
    }

    public function destroy(int $itemCategory): RedirectResponse
    {
        try {
            $this->s3->deleteItemCategory($itemCategory);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Item category deactivated.');
    }
}
