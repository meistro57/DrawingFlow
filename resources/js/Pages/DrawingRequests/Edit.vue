<script setup>
import { computed } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    drawingRequest: Object,
    customers: Array,
    projects: Array,
    users: Array,
    drawingTypes: Array,
});

const form = useForm({
    project_id: props.drawingRequest.project_id,
    customer_id: props.drawingRequest.customer_id,
    assigned_to_user_id: props.drawingRequest.assigned_to_user_id || '',
    title: props.drawingRequest.title,
    description: props.drawingRequest.description || '',
    priority: props.drawingRequest.priority || 'normal',
    drawing_type: props.drawingRequest.drawing_type || '',
    job_number: props.drawingRequest.job_number || '',
    customer_address: props.drawingRequest.customer_address || '',
    required_date: props.drawingRequest.required_date?.split('T')[0] || '',
    notes: props.drawingRequest.notes || '',
});

const filteredProjects = computed(() => {
    if (form.customer_id) {
        return props.projects.filter(p => p.customer_id == form.customer_id);
    }
    return props.projects;
});

function onProjectChange() {
    const project = props.projects.find(p => p.id == form.project_id);
    if (project) {
        form.customer_id = project.customer_id;
    }
}

function submit() {
    form.put(route('drawing-requests.update', props.drawingRequest.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="`Edit ${drawingRequest.request_number}`" />

        <div class="py-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <Link :href="route('drawing-requests.show', drawingRequest.id)" class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to {{ drawingRequest.request_number }}</Link>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit {{ drawingRequest.request_number }}</h1>
                </div>

                <form @submit.prevent="submit" class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title *</label>
                            <input v-model="form.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
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
                                <label class="block text-sm font-medium text-gray-700">Project *</label>
                                <select v-model="form.project_id" @change="onProjectChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">Select a project</option>
                                    <option v-for="project in filteredProjects" :key="project.id" :value="project.id">
                                        {{ project.name }} ({{ project.project_number }})
                                    </option>
                                </select>
                                <p v-if="form.errors.project_id" class="mt-1 text-sm text-red-600">{{ form.errors.project_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Priority *</label>
                                <select v-model="form.priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Drawing Type *</label>
                                <select v-model="form.drawing_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">Select drawing type</option>
                                    <option v-for="drawingType in drawingTypes" :key="drawingType" :value="drawingType">
                                        {{ drawingType }}
                                    </option>
                                </select>
                                <p v-if="form.errors.drawing_type" class="mt-1 text-sm text-red-600">{{ form.errors.drawing_type }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Required Date</label>
                                <input v-model="form.required_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Assign To</label>
                                <select v-model="form.assigned_to_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">Unassigned</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.role }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Job Number</label>
                                <input v-model="form.job_number" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Customer Address</label>
                            <input v-model="form.customer_address" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <Link :href="route('drawing-requests.show', drawingRequest.id)" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50">
                            Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
