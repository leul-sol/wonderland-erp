<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = session('portal.user', []);

        return Inertia::render('Dashboard/Index', [
            'welcome' => 'Pilot portal shell — Phase 0 complete. Front desk flows arrive in Phase 1.',
            'roles' => is_array($user) ? ($user['roles'] ?? []) : [],
        ]);
    }
}
