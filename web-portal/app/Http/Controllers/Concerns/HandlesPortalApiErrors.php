<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\RedirectResponse;

trait HandlesPortalApiErrors
{
    protected function redirectApiError(ApiException $exception, ?string $fallbackRoute = null): RedirectResponse
    {
        $redirect = $fallbackRoute ? redirect()->route($fallbackRoute) : back();

        return $redirect
            ->withInput()
            ->with('error', $exception->getMessage());
    }
}
