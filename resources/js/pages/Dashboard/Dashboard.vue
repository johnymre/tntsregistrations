<script setup>
import { computed, ref } from 'vue'
import AppLayout from '../../layouts/AppLayout.vue'

const props = defineProps({
  totalStudents: {
    type: Number,
    default: 0
  }
})

const stats = computed(() => [
  { 
    label: 'Registered Students', 
    value: props.totalStudents.toLocaleString(), 
    change: '35%', 
    up: true, 
    color: 'bg-red-100 text-red-500' 
  },
  { label: 'Invoices', value: '2,221', change: '12%', up: true, color: 'bg-green-100 text-green-500' },
  { label: 'Clients', value: '1,423', change: '8%', up: true, color: 'bg-blue-100 text-blue-500' },
  { label: 'Loyalty', value: '78%', change: '1%', up: false, color: 'bg-pink-100 text-pink-500' },
])

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
]

const activities = [
  { type: 'invoice', label: 'New invoice', name: 'Francisco Gibbs created invoice PQ-4491C', time: 'Just now' },
  { type: 'sent', label: '', name: 'Invoice JL-3432B reminder was sent to Chester Corp', time: 'Friday, 12:26PM' },
]

const invoices = [
  { no: 'PQ-4491C', date: '3 Jul, 2020', client: 'Daniel Padilla', amount: '$2,450', status: 'Paid' },
  { no: 'IN-9911J', date: '21 May, 2021', client: 'Christina Jacobs', amount: '$14,810', status: 'Overdue' },
  { no: 'UV-2319A', date: '14 Apr, 2020', client: 'Elizabeth Bailey', amount: '$450', status: 'Paid' },
]

const statusStyles = {
  Paid: 'bg-green-100 text-green-600',
  Overdue: 'bg-red-100 text-red-500',
}

const hovered = ref(chart.findIndex((c) => c.active))
</script>

<template>
  <AppLayout active="Dashboard">
    <!-- Stat cards -->
    <div class="bg-white rounded-2xl border border-gray-100 grid grid-cols-4 divide-x divide-gray-100 mb-6">
      <div v-for="s in stats" :key="s.label" class="px-6 py-5">
        <div class="flex items-center gap-2 mb-2">
          <span class="w-6 h-6 rounded-full flex items-center justify-center" :class="s.color">
            <span class="w-2 h-2 rounded-full bg-current"></span>
          </span>
          <span class="text-xs text-gray-400">{{ s.label }}</span>
        </div>
        <div class="flex items-baseline gap-2">
          <p class="text-2xl font-bold text-gray-900">{{ s.prefix }}{{ s.value }}</p>
          <span class="text-[11px] font-semibold flex items-center gap-0.5" :class="s.up ? 'text-green-500' : 'text-red-400'">
            {{ s.change }}
            <svg class="w-2.5 h-2.5" :class="{ 'rotate-180': !s.up }" fill="currentColor" viewBox="0 0 12 12"><path d="M6 2l4 5H2l4-5z"/></svg>
          </span>
        </div>
      </div>
    </div>

    <!-- Chart + promo -->
    <div class="grid grid-cols-3 gap-6 mb-6">
      <div class="col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <p class="text-sm text-gray-400 mb-1">Monthly Students</p>
        <p class="text-2xl font-bold text-gray-900 mb-6">216,000</p>

        <div class="flex items-end justify-between gap-3 h-40">
          <div
            v-for="(c, i) in chart"
            :key="c.m"
            class="flex-1 flex flex-col items-center gap-2 relative"
            @mouseenter="hovered = i"
          >
            <div
              v-if="hovered === i"
              class="absolute -top-9 bg-gray-900 text-white text-xs font-semibold rounded-lg px-2.5 py-1 whitespace-nowrap"
            >
              ${{ (c.v * 150).toLocaleString() }}
            </div>
            <div class="w-full rounded-lg transition-all" :class="hovered === i ? 'bg-blue-600' : 'bg-gray-100'" :style="{ height: c.v + 'px' }"></div>
            <span class="text-xs" :class="hovered === i ? 'text-blue-600 font-semibold' : 'text-gray-400'">{{ c.m }}</span>
          </div>
        </div>
      </div>

      <div class="bg-blue-600 rounded-2xl p-6 flex flex-col text-white relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-blue-500/40"></div>
        <span class="text-[10px] font-bold bg-white/20 rounded-full px-2 py-0.5 w-fit mb-4">NEW</span>
        <h3 class="text-lg font-bold leading-snug mb-2 relative">We have added new invoicing templates!</h3>
        <p class="text-sm text-blue-100 mb-6 relative">New templates focused on helping you improve your business</p>
        <button class="mt-auto bg-white text-blue-600 text-sm font-semibold rounded-xl py-2.5 relative hover:bg-blue-50 transition">
          Download Now
        </button>
      </div>
    </div>

    <!-- Activities + Recent Invoices -->
    <div class="grid grid-cols-3 gap-6">
      <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Activities</h3>
        <div class="space-y-5">
          <div v-for="(a, i) in activities" :key="i" class="flex gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="a.type === 'invoice' ? 'bg-green-100' : 'bg-yellow-100'">
              <span class="w-2 h-2 rounded-full" :class="a.type === 'invoice' ? 'bg-green-500' : 'bg-yellow-500'"></span>
            </div>
            <div>
              <p v-if="a.label" class="text-xs font-semibold text-green-500 mb-0.5">{{ a.label }}</p>
              <p class="text-sm text-gray-700 leading-snug">{{ a.name }}</p>
              <p class="text-xs text-gray-400 mt-0.5">{{ a.time }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-2 bg-white rounded-2xl border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Recent Invoices</h3>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-400">
              <th class="font-medium pb-3">No</th>
              <th class="font-medium pb-3">Date Created</th>
              <th class="font-medium pb-3">Client</th>
              <th class="font-medium pb-3">Amount</th>
              <th class="font-medium pb-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="inv in invoices" :key="inv.no">
              <td class="py-3 font-medium text-gray-900">{{ inv.no }}</td>
              <td class="py-3 text-gray-500">{{ inv.date }}</td>
              <td class="py-3 text-gray-700">{{ inv.client }}</td>
              <td class="py-3 text-gray-900 font-medium">{{ inv.amount }}</td>
              <td class="py-3">
                <span class="text-xs font-semibold rounded-full px-2.5 py-1" :class="statusStyles[inv.status]">{{ inv.status }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>