<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\RedirectResponse;

trait HandlesPortalApiErrors
{
    /**
     * @return array{loadError: string, loadErrorCode: string, loadErrorTitle: string, loadErrorRecommendation: string}
     */
    protected function apiLoadErrorProps(ApiException $exception): array
    {
        $friendly = $exception->userMessage();

        return [
            'loadError' => $friendly['message'],
            'loadErrorCode' => $friendly['code'],
            'loadErrorTitle' => $friendly['title'],
            'loadErrorRecommendation' => $friendly['recommendation'],
        ];
    }

    protected function redirectApiError(ApiException $exception, ?string $fallbackRoute = null): RedirectResponse
    {
        $friendly = $exception->userMessage();
        $redirect = $fallbackRoute ? redirect()->route($fallbackRoute) : back();

        return $redirect
            ->withInput()
            ->with('error', $friendly['message'])
            ->with('error_detail', $friendly);
    }
}
