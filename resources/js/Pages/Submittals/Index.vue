<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    submittals: Object,
    filters: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

let searchTimer = null;

function applyFilters() {
    router.get(
        route('submittals.index'),
        Object.fromEntries(Object.entries(localFilters.value).filter(([, v]) => v !== '')),
        { preserveState: true, replace: true }
    );
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
}

watch(() => localFilters.value.status, applyFilters);

function clearFilters() {
    localFilters.value = { status: '', search: '' };
    router.get(route('submittals.index'), {}, { preserveState: true, replace: true });
}

const hasActiveFilters = () => localFilters.value.status || localFilters.value.search;
</script>

<template>
    <AppLayout>
        <Head title="Submittals" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Submittals</h1>
                    <p class="mt-1 text-sm text-gray-500">Track drawing submittals and approval status.</p>
                </div>

                <!-- Filters -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4 mb-4">
                    <div class="flex flex-wrap gap-3 items-center">
                        <input
                            v-model="localFilters.search"
                            @input="onSearchInput"
                            type="text"
                            placeholder="Search submittals..."
                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm w-56"
                        />
                        <select
                            v-model="localFilters.status"
                            class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="ready_to_submit">Ready to Submit</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="approved_as_noted">Approved as Noted</option>
                            <option value="revise_and_resubmit">Revise &amp; Resubmit</option>
                            <option value="rejected">Rejected</option>
                            <option value="field_verify_required">Field Verify Required</option>
                            <option value="superseded">Superseded</option>
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
                    <table v-if="submittals.data.length" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rev</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="submittal in submittals.data" :key="submittal.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link :href="route('submittals.show', submittal.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ submittal.submittal_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ submittal.revision }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link v-if="submittal.drawing_request" :href="route('drawing-requests.show', submittal.drawing_request.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ submittal.drawing_request.request_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link v-if="submittal.project" :href="route('projects.show', submittal.project.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ submittal.project.name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ submittal.submitted_by?.name || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <StatusBadge :status="submittal.status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('submittals.show', submittal.id)" class="text-primary-600 hover:text-primary-800">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="No submittals found" description="Submittals are created from drawing requests. Try adjusting your filters." />

                    <Pagination :links="submittals.links" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
