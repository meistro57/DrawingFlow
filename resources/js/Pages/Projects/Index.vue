<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
  projects: Object,
});

function deleteProject(project) {
  if (confirm(`Are you sure you want to delete "${project.name}"?`)) {
    router.delete(route('projects.destroy', project.id));
  }
}
</script>

<template>
  <AppLayout>
    <Head title="Projects" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <p class="mt-1 text-sm text-gray-500">Manage construction projects.</p>
          </div>
          <Link
            :href="route('projects.create')"
            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
          >
            Add Project
          </Link>
        </div>

        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
          <table v-if="projects.data.length" class="min-w-full divide-y divide-gray-200">
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
                  Customer
                </th>
                <th
                  class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                >
                  Requests
                </th>
                <th
                  class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                >
                  Submittals
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
              <tr v-for="project in projects.data" :key="project.id" class="hover:bg-gray-50 transition-colors duration-150 ease-out">
                <td class="px-5 py-3 whitespace-nowrap text-sm font-medium">
                  <Link
                    :href="route('projects.show', project.id)"
                    class="text-primary-600 hover:text-primary-800"
                  >
                    {{ project.project_number }}
                  </Link>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">
                  {{ project.name }}
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                  <Link
                    v-if="project.customer"
                    :href="route('customers.show', project.customer.id)"
                    class="text-primary-600 hover:text-primary-800"
                  >
                    {{ project.customer.name }}
                  </Link>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ project.drawing_requests_count }}
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ project.submittals_count }}
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <StatusBadge :status="project.status" />
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3">
                  <Link
                    :href="route('projects.edit', project.id)"
                    class="text-primary-600 hover:text-primary-800"
                    >Edit</Link
                  >
                  <button @click="deleteProject(project)" class="text-red-600 hover:text-red-800">
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <EmptyState
            v-else
            title="No projects yet"
            description="Create your first project to get started."
          >
            <template #action>
              <Link
                :href="route('projects.create')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
              >
                Add Project
              </Link>
            </template>
          </EmptyState>

          <Pagination :links="projects.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
