<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDisplayDate } from '@/utils/dateFormatting';

const props = defineProps({
  stats: Object,
  my_workload: Object,
  my_queue_filter: String,
  my_queue_filter_counts: Object,
  my_queue: Array,
  my_submittal_work: Array,
  my_fab_assignments: Array,
  recent_requests: Array,
  recent_submittals: Array,
});

const statCards = computed(() => [
  {
    name: 'Active Projects',
    value: props.stats.active_projects,
    color: 'bg-blue-500',
    href: '/projects',
  },
  {
    name: 'Pending Requests',
    value: props.stats.pending_requests,
    color: 'bg-yellow-500',
    href: '/drawing-requests',
  },
  {
    name: 'In Progress',
    value: props.stats.in_progress_requests,
    color: 'bg-indigo-500',
    href: '/drawing-requests',
  },
  {
    name: 'Awaiting Approval',
    value: props.stats.awaiting_approval,
    color: 'bg-orange-500',
    href: '/submittals',
  },
  {
    name: 'Fab Queue',
    value: props.stats.fab_queue_count,
    color: 'bg-green-500',
    href: '/fab-queue',
  },
]);

const workloadCards = computed(() => [
  {
    name: 'Assigned Requests',
    value: props.my_workload.assigned_requests,
    tone: 'from-slate-900 to-slate-700 dark:from-slate-200 dark:to-slate-400',
    text: 'text-white dark:text-slate-950',
    hint: `${props.my_workload.due_this_week} due this week`,
    href: route('drawing-requests.index'),
  },
  {
    name: 'Submittals Needing Action',
    value: props.my_workload.submittals_needing_action,
    tone: 'from-amber-500 to-orange-500 dark:from-amber-400 dark:to-orange-400',
    text: 'text-white dark:text-slate-950',
    hint: 'Drafts and revisions waiting on you',
    href: route('submittals.index'),
  },
  {
    name: 'Fab Assignments',
    value: props.my_workload.fab_assignments,
    tone: 'from-emerald-500 to-teal-500 dark:from-emerald-400 dark:to-teal-400',
    text: 'text-white dark:text-slate-950',
    hint: 'Active work in fabrication',
    href: route('fab-queue.index'),
  },
]);

const queueFilters = computed(() => [
  {
    name: 'All',
    value: 'all',
    count: props.my_queue_filter_counts.all,
  },
  {
    name: 'Overdue',
    value: 'overdue',
    count: props.my_queue_filter_counts.overdue,
  },
  {
    name: 'Due Soon',
    value: 'due_soon',
    count: props.my_queue_filter_counts.due_soon,
  },
  {
    name: 'High Priority',
    value: 'high_priority',
    count: props.my_queue_filter_counts.high_priority,
  },
]);

function formatDate(value) {
  return formatDisplayDate(value, 'No due date');
}

function dueState(value) {
  if (!value) {
    return {
      label: 'No due date',
      classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };
  }

  const dueDate = new Date(value);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  dueDate.setHours(0, 0, 0, 0);

  const diffDays = Math.round((dueDate.getTime() - today.getTime()) / 86400000);

  if (diffDays < 0) {
    return {
      label: `Overdue ${Math.abs(diffDays)}d`,
      classes: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    };
  }

  if (diffDays === 0) {
    return {
      label: 'Due today',
      classes: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    };
  }

  if (diffDays <= 7) {
    return {
      label: `Due in ${diffDays}d`,
      classes: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    };
  }

  return {
    label: formatDate(value),
    classes: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  };
}

function requestCardClasses(request) {
  const isOverdue = dueState(request.required_date).label.startsWith('Overdue');

  if (isOverdue || request.priority === 'urgent') {
    return 'border-l-4 border-l-red-500 bg-red-50/70 hover:bg-red-50 dark:border-l-red-400 dark:bg-red-950/20 dark:hover:bg-red-950/30';
  }

  if (request.priority === 'high') {
    return 'border-l-4 border-l-amber-500 bg-amber-50/70 hover:bg-amber-50 dark:border-l-amber-400 dark:bg-amber-950/20 dark:hover:bg-amber-950/30';
  }

  if (request.priority === 'normal') {
    return 'border-l-4 border-l-blue-400 bg-blue-50/50 hover:bg-blue-50 dark:border-l-blue-400 dark:bg-blue-950/15 dark:hover:bg-blue-950/25';
  }

  return 'border-l-4 border-l-slate-300 bg-white hover:bg-gray-50 dark:border-l-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800/60';
}

function priorityBadgeClasses(priority) {
  if (priority === 'urgent') {
    return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
  }

  if (priority === 'high') {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
  }

  if (priority === 'normal') {
    return 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300';
  }

  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
}
</script>

<template>
  <AppLayout>
    <Head title="Dashboard" />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
              Your work queue, project pressure points, and recent activity.
            </p>
          </div>
          <div class="flex items-center gap-3">
            <Link
              :href="route('projects.create')"
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            >
              New Project
            </Link>
            <Link
              :href="route('drawing-requests.create')"
              class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-primary-700"
            >
              New Request
            </Link>
          </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <Link
            v-for="stat in statCards"
            :key="stat.name"
            :href="stat.href"
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
          >
            <div class="p-5">
              <div class="flex items-center">
                <div class="shrink-0">
                  <div :class="[stat.color, 'w-3 h-3 rounded-full']"></div>
                </div>
                <div class="ml-3 w-0 flex-1">
                  <p class="truncate text-sm font-medium text-gray-500 dark:text-slate-400">
                    {{ stat.name }}
                  </p>
                  <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100">
                    {{ stat.value }}
                  </p>
                </div>
              </div>
            </div>
          </Link>
        </div>

        <section class="mb-8">
          <div class="mb-4 flex items-end justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">My Queue</h2>
              <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                The work currently assigned to you across requests, submittals, and fabrication.
              </p>
            </div>
            <Link
              :href="route('drawing-requests.index')"
              class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
            >
              Open request board
            </Link>
          </div>

          <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <Link
              v-for="card in workloadCards"
              :key="card.name"
              :href="card.href"
              class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500/60 dark:border-slate-800 dark:bg-slate-900"
            >
              <div :class="[card.tone, card.text, 'bg-gradient-to-br p-5']">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] opacity-80">
                  {{ card.name }}
                </p>
                <p class="mt-4 text-4xl font-bold">{{ card.value }}</p>
                <p class="mt-2 text-sm opacity-80">{{ card.hint }}</p>
                <p class="mt-3 text-xs font-semibold uppercase tracking-wider opacity-90 group-hover:underline">
                  Open
                </p>
              </div>
            </Link>
          </div>
        </section>

        <div class="mb-8 grid grid-cols-1 gap-8 xl:grid-cols-3">
          <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
          >
            <div
              class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800"
            >
              <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
                  Assigned Requests
                </h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                  Prioritized by urgency and due date
                </p>
              </div>
              <Link
                :href="route('drawing-requests.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                >View All</Link
              >
            </div>
            <div
              class="flex flex-wrap gap-2 border-b border-gray-200 px-6 py-3 dark:border-slate-800"
            >
              <Link
                v-for="filter in queueFilters"
                :key="filter.value"
                :href="
                  route('dashboard', filter.value === 'all' ? {} : { queue_filter: filter.value })
                "
                preserve-scroll
                class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition"
                :class="[
                  my_queue_filter === filter.value
                    ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-slate-800 dark:text-primary-300'
                    : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-white',
                ]"
              >
                <span>{{ filter.name }}</span>
                <span class="rounded-full bg-black/6 px-2 py-0.5 text-[10px] dark:bg-white/10">{{
                  filter.count
                }}</span>
              </Link>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
              <Link
                v-for="request in my_queue"
                :key="request.id"
                :href="route('drawing-requests.show', request.id)"
                :class="[requestCardClasses(request), 'block px-6 py-4 transition-colors']"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                      {{ request.title }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                      {{ request.request_number }}
                      <span v-if="request.project"> · {{ request.project.name }}</span>
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                      <StatusBadge :status="request.status" />
                      <span
                        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                        :class="dueState(request.required_date).classes"
                      >
                        {{ dueState(request.required_date).label }}
                      </span>
                    </div>
                  </div>
                  <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                    :class="priorityBadgeClasses(request.priority)"
                  >
                    {{ request.priority }}
                  </span>
                </div>
              </Link>
              <div
                v-if="!my_queue?.length"
                class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400"
              >
                <span v-if="my_queue_filter === 'all'"
                  >No requests are assigned to you right now.</span
                >
                <span v-else>No requests match the current queue filter.</span>
              </div>
            </div>
          </section>

          <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
          >
            <div
              class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800"
            >
              <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
                  Submittals Needing Action
                </h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                  Drafts, revisions, and follow-up items
                </p>
              </div>
              <Link
                :href="route('submittals.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                >View All</Link
              >
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
              <Link
                v-for="submittal in my_submittal_work"
                :key="submittal.id"
                :href="route('submittals.show', submittal.id)"
                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
              >
                <div class="flex items-center justify-between">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                      {{ submittal.submittal_number }}
                      <span class="font-normal text-gray-500 dark:text-slate-400"
                        >Rev {{ submittal.revision }}</span
                      >
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                      <span v-if="submittal.project">{{ submittal.project.name }}</span>
                      <span v-if="submittal.drawing_request">
                        · {{ submittal.drawing_request.title }}</span
                      >
                    </p>
                  </div>
                  <StatusBadge :status="submittal.status" class="ml-3" />
                </div>
              </Link>
              <div
                v-if="!my_submittal_work?.length"
                class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400"
              >
                No submittals need action from you right now.
              </div>
            </div>
          </section>

          <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
          >
            <div
              class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800"
            >
              <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
                  Fab Assignments
                </h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                  What is currently on your fabrication plate
                </p>
              </div>
              <Link
                :href="route('fab-queue.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                >View All</Link
              >
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
              <Link
                v-for="entry in my_fab_assignments"
                :key="entry.id"
                :href="route('fab-queue.show', entry.id)"
                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
              >
                <div class="flex items-center justify-between gap-3">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                      {{ entry.queue_number }}
                      <span
                        v-if="entry.project"
                        class="font-normal text-gray-500 dark:text-slate-400"
                        >· {{ entry.project.name }}</span
                      >
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                      <span v-if="entry.submittal"
                        >{{ entry.submittal.submittal_number }} Rev
                        {{ entry.submittal.revision }}</span
                      >
                    </p>
                  </div>
                  <div class="text-right">
                    <StatusBadge :status="entry.status" />
                    <p
                      class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400"
                    >
                      Priority {{ entry.priority }}
                    </p>
                  </div>
                </div>
              </Link>
              <div
                v-if="!my_fab_assignments?.length"
                class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400"
              >
                No fabrication assignments are currently assigned to you.
              </div>
            </div>
          </section>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
          <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
          >
            <div
              class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800"
            >
              <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
                Recent Drawing Requests
              </h2>
              <Link
                :href="route('drawing-requests.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                >View All</Link
              >
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
              <Link
                v-for="request in recent_requests"
                :key="request.id"
                :href="route('drawing-requests.show', request.id)"
                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
              >
                <div class="flex items-center justify-between">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                      {{ request.title }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                      {{ request.request_number }}
                      <span v-if="request.project"> · {{ request.project.name }}</span>
                    </p>
                  </div>
                  <StatusBadge :status="request.status" class="ml-3" />
                </div>
              </Link>
            </div>
          </section>

          <section
            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
          >
            <div
              class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-800"
            >
              <h2 class="text-lg font-medium text-gray-900 dark:text-slate-100">
                Recent Submittals
              </h2>
              <Link
                :href="route('submittals.index')"
                class="text-sm font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300"
                >View All</Link
              >
            </div>
            <div class="divide-y divide-gray-200 dark:divide-slate-800">
              <Link
                v-for="submittal in recent_submittals"
                :key="submittal.id"
                :href="route('submittals.show', submittal.id)"
                class="block px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60"
              >
                <div class="flex items-center justify-between">
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-slate-100">
                      {{ submittal.submittal_number }}
                      <span class="font-normal text-gray-500 dark:text-slate-400"
                        >Rev {{ submittal.revision }}</span
                      >
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                      <span v-if="submittal.project">{{ submittal.project.name }}</span>
                      <span v-if="submittal.customer"> · {{ submittal.customer.name }}</span>
                    </p>
                  </div>
                  <StatusBadge :status="submittal.status" class="ml-3" />
                </div>
              </Link>
            </div>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
