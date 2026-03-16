<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDisplayDateTime } from '@/utils/dateFormatting';

defineProps({
  backups: Array,
});

const restoreForm = useForm({
  backup_file: null,
});

const createBackupForm = useForm({});

function createBackup() {
  createBackupForm.post(route('admin.backups.store'), {
    preserveScroll: true,
  });
}

function submitRestore() {
  restoreForm.post(route('admin.backups.restore'), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      restoreForm.reset();
    },
  });
}

function handleFileChange(event) {
  const [file] = event.target.files ?? [];
  restoreForm.backup_file = file ?? null;
}

function formatSize(size) {
  if (size < 1024) {
    return `${size} B`;
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`;
  }

  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
  <AppLayout>
    <Head title="Admin - Data Backup" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Backup & Restore</h1>
            <p class="mt-1 text-sm text-gray-500">Create backups and restore system data.</p>
          </div>
          <Link
            :href="route('admin.users.index')"
            class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
          >
            User Control
          </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <form
            class="rounded-lg border border-gray-200 bg-white shadow-sm"
            @submit.prevent="createBackup"
          >
            <div class="p-5">
              <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700">
                Create Backup
              </h2>
              <p class="mt-2 text-sm text-gray-500">
                Generate a JSON snapshot of current application data.
              </p>
            </div>
            <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-5 py-3">
              <button
                type="submit"
                :disabled="createBackupForm.processing"
                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-primary-700 disabled:opacity-50"
              >
                Create Backup
              </button>
            </div>
          </form>

          <form
            class="rounded-lg border border-gray-200 bg-white shadow-sm"
            @submit.prevent="submitRestore"
          >
            <div class="p-5">
              <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700">
                Restore Backup
              </h2>
              <p class="mt-2 text-sm text-gray-500">
                Upload a backup file to replace current data.
              </p>
              <div class="mt-4">
                <input
                  type="file"
                  accept=".json"
                  class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                  @change="handleFileChange"
                />
                <p v-if="restoreForm.errors.backup_file" class="mt-1 text-xs text-red-600">
                  {{ restoreForm.errors.backup_file }}
                </p>
              </div>
            </div>
            <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-5 py-3">
              <button
                type="submit"
                :disabled="restoreForm.processing || !restoreForm.backup_file"
                class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700 disabled:opacity-50"
              >
                Restore Backup
              </button>
            </div>
          </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700">
              Available Backups
            </h2>
          </div>

          <div v-if="backups.length === 0" class="px-5 py-6 text-sm text-gray-500">
            No backups available yet.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    File
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    Created
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    Size
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600"
                  >
                    Action
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr
                  v-for="backup in backups"
                  :key="backup.name"
                  class="transition hover:bg-gray-50"
                >
                  <td class="px-5 py-3 text-sm text-gray-700">{{ backup.name }}</td>
                  <td class="px-5 py-3 text-sm text-gray-700">
                    {{ formatDisplayDateTime(backup.created_at) }}
                  </td>
                  <td class="px-5 py-3 text-sm text-gray-700">{{ formatSize(backup.size) }}</td>
                  <td class="px-5 py-3">
                    <a
                      :href="backup.download_url"
                      class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
                    >
                      Download
                    </a>
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
