<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
const showMobileMenu = ref(false);
const theme = ref('light');
const isDarkMode = computed(() => theme.value === 'dark');

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
});

watch(theme, (nextTheme) => {
    localStorage.setItem('drawingflow-theme', nextTheme);
    applyTheme(nextTheme);
});
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-slate-950">
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
                                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800'
                                ]"
                            >
                                {{ item.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:flex sm:items-center sm:space-x-4">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-600 hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                            @click="toggleTheme"
                        >
                            {{ isDarkMode ? 'Light' : 'Dark' }}
                        </button>
                        <span class="text-sm text-gray-600 dark:text-slate-300">{{ user?.name }}</span>
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
                                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800'
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
                    <div class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ user?.name }}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-400">{{ user?.email }}</div>
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
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300">
                {{ $page.props.flash.success }}
            </div>
        </div>
        <div v-if="$page.props.flash?.error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md text-sm dark:bg-red-950 dark:border-red-800 dark:text-red-300">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
