<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    submittal: Object,
});

const showApprovalModal = ref(false);
const approvalForm = ref({
    approval_type: '',
    reviewer_name: '',
    reviewer_title: '',
    reviewer_company: '',
    reviewer_email: '',
    approval_notes: '',
    conditions: '',
});

function submitForApproval() {
    if (confirm('Submit this drawing for customer approval?')) {
        router.post(route('submittals.submit', props.submittal.id));
    }
}

function processApproval() {
    router.post(route('submittals.process-approval', props.submittal.id), approvalForm.value, {
        onSuccess: () => {
            showApprovalModal.value = false;
            approvalForm.value = {
                approval_type: '',
                reviewer_name: '',
                reviewer_title: '',
                reviewer_company: '',
                reviewer_email: '',
                approval_notes: '',
                conditions: '',
            };
        },
    });
}

function createRevision() {
    if (confirm('Create a new revision of this submittal?')) {
        router.post(route('submittals.create-revision', props.submittal.id));
    }
}

function deleteSubmittal() {
    if (confirm('Delete this submittal? This cannot be undone.')) {
        router.delete(route('submittals.destroy', props.submittal.id));
    }
}

const approvalTypeLabels = {
    approved: 'Approved',
    approved_as_noted: 'Approved as Noted',
    revise_and_resubmit: 'Revise & Resubmit',
    rejected: 'Rejected',
    field_verify_required: 'Field Verify Required',
};
</script>

<template>
    <AppLayout>
        <Head :title="submittal.submittal_number" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('submittals.index')" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Submittals</Link>
                </div>

                <!-- Header -->
                <div class="flex items-start justify-between mb-8">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h1 class="text-2xl font-bold text-gray-900">{{ submittal.submittal_number }}</h1>
                            <span class="text-lg text-gray-500">Rev {{ submittal.revision }}</span>
                            <StatusBadge :status="submittal.status" />
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            <Link v-if="submittal.drawing_request" :href="route('drawing-requests.show', submittal.drawing_request.id)" class="text-primary-600 hover:text-primary-800">
                                {{ submittal.drawing_request.request_number }} - {{ submittal.drawing_request.title }}
                            </Link>
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            <Link v-if="submittal.project" :href="route('projects.show', submittal.project.id)" class="text-primary-600 hover:text-primary-800">
                                {{ submittal.project.name }}
                            </Link>
                            <span v-if="submittal.customer"> &middot;
                                <Link :href="route('customers.show', submittal.customer.id)" class="text-primary-600 hover:text-primary-800">
                                    {{ submittal.customer.name }}
                                </Link>
                            </span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="['draft', 'ready_to_submit'].includes(submittal.status)"
                            @click="submitForApproval"
                            class="inline-flex items-center px-3 py-2 bg-blue-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-blue-700 transition uppercase tracking-widest"
                        >
                            Submit for Approval
                        </button>
                        <button
                            v-if="submittal.status === 'submitted'"
                            @click="showApprovalModal = true"
                            class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-green-700 transition uppercase tracking-widest"
                        >
                            Record Approval
                        </button>
                        <button
                            v-if="submittal.status === 'revise_and_resubmit'"
                            @click="createRevision"
                            class="inline-flex items-center px-3 py-2 bg-orange-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-orange-700 transition uppercase tracking-widest"
                        >
                            Create Revision
                        </button>
                        <button
                            @click="deleteSubmittal"
                            class="inline-flex items-center px-3 py-2 border border-red-300 rounded-md text-xs font-semibold text-red-700 bg-white hover:bg-red-50 transition uppercase tracking-widest"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Details -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-medium text-gray-900">Submittal Details</h2>
                            </div>
                            <dl class="divide-y divide-gray-200">
                                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Purpose</dt>
                                    <dd class="text-sm text-gray-900 col-span-2">{{ submittal.purpose || '-' }}</dd>
                                </div>
                                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Discipline</dt>
                                    <dd class="text-sm text-gray-900 col-span-2">{{ submittal.drawing_discipline || '-' }}</dd>
                                </div>
                                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Submitted At</dt>
                                    <dd class="text-sm text-gray-900 col-span-2">{{ submittal.submitted_at || 'Not yet submitted' }}</dd>
                                </div>
                                <div v-if="submittal.approval_type" class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Approval Type</dt>
                                    <dd class="text-sm text-gray-900 col-span-2">
                                        <StatusBadge :status="submittal.approval_type" />
                                    </dd>
                                </div>
                                <div v-if="submittal.approval_notes" class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Approval Notes</dt>
                                    <dd class="text-sm text-gray-900 col-span-2 whitespace-pre-line">{{ submittal.approval_notes }}</dd>
                                </div>
                                <div v-if="submittal.notes" class="px-6 py-4 grid grid-cols-3 gap-4">
                                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd class="text-sm text-gray-900 col-span-2 whitespace-pre-line">{{ submittal.notes }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Approval History -->
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-medium text-gray-900">Approval History</h2>
                            </div>
                            <div v-if="submittal.approvals?.length" class="divide-y divide-gray-200">
                                <div v-for="approval in submittal.approvals" :key="approval.id" class="px-6 py-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <StatusBadge :status="approval.approval_type" />
                                        <span class="text-xs text-gray-500">{{ approval.approved_at }}</span>
                                    </div>
                                    <div class="text-sm text-gray-700">
                                        <p v-if="approval.reviewer_name">
                                            <span class="font-medium">{{ approval.reviewer_name }}</span>
                                            <span v-if="approval.reviewer_title">, {{ approval.reviewer_title }}</span>
                                            <span v-if="approval.reviewer_company"> at {{ approval.reviewer_company }}</span>
                                        </p>
                                        <p v-if="approval.approval_notes" class="mt-1 whitespace-pre-line">{{ approval.approval_notes }}</p>
                                        <p v-if="approval.conditions" class="mt-1 text-orange-700">Conditions: {{ approval.conditions }}</p>
                                        <p class="mt-1 text-xs text-gray-400">Recorded by {{ approval.created_by?.name || 'Unknown' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                                No approval records yet.
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-4">Submitted By</h3>
                            <p class="text-sm font-medium text-gray-900">{{ submittal.submitted_by?.name || '-' }}</p>
                        </div>

                        <!-- Fab Queue -->
                        <div v-if="submittal.fab_queue_entry" class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-4">Fabrication Queue</h3>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Queue #</dt>
                                    <dd class="text-sm font-medium">
                                        <Link :href="route('fab-queue.show', submittal.fab_queue_entry.id)" class="text-primary-600 hover:text-primary-800">
                                            {{ submittal.fab_queue_entry.queue_number }}
                                        </Link>
                                    </dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Status</dt>
                                    <dd><StatusBadge :status="submittal.fab_queue_entry.status" /></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Priority</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ submittal.fab_queue_entry.priority }}</dd>
                                </div>
                                <div v-if="submittal.fab_queue_entry.assigned_to" class="flex justify-between">
                                    <dt class="text-sm text-gray-600">Assigned To</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ submittal.fab_queue_entry.assigned_to.name }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Modal -->
        <Modal :show="showApprovalModal" title="Record Approval Response" max-width="xl" @close="showApprovalModal = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Approval Type *</label>
                    <select v-model="approvalForm.approval_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                        <option value="">Select type</option>
                        <option v-for="(label, value) in approvalTypeLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reviewer Name</label>
                        <input v-model="approvalForm.reviewer_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reviewer Title</label>
                        <input v-model="approvalForm.reviewer_title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reviewer Company</label>
                        <input v-model="approvalForm.reviewer_company" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reviewer Email</label>
                        <input v-model="approvalForm.reviewer_email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Approval Notes</label>
                    <textarea v-model="approvalForm.approval_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Conditions</label>
                    <textarea v-model="approvalForm.conditions" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Any conditions for approval..."></textarea>
                </div>
            </div>
            <template #footer>
                <button @click="showApprovalModal = false" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button @click="processApproval" :disabled="!approvalForm.approval_type" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50">
                    Record Approval
                </button>
            </template>
        </Modal>
    </AppLayout>
</template>
