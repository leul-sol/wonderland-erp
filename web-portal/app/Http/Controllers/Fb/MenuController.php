<?php

namespace App\Http\Controllers\Fb;

use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    use DefersGatewayPageData;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Fb/Menu/Index', [
            'menuItems' => $this->deferApi(fn () => ($this->s3->menuItems())['data'] ?? []),
        ]);
    }
}
