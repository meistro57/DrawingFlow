import { computed } from 'vue';

export function useSubmittalStatus(submittal) {
    const statusColor = computed(() => {
        const colors = {
            draft: 'gray',
            ready_to_submit: 'indigo',
            submitted: 'blue',
            approved: 'green',
            approved_as_noted: 'yellow',
            revise_and_resubmit: 'orange',
            rejected: 'red',
            field_verify_required: 'purple',
            superseded: 'gray',
        };
        return colors[submittal.status] || 'gray';
    });

    const statusIcon = computed(() => {
        const icons = {
            draft: 'PencilIcon',
            ready_to_submit: 'DocumentCheckIcon',
            submitted: 'PaperAirplaneIcon',
            approved: 'CheckCircleIcon',
            approved_as_noted: 'ExclamationCircleIcon',
            revise_and_resubmit: 'ArrowPathIcon',
            rejected: 'XCircleIcon',
            field_verify_required: 'MapPinIcon',
            superseded: 'ArchiveBoxIcon',
        };
        return icons[submittal.status] || 'QuestionMarkCircleIcon';
    });

    const canSubmit = computed(() => {
        return submittal.status === 'ready_to_submit'
            && submittal.permissions?.can_submit;
    });

    const canEdit = computed(() => {
        return ['draft', 'ready_to_submit'].includes(submittal.status)
            && submittal.permissions?.can_edit;
    });

    return {
        statusColor,
        statusIcon,
        canSubmit,
        canEdit,
    };
}
