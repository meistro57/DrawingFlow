<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
  customers: Object,
  filters: Object,
});

const importForm = useForm({
  file: null,
});
const filterForm = reactive({
  search: props.filters?.search ?? '',
  status: props.filters?.status ?? 'all',
  sort: props.filters?.sort ?? 'latest',
});
const localTableForm = reactive({
  quickSearch: '',
  status: 'all',
  contact: 'all',
  activity: 'all',
  density: 'comfortable',
});
const sortState = ref({
  key: 'name',
  direction: 'asc',
});

const customerRows = computed(() => props.customers?.data ?? []);
const pageStats = computed(() => {
  const total = customerRows.value.length;
  const active = customerRows.value.filter((customer) => customer.active).length;
  const withEmail = customerRows.value.filter((customer) => customer.email).length;

  return {
    total,
    active,
    inactive: total - active,
    withEmail,
  };
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
const displayedCustomers = computed(() => {
  const quickSearch = localTableForm.quickSearch.trim().toLowerCase();

  const filtered = customerRows.value.filter((customer) => {
    if (localTableForm.status === 'active' && !customer.active) {
      return false;
    }

    if (localTableForm.status === 'inactive' && customer.active) {
      return false;
    }

    if (localTableForm.contact === 'has_email' && !customer.email) {
      return false;
    }

    if (localTableForm.contact === 'missing_email' && customer.email) {
      return false;
    }

    const activityTotal = (customer.projects_count ?? 0) + (customer.drawing_requests_count ?? 0);

    if (localTableForm.activity === 'high' && activityTotal < 10) {
      return false;
    }

    if (localTableForm.activity === 'medium' && (activityTotal < 4 || activityTotal > 9)) {
      return false;
    }

    if (localTableForm.activity === 'low' && activityTotal > 3) {
      return false;
    }

    if (quickSearch === '') {
      return true;
    }

    return [customer.name, customer.email ?? ''].join(' ').toLowerCase().includes(quickSearch);
  });

  const multiplier = sortState.value.direction === 'asc' ? 1 : -1;

  return [...filtered].sort((left, right) => {
    const leftValue = sortValue(left, sortState.value.key);
    const rightValue = sortValue(right, sortState.value.key);

    if (typeof leftValue === 'number' && typeof rightValue === 'number') {
      return (leftValue - rightValue) * multiplier;
    }

    return String(leftValue).localeCompare(String(rightValue)) * multiplier;
  });
});

function sortValue(customer, key) {
  if (key === 'projects_count') {
    return customer.projects_count ?? 0;
  }

  if (key === 'drawing_requests_count') {
    return customer.drawing_requests_count ?? 0;
  }

  if (key === 'active') {
    return customer.active ? 1 : 0;
  }

  return customer[key] ?? '';
}

function toggleSort(key) {
  if (sortState.value.key === key) {
    sortState.value.direction = sortState.value.direction === 'asc' ? 'desc' : 'asc';
    return;
  }

  sortState.value.key = key;
  sortState.value.direction = 'asc';
}

function sortIndicator(key) {
  if (sortState.value.key !== key) {
    return '↕';
  }

  return sortState.value.direction === 'asc' ? '↑' : '↓';
}

function deleteCustomer(customer) {
  if (confirm(`Are you sure you want to delete "${customer.name}"?`)) {
    router.delete(route('customers.destroy', customer.id));
  }
}

function onFileChange(event) {
  importForm.file = event.target.files[0] ?? null;
}

function importCustomers() {
  importForm.post(route('customers.import'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      importForm.reset('file');
    },
  });
}

function applyFilters() {
  router.get(
    route('customers.index'),
    {
      search: filterForm.search || undefined,
      status: filterForm.status !== 'all' ? filterForm.status : undefined,
      sort: filterForm.sort !== 'latest' ? filterForm.sort : undefined,
    },
    {
      preserveState: true,
      replace: true,
    }
  );
}

function resetFilters() {
  filterForm.search = '';
  filterForm.status = 'all';
  filterForm.sort = 'latest';
  applyFilters();
}

function resetQuickFilters() {
  localTableForm.quickSearch = '';
  localTableForm.status = 'all';
  localTableForm.contact = 'all';
  localTableForm.activity = 'all';
}

function activityLabel(customer) {
  const total = (customer.projects_count ?? 0) + (customer.drawing_requests_count ?? 0);

  if (total >= 10) {
    return 'High';
  }

  if (total >= 4) {
    return 'Medium';
  }

  return 'Low';
}
</script>

<template>
  <AppLayout>
    <Head title="Customers" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <p class="mt-1 text-sm text-gray-500">
              Manage customer accounts with rich table controls.
            </p>
          </div>
          <Link
            :href="route('customers.create')"
            class="inline-flex items-center px-4 py-2 rounded-md border border-transparent bg-primary-600 font-semibold text-xs uppercase tracking-widest text-white transition hover:bg-primary-700"
          >
            Add Customer
          </Link>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-gray-500">On This Page</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ pageStats.total }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-gray-500">Active</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-700">{{ pageStats.active }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-gray-500">Inactive</p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">{{ pageStats.inactive }}</p>
          </div>
          <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wider text-gray-500">With Email</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900">{{ pageStats.withEmail }}</p>
          </div>
        </div>

        <form
          @submit.prevent="importCustomers"
          class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">Import Customers (.csv)</label>
              <input
                type="file"
                accept=".csv,text/csv"
                class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-primary-700"
                @change="onFileChange"
              />
              <p class="mt-1 text-xs text-gray-500">
                Required columns: <span class="font-mono">name</span>. Optional: email, phone,
                address, city, state, zip, country, notes, active.
              </p>
              <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-600">
                {{ importForm.errors.file }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <a
                :href="route('customers.import.template')"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold text-xs uppercase tracking-widest text-gray-700 transition hover:bg-gray-50"
              >
                Download Template
              </a>
              <button
                type="submit"
                :disabled="importForm.processing || !importForm.file"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 font-semibold text-xs uppercase tracking-widest text-white transition hover:bg-primary-700 disabled:opacity-50"
              >
                Import CSV
              </button>
            </div>
          </div>
        </form>

        <form
          @submit.prevent="applyFilters"
          class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
        >
          <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Server Search</label>
              <input
                v-model="filterForm.search"
                type="text"
                placeholder="Name or email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Server Status</label>
              <select
                v-model="filterForm.status"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                @change="applyFilters"
              >
                <option value="all">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Server Sort</label>
              <select
                v-model="filterForm.sort"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                @change="applyFilters"
              >
                <option value="latest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="name_asc">Name A-Z</option>
                <option value="name_desc">Name Z-A</option>
              </select>
            </div>
            <div class="flex items-end gap-2">
              <button
                type="submit"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 font-semibold text-xs uppercase tracking-widest text-white transition hover:bg-primary-700"
              >
                Apply
              </button>
              <button
                type="button"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 font-semibold text-xs uppercase tracking-widest text-gray-700 transition hover:bg-gray-50"
                @click="resetFilters"
              >
                Reset
              </button>
            </div>
          </div>
        </form>

        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Quick Table Search</label>
              <input
                v-model="localTableForm.quickSearch"
                type="text"
                placeholder="Filter current page instantly"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Contact</label>
              <select
                v-model="localTableForm.contact"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="all">All</option>
                <option value="has_email">Has Email</option>
                <option value="missing_email">Missing Email</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Activity</label>
              <select
                v-model="localTableForm.activity"
                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="all">All</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
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
              @click="localTableForm.status = 'all'"
            >
              All
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.status === 'active'
                  ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.status = 'active'"
            >
              Active Only
            </button>
            <button
              type="button"
              class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-wider"
              :class="
                localTableForm.status === 'inactive'
                  ? 'border-amber-300 bg-amber-50 text-amber-700'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="localTableForm.status = 'inactive'"
            >
              Inactive Only
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

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <div v-if="customers.data.length" class="overflow-x-auto">
            <table class="min-w-[860px] divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    <button
                      type="button"
                      class="inline-flex items-center gap-1"
                      @click="toggleSort('name')"
                    >
                      Customer
                      <span class="text-gray-400">{{ sortIndicator('name') }}</span>
                    </button>
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    <button
                      type="button"
                      class="inline-flex items-center gap-1"
                      @click="toggleSort('projects_count')"
                    >
                      Projects
                      <span class="text-gray-400">{{ sortIndicator('projects_count') }}</span>
                    </button>
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    <button
                      type="button"
                      class="inline-flex items-center gap-1"
                      @click="toggleSort('drawing_requests_count')"
                    >
                      Requests
                      <span class="text-gray-400">{{
                        sortIndicator('drawing_requests_count')
                      }}</span>
                    </button>
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    <button
                      type="button"
                      class="inline-flex items-center gap-1"
                      @click="toggleSort('active')"
                    >
                      Status
                      <span class="text-gray-400">{{ sortIndicator('active') }}</span>
                    </button>
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    Activity
                  </th>
                  <th
                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr
                  v-for="customer in displayedCustomers"
                  :key="customer.id"
                  class="transition hover:bg-gray-50"
                >
                  <td :class="rowSpacingClass">
                    <div class="space-y-1">
                      <div class="flex items-center gap-2">
                        <Link
                          :href="route('customers.show', customer.id)"
                          class="text-sm font-semibold text-primary-600 hover:text-primary-800"
                        >
                          {{ customer.name }}
                        </Link>
                      </div>
                      <p class="text-xs text-gray-500">
                        {{ customer.email || 'No email on file' }}
                      </p>
                    </div>
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-600`">
                    {{ customer.projects_count }}
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap text-sm text-gray-600`">
                    {{ customer.drawing_requests_count }}
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap`">
                    <StatusBadge :status="customer.active ? 'active' : 'on_hold'" />
                  </td>
                  <td :class="`${rowSpacingClass} whitespace-nowrap`">
                    <span
                      class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                      :class="{
                        'bg-emerald-100 text-emerald-700': activityLabel(customer) === 'High',
                        'bg-amber-100 text-amber-700': activityLabel(customer) === 'Medium',
                        'bg-gray-100 text-gray-700': activityLabel(customer) === 'Low',
                      }"
                    >
                      {{ activityLabel(customer) }}
                    </span>
                  </td>
                  <td
                    :class="`${rowSpacingClass} whitespace-nowrap text-right text-sm font-medium`"
                  >
                    <div class="inline-flex items-center gap-3">
                      <Link
                        :href="route('customers.edit', customer.id)"
                        class="text-primary-600 hover:text-primary-800"
                        >Edit</Link
                      >
                      <button
                        @click="deleteCustomer(customer)"
                        class="text-red-600 hover:text-red-800"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="displayedCustomers.length === 0">
                  <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                    No rows match the current quick filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <EmptyState
            v-else
            title="No customers yet"
            description="Get started by adding your first customer."
          >
            <template #action>
              <Link
                :href="route('customers.create')"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 font-semibold text-xs uppercase tracking-widest text-white transition hover:bg-primary-700"
              >
                Add Customer
              </Link>
            </template>
          </EmptyState>

          <Pagination :links="customers.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
