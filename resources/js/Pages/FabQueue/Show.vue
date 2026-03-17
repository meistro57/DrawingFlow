<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  entry: Object,
  users: Array,
});

const showAssignModal = ref(false);
const assignUserId = ref(props.entry.assigned_to_user_id || '');
const shopNotes = ref(props.entry.shop_notes || '');
const notes = ref(props.entry.notes || '');

function assignUser() {
  router.post(
    route('fab-queue.assign', props.entry.id),
    {
      user_id: assignUserId.value,
    },
    {
      onSuccess: () => {
        showAssignModal.value = false;
      },
    }
  );
}

function markComplete() {
  if (confirm('Mark this entry as completed?')) {
    router.post(route('fab-queue.complete', props.entry.id));
  }
}

function saveNotes() {
  router.put(route('fab-queue.update-notes', props.entry.id), {
    shop_notes: shopNotes.value,
    notes: notes.value,
  });
}
</script>

<template>
  <AppLayout>
    <Head :title="entry.queue_number" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link :href="route('fab-queue.index')" class="text-sm text-gray-500 hover:text-gray-700"
            >&larr; Back to Fab Queue</Link
          >
        </div>

        <!-- Header -->
        <div class="flex items-start justify-between mb-8">
          <div>
            <div class="flex items-center space-x-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ entry.queue_number }}</h1>
              <StatusBadge :status="entry.status" />
              <span
                :class="[
                  entry.priority <= 1
                    ? 'bg-red-100 text-red-800'
                    : entry.priority <= 3
                      ? 'bg-orange-100 text-orange-800'
                      : entry.priority <= 5
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-gray-100 text-gray-600',
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                ]"
              >
                Priority {{ entry.priority }}
              </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">
              <Link
                v-if="entry.project"
                :href="route('projects.show', entry.project.id)"
                class="text-primary-600 hover:text-primary-800"
              >
                {{ entry.project.name }}
              </Link>
              <span v-if="entry.submittal?.customer">
                &middot; {{ entry.submittal.customer.name }}</span
              >
            </p>
          </div>
          <div class="flex space-x-3">
            <button
              v-if="entry.status === 'queued'"
              @click="showAssignModal = true"
              class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
            >
              Assign
            </button>
            <button
              v-if="entry.status === 'in_progress'"
              @click="markComplete"
              class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition"
            >
              Mark Complete
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Details -->
          <div class="lg:col-span-2 space-y-8">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Queue Details</h2>
              </div>
              <dl class="divide-y divide-gray-200">
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Submittal</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    <Link
                      v-if="entry.submittal"
                      :href="route('submittals.show', entry.submittal.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ entry.submittal.submittal_number }} (Rev {{ entry.submittal.revision }})
                    </Link>
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Drawing Request</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    <Link
                      v-if="entry.submittal?.drawing_request"
                      :href="route('drawing-requests.show', entry.submittal.drawing_request.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ entry.submittal.drawing_request.request_number }} -
                      {{ entry.submittal.drawing_request.title }}
                    </Link>
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ entry.assigned_to?.name || 'Unassigned' }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Assigned At</dt>
                  <dd class="text-sm text-gray-900 col-span-2">{{ entry.assigned_at || '-' }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Started At</dt>
                  <dd class="text-sm text-gray-900 col-span-2">{{ entry.started_at || '-' }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Completed At</dt>
                  <dd class="text-sm text-gray-900 col-span-2">{{ entry.completed_at || '-' }}</dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">Material Requirements</dt>
                  <dd class="text-sm text-gray-900 col-span-2 whitespace-pre-line">
                    {{ entry.material_requirements || '-' }}
                  </dd>
                </div>
                <div class="px-6 py-4 grid grid-cols-3 gap-4">
                  <dt class="text-sm font-medium text-gray-500">CNC Files Attached</dt>
                  <dd class="text-sm text-gray-900 col-span-2">
                    {{ entry.cnc_files_attached ? 'Yes' : 'No' }}
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Notes -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">Notes</h2>
              </div>
              <div class="p-6 space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Shop Notes</label>
                  <textarea
                    v-model="shopNotes"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    placeholder="Notes for the shop floor..."
                  ></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">General Notes</label>
                  <textarea
                    v-model="notes"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  ></textarea>
                </div>
                <div class="flex justify-end">
                  <button
                    @click="saveNotes"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
                  >
                    Save Notes
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar placeholder -->
          <div></div>
        </div>
      </div>
    </div>

    <!-- Assign Modal -->
    <Modal :show="showAssignModal" title="Assign to Fabricator" @close="showAssignModal = false">
      <div>
        <label class="block text-sm font-medium text-gray-700">Assign To</label>
        <select
          v-model="assignUserId"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        >
          <option value="">Select a fabricator</option>
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
          class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50"
        >
          Assign
        </button>
      </template>
    </Modal>
  </AppLayout>
</template>
