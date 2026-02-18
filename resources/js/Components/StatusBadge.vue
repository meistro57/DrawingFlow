<script setup>
const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const badgeClasses = {
    pending: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    ready_to_submit: 'bg-indigo-100 text-indigo-800',
    submitted: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    approved_as_noted: 'bg-lime-100 text-lime-800',
    revise_and_resubmit: 'bg-orange-100 text-orange-800',
    rejected: 'bg-red-100 text-red-800',
    draft: 'bg-gray-100 text-gray-600',
    on_hold: 'bg-purple-100 text-purple-800',
    cancelled: 'bg-red-50 text-red-600',
    queued: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    active: 'bg-green-100 text-green-800',
    field_verify_required: 'bg-purple-100 text-purple-800',
    superseded: 'bg-gray-100 text-gray-500',
};

function formatStatus(status) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <span
        :class="[badgeClasses[status] || 'bg-gray-100 text-gray-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']"
    >
        {{ formatStatus(status) }}
    </span>
</template>
