<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

const props = withDefaults(
    defineProps<{
        totalStudents?: number;
    }>(),
    {
        totalStudents: 0,
    },
);

const stats = computed(() => [
    {
        label: 'Registered Students',
        value: props.totalStudents.toLocaleString(),
        change: '35%',
        up: true,
        color: 'bg-red-100 text-red-500',
        prefix: '',
    },
    {
        label: 'Invoices',
        value: '2,221',
        change: '12%',
        up: true,
        color: 'bg-green-100 text-green-500',
        prefix: '',
    },
    {
        label: 'Clients',
        value: '1,423',
        change: '8%',
        up: true,
        color: 'bg-blue-100 text-blue-500',
        prefix: '',
    },
    {
        label: 'Loyalty',
        value: '78%',
        change: '1%',
        up: false,
        color: 'bg-pink-100 text-pink-500',
        prefix: '',
    },
]);

const chart = [
    { m: 'Mar', v: 32 },
    { m: 'Apr', v: 44 },
    { m: 'May', v: 26 },
    { m: 'Jun', v: 100, active: true },
    { m: 'Jul', v: 40 },
    { m: 'Aug', v: 52 },
    { m: 'Sep', v: 46 },
    { m: 'Oct', v: 58 },
    { m: 'Nov', v: 30 },
];

const activities = [
    {
        type: 'invoice',
        label: 'New invoice',
        name: 'Francisco Gibbs created invoice PQ-4491C',
        time: 'Just now',
    },
    {
        type: 'sent',
        label: '',
        name: 'Invoice JL-3432B reminder was sent to Chester Corp',
        time: 'Friday, 12:26PM',
    },
];

const invoices = [
    {
        no: 'PQ-4491C',
        date: '3 Jul, 2020',
        client: 'Daniel Padilla',
        amount: '$2,450',
        status: 'Paid',
    },
    {
        no: 'IN-9911J',
        date: '21 May, 2021',
        client: 'Christina Jacobs',
        amount: '$14,810',
        status: 'Overdue',
    },
    {
        no: 'UV-2319A',
        date: '14 Apr, 2020',
        client: 'Elizabeth Bailey',
        amount: '$450',
        status: 'Paid',
    },
];

const statusStyles: Record<string, string> = {
    Paid: 'bg-green-100 text-green-600',
    Overdue: 'bg-red-100 text-red-500',
};

const hovered = ref(chart.findIndex((c) => c.active));
</script>

<template>
    <AppLayout active="Dashboard">
        <!-- Stat cards -->
        <div
            class="mb-6 grid grid-cols-4 divide-x divide-gray-100 rounded-2xl border border-gray-100 bg-white"
        >
            <div v-for="s in stats" :key="s.label" class="px-6 py-5">
                <div class="mb-2 flex items-center gap-2">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full"
                        :class="s.color"
                    >
                        <span class="h-2 w-2 rounded-full bg-current"></span>
                    </span>
                    <span class="text-xs text-gray-400">{{ s.label }}</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-bold text-gray-900">
                        {{ s.prefix }}{{ s.value }}
                    </p>
                    <span
                        class="flex items-center gap-0.5 text-[11px] font-semibold"
                        :class="s.up ? 'text-green-500' : 'text-red-400'"
                    >
                        {{ s.change }}
                        <svg
                            class="h-2.5 w-2.5"
                            :class="{ 'rotate-180': !s.up }"
                            fill="currentColor"
                            viewBox="0 0 12 12"
                        >
                            <path d="M6 2l4 5H2l4-5z" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- Chart + promo -->
        <div class="mb-6 grid grid-cols-3 gap-6">
            <div
                class="col-span-2 rounded-2xl border border-gray-100 bg-white p-6"
            >
                <p class="mb-1 text-sm text-gray-400">Monthly Students</p>
                <p class="mb-6 text-2xl font-bold text-gray-900">216,000</p>
                <div class="flex h-40 items-end justify-between gap-3">
                    <div
                        v-for="(c, i) in chart"
                        :key="c.m"
                        class="relative flex flex-1 flex-col items-center gap-2"
                        @mouseenter="hovered = i"
                    >
                        <div
                            v-if="hovered === i"
                            class="absolute -top-9 rounded-lg bg-gray-900 px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white"
                        >
                            ${{ (c.v * 150).toLocaleString() }}
                        </div>
                        <div
                            class="w-full rounded-lg transition-all"
                            :class="
                                hovered === i ? 'bg-blue-600' : 'bg-gray-100'
                            "
                            :style="{ height: c.v + 'px' }"
                        ></div>
                        <span
                            class="text-xs"
                            :class="
                                hovered === i
                                    ? 'font-semibold text-blue-600'
                                    : 'text-gray-400'
                            "
                            >{{ c.m }}</span
                        >
                    </div>
                </div>
            </div>
            <div
                class="relative flex flex-col overflow-hidden rounded-2xl bg-blue-600 p-6 text-white"
            >
                <div
                    class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-blue-500/40"
                ></div>
                <span
                    class="mb-4 w-fit rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold"
                    >NEW</span
                >
                <h3 class="relative mb-2 text-lg leading-snug font-bold">
                    We have added new invoicing templates!
                </h3>
                <p class="relative mb-6 text-sm text-blue-100">
                    New templates focused on helping you improve your business
                </p>
                <button
                    class="relative mt-auto rounded-xl bg-white py-2.5 text-sm font-semibold text-blue-600 transition hover:bg-blue-50"
                >
                    Download Now
                </button>
            </div>
        </div>

        <!-- Activities + Recent Invoices -->
        <div class="grid grid-cols-3 gap-6">
            <div class="rounded-2xl border border-gray-100 bg-white p-6">
                <h3 class="mb-4 text-sm font-semibold text-gray-900">
                    Activities
                </h3>
                <div class="space-y-5">
                    <div
                        v-for="(a, i) in activities"
                        :key="i"
                        class="flex gap-3"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                            :class="
                                a.type === 'invoice'
                                    ? 'bg-green-100'
                                    : 'bg-yellow-100'
                            "
                        >
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="
                                    a.type === 'invoice'
                                        ? 'bg-green-500'
                                        : 'bg-yellow-500'
                                "
                            ></span>
                        </div>
                        <div>
                            <p
                                v-if="a.label"
                                class="mb-0.5 text-xs font-semibold text-green-500"
                            >
                                {{ a.label }}
                            </p>
                            <p class="text-sm leading-snug text-gray-700">
                                {{ a.name }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ a.time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div
                class="col-span-2 rounded-2xl border border-gray-100 bg-white p-6"
            >
                <h3 class="mb-4 text-sm font-semibold text-gray-900">
                    Recent Invoices
                </h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400">
                            <th class="pb-3 font-medium">No</th>
                            <th class="pb-3 font-medium">Date Created</th>
                            <th class="pb-3 font-medium">Client</th>
                            <th class="pb-3 font-medium">Amount</th>
                            <th class="pb-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="inv in invoices" :key="inv.no">
                            <td class="py-3 font-medium text-gray-900">
                                {{ inv.no }}
                            </td>
                            <td class="py-3 text-gray-500">{{ inv.date }}</td>
                            <td class="py-3 text-gray-700">{{ inv.client }}</td>
                            <td class="py-3 font-medium text-gray-900">
                                {{ inv.amount }}
                            </td>
                            <td class="py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="statusStyles[inv.status]"
                                    >{{ inv.status }}</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
