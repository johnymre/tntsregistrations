<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'

const props = defineProps({
  sections: { type: Array, default: () => [] },
  students: { type: Array, default: () => [] },
})

const gradeFilter = ref('All')
const grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12']
const gradeOptions = ['7', '8', '9', '10', '11', '12']

// Create Section Modal state
const showModal = ref(false)
const form = useForm({
  name: '',
  grade_level: 'Grade 7',
  adviser_name: '',
})

function openCreateModal() {
  form.reset()
  form.clearErrors()
  showModal.value = true
}

function submitSection() {
  form.post('/sectioning', {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      form.reset()
    },
  })
}

// Manage Students Modal state
const showManageModal = ref(false)
const activeSection = ref(null)
const unassignedList = ref([])
const assignedList = ref([])
const searchUnassigned = ref('')
const searchAssigned = ref('')

const studentForm = useForm({
  student_ids: [],
})

function openManageStudents(section) {
  activeSection.value = section
  searchUnassigned.value = ''
  searchAssigned.value = ''
  
  // Table 1: Unassigned students (no section)
  unassignedList.value = props.students.filter((s) => !s.section)
  
  // Table 2: Selected students in this section
  assignedList.value = props.students.filter((s) => s.section === section.name)
  
  showManageModal.value = true
}

function addStudent(student) {
  unassignedList.value = unassignedList.value.filter((s) => s.id !== student.id)
  assignedList.value.push(student)
}

function removeStudent(student) {
  assignedList.value = assignedList.value.filter((s) => s.id !== student.id)
  unassignedList.value.push(student)
}

function saveStudentAssignments() {
  if (!activeSection.value) return
  
  studentForm.student_ids = assignedList.value.map((s) => s.id)
  studentForm.post(`/sectioning/${activeSection.value.id}/students`, {
    preserveScroll: true,
    onSuccess: () => {
      showManageModal.value = false
    },
  })
}

const filteredUnassigned = computed(() => {
  const query = searchUnassigned.value.toLowerCase().trim()
  if (!query) return unassignedList.value
  return unassignedList.value.filter((s) => 
    `${s.first_name} ${s.last_name}`.toLowerCase().includes(query)
  )
})

const filteredAssigned = computed(() => {
  const query = searchAssigned.value.toLowerCase().trim()
  if (!query) return assignedList.value
  return assignedList.value.filter((s) => 
    `${s.first_name} ${s.last_name}`.toLowerCase().includes(query)
  )
})

const filteredSections = computed(() => {
  return props.sections.filter((s) => {
    return gradeFilter.value === 'All' || s.grade_level === gradeFilter.value
  })
})

function isFull(s) {
  return s.enrolled_count >= s.capacity
}
</script>

<template>
  <AppLayout active="Sectioning">
    <!-- Header -->
    <div class="flex items-start justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Section Management</h1>
        <p class="text-sm text-gray-500 mt-1">Manage class sections, advisers, and student capacities for Tanza National Trade School.</p>
      </div>
      <button
        @click="openCreateModal"
        class="flex items-center gap-2 bg-blue-600 text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-blue-700 transition shrink-0"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Create New Section
      </button>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-2 bg-white rounded-2xl border border-gray-100 p-2 mb-6">
      <button
        @click="gradeFilter = 'All'"
        class="px-4 py-2 rounded-xl text-sm font-semibold transition"
        :class="gradeFilter === 'All' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:bg-gray-50'"
      >
        All Sections
      </button>
      <div class="w-px h-6 bg-gray-100 mx-1"></div>
      <select v-model="gradeFilter" class="px-3.5 py-2 rounded-xl text-sm font-medium text-gray-600 bg-gray-50 border-none focus:outline-none focus:ring-2 focus:ring-blue-500/30">
        <option value="All">All Grades</option>
        <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
      </select>
    </div>

    <!-- Section Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <div v-for="s in filteredSections" :key="s.id" class="bg-white rounded-2xl border border-gray-100 overflow-hidden flex flex-col">
        <!-- Banner -->
        <div class="relative h-20 bg-gradient-to-br from-blue-50 to-blue-100 px-5 pt-4 overflow-hidden">
          <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-blue-200/40"></div>
          <span class="relative inline-block bg-gray-900 text-white text-[10px] font-bold tracking-wide rounded-md px-2 py-1 mb-1.5">
            {{ s.grade_level }}
          </span>
          <h3 class="relative text-lg font-bold text-gray-900">{{ s.name }}</h3>
        </div>
        <!-- Body -->
        <div class="p-5 flex-1 flex flex-col">
          <div class="space-y-3 mb-4">
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
              <div>
                <p class="text-[10px] uppercase tracking-wide text-gray-400 leading-tight">Adviser</p>
                <p class="text-sm font-medium text-gray-900 leading-tight">{{ s.adviser_name || '-' }}</p>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span :class="isFull(s) ? 'text-blue-600 font-semibold' : 'text-gray-400'">
                {{ isFull(s) ? 'Status: Full' : 'Enrollment Capacity' }}
              </span>
              <span class="font-semibold text-gray-900">{{ s.enrolled_count }}/{{ s.capacity }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="isFull(s) ? 'bg-gray-900' : 'bg-blue-500'"
                :style="{ width: Math.min(100, (s.enrolled_count / s.capacity) * 100) + '%' }"
              ></div>
            </div>
          </div>
          <div class="flex items-center gap-2 mt-auto">
            <button class="flex-1 py-2 rounded-xl text-sm font-semibold text-blue-600 border border-blue-200 hover:bg-blue-50 transition">
              Edit Adviser
            </button>
            <button
              @click="openManageStudents(s)"
              class="flex-1 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition"
            >
              Manage Students
            </button>
          </div>
        </div>
      </div>

      <!-- Add Another Section Card -->
      <button
        type="button"
        @click="openCreateModal"
        class="border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center py-10 hover:border-blue-300 hover:bg-blue-50/30 transition min-h-[280px]"
      >
        <div class="w-11 h-11 rounded-full bg-gray-100 flex items-center justify-center mb-3">
          <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
        </div>
        <p class="text-sm font-semibold text-gray-900">Add Another Section</p>
        <p class="text-xs text-gray-400 mt-0.5">Create a new class for the semester</p>
      </button>
    </div>

    <!-- Create Section Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-base font-semibold text-gray-900">Create New Section</h3>
          <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form @submit.prevent="submitSection" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Section Name</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g. Diamond, Einstein"
              required
              class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Grade Level</label>
            <select
              v-model="form.grade_level"
              required
              class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
            >
              <option v-for="g in gradeOptions" :key="g" :value="'Grade ' + g">
                {{ g }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1.5">Adviser Name</label>
            <input
              v-model="form.adviser_name"
              type="text"
              placeholder="e.g. Maria Santos"
              required
              class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
            />
          </div>

          <div class="flex gap-3 pt-3">
            <button
              type="button"
              @click="showModal = false"
              class="flex-1 py-2.5 rounded-xl text-sm font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 transition"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 transition"
            >
              {{ form.processing ? 'Creating...' : 'Create Section' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Manage Students Modal -->
    <div v-if="showManageModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl p-6 flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Manage Students — {{ activeSection?.name }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ activeSection?.grade_level }} | Capacity: {{ assignedList.length }}/{{ activeSection?.capacity }}</p>
          </div>
          <button @click="showManageModal = false" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Tables Container -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 overflow-y-auto flex-1 pr-1 mb-6">
          <!-- Table 1: Unassigned Students -->
          <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Unassigned Students</h4>
              <span class="text-xs font-bold text-gray-400">{{ unassignedList.length }}</span>
            </div>
            
            <input
              v-model="searchUnassigned"
              type="text"
              placeholder="Search unassigned..."
              class="w-full bg-white rounded-xl border border-gray-200 px-3 py-1.5 text-xs mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            />

            <div class="overflow-y-auto flex-1 max-h-[320px] bg-white rounded-xl border border-gray-100">
              <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 sticky top-0 border-b border-gray-100">
                  <tr class="text-gray-400">
                    <th class="py-2 px-3 font-medium">Student</th>
                    <th class="py-2 px-3 font-medium text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="s in filteredUnassigned" :key="s.id" class="hover:bg-gray-50/80 transition">
                    <td class="py-2 px-3 text-gray-800 font-medium">
                      {{ s.first_name }} {{ s.last_name }}
                    </td>
                    <td class="py-2 px-3 text-right">
                      <button
                        type="button"
                        @click="addStudent(s)"
                        class="px-3 py-1 rounded-lg text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition"
                      >
                        Add
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredUnassigned.length === 0">
                    <td colspan="2" class="py-6 text-center text-gray-400">No unassigned students</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Table 2: Assigned Students -->
          <div class="bg-blue-50/40 rounded-2xl p-4 border border-blue-100/60 flex flex-col">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-blue-700">Students in {{ activeSection?.name }}</h4>
              <span class="text-xs font-bold text-blue-600">{{ assignedList.length }}</span>
            </div>

            <input
              v-model="searchAssigned"
              type="text"
              placeholder="Search section students..."
              class="w-full bg-white rounded-xl border border-gray-200 px-3 py-1.5 text-xs mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
            />

            <div class="overflow-y-auto flex-1 max-h-[320px] bg-white rounded-xl border border-gray-100">
              <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 sticky top-0 border-b border-gray-100">
                  <tr class="text-gray-400">
                    <th class="py-2 px-3 font-medium">Student</th>
                    <th class="py-2 px-3 font-medium text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr v-for="s in filteredAssigned" :key="s.id" class="hover:bg-gray-50/80 transition">
                    <td class="py-2 px-3 text-gray-800 font-medium">
                      {{ s.first_name }} {{ s.last_name }}
                    </td>
                    <td class="py-2 px-3 text-right">
                      <button
                        type="button"
                        @click="removeStudent(s)"
                        class="px-3 py-1 rounded-lg text-xs font-medium text-red-600 border border-red-200 hover:bg-red-50 transition"
                      >
                        Remove
                      </button>
                    </td>
                  </tr>
                  <tr v-if="filteredAssigned.length === 0">
                    <td colspan="2" class="py-6 text-center text-gray-400">No students added to this section yet</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Bottom Right Actions -->
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
          <button
            type="button"
            @click="showManageModal = false"
            class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveStudentAssignments"
            :disabled="studentForm.processing"
            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 transition"
          >
            {{ studentForm.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>