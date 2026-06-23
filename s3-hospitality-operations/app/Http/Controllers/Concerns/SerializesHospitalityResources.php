<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Folio;
use App\Models\GroupBooking;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\PurchaseOrder;
use App\Models\Reservation;
use App\Models\RestaurantOrder;
use App\Models\Room;

trait SerializesHospitalityResources
{
    protected function roomPayload(Room $room): array
    {
        $room->loadMissing('roomType');

        return [
            'id' => $room->id,
            'room_number' => $room->room_number,
            'floor' => $room->floor,
            'status' => $room->status,
            'room_type' => $room->roomType ? [
                'id' => $room->roomType->id,
                'code' => $room->roomType->code,
                'name' => $room->roomType->name,
                'base_rate' => (string) $room->roomType->base_rate,
            ] : null,
        ];
    }

    protected function reservationPayload(Reservation $reservation): array
    {
        $reservation->loadMissing(['room', 'roomType', 'folio']);

        return [
            'id' => $reservation->id,
            'confirmation_code' => $reservation->confirmation_code,
            'guest_name' => $reservation->guest_name,
            'guest_email' => $reservation->guest_email,
            'guest_phone' => $reservation->guest_phone,
            'status' => $reservation->status,
            'check_in_date' => $reservation->check_in_date?->toDateString(),
            'check_out_date' => $reservation->check_out_date?->toDateString(),
            'checked_in_at' => $reservation->checked_in_at?->toIso8601String(),
            'checked_out_at' => $reservation->checked_out_at?->toIso8601String(),
            'adults' => $reservation->adults,
            'room_type_id' => $reservation->room_type_id,
            'room_id' => $reservation->room_id,
            'room' => $reservation->room ? $this->roomPayload($reservation->room) : null,
            'folio_id' => $reservation->folio?->id,
            'group_booking_id' => $reservation->group_booking_id,
        ];
    }

    protected function groupBookingPayload(GroupBooking $group): array
    {
        $group->loadMissing('reservations.roomType', 'reservations.room');

        return [
            'id' => $group->id,
            'group_code' => $group->group_code,
            'group_name' => $group->group_name,
            'contact_name' => $group->contact_name,
            'contact_email' => $group->contact_email,
            'contact_phone' => $group->contact_phone,
            'check_in_date' => $group->check_in_date?->toDateString(),
            'check_out_date' => $group->check_out_date?->toDateString(),
            'status' => $group->status,
            'room_count' => $group->room_count,
            'reservations' => $group->reservations->map(fn ($r) => $this->reservationPayload($r))->values(),
        ];
    }

    protected function folioPayload(Folio $folio): array
    {
        $folio->loadMissing('lines');

        return [
            'id' => $folio->id,
            'reservation_id' => $folio->reservation_id,
            'status' => $folio->status,
            'total_charges' => (string) $folio->total_charges,
            'total_payments' => (string) $folio->total_payments,
            'balance' => number_format($folio->balance(), 2, '.', ''),
            'currency' => $folio->currency,
            'settled_at' => $folio->settled_at?->toIso8601String(),
            'lines' => $folio->lines->map(fn ($line) => [
                'id' => $line->id,
                'line_type' => $line->line_type,
                'charge_category' => $line->charge_category,
                'description' => $line->description,
                'subtotal' => $line->subtotal !== null ? (string) $line->subtotal : null,
                'service_charge_amount' => $line->service_charge_amount !== null ? (string) $line->service_charge_amount : null,
                'vat_amount' => $line->vat_amount !== null ? (string) $line->vat_amount : null,
                'amount' => (string) $line->amount,
                'payment_method' => $line->payment_method,
                's4_journal_entry_id' => $line->s4_journal_entry_id,
                'posted_at' => $line->posted_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    protected function inventoryItemPayload(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'sku' => $item->sku,
            'name' => $item->name,
            'unit' => $item->unit,
            'unit_cost' => (string) $item->unit_cost,
            'quantity_on_hand' => (string) $item->quantity_on_hand,
            'reorder_level' => (string) $item->reorder_level,
            'is_active' => $item->is_active,
        ];
    }

    protected function menuItemPayload(MenuItem $item): array
    {
        $item->loadMissing('ingredients');

        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'price' => (string) $item->price,
            'category' => $item->category,
            'is_active' => $item->is_active,
            'ingredients' => $item->ingredients->map(fn ($ingredient) => [
                'inventory_item_id' => $ingredient->id,
                'sku' => $ingredient->sku,
                'name' => $ingredient->name,
                'quantity' => (string) $ingredient->pivot->quantity,
            ])->values()->all(),
        ];
    }

    protected function purchaseOrderPayload(PurchaseOrder $po): array
    {
        $po->loadMissing('lines.inventoryItem');

        return [
            'id' => $po->id,
            'po_number' => $po->po_number,
            'vendor_name' => $po->vendor_name,
            'status' => $po->status,
            'total_amount' => (string) $po->total_amount,
            'approval_tier' => $po->approval_tier,
            'approved_by' => $po->approved_by,
            'approved_at' => $po->approved_at?->toIso8601String(),
            'received_at' => $po->received_at?->toIso8601String(),
            's4_journal_entry_id' => $po->s4_journal_entry_id,
            'lines' => $po->lines->map(fn ($line) => [
                'id' => $line->id,
                'inventory_item_id' => $line->inventory_item_id,
                'sku' => $line->inventoryItem?->sku,
                'name' => $line->inventoryItem?->name,
                'quantity' => (string) $line->quantity,
                'unit_cost' => (string) $line->unit_cost,
                'line_total' => (string) $line->line_total,
            ])->values()->all(),
        ];
    }

    protected function orderPayload(RestaurantOrder $order): array
    {
        $order->loadMissing(['lines.menuItem', 'folio']);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'folio_id' => $order->folio_id,
            'employee_consumption_period_id' => $order->employee_consumption_period_id,
            'status' => $order->status,
            'payment_context' => $order->payment_context,
            'subtotal' => (string) $order->subtotal,
            'service_charge_amount' => (string) ($order->service_charge_amount ?? 0),
            'vat_amount' => (string) ($order->vat_amount ?? 0),
            'total_amount' => (string) ($order->total_amount ?? $order->subtotal),
            'cogs_total' => (string) $order->cogs_total,
            'revenue_journal_entry_id' => $order->revenue_journal_entry_id,
            'cogs_journal_entry_id' => $order->cogs_journal_entry_id,
            'finalized_at' => $order->finalized_at?->toIso8601String(),
            'lines' => $order->lines->map(fn ($line) => [
                'id' => $line->id,
                'menu_item_id' => $line->menu_item_id,
                'menu_item_code' => $line->menuItem?->code,
                'menu_item_name' => $line->menuItem?->name,
                'quantity' => $line->quantity,
                'unit_price' => (string) $line->unit_price,
                'line_total' => (string) $line->line_total,
            ])->values()->all(),
        ];
    }
}
