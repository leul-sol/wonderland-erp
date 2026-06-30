import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePortalPermission() {
    const page = usePage();

    const permissions = computed(() => {
        const granted = page.props.auth?.permissions ?? [];

        return new Set(Array.isArray(granted) ? granted : []);
    });

    function hasAnyPermission(needles) {
        if (!needles?.length) {
            return true;
        }

        return needles.some((permission) => permissions.value.has(permission));
    }

    function canCheckInGuest() {
        return hasAnyPermission(['S3.hotel.checkinout.write', 'S3.hotel.reservations.write']);
    }

    function canBookReservation() {
        return hasAnyPermission(['S3.hotel.reservations.write']);
    }

    function canManageGuests() {
        return hasAnyPermission(['S3.hotel.guests.write']);
    }

    function canManageHotelSettings() {
        return hasAnyPermission(['S3.hotel.rooms.write']);
    }

    function canCreateGroupBooking() {
        return hasAnyPermission(['S3.hotel.group_bookings.create']);
    }

    function canGroupCheckIn() {
        return hasAnyPermission(['S3.hotel.group_bookings.check_in']);
    }

    function canSettleFolios() {
        return hasAnyPermission(['S3.hotel.folios.write']);
    }

    function canGroupCheckOut() {
        return hasAnyPermission(['S3.hotel.group_bookings.check_out']);
    }

    function canManageMenuCatalog() {
        return hasAnyPermission(['S3.restaurant.menu.write']);
    }

    function canCreateOrders() {
        return hasAnyPermission(['S3.restaurant.orders.write']);
    }

    function canManageInventoryItems() {
        return hasAnyPermission(['S3.inventory.items.write']);
    }

    function canReadPurchaseOrders() {
        return hasAnyPermission(['S3.inventory.purchase_orders.read']);
    }

    function canCreatePurchaseOrders() {
        return hasAnyPermission(['S3.inventory.purchase_orders.write']);
    }

    function canReadSuppliers() {
        return hasAnyPermission(['S3.inventory.suppliers.read']);
    }

    function canManageSuppliers() {
        return hasAnyPermission(['S3.inventory.suppliers.write']);
    }

    function canReadInventoryReports() {
        return hasAnyPermission(['S3.inventory.reports.read']);
    }

    function canAdjustStock() {
        return hasAnyPermission(['S3.inventory.stock.write']);
    }

    function canReadInventoryItems() {
        return hasAnyPermission(['S3.inventory.items.read']);
    }

    function canReadRestaurantMenu() {
        return hasAnyPermission(['S3.restaurant.menu.read']);
    }

    return {
        hasAnyPermission,
        canCheckInGuest,
        canBookReservation,
        canManageGuests,
        canManageHotelSettings,
        canCreateGroupBooking,
        canGroupCheckIn,
        canSettleFolios,
        canGroupCheckOut,
        canManageMenuCatalog,
        canCreateOrders,
        canManageInventoryItems,
        canReadPurchaseOrders,
        canCreatePurchaseOrders,
        canReadSuppliers,
        canManageSuppliers,
        canReadInventoryReports,
        canAdjustStock,
        canReadInventoryItems,
        canReadRestaurantMenu,
    };
}
