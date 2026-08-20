<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '../../layouts/AppLayout.vue'

interface Registration {
  id: number
  first_name: string
  middle_name?: string
  last_name: string
  school_year?: string
  section?: string
  address: string
  birthday?: string
  parent_name: string
  parent_contact_number: string
  photo_url?: string
}

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface RegistrationsPaginator {
  data: Registration[]
  links: PaginationLink[]
  from: number
  to: number
  total: number
  last_page: number
}

const props = defineProps<{
  registrations: RegistrationsPaginator
  filters?: { search?: string }
}>()

const search = ref(props.filters?.search ?? '')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

watch(search, (value: string) => {
  if (debounceTimer) { clearTimeout(debounceTimer) }
  debounceTimer = setTimeout(() => {
    router.get('/students', { search: value || undefined }, {
      preserveState: true,
      replace: true,
    })
  }, 350)
})

function initials(first?: string, last?: string): string {
  return `${first?.[0] ?? ''}${last?.[0] ?? ''}`.toUpperCase()
}

function formatDate(dateStr?: string): string {
  if (!dateStr) { return '' }
  return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <AppLayout active="Students">
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-lg font-semibold text-gray-900">All Registered Students</h1>
        <div class="relative w-72">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Search by name..."
            class="w-full bg-gray-50 rounded-xl border border-gray-100 pl-10 pr-4 py-2.5 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
          />
        </div>
      </div>
      <table class="w-full text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-400 border-b border-gray-100">
            <th class="font-medium pb-3">Student</th>
            <th class="font-medium pb-3">School Year</th>
            <th class="font-medium pb-3">Section</th>
            <th class="font-medium pb-3">Address</th>
            <th class="font-medium pb-3">Birthday</th>
            <th class="font-medium pb-3">Parent / Guardian</th>
            <th class="font-medium pb-3">Contact Number</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="r in registrations.data" :key="r.id">
            <td class="py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center shrink-0">
                  <img v-if="r.photo_url" :src="r.photo_url" alt="" class="w-full h-full object-cover" />
                  <span v-else class="text-[10px] font-semibold text-gray-400">{{ initials(r.first_name, r.last_name) }}</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ r.first_name }} {{ r.middle_name ? r.middle_name[0] + '. ' : '' }}{{ r.last_name }}</p>
                </div>
              </div>
            </td>
            <td class="py-3.5 text-gray-500">{{ r.school_year || '-' }}</td>
            <td class="py-3.5 text-gray-500">{{ r.section || '-' }}</td>
            <td class="py-3.5 text-gray-500">{{ r.address }}</td>
            <td class="py-3.5 text-gray-500">{{ formatDate(r.birthday) }}</td>
            <td class="py-3.5 text-gray-700">{{ r.parent_name }}</td>
            <td class="py-3.5 text-gray-500">{{ r.parent_contact_number }}</td>
          </tr>
          <tr v-if="registrations.data.length === 0">
            <td colspan="7" class="py-10 text-center text-gray-400 text-sm">
              No students found.
            </td>
          </tr>
        </tbody>
      </table>
      <!-- Pagination -->
      <div v-if="registrations.last_page > 1" class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
        <p class="text-xs text-gray-400">
          Showing {{ registrations.from }} to {{ registrations.to }} of {{ registrations.total }}
        </p>
        <div class="flex items-center gap-1.5">
          <template v-for="(link, i) in registrations.links" :key="i">
            <span
              v-if="!link.url"
              class="px-3 py-1.5 h-8 min-w-[2rem] flex items-center justify-center text-xs text-gray-300 rounded-xl select-none"
            >
              <span v-html="link.label"></span>
            </span>
            <Link
              v-else
              :href="link.url"
              preserve-scroll
              class="px-3 py-1.5 h-8 min-w-[2rem] flex items-center justify-center text-xs rounded-xl transition font-medium"
              :class="link.active ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
            >
              <span v-html="link.label"></span>
            </Link>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
