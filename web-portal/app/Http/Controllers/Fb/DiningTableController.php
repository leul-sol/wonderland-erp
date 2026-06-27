<?php

namespace App\Http\Controllers\Fb;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiningTableController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Fb/DiningTables/Index', [
            'tables' => $this->deferApi(fn () => ($this->s3->diningTables(false))['data'] ?? []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $this->s3->createDiningTable([
                'table_number' => $data['table_number'],
                'capacity' => (int) $data['capacity'],
                'location' => $data['location'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Dining table added.');
    }

    public function update(Request $request, int $diningTable): RedirectResponse
    {
        $data = $request->validate([
            'table_number' => ['sometimes', 'string', 'max:10'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s3->updateDiningTable($diningTable, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Dining table updated.');
    }
}
