<?php

namespace App\Services\FrontDesk;

use App\Exceptions\ApiException;
use App\Services\Api\S3HospitalityClient;
use App\Services\Auth\PortalAuthService;

class CashierShiftResolver
{
    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly PortalAuthService $auth,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openShiftForCurrentUser(): ?array
    {
        $userId = (int) data_get($this->auth->user(), 'id', 0);
        if ($userId <= 0 || ! $this->canReadShifts()) {
            return null;
        }

        return $this->firstOpenShift(['cashier_id' => $userId]);
    }

    /**
     * Shift to attach cash payments: own open shift, else any open shift on property.
     */
    public function openShiftIdForCashCollection(): ?int
    {
        $ownShift = $this->openShiftForCurrentUser();
        if ($ownShift !== null) {
            return (int) $ownShift['id'];
        }

        if (! $this->canReadShifts()) {
            return null;
        }

        $anyShift = $this->firstOpenShift(['status' => 'open']);

        return $anyShift !== null ? (int) $anyShift['id'] : null;
    }

    public function openShiftIdForCurrentUser(): ?int
    {
        $shift = $this->openShiftForCurrentUser();

        return $shift !== null ? (int) $shift['id'] : null;
    }

    /**
     * Any open shift on property (for F&B staff posting to front-desk drawer).
     *
     * @return array<string, mixed>|null
     */
    public function openShiftForProperty(): ?array
    {
        if (! $this->canReadShifts()) {
            return null;
        }

        return $this->firstOpenShift([]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openShiftForDisplay(): ?array
    {
        return $this->openShiftForCurrentUser() ?? $this->openShiftForProperty();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|null
     */
    private function firstOpenShift(array $query): ?array
    {
        try {
            $response = $this->s3->cashierShifts([
                ...$query,
                'status' => 'open',
                'per_page' => 5,
            ]);
        } catch (ApiException) {
            return null;
        }

        $payload = $response['data'] ?? [];
        $shifts = is_array($payload['data'] ?? null) ? $payload['data'] : (is_array($payload) ? $payload : []);

        foreach ($shifts as $shift) {
            if (is_array($shift) && ($shift['status'] ?? '') === 'open') {
                return $shift;
            }
        }

        return null;
    }

    private function canReadShifts(): bool
    {
        return $this->auth->hasAnyPermission(['S3.hotel.cashier.read']);
    }
}
