<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PositionController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $positions = $this->s2->positions();
            $departments = $this->s2->departments();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.employees.index');
        }

        return Inertia::render('Hr/Organization/Positions/Index', [
            'positions' => $positions['data'] ?? [],
            'departments' => $departments['data'] ?? [],
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.positions.create']),
            'canUpdate' => $this->auth->hasAnyPermission(['S2.workforce.positions.update']),
            'canDelete' => $this->auth->hasAnyPermission(['S2.workforce.positions.delete']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'department_id' => ['required', 'integer'],
            'grade' => ['nullable', 'string', 'max:10'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->s2->createPosition([
                'title' => $data['title'],
                'department_id' => (int) $data['department_id'],
                'grade' => $data['grade'] ?? null,
                'transport_allowance' => isset($data['transport_allowance']) ? (float) $data['transport_allowance'] : 0,
                'housing_allowance' => isset($data['housing_allowance']) ? (float) $data['housing_allowance'] : 0,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.positions.index')
            ->with('success', 'Position created.');
    }

    public function edit(int $position): Response|RedirectResponse
    {
        try {
            $response = $this->s2->position($position);
            $departments = $this->s2->departments();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.positions.index');
        }

        return Inertia::render('Hr/Organization/Positions/Edit', [
            'position' => $response['data'] ?? [],
            'departments' => $departments['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $position): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'department_id' => ['required', 'integer'],
            'grade' => ['nullable', 'string', 'max:10'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'housing_allowance' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->s2->updatePosition($position, [
                'title' => $data['title'],
                'department_id' => (int) $data['department_id'],
                'grade' => $data['grade'] ?? null,
                'transport_allowance' => isset($data['transport_allowance']) ? (float) $data['transport_allowance'] : 0,
                'housing_allowance' => isset($data['housing_allowance']) ? (float) $data['housing_allowance'] : 0,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.positions.index')
            ->with('success', 'Position updated.');
    }

    public function destroy(int $position): RedirectResponse
    {
        try {
            $this->s2->deletePosition($position);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.positions.index')
            ->with('success', 'Position deleted.');
    }
}
