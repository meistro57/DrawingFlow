<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
  customer: Object,
});

function deleteCustomer() {
  if (confirm(`Are you sure you want to delete "${props.customer.name}"?`)) {
    router.delete(route('customers.destroy', props.customer.id));
  }
}
</script>

<template>
  <AppLayout>
    <Head :title="customer.name" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link :href="route('customers.index')" class="text-sm text-gray-500 hover:text-gray-700"
            >&larr; Back to Customers</Link
          >
        </div>

        <!-- Header -->
        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ customer.name }}</h1>
              <StatusBadge :status="customer.active ? 'active' : 'on_hold'" />
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <Link
              :href="route('customers.edit', customer.id)"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition"
            >
              Edit
            </Link>
            <button
              @click="deleteCustomer"
              class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition"
            >
              Delete
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Details Card -->
          <div class="lg:sm:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Customer Details</h2>
            </div>
            <dl class="divide-y divide-gray-200">
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">{{ customer.email || '-' }}</dd>
              </div>
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">{{ customer.phone || '-' }}</dd>
              </div>
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Address</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">
                  <template v-if="customer.address">
                    {{ customer.address }}<br />
                    {{ customer.city }}, {{ customer.state }} {{ customer.zip }}
                    <template v-if="customer.country"><br />{{ customer.country }}</template>
                  </template>
                  <template v-else>-</template>
                </dd>
              </div>
              <div
                v-if="customer.notes"
                class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-gray-500">Notes</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2 whitespace-pre-line">
                  {{ customer.notes }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Stats Sidebar -->
          <div class="space-y-6">
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
              <h3 class="text-sm font-medium text-gray-500 mb-4">Quick Stats</h3>
              <dl class="space-y-4">
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-600">Projects</dt>
                  <dd class="text-sm font-semibold text-gray-900">{{ customer.projects_count }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-600">Drawing Requests</dt>
                  <dd class="text-sm font-semibold text-gray-900">
                    {{ customer.drawing_requests_count }}
                  </dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-600">Submittals</dt>
                  <dd class="text-sm font-semibold text-gray-900">
                    {{ customer.submittals_count }}
                  </dd>
                </div>
              </dl>
            </div>

            <Link
              :href="route('projects.create', { customer_id: customer.id })"
              class="block w-full text-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
            >
              Add Project
            </Link>
          </div>
        </div>

        <!-- Projects Table -->
        <div class="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Projects</h2>
          </div>
          <div v-if="customer.projects?.length" class="overflow-x-auto">
            <table class="min-w-[640px] divide-y divide-gray-200">
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
                    Name
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="project in customer.projects"
                  :key="project.id"
                  class="hover:bg-gray-50 transition-colors duration-150 ease-out"
                >
                  <td class="px-5 py-3 whitespace-nowrap text-sm">
                    <Link
                      :href="route('projects.show', project.id)"
                      class="text-primary-600 hover:text-primary-800 font-medium"
                    >
                      {{ project.project_number }}
                    </Link>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ project.name }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap">
                    <StatusBadge :status="project.status" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
            No projects for this customer yet.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
