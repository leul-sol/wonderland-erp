<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreGuarantorRequest;
use App\Models\Employee;
use App\Services\GuarantorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class GuarantorController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly GuarantorService $guarantors)
    {
    }

    public function index(Employee $employee): JsonResponse
    {
        $items = $employee->guarantors()->orderByDesc('id')->get();

        return response()->json([
            'data' => $items->map(fn ($g) => $this->guarantorPayload($g))->values(),
        ]);
    }

    public function store(StoreGuarantorRequest $request, Employee $employee): JsonResponse
    {
        $guarantor = $this->guarantors->register($employee, $request->validated());

        return response()->json(['data' => $this->guarantorPayload($guarantor)], 201);
    }

    public function downloadLetter(Employee $employee, \App\Models\Guarantor $guarantor): \Symfony\Component\HttpFoundation\Response
    {
        if ($guarantor->employee_id !== $employee->id) {
            return $this->error('NOT_FOUND', 'Guarantor not found for employee.', 404);
        }

        if ($guarantor->letter_path === null || ! Storage::disk('local')->exists($guarantor->letter_path)) {
            return $this->error('NOT_FOUND', 'Guarantor letter not found.', 404);
        }

        return response(Storage::disk('local')->get($guarantor->letter_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="guarantor-'.$guarantor->id.'.pdf"',
        ]);
    }
}
