import { ref } from 'vue';
import { usePortalPermission } from './usePortalPermission';
import { useQueryModal } from './useQueryModal';

export function useCheckInModal(initialGuestId = null) {
    const showCheckInModal = ref(false);
    const checkInGuestId = ref(initialGuestId ?? '');

    const { canCheckInGuest } = usePortalPermission();

    function openCheckInModal(guestId = '') {
        checkInGuestId.value = guestId ?? '';
        showCheckInModal.value = true;
    }

    function closeCheckInModal() {
        showCheckInModal.value = false;
    }

    useQueryModal(showCheckInModal, {
        param: 'open',
        expected: 'check-in',
        when: () => canCheckInGuest(),
        onOpen(params) {
            checkInGuestId.value = params.get('guest_id') ?? '';
        },
    });

    return {
        showCheckInModal,
        checkInGuestId,
        openCheckInModal,
        closeCheckInModal,
        canCheckInGuest,
    };
}
