<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  availableNodeTypes: Array,
  initialNodes: Array,
  initialEdges: Array,
});

const canvasWidth = 980;
const canvasHeight = 520;
const nodeWidth = 170;
const nodeHeight = 74;

const palette = {
  trigger: 'from-emerald-400/80 to-emerald-500/80',
  action: 'from-sky-400/80 to-blue-500/80',
  condition: 'from-amber-400/80 to-orange-500/80',
  delay: 'from-violet-400/80 to-purple-500/80',
  notification: 'from-fuchsia-400/80 to-pink-500/80',
};

const form = reactive({
  label: '',
  type: props.availableNodeTypes[0]?.value ?? 'action',
});

const edgeForm = reactive({
  from: props.initialNodes[0]?.id ?? '',
  to: props.initialNodes[1]?.id ?? '',
});

const nodes = ref(props.initialNodes.map((node) => ({ ...node })));
const edges = ref(props.initialEdges.map((edge) => ({ ...edge })));

const selectedNodeId = ref(nodes.value[0]?.id ?? null);
const dragging = reactive({
  id: null,
  offsetX: 0,
  offsetY: 0,
});

const selectedNode = computed(() => nodes.value.find((node) => node.id === selectedNodeId.value) ?? null);

const connectedNodeIds = computed(() => {
  if (!selectedNodeId.value) {
    return [];
  }

  return edges.value
    .filter((edge) => edge.from === selectedNodeId.value || edge.to === selectedNodeId.value)
    .map((edge) => (edge.from === selectedNodeId.value ? edge.to : edge.from));
});

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function addNode() {
  if (!form.label.trim()) {
    return;
  }

  const id = `n${Date.now()}`;
  const count = nodes.value.length;
  const x = 40 + (count % 4) * 220;
  const y = 36 + Math.floor(count / 4) * 130;

  nodes.value.push({
    id,
    label: form.label.trim(),
    type: form.type,
    x: clamp(x, 20, canvasWidth - nodeWidth - 20),
    y: clamp(y, 20, canvasHeight - nodeHeight - 20),
  });

  selectedNodeId.value = id;
  edgeForm.from = id;
  edgeForm.to = nodes.value.find((node) => node.id !== id)?.id ?? id;
  form.label = '';
}

function addEdge() {
  if (!edgeForm.from || !edgeForm.to || edgeForm.from === edgeForm.to) {
    return;
  }

  const duplicate = edges.value.some(
    (edge) => edge.from === edgeForm.from && edge.to === edgeForm.to
  );

  if (duplicate) {
    return;
  }

  edges.value.push({
    id: `e${Date.now()}`,
    from: edgeForm.from,
    to: edgeForm.to,
  });
}

function removeSelectedNode() {
  if (!selectedNode.value) {
    return;
  }

  const id = selectedNode.value.id;
  nodes.value = nodes.value.filter((node) => node.id !== id);
  edges.value = edges.value.filter((edge) => edge.from !== id && edge.to !== id);
  selectedNodeId.value = nodes.value[0]?.id ?? null;
  edgeForm.from = nodes.value[0]?.id ?? '';
  edgeForm.to = nodes.value[1]?.id ?? nodes.value[0]?.id ?? '';
}

function startDrag(event, node) {
  const rect = event.currentTarget.getBoundingClientRect();
  dragging.id = node.id;
  dragging.offsetX = event.clientX - rect.left;
  dragging.offsetY = event.clientY - rect.top;
  selectedNodeId.value = node.id;
}

function onCanvasMove(event) {
  if (!dragging.id) {
    return;
  }

  const canvasRect = event.currentTarget.getBoundingClientRect();
  const node = nodes.value.find((candidate) => candidate.id === dragging.id);

  if (!node) {
    return;
  }

  const x = event.clientX - canvasRect.left - dragging.offsetX;
  const y = event.clientY - canvasRect.top - dragging.offsetY;

  node.x = clamp(x, 8, canvasWidth - nodeWidth - 8);
  node.y = clamp(y, 8, canvasHeight - nodeHeight - 8);
}

function endDrag() {
  dragging.id = null;
}

function nodeCenter(node) {
  return {
    x: node.x + nodeWidth / 2,
    y: node.y + nodeHeight / 2,
  };
}

function edgePath(edge) {
  const from = nodes.value.find((node) => node.id === edge.from);
  const to = nodes.value.find((node) => node.id === edge.to);

  if (!from || !to) {
    return '';
  }

  const start = nodeCenter(from);
  const end = nodeCenter(to);
  const delta = Math.abs(end.x - start.x) * 0.45;

  return `M ${start.x} ${start.y} C ${start.x + delta} ${start.y}, ${end.x - delta} ${end.y}, ${end.x} ${end.y}`;
}

function nodeStyle(node) {
  return {
    left: `${node.x}px`,
    top: `${node.y}px`,
  };
}

function nodeTypeLabel(type) {
  return props.availableNodeTypes.find((candidate) => candidate.value === type)?.label ?? type;
}
</script>

<template>
  <AppLayout>
    <Head title="Admin - Agent Flow Designer" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Agent Flow Designer</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
              Visual node layout GUI for creating workflow routes.
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
            <Link
              :href="route('admin.backups.index')"
              class="inline-flex items-center rounded-md border border-white/30 bg-white/40 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 backdrop-blur-md transition hover:bg-white/60 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200 dark:hover:bg-slate-900/60"
            >
              Data Backup
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
          <div
            class="space-y-6 rounded-2xl border border-white/40 bg-white/60 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:bg-slate-900/50 dark:shadow-black/30 xl:col-span-1"
          >
            <div>
              <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
                Add Node
              </h2>
              <div class="mt-3 space-y-3">
                <input
                  v-model="form.label"
                  type="text"
                  placeholder="Node label"
                  class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <select
                  v-model="form.type"
                  class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option v-for="type in availableNodeTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </option>
                </select>
                <button
                  type="button"
                  class="inline-flex w-full items-center justify-center rounded-md bg-primary-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-primary-700"
                  @click="addNode"
                >
                  Add Node
                </button>
              </div>
            </div>

            <div>
              <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
                Connect Nodes
              </h2>
              <div class="mt-3 space-y-3">
                <select
                  v-model="edgeForm.from"
                  class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option v-for="node in nodes" :key="`from-${node.id}`" :value="node.id">
                    From: {{ node.label }}
                  </option>
                </select>
                <select
                  v-model="edgeForm.to"
                  class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                  <option v-for="node in nodes" :key="`to-${node.id}`" :value="node.id">
                    To: {{ node.label }}
                  </option>
                </select>
                <button
                  type="button"
                  class="inline-flex w-full items-center justify-center rounded-md bg-slate-700 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-slate-800 dark:bg-slate-600 dark:hover:bg-slate-500"
                  @click="addEdge"
                >
                  Add Route
                </button>
              </div>
            </div>

            <div>
              <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
                Selection
              </h2>
              <div
                class="mt-3 rounded-xl border border-white/40 bg-white/50 p-3 text-sm text-gray-700 dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-slate-300"
              >
                <p v-if="selectedNode" class="font-semibold">{{ selectedNode.label }}</p>
                <p v-if="selectedNode" class="text-xs uppercase tracking-wider text-gray-500 dark:text-slate-400">
                  {{ nodeTypeLabel(selectedNode.type) }}
                </p>
                <p class="mt-2 text-xs">
                  Connected nodes: {{ connectedNodeIds.length }}
                </p>
                <button
                  type="button"
                  class="mt-3 inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-red-700 disabled:opacity-50"
                  :disabled="!selectedNode"
                  @click="removeSelectedNode"
                >
                  Remove Selected Node
                </button>
              </div>
            </div>
          </div>

          <div
            class="relative overflow-hidden rounded-2xl border border-white/40 bg-white/60 p-4 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:bg-slate-900/50 dark:shadow-black/30 xl:col-span-3"
          >
            <div class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-slate-300">
              Drag nodes to lay out your route map
            </div>
            <div
              class="relative rounded-xl border border-white/40 bg-gradient-to-br from-sky-50/70 via-white/50 to-violet-100/50 dark:border-slate-700/60 dark:from-slate-900/80 dark:via-slate-900/60 dark:to-violet-950/30"
              :style="{ width: `${canvasWidth}px`, height: `${canvasHeight}px` }"
              @mousemove="onCanvasMove"
              @mouseup="endDrag"
              @mouseleave="endDrag"
            >
              <svg
                class="pointer-events-none absolute inset-0 h-full w-full"
                :viewBox="`0 0 ${canvasWidth} ${canvasHeight}`"
                fill="none"
              >
                <defs>
                  <marker
                    id="arrowhead"
                    markerWidth="10"
                    markerHeight="7"
                    refX="9"
                    refY="3.5"
                    orient="auto"
                  >
                    <polygon points="0 0, 10 3.5, 0 7" class="fill-slate-500 dark:fill-slate-300" />
                  </marker>
                </defs>

                <path
                  v-for="edge in edges"
                  :key="edge.id"
                  :d="edgePath(edge)"
                  class="stroke-slate-500/80 dark:stroke-slate-300/80"
                  stroke-width="2.2"
                  marker-end="url(#arrowhead)"
                />
              </svg>

              <button
                v-for="node in nodes"
                :key="node.id"
                type="button"
                class="absolute flex h-[74px] w-[170px] cursor-grab select-none flex-col justify-center rounded-xl border border-white/50 bg-gradient-to-br px-3 text-left text-white shadow-lg backdrop-blur-sm active:cursor-grabbing"
                :class="[
                  palette[node.type] ?? 'from-slate-400/80 to-slate-500/80',
                  selectedNodeId === node.id
                    ? 'ring-2 ring-primary-500 ring-offset-2 ring-offset-transparent'
                    : '',
                ]"
                :style="nodeStyle(node)"
                @mousedown="startDrag($event, node)"
                @click="selectedNodeId = node.id"
              >
                <span class="truncate text-sm font-semibold">{{ node.label }}</span>
                <span class="mt-1 text-[10px] uppercase tracking-[0.1em] text-white/90">
                  {{ nodeTypeLabel(node.type) }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
