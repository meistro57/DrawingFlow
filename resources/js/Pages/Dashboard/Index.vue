<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    recent_requests: Array,
    recent_submittals: Array,
});

const statCards = computed(() => [
    {
        name: 'Active Projects',
        value: props.stats.active_projects,
        color: 'bg-blue-500',
    },
    {
        name: 'Pending Requests',
        value: props.stats.pending_requests,
        color: 'bg-yellow-500',
    },
    {
        name: 'In Progress',
        value: props.stats.in_progress_requests,
        color: 'bg-indigo-500',
    },
    {
        name: 'Awaiting Approval',
        value: props.stats.awaiting_approval,
        color: 'bg-orange-500',
    },
    {
        name: 'Fab Queue',
        value: props.stats.fab_queue_count,
        color: 'bg-green-500',
    },
]);

function statusBadgeClass(status) {
    const classes = {
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
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function formatStatus(status) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Overview of your drawing workflow pipeline.
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-8">
                    <div
                        v-for="stat in statCards"
                        :key="stat.name"
                        class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200"
                    >
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <div :class="[stat.color, 'w-3 h-3 rounded-full']"></div>
                                </div>
                                <div class="ml-3 w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        {{ stat.name }}
                                    </p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        {{ stat.value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Drawing Requests -->
                    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Recent Drawing Requests</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="request in recent_requests"
                                :key="request.id"
                                class="px-6 py-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ request.title }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ request.request_number }}
                                            <span v-if="request.project"> &middot; {{ request.project.name }}</span>
                                        </p>
                                    </div>
                                    <span
                                        :class="[statusBadgeClass(request.status), 'ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']"
                                    >
                                        {{ formatStatus(request.status) }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!recent_requests?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                                No drawing requests yet.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Submittals -->
                    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Recent Submittals</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="submittal in recent_submittals"
                                :key="submittal.id"
                                class="px-6 py-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ submittal.submittal_number }}
                                            <span class="text-gray-500 font-normal">Rev {{ submittal.revision }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span v-if="submittal.project">{{ submittal.project.name }}</span>
                                            <span v-if="submittal.customer"> &middot; {{ submittal.customer.name }}</span>
                                        </p>
                                    </div>
                                    <span
                                        :class="[statusBadgeClass(submittal.status), 'ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']"
                                    >
                                        {{ formatStatus(submittal.status) }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!recent_submittals?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                                No submittals yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
