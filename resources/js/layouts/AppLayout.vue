<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

withDefaults(
    defineProps<{
        active?: string;
    }>(),
    {
        active: 'Dashboard',
    },
);

const page = usePage();
const user = computed(
    () => (page.props.auth as { user?: { name: string; email: string } })?.user,
);

const initials = computed(() => {
    if (!user.value?.name) {
        return '?';
    }

    return user.value.name
        .split(' ')
        .map((n: string) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});

const navItems = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Students', href: '/students' },
    { label: 'Sectioning', href: '/sectioning' },
    { label: 'School Year', href: '/school-year' },
    { label: 'ID Maker App', href: '/id-maker' },
];

const showLogoutModal = ref(false);

function confirmLogout(): void {
    router.post('/logout');
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            class="flex w-60 shrink-0 flex-col border-r border-gray-100 bg-white"
        >
            <div class="flex items-center gap-2 px-6 py-6">
                <svg width="26" height="26" viewBox="0 0 28 28" fill="none">
                    <path d="M14 1L4 14L14 27L24 14L14 1Z" fill="#3B82F6" />
                    <path d="M14 1L24 14L14 27" fill="#F59E0B" />
                </svg>
                <span class="text-lg font-bold text-gray-900">Invo.</span>
            </div>
            <nav class="flex-1 space-y-1 px-4">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition"
                    :class="
                        active === item.label
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-600/30'
                            : 'text-gray-500 hover:bg-gray-50'
                    "
                >
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        :class="
                            active === item.label
                                ? 'bg-white/20'
                                : 'bg-gray-100'
                        "
                    >
                        <svg
                            v-if="item.label === 'Dashboard'"
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"
                            />
                        </svg>
                        <svg
                            v-else-if="item.label === 'Students'"
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"
                            />
                        </svg>
                        <svg
                            v-else-if="item.label === 'Sectioning'"
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h17.25"
                            />
                        </svg>
                        <svg
                            v-else-if="item.label === 'School Year'"
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                            />
                        </svg>
                        <svg
                            v-else-if="item.label === 'ID Maker App'"
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="h-3.5 w-3.5"
                            :class="
                                active === item.label
                                    ? 'text-white'
                                    : 'text-gray-500'
                            "
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"
                            />
                        </svg>
                    </span>
                    {{ item.label }}
                </Link>
            </nav>

            <button
                @click="showLogoutModal = true"
                class="flex items-center gap-3 px-6 py-6 text-sm font-medium text-gray-400 transition hover:text-gray-600"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"
                    />
                </svg>
                Log Out
            </button>
        </aside>

        <!-- Main -->
        <main class="flex-1 overflow-y-auto p-8">
            <div class="mb-8 flex items-center justify-between">
                <div class="relative w-80">
                    <svg
                        class="absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                        />
                    </svg>
                    <input
                        type="text"
                        placeholder="Tap to search"
                        class="w-full rounded-xl border border-gray-100 bg-white py-2.5 pr-4 pl-11 text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                    />
                </div>
                <div class="flex items-center gap-5">
                    <button
                        class="relative flex h-9 w-9 items-center justify-center rounded-full border border-gray-100 bg-white"
                    >
                        <svg
                            class="h-4 w-4 text-gray-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                            />
                        </svg>
                    </button>

                    <div class="group relative">
                        <button
                            class="flex items-center gap-3 transition hover:opacity-80"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-xs font-bold text-white"
                            >
                                {{ initials }}
                            </div>
                            <div class="text-left">
                                <p
                                    class="text-sm leading-tight font-semibold text-gray-900"
                                >
                                    {{ user?.name ?? 'Guest' }}
                                </p>
                                <p class="text-xs leading-tight text-gray-400">
                                    {{ user?.email ?? '' }}
                                </p>
                            </div>
                        </button>
                        <div class="absolute top-full right-0 h-2 w-full"></div>
                        <div
                            class="invisible absolute top-full right-0 z-40 mt-2 w-48 translate-y-1 rounded-xl border border-gray-100 bg-white py-1.5 opacity-0 shadow-lg transition-all duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100"
                        >
                            <div class="border-b border-gray-50 px-3.5 py-2">
                                <p
                                    class="truncate text-sm font-semibold text-gray-900"
                                >
                                    {{ user?.name ?? 'Guest' }}
                                </p>
                                <p class="truncate text-xs text-gray-400">
                                    {{ user?.email ?? '' }}
                                </p>
                            </div>
                            <button
                                @click="showLogoutModal = true"
                                class="flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm text-red-500 transition hover:bg-red-50"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"
                                    />
                                </svg>
                                Log Out
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <slot />
        </main>

        <!-- Logout modal -->
        <div
            v-if="showLogoutModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div
                    class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-red-100"
                >
                    <svg
                        class="h-5 w-5 text-red-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"
                        />
                    </svg>
                </div>
                <h3 class="mb-1 text-base font-semibold text-gray-900">
                    Log out of your account?
                </h3>
                <p class="mb-6 text-sm text-gray-500">
                    You'll need to log in again to access your dashboard.
                </p>
                <div class="flex gap-3">
                    <button
                        @click="showLogoutModal = false"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmLogout"
                        class="flex-1 rounded-xl bg-red-500 py-2.5 text-sm font-semibold text-white transition hover:bg-red-600"
                    >
                        Log Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
