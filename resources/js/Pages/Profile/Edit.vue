<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const localAvatarPreview = ref(null);

const avatarForm = useForm({
  avatar: null,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const avatarPreview = computed(() => {
  return localAvatarPreview.value ?? user.value?.avatar_url ?? null;
});

const initials = computed(() => {
  return (user.value?.name ?? 'User')
    .split(' ')
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
    .toUpperCase();
});

function submitAvatar() {
  avatarForm.put(route('profile.avatar.update'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      avatarForm.reset('avatar');
      releaseAvatarPreview();
    },
  });
}

function submitPassword() {
  passwordForm.put(route('profile.password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
}

function updateAvatar(event) {
  avatarForm.avatar = event.target.files?.[0] ?? null;

  releaseAvatarPreview();

  if (avatarForm.avatar instanceof File) {
    localAvatarPreview.value = URL.createObjectURL(avatarForm.avatar);
  }
}

function releaseAvatarPreview() {
  if (localAvatarPreview.value !== null) {
    URL.revokeObjectURL(localAvatarPreview.value);
    localAvatarPreview.value = null;
  }
}

onBeforeUnmount(() => {
  releaseAvatarPreview();
});
</script>

<template>
  <AppLayout>
    <Head title="Profile" />

    <div class="py-8">
      <div class="mx-auto grid max-w-5xl gap-6 px-4 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
        <section
          class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
          <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div
              class="relative flex h-28 w-28 items-center justify-center overflow-hidden rounded-3xl bg-slate-200 text-3xl font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
            >
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                :alt="`${user?.name} avatar`"
                class="h-full w-full object-cover"
              />
              <span v-else>{{ initials }}</span>
            </div>

            <div class="min-w-0 flex-1">
              <h1 class="text-2xl font-semibold text-gray-900 dark:text-slate-100">
                {{ user?.name }}
              </h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ user?.email }}</p>
              <p v-if="user?.title" class="mt-3 text-sm text-gray-600 dark:text-slate-300">
                {{ user.title }}
              </p>
              <p
                class="mt-2 inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary-700 dark:bg-primary-950/50 dark:text-primary-300"
              >
                {{ user?.role }}
              </p>
            </div>
          </div>

          <form class="mt-8 space-y-4" @submit.prevent="submitAvatar">
            <div>
              <label
                for="avatar"
                class="block text-sm font-medium text-gray-700 dark:text-slate-200"
              >
                Avatar
              </label>
              <input
                id="avatar"
                type="file"
                accept="image/png,image/jpeg,image/webp,image/gif"
                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:file:bg-slate-800 dark:file:text-slate-200 dark:hover:file:bg-slate-700"
                @change="updateAvatar"
              />
              <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                Upload an image up to 2 MB.
              </p>
              <p
                v-if="avatarForm.errors.avatar"
                class="mt-2 text-sm text-red-600 dark:text-red-400"
              >
                {{ avatarForm.errors.avatar }}
              </p>
            </div>

            <button
              type="submit"
              :disabled="avatarForm.processing || !avatarForm.avatar"
              class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ avatarForm.processing ? 'Saving avatar...' : 'Save avatar' }}
            </button>
          </form>
        </section>

        <section
          class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-slate-100">Change password</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
              Use your current password to confirm the change.
            </p>
          </div>

          <form class="mt-6 space-y-4" @submit.prevent="submitPassword">
            <div>
              <label
                for="current_password"
                class="block text-sm font-medium text-gray-700 dark:text-slate-200"
              >
                Current password
              </label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                autocomplete="current-password"
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
              />
              <p
                v-if="passwordForm.errors.current_password"
                class="mt-2 text-sm text-red-600 dark:text-red-400"
              >
                {{ passwordForm.errors.current_password }}
              </p>
            </div>

            <div>
              <label
                for="password"
                class="block text-sm font-medium text-gray-700 dark:text-slate-200"
              >
                New password
              </label>
              <input
                id="password"
                v-model="passwordForm.password"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
              />
              <p
                v-if="passwordForm.errors.password"
                class="mt-2 text-sm text-red-600 dark:text-red-400"
              >
                {{ passwordForm.errors.password }}
              </p>
            </div>

            <div>
              <label
                for="password_confirmation"
                class="block text-sm font-medium text-gray-700 dark:text-slate-200"
              >
                Confirm new password
              </label>
              <input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
              />
            </div>

            <button
              type="submit"
              :disabled="passwordForm.processing"
              class="inline-flex items-center rounded-md border border-transparent bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-300"
            >
              {{ passwordForm.processing ? 'Updating password...' : 'Update password' }}
            </button>
          </form>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
