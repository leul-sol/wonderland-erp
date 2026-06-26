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

class MenuCategoryController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->menuCategories(false);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'fb.settings.index');
        }

        return Inertia::render('Fb/MenuCategories/Index', [
            'categories' => $response['data'] ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = ['name' => $data['name']];
        if (isset($data['display_order'])) {
            $payload['display_order'] = (int) $data['display_order'];
        }

        try {
            $this->s3->createMenuCategory($payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Menu category created.');
    }

    public function update(Request $request, int $menuCategory): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s3->updateMenuCategory($menuCategory, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Menu category updated.');
    }
}
