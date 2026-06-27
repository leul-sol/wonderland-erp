<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use Inertia\DeferProp;
use Inertia\Inertia;

trait DefersGatewayPageData
{
    use HandlesPortalApiErrors;

    /**
     * Load API-backed page data after the shell renders (Inertia deferred props).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     */
    protected function deferApi(callable $callback, string $group = 'default'): DeferProp
    {
        return Inertia::defer(function () use ($callback) {
            try {
                return $callback();
            } catch (ApiException $e) {
                report($e);

                return null;
            }
        }, $group, rescue: true);
    }

    /**
     * Deferred bundle that can surface API load errors to the page.
     *
     * @param  callable(): array<string, mixed>  $callback
     */
    protected function deferPageLoad(callable $callback, string $group = 'default'): DeferProp
    {
        return Inertia::defer(function () use ($callback) {
            try {
                return $callback();
            } catch (ApiException $e) {
                report($e);

                return array_merge(
                    ['loadFailed' => true],
                    $this->apiLoadErrorProps($e),
                );
            }
        }, $group, rescue: true);
    }
}
