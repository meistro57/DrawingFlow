<script setup>
import { ref, computed } from 'vue';

const CATEGORY_LABELS = {
  specs: 'Specs',
  design_drawings: 'Design Drawings',
  collab: 'Collab',
  other: 'Other',
};

const CATEGORY_ORDER = ['specs', 'design_drawings', 'collab', 'other'];
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDisplayDate } from '@/utils/dateFormatting';

const props = defineProps({
  project: Object,
});
const selectedPdfAttachmentId = ref(null);

const selectedPdfAttachment = computed(() =>
  props.project.attachments?.find((attachment) => attachment.id === selectedPdfAttachmentId.value)
);

const attachmentsByCategory = computed(() => {
  const attachments = props.project.attachments ?? [];
  const groups = {};

  for (const attachment of attachments) {
    const cat = attachment.category || 'other';
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(attachment);
  }

  return CATEGORY_ORDER.filter((cat) => groups[cat]?.length).map((cat) => ({
    key: cat,
    label: CATEGORY_LABELS[cat] ?? cat,
    attachments: groups[cat],
  }));
});

const selectedPdfViewerUrl = computed(() =>
  selectedPdfAttachmentId.value
    ? route('projects.attachments.view', [props.project.id, selectedPdfAttachmentId.value])
    : null
);

function deleteProject() {
  if (confirm(`Are you sure you want to delete "${props.project.name}"?`)) {
    router.delete(route('projects.destroy', props.project.id));
  }
}

function deleteAttachment(attachment) {
  if (confirm(`Delete attachment "${attachment.original_filename}"?`)) {
    router.delete(route('projects.attachments.destroy', [props.project.id, attachment.id]), {
      onSuccess: () => {
        if (selectedPdfAttachmentId.value === attachment.id) {
          selectedPdfAttachmentId.value = null;
        }
      },
    });
  }
}

function openPdfViewer(attachmentId) {
  selectedPdfAttachmentId.value = attachmentId;
}

function closePdfViewer() {
  selectedPdfAttachmentId.value = null;
}

function isPdfAttachment(attachment) {
  return (
    attachment.mime_type === 'application/pdf' ||
    attachment.original_filename.toLowerCase().endsWith('.pdf')
  );
}

function formatFileSize(sizeInBytes) {
  const size = Number(sizeInBytes ?? 0);

  if (size >= 1048576) {
    return `${(size / 1048576).toFixed(2)} MB`;
  }

  return `${Math.max(1, Math.round(size / 1024))} KB`;
}
</script>

<template>
  <AppLayout>
    <Head :title="project.name" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link :href="route('projects.index')" class="text-sm text-gray-500 hover:text-gray-700"
            >&larr; Back to Projects</Link
          >
        </div>

        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
              <h1 class="text-2xl font-bold text-gray-900">{{ project.name }}</h1>
              <StatusBadge :status="project.status" />
            </div>
            <p class="mt-1 text-sm text-gray-500">
              {{ project.project_number }}
              <span v-if="project.customer">
                &middot;
                <Link
                  :href="route('customers.show', project.customer.id)"
                  class="text-primary-600 hover:text-primary-800"
                >
                  {{ project.customer.name }}
                </Link>
              </span>
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Link
              :href="route('drawing-requests.create', { project_id: project.id })"
              class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm"
            >
              New Request
            </Link>
            <Link
              :href="route('projects.edit', project.id)"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition"
            >
              Edit
            </Link>
            <button
              @click="deleteProject"
              class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition"
            >
              Delete
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Details -->
          <div class="lg:sm:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-medium text-gray-900">Project Details</h2>
            </div>
            <dl class="divide-y divide-gray-200">
              <div
                v-if="project.description"
                class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2 whitespace-pre-line">
                  {{ project.description }}
                </dd>
              </div>
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Address</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">
                  <template v-if="project.address">
                    {{ project.address }}<br />
                    {{ project.city }}, {{ project.state }} {{ project.zip }}
                  </template>
                  <template v-else>-</template>
                </dd>
              </div>
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">
                  {{ formatDisplayDate(project.start_date) }}
                </dd>
              </div>
              <div class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500">Target Completion</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">
                  {{ formatDisplayDate(project.target_completion_date) }}
                </dd>
              </div>
              <div
                v-if="project.model_link"
                class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-gray-500">3D Model</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2">
                  <a
                    :href="project.model_link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-primary-600 hover:text-primary-800 hover:underline"
                  >
                    Open 3D Model
                  </a>
                </dd>
              </div>
              <div
                v-if="project.notes"
                class="px-6 py-4 grid grid-cols-1 gap-2 sm:grid-cols-3 sm:gap-4"
              >
                <dt class="text-sm font-medium text-gray-500">Notes</dt>
                <dd class="text-sm text-gray-900 sm:col-span-2 whitespace-pre-line">
                  {{ project.notes }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Stats -->
          <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Quick Stats</h3>
            <dl class="space-y-4">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">Drawing Requests</dt>
                <dd class="text-sm font-semibold text-gray-900">
                  {{ project.drawing_requests_count }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">Submittals</dt>
                <dd class="text-sm font-semibold text-gray-900">{{ project.submittals_count }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-600">Fab Queue</dt>
                <dd class="text-sm font-semibold text-gray-900">
                  {{ project.fab_queue_entries_count }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Attachments -->
        <div class="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Project Attachments</h2>
          </div>
          <div v-if="project.attachments?.length" class="overflow-x-auto">
            <template v-for="group in attachmentsByCategory" :key="group.key">
              <div class="bg-gray-50 px-5 py-2 border-b border-gray-200">
                <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">{{
                  group.label
                }}</span>
              </div>
              <table class="min-w-[900px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      File
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Version
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Type
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Size
                    </th>
                    <th
                      class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Uploaded By
                    </th>
                    <th
                      class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr
                    v-for="attachment in group.attachments"
                    :key="attachment.id"
                    class="transition hover:bg-gray-50"
                  >
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">
                      {{ attachment.original_filename }}
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                      <span class="font-medium text-gray-700"
                        >v{{ attachment.version_number || 1 }}</span
                      >
                      <span
                        v-if="attachment.is_latest"
                        class="ml-2 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700"
                      >
                        Latest
                      </span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                      {{ attachment.mime_type || '-' }}
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                      {{ formatFileSize(attachment.file_size) }}
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                      {{ attachment.uploaded_by?.name || '-' }}
                    </td>
                    <td
                      class="px-5 py-3 whitespace-nowrap text-right text-sm font-medium space-x-3"
                    >
                      <button
                        v-if="isPdfAttachment(attachment)"
                        type="button"
                        class="text-primary-600 hover:text-primary-800"
                        @click="openPdfViewer(attachment.id)"
                      >
                        View PDF
                      </button>
                      <Link
                        :href="route('projects.attachments.download', [project.id, attachment.id])"
                        class="text-primary-600 hover:text-primary-800"
                      >
                        Download
                      </Link>
                      <button
                        type="button"
                        class="text-red-600 hover:text-red-800"
                        @click="deleteAttachment(attachment)"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </template>
          </div>
          <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
            No attachments uploaded for this project yet.
          </div>
        </div>

        <div
          v-if="selectedPdfViewerUrl"
          class="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden"
        >
          <div
            class="flex flex-col gap-2 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <h2 class="text-lg font-medium text-gray-900">
              PDF Viewer
              <span v-if="selectedPdfAttachment" class="text-sm text-gray-500 font-normal">
                - {{ selectedPdfAttachment.original_filename }}
              </span>
            </h2>
            <button
              type="button"
              class="text-sm text-gray-600 hover:text-gray-800"
              @click="closePdfViewer"
            >
              Close Viewer
            </button>
          </div>
          <div class="h-[60vh] max-h-[calc(100vh-8rem)] sm:h-[70vh]">
            <iframe :src="selectedPdfViewerUrl" title="Project PDF Viewer" class="h-full w-full" />
          </div>
        </div>

        <!-- Drawing Requests -->
        <div class="mt-8 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
          <div
            class="flex flex-col gap-2 border-b border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <h2 class="text-lg font-medium text-gray-900">Drawing Requests</h2>
            <Link
              :href="route('drawing-requests.create', { project_id: project.id })"
              class="text-sm text-primary-600 hover:text-primary-800 font-medium"
            >
              Add Request
            </Link>
          </div>
          <div v-if="project.drawing_requests?.length" class="overflow-x-auto">
            <table class="min-w-[760px] divide-y divide-gray-200">
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
                    Title
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Assigned To
                  </th>
                  <th
                    class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                  >
                    Priority
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
                  v-for="request in project.drawing_requests"
                  :key="request.id"
                  class="hover:bg-gray-50 transition-colors duration-150 ease-out"
                >
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-medium">
                    <Link
                      :href="route('drawing-requests.show', request.id)"
                      class="text-primary-600 hover:text-primary-800"
                    >
                      {{ request.request_number }}
                    </Link>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">
                    {{ request.title }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">
                    {{ request.assigned_to?.name || 'Unassigned' }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap">
                    <StatusBadge :status="request.priority || 'normal'" />
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap">
                    <StatusBadge :status="request.status" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
            No drawing requests for this project yet.
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
