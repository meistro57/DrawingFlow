<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { formatDisplayDateTime } from '@/utils/dateFormatting';

const props = defineProps({
  submittalId: {
    type: Number,
    required: true,
  },
  files: {
    type: Array,
    default: () => [],
  },
  initialFileId: {
    type: Number,
    default: null,
  },
});

const selectedFileId = ref(props.initialFileId);
const markups = ref([]);
const pageScales = ref([]);
const activeTool = ref('circle');
const pageNumber = ref(1);
const isLoadingMarkups = ref(false);
const saveError = ref('');
const isSaving = ref(false);
const viewerSurface = ref(null);
const draftMarkup = ref(null);
const compareMode = ref(false);
const comparisonFileId = ref(null);
const selectedHistoryMarkupId = ref(null);
const historyScope = ref('current');
const historyQuery = ref('');
const historyTypeFilter = ref('all');
const historyAuthorFilter = ref('all');
const commentsOnly = ref(false);
const hiddenMarkupTypes = ref({});
const importFile = ref(null);
const importFileName = ref('');
const isCalibratingScale = ref(false);
const scaleDraft = ref(null);
const scaleRealLength = ref('');
const scaleUnit = ref('ft');
const pathPreviewPoint = ref(null);
const editPageNumber = ref(1);
const editComment = ref('');
const editText = ref('');
const editLabel = ref('');
const editColor = ref('#ef4444');
const editStrokeWidth = ref(1.2);
const editOpacity = ref(0.35);
const editFontSize = ref(2.8);
const interactionState = ref(null);
const editDraftMarkup = ref(null);

const currentColor = ref('#ef4444');
const currentStrokeWidth = ref(1.2);
const currentOpacity = ref(0.35);
const currentTextSize = ref(2.8);
const commentText = ref('');
const textContent = ref('');
const customStampLabel = ref('');
const dimensionLabel = ref('');

const toolOptions = [
  { value: 'circle', label: 'Circle' },
  { value: 'rectangle', label: 'Rectangle' },
  { value: 'cloud', label: 'Cloud' },
  { value: 'pen', label: 'Pen' },
  { value: 'polyline', label: 'Polyline' },
  { value: 'polygon', label: 'Polygon' },
  { value: 'arrow', label: 'Arrow' },
  { value: 'dimension', label: 'Dimension' },
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
  props.files.filter(
    (file) =>
      file.mime_type === 'application/pdf' || file.original_filename.toLowerCase().endsWith('.pdf')
  )
);

const selectedFile = computed(
  () => pdfFiles.value.find((file) => file.id === selectedFileId.value) ?? null
);

const selectedPdfViewerUrl = computed(() =>
  selectedFileId.value
    ? route('submittals.files.view', [props.submittalId, selectedFileId.value])
    : null
);

const selectedExportUrl = computed(() =>
  selectedFileId.value
    ? route('submittals.files.markups.export', [props.submittalId, selectedFileId.value])
    : null
);

const comparisonCandidates = computed(() =>
  pdfFiles.value.filter((file) => file.id !== selectedFileId.value)
);

const comparisonViewerUrl = computed(() =>
  comparisonFileId.value
    ? route('submittals.files.view', [props.submittalId, comparisonFileId.value])
    : null
);

const comparisonFile = computed(
  () => pdfFiles.value.find((file) => file.id === comparisonFileId.value) ?? null
);

const activeStampLabel = computed(() => customStampLabel.value.trim() || selectedStamp.value);
const selectedHistoryMarkup = computed(
  () => markups.value.find((markup) => markup.id === selectedHistoryMarkupId.value) ?? null
);
const availableMarkupTypes = computed(() =>
  [...new Set(markups.value.map((markup) => markup.markup_type))].sort()
);
const availableMarkupAuthors = computed(() =>
  [...new Set(markups.value.map((markup) => markup.user?.name).filter(Boolean))].sort()
);
const visibleMarkupTypes = computed(() =>
  toolOptions.filter((tool) => !hiddenMarkupTypes.value[tool.value]).map((tool) => tool.value)
);
const isMultiPointTool = computed(() => ['polyline', 'polygon'].includes(activeTool.value));
const isPathDraftActive = computed(() =>
  Boolean(draftMarkup.value && ['polyline', 'polygon'].includes(draftMarkup.value.markup_type))
);

const currentPageMarkups = computed(() =>
  markups.value.filter((markup) => markup.page_number === pageNumber.value)
);
const currentPageScale = computed(
  () => pageScales.value.find((scale) => scale.page_number === pageNumber.value) ?? null
);
const selectedCanvasMarkup = computed(() => {
  const draft = editDraftMarkup.value;

  if (draft && draft.id === selectedHistoryMarkupId.value) {
    return draft;
  }

  if (
    !selectedHistoryMarkup.value ||
    selectedHistoryMarkup.value.page_number !== pageNumber.value ||
    hiddenMarkupTypes.value[selectedHistoryMarkup.value.markup_type]
  ) {
    return null;
  }

  return selectedHistoryMarkup.value;
});

const renderedMarkups = computed(() => {
  const allMarkups = [...markups.value];

  if (draftMarkup.value) {
    allMarkups.push(draftMarkup.value);
  }

  return allMarkups.filter(
    (markup) =>
      markup.page_number === pageNumber.value && !hiddenMarkupTypes.value[markup.markup_type]
  );
});

const markupHistory = computed(() => {
  const source =
    historyScope.value === 'current'
      ? markups.value.filter((markup) => markup.page_number === pageNumber.value)
      : markups.value;

  return [...source]
    .filter((markup) => !hiddenMarkupTypes.value[markup.markup_type])
    .filter((markup) =>
      historyTypeFilter.value === 'all' ? true : markup.markup_type === historyTypeFilter.value
    )
    .filter((markup) =>
      historyAuthorFilter.value === 'all'
        ? true
        : (markup.user?.name || '') === historyAuthorFilter.value
    )
    .filter((markup) => (commentsOnly.value ? Boolean(markup.markup_data?.comment) : true))
    .filter((markup) => {
      const query = historyQuery.value.trim().toLowerCase();

      if (!query) {
        return true;
      }

      return [
        markup.markup_type,
        markup.user?.name,
        markup.markup_data?.text,
        markup.markup_data?.label,
        markup.markup_data?.comment,
        `page ${markup.page_number}`,
      ]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(query));
    })
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 20);
});

function formatMarkupDate(value) {
  return formatDisplayDateTime(value);
}

function formatMarkupType(markupType) {
  return markupType.replaceAll('_', ' ').replace(/\b\w/g, (match) => match.toUpperCase());
}

function toggleMarkupTypeVisibility(markupType) {
  hiddenMarkupTypes.value = {
    ...hiddenMarkupTypes.value,
    [markupType]: !hiddenMarkupTypes.value[markupType],
  };
}

function resetHistoryFilters() {
  historyQuery.value = '';
  historyTypeFilter.value = 'all';
  historyAuthorFilter.value = 'all';
  commentsOnly.value = false;
}

function syncSelectedMarkupEditor(markup) {
  if (!markup) {
    editPageNumber.value = pageNumber.value;
    editComment.value = '';
    editText.value = '';
    editLabel.value = '';
    editColor.value = currentColor.value;
    editStrokeWidth.value = currentStrokeWidth.value;
    editOpacity.value = currentOpacity.value;
    editFontSize.value = currentTextSize.value;
    return;
  }

  editPageNumber.value = markup.page_number;
  editComment.value = markup.markup_data?.comment || '';
  editText.value = markup.markup_data?.text || '';
  editLabel.value = markup.markup_data?.label || '';
  editColor.value = markup.markup_data?.color || '#ef4444';
  editStrokeWidth.value = Number(markup.markup_data?.stroke_width ?? 1.2);
  editOpacity.value = Number(markup.markup_data?.opacity ?? 0.35);
  editFontSize.value = Number(markup.markup_data?.font_size ?? 2.8);
}

function cloneMarkup(markup) {
  return JSON.parse(JSON.stringify(markup));
}

function asPercent(point, rect) {
  return {
    x: Math.max(0, Math.min(100, ((point.clientX - rect.left) / rect.width) * 100)),
    y: Math.max(0, Math.min(100, ((point.clientY - rect.top) / rect.height) * 100)),
  };
}

function createBaseMarkupData() {
  return {
    color: currentColor.value,
    stroke_width: currentStrokeWidth.value,
    opacity: currentOpacity.value,
    comment: commentText.value.trim() || null,
    font_size: currentTextSize.value,
  };
}

function pathPoints(markup) {
  return Array.isArray(markup?.markup_data?.points) ? markup.markup_data.points : [];
}

function pointListBounds(points) {
  if (!points.length) {
    return null;
  }

  const xs = points.map((point) => Number(point.x));
  const ys = points.map((point) => Number(point.y));

  return {
    x: Math.min(...xs),
    y: Math.min(...ys),
    width: Math.max(0.5, Math.max(...xs) - Math.min(...xs)),
    height: Math.max(0.5, Math.max(...ys) - Math.min(...ys)),
  };
}

function pointsString(markup) {
  const points = [...pathPoints(markup)];

  if (markup === draftMarkup.value && pathPreviewPoint.value) {
    points.push(pathPreviewPoint.value);
  }

  return points.map((point) => `${point.x},${point.y}`).join(' ');
}

function pointDistance(a, b) {
  return Math.sqrt((a.x - b.x) ** 2 + (a.y - b.y) ** 2);
}

function measuredDistanceLabel(x, y, x2, y2) {
  const distance = Math.sqrt((x2 - x) ** 2 + (y2 - y) ** 2);
  const scale = currentPageScale.value;

  if (!scale) {
    return `${distance.toFixed(1)} u`;
  }

  const convertedLength =
    (distance / Number(scale.calibration_distance)) * Number(scale.real_length);

  return `${convertedLength.toFixed(2)} ${scale.unit}`;
}

function beginDrawing(event) {
  if (!selectedFileId.value || !viewerSurface.value) {
    return;
  }

  saveError.value = '';

  const rect = viewerSurface.value.getBoundingClientRect();
  const start = asPercent(event, rect);

  if (isCalibratingScale.value) {
    scaleDraft.value = {
      x: start.x,
      y: start.y,
      x2: start.x,
      y2: start.y,
    };

    return;
  }

  if (['text', 'stamp', 'polyline', 'polygon'].includes(activeTool.value)) {
    return;
  }

  if (activeTool.value === 'pen') {
    draftMarkup.value = {
      page_number: pageNumber.value,
      markup_type: activeTool.value,
      markup_data: {
        ...createBaseMarkupData(),
        points: [start],
      },
    };

    return;
  }

  draftMarkup.value = {
    page_number: pageNumber.value,
    markup_type: activeTool.value,
    markup_data: {
      ...createBaseMarkupData(),
      x: start.x,
      y: start.y,
      x2: start.x,
      y2: start.y,
      label: activeTool.value === 'dimension' ? dimensionLabel.value.trim() : null,
    },
  };
}

function continueDrawing(event) {
  if ((!draftMarkup.value && !scaleDraft.value) || !viewerSurface.value) {
    return;
  }

  const rect = viewerSurface.value.getBoundingClientRect();
  const current = asPercent(event, rect);

  if (scaleDraft.value) {
    scaleDraft.value = {
      ...scaleDraft.value,
      x2: current.x,
      y2: current.y,
    };

    return;
  }

  if (draftMarkup.value?.markup_type === 'pen') {
    const points = [...pathPoints(draftMarkup.value)];
    const lastPoint = points[points.length - 1];

    if (!lastPoint || pointDistance(lastPoint, current) >= 0.35) {
      draftMarkup.value = {
        ...draftMarkup.value,
        markup_data: {
          ...draftMarkup.value.markup_data,
          points: [...points, current],
        },
      };
    }

    return;
  }

  if (['polyline', 'polygon'].includes(draftMarkup.value?.markup_type)) {
    pathPreviewPoint.value = current;
    return;
  }

  draftMarkup.value = {
    ...draftMarkup.value,
    markup_data: {
      ...draftMarkup.value.markup_data,
      x2: current.x,
      y2: current.y,
    },
  };
}

function normalizeMarkupPayload(markup) {
  if (['pen', 'polyline', 'polygon'].includes(markup.markup_type)) {
    const points = pathPoints(markup);
    const minimumPoints = markup.markup_type === 'polygon' ? 3 : 2;

    if (points.length < minimumPoints) {
      return null;
    }

    const bounds = pointListBounds(points);

    return {
      ...markup,
      markup_data: {
        ...markup.markup_data,
        points,
        x: bounds?.x ?? 0,
        y: bounds?.y ?? 0,
        width: bounds?.width ?? 0,
        height: bounds?.height ?? 0,
      },
    };
  }

  if (markup.markup_type === 'text') {
    return markup.markup_data.text ? markup : null;
  }

  const { x, y, x2, y2 } = markup.markup_data;
  const width = Math.abs(x2 - x);
  const height = Math.abs(y2 - y);

  if (width < 0.3 && height < 0.3) {
    return null;
  }

  const normalized = {
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

  if (markup.markup_type === 'dimension') {
    normalized.markup_data.label =
      markup.markup_data.label?.trim() || measuredDistanceLabel(x, y, x2, y2);
  }

  return normalized;
}

async function finishDrawing() {
  if (scaleDraft.value) {
    const { x, y, x2, y2 } = scaleDraft.value;
    const distance = Math.sqrt((x2 - x) ** 2 + (y2 - y) ** 2);

    if (distance >= 0.3) {
      scaleDraft.value = {
        ...scaleDraft.value,
        distance,
      };
    } else {
      scaleDraft.value = null;
    }

    return;
  }

  if (!draftMarkup.value) {
    return;
  }

  if (['polyline', 'polygon'].includes(draftMarkup.value.markup_type)) {
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
  scaleDraft.value = null;
  pathPreviewPoint.value = null;
}

function handleSurfaceLeave() {
  if (isPathDraftActive.value) {
    pathPreviewPoint.value = null;
    return;
  }

  cancelDraft();
}

async function finishPathMarkup() {
  if (!draftMarkup.value || !['polyline', 'polygon'].includes(draftMarkup.value.markup_type)) {
    return;
  }

  const payload = normalizeMarkupPayload(draftMarkup.value);
  draftMarkup.value = null;
  pathPreviewPoint.value = null;

  if (!payload) {
    saveError.value = `Add at least ${activeTool.value === 'polygon' ? 3 : 2} points before saving this markup.`;
    return;
  }

  await storeMarkup(payload);
}

function addPathVertex(point) {
  saveError.value = '';

  if (!draftMarkup.value || draftMarkup.value.markup_type !== activeTool.value) {
    draftMarkup.value = {
      page_number: pageNumber.value,
      markup_type: activeTool.value,
      markup_data: {
        ...createBaseMarkupData(),
        points: [point],
      },
    };
    pathPreviewPoint.value = point;
    return;
  }

  draftMarkup.value = {
    ...draftMarkup.value,
    markup_data: {
      ...draftMarkup.value.markup_data,
      points: [...pathPoints(draftMarkup.value), point],
    },
  };
  pathPreviewPoint.value = point;
}

async function handleSurfaceClick(event) {
  if (!selectedFileId.value || !viewerSurface.value) {
    return;
  }

  const rect = viewerSurface.value.getBoundingClientRect();
  const point = asPercent(event, rect);

  if (['polyline', 'polygon'].includes(activeTool.value)) {
    addPathVertex(point);
    return;
  }

  if (!['text', 'stamp'].includes(activeTool.value)) {
    return;
  }

  saveError.value = '';

  if (activeTool.value === 'text') {
    const text = textContent.value.trim();

    if (!text) {
      saveError.value = 'Enter text before placing a text markup.';
      return;
    }

    await storeMarkup({
      page_number: pageNumber.value,
      markup_type: 'text',
      markup_data: {
        ...createBaseMarkupData(),
        x: point.x,
        y: point.y,
        text,
      },
    });

    return;
  }

  await storeMarkup({
    page_number: pageNumber.value,
    markup_type: 'stamp',
    markup_data: {
      ...createBaseMarkupData(),
      x: point.x,
      y: point.y,
      label: activeStampLabel.value,
      color: currentColor.value,
      bg_color: '#fff7ed',
      border_color: currentColor.value,
    },
  });
}

async function storeMarkup(payload) {
  if (!selectedFileId.value) {
    return;
  }

  isSaving.value = true;
  saveError.value = '';

  try {
    const response = await window.axios.post(
      route('submittals.files.markups.store', [props.submittalId, selectedFileId.value]),
      payload
    );

    markups.value = [...markups.value, response.data.data];
    selectedHistoryMarkupId.value = response.data.data.id;
  } catch {
    saveError.value = 'Could not save markup. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function loadMarkups() {
  if (!selectedFileId.value) {
    markups.value = [];
    selectedHistoryMarkupId.value = null;
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

async function loadPageScales() {
  if (!selectedFileId.value) {
    pageScales.value = [];
    return;
  }

  try {
    const response = await window.axios.get(
      route('submittals.files.page-scales.index', [props.submittalId, selectedFileId.value])
    );

    pageScales.value = response.data.data;
  } catch {
    pageScales.value = [];
  }
}

async function deleteMarkup(markupId) {
  if (!selectedFileId.value || !markupId) {
    return;
  }

  isSaving.value = true;
  saveError.value = '';

  try {
    await window.axios.delete(
      route('submittals.files.markups.destroy', [props.submittalId, selectedFileId.value, markupId])
    );

    markups.value = markups.value.filter((markup) => markup.id !== markupId);

    if (selectedHistoryMarkupId.value === markupId) {
      selectedHistoryMarkupId.value = null;
    }
  } catch {
    saveError.value = 'Could not delete markup. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function updateSelectedMarkup() {
  if (!selectedFileId.value || !selectedHistoryMarkup.value) {
    return;
  }

  const existingMarkup = selectedHistoryMarkup.value;
  const payload = {
    page_number: editPageNumber.value,
    markup_type: existingMarkup.markup_type,
    markup_data: {
      ...existingMarkup.markup_data,
      comment: editComment.value.trim() || null,
      color: editColor.value,
      stroke_width: editStrokeWidth.value,
      opacity: editOpacity.value,
      font_size: editFontSize.value,
      text:
        existingMarkup.markup_type === 'text'
          ? editText.value.trim()
          : existingMarkup.markup_data?.text,
      label: ['stamp', 'dimension'].includes(existingMarkup.markup_type)
        ? editLabel.value.trim()
        : existingMarkup.markup_data?.label,
    },
  };

  isSaving.value = true;
  saveError.value = '';

  try {
    const response = await window.axios.put(
      route('submittals.files.markups.update', [
        props.submittalId,
        selectedFileId.value,
        existingMarkup.id,
      ]),
      payload
    );

    markups.value = markups.value.map((markup) =>
      markup.id === existingMarkup.id ? response.data.data : markup
    );
    pageNumber.value = response.data.data.page_number;
    selectedHistoryMarkupId.value = response.data.data.id;
  } catch {
    saveError.value = 'Could not update markup. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function duplicateSelectedMarkup() {
  if (!selectedHistoryMarkup.value) {
    return;
  }

  const existingMarkup = selectedHistoryMarkup.value;

  await storeMarkup({
    page_number: pageNumber.value,
    markup_type: existingMarkup.markup_type,
    markup_data: {
      ...existingMarkup.markup_data,
      comment: existingMarkup.markup_data?.comment || null,
    },
  });
}

function handleImportFileChange(event) {
  const [file] = event.target.files ?? [];
  importFile.value = file ?? null;
  importFileName.value = file?.name ?? '';
}

async function savePageScale() {
  if (
    !selectedFileId.value ||
    !scaleDraft.value?.distance ||
    !scaleRealLength.value ||
    !scaleUnit.value
  ) {
    saveError.value = 'Draw a calibration line and enter a real-world length and unit first.';
    return;
  }

  isSaving.value = true;
  saveError.value = '';

  try {
    const response = await window.axios.put(
      route('submittals.files.page-scales.upsert', [
        props.submittalId,
        selectedFileId.value,
        pageNumber.value,
      ]),
      {
        calibration_distance: scaleDraft.value.distance,
        real_length: Number(scaleRealLength.value),
        unit: scaleUnit.value.trim(),
      }
    );

    pageScales.value = [
      ...pageScales.value.filter((scale) => scale.page_number !== pageNumber.value),
      response.data.data,
    ].sort((a, b) => a.page_number - b.page_number);

    isCalibratingScale.value = false;
    scaleDraft.value = null;
  } catch {
    saveError.value = 'Could not save page scale. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function clearPageScale() {
  if (!selectedFileId.value) {
    return;
  }

  isSaving.value = true;
  saveError.value = '';

  try {
    await window.axios.delete(
      route('submittals.files.page-scales.destroy', [
        props.submittalId,
        selectedFileId.value,
        pageNumber.value,
      ])
    );

    pageScales.value = pageScales.value.filter((scale) => scale.page_number !== pageNumber.value);
    scaleDraft.value = null;
  } catch {
    saveError.value = 'Could not clear page scale. Try again.';
  } finally {
    isSaving.value = false;
  }
}

async function importMarkups() {
  if (!selectedFileId.value || !importFile.value) {
    return;
  }

  isSaving.value = true;
  saveError.value = '';

  try {
    const formData = new FormData();
    formData.append('markups_file', importFile.value);

    const response = await window.axios.post(
      route('submittals.files.markups.import', [props.submittalId, selectedFileId.value]),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );

    markups.value = response.data.data;
    importFile.value = null;
    importFileName.value = '';
  } catch {
    saveError.value = 'Could not import markups. Make sure the JSON came from a compatible export.';
  } finally {
    isSaving.value = false;
  }
}

async function undoLastMarkup() {
  const latestMarkup = [...currentPageMarkups.value].sort(
    (a, b) => new Date(b.created_at) - new Date(a.created_at)
  )[0];

  if (!latestMarkup) {
    return;
  }

  await deleteMarkup(latestMarkup.id);
}

async function clearCurrentPageMarkups() {
  if (!currentPageMarkups.value.length) {
    return;
  }

  if (!window.confirm(`Delete all markups on page ${pageNumber.value}?`)) {
    return;
  }

  for (const markup of [...currentPageMarkups.value]) {
    await deleteMarkup(markup.id);
  }
}

function selectPdf(fileId) {
  if (selectedFileId.value === fileId) {
    return;
  }

  selectedFileId.value = fileId;
  selectedHistoryMarkupId.value = null;
}

function jumpToMarkup(markup) {
  pageNumber.value = markup.page_number;
  selectedHistoryMarkupId.value = markup.id;
}

function previousPage() {
  pageNumber.value = Math.max(1, pageNumber.value - 1);
}

function nextPage() {
  pageNumber.value += 1;
}

function numericMarkupValue(markup, field, fallback = 0) {
  return Number(markup.markup_data?.[field] ?? fallback);
}

function selectionBounds(markup) {
  if (!markup) {
    return null;
  }

  if (['circle', 'highlight', 'rectangle', 'cloud'].includes(markup.markup_type)) {
    return {
      x: numericMarkupValue(markup, 'x'),
      y: numericMarkupValue(markup, 'y'),
      width: numericMarkupValue(markup, 'width'),
      height: numericMarkupValue(markup, 'height'),
    };
  }

  if (['arrow', 'dimension'].includes(markup.markup_type)) {
    const x1 = numericMarkupValue(markup, 'x');
    const y1 = numericMarkupValue(markup, 'y');
    const x2 = numericMarkupValue(markup, 'x2');
    const y2 = numericMarkupValue(markup, 'y2');

    return {
      x: Math.min(x1, x2),
      y: Math.min(y1, y2),
      width: Math.abs(x2 - x1),
      height: Math.abs(y2 - y1),
    };
  }

  if (markup.markup_type === 'text') {
    const x = numericMarkupValue(markup, 'x');
    const y = numericMarkupValue(markup, 'y');
    const size = numericMarkupValue(markup, 'font_size', 2.8);
    const textLength = (markup.markup_data?.text || '').length;

    return {
      x,
      y: y - size,
      width: Math.max(6, textLength * 1.15),
      height: size + 1.8,
    };
  }

  if (markup.markup_type === 'stamp') {
    return {
      x: numericMarkupValue(markup, 'x') - 10,
      y: numericMarkupValue(markup, 'y') - 3.4,
      width: 20,
      height: 6.8,
    };
  }

  if (['pen', 'polyline', 'polygon'].includes(markup.markup_type)) {
    return pointListBounds(pathPoints(markup));
  }

  return null;
}

function selectionHandlePoints(markup) {
  const bounds = selectionBounds(markup);

  if (!bounds) {
    return [];
  }

  if (['arrow', 'dimension'].includes(markup.markup_type)) {
    return [
      { key: 'start', x: numericMarkupValue(markup, 'x'), y: numericMarkupValue(markup, 'y') },
      { key: 'end', x: numericMarkupValue(markup, 'x2'), y: numericMarkupValue(markup, 'y2') },
    ];
  }

  if (['text', 'stamp'].includes(markup.markup_type)) {
    return [
      {
        key: 'move',
        x: bounds.x + bounds.width / 2,
        y: bounds.y - 1.4,
      },
    ];
  }

  if (['pen', 'polyline', 'polygon'].includes(markup.markup_type)) {
    return [
      {
        key: 'move',
        x: bounds.x + bounds.width / 2,
        y: bounds.y - 1.4,
      },
    ];
  }

  return [
    { key: 'nw', x: bounds.x, y: bounds.y },
    { key: 'ne', x: bounds.x + bounds.width, y: bounds.y },
    { key: 'sw', x: bounds.x, y: bounds.y + bounds.height },
    { key: 'se', x: bounds.x + bounds.width, y: bounds.y + bounds.height },
  ];
}

function updateDimensionLabel(markup) {
  if (markup.markup_type !== 'dimension') {
    return markup;
  }

  const x = numericMarkupValue(markup, 'x');
  const y = numericMarkupValue(markup, 'y');
  const x2 = numericMarkupValue(markup, 'x2');
  const y2 = numericMarkupValue(markup, 'y2');

  return {
    ...markup,
    markup_data: {
      ...markup.markup_data,
      label: measuredDistanceLabel(x, y, x2, y2),
    },
  };
}

function applyInteractionDelta(markup, interaction, currentPoint) {
  const nextMarkup = cloneMarkup(markup);
  const start = interaction.startPoint;
  const deltaX = currentPoint.x - start.x;
  const deltaY = currentPoint.y - start.y;

  if (interaction.mode === 'move') {
    if (['pen', 'polyline', 'polygon'].includes(nextMarkup.markup_type)) {
      nextMarkup.markup_data.points = pathPoints(interaction.original).map((point) => ({
        x: point.x + deltaX,
        y: point.y + deltaY,
      }));

      const bounds = pointListBounds(nextMarkup.markup_data.points);
      nextMarkup.markup_data.x = bounds?.x ?? 0;
      nextMarkup.markup_data.y = bounds?.y ?? 0;
      nextMarkup.markup_data.width = bounds?.width ?? 0;
      nextMarkup.markup_data.height = bounds?.height ?? 0;

      return nextMarkup;
    }

    if (['arrow', 'dimension'].includes(nextMarkup.markup_type)) {
      nextMarkup.markup_data.x = interaction.original.markup_data.x + deltaX;
      nextMarkup.markup_data.y = interaction.original.markup_data.y + deltaY;
      nextMarkup.markup_data.x2 = interaction.original.markup_data.x2 + deltaX;
      nextMarkup.markup_data.y2 = interaction.original.markup_data.y2 + deltaY;
      return updateDimensionLabel(nextMarkup);
    }

    nextMarkup.markup_data.x = interaction.original.markup_data.x + deltaX;
    nextMarkup.markup_data.y = interaction.original.markup_data.y + deltaY;

    if ('x2' in interaction.original.markup_data) {
      nextMarkup.markup_data.x2 = interaction.original.markup_data.x2 + deltaX;
      nextMarkup.markup_data.y2 = interaction.original.markup_data.y2 + deltaY;
    }

    return nextMarkup;
  }

  if (['arrow', 'dimension'].includes(nextMarkup.markup_type)) {
    const fieldPrefix = interaction.handle === 'start' ? '' : '2';
    nextMarkup.markup_data[`x${fieldPrefix}`] = currentPoint.x;
    nextMarkup.markup_data[`y${fieldPrefix}`] = currentPoint.y;

    return updateDimensionLabel(nextMarkup);
  }

  const originalBounds = selectionBounds(interaction.original);

  if (!originalBounds) {
    return nextMarkup;
  }

  let left = originalBounds.x;
  let top = originalBounds.y;
  let right = originalBounds.x + originalBounds.width;
  let bottom = originalBounds.y + originalBounds.height;

  if (interaction.handle.includes('n')) {
    top = currentPoint.y;
  }

  if (interaction.handle.includes('s')) {
    bottom = currentPoint.y;
  }

  if (interaction.handle.includes('w')) {
    left = currentPoint.x;
  }

  if (interaction.handle.includes('e')) {
    right = currentPoint.x;
  }

  const normalizedLeft = Math.min(left, right);
  const normalizedTop = Math.min(top, bottom);
  const normalizedRight = Math.max(left, right);
  const normalizedBottom = Math.max(top, bottom);

  nextMarkup.markup_data.x = normalizedLeft;
  nextMarkup.markup_data.y = normalizedTop;
  nextMarkup.markup_data.width = Math.max(0.5, normalizedRight - normalizedLeft);
  nextMarkup.markup_data.height = Math.max(0.5, normalizedBottom - normalizedTop);
  nextMarkup.markup_data.x2 = normalizedRight;
  nextMarkup.markup_data.y2 = normalizedBottom;

  return nextMarkup;
}

function beginMarkupInteraction(mode, handle, event) {
  if (!selectedCanvasMarkup.value || !viewerSurface.value) {
    return;
  }

  event.stopPropagation();
  event.preventDefault();

  const rect = viewerSurface.value.getBoundingClientRect();

  interactionState.value = {
    mode,
    handle,
    startPoint: asPercent(event, rect),
    original: cloneMarkup(selectedCanvasMarkup.value),
  };

  editDraftMarkup.value = cloneMarkup(selectedCanvasMarkup.value);
}

function isSelectedMarkup(markup) {
  return markup.id && markup.id === selectedHistoryMarkupId.value;
}

function effectiveStrokeWidth(markup) {
  const baseWidth = numericMarkupValue(markup, 'stroke_width', 0.8);
  return isSelectedMarkup(markup) ? baseWidth + 0.4 : baseWidth;
}

async function commitCanvasEdit() {
  if (!editDraftMarkup.value || !selectedHistoryMarkup.value) {
    return;
  }

  const payload = {
    page_number: editDraftMarkup.value.page_number,
    markup_type: editDraftMarkup.value.markup_type,
    markup_data: editDraftMarkup.value.markup_data,
  };

  isSaving.value = true;
  saveError.value = '';

  try {
    const response = await window.axios.put(
      route('submittals.files.markups.update', [
        props.submittalId,
        selectedFileId.value,
        selectedHistoryMarkup.value.id,
      ]),
      payload
    );

    markups.value = markups.value.map((markup) =>
      markup.id === response.data.data.id ? response.data.data : markup
    );
    selectedHistoryMarkupId.value = response.data.data.id;
  } catch {
    saveError.value = 'Could not apply canvas edit. Try again.';
  } finally {
    isSaving.value = false;
    interactionState.value = null;
    editDraftMarkup.value = null;
  }
}

function styleFromMarkup(markup) {
  const color = markup.markup_data?.color || '#ef4444';

  if (markup.markup_type === 'polygon') {
    return {
      stroke: color,
      fill: color,
      fillOpacity: Number(markup.markup_data?.opacity ?? 0.18),
      strokeWidth: effectiveStrokeWidth(markup),
    };
  }

  if (markup.markup_type === 'highlight') {
    return {
      stroke: color,
      fill: color,
      fillOpacity: Number(markup.markup_data?.opacity ?? 0.35),
      strokeWidth: effectiveStrokeWidth(markup),
    };
  }

  return {
    stroke: color,
    fill: 'transparent',
    fillOpacity: 1,
    strokeWidth: effectiveStrokeWidth(markup),
  };
}

function arrowHeadPoints(markup) {
  const x1 = numericMarkupValue(markup, 'x');
  const y1 = numericMarkupValue(markup, 'y');
  const x2 = numericMarkupValue(markup, 'x2');
  const y2 = numericMarkupValue(markup, 'y2');
  const dx = x2 - x1;
  const dy = y2 - y1;
  const length = Math.sqrt(dx * dx + dy * dy) || 1;
  const ux = dx / length;
  const uy = dy / length;
  const size = 1.8;
  const backX = x2 - ux * size;
  const backY = y2 - uy * size;
  const perpX = -uy * 0.75;
  const perpY = ux * 0.75;

  return `${x2},${y2} ${backX + perpX},${backY + perpY} ${backX - perpX},${backY - perpY}`;
}

function dimensionLabelX(markup) {
  return (numericMarkupValue(markup, 'x') + numericMarkupValue(markup, 'x2')) / 2;
}

function dimensionLabelY(markup) {
  return (numericMarkupValue(markup, 'y') + numericMarkupValue(markup, 'y2')) / 2 - 1.8;
}

watch(selectedFileId, async () => {
  if (comparisonFileId.value === selectedFileId.value) {
    comparisonFileId.value = comparisonCandidates.value[0]?.id ?? null;
  }

  await loadMarkups();
  await loadPageScales();
});

watch(
  selectedHistoryMarkup,
  (markup) => {
    syncSelectedMarkupEditor(markup);
  },
  { immediate: true }
);

watch(compareMode, (enabled) => {
  if (!enabled) {
    comparisonFileId.value = null;
    return;
  }

  comparisonFileId.value = comparisonCandidates.value[0]?.id ?? null;
});

watch(
  pdfFiles,
  (nextFiles) => {
    if (!nextFiles.length) {
      selectedFileId.value = null;
      return;
    }

    if (!nextFiles.some((file) => file.id === selectedFileId.value)) {
      selectedFileId.value = null;
    }
  },
  { immediate: true }
);

watch(
  () => props.initialFileId,
  (nextInitialFileId) => {
    selectedFileId.value = nextInitialFileId;
  }
);

watch(
  currentPageScale,
  (scale) => {
    if (!scale) {
      scaleRealLength.value = '';
      scaleUnit.value = 'ft';
      return;
    }

    scaleRealLength.value = String(scale.real_length);
    scaleUnit.value = scale.unit;
  },
  { immediate: true }
);

onMounted(async () => {
  if (selectedFileId.value) {
    await loadMarkups();
    await loadPageScales();
  }
});

function handleWindowPointerMove(event) {
  if (!interactionState.value || !viewerSurface.value || !editDraftMarkup.value) {
    return;
  }

  const rect = viewerSurface.value.getBoundingClientRect();
  const currentPoint = asPercent(event, rect);

  editDraftMarkup.value = applyInteractionDelta(
    interactionState.value.original,
    interactionState.value,
    currentPoint
  );
}

function handleWindowPointerUp() {
  if (!interactionState.value) {
    return;
  }

  void commitCanvasEdit();
}

onMounted(() => {
  window.addEventListener('mousemove', handleWindowPointerMove);
  window.addEventListener('mouseup', handleWindowPointerUp);
});

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', handleWindowPointerMove);
  window.removeEventListener('mouseup', handleWindowPointerUp);
});
</script>

<template>
  <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="space-y-4 border-b border-gray-200 px-6 py-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <h2 class="text-lg font-medium text-gray-900">PDF Markup Workspace</h2>
          <p class="mt-1 text-xs text-gray-500">
            Bluebeam-style review tools: compare versions, annotate by page, add dimensions, manage
            comments, and export markup sets.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50"
            @click="previousPage"
          >
            Prev Page
          </button>
          <div
            class="flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2"
          >
            <label for="pdf-page" class="text-xs font-medium text-gray-600">Page</label>
            <input
              id="pdf-page"
              v-model.number="pageNumber"
              type="number"
              min="1"
              class="w-20 rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
          <button
            type="button"
            class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50"
            @click="nextPage"
          >
            Next Page
          </button>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          v-for="tool in toolOptions"
          :key="tool.value"
          type="button"
          class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold uppercase tracking-widest transition"
          :class="
            activeTool === tool.value
              ? 'border-primary-600 bg-primary-600 text-white'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
          "
          @click="activeTool = tool.value"
        >
          {{ tool.label }}
        </button>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
        <label class="block text-xs font-medium text-gray-600">
          Color
          <input
            v-model="currentColor"
            type="color"
            class="mt-1 block h-10 w-full rounded-md border border-gray-300 bg-white p-1"
          />
        </label>

        <label class="block text-xs font-medium text-gray-600">
          Line Weight
          <select
            v-model.number="currentStrokeWidth"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="0.6">Thin</option>
            <option :value="1.2">Standard</option>
            <option :value="2">Bold</option>
            <option :value="3">Heavy</option>
          </select>
        </label>

        <label class="block text-xs font-medium text-gray-600">
          Opacity
          <select
            v-model.number="currentOpacity"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="0.2">20%</option>
            <option :value="0.35">35%</option>
            <option :value="0.5">50%</option>
            <option :value="0.7">70%</option>
          </select>
        </label>

        <label class="block text-xs font-medium text-gray-600">
          Text Size
          <select
            v-model.number="currentTextSize"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="2">Small</option>
            <option :value="2.8">Standard</option>
            <option :value="3.5">Large</option>
            <option :value="4.5">XL</option>
          </select>
        </label>

        <label class="block text-xs font-medium text-gray-600">
          Comment
          <input
            v-model="commentText"
            type="text"
            maxlength="500"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder="Optional note"
          />
        </label>
      </div>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label
          v-if="activeTool === 'text'"
          class="block text-xs font-medium text-gray-600 xl:col-span-2"
        >
          Text
          <input
            v-model="textContent"
            type="text"
            maxlength="500"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder="Click on the PDF to place this text"
          />
        </label>

        <template v-if="activeTool === 'stamp'">
          <label class="block text-xs font-medium text-gray-600">
            Stamp Preset
            <select
              v-model="selectedStamp"
              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option v-for="stamp in stampOptions" :key="stamp" :value="stamp">{{ stamp }}</option>
            </select>
          </label>

          <label class="block text-xs font-medium text-gray-600 xl:col-span-2">
            Custom Stamp Label
            <input
              v-model="customStampLabel"
              type="text"
              maxlength="100"
              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="Optional override"
            />
          </label>
        </template>

        <label
          v-if="activeTool === 'dimension'"
          class="block text-xs font-medium text-gray-600 xl:col-span-2"
        >
          Dimension Label
          <input
            v-model="dimensionLabel"
            type="text"
            maxlength="100"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
            placeholder='Optional override, e.g. 24"-0"'
          />
        </label>

        <label class="block text-xs font-medium text-gray-600">
          History Scope
          <select
            v-model="historyScope"
            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="current">Current Page</option>
            <option value="all">All Pages</option>
          </select>
        </label>
      </div>

      <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Page Scale</h4>
            <p class="mt-1 text-xs text-gray-500">
              Calibrate this page so dimension markups convert to real units.
            </p>
            <p class="mt-2 text-xs text-gray-600">
              <span v-if="currentPageScale">
                Current scale: {{ Number(currentPageScale.real_length).toFixed(2) }}
                {{ currentPageScale.unit }} per
                {{ Number(currentPageScale.calibration_distance).toFixed(2) }} canvas units
              </span>
              <span v-else>No scale saved for page {{ pageNumber }}.</span>
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-md border px-3 py-2 text-xs font-semibold uppercase tracking-wide transition"
              :class="
                isCalibratingScale
                  ? 'border-primary-600 bg-primary-600 text-white'
                  : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              "
              @click="isCalibratingScale = !isCalibratingScale"
            >
              {{ isCalibratingScale ? 'Cancel Calibration' : 'Draw Scale Line' }}
            </button>
            <button
              type="button"
              class="rounded-md border border-red-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-700 transition hover:bg-red-50"
              @click="clearPageScale"
            >
              Clear Scale
            </button>
          </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
          <label class="block text-xs font-medium text-gray-600">
            Real Length
            <input
              v-model="scaleRealLength"
              type="number"
              min="0"
              step="0.01"
              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="24"
            />
          </label>

          <label class="block text-xs font-medium text-gray-600">
            Unit
            <input
              v-model="scaleUnit"
              type="text"
              maxlength="20"
              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
              placeholder="ft"
            />
          </label>

          <div class="flex items-end">
            <button
              type="button"
              class="w-full rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-primary-700"
              @click="savePageScale"
            >
              Save Page Scale
            </button>
          </div>
        </div>

        <p v-if="scaleDraft?.distance" class="mt-3 text-xs text-gray-500">
          Calibration line captured: {{ scaleDraft.distance.toFixed(2) }} canvas units.
        </p>
      </div>

      <div v-if="pdfFiles.length" class="flex flex-wrap gap-2">
        <button
          v-for="file in pdfFiles"
          :key="file.id"
          type="button"
          class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-medium transition"
          :class="
            selectedFileId === file.id
              ? 'border-primary-600 bg-primary-50 text-primary-700'
              : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
          "
          @click="selectPdf(file.id)"
        >
          {{ file.original_filename }}
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <label
          v-if="pdfFiles.length > 1"
          class="inline-flex items-center gap-2 text-xs font-medium text-gray-600"
        >
          <input
            v-model="compareMode"
            type="checkbox"
            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
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

        <button
          v-if="isPathDraftActive"
          type="button"
          class="rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-primary-700"
          @click="finishPathMarkup"
        >
          Save Path
        </button>

        <button
          v-if="isPathDraftActive"
          type="button"
          class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50"
          @click="cancelDraft"
        >
          Cancel Path
        </button>

        <button
          type="button"
          class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50"
          @click="undoLastMarkup"
        >
          Undo Last
        </button>

        <button
          type="button"
          class="rounded-md border border-red-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-700 transition hover:bg-red-50"
          @click="clearCurrentPageMarkups"
        >
          Clear Page
        </button>

        <label
          class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700"
        >
          <span>Import JSON</span>
          <input
            type="file"
            accept=".json,application/json"
            class="hidden"
            @change="handleImportFileChange"
          />
        </label>

        <span v-if="importFileName" class="text-xs text-gray-500">{{ importFileName }}</span>

        <button
          type="button"
          class="rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
          :disabled="!importFile"
          @click="importMarkups"
        >
          Import Markups
        </button>
      </div>
    </div>

    <div v-if="!pdfFiles.length" class="px-6 py-8 text-center text-sm text-gray-500">
      No PDF files available on this submittal.
    </div>

    <div v-else>
      <div
        class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-6 py-3 text-xs text-gray-500"
      >
        <span v-if="selectedFile">
          Viewing: {{ selectedFile.original_filename }} · Page {{ pageNumber }} ·
          {{ currentPageMarkups.length }} markup{{ currentPageMarkups.length === 1 ? '' : 's' }} on
          page
        </span>
        <div class="flex items-center gap-3">
          <a
            v-if="selectedExportUrl"
            :href="selectedExportUrl"
            class="font-medium text-primary-600 hover:text-primary-800"
          >
            Export Markups
          </a>
          <span v-if="isLoadingMarkups">Loading markups...</span>
          <span v-else-if="isSaving">Saving markup...</span>
          <span v-else>
            {{
              activeTool === 'text' || activeTool === 'stamp'
                ? 'Click to place'
                : ['polyline', 'polygon'].includes(activeTool)
                  ? 'Click to add vertices, then save path'
                  : activeTool === 'pen'
                    ? 'Drag to sketch'
                    : 'Drag to place'
            }}
          </span>
        </div>
      </div>

      <div
        class="grid grid-cols-1 gap-4 p-4"
        :class="
          compareMode && comparisonViewerUrl
            ? 'xl:grid-cols-[minmax(0,1fr)_22rem]'
            : 'xl:grid-cols-[minmax(0,1fr)_22rem]'
        "
      >
        <div class="space-y-4">
          <div
            class="grid grid-cols-1 gap-4"
            :class="compareMode && comparisonViewerUrl ? 'md:grid-cols-2' : 'md:grid-cols-1'"
          >
            <div>
              <p class="mb-2 text-xs font-medium text-gray-600">Primary</p>
              <div
                ref="viewerSurface"
                class="relative h-[60vh] cursor-crosshair bg-gray-900 md:h-[75vh]"
                @mousedown="beginDrawing"
                @mousemove="continueDrawing"
                @mouseup="finishDrawing"
                @mouseleave="handleSurfaceLeave"
                @click="handleSurfaceClick"
              >
                <iframe
                  v-if="selectedPdfViewerUrl"
                  :src="selectedPdfViewerUrl"
                  title="Submittal PDF Viewer"
                  class="h-full w-full"
                />

                <svg
                  viewBox="0 0 100 100"
                  preserveAspectRatio="none"
                  class="pointer-events-none absolute inset-0 h-full w-full"
                >
                  <line
                    v-if="scaleDraft"
                    :x1="scaleDraft.x"
                    :y1="scaleDraft.y"
                    :x2="scaleDraft.x2"
                    :y2="scaleDraft.y2"
                    stroke="#22c55e"
                    stroke-width="1.1"
                    stroke-dasharray="2 1.2"
                  />
                  <template
                    v-for="markup in renderedMarkups"
                    :key="`markup-${markup.id ?? `draft-${markup.markup_type}-${markup.markup_data.x}-${markup.markup_data.y}`}`"
                  >
                    <ellipse
                      v-if="markup.markup_type === 'circle'"
                      :cx="
                        numericMarkupValue(markup, 'x') + numericMarkupValue(markup, 'width') / 2
                      "
                      :cy="
                        numericMarkupValue(markup, 'y') + numericMarkupValue(markup, 'height') / 2
                      "
                      :rx="numericMarkupValue(markup, 'width') / 2"
                      :ry="numericMarkupValue(markup, 'height') / 2"
                      v-bind="styleFromMarkup(markup)"
                    />

                    <rect
                      v-else-if="markup.markup_type === 'highlight'"
                      :x="numericMarkupValue(markup, 'x')"
                      :y="numericMarkupValue(markup, 'y')"
                      :width="numericMarkupValue(markup, 'width')"
                      :height="numericMarkupValue(markup, 'height')"
                      v-bind="styleFromMarkup(markup)"
                    />

                    <rect
                      v-else-if="markup.markup_type === 'rectangle'"
                      :x="numericMarkupValue(markup, 'x')"
                      :y="numericMarkupValue(markup, 'y')"
                      :width="numericMarkupValue(markup, 'width')"
                      :height="numericMarkupValue(markup, 'height')"
                      rx="0.6"
                      v-bind="styleFromMarkup(markup)"
                    />

                    <rect
                      v-else-if="markup.markup_type === 'cloud'"
                      :x="numericMarkupValue(markup, 'x')"
                      :y="numericMarkupValue(markup, 'y')"
                      :width="numericMarkupValue(markup, 'width')"
                      :height="numericMarkupValue(markup, 'height')"
                      rx="3"
                      ry="3"
                      :stroke="markup.markup_data.color || '#9333ea'"
                      :stroke-width="effectiveStrokeWidth(markup)"
                      stroke-dasharray="1.5 1.2"
                      fill="rgba(255,255,255,0.01)"
                    />

                    <g v-else-if="markup.markup_type === 'arrow'">
                      <line
                        :x1="numericMarkupValue(markup, 'x')"
                        :y1="numericMarkupValue(markup, 'y')"
                        :x2="numericMarkupValue(markup, 'x2')"
                        :y2="numericMarkupValue(markup, 'y2')"
                        :stroke="markup.markup_data.color || '#ef4444'"
                        :stroke-width="effectiveStrokeWidth(markup)"
                      />
                      <polygon
                        :points="arrowHeadPoints(markup)"
                        :fill="markup.markup_data.color || '#ef4444'"
                      />
                    </g>

                    <g v-else-if="markup.markup_type === 'dimension'">
                      <line
                        :x1="numericMarkupValue(markup, 'x')"
                        :y1="numericMarkupValue(markup, 'y')"
                        :x2="numericMarkupValue(markup, 'x2')"
                        :y2="numericMarkupValue(markup, 'y2')"
                        :stroke="markup.markup_data.color || '#2563eb'"
                        :stroke-width="effectiveStrokeWidth(markup)"
                      />
                      <circle
                        :cx="numericMarkupValue(markup, 'x')"
                        :cy="numericMarkupValue(markup, 'y')"
                        r="0.8"
                        :fill="markup.markup_data.color || '#2563eb'"
                      />
                      <circle
                        :cx="numericMarkupValue(markup, 'x2')"
                        :cy="numericMarkupValue(markup, 'y2')"
                        r="0.8"
                        :fill="markup.markup_data.color || '#2563eb'"
                      />
                      <rect
                        :x="dimensionLabelX(markup) - 5.5"
                        :y="dimensionLabelY(markup) - 1.9"
                        width="11"
                        height="3.8"
                        rx="0.8"
                        fill="white"
                        fill-opacity="0.85"
                        :stroke="markup.markup_data.color || '#2563eb'"
                        stroke-width="0.35"
                      />
                      <text
                        :x="dimensionLabelX(markup)"
                        :y="dimensionLabelY(markup)"
                        :fill="markup.markup_data.color || '#2563eb'"
                        font-size="1.8"
                        font-weight="700"
                        text-anchor="middle"
                      >
                        {{ markup.markup_data.label || 'DIM' }}
                      </text>
                    </g>

                    <polyline
                      v-else-if="markup.markup_type === 'pen'"
                      :points="pointsString(markup)"
                      fill="none"
                      :stroke="markup.markup_data.color || '#0f766e'"
                      :stroke-width="effectiveStrokeWidth(markup)"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />

                    <polyline
                      v-else-if="markup.markup_type === 'polyline'"
                      :points="pointsString(markup)"
                      fill="none"
                      :stroke="markup.markup_data.color || '#f97316'"
                      :stroke-width="effectiveStrokeWidth(markup)"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />

                    <polygon
                      v-else-if="markup.markup_type === 'polygon'"
                      :points="pointsString(markup)"
                      v-bind="styleFromMarkup(markup)"
                      stroke-linejoin="round"
                    />

                    <text
                      v-else-if="markup.markup_type === 'text'"
                      :x="numericMarkupValue(markup, 'x')"
                      :y="numericMarkupValue(markup, 'y')"
                      :fill="markup.markup_data.color || '#ef4444'"
                      :font-size="numericMarkupValue(markup, 'font_size', 2.8)"
                      font-weight="600"
                    >
                      {{ markup.markup_data.text }}
                    </text>

                    <g v-else-if="markup.markup_type === 'stamp'">
                      <rect
                        :x="Math.max(0, numericMarkupValue(markup, 'x') - 10)"
                        :y="Math.max(0, numericMarkupValue(markup, 'y') - 3.4)"
                        width="20"
                        height="6.8"
                        rx="0.8"
                        :fill="markup.markup_data.bg_color || '#fff7ed'"
                        fill-opacity="0.9"
                        :stroke="
                          markup.markup_data.border_color || markup.markup_data.color || '#b91c1c'
                        "
                        :stroke-width="effectiveStrokeWidth(markup) / 2"
                      />
                      <text
                        :x="numericMarkupValue(markup, 'x')"
                        :y="numericMarkupValue(markup, 'y') + 1.2"
                        :fill="markup.markup_data.color || '#b91c1c'"
                        font-size="1.65"
                        font-weight="700"
                        text-anchor="middle"
                      >
                        {{ markup.markup_data.label || 'STAMP' }}
                      </text>
                    </g>
                  </template>
                </svg>

                <svg
                  v-if="selectedCanvasMarkup"
                  viewBox="0 0 100 100"
                  preserveAspectRatio="none"
                  class="pointer-events-none absolute inset-0 h-full w-full"
                >
                  <g v-if="selectionBounds(selectedCanvasMarkup)">
                    <rect
                      v-if="
                        [
                          'circle',
                          'highlight',
                          'rectangle',
                          'cloud',
                          'text',
                          'stamp',
                          'pen',
                          'polyline',
                          'polygon',
                        ].includes(selectedCanvasMarkup.markup_type)
                      "
                      :x="selectionBounds(selectedCanvasMarkup).x"
                      :y="selectionBounds(selectedCanvasMarkup).y"
                      :width="selectionBounds(selectedCanvasMarkup).width"
                      :height="selectionBounds(selectedCanvasMarkup).height"
                      fill="transparent"
                      stroke="#0f766e"
                      stroke-width="0.45"
                      stroke-dasharray="1.4 1"
                      class="pointer-events-auto cursor-move"
                      @mousedown.stop.prevent="beginMarkupInteraction('move', 'move', $event)"
                    />

                    <line
                      v-if="['arrow', 'dimension'].includes(selectedCanvasMarkup.markup_type)"
                      :x1="numericMarkupValue(selectedCanvasMarkup, 'x')"
                      :y1="numericMarkupValue(selectedCanvasMarkup, 'y')"
                      :x2="numericMarkupValue(selectedCanvasMarkup, 'x2')"
                      :y2="numericMarkupValue(selectedCanvasMarkup, 'y2')"
                      stroke="#0f766e"
                      stroke-width="1.3"
                      stroke-opacity="0.55"
                      class="pointer-events-auto cursor-move"
                      @mousedown.stop.prevent="beginMarkupInteraction('move', 'move', $event)"
                    />

                    <circle
                      v-for="handle in selectionHandlePoints(selectedCanvasMarkup)"
                      :key="`handle-${selectedCanvasMarkup.id}-${handle.key}`"
                      :cx="handle.x"
                      :cy="handle.y"
                      r="1.1"
                      fill="white"
                      stroke="#0f766e"
                      stroke-width="0.5"
                      :class="
                        ['text', 'stamp'].includes(selectedCanvasMarkup.markup_type) ||
                        ['start', 'end'].includes(handle.key)
                          ? 'pointer-events-auto cursor-move'
                          : 'pointer-events-auto cursor-nwse-resize'
                      "
                      @mousedown.stop.prevent="
                        beginMarkupInteraction(
                          handle.key === 'move' ? 'move' : 'resize',
                          handle.key,
                          $event
                        )
                      "
                    />
                  </g>
                </svg>
              </div>
            </div>

            <div v-if="compareMode && comparisonViewerUrl">
              <p class="mb-2 text-xs font-medium text-gray-600">
                Compare: {{ comparisonFile?.original_filename || 'Secondary' }}
              </p>
              <div class="relative h-[60vh] bg-gray-900 md:h-[75vh]">
                <iframe
                  :src="comparisonViewerUrl"
                  title="Submittal PDF Comparison Viewer"
                  class="h-full w-full"
                />
              </div>
            </div>
          </div>
        </div>

        <aside class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/70 p-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Markup History</h3>
            <span class="text-[11px] uppercase tracking-wide text-gray-500">
              {{ historyScope === 'current' ? `Page ${pageNumber}` : 'All Pages' }}
            </span>
          </div>

          <div class="space-y-3 rounded-md border border-gray-200 bg-white p-3">
            <div class="flex items-center justify-between gap-3">
              <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                Review Filters
              </h4>
              <button
                type="button"
                class="text-[11px] font-semibold uppercase tracking-wide text-primary-600 transition hover:text-primary-800"
                @click="resetHistoryFilters"
              >
                Reset
              </button>
            </div>

            <label class="block text-xs font-medium text-gray-600">
              Search
              <input
                v-model="historyQuery"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="Text, label, comment, page..."
              />
            </label>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <label class="block text-xs font-medium text-gray-600">
                Type
                <select
                  v-model="historyTypeFilter"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="all">All Types</option>
                  <option
                    v-for="markupType in availableMarkupTypes"
                    :key="markupType"
                    :value="markupType"
                  >
                    {{ formatMarkupType(markupType) }}
                  </option>
                </select>
              </label>

              <label class="block text-xs font-medium text-gray-600">
                Author
                <select
                  v-model="historyAuthorFilter"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option value="all">All Authors</option>
                  <option v-for="author in availableMarkupAuthors" :key="author" :value="author">
                    {{ author }}
                  </option>
                </select>
              </label>
            </div>

            <label class="inline-flex items-center gap-2 text-xs font-medium text-gray-600">
              <input
                v-model="commentsOnly"
                type="checkbox"
                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
              />
              Only show markups with comments
            </label>
          </div>

          <div class="space-y-3 rounded-md border border-gray-200 bg-white p-3">
            <div class="flex items-center justify-between gap-3">
              <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-600">Layers</h4>
              <span class="text-[11px] uppercase tracking-wide text-gray-400">
                {{ visibleMarkupTypes.length }}/{{ toolOptions.length }} visible
              </span>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                v-for="tool in toolOptions"
                :key="`layer-${tool.value}`"
                type="button"
                class="rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide transition"
                :class="
                  hiddenMarkupTypes[tool.value]
                    ? 'border-gray-200 bg-gray-100 text-gray-400'
                    : 'border-primary-200 bg-primary-50 text-primary-700'
                "
                @click="toggleMarkupTypeVisibility(tool.value)"
              >
                {{ tool.label }}
              </button>
            </div>
          </div>

          <div v-if="markupHistory.length" class="mt-3 space-y-2 text-xs text-gray-600">
            <button
              v-for="item in markupHistory"
              :key="`history-${item.id}`"
              type="button"
              class="block w-full rounded-md border px-3 py-3 text-left transition"
              :class="
                selectedHistoryMarkupId === item.id
                  ? 'border-primary-500 bg-primary-50'
                  : 'border-gray-200 bg-white hover:border-gray-300'
              "
              @click="jumpToMarkup(item)"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-gray-800">
                      {{ formatMarkupType(item.markup_type) }}
                    </span>
                    <span
                      class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600"
                    >
                      Page {{ item.page_number }}
                    </span>
                  </div>
                  <div class="mt-1 flex flex-wrap items-center gap-3 text-gray-500">
                    <span>{{ item.user?.name || 'Unknown' }}</span>
                    <span>{{ formatMarkupDate(item.created_at) }}</span>
                  </div>
                  <p v-if="item.markup_data?.text" class="mt-2 text-gray-700">
                    {{ item.markup_data.text }}
                  </p>
                  <p v-else-if="item.markup_data?.label" class="mt-2 text-gray-700">
                    {{ item.markup_data.label }}
                  </p>
                  <p v-if="item.markup_data?.comment" class="mt-2 text-gray-500">
                    {{ item.markup_data.comment }}
                  </p>
                </div>
                <button
                  type="button"
                  class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-red-600 transition hover:text-red-800"
                  @click.stop="deleteMarkup(item.id)"
                >
                  Delete
                </button>
              </div>
            </button>
          </div>
          <p v-else class="mt-3 text-xs text-gray-500">No saved markups yet.</p>

          <div v-if="selectedHistoryMarkup" class="rounded-md border border-gray-200 bg-white p-3">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h4 class="text-sm font-semibold text-gray-900">
                  Edit {{ formatMarkupType(selectedHistoryMarkup.markup_type) }}
                </h4>
                <p class="mt-1 text-[11px] uppercase tracking-wide text-gray-500">
                  Markup #{{ selectedHistoryMarkup.id }}
                </p>
              </div>
              <button
                type="button"
                class="text-[11px] font-semibold uppercase tracking-wide text-primary-600 transition hover:text-primary-800"
                @click="duplicateSelectedMarkup"
              >
                Duplicate
              </button>
            </div>

            <div class="mt-3 space-y-3">
              <label class="block text-xs font-medium text-gray-600">
                Page
                <input
                  v-model.number="editPageNumber"
                  type="number"
                  min="1"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                />
              </label>

              <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs font-medium text-gray-600">
                  Color
                  <input
                    v-model="editColor"
                    type="color"
                    class="mt-1 block h-10 w-full rounded-md border border-gray-300 bg-white p-1"
                  />
                </label>

                <label class="block text-xs font-medium text-gray-600">
                  Line Weight
                  <select
                    v-model.number="editStrokeWidth"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                  >
                    <option :value="0.6">Thin</option>
                    <option :value="1.2">Standard</option>
                    <option :value="2">Bold</option>
                    <option :value="3">Heavy</option>
                  </select>
                </label>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <label class="block text-xs font-medium text-gray-600">
                  Opacity
                  <select
                    v-model.number="editOpacity"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                  >
                    <option :value="0.2">20%</option>
                    <option :value="0.35">35%</option>
                    <option :value="0.5">50%</option>
                    <option :value="0.7">70%</option>
                  </select>
                </label>

                <label class="block text-xs font-medium text-gray-600">
                  Text Size
                  <select
                    v-model.number="editFontSize"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                  >
                    <option :value="2">Small</option>
                    <option :value="2.8">Standard</option>
                    <option :value="3.5">Large</option>
                    <option :value="4.5">XL</option>
                  </select>
                </label>
              </div>

              <label
                v-if="selectedHistoryMarkup.markup_type === 'text'"
                class="block text-xs font-medium text-gray-600"
              >
                Text
                <input
                  v-model="editText"
                  type="text"
                  maxlength="500"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                />
              </label>

              <label
                v-if="['stamp', 'dimension'].includes(selectedHistoryMarkup.markup_type)"
                class="block text-xs font-medium text-gray-600"
              >
                Label
                <input
                  v-model="editLabel"
                  type="text"
                  maxlength="100"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                />
              </label>

              <label class="block text-xs font-medium text-gray-600">
                Comment
                <textarea
                  v-model="editComment"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500"
                />
              </label>

              <button
                type="button"
                class="w-full rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-primary-700"
                @click="updateSelectedMarkup"
              >
                Save Markup Changes
              </button>
            </div>
          </div>
        </aside>
      </div>

      <div v-if="saveError" class="border-t border-gray-200 px-6 py-3 text-sm text-red-600">
        {{ saveError }}
      </div>
    </div>
  </div>
</template>
