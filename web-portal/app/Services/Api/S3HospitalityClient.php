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
    public function room(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/rooms/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRoom(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/rooms', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateRoom(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/rooms/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function roomTypes(bool $activeOnly = true): array
    {
        return $this->json('GET', '/s3/api/v1/room-types', ['active_only' => $activeOnly]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRoomType(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/room-types', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateRoomType(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/room-types/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function cashierShifts(): array
    {
        return $this->json('GET', '/s3/api/v1/cashier-shifts');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function openCashierShift(array $payload = []): array
    {
        return $this->json('POST', '/s3/api/v1/cashier-shifts', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function cashierShift(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/cashier-shifts/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function cashierShiftReport(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/cashier-shifts/{$id}/report");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function closeCashierShift(int $id, array $payload): array
    {
        return $this->json('POST', "/s3/api/v1/cashier-shifts/{$id}/close", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function reservations(?string $status = null): array
    {
        $query = ['per_page' => 50];
        if ($status) {
            $query['status'] = $status;
        }

        return $this->json('GET', '/s3/api/v1/reservations', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function guestProfiles(): array
    {
        return $this->json('GET', '/s3/api/v1/guest-profiles', ['per_page' => 50]);
    }

    /**
     * @return array<string, mixed>
     */
    public function guestProfile(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/guest-profiles/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createGuestProfile(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/guest-profiles', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateGuestProfile(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/guest-profiles/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelReservation(int $id): array
    {
        return $this->json('PUT', "/s3/api/v1/reservations/{$id}/cancel");
    }

    /**
     * @return array<string, mixed>
     */
    public function noShowReservation(int $id): array
    {
        return $this->json('PUT', "/s3/api/v1/reservations/{$id}/no-show");
    }

    /**
     * @return array<string, mixed>
     */
    public function updateRoomStatus(int $roomId, string $status): array
    {
        return $this->json('PUT', "/s3/api/v1/rooms/{$roomId}/status", ['status' => $status]);
    }

    /**
     * @return array<string, mixed>
     */
    public function folioInvoice(int $folioId): array
    {
        return $this->json('GET', "/s3/api/v1/folios/{$folioId}/invoice");
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
     * @return array<string, mixed>
     */
    public function menuItemsCatalog(bool $activeOnly = false): array
    {
        return $this->json('GET', '/s3/api/v1/menu-items', ['active_only' => $activeOnly]);
    }

    /**
     * @return array<string, mixed>
     */
    public function menuItem(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/menu-items/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMenuItem(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/menu-items', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateMenuItem(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/menu-items/{$id}", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateMenuItemRecipe(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/menu-items/{$id}/recipe", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function menuCategories(bool $activeOnly = false): array
    {
        return $this->json('GET', '/s3/api/v1/menu-categories', ['active_only' => $activeOnly]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMenuCategory(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/menu-categories', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateMenuCategory(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/menu-categories/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function diningTables(bool $activeOnly = true): array
    {
        return $this->json('GET', '/s3/api/v1/dining-tables', ['active_only' => $activeOnly]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createDiningTable(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/dining-tables', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateDiningTable(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/dining-tables/{$id}", $payload);
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
    public function cancelOrder(int $orderId): array
    {
        return $this->json('PUT', "/s3/api/v1/orders/{$orderId}/cancel");
    }

    /**
     * @return array<string, mixed>
     */
    public function removeOrderLine(int $orderId, int $lineId): array
    {
        return $this->json('DELETE', "/s3/api/v1/orders/{$orderId}/lines/{$lineId}");
    }

    /**
     * @return array<string, mixed>
     */
    public function orders(?string $status = null): array
    {
        $query = $status ? ['status' => $status] : [];

        return $this->json('GET', '/s3/api/v1/orders', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function bill(int $billId): array
    {
        return $this->json('GET', "/s3/api/v1/bills/{$billId}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function payBill(int $billId, array $payload, string $idempotencyKey): array
    {
        return $this->json('POST', "/s3/api/v1/bills/{$billId}/payments", $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryItems(bool $activeOnly = true): array
    {
        return $this->json('GET', '/s3/api/v1/items', ['active_only' => $activeOnly]);
    }

    /**
     * @return array<string, mixed>
     */
    public function itemCategories(): array
    {
        return $this->json('GET', '/s3/api/v1/item-categories');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createItemCategory(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/item-categories', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateItemCategory(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/item-categories/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteItemCategory(int $id): array
    {
        return $this->json('DELETE', "/s3/api/v1/item-categories/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createInventoryItem(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/items', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateInventoryItem(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/items/{$id}", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function adjustStock(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/stock/adjustments', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function writeOffStock(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/stock/write-offs', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryItem(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/items/{$id}");
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryItemStock(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/items/{$id}/stock");
    }

    /**
     * @return array<string, mixed>
     */
    public function inventoryItemMovements(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/items/{$id}/movements", ['per_page' => 25]);
    }

    /**
     * @return array<string, mixed>
     */
    public function lowStockAlerts(): array
    {
        return $this->json('GET', '/s3/api/v1/stock/low-stock-alerts');
    }

    /**
     * @return array<string, mixed>
     */
    public function expiryAlerts(): array
    {
        return $this->json('GET', '/s3/api/v1/stock/expiry-alerts');
    }

    /**
     * @return array<string, mixed>
     */
    public function stockValuation(): array
    {
        return $this->json('GET', '/s3/api/v1/stock/valuation');
    }

    /**
     * @return array<string, mixed>
     */
    public function suppliers(): array
    {
        return $this->json('GET', '/s3/api/v1/suppliers');
    }

    /**
     * @return array<string, mixed>
     */
    public function supplier(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/suppliers/{$id}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function paySupplier(int $supplierId, array $payload, string $idempotencyKey): array
    {
        return $this->json('POST', "/s3/api/v1/suppliers/{$supplierId}/payments", $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSupplier(array $payload): array
    {
        return $this->json('POST', '/s3/api/v1/suppliers', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateSupplier(int $id, array $payload): array
    {
        return $this->json('PUT', "/s3/api/v1/suppliers/{$id}", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function goodsReceipt(int $id): array
    {
        return $this->json('GET', "/s3/api/v1/goods-receipts/{$id}");
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
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function receivePurchaseOrder(int $id, array $payload = []): array
    {
        return $this->json('POST', "/s3/api/v1/purchase-orders/{$id}/receive", $payload);
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
