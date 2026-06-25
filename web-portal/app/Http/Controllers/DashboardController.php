<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetricsBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsBuilder $metrics,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        return Inertia::render('Dashboard/Index', $this->metrics->build($from, $to));
    }
}
