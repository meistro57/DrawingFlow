<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

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
        href: '/projects',
    },
    {
        name: 'Pending Requests',
        value: props.stats.pending_requests,
        color: 'bg-yellow-500',
        href: '/drawing-requests',
    },
    {
        name: 'In Progress',
        value: props.stats.in_progress_requests,
        color: 'bg-indigo-500',
        href: '/drawing-requests',
    },
    {
        name: 'Awaiting Approval',
        value: props.stats.awaiting_approval,
        color: 'bg-orange-500',
        href: '/submittals',
    },
    {
        name: 'Fab Queue',
        value: props.stats.fab_queue_count,
        color: 'bg-green-500',
        href: '/fab-queue',
    },
]);
</script>

<template>
    <AppLayout>
        <Head title="Dashboard" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Overview of your drawing workflow pipeline.
                        </p>
                    </div>
                    <Link
                        :href="route('drawing-requests.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
                    >
                        New Request
                    </Link>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-8">
                    <Link
                        v-for="stat in statCards"
                        :key="stat.name"
                        :href="stat.href"
                        class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200 hover:shadow-md transition-shadow"
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
                    </Link>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Drawing Requests -->
                    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Recent Drawing Requests</h2>
                            <Link :href="route('drawing-requests.index')" class="text-sm text-primary-600 hover:text-primary-800 font-medium">View All</Link>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <Link
                                v-for="request in recent_requests"
                                :key="request.id"
                                :href="route('drawing-requests.show', request.id)"
                                class="block px-6 py-4 hover:bg-gray-50 transition-colors"
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
                                    <StatusBadge :status="request.status" class="ml-3" />
                                </div>
                            </Link>
                            <div v-if="!recent_requests?.length" class="px-6 py-8 text-center text-sm text-gray-500">
                                No drawing requests yet.
                                <Link :href="route('drawing-requests.create')" class="block mt-2 text-primary-600 hover:text-primary-800 font-medium">Create your first request</Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Submittals -->
                    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">Recent Submittals</h2>
                            <Link :href="route('submittals.index')" class="text-sm text-primary-600 hover:text-primary-800 font-medium">View All</Link>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <Link
                                v-for="submittal in recent_submittals"
                                :key="submittal.id"
                                :href="route('submittals.show', submittal.id)"
                                class="block px-6 py-4 hover:bg-gray-50 transition-colors"
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
                                    <StatusBadge :status="submittal.status" class="ml-3" />
                                </div>
                            </Link>
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
