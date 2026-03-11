<script setup>
import { reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    users: Object,
    roleOptions: Array,
});

const forms = reactive({});
const savingByUser = reactive({});
const createForm = useForm({
    name: '',
    email: '',
    role: 'detailer',
    title: '',
    active: true,
    password: '',
    password_confirmation: '',
});

function getForm(user) {
    if (!forms[user.id]) {
        forms[user.id] = {
            role: user.role,
            active: Boolean(user.active),
        };
    }

    return forms[user.id];
}

function updateUser(user) {
    const form = getForm(user);
    savingByUser[user.id] = true;

    router.put(route('admin.users.update', user.id), form, {
        preserveScroll: true,
        onFinish: () => {
            savingByUser[user.id] = false;
        },
    });
}

function createUser() {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'email', 'title', 'password', 'password_confirmation');
            createForm.role = 'detailer';
            createForm.active = true;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Admin - User Control" />

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Admin Panel</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage user roles and account status.</p>
                </div>

                <form @submit.prevent="createUser" class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="p-5">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700">Add User</h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Name</label>
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Email</label>
                                <input
                                    v-model="createForm.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                <p v-if="createForm.errors.email" class="mt-1 text-xs text-red-600">{{ createForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Role</label>
                                <select
                                    v-model="createForm.role"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option v-for="role in roleOptions" :key="role" :value="role">
                                        {{ role }}
                                    </option>
                                </select>
                                <p v-if="createForm.errors.role" class="mt-1 text-xs text-red-600">{{ createForm.errors.role }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Title</label>
                                <input
                                    v-model="createForm.title"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                <p v-if="createForm.errors.title" class="mt-1 text-xs text-red-600">{{ createForm.errors.title }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Password</label>
                                <input
                                    v-model="createForm.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                <p v-if="createForm.errors.password" class="mt-1 text-xs text-red-600">{{ createForm.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Confirm Password</label>
                                <input
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                            </div>
                            <div class="flex items-center pt-6">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        v-model="createForm.active"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    >
                                    <span>Active</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-5 py-3">
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold uppercase tracking-wider text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                        >
                            Create User
                        </button>
                    </div>
                </form>

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Active</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="user in users.data" :key="user.id">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select
                                            v-model="getForm(user).role"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        >
                                            <option v-for="role in roleOptions" :key="role" :value="role">
                                                {{ role }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input
                                                v-model="getForm(user).active"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                            >
                                            <span>{{ getForm(user).active ? 'Enabled' : 'Disabled' }}</span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            :disabled="savingByUser[user.id]"
                                            class="inline-flex items-center px-3 py-2 rounded-md text-xs font-semibold uppercase tracking-wider text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                                            @click="updateUser(user)"
                                        >
                                            Save
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
