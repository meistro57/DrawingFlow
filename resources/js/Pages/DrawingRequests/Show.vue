<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { formatDisplayDate } from '@/utils/dateFormatting';

const props = defineProps({
  drawingRequest: Object,
  users: Array,
});

const showAssignModal = ref(false);
const assignUserId = ref('');

function assignUser() {
  router.post(
    route('drawing-requests.assign', props.drawingRequest.id),
    {
      user_id: assignUserId.value,
    },
    {
      onSuccess: () => {
        showAssignModal.value = false;
        assignUserId.value = '';
      },
    }
  );
}

function markReady() {
  if (confirm('Mark this request as ready to submit?')) {
    router.post(route('drawing-requests.mark-ready', props.drawingRequest.id));
  }
}

function cancelRequest() {
  if (confirm('Are you sure you want to cancel this request?')) {
    router.post(route('drawing-requests.cancel', props.drawingRequest.id));
  }
}

function holdRequest() {
  if (confirm('Put this request on hold?')) {
    router.post(route('drawing-requests.hold', props.drawingRequest.id));
  }
}

function deleteRequest() {
  if (confirm(`Delete "${props.drawingRequest.title}"? This cannot be undone.`)) {
    router.delete(route('drawing-requests.destroy', props.drawingRequest.id));
  }
}

function createSubmittal() {
  router.post(route('submittals.create-from-request', props.drawingRequest.id));
}

const priorityClasses = {
  urgent: 'bg-red-100 text-red-800',
  high: 'bg-orange-100 text-orange-800',
  normal: 'bg-blue-100 text-blue-800',
  low: 'bg-gray-100 text-gray-600',
};
</script>

<template>
  <AppLayout>
    <Head :title="drawingRequest.request_number" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link
            :href="route('drawing-requests.index')"
            class="text-sm text-gray-500 hover:text-gray-700"
            >&larr; Back to Drawing Requests</Link
          >
        </div>

        <!-- Header -->
        <div class="flex items-start justify-between mb-8">
          <div>
            <div class="flex items-center space-x-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ drawingRequest.request_number }}</h1>
              <StatusBadge :status="drawingRequest.status" />
              <span
                :class="[
                  priorityClasses[drawingRequest.priority] || 'bg-gray-100 text-gray-800',
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                ]"
              >
                {{
                  drawingRequest.priority
                    ? drawingRequest.priority.charAt(0).toUpperCase() +
                      drawingRequest.priority.slice(1)
                    : 'Normal'
                }}
              </span>
            </div>
            <p class="mt-1 text-lg text-gray-700">{{ drawingRequest.title }}</p>
            <p class="mt-1 text-sm text-gray-500">
              <Link
                v-if="drawingRequest.project"
                :href="route('projects.show', drawingRequest.project.id)"
                class="text-primary-600 hover:text-primary-800"
              >
                {{ drawingRequest.project.name }}
              </Link>
              <span v-if="drawingRequest.customer">
                &middot;
                <Link
                  :href="route('customers.show', drawingRequest.customer.id)"
                  class="text-primary-600 hover:text-primary-800"
                >
                  {{ drawingRequest.customer.name }}
                </Link>
              </span>
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-if="drawingRequest.status === 'pending' || drawingRequest.status === 'in_progress'"
              @click="showAssignModal = true"
              class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition uppercase tracking-widest"
            >
              Assign
            </button>
            <button
              v-if="drawingRequest.status === 'in_progress'"
              @click="markReady"
              class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-indigo-700 transition uppercase tracking-widest"
            >
              Mark Ready
            </button>
            <button
              v-if="drawingRequest.status === 'ready_to_submit'"
              @click="createSubmittal"
              class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md text-xs font-semibold text-white hover:bg-green-700 transition uppercase tracking-widest"
            >
              Create Submittal
            </button>
            <button
              v-if="!['cancelled', 'on_hold'].includes(drawingRequest.status)"
              @click="holdRequest"
              class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition uppercase tracking-widest"
            >
              Hold
            </button>
            <Link
              :href="route('drawing-requests.edit', drawingRequest.id)"
              class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 transition uppercase tracking-widest"
            >
              Edit
            </Link>
            <button
              @click="cancelRequest"
              class="inline-flex items-center px-3 py-2 border border-red-300 rounded-md text-xs font-semibold text-red-700 bg-white hover:bg-red-50 transition uppercase tracking-widest"
            >
              Cancel
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Details -->
          <div class="lg:col-span-2 space-y-8">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Request Details</h2>
              </div>
              <dl class="divide-y divide-gray-200">
                <div v-if="drawingRequest.description" class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Description</dt>
                  <dd class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                    {{ drawingRequest.description }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Drawing Type</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ drawingRequest.drawing_type || '-' }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Job Number</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    <Link
                      v-if="drawingRequest.project"
                      :href="route('projects.show', drawingRequest.project.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ drawingRequest.job_number || drawingRequest.project.project_number || '-' }}
                    </Link>
                    <span v-else>{{ drawingRequest.job_number || '-' }}</span>
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Customer Address</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ drawingRequest.customer_address || '-' }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Required Date</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ formatDisplayDate(drawingRequest.required_date) }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Requested Date</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ formatDisplayDate(drawingRequest.requested_date) }}
                  </dd>
                </div>
                <div v-if="drawingRequest.notes" class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Notes</dt>
                  <dd class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                    {{ drawingRequest.notes }}
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Submittals -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Submittals</h2>
              </div>
              <table
                v-if="drawingRequest.submittals?.length"
                class="min-w-full divide-y divide-gray-200"
              >
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Number
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Revision
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Submitted By
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr
                    v-for="submittal in drawingRequest.submittals"
                    :key="submittal.id"
                    class="hover:bg-gray-50 transition-colors duration-150 ease-out"
                  >
                    <td class="px-5 py-3 text-sm">
                      <Link
                        :href="route('submittals.show', submittal.id)"
                        class="text-primary-600 hover:text-primary-800 font-medium"
                      >
                        {{ submittal.submittal_number }}
                      </Link>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-900">Rev {{ submittal.revision }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500">
                      {{ submittal.submitted_by?.name || '-' }}
                    </td>
                    <td class="px-5 py-3"><StatusBadge :status="submittal.status" /></td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                No submittals created yet.
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
              <h3 class="text-sm font-medium text-gray-500 mb-4">People</h3>
              <dl class="space-y-4">
                <div>
                  <dt class="text-xs text-gray-500">Requested By</dt>
                  <dd class="text-sm font-medium text-gray-900">
                    {{ drawingRequest.requested_by?.name || '-' }}
                  </dd>
                </div>
                <div>
                  <dt class="text-xs text-gray-500">Assigned To</dt>
                  <dd class="text-sm font-medium text-gray-900">
                    {{ drawingRequest.assigned_to?.name || 'Unassigned' }}
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Assign Modal -->
    <Modal :show="showAssignModal" title="Assign Request" @close="showAssignModal = false">
      <div>
        <label class="block text-sm font-medium text-gray-700">Assign To</label>
        <select
          v-model="assignUserId"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        >
          <option value="">Select a user</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }} ({{ user.role }})
          </option>
        </select>
      </div>
      <template #footer>
        <button
          @click="showAssignModal = false"
          class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition"
        >
          Cancel
        </button>
        <button
          @click="assignUser"
          :disabled="!assignUserId"
          class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm disabled:opacity-50"
        >
          Assign
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>
