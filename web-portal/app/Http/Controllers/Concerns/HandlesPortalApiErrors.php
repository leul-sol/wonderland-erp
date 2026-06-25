<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\RedirectResponse;

trait HandlesPortalApiErrors
{
    /**
     * @return array{loadError: string, loadErrorCode: string}
     */
    protected function apiLoadErrorProps(ApiException $exception): array
    {
        return [
            'loadError' => $exception->getMessage(),
            'loadErrorCode' => $exception->errorCode,
        ];
    }

    protected function redirectApiError(ApiException $exception, ?string $fallbackRoute = null): RedirectResponse
    {
        $redirect = $fallbackRoute ? redirect()->route($fallbackRoute) : back();

        return $redirect
            ->withInput()
            ->with('error', $exception->getMessage());
    }
}
