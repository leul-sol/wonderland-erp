<?php

namespace App\Services\Api;

class S3HospitalityClient extends GatewayClient
{
    /**
     * @return array<string, mixed>
     */
    public function rooms(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s3/api/v1/rooms', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function roomTypes(): array
    {
        return $this->json('GET', '/s3/api/v1/room-types');
    }

    /**
     * @return array<string, mixed>
     */
    public function reservations(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s3/api/v1/reservations', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createReservation(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/reservations', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function reservation(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/reservations/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function checkIn(int $reservationId, int $roomId): array
    {
        return $this->json('POST', "/s3/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $roomId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkOut(int $reservationId): array
    {
        return $this->json('POST', "/s3/api/v1/reservations/{$reservationId}/check-out");
    }

    /**
     * @return array<string, mixed>
     */
    public function folios(?string $status = null): array
    {
        $query = $status ? ['status' => $status, 'per_page' => 50] : ['per_page' => 50];

        return $this->json('GET', '/s3/api/v1/folios', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function folio(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/folios/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addFolioCharge(int $folioId, array $payload): array
    {
        return $this->json('POST', "/s3/api/v1/folios/{$folioId}/charges", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function settleFolio(int $folioId, array $payload): array
    {
        return $this->json('POST', "/s3/api/v1/folios/{$folioId}/settle", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrders(): array
    {
        return $this->json('GET', '/s3/api/v1/purchase-orders', ['per_page' => 50]);
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseOrder(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/purchase-orders/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function approvePurchaseOrder(int $id, string $idempotencyKey): array
    {
        return $this->json('POST', "/s3/api/v1/purchase-orders/{$id}/approve", [], [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function submitPurchaseOrder(int $id): array
    {
        return $this->json('POST', "/s3/api/v1/purchase-orders/{$id}/submit");
    }

    /**
     * @return array<string, mixed>
     */
    public function menuItems(): array
    {
        return $this->json('GET', '/s3/api/v1/menu-items', ['active_only' => true]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/orders', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function order(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/orders/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function addOrderLine(int $orderId, array $payload): array
    {
        return $this->json('POST', "/s3/api/v1/orders/{$orderId}/lines", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function finalizeOrder(int $orderId): array
    {
        return $this->json('POST', "/s3/api/v1/orders/{$orderId}/finalize");
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryItems(): array
    {
        return $this->json('GET', '/s3/api/v1/items');
    }

    /**
     * @return array<string, mixed>
     */
    public function suppliers(): array
    {
        return $this->json('GET', '/s3/api/v1/suppliers');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPurchaseOrder(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/purchase-orders', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function receivePurchaseOrder(int $id): array
    {
        return $this->json('POST', "/s3/api/v1/purchase-orders/{$id}/receive");
    }

    /**
     * @return array<string, mixed>
     */
    public function consumptionPeriods(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s3/api/v1/employee-consumption-periods', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function openConsumptionPeriod(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/employee-consumption-periods', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function closeConsumptionPeriod(int $id): array
    {
        return $this->json('POST', "/s3/api/v1/employee-consumption-periods/{$id}/close");
    }

    /**
     * @return array<string, mixed>
     */
    public function createConsumptionOrder(int $periodId): array
    {
        return $this->json('POST', '/s3/api/v1/orders', [
            'employee_consumption_period_id' => $periodId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function groupBookings(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s3/api/v1/group-bookings', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function groupBooking(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/group-bookings/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createGroupBooking(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/group-bookings', $payload);
    }

    /**
     * @param  list<array{reservation_id: int, room_id: int}>  $assignments
     * @return array<string, mixed>
     */
    public function checkInGroupBooking(int $id, array $assignments): array
    {
        return $this->json('POST', "/s3/api/v1/group-bookings/{$id}/check-in", [
            'assignments' => $assignments,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkOutGroupBooking(int $id): array
    {
        return $this->json('POST', "/s3/api/v1/group-bookings/{$id}/check-out");
    }
}
