<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usStates } from '@/utils/usStates';

const props = defineProps({
  project: Object,
  customers: Array,
});

const form = useForm({
  _method: 'put',
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
  model_link: props.project.model_link || '',
  attachments: [],
});
const isDraggingFiles = ref(false);
const fileInput = ref(null);

function submit() {
  form.post(route('projects.update', props.project.id), {
    forceFormData: true,
  });
}

function onFileSelect(event) {
  setFiles(event.target.files);
}

function onFileDrop(event) {
  isDraggingFiles.value = false;
  setFiles(event.dataTransfer?.files ?? []);
}

function setFiles(fileList) {
  const selectedFiles = Array.from(fileList ?? []);

  if (selectedFiles.length === 0) {
    return;
  }

  form.attachments = [...form.attachments, ...selectedFiles];
}

function removeAttachment(index) {
  form.attachments.splice(index, 1);
}

function openFileDialog() {
  fileInput.value?.click();
}

function formatFileSize(sizeInBytes) {
  if (sizeInBytes >= 1048576) {
    return `${(sizeInBytes / 1048576).toFixed(2)} MB`;
  }

  return `${Math.max(1, Math.round(sizeInBytes / 1024))} KB`;
}
</script>

<template>
  <AppLayout>
    <Head :title="`Edit ${project.name}`" />

    <div class="py-8">
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link
            :href="route('projects.show', project.id)"
            class="text-sm text-gray-500 hover:text-gray-700"
            >&larr; Back to {{ project.name }}</Link
          >
          <h1 class="mt-2 text-2xl font-bold text-gray-900">Edit {{ project.name }}</h1>
        </div>

        <form @submit.prevent="submit" class="bg-white shadow-sm rounded-lg border border-gray-200">
          <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">Project Number *</label>
                <input
                  v-model="form.project_number"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
                <p v-if="form.errors.project_number" class="mt-1 text-sm text-red-600">
                  {{ form.errors.project_number }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Name *</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">Customer *</label>
                <select
                  v-model="form.customer_id"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                  <option value="">Select a customer</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }}
                  </option>
                </select>
                <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">
                  {{ form.errors.customer_id }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Status *</label>
                <select
                  v-model="form.status"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                  <option value="active">Active</option>
                  <option value="on_hold">On Hold</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Address</label>
              <input
                v-model="form.address"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
            </div>

            <div class="grid grid-cols-2 gap-6 sm:grid-cols-3">
              <div>
                <label class="block text-sm font-medium text-gray-700">City</label>
                <input
                  v-model="form.city"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">State</label>
                <select
                  v-model="form.state"
                  class="mt-1 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                >
                  <option value="">Select state</option>
                  <option
                    v-for="stateOption in usStates"
                    :key="stateOption.code"
                    :value="stateOption.code"
                  >
                    {{ stateOption.code }} - {{ stateOption.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">ZIP</label>
                <input
                  v-model="form.zip"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700"
                  >Target Completion Date</label
                >
                <input
                  v-model="form.target_completion_date"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                />
                <p v-if="form.errors.target_completion_date" class="mt-1 text-sm text-red-600">
                  {{ form.errors.target_completion_date }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">3D Model Link</label>
              <input
                v-model="form.model_link"
                type="url"
                placeholder="https://example.com/model/123"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              />
              <p v-if="form.errors.model_link" class="mt-1 text-sm text-red-600">
                {{ form.errors.model_link }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Notes</label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              ></textarea>
            </div>

            <div>
              <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700">Project Attachments</label>
                <button
                  type="button"
                  class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
                  @click="openFileDialog"
                >
                  Select Files
                </button>
              </div>
              <input
                ref="fileInput"
                type="file"
                multiple
                class="hidden"
                accept=".pdf,.dwg,.dxf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx,.csv,.txt"
                @change="onFileSelect"
              />
              <div
                class="mt-2 cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-6 text-center transition"
                :class="isDraggingFiles ? 'border-primary-500 bg-primary-50/50' : ''"
                @click="openFileDialog"
                @dragenter.prevent="isDraggingFiles = true"
                @dragover.prevent="isDraggingFiles = true"
                @dragleave.prevent="isDraggingFiles = false"
                @drop.prevent="onFileDrop"
              >
                <p class="text-sm text-gray-600">
                  Drag and drop files here, or click anywhere in this box to browse.
                </p>
                <p class="mt-1 text-xs text-gray-500">
                  Up to 10 files, 30MB each. Allowed: PDF, DWG, DXF, images, Office docs, CSV, TXT.
                </p>
              </div>
              <p v-if="form.errors.attachments" class="mt-2 text-sm text-red-600">
                {{ form.errors.attachments }}
              </p>
              <p v-if="form.errors['attachments.0']" class="mt-1 text-sm text-red-600">
                {{ form.errors['attachments.0'] }}
              </p>

              <p v-if="form.attachments.length" class="mt-2 text-xs font-medium text-gray-600">
                {{ form.attachments.length }} file(s) selected
              </p>
              <ul
                v-if="form.attachments.length"
                class="mt-2 divide-y divide-gray-200 rounded-md border border-gray-200"
              >
                <li
                  v-for="(file, index) in form.attachments"
                  :key="`${file.name}-${index}`"
                  class="flex items-center justify-between px-3 py-2"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-900">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                  </div>
                  <button
                    type="button"
                    class="text-xs font-semibold text-red-600 hover:text-red-700"
                    @click="removeAttachment(index)"
                  >
                    Remove
                  </button>
                </li>
              </ul>
            </div>
          </div>

          <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end space-x-3">
            <Link
              :href="route('projects.show', project.id)"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest bg-white hover:bg-gray-50 transition"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition-all duration-150 ease-out active:scale-95 hover:shadow-sm disabled:opacity-50"
            >
              Update Project
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
