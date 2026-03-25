<script setup>
import { computed, reactive } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
  submittals: Object,
});

const localTableForm = reactive({
  quickSearch: '',
  status: 'all',
  customerDelivery: 'all',
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

const displayedSubmittals = computed(() => {
  const quickSearch = localTableForm.quickSearch.trim().toLowerCase();

  return (props.submittals?.data ?? []).filter((submittal) => {
    if (localTableForm.status !== 'all' && submittal.status !== localTableForm.status) {
      return false;
    }

    if (localTableForm.customerDelivery === 'sent' && !submittal.submitted_at) {
      return false;
    }

    if (localTableForm.customerDelivery === 'not_sent' && submittal.submitted_at) {
      return false;
    }

    if (quickSearch === '') {
      return true;
    }

    return [
      submittal.submittal_number,
      submittal.drawing_request?.request_number,
      submittal.project?.name,
      submittal.submitted_by?.name,
    ]
      .join(' ')
      .toLowerCase()
      .includes(quickSearch);
  });
});

function resetQuickFilters() {
  localTableForm.quickSearch = '';
  localTableForm.status = 'all';
  localTableForm.customerDelivery = 'all';
}
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

        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Quick Table Search</label>
              <input
                v-model="localTableForm.quickSearch"
                type="text"
                placeholder="Submittal #, request #, project, submitter"
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
                <option value="draft">Draft</option>
                <option value="ready_to_submit">Ready to Submit</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="approved_as_noted">Approved as Noted</option>
                <option value="revise_and_resubmit">Revise & Resubmit</option>
                <option value="rejected">Rejected</option>
                <option value="field_verify_required">Field Verify Required</option>
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
              @click="localTableForm.customerDelivery = 'all'"
            >
              All Delivery States
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.customerDelivery === 'sent'
                  ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.customerDelivery = 'sent'"
            >
              Sent
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.customerDelivery === 'not_sent'
                  ? 'border-amber-300 bg-amber-50 text-amber-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.customerDelivery = 'not_sent'"
            >
              Not Sent
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
          <div v-if="submittals.data.length" class="overflow-x-auto">
            <table class="min-w-[860px] divide-y divide-gray-200">
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
                    Rev
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
                    Submitted By
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
                  v-for="submittal in displayedSubmittals"
                  :key="submittal.id"
                  class="transition hover:bg-gray-50"
                >
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm font-medium`">
                    <Link
                      :href="route('submittals.show', submittal.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ submittal.submittal_number }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    {{ submittal.revision }}
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    <Link
                      v-if="submittal.drawing_request"
                      :href="route('drawing-requests.show', submittal.drawing_request.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ submittal.drawing_request.request_number }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    <Link
                      v-if="submittal.project"
                      :href="route('projects.show', submittal.project.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ submittal.project.name }}
                    </Link>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-500`">
                    {{ submittal.submitted_by?.name || '-' }}
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap`">
                    <StatusBadge :status="submittal.status" />
                  </td>
                  <td
                    :class="`${rowSpacingClass} whitespace-nowrap text-right text-sm font-medium`"
                  >
                    <Link
                      :href="route('submittals.show', submittal.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      View
                    </Link>
                  </td>
                </tr>
                <tr v-if="displayedSubmittals.length === 0">
                  <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                    No rows match the current quick filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-else
            title="No submittals yet"
            description="Submittals are created from drawing requests."
          />

          <Pagination :links="submittals.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
