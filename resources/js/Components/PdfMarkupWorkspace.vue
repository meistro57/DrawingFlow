<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  submittalId: {
    type: Number,
    required: true,
  },
  files: {
    type: Array,
    default: () => [],
  },
});

const selectedFileId = ref(null);
const markups = ref([]);
const activeTool = ref('circle');
const pageNumber = ref(1);
const isLoadingMarkups = ref(false);
const saveError = ref('');
const isSaving = ref(false);
const viewerSurface = ref(null);
const draftMarkup = ref(null);
const compareMode = ref(false);
const comparisonFileId = ref(null);

const toolOptions = [
  { value: 'circle', label: 'Circle' },
  { value: 'arrow', label: 'Arrow' },
  { value: 'text', label: 'Text' },
  { value: 'highlight', label: 'Highlight' },
  { value: 'stamp', label: 'Stamp' },
];

const stampOptions = [
  'APPROVED',
  'APPROVED AS NOTED',
  'REVISE & RESUBMIT',
  'REJECTED',
  'FIELD VERIFY',
];

const selectedStamp = ref(stampOptions[0]);

const pdfFiles = computed(() =>
  props.files.filter((file) => file.mime_type === 'application/pdf' || file.original_filename.toLowerCase().endsWith('.pdf'))
);

const selectedFile = computed(() =>
  pdfFiles.value.find((file) => file.id === selectedFileId.value) ?? null
);

const selectedPdfViewerUrl = computed(() =>
  selectedFileId.value ? route('submittals.files.view', [props.submittalId, selectedFileId.value]) : null
);

const selectedExportUrl = computed(() =>
  selectedFileId.value ? route('submittals.files.markups.export', [props.submittalId, selectedFileId.value]) : null
);

const comparisonCandidates = computed(() =>
  pdfFiles.value.filter((file) => file.id !== selectedFileId.value)
);

const comparisonViewerUrl = computed(() =>
  comparisonFileId.value ? route('submittals.files.view', [props.submittalId, comparisonFileId.value]) : null
);

const comparisonFile = computed(() =>
  pdfFiles.value.find((file) => file.id === comparisonFileId.value) ?? null
);

function asPercent(point, rect) {
  return {
    x: Math.max(0, Math.min(100, ((point.clientX - rect.left) / rect.width) * 100)),
    y: Math.max(0, Math.min(100, ((point.clientY - rect.top) / rect.height) * 100)),
  };
}

function beginDrawing(event) {
  if (!selectedFileId.value || !viewerSurface.value || ['text', 'stamp'].includes(activeTool.value)) {
    return;
  }

  saveError.value = '';

  const rect = viewerSurface.value.getBoundingClientRect();
  const start = asPercent(event, rect);

  draftMarkup.value = {
    page_number: pageNumber.value,
    markup_type: activeTool.value,
    markup_data: {
      x: start.x,
      y: start.y,
      x2: start.x,
      y2: start.y,
      color: activeTool.value === 'highlight' ? '#fde047' : '#ef4444',
    },
  };
}

function continueDrawing(event) {
  if (!draftMarkup.value || !viewerSurface.value) {
    return;
  }

  const rect = viewerSurface.value.getBoundingClientRect();
  const current = asPercent(event, rect);

  draftMarkup.value = {
    ...draftMarkup.value,
    markup_data: {
      ...draftMarkup.value.markup_data,
      x2: current.x,
      y2: current.y,
    },
  };
}

async function finishDrawing() {
  if (!draftMarkup.value) {
    return;
  }

  const payload = normalizeMarkupPayload(draftMarkup.value);
  draftMarkup.value = null;

  if (!payload) {
    return;
  }

  await storeMarkup(payload);
}

function cancelDraft() {
  draftMarkup.value = null;
}

function normalizeMarkupPayload(markup) {
  if (markup.markup_type === 'text') {
    if (!markup.markup_data.text) {
      return null;
    }

    return markup;
  }

  const { x, y, x2, y2 } = markup.markup_data;
  const width = Math.abs(x2 - x);
  const height = Math.abs(y2 - y);

  if (width < 0.3 && height < 0.3) {
    return null;
  }

  return {
    ...markup,
    markup_data: {
      ...markup.markup_data,
      x: Math.min(x, x2),
      y: Math.min(y, y2),
      width,
      height,
      x2,
      y2,
    },
  };
}

async function placePointMarkup(event) {
  if (!selectedFileId.value || !viewerSurface.value || !['text', 'stamp'].includes(activeTool.value)) {
    return;
  }

  saveError.value = '';

  const rect = viewerSurface.value.getBoundingClientRect();
  const point = asPercent(event, rect);

  if (activeTool.value === 'text') {
    const text = window.prompt('Enter markup text');
    if (!text) {
      return;
    }

    await storeMarkup({
      page_number: pageNumber.value,
      markup_type: 'text',
      markup_data: {
        x: point.x,
        y: point.y,
        text,
        color: '#ef4444',
      },
    });

    return;
  }

  await storeMarkup({
    page_number: pageNumber.value,
    markup_type: 'stamp',
    markup_data: {
      x: point.x,
      y: point.y,
      label: selectedStamp.value,
      color: '#b91c1c',
      bg_color: '#fee2e2',
      border_color: '#b91c1c',
    },
  });
}

async function storeMarkup(payload) {
  if (!selectedFileId.value) {
    return;
  }

  isSaving.value = true;

  try {
    const response = await window.axios.post(
      route('submittals.files.markups.store', [props.submittalId, selectedFileId.value]),
      payload
    );

    markups.value = [...markups.value, response.data.data];
  } catch {
    saveError.value = 'Could not save markup. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function loadMarkups() {
  if (!selectedFileId.value) {
    markups.value = [];
    return;
  }

  isLoadingMarkups.value = true;
  saveError.value = '';

  try {
    const response = await window.axios.get(
      route('submittals.files.markups.index', [props.submittalId, selectedFileId.value])
    );

    markups.value = response.data.data;
  } catch {
    markups.value = [];
    saveError.value = 'Could not load markups.';
  } finally {
    isLoadingMarkups.value = false;
  }
}

function selectPdf(fileId) {
  if (selectedFileId.value === fileId) {
    return;
  }

  selectedFileId.value = fileId;
}

function styleFromMarkup(markup) {
  const color = markup.markup_data.color || '#ef4444';

  if (markup.markup_type === 'highlight') {
    return {
      stroke: '#f59e0b',
      fill: color,
      fillOpacity: 0.35,
      strokeWidth: 0.4,
    };
  }

  return {
    stroke: color,
    fill: 'transparent',
    fillOpacity: 1,
    strokeWidth: 0.5,
  };
}

const renderedMarkups = computed(() => {
  const allMarkups = [...markups.value];

  if (draftMarkup.value) {
    allMarkups.push(draftMarkup.value);
  }

  return allMarkups.filter((markup) => markup.page_number === pageNumber.value);
});

const markupHistory = computed(() =>
  [...markups.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 15)
);

function formatMarkupDate(value) {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleString();
}

watch(selectedFileId, async () => {
  if (comparisonFileId.value === selectedFileId.value) {
    comparisonFileId.value = comparisonCandidates.value[0]?.id ?? null;
  }

  await loadMarkups();
});

watch(compareMode, (enabled) => {
  if (!enabled) {
    comparisonFileId.value = null;
    return;
  }

  comparisonFileId.value = comparisonCandidates.value[0]?.id ?? null;
});

watch(pdfFiles, (nextFiles) => {
  if (!nextFiles.length) {
    selectedFileId.value = null;
    return;
  }

  if (!nextFiles.some((file) => file.id === selectedFileId.value)) {
    selectedFileId.value = nextFiles[0].id;
  }
}, { immediate: true });

onMounted(async () => {
  if (selectedFileId.value) {
    await loadMarkups();
  }
});
</script>

<template>
  <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 space-y-3">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <h2 class="text-lg font-medium text-gray-900">PDF Markup Workspace</h2>
        <div class="flex items-center gap-2">
          <label for="pdf-page" class="text-sm text-gray-600">Page</label>
          <input
            id="pdf-page"
            v-model.number="pageNumber"
            type="number"
            min="1"
            class="w-20 rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          v-for="tool in toolOptions"
          :key="tool.value"
          type="button"
          class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-widest transition"
          :class="activeTool === tool.value
            ? 'border-primary-600 bg-primary-600 text-white'
            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
          @click="activeTool = tool.value"
        >
          {{ tool.label }}
        </button>
      </div>

      <div v-if="activeTool === 'stamp'" class="flex flex-wrap items-center gap-2">
        <label class="text-xs font-medium text-gray-600">Stamp</label>
        <select
          v-model="selectedStamp"
          class="rounded-md border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500"
        >
          <option v-for="stamp in stampOptions" :key="stamp" :value="stamp">{{ stamp }}</option>
        </select>
      </div>

      <div v-if="pdfFiles.length" class="flex flex-wrap gap-2">
        <button
          v-for="file in pdfFiles"
          :key="file.id"
          type="button"
          class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium transition"
          :class="selectedFileId === file.id
            ? 'border-primary-600 bg-primary-50 text-primary-700'
            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
          @click="selectPdf(file.id)"
        >
          {{ file.original_filename }}
        </button>
      </div>

      <div v-if="pdfFiles.length > 1" class="flex flex-wrap items-center gap-3">
        <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600">
          <input
            v-model="compareMode"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          >
          Compare side-by-side
        </label>

        <select
          v-if="compareMode"
          v-model="comparisonFileId"
          class="rounded-md border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500"
        >
          <option
            v-for="candidate in comparisonCandidates"
            :key="candidate.id"
            :value="candidate.id"
          >
            {{ candidate.original_filename }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="!pdfFiles.length" class="px-6 py-8 text-center text-sm text-gray-500">
      No PDF files available on this submittal.
    </div>

    <div v-else>
      <div class="px-6 py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span v-if="selectedFile">Viewing: {{ selectedFile.original_filename }}</span>
        <div class="flex items-center gap-3">
          <a
            v-if="selectedExportUrl"
            :href="selectedExportUrl"
            class="text-primary-600 hover:text-primary-800 font-medium"
          >
            Export Markups
          </a>
          <span v-if="isLoadingMarkups">Loading markups...</span>
          <span v-else-if="isSaving">Saving markup...</span>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 p-4" :class="compareMode && comparisonViewerUrl ? 'md:grid-cols-2' : 'md:grid-cols-1'">
        <div>
          <p class="mb-2 text-xs font-medium text-gray-600">Primary</p>
          <div
            ref="viewerSurface"
            class="relative h-[60vh] md:h-[75vh] bg-gray-900"
            @mousedown="beginDrawing"
            @mousemove="continueDrawing"
            @mouseup="finishDrawing"
            @mouseleave="cancelDraft"
            @click="placePointMarkup"
          >
            <iframe
              v-if="selectedPdfViewerUrl"
              :src="selectedPdfViewerUrl"
              title="Submittal PDF Viewer"
              class="h-full w-full"
            />

            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 h-full w-full">
              <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="8" refY="3.5" orient="auto">
                  <polygon points="0 0, 10 3.5, 0 7" fill="#ef4444" />
                </marker>
              </defs>

              <template v-for="markup in renderedMarkups" :key="`markup-${markup.id ?? `draft-${markup.markup_type}-${markup.markup_data.x}-${markup.markup_data.y}`}`">
                <ellipse
                  v-if="markup.markup_type === 'circle'"
                  :cx="Number(markup.markup_data.x) + Number(markup.markup_data.width || 0) / 2"
                  :cy="Number(markup.markup_data.y) + Number(markup.markup_data.height || 0) / 2"
                  :rx="Number(markup.markup_data.width || 0) / 2"
                  :ry="Number(markup.markup_data.height || 0) / 2"
                  v-bind="styleFromMarkup(markup)"
                />

                <rect
                  v-else-if="markup.markup_type === 'highlight'"
                  :x="Number(markup.markup_data.x)"
                  :y="Number(markup.markup_data.y)"
                  :width="Number(markup.markup_data.width || 0)"
                  :height="Number(markup.markup_data.height || 0)"
                  v-bind="styleFromMarkup(markup)"
                />

                <line
                  v-else-if="markup.markup_type === 'arrow'"
                  :x1="Number(markup.markup_data.x)"
                  :y1="Number(markup.markup_data.y)"
                  :x2="Number(markup.markup_data.x2)"
                  :y2="Number(markup.markup_data.y2)"
                  stroke="#ef4444"
                  stroke-width="0.6"
                  marker-end="url(#arrowhead)"
                />

                <text
                  v-else-if="markup.markup_type === 'text'"
                  :x="Number(markup.markup_data.x)"
                  :y="Number(markup.markup_data.y)"
                  fill="#ef4444"
                  font-size="2.5"
                  font-weight="600"
                >
                  {{ markup.markup_data.text }}
                </text>

                <g v-else-if="markup.markup_type === 'stamp'">
                  <rect
                    :x="Math.max(0, Number(markup.markup_data.x) - 9)"
                    :y="Math.max(0, Number(markup.markup_data.y) - 3)"
                    width="18"
                    height="6"
                    rx="0.6"
                    :fill="markup.markup_data.bg_color || '#fee2e2'"
                    :stroke="markup.markup_data.border_color || '#b91c1c'"
                    stroke-width="0.4"
                  />
                  <text
                    :x="Number(markup.markup_data.x)"
                    :y="Number(markup.markup_data.y) + 1.2"
                    :fill="markup.markup_data.color || '#b91c1c'"
                    font-size="1.7"
                    font-weight="700"
                    text-anchor="middle"
                  >
                    {{ markup.markup_data.label || 'STAMP' }}
                  </text>
                </g>
              </template>
            </svg>
          </div>
        </div>

        <div v-if="compareMode && comparisonViewerUrl">
          <p class="mb-2 text-xs font-medium text-gray-600">
            Compare: {{ comparisonFile?.original_filename || 'Secondary' }}
          </p>
          <div class="relative h-[60vh] md:h-[75vh] bg-gray-900">
            <iframe
              :src="comparisonViewerUrl"
              title="Submittal PDF Comparison Viewer"
              class="h-full w-full"
            />
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-900">Markup History</h3>
        <div v-if="markupHistory.length" class="mt-3 space-y-2 text-xs text-gray-600">
          <div
            v-for="item in markupHistory"
            :key="`history-${item.id}`"
            class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2"
          >
            <span class="font-medium text-gray-700">{{ item.markup_type }} · Page {{ item.page_number }}</span>
            <span>{{ item.user?.name || 'Unknown' }}</span>
            <span>{{ formatMarkupDate(item.created_at) }}</span>
          </div>
        </div>
        <p v-else class="mt-3 text-xs text-gray-500">No saved markups yet.</p>
      </div>

      <div v-if="saveError" class="px-6 py-3 border-t border-gray-200 text-sm text-red-600">
        {{ saveError }}
      </div>
    </div>
  </div>
</template>
