<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  boost: Object,
});

const mcpChecking = ref(false);
const mcpAvailable = ref(Boolean(props.boost?.mcp));
const mcpError = ref(false);
const minimumMcpActivityMs = 900;
const browserLogs = ref([]);
const browserLogsLoading = ref(false);
const browserLogsClearing = ref(false);
let mcpPoller = null;
let browserLogsPoller = null;

const mcpIndicatorClass = computed(() => {
  if (mcpChecking.value) {
    return 'bg-emerald-500 ring-4 ring-emerald-200/70 dark:ring-emerald-500/20';
  }

  if (mcpError.value) {
    return 'bg-red-500';
  }

  if (mcpAvailable.value) {
    return 'bg-emerald-300 dark:bg-emerald-400';
  }

  return 'bg-slate-300 dark:bg-slate-600';
});

const mcpIndicatorText = computed(() => {
  if (mcpChecking.value) {
    return 'Accessing';
  }

  if (mcpError.value) {
    return 'Unavailable';
  }

  return mcpAvailable.value ? 'Idle' : 'Off';
});

async function checkMcpStatus() {
  if (!props.boost?.mcp) {
    mcpAvailable.value = false;
    return;
  }

  const startedAt = Date.now();
  mcpChecking.value = true;
  mcpError.value = false;

  try {
    const response = await window.axios.get(route('admin.boost.mcp-status'));
    mcpAvailable.value = Boolean(response.data?.mcp_enabled);
  } catch {
    mcpAvailable.value = false;
    mcpError.value = true;
  } finally {
    const elapsed = Date.now() - startedAt;

    if (elapsed < minimumMcpActivityMs) {
      await new Promise((resolve) => window.setTimeout(resolve, minimumMcpActivityMs - elapsed));
    }

    mcpChecking.value = false;
  }
}

async function fetchBrowserLogs() {
  browserLogsLoading.value = true;

  try {
    const response = await window.axios.get(route('admin.boost.browser-logs'));
    browserLogs.value = Array.isArray(response.data?.logs) ? response.data.logs : [];
  } catch {
    browserLogs.value = [];
  } finally {
    browserLogsLoading.value = false;
  }
}

async function clearBrowserLogs() {
  browserLogsClearing.value = true;

  try {
    await window.axios.delete(route('admin.boost.browser-logs.clear'));
    browserLogs.value = [];
  } finally {
    browserLogsClearing.value = false;
  }
}

onMounted(() => {
  checkMcpStatus();
  fetchBrowserLogs();
  mcpPoller = setInterval(checkMcpStatus, 5000);
  browserLogsPoller = setInterval(fetchBrowserLogs, 3000);
});

onBeforeUnmount(() => {
  if (mcpPoller !== null) {
    clearInterval(mcpPoller);
  }

  if (browserLogsPoller !== null) {
    clearInterval(browserLogsPoller);
  }
});
</script>

<template>
  <AppLayout>
    <Head title="Admin - Boost" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Boost Workspace</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
              Laravel Boost capabilities and recommended quality workflow.
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
              :href="route('admin.backups.index')"
              class="inline-flex items-center rounded-md border border-white/30 bg-white/40 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 backdrop-blur-md transition hover:bg-white/60 dark:border-slate-600/60 dark:bg-slate-900/40 dark:text-slate-200 dark:hover:bg-slate-900/60"
            >
              Data Backup
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
          >
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
              Status
            </h2>
            <p class="mt-3 text-sm text-gray-700 dark:text-slate-300">
              Boost package: <span class="font-semibold">{{ boost.installed ? 'Detected' : 'Missing' }}</span>
            </p>
            <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-slate-300">
              <li class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40">
                <span>Guidelines</span>
                <span class="font-semibold">{{ boost.guidelines ? 'On' : 'Off' }}</span>
              </li>
              <li class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40">
                <span>MCP</span>
                <span class="font-semibold">{{ boost.mcp ? 'On' : 'Off' }}</span>
              </li>
              <li class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40">
                <span>MCP Activity</span>
                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider">
                  <span class="h-2.5 w-2.5 rounded-full transition-all duration-150" :class="mcpIndicatorClass" />
                  {{ mcpIndicatorText }}
                </span>
              </li>
              <li class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40">
                <span>Sail</span>
                <span class="font-semibold">{{ boost.sail ? 'On' : 'Off' }}</span>
              </li>
            </ul>
          </div>

          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-cyan-100/40 p-5 shadow-xl shadow-cyan-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-cyan-900/20 dark:shadow-black/30"
          >
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
              Recommended Commands
            </h2>
            <div class="mt-3 space-y-2">
              <code
                v-for="command in boost.commands"
                :key="command"
                class="block rounded-lg border border-white/40 bg-white/50 px-3 py-2 text-xs text-gray-800 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200"
              >
                {{ command }}
              </code>
            </div>
          </div>

          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-violet-100/40 p-5 shadow-xl shadow-violet-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-violet-900/20 dark:shadow-black/30"
          >
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
              Active Agents
            </h2>
            <div class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="agent in boost.agents"
                :key="agent"
                class="rounded-full border border-white/40 bg-white/60 px-3 py-1 text-xs font-semibold text-gray-800 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200"
              >
                {{ agent }}
              </span>
              <span
                v-if="boost.agents.length === 0"
                class="rounded-full border border-white/40 bg-white/60 px-3 py-1 text-xs font-semibold text-gray-800 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200"
              >
                none
              </span>
            </div>

            <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
              Skills
            </h3>
            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="skill in boost.skills"
                :key="skill"
                class="rounded-full border border-white/40 bg-white/60 px-3 py-1 text-xs text-gray-700 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-300"
              >
                {{ skill }}
              </span>
              <span
                v-if="boost.skills.length === 0"
                class="rounded-full border border-white/40 bg-white/60 px-3 py-1 text-xs text-gray-700 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-300"
              >
                none
              </span>
            </div>
          </div>
        </div>

        <div
          class="mt-6 rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200">
              Live MCP Browser Logs
            </h2>
            <button
              type="button"
              :disabled="browserLogsClearing"
              class="inline-flex items-center rounded-md border border-white/30 bg-white/50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 transition hover:bg-white/60 disabled:opacity-60 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200 dark:hover:bg-slate-900/60"
              @click="clearBrowserLogs"
            >
              Reset
            </button>
          </div>

          <div
            class="mt-4 h-64 overflow-y-auto rounded-lg border border-slate-200/70 bg-slate-950/95 px-4 py-3 font-mono text-xs leading-5 text-emerald-300 dark:border-slate-700"
          >
            <p v-if="browserLogsLoading && browserLogs.length === 0" class="text-slate-400">
              Loading logs...
            </p>
            <p v-else-if="browserLogs.length === 0" class="text-slate-400">
              No browser logs yet.
            </p>
            <pre v-else class="whitespace-pre-wrap">{{ browserLogs.join('\n') }}</pre>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
