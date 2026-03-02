<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    priority: props.filters?.priority || '',
    search: props.filters?.search || '',
});

let searchTimer = null;

function applyFilters() {
    router.get(
        route('drawing-requests.index'),
        Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v !== '')),
        { preserveState: true, replace: true }
    );
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
}

watch(() => [localFilters.value.status, localFilters.value.priority], applyFilters);

function clearFilters() {
    localFilters.value = { status: '', priority: '', search: '' };
    router.get(route('drawing-requests.index'), {}, { preserveState: true, replace: true });
}

function deleteRequest(request) {
    if (confirm(`Are you sure you want to delete "${request.title}"?`)) {
        router.delete(route('drawing-requests.destroy', request.id));
    }
}

const priorityClasses = {
    urgent: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    normal: 'bg-blue-100 text-blue-800',
    low: 'bg-gray-100 text-gray-600',
};

const hasActiveFilters = () =>
    localFilters.value.status || localFilters.value.priority || localFilters.value.search;
</script>

<template>
    <AppLayout>
        <Head title="Drawing Requests" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Drawing Requests</h1>
                        <p class="mt-1 text-sm text-gray-500">Track and manage shop drawing requests.</p>
                    </div>
                    <Link
                        :href="route('drawing-requests.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
                    >
                        New Request
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 mb-4">
                    <div class="flex flex-wrap gap-3 items-center">
                        <input
                            v-model="localFilters.search"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search requests..."
                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm w-56"
                        />
                        <select
                            v-model="localFilters.status"
                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="ready_to_submit">Ready to Submit</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="on_hold">On Hold</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <select
                            v-model="localFilters.priority"
                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                        >
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                        <button
                            v-if="hasActiveFilters()"
                            @click="clearFilters"
                            class="text-sm text-gray-500 hover:text-gray-700 underline"
                        >
                            Clear filters
                        </button>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <table v-if="requests.data.length" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link :href="route('drawing-requests.show', request.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ request.request_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ request.title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link v-if="request.project" :href="route('projects.show', request.project.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ request.project.name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ request.assigned_to?.name || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[priorityClasses[request.priority] || 'bg-gray-100 text-gray-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                        {{ request.priority ? request.priority.charAt(0).toUpperCase() + request.priority.slice(1) : 'Normal' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <StatusBadge :status="request.status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <Link :href="route('drawing-requests.edit', request.id)" class="text-primary-600 hover:text-primary-800">Edit</Link>
                                    <button @click="deleteRequest(request)" class="text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="No drawing requests found" description="Try adjusting your filters or create your first drawing request.">
                        <template #action>
                            <Link :href="route('drawing-requests.create')" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                                New Request
                            </Link>
                        </template>
                    </EmptyState>

                    <Pagination :links="requests.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
