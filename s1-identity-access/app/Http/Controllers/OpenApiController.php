<?php

namespace App\Http\Controllers;

use App\Support\OpenApiDocument;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(OpenApiDocument::build());
    }
}
