<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\ApiException;
use App\Services\Api\GatewayClient;

trait LoadsGatewayDataInParallel
{
    /**
     * @param  array<string, array{path: string, query?: array<string, mixed>}>  $requests
     * @return array<string, array<string, mixed>|null>
     *
     * @throws ApiException
     */
    protected function fetchGatewayInParallel(GatewayClient $client, array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        if (count($requests) === 1) {
            $key = array_key_first($requests);
            $request = $requests[$key];

            return [
                $key => $client->json('GET', $request['path'], $request['query'] ?? []),
            ];
        }

        return $client->fetchMany($requests);
    }

    /**
     * @param  array<string, array<string, mixed>|null>  $results
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    protected function requireParallelResult(array $results, string $key): array
    {
        $value = $results[$key] ?? null;

        if (! is_array($value)) {
            throw new ApiException('SERVICE_UNAVAILABLE', "Unable to load {$key}.", 503);
        }

        return $value;
    }
}
