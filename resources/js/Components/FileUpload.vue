<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    uploadRoute: { type: String, required: true },
});

const emit = defineEmits(['uploaded']);

const fileInput = ref(null);
const dragging = ref(false);
const uploading = ref(false);
const selectedFile = ref(null);
const fileType = ref('drawing');
const notes = ref('');
const error = ref('');

const fileTypeOptions = [
    { value: 'drawing', label: 'Drawing' },
    { value: 'calculation', label: 'Calculation' },
    { value: 'specification', label: 'Specification' },
    { value: 'photo', label: 'Photo' },
    { value: 'markup', label: 'Markup' },
    { value: 'approval', label: 'Approval' },
    { value: 'other', label: 'Other' },
];

function onDragOver(e) {
    e.preventDefault();
    dragging.value = true;
}

function onDragLeave() {
    dragging.value = false;
}

function onDrop(e) {
    e.preventDefault();
    dragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file) {
        selectedFile.value = file;
        error.value = '';
    }
}

function onFileChange(e) {
    selectedFile.value = e.target.files[0] || null;
    error.value = '';
}

function formatBytes(bytes) {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return bytes + ' B';
}

function reset() {
    selectedFile.value = null;
    fileType.value = 'drawing';
    notes.value = '';
    error.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function upload() {
    if (!selectedFile.value) return;

    uploading.value = true;
    error.value = '';

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('file_type', fileType.value);
    if (notes.value) formData.append('notes', notes.value);

    router.post(props.uploadRoute, formData, {
        forceFormData: true,
        onSuccess: () => {
            reset();
            emit('uploaded');
        },
        onError: (errors) => {
            error.value = errors.file || errors.file_type || 'Upload failed. Please try again.';
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Drop Zone -->
        <div
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="fileInput.click()"
            :class="[
                'relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors',
                dragging ? 'border-primary-400 bg-primary-50' : 'border-gray-300 hover:border-gray-400',
                selectedFile ? 'bg-green-50 border-green-300' : '',
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                class="sr-only"
                accept=".pdf,.dwg,.dxf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                @change="onFileChange"
            />
            <template v-if="selectedFile">
                <svg class="mx-auto h-8 w-8 text-green-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                <p class="text-xs text-gray-500">{{ formatBytes(selectedFile.size) }}</p>
                <button
                    type="button"
                    @click.stop="reset"
                    class="mt-2 text-xs text-red-600 hover:text-red-800"
                >
                    Remove
                </button>
            </template>
            <template v-else>
                <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="text-sm font-medium text-gray-700">
                    Drop file here, or <span class="text-primary-600">browse</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">PDF, DWG, DXF, JPG, PNG, DOC, XLS up to 50MB</p>
            </template>
        </div>

        <!-- File Type + Notes -->
        <div v-if="selectedFile" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">File Type</label>
                <select
                    v-model="fileType"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                >
                    <option v-for="opt in fileTypeOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Notes (optional)</label>
                <input
                    v-model="notes"
                    type="text"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                    placeholder="Brief description..."
                />
            </div>
        </div>

        <!-- Error -->
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <!-- Upload Button -->
        <div v-if="selectedFile" class="flex justify-end">
            <button
                type="button"
                @click="upload"
                :disabled="uploading"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition disabled:opacity-50"
            >
                <svg v-if="uploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                {{ uploading ? 'Uploading...' : 'Upload File' }}
            </button>
        </div>
    </div>
</template>
