<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
  requests: Object,
});

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
</script>

<template>
  <AppLayout>
    <Head title="Drawing Requests" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Drawing Requests</h1>
            <p class="mt-1 text-sm text-gray-500">Track and manage shop drawing requests.</p>
          </div>
          <Link
            :href="route('drawing-requests.create')"
            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
          >
            New Request
          </Link>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <div v-if="requests.data.length" class="overflow-x-auto">
            <table class="min-w-[720px] divide-y divide-gray-200">
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
                    Title
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
                    Priority
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
                  v-for="request in requests.data"
                  :key="request.id"
                  class="hover:bg-gray-50 transition-colors duration-150 ease-out"
                >
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-medium">
                    <Link
                      :href="route('drawing-requests.show', request.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ request.request_number }}
                    </Link>
                  </td>
                  <td class="px-5 py-3 text-sm text-gray-900 max-w-xs truncate">
                    {{ request.title }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                    <Link
                      v-if="request.project"
                      :href="route('projects.show', request.project.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ request.project.name }}
                    </Link>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                    {{ request.assigned_to?.name || 'Unassigned' }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap">
                    <span
                      :class="[
                        priorityClasses[request.priority] || 'bg-gray-100 text-gray-800',
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      ]"
                    >
                      {{
                        request.priority
                          ? request.priority.charAt(0).toUpperCase() + request.priority.slice(1)
                          : 'Normal'
                      }}
                    </span>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap">
                    <StatusBadge :status="request.status" />
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                    <Link
                      :href="route('drawing-requests.edit', request.id)"
                      class="text-primary-600 hover:text-primary-800"
                      >Edit</Link
                    >
                    <button @click="deleteRequest(request)" class="text-red-600 hover:text-red-800">
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-else
            title="No drawing requests yet"
            description="Create your first drawing request to get started."
          >
            <template #action>
              <Link
                :href="route('drawing-requests.create')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
              >
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
