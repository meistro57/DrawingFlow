<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  boost: Object,
});

// ── MCP Status ───────────────────────────────────────────────────────────────
const mcpChecking = ref(false);
const mcpAvailable = ref(Boolean(props.boost?.mcp));
const mcpError = ref(false);
const minimumMcpActivityMs = 900;
const lastCheckedSeconds = ref(0);

const mcpBgColor = computed(() => {
  if (mcpChecking.value) return 'bg-emerald-500';
  if (mcpError.value) return 'bg-red-500';
  if (mcpAvailable.value) return 'bg-emerald-400 dark:bg-emerald-500';
  return 'bg-slate-300 dark:bg-slate-600';
});

const mcpIndicatorText = computed(() => {
  if (mcpChecking.value) return 'Accessing';
  if (mcpError.value) return 'Unavailable';
  return mcpAvailable.value ? 'Idle' : 'Off';
});

const lastCheckedLabel = computed(() => {
  const s = lastCheckedSeconds.value;
  return s < 60 ? `${s}s ago` : `${Math.floor(s / 60)}m ago`;
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
    lastCheckedSeconds.value = 0;
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

// ── MCP Stats ────────────────────────────────────────────────────────────────
const mcpStats = ref(null);

async function fetchMcpStats() {
  try {
    const response = await window.axios.get(route('admin.boost.mcp-stats'));
    mcpStats.value = response.data;
  } catch {
    /* silent */
  }
}

// ── Gauge ────────────────────────────────────────────────────────────────────
const GAUGE_R = 80;
const CIRCUMFERENCE = 2 * Math.PI * GAUGE_R;
const ARC_LENGTH = CIRCUMFERENCE * (270 / 360);
const gaugeTrackDash = `${ARC_LENGTH} ${CIRCUMFERENCE - ARC_LENGTH}`;

const gaugeFillDash = computed(() => {
  const pct = Math.min(100, Math.max(0, mcpStats.value?.uptime_percent ?? 0));
  return `${(ARC_LENGTH * pct) / 100} ${CIRCUMFERENCE}`;
});

const gaugeStroke = computed(() => {
  const pct = mcpStats.value?.uptime_percent ?? 0;
  if (pct >= 90) return '#10b981';
  if (pct >= 50) return '#f59e0b';
  return '#ef4444';
});

const gaugeTextClass = computed(() => {
  const pct = mcpStats.value?.uptime_percent ?? 0;
  if (pct >= 90) return 'text-emerald-500 dark:text-emerald-400';
  if (pct >= 50) return 'text-amber-500 dark:text-amber-400';
  return 'text-red-500 dark:text-red-400';
});

// ── Sparkline ────────────────────────────────────────────────────────────────
const hoveredBar = ref(null);

const sparklineBars = computed(() => {
  const timeline =
    mcpStats.value?.activity_timeline ??
    Array.from({ length: 24 }, (_, i) => ({ hour: i, label: '', count: 0 }));
  const maxCount = Math.max(1, ...timeline.map((b) => b.count));
  const currentHour = new Date().getHours();

  return timeline.map((bucket) => ({
    heightPct: (bucket.count / maxCount) * 100,
    count: bucket.count,
    hour: bucket.hour,
    label: bucket.label,
    isCurrent: bucket.hour === currentHour,
  }));
});

// ── Relative Time ────────────────────────────────────────────────────────────
function relativeTime(iso) {
  if (!iso) return 'Never';
  const s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 5) return 'just now';
  if (s < 60) return `${s}s ago`;
  if (s < 3600) return `${Math.floor(s / 60)}m ago`;
  if (s < 86400) return `${Math.floor(s / 3600)}h ago`;
  return `${Math.floor(s / 86400)}d ago`;
}

// ── Browser Logs ─────────────────────────────────────────────────────────────
const browserLogs = ref([]);
const browserLogsLoading = ref(false);
const browserLogsClearing = ref(false);
const autoScroll = ref(true);
const logContainer = ref(null);

function logLineClass(line) {
  if (line.includes('mcp.ONLINE')) return 'text-emerald-400';
  if (line.includes('mcp.OFFLINE')) return 'text-orange-400';
  if (/error/i.test(line)) return 'text-red-400';
  return 'text-slate-400';
}

watch(browserLogs, async () => {
  if (autoScroll.value && logContainer.value) {
    await nextTick();
    logContainer.value.scrollTop = logContainer.value.scrollHeight;
  }
});

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
    mcpStats.value = null;
    await fetchMcpStats();
  } finally {
    browserLogsClearing.value = false;
  }
}

// ── Pollers ──────────────────────────────────────────────────────────────────
let mcpPoller = null;
let browserLogsPoller = null;
let mcpStatsPoller = null;
let lastCheckedTimer = null;

onMounted(() => {
  checkMcpStatus();
  fetchBrowserLogs();
  fetchMcpStats();
  mcpPoller = setInterval(checkMcpStatus, 5000);
  browserLogsPoller = setInterval(fetchBrowserLogs, 3000);
  mcpStatsPoller = setInterval(fetchMcpStats, 10000);
  lastCheckedTimer = setInterval(() => {
    lastCheckedSeconds.value++;
  }, 1000);
});

onBeforeUnmount(() => {
  clearInterval(mcpPoller);
  clearInterval(browserLogsPoller);
  clearInterval(mcpStatsPoller);
  clearInterval(lastCheckedTimer);
});
</script>

<template>
  <AppLayout>
    <Head title="Admin - Boost" />

    <div class="py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- ── Page Header ── -->
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

        <!-- ── Row 1: MCP Health Gauge + Activity Sparkline ── -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- MCP Health Gauge -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-emerald-100/30 p-5 shadow-xl shadow-emerald-100/40 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-emerald-900/10 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
              MCP Health
            </h2>

            <!-- Arc gauge -->
            <div class="relative mx-auto mt-4 h-44 w-44">
              <svg viewBox="0 0 200 200" class="h-full w-full">
                <!-- Track arc -->
                <circle
                  cx="100"
                  cy="100"
                  r="80"
                  fill="none"
                  stroke="#94a3b8"
                  stroke-opacity="0.2"
                  stroke-width="14"
                  :stroke-dasharray="gaugeTrackDash"
                  stroke-linecap="round"
                  transform="rotate(135 100 100)"
                />
                <!-- Fill arc -->
                <circle
                  cx="100"
                  cy="100"
                  r="80"
                  fill="none"
                  :stroke="gaugeStroke"
                  stroke-width="14"
                  :stroke-dasharray="gaugeFillDash"
                  stroke-linecap="round"
                  transform="rotate(135 100 100)"
                  style="transition: stroke-dasharray 0.6s cubic-bezier(0.4, 0, 0.2, 1)"
                />
              </svg>

              <!-- Center content -->
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span
                  v-if="mcpStats"
                  :class="gaugeTextClass"
                  class="text-3xl font-bold tabular-nums leading-none"
                >
                  {{ mcpStats.uptime_percent }}%
                </span>
                <span v-else class="text-3xl font-bold text-slate-300 dark:text-slate-600">—</span>
                <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">uptime</span>

                <!-- Pulsing dot when checking -->
                <div v-if="mcpChecking" class="mt-2.5 flex items-center gap-1.5">
                  <span class="relative flex h-2 w-2">
                    <span
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                    />
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
                  </span>
                  <span class="text-xs font-medium text-emerald-500 dark:text-emerald-400"
                    >live</span
                  >
                </div>
              </div>
            </div>

            <!-- Status indicator -->
            <div class="mt-5 flex flex-col items-center gap-1.5">
              <div class="flex items-center gap-2.5">
                <span class="relative flex h-5 w-5">
                  <span
                    v-if="mcpChecking"
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"
                  />
                  <span
                    class="relative inline-flex h-5 w-5 rounded-full transition-all duration-300"
                    :class="[
                      mcpBgColor,
                      mcpChecking ? 'ring-4 ring-emerald-300/60 dark:ring-emerald-500/40' : '',
                    ]"
                  />
                </span>
                <span
                  class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
                >
                  {{ mcpIndicatorText }}
                </span>
              </div>
              <p class="text-xs text-slate-400 dark:text-slate-500">
                checked {{ lastCheckedLabel }}
              </p>
            </div>
          </div>

          <!-- Activity Sparkline + Stats -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-indigo-100/30 p-5 shadow-xl shadow-indigo-100/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-indigo-900/10 dark:shadow-black/30 lg:col-span-2"
          >
            <div class="flex items-center justify-between">
              <h2
                class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
              >
                24-Hour Activity
              </h2>
              <span class="text-xs text-slate-400 dark:text-slate-500">
                {{ mcpStats?.checks_last_24h ?? 0 }} checks today
              </span>
            </div>

            <!-- CSS flex bar chart -->
            <div class="mt-4">
              <div
                class="relative flex h-16 items-end gap-px rounded bg-slate-100/60 px-1 py-1 dark:bg-slate-800/40"
              >
                <!-- Baseline -->
                <div
                  class="pointer-events-none absolute inset-x-1 bottom-1 h-px bg-slate-300/60 dark:bg-slate-600/40"
                />

                <div
                  v-for="bar in sparklineBars"
                  :key="bar.hour"
                  class="group relative flex-1 cursor-pointer rounded-t-sm transition-all duration-150"
                  :class="
                    bar.isCurrent
                      ? 'bg-indigo-400 dark:bg-indigo-500'
                      : hoveredBar?.hour === bar.hour
                        ? 'bg-slate-400 dark:bg-slate-400'
                        : 'bg-slate-300 dark:bg-slate-600'
                  "
                  :style="{ height: `${bar.heightPct}%` }"
                  @mouseover="hoveredBar = bar"
                  @mouseout="hoveredBar = null"
                >
                  <!-- Tooltip -->
                  <div
                    v-if="hoveredBar?.hour === bar.hour"
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-xs font-medium text-white shadow-lg dark:bg-slate-700"
                  >
                    {{ bar.hour }}:00 — {{ bar.count }} checks
                  </div>
                </div>
              </div>

              <!-- X-axis labels -->
              <div class="mt-1 flex px-1">
                <div
                  v-for="bar in sparklineBars"
                  :key="`l${bar.hour}`"
                  class="flex-1 text-center text-xs text-slate-400 dark:text-slate-500"
                >
                  {{ bar.label }}
                </div>
              </div>
            </div>

            <!-- Stats cards row -->
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div
                class="rounded-xl border border-white/50 bg-white/50 p-3 text-center dark:border-slate-700/50 dark:bg-slate-900/50"
              >
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Checks</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-800 dark:text-slate-100">
                  {{ (mcpStats?.total_checks ?? 0).toLocaleString() }}
                </p>
              </div>
              <div
                class="rounded-xl border border-white/50 bg-white/50 p-3 text-center dark:border-slate-700/50 dark:bg-slate-900/50"
              >
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Last Hour</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-800 dark:text-slate-100">
                  {{ mcpStats?.checks_last_hour ?? 0 }}
                </p>
              </div>
              <div
                class="rounded-xl border border-white/50 bg-white/50 p-3 text-center dark:border-slate-700/50 dark:bg-slate-900/50"
              >
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Last 24h</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-800 dark:text-slate-100">
                  {{ mcpStats?.checks_last_24h ?? 0 }}
                </p>
              </div>
              <div
                class="rounded-xl border border-white/50 bg-white/50 p-3 text-center dark:border-slate-700/50 dark:bg-slate-900/50"
              >
                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Last Check</p>
                <p class="mt-1 text-base font-bold text-gray-800 dark:text-slate-100">
                  {{ relativeTime(mcpStats?.last_check_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Row 2: Connection Info ── -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
          <!-- Recent Users -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
              Recent Users
            </h2>
            <div class="mt-3">
              <p
                v-if="!mcpStats || mcpStats.recent_users.length === 0"
                class="text-sm text-slate-400 dark:text-slate-500"
              >
                No data yet
              </p>
              <ul v-else class="space-y-1.5">
                <li
                  v-for="(uid, i) in mcpStats.recent_users"
                  :key="uid"
                  class="flex min-w-0 items-center justify-between gap-2 rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
                >
                  <span
                    class="min-w-0 break-all font-mono text-sm text-gray-700 dark:text-slate-300"
                    >{{ uid }}</span
                  >
                  <span class="text-xs text-slate-400 dark:text-slate-500">#{{ i + 1 }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Recent IPs -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
              Recent IPs
            </h2>
            <div class="mt-3">
              <p
                v-if="!mcpStats || mcpStats.recent_ips.length === 0"
                class="text-sm text-slate-400 dark:text-slate-500"
              >
                No data yet
              </p>
              <ul v-else class="space-y-1.5">
                <li
                  v-for="(ip, i) in mcpStats.recent_ips"
                  :key="ip"
                  class="flex min-w-0 items-center justify-between gap-2 rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
                >
                  <span
                    class="min-w-0 break-all font-mono text-sm text-gray-700 dark:text-slate-300"
                    >{{ ip }}</span
                  >
                  <span class="text-xs text-slate-400 dark:text-slate-500">#{{ i + 1 }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- ── Row 3: Status | Commands | Agents ── -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
          <!-- Status -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
              Status
            </h2>
            <p class="mt-3 text-sm text-gray-700 dark:text-slate-300">
              Boost package:
              <span class="font-semibold">{{ boost.installed ? 'Detected' : 'Missing' }}</span>
            </p>
            <ul class="mt-3 space-y-2 text-sm text-gray-600 dark:text-slate-300">
              <li
                class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
              >
                <span>Guidelines</span>
                <span class="font-semibold">{{ boost.guidelines ? 'On' : 'Off' }}</span>
              </li>
              <li
                class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
              >
                <span>MCP</span>
                <span class="font-semibold">{{ boost.mcp ? 'On' : 'Off' }}</span>
              </li>
              <li
                class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
              >
                <span>MCP Activity</span>
                <span
                  class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider"
                >
                  <span class="relative flex h-2.5 w-2.5">
                    <span
                      v-if="mcpChecking"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                    />
                    <span
                      class="relative inline-flex h-2.5 w-2.5 rounded-full transition-all duration-150"
                      :class="mcpBgColor"
                    />
                  </span>
                  {{ mcpIndicatorText }}
                </span>
              </li>
              <li
                class="flex items-center justify-between rounded-lg bg-white/40 px-3 py-2 dark:bg-slate-900/40"
              >
                <span>Sail</span>
                <span class="font-semibold">{{ boost.sail ? 'On' : 'Off' }}</span>
              </li>
            </ul>
          </div>

          <!-- Commands -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-cyan-100/40 p-5 shadow-xl shadow-cyan-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-cyan-900/20 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
              Recommended Commands
            </h2>
            <div class="mt-3 space-y-2">
              <code
                v-for="command in boost.commands"
                :key="command"
                class="block overflow-x-auto rounded-lg border border-white/40 bg-white/50 px-3 py-2 text-xs text-gray-800 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200"
              >
                {{ command }}
              </code>
            </div>
          </div>

          <!-- Agents -->
          <div
            class="rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-violet-100/40 p-5 shadow-xl shadow-violet-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-violet-900/20 dark:shadow-black/30"
          >
            <h2
              class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
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

            <h3
              class="mt-5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
            >
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

        <!-- ── Row 4: Enhanced Log Terminal ── -->
        <div
          class="mt-6 rounded-2xl border border-white/40 bg-gradient-to-br from-white/70 to-slate-100/40 p-5 shadow-xl shadow-slate-200/30 backdrop-blur-lg dark:border-slate-700/60 dark:from-slate-900/70 dark:to-slate-800/40 dark:shadow-black/30"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <h2
                class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-200"
              >
                Live MCP Browser Logs
              </h2>
              <span
                class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
              >
                {{ browserLogs.length }}
              </span>
            </div>
            <div class="flex items-center gap-4">
              <label
                class="flex cursor-pointer items-center gap-1.5 text-xs text-gray-600 dark:text-slate-400"
              >
                <input
                  v-model="autoScroll"
                  type="checkbox"
                  class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800"
                />
                Auto-scroll
              </label>
              <button
                type="button"
                :disabled="browserLogsClearing"
                class="inline-flex items-center rounded-md border border-white/30 bg-white/50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-800 transition hover:bg-white/60 disabled:opacity-60 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-200 dark:hover:bg-slate-900/60"
                @click="clearBrowserLogs"
              >
                Reset
              </button>
            </div>
          </div>

          <div
            ref="logContainer"
            class="mt-4 h-64 overflow-y-auto rounded-lg border border-slate-200/70 bg-slate-950/95 px-4 py-3 font-mono text-xs leading-5 dark:border-slate-700"
          >
            <p v-if="browserLogsLoading && browserLogs.length === 0" class="text-slate-500">
              Loading logs...
            </p>
            <p v-else-if="browserLogs.length === 0" class="text-slate-500">
              No events yet. MCP activity and state changes will appear here.
            </p>
            <div v-else>
              <p
                v-for="(line, i) in browserLogs"
                :key="i"
                class="whitespace-pre-wrap"
                :class="logLineClass(line)"
              >
                {{ line }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
