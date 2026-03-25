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
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
              Data Backup & Restore
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
              Create backups and restore system data.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <Link
              :href="route('admin.users.index')"
              class="inline-flex items-center rounded-md border border-white/30 bg-white/40 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 backdrop-blur-md transition hover:bg-white/60 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200 dark:hover:bg-slate-900/60"
            >
              User Control
            </Link>
            <Link
              :href="route('admin.boost.index')"
              class="inline-flex items-center rounded-md border border-white/30 bg-white/40 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 backdrop-blur-md transition hover:bg-white/60 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200 dark:hover:bg-slate-900/60"
            >
              Boost
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <form
            class="rounded-2xl border border-white/40 bg-white/60 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:bg-slate-900/50 dark:shadow-black/30"
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
            <div
              class="flex justify-end border-t border-white/40 bg-white/40 px-5 py-3 dark:border-slate-700/60 dark:bg-slate-900/40"
            >
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
            class="rounded-2xl border border-white/40 bg-white/60 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:bg-slate-900/50 dark:shadow-black/30"
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
            <div
              class="flex justify-end border-t border-white/40 bg-white/40 px-5 py-3 dark:border-slate-700/60 dark:bg-slate-900/40"
            >
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

        <div
          class="mt-6 overflow-hidden rounded-2xl border border-white/40 bg-white/60 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:bg-slate-900/50 dark:shadow-black/30"
        >
          <div class="border-b border-white/40 px-5 py-4 dark:border-slate-700/60">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700">
              Available Backups
            </h2>
          </div>

          <div v-if="backups.length === 0" class="px-5 py-6 text-sm text-gray-500">
            No backups available yet.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-[720px] divide-y divide-white/40 dark:divide-slate-700/60">
              <thead class="bg-white/60 dark:bg-slate-900/60">
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
              <tbody
                class="divide-y divide-white/30 bg-white/40 dark:divide-slate-700/60 dark:bg-slate-900/30"
              >
                <tr
                  v-for="backup in backups"
                  :key="backup.name"
                  class="transition hover:bg-white/70 dark:hover:bg-slate-800/60"
                >
                  <td class="px-5 py-3 text-sm text-gray-700">{{ backup.name }}</td>
                  <td class="px-5 py-3 text-sm text-gray-700">
                    {{ formatDisplayDateTime(backup.created_at) }}
                  </td>
                  <td class="px-5 py-3 text-sm text-gray-700">{{ formatSize(backup.size) }}</td>
                  <td class="px-5 py-3">
                    <a
                      :href="backup.download_url"
                      class="inline-flex items-center rounded-md border border-white/30 bg-white/50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 backdrop-blur-md transition hover:bg-white/70 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200 dark:hover:bg-slate-900/60"
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
