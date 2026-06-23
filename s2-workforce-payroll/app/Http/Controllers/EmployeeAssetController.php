<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentScope;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\ReturnEmployeeAssetRequest;
use App\Http\Requests\StoreEmployeeAssetRequest;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmployeeAssetController extends Controller
{
    use AppliesDepartmentScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly AssetService $assets)
    {
    }

    public function index(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        $items = $employee->employeeAssets()->with('assetType')->orderByDesc('assigned_date')->get();

        return response()->json([
            'data' => $items->map(fn ($a) => $this->employeeAssetPayload($a))->values(),
        ]);
    }

    public function store(StoreEmployeeAssetRequest $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
            $asset = $this->assets->assign($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->employeeAssetPayload($asset)], 201);
    }

    public function returnAsset(ReturnEmployeeAssetRequest $request, EmployeeAsset $employeeAsset): JsonResponse
    {
        try {
            $employeeAsset->loadMissing('employee');

            if ($employeeAsset->employee !== null) {
                $this->assertEmployeeInScope($employeeAsset->employee, $request);
            }

            $asset = $this->assets->returnAsset($employeeAsset, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->employeeAssetPayload($asset)]);
    }
}
