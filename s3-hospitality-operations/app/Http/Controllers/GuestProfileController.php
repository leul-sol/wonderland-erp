<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\GuestProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestProfileController extends Controller
{
    use RespondsWithApiErrors;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => GuestProfile::query()->orderBy('full_name')->paginate(25),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:40'],
            'id_document_number' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $guest = GuestProfile::query()->create($data);

        return response()->json(['data' => $guest], 201);
    }

    public function show(GuestProfile $guestProfile): JsonResponse
    {
        $guestProfile->load('reservations');

        return response()->json(['data' => $guestProfile]);
    }

    public function update(Request $request, GuestProfile $guestProfile): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'id_document_type' => ['nullable', 'string', 'max:40'],
            'id_document_number' => ['nullable', 'string', 'max:60'],
            'nationality' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $guestProfile->update($data);

        return response()->json(['data' => $guestProfile->fresh()]);
    }
}
