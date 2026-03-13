<script setup>
import { computed, reactive, ref } from 'vue';
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
const localTableForm = reactive({
  quickSearch: '',
  status: 'all',
  assignee: 'all',
  density: 'comfortable',
});

const rowSpacingClass = computed(() => {
  if (localTableForm.density === 'compact') {
    return 'px-4 py-2';
  }

  if (localTableForm.density === 'spacious') {
    return 'px-6 py-5';
  }

  return 'px-5 py-3';
});

const displayedEntries = computed(() => {
  const quickSearch = localTableForm.quickSearch.trim().toLowerCase();

  return (props.entries?.data ?? []).filter((entry) => {
    if (localTableForm.status !== 'all' && entry.status !== localTableForm.status) {
      return false;
    }

    if (localTableForm.assignee === 'assigned' && !entry.assigned_to?.name) {
      return false;
    }

    if (localTableForm.assignee === 'unassigned' && entry.assigned_to?.name) {
      return false;
    }

    if (quickSearch === '') {
      return true;
    }

    return [
      entry.queue_number,
      entry.project?.name,
      entry.submittal?.drawing_request?.request_number,
      entry.assigned_to?.name,
    ]
      .join(' ')
      .toLowerCase()
      .includes(quickSearch);
  });
});

function openAssign(entry) {
  assignEntryId.value = entry.id;
  assignUserId.value = entry.assigned_to_user_id || '';
  showAssignModal.value = true;
}

function assignUser() {
  router.post(
    route('fab-queue.assign', assignEntryId.value),
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

function markComplete(entry) {
  if (confirm('Mark this fab queue entry as completed?')) {
    router.post(route('fab-queue.complete', entry.id));
  }
}

function resetQuickFilters() {
  localTableForm.quickSearch = '';
  localTableForm.status = 'all';
  localTableForm.assignee = 'all';
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

        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Quick Table Search</label>
              <input
                v-model="localTableForm.quickSearch"
                type="text"
                placeholder="Queue #, project, request, assignee"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Status</label>
              <select
                v-model="localTableForm.status"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="all">All</option>
                <option value="queued">Queued</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="on_hold">On Hold</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Density</label>
              <select
                v-model="localTableForm.density"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="compact">Compact</option>
                <option value="comfortable">Comfortable</option>
                <option value="spacious">Spacious</option>
              </select>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
              @click="localTableForm.assignee = 'all'"
            >
              All Assignees
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.assignee === 'assigned'
                  ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.assignee = 'assigned'"
            >
              Assigned
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.assignee === 'unassigned'
                  ? 'border-amber-300 bg-amber-50 text-amber-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.assignee = 'unassigned'"
            >
              Unassigned
            </button>
            <button
              type="button"
              class="ml-auto inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
              @click="resetQuickFilters"
            >
              Reset Quick Filters
            </button>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
          <div v-if="entries.data.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Priority
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Queue #
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Request
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Project
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Assigned To
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Status
                  </th>
                  <th
                    class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="entry in displayedEntries"
                  :key="entry.id"
                  class="transition hover:bg-gray-50"
                >
                  <td :class="`${rowSpacingClass} whitespace-nowrap`">
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
                      P{{ entry.priority }}
                    </span>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm font-medium`">
                    <Link
                      :href="route('fab-queue.show', entry.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ entry.queue_number }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    <Link
                      v-if="entry.submittal?.drawing_request"
                      :href="route('drawing-requests.show', entry.submittal.drawing_request.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ entry.submittal.drawing_request.request_number }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    <Link
                      v-if="entry.project"
                      :href="route('projects.show', entry.project.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ entry.project.name }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    {{ entry.assigned_to?.name || 'Unassigned' }}
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap`">
                    <StatusBadge :status="entry.status" />
                  </td>
                  <td
                    :class="`${rowSpacingClass} whitespace-nowrap text-right text-sm font-medium space-x-2`"
                  >
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
                    <Link
                      :href="route('fab-queue.show', entry.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      View
                    </Link>
                  </td>
                </tr>
                <tr v-if="displayedEntries.length === 0">
                  <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                    No rows match the current quick filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-else
            title="Fabrication queue is empty"
            description="Approved submittals will appear here."
          />

          <Pagination :links="entries.links" />
        </div>
      </div>
    </div>

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
