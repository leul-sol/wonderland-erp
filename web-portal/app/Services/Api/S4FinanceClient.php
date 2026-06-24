<?php

namespace App\Services\Api;

class S4FinanceClient extends GatewayClient
{
    /**
     * @return array<string, mixed>
     */
    public function payables(?string $status = 'open'): array
    {
        $query = ['per_page' => 50];
        if ($status) {
            $query['status'] = $status;
        }

        return $this->json('GET', '/s4/api/v1/payables', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function settlePayable(int $payableId, array $payload): array
    {
        return $this->json('POST', "/s4/api/v1/payables/{$payableId}/settle", $payload);
    }
}
