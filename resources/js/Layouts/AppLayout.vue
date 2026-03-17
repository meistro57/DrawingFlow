<script setup>
import { onBeforeUnmount, onMounted, ref, computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const userInitials = computed(() => {
  return (user.value?.name ?? 'User')
    .split(' ')
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
    .toUpperCase();
});
const showMobileMenu = ref(false);
const theme = ref('light');
const isDarkMode = computed(() => theme.value === 'dark');
const showNotifications = ref(false);
const notifications = ref([]);
const unreadCount = ref(user.value?.notification_unread_count ?? 0);
const isLoadingNotifications = ref(false);
let notificationsPoller = null;

function applyTheme(nextTheme) {
  const root = document.documentElement;

  root.classList.toggle('dark', nextTheme === 'dark');
  root.style.colorScheme = nextTheme;
}

function toggleTheme() {
  theme.value = isDarkMode.value ? 'light' : 'dark';
}

const navigation = computed(() => {
  const items = [
    { name: 'Dashboard', href: '/', prefix: '/' },
    { name: 'Requests', href: '/drawing-requests', prefix: '/drawing-requests' },
    { name: 'Submittals', href: '/submittals', prefix: '/submittals' },
    { name: 'Fab Queue', href: '/fab-queue', prefix: '/fab-queue' },
    { name: 'Projects', href: '/projects', prefix: '/projects' },
    { name: 'Customers', href: '/customers', prefix: '/customers' },
  ];

  if (user.value?.role === 'admin') {
    items.push({ name: 'Admin', href: '/admin/users', prefix: '/admin' });
  }

  return items;
});

function isActive(item) {
  if (item.prefix === '/') return page.props.ziggy?.location?.endsWith('/') || page.url === '/';
  return page.url?.startsWith(item.prefix);
}

async function fetchNotifications() {
  if (!user.value) {
    return;
  }

  isLoadingNotifications.value = true;

  try {
    const response = await window.axios.get(route('notifications.index'));
    notifications.value = response.data.data;
    unreadCount.value = response.data.unread_count;
  } catch {
    notifications.value = [];
  } finally {
    isLoadingNotifications.value = false;
  }
}

async function markNotificationAsRead(notification) {
  if (!notification?.id) {
    return;
  }

  if (notification.read_at === null) {
    await window.axios.post(route('notifications.read', notification.id));
    notification.read_at = new Date().toISOString();
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  }

  showNotifications.value = false;

  if (notification.data?.url) {
    router.visit(notification.data.url);
  }
}

async function markAllNotificationsAsRead() {
  await window.axios.post(route('notifications.read-all'));
  notifications.value = notifications.value.map((notification) => ({
    ...notification,
    read_at: notification.read_at ?? new Date().toISOString(),
  }));
  unreadCount.value = 0;
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value;

  if (showNotifications.value) {
    fetchNotifications();
  }
}

function logout() {
  router.post(route('logout'));
}

onMounted(() => {
  const savedTheme = localStorage.getItem('drawingflow-theme');

  if (savedTheme === 'dark' || savedTheme === 'light') {
    theme.value = savedTheme;
  } else {
    theme.value = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  applyTheme(theme.value);

  fetchNotifications();
  notificationsPoller = setInterval(() => {
    fetchNotifications();
  }, 15000);
});

onBeforeUnmount(() => {
  if (notificationsPoller !== null) {
    clearInterval(notificationsPoller);
  }
});

watch(theme, (nextTheme) => {
  localStorage.setItem('drawingflow-theme', nextTheme);
  applyTheme(nextTheme);
});
</script>

<template>
  <div class="min-h-screen bg-gray-200 dark:bg-slate-950">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 dark:bg-slate-900 dark:border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="shrink-0 flex items-center">
              <Link href="/" class="text-xl font-bold text-primary-600 dark:text-primary-400">
                DrawingFlow
              </Link>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden sm:ml-8 sm:flex sm:space-x-4">
              <Link
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors"
                :class="[
                  isActive(item)
                    ? 'text-primary-700 bg-primary-50 dark:text-primary-300 dark:bg-slate-800'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800',
                ]"
              >
                {{ item.name }}
              </Link>
            </div>
          </div>

          <!-- User Menu -->
          <div class="hidden sm:flex sm:items-center sm:space-x-4">
            <div class="relative">
              <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="toggleNotifications"
              >
                <span class="sr-only">Notifications</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"
                  />
                </svg>
                <span
                  v-if="unreadCount > 0"
                  class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white"
                >
                  {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
              </button>

              <div
                v-if="showNotifications"
                class="absolute right-0 z-50 mt-2 w-96 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
              >
                <div
                  class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-slate-700"
                >
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                    Notifications
                  </h3>
                  <button
                    type="button"
                    class="text-xs font-medium text-primary-600 hover:text-primary-800"
                    @click="markAllNotificationsAsRead"
                  >
                    Mark all read
                  </button>
                </div>
                <div
                  v-if="isLoadingNotifications"
                  class="px-4 py-6 text-center text-sm text-gray-500 dark:text-slate-400"
                >
                  Loading...
                </div>
                <div
                  v-else-if="notifications.length === 0"
                  class="px-4 py-6 text-center text-sm text-gray-500 dark:text-slate-400"
                >
                  No notifications yet.
                </div>
                <div
                  v-else
                  class="max-h-96 divide-y divide-gray-200 overflow-y-auto dark:divide-slate-700"
                >
                  <button
                    v-for="notification in notifications"
                    :key="notification.id"
                    type="button"
                    class="w-full px-4 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-slate-800"
                    @click="markNotificationAsRead(notification)"
                  >
                    <div class="flex items-start gap-2">
                      <span
                        class="mt-1 inline-flex h-2 w-2 rounded-full"
                        :class="
                          notification.read_at ? 'bg-gray-300 dark:bg-slate-600' : 'bg-primary-600'
                        "
                      />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100">
                          {{ notification.data?.title || 'Notification' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-600 dark:text-slate-300">
                          {{ notification.data?.message || '' }}
                        </p>
                      </div>
                    </div>
                  </button>
                </div>
              </div>
            </div>
            <button
              type="button"
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              @click="toggleTheme"
            >
              {{ isDarkMode ? 'Light' : 'Dark' }}
            </button>
            <Link
              :href="route('profile.edit')"
              class="inline-flex items-center gap-3 rounded-full border border-transparent px-2 py-1 transition hover:bg-gray-100 dark:hover:bg-slate-800"
            >
              <span
                class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100"
              >
                <img
                  v-if="user?.avatar_url"
                  :src="user.avatar_url"
                  :alt="`${user.name} avatar`"
                  class="h-full w-full object-cover"
                />
                <span v-else>{{ userInitials }}</span>
              </span>
              <span class="text-sm text-gray-600 dark:text-slate-300">{{ user?.name }}</span>
            </Link>
            <button
              @click="logout"
              class="text-sm text-gray-500 hover:text-gray-700 transition-colors dark:text-slate-400 dark:hover:text-slate-200"
            >
              Logout
            </button>
          </div>

          <!-- Mobile menu button -->
          <div class="sm:hidden flex items-center">
            <button
              @click="showMobileMenu = !showMobileMenu"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                  v-if="!showMobileMenu"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
                <path
                  v-else
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile menu -->
      <div v-show="showMobileMenu" class="sm:hidden border-t border-gray-200 dark:border-slate-800">
        <div class="pt-2 pb-3 space-y-1 px-4">
          <Link
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            class="block px-3 py-2 text-base font-medium rounded-md"
            :class="[
              isActive(item)
                ? 'text-primary-700 bg-primary-50 dark:text-primary-300 dark:bg-slate-800'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800',
            ]"
          >
            {{ item.name }}
          </Link>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200 px-4 dark:border-slate-800">
          <button
            type="button"
            class="mb-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="toggleTheme"
          >
            {{ isDarkMode ? 'Switch to Light' : 'Switch to Dark' }}
          </button>
          <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-xl px-1 py-2">
            <span
              class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-sm font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100"
            >
              <img
                v-if="user?.avatar_url"
                :src="user.avatar_url"
                :alt="`${user.name} avatar`"
                class="h-full w-full object-cover"
              />
              <span v-else>{{ userInitials }}</span>
            </span>
            <span>
              <div class="text-sm font-medium text-gray-800 dark:text-slate-200">
                {{ user?.name }}
              </div>
              <div class="text-sm text-gray-500 dark:text-slate-400">{{ user?.email }}</div>
            </span>
          </Link>
          <button
            @click="logout"
            class="mt-3 block w-full text-left px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800"
          >
            Logout
          </button>
        </div>
      </div>
    </nav>

    <!-- Flash Messages -->
    <div v-if="$page.props.flash?.success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
      <div
        class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300"
      >
        {{ $page.props.flash.success }}
      </div>
    </div>
    <div v-if="$page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
      <div
        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm dark:bg-red-950 dark:border-red-800 dark:text-red-300"
      >
        {{ $page.props.flash.error }}
      </div>
    </div>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>
