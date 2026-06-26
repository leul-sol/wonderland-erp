<?php

namespace App\Http\Controllers\Fb;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Fb/Settings/Index');
    }
}
