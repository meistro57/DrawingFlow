<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    project: Object,
    customers: Array,
});

const form = useForm({
    project_number: props.project.project_number,
    name: props.project.name,
    customer_id: props.project.customer_id,
    description: props.project.description || '',
    address: props.project.address || '',
    city: props.project.city || '',
    state: props.project.state || '',
    zip: props.project.zip || '',
    start_date: props.project.start_date?.split('T')[0] || '',
    target_completion_date: props.project.target_completion_date?.split('T')[0] || '',
    status: props.project.status,
    notes: props.project.notes || '',
});

function submit() {
    form.put(route('projects.update', props.project.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit ${project.name}`" />

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('projects.show', project.id)" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to {{ project.name }}</Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit {{ project.name }}</h1>
                </div>

                <form @submit.prevent="submit" class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Project Number *</label>
                                <input v-model="form.project_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                                <p v-if="form.errors.project_number" class="mt-1 text-sm text-red-600">{{ form.errors.project_number }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer *</label>
                                <select v-model="form.customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">Select a customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }} ({{ customer.code }})
                                    </option>
                                </select>
                                <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status *</label>
                                <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="active">Active</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <input v-model="form.address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">City</label>
                                <input v-model="form.city" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State</label>
                                <input v-model="form.state" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ZIP</label>
                                <input v-model="form.zip" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input v-model="form.start_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Target Completion Date</label>
                                <input v-model="form.target_completion_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                                <p v-if="form.errors.target_completion_date" class="mt-1 text-sm text-red-600">{{ form.errors.target_completion_date }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <Link :href="route('projects.show', project.id)" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50">
                            Update Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
