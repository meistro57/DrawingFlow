<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    entries: Object,
    users: Array,
});

const showAssignModal = ref(false);
const assignEntryId = ref(null);
const assignUserId = ref('');

function openAssign(entry) {
    assignEntryId.value = entry.id;
    assignUserId.value = entry.assigned_to_user_id || '';
    showAssignModal.value = true;
}

function assignUser() {
    router.post(route('fab-queue.assign', assignEntryId.value), {
        user_id: assignUserId.value,
    }, {
        onSuccess: () => {
            showAssignModal.value = false;
        },
    });
}

function markComplete(entry) {
    if (confirm('Mark this fab queue entry as completed?')) {
        router.post(route('fab-queue.complete', entry.id));
    }
}
</script>

<template>
    <AppLayout>
        <Head title="Fabrication Queue" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Fabrication Queue</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage fabrication jobs ordered by priority.</p>
                </div>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <table v-if="entries.data.length" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            entry.priority <= 1 ? 'bg-red-100 text-red-800' :
                                            entry.priority <= 3 ? 'bg-orange-100 text-orange-800' :
                                            entry.priority <= 5 ? 'bg-blue-100 text-blue-800' :
                                            'bg-gray-100 text-gray-600',
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
                                        ]"
                                    >
                                        P{{ entry.priority }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link :href="route('fab-queue.show', entry.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ entry.queue_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link v-if="entry.submittal?.drawing_request" :href="route('drawing-requests.show', entry.submittal.drawing_request.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ entry.submittal.drawing_request.request_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link v-if="entry.project" :href="route('projects.show', entry.project.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ entry.project.name }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ entry.assigned_to?.name || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <StatusBadge :status="entry.status" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <button
                                        v-if="entry.status === 'queued'"
                                        @click="openAssign(entry)"
                                        class="text-primary-600 hover:text-primary-800"
                                    >
                                        Assign
                                    </button>
                                    <button
                                        v-if="entry.status === 'in_progress'"
                                        @click="markComplete(entry)"
                                        class="text-green-600 hover:text-green-800"
                                    >
                                        Complete
                                    </button>
                                    <Link :href="route('fab-queue.show', entry.id)" class="text-primary-600 hover:text-primary-800">View</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState v-else title="Fabrication queue is empty" description="Approved submittals will appear here." />

                    <Pagination :links="entries.links" />
                </div>
            </div>
        </div>

        <!-- Assign Modal -->
        <Modal :show="showAssignModal" title="Assign to Fabricator" @close="showAssignModal = false">
            <div>
                <label class="block text-sm font-medium text-gray-700">Assign To</label>
                <select v-model="assignUserId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="">Select a fabricator</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                        {{ user.name }} ({{ user.role }})
                    </option>
                </select>
            </div>
            <template #footer>
                <button @click="showAssignModal = false" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button @click="assignUser" :disabled="!assignUserId" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50">
                    Assign
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
