<script setup>
import { reactive } from 'vue';
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
</script>

<template>
    <AppLayout>
        <Head title="Customers" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
                        <p class="mt-1 text-sm text-gray-500">Manage your customer accounts.</p>
                    </div>
                    <Link
                        :href="route('customers.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
                    >
                        Add Customer
                    </Link>
                </div>

                <form @submit.prevent="importCustomers" class="mb-6 bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Import Customers (.csv)</label>
                            <input
                                type="file"
                                accept=".csv,text/csv"
                                class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-primary-600 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-primary-700"
                                @change="onFileChange"
                            >
                            <p class="mt-1 text-xs text-gray-500">
                                Required columns: <span class="font-mono">name</span>, <span class="font-mono">code</span>. Optional: email, phone, address, city, state, zip, country, notes, active.
                            </p>
                            <p v-if="importForm.errors.file" class="mt-1 text-sm text-red-600">{{ importForm.errors.file }}</p>
                        </div>
                        <div>
                            <button
                                type="submit"
                                :disabled="importForm.processing || !importForm.file"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50"
                            >
                                Import CSV
                            </button>
                        </div>
                    </div>
                </form>

                <form @submit.prevent="applyFilters" class="mb-6 bg-white shadow-sm rounded-lg border border-gray-200 p-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Search</label>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Name, code, or email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select
                                v-model="filterForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                                @change="applyFilters"
                            >
                                <option value="all">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort</label>
                            <select
                                v-model="filterForm.sort"
                                class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100"
                                @change="applyFilters"
                            >
                                <option value="latest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="name_asc">Name A-Z</option>
                                <option value="name_desc">Name Z-A</option>
                                <option value="code_asc">Code A-Z</option>
                                <option value="code_desc">Code Z-A</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition"
                                @click="resetFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </form>

                <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                    <table v-if="customers.data.length" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <Link :href="route('customers.show', customer.id)" class="text-primary-600 hover:text-primary-800">
                                        {{ customer.code }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ customer.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ customer.email || '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ customer.projects_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ customer.drawing_requests_count }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <StatusBadge :status="customer.active ? 'active' : 'on_hold'" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <Link :href="route('customers.edit', customer.id)" class="text-primary-600 hover:text-primary-800">Edit</Link>
                                    <button @click="deleteCustomer(customer)" class="text-red-600 hover:text-red-800">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <EmptyState
                        v-else
                        title="No customers yet"
                        description="Get started by adding your first customer."
                    >
                        <template #action>
                            <Link
                                :href="route('customers.create')"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition"
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
