<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'
import IdCardFace from '../../components/IdCardFace.vue'

// --- PROPS ---
const props = defineProps({
  template: { type: Object, default: () => ({}) },
  sample: { type: Object, default: null },
  students: { type: Array, default: () => [] },
  done_student_ids: { type: Array, default: () => [] },
  grades: { type: Array, default: () => [] },
  sections: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({ grade: '', section: '', status: 'pending', search: '' }) },
  counts: { type: Object, default: () => ({ pending: 0, completed: 0, total: 0 }) },
})

const page = usePage()

// --- CONSTANTS ---
const CARD_WIDTH = 340
const PHOTO_SIZE_PCT = (1 / 2.125) * 100
const ITEMS_PER_PAGE = 20

const AVAILABLE_FIELDS = [
  { key: 'full_name', label: 'Student Name', type: 'text' },
  { key: 'grade_level', label: 'Grade Level', type: 'text' },
  { key: 'section', label: 'Section', type: 'text' },
  { key: 'adviser', label: 'Adviser', type: 'text' },
  { key: 'school_year', label: 'School Year', type: 'text' },
  { key: 'address', label: 'Address', type: 'text' },
  { key: 'birthday', label: 'Birthday', type: 'text' },
  { key: 'parent_name', label: 'Parents/Guardians Name', type: 'text' },
  { key: 'parent_address', label: 'Parents/Guardians Address', type: 'text' },
  { key: 'parent_contact_number', label: 'Parents/Guardians Contact Number', type: 'text' },
  { key: 'photo', label: 'Photo', type: 'photo' },
]

// --- CANVAS & TEMPLATE STATE ---
const frontImage = computed(() => page.props.template?.front_image_url)
const backImage = computed(() => page.props.template?.back_image_url)

const front = ref(props.template?.front_layout?.length ? JSON.parse(JSON.stringify(props.template.front_layout)) : [])
const back = ref(props.template?.back_layout?.length ? JSON.parse(JSON.stringify(props.template.back_layout)) : [])
const activeSample = ref(props.sample)

const selected = ref(null)
const saving = ref(false)
const frontCardEl = ref(null)
const backCardEl = ref(null)

const dragState = ref(null)
const resizeState = ref(null)

// --- FILTERS & TABLE STATE ---
const selectedGradeFilter = ref(props.filters?.grade || props.grades?.[0] || '')
const selectedSectionFilter = ref(props.filters?.section || props.sections?.[0] || '')
const statusFilter = ref(props.filters?.status || 'pending')
const studentSearch = ref(props.filters?.search || '')

const currentPage = ref(1)
const checkedStudentIds = ref(new Set())
const showMarkDoneModal = ref(false)

let searchTimeout = null

// Update state when Inertia reloads partial props
watch(() => props.filters, (newFilters) => {
  if (newFilters) {
    if (newFilters.grade !== undefined) selectedGradeFilter.value = newFilters.grade
    if (newFilters.section !== undefined) selectedSectionFilter.value = newFilters.section
    if (newFilters.status !== undefined) statusFilter.value = newFilters.status
  }
}, { deep: true })

// Backend Filter Requests
function fetchFilteredData() {
  router.get(
    '/id-maker',
    {
      grade: selectedGradeFilter.value,
      section: selectedSectionFilter.value,
      status: statusFilter.value,
      search: studentSearch.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['students', 'sections', 'filters', 'counts', 'sample'],
    }
  )
}

function onGradeChange() {
  selectedSectionFilter.value = ''
  fetchFilteredData()
}

function onSectionOrStatusChange() {
  currentPage.value = 1
  fetchFilteredData()
}

watch(studentSearch, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchFilteredData()
  }, 300)
})

// --- COMPUTED: STUDENTS ---
const studentList = computed(() => {
  if (Array.isArray(props.students)) return props.students
  return props.students?.data || []
})

const dbDoneStudentIds = computed(() => new Set((props.done_student_ids || []).map(Number)))

const totalPages = computed(() => Math.ceil(studentList.value.length / ITEMS_PER_PAGE) || 1)

const paginatedStudents = computed(() => {
  const start = (currentPage.value - 1) * ITEMS_PER_PAGE
  return studentList.value.slice(start, start + ITEMS_PER_PAGE)
})

const checkedDoneIds = computed(() => Array.from(checkedStudentIds.value).filter((id) => dbDoneStudentIds.value.has(id)))
const checkedPendingIds = computed(() => Array.from(checkedStudentIds.value).filter((id) => !dbDoneStudentIds.value.has(id)))

const isAllSelected = computed(() => {
  if (paginatedStudents.value.length === 0) return false
  return paginatedStudents.value.every((s) => checkedStudentIds.value.has(s.id))
})

const printUrl = computed(() => {
  if (checkedPendingIds.value.length > 0) {
    return `/id-maker/print?ids=${checkedPendingIds.value.join(',')}`
  }
  return '#'
})

const printButtonText = computed(() => {
  return `Print Selected IDs (${checkedPendingIds.value.length})`
})

const previewValues = computed(() => ({
  full_name: activeSample.value ? `${activeSample.value.first_name} ${activeSample.value.last_name}` : null,
  grade_level: activeSample.value?.grade_level ?? null,
  section: activeSample.value?.section ?? null,
  adviser: activeSample.value?.adviser ?? activeSample.value?.adviser_name ?? null,
  school_year: activeSample.value?.school_year ?? null,
  address: activeSample.value?.address ?? null,
  birthday: formatDate(activeSample.value?.birthday),
  parent_name: activeSample.value?.parent_name ?? null,
  parent_address: activeSample.value?.parent_address ?? null,
  parent_contact_number: activeSample.value?.parent_contact_number ?? null,
  photo_url: activeSample.value?.photo_url ?? null,
}))

// --- HELPERS ---
function clamp(v, min, max) {
  return Math.max(min, Math.min(max, v))
}

function formatDate(d) {
  if (!d) return null
  const date = new Date(d)
  return Number.isNaN(date.getTime()) ? null : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function isInsideRect(event, rect) {
  return event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom
}

// --- TABLE & SELECTION ACTIONS ---
function selectStudent(s) {
  activeSample.value = s
}

function toggleSelectAll() {
  if (isAllSelected.value) {
    paginatedStudents.value.forEach((s) => checkedStudentIds.value.delete(s.id))
  } else {
    paginatedStudents.value.forEach((s) => checkedStudentIds.value.add(s.id))
  }
}

function toggleStudentCheck(studentId) {
  if (checkedStudentIds.value.has(studentId)) {
    checkedStudentIds.value.delete(studentId)
  } else {
    checkedStudentIds.value.add(studentId)
  }
}

function confirmMarkDone() {
  showMarkDoneModal.value = true
}

function markActiveAsDone() {
  const idsToMark = checkedPendingIds.value.length > 0
    ? checkedPendingIds.value
    : (activeSample.value && !dbDoneStudentIds.value.has(activeSample.value.id) ? [activeSample.value.id] : [])

  if (idsToMark.length === 0) return

  router.post('/id-maker/mark-done', { student_ids: idsToMark }, {
    preserveScroll: true,
    onSuccess: () => {
      checkedStudentIds.value.clear()
      showMarkDoneModal.value = false
    },
  })
}

function unmarkSelectedAsDone() {
  const idsToUnmark = checkedDoneIds.value.length > 0
    ? checkedDoneIds.value
    : (activeSample.value && dbDoneStudentIds.value.has(activeSample.value.id) ? [activeSample.value.id] : [])

  if (idsToUnmark.length === 0) return

  router.post('/id-maker/unmark-done', { student_ids: idsToUnmark }, {
    preserveScroll: true,
    onSuccess: () => {
      checkedStudentIds.value.clear()
    },
  })
}

// --- CANVAS EDITING ACTIONS ---
function remove(f) {
  front.value = front.value.filter((x) => x.id !== f.id)
  back.value = back.value.filter((x) => x.id !== f.id)
  if (selected.value?.id === f.id) selected.value = null
}

function onPanelDragStart(event, field) {
  event.dataTransfer.setData('text/plain', JSON.stringify({ mode: 'new', key: field.key, label: field.label, type: field.type }))
}

function onDrop(event, side) {
  const raw = event.dataTransfer.getData('text/plain')
  if (!raw) return
  const data = JSON.parse(raw)
  if (data.mode !== 'new') return
  const rect = event.currentTarget.getBoundingClientRect()
  const xPct = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 96)
  const yPct = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 96)
  const targetArr = side === 'front' ? front : back
  const newField = {
    id: Date.now() + Math.random(),
    key: data.key,
    label: data.label,
    type: data.type,
    text: data.type === 'custom' ? data.text : '',
    xPct,
    yPct,
    fontSize: 14,
    bold: false,
    color: '#111827',
    widthPct: data.type === 'photo' ? PHOTO_SIZE_PCT : 80,
    align: 'left',
  }
  targetArr.value.push(newField)
  selected.value = newField
}

function addCustomText() {
  const text = window.prompt('Enter custom text')
  if (!text) return
  const field = {
    id: Date.now() + Math.random(),
    key: 'custom',
    label: 'Custom Text',
    type: 'custom',
    text,
    xPct: 10,
    yPct: 10,
    fontSize: 14,
    bold: false,
    color: '#111827',
    widthPct: 80,
    align: 'left',
  }
  front.value.push(field)
  selected.value = field
}

function startFieldDrag(event, side, field) {
  event.stopPropagation()
  dragState.value = { side, id: field.id, moved: false }
  window.addEventListener('mousemove', onFieldDragMove)
  window.addEventListener('mouseup', onFieldDragEnd)
}

function onFieldDragMove(event) {
  if (!dragState.value) return
  dragState.value.moved = true
  const frontRect = frontCardEl.value?.$el?.getBoundingClientRect()
  const backRect = backCardEl.value?.$el?.getBoundingClientRect()
  let targetSide = null
  let rect = null

  if (frontRect && isInsideRect(event, frontRect)) {
    targetSide = 'front'
    rect = frontRect
  } else if (backRect && isInsideRect(event, backRect)) {
    targetSide = 'back'
    rect = backRect
  }

  if (!targetSide) return

  const xPct = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 96)
  const yPct = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 96)
  const arrays = { front, back }
  const currentSide = dragState.value.side
  const sourceArr = arrays[currentSide]
  const field = sourceArr.value.find((f) => f.id === dragState.value.id)

  if (!field) return

  field.xPct = xPct
  field.yPct = yPct

  if (targetSide !== currentSide) {
    sourceArr.value = sourceArr.value.filter((f) => f.id !== dragState.value.id)
    arrays[targetSide].value.push(field)
    dragState.value.side = targetSide
  }
}

function onFieldDragEnd() {
  window.removeEventListener('mousemove', onFieldDragMove)
  window.removeEventListener('mouseup', onFieldDragEnd)
  if (dragState.value && !dragState.value.moved) {
    const arr = dragState.value.side === 'front' ? front.value : back.value
    const field = arr.find((f) => f.id === dragState.value.id)
    if (field) selected.value = field
  }
  dragState.value = null
}

function startResize(event, side, field) {
  resizeState.value = { side, id: field.id, startX: event.clientX, startWidthPct: field.widthPct || (field.type === 'photo' ? PHOTO_SIZE_PCT : 80) }
  window.addEventListener('mousemove', onResizeMove)
  window.addEventListener('mouseup', onResizeEnd)
}

function onResizeMove(event) {
  if (!resizeState.value) return
  const cardEl = resizeState.value.side === 'front' ? frontCardEl.value?.$el : backCardEl.value?.$el
  if (!cardEl) return
  const rect = cardEl.getBoundingClientRect()
  const deltaPct = ((event.clientX - resizeState.value.startX) / rect.width) * 100
  const arr = resizeState.value.side === 'front' ? front.value : back.value
  const field = arr.find((f) => f.id === resizeState.value.id)
  if (!field) return
  field.widthPct = clamp(resizeState.value.startWidthPct + deltaPct, 15, 100)
}

function onResizeEnd() {
  window.removeEventListener('mousemove', onResizeMove)
  window.removeEventListener('mouseup', onResizeEnd)
  resizeState.value = null
}

function uploadBg(side, event) {
  const file = event.target.files?.[0]
  if (!file) return
  const form = new FormData()
  form.append('image', file)
  router.post(`/id-maker/background/${side}`, form, {
    forceFormData: true,
    preserveScroll: true,
    preserveState: true,
  })
  event.target.value = ''
}

function saveTemplate() {
  saving.value = true
  router.post('/id-maker', {
    front_layout: front.value,
    back_layout: back.value,
  }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { saving.value = false },
  })
}

onUnmounted(() => {
  window.removeEventListener('mousemove', onFieldDragMove)
  window.removeEventListener('mouseup', onFieldDragEnd)
  window.removeEventListener('mousemove', onResizeMove)
  window.removeEventListener('mouseup', onResizeEnd)
  clearTimeout(searchTimeout)
})
</script>

<template>
  <AppLayout active="ID Maker App">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">ID Card Designer</h1>
      <p class="text-sm text-gray-500 mt-1">Upload card backgrounds, drag fields onto them, then print IDs for every student.</p>
    </div>

    <div class="flex items-start gap-8" @click="selected = null">
      <div class="flex flex-wrap gap-6 shrink-0">
        <div @click.stop>
          <div class="flex items-center justify-between mb-2 w-[340px]">
            <p class="text-sm font-semibold text-gray-700">Front Layout</p>
            <label class="text-xs font-medium text-blue-600 cursor-pointer hover:underline">
              Change Front Image
              <input type="file" accept="image/*" class="hidden" @change="uploadBg('front', $event)" />
            </label>
          </div>
          <div @dragover.prevent @drop="onDrop($event, 'front')">
            <IdCardFace
              ref="frontCardEl"
              :image="frontImage"
              :fields="front"
              :values="previewValues"
              :width="CARD_WIDTH"
              :interactive="true"
              :selected-id="selected?.id ?? null"
              @field-mousedown="(e, f) => startFieldDrag(e, 'front', f)"
              @resize-mousedown="(e, f) => startResize(e, 'front', f)"
              @delete="remove"
            />
          </div>
        </div>

        <div @click.stop>
          <div class="flex items-center justify-between mb-2 w-[340px]">
            <p class="text-sm font-semibold text-gray-700">Back Layout</p>
            <label class="text-xs font-medium text-blue-600 cursor-pointer hover:underline">
              Change Back Image
              <input type="file" accept="image/*" class="hidden" @change="uploadBg('back', $event)" />
            </label>
          </div>
          <div @dragover.prevent @drop="onDrop($event, 'back')">
            <IdCardFace
              ref="backCardEl"
              :image="backImage"
              :fields="back"
              :values="previewValues"
              :width="CARD_WIDTH"
              :interactive="true"
              :selected-id="selected?.id ?? null"
              @field-mousedown="(e, f) => startFieldDrag(e, 'back', f)"
              @resize-mousedown="(e, f) => startResize(e, 'back', f)"
              @delete="remove"
            />
          </div>
        </div>
      </div>

      <div class="flex-1 flex gap-6 min-w-0" @click.stop>
        <div class="w-60 shrink-0">
          <h3 class="text-sm font-semibold text-gray-900 mb-3">Database Fields</h3>
          <div class="space-y-2 mb-4">
            <div
              v-for="f in AVAILABLE_FIELDS"
              :key="f.key"
              draggable="true"
              @dragstart="onPanelDragStart($event, f)"
              class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 cursor-grab hover:border-blue-300 hover:bg-blue-50 transition select-none"
              v-text="'{{ ' + f.label + ' }}'"
            ></div>
          </div>

          <button @click="addCustomText" class="w-full text-sm font-medium text-blue-600 border border-dashed border-blue-300 rounded-lg py-2 hover:bg-blue-50 transition mb-6">
            + Add Custom Text
          </button>

          <!-- Selected Field Editor Controls -->
          <div v-if="selected" class="bg-gray-50 rounded-2xl border border-gray-200 p-3 mb-4 text-xs space-y-3">
            <p class="font-semibold text-gray-700 truncate">{{ selected.label }}</p>

            <template v-if="selected.type !== 'photo'">
              <label class="flex items-center justify-between gap-2">
                Font Size
                <input type="number" v-model.number="selected.fontSize" class="w-16 border border-gray-200 rounded px-1.5 py-1 text-center" />
              </label>
              <label class="flex items-center justify-between gap-2 cursor-pointer">
                Bold Font
                <input type="checkbox" v-model="selected.bold" class="rounded border-gray-300 text-blue-600" />
              </label>

              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <span>Text Box Width</span>
                  <span class="text-gray-400 font-mono">{{ selected.widthPct || 80 }}%</span>
                </div>
                <input
                  type="range"
                  min="15"
                  max="100"
                  v-model.number="selected.widthPct"
                  class="w-full cursor-pointer accent-blue-600"
                />
              </div>

              <div>
                <p class="mb-1.5">Text Alignment Inside Box</p>
                <div class="grid grid-cols-3 gap-1">
                  <button
                    type="button"
                    @click="selected.align = 'left'"
                    class="py-1.5 rounded-lg border text-[11px] font-medium transition"
                    :class="selected.align === 'left' || !selected.align ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                  >
                    Left
                  </button>
                  <button
                    type="button"
                    @click="selected.align = 'center'"
                    class="py-1.5 rounded-lg border text-[11px] font-medium transition"
                    :class="selected.align === 'center' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                  >
                    Center
                  </button>
                  <button
                    type="button"
                    @click="selected.align = 'right'"
                    class="py-1.5 rounded-lg border text-[11px] font-medium transition"
                    :class="selected.align === 'right' ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100'"
                  >
                    Right
                  </button>
                </div>
              </div>

              <label class="flex items-center justify-between gap-2">
                Text Color
                <input type="color" v-model="selected.color" class="w-8 h-6 rounded border border-gray-200 cursor-pointer" />
              </label>
            </template>

            <template v-else>
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <span>Photo Size</span>
                  <span class="text-gray-400 font-mono">{{ selected.widthPct || 30 }}%</span>
                </div>
                <input
                  type="range"
                  min="15"
                  max="60"
                  v-model.number="selected.widthPct"
                  class="w-full cursor-pointer accent-blue-600"
                />
              </div>
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <span>Corner Radius</span>
                  <span class="text-gray-400 font-mono">{{ selected.borderRadius || 0 }}%</span>
                </div>
                <input type="range" min="0" max="50" v-model.number="selected.borderRadius" class="w-full cursor-pointer accent-blue-600" />
              </div>
            </template>
          </div>

          <button
            @click="saveTemplate"
            :disabled="saving"
            class="w-full bg-blue-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-blue-700 disabled:opacity-60 transition mb-2"
          >
            {{ saving ? 'Saving...' : 'Save Template' }}
          </button>

          <a
            :href="printUrl"
            target="_blank"
            :class="[
              'block text-center w-full border border-gray-200 text-sm font-semibold rounded-xl py-2.5 transition',
              checkedPendingIds.length === 0
                ? 'opacity-50 pointer-events-none text-gray-400 bg-gray-50 cursor-not-allowed'
                : 'text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ printButtonText }}
          </a>
        </div>

        <div class="flex-1 bg-white rounded-2xl border border-gray-100 p-4 shadow-sm min-w-0 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3 gap-2">
              <div>
                <h3 class="text-sm font-semibold text-gray-900">Registered Students</h3>
                <p class="text-[11px] text-gray-400">{{ counts.completed }}/{{ counts.total }} Done</p>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <button
                  v-if="checkedDoneIds.length > 0 || (activeSample && dbDoneStudentIds.has(activeSample.id))"
                  type="button"
                  @click="unmarkSelectedAsDone"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 transition shadow-sm"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                  </svg>
                  Re-enable {{ checkedDoneIds.length > 1 ? `(${checkedDoneIds.length})` : '' }}
                </button>

                <button
                  type="button"
                  @click="confirmMarkDone"
                  :disabled="checkedPendingIds.length === 0 && (!activeSample || dbDoneStudentIds.has(activeSample.id))"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition shadow-sm"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                  Mark as Done {{ checkedPendingIds.length > 1 ? `(${checkedPendingIds.length})` : '' }}
                </button>
              </div>
            </div>

            <!-- Backend Query Status Tabs -->
            <div class="flex items-center bg-gray-100 p-1 rounded-xl mb-3 text-xs font-semibold">
              <button
                type="button"
                @click="statusFilter = 'pending'; onSectionOrStatusChange()"
                :class="[
                  'flex-1 py-1.5 rounded-lg transition text-center',
                  statusFilter === 'pending'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-900'
                ]"
              >
                In Progress ({{ counts.pending }})
              </button>

              <button
                type="button"
                @click="statusFilter = 'completed'; onSectionOrStatusChange()"
                :class="[
                  'flex-1 py-1.5 rounded-lg transition text-center',
                  statusFilter === 'completed'
                    ? 'bg-white text-green-700 shadow-sm'
                    : 'text-gray-500 hover:text-gray-900'
                ]"
              >
                Completed ({{ counts.completed }})
              </button>

              <button
                type="button"
                @click="statusFilter = 'all'; onSectionOrStatusChange()"
                :class="[
                  'flex-1 py-1.5 rounded-lg transition text-center',
                  statusFilter === 'all'
                    ? 'bg-white text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-900'
                ]"
              >
                All ({{ counts.total }})
              </button>
            </div>

            <!-- Grade & Section Dropdowns -->
            <div class="flex flex-col gap-2 mb-3">
              <div class="relative w-full">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                  v-model="studentSearch"
                  type="text"
                  placeholder="Search student name..."
                  class="w-full bg-gray-50 rounded-xl border border-gray-200 pl-8 pr-3 py-1.5 text-xs placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                />
              </div>

              <div class="grid grid-cols-2 gap-2">
                <select
                  v-model="selectedGradeFilter"
                  @change="onGradeChange"
                  class="w-full bg-gray-50 rounded-xl border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                >
                  <option v-for="g in grades" :key="g" :value="g">
                    {{ g }}
                  </option>
                </select>

                <select
                  v-model="selectedSectionFilter"
                  @change="onSectionOrStatusChange"
                  class="w-full bg-gray-50 rounded-xl border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                >
                  <option v-for="sec in sections" :key="sec" :value="sec">
                    {{ sec }}
                  </option>
                </select>
              </div>
            </div>

            <div class="max-h-[520px] overflow-y-auto">
              <table class="w-full text-xs text-left">
                <thead>
                  <tr class="text-gray-400 border-b border-gray-100">
                    <th class="pb-2 pl-1 w-6">
                      <input
                        type="checkbox"
                        :checked="isAllSelected"
                        @change="toggleSelectAll"
                        title="Select / Deselect Page"
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500/30 cursor-pointer"
                      />
                    </th>
                    <th class="pb-2 font-medium w-8 text-center">#</th>
                    <th class="pb-2 font-medium">Student Name</th>
                    <th class="pb-2 font-medium">Grade</th>
                    <th class="pb-2 font-medium">Section</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                  <tr
                    v-for="(s, index) in paginatedStudents"
                    :key="s.id"
                    @click="selectStudent(s)"
                    class="cursor-pointer transition select-none"
                    :class="[
                      dbDoneStudentIds.has(s.id)
                        ? 'bg-green-50/70 text-gray-400 line-through'
                        : activeSample?.id === s.id
                          ? 'bg-blue-50 font-semibold text-blue-600'
                          : 'hover:bg-blue-50/50 text-gray-700'
                    ]"
                  >
                    <td class="py-2.5 pl-1 pr-2" @click.stop>
                      <input
                        type="checkbox"
                        :checked="checkedStudentIds.has(s.id)"
                        @change="toggleStudentCheck(s.id)"
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500/30 cursor-pointer"
                      />
                    </td>
                    <td class="py-2.5 text-center font-medium text-gray-400">
                      {{ (currentPage - 1) * ITEMS_PER_PAGE + index + 1 }}
                    </td>
                    <td class="py-2.5 flex items-center gap-2">
                      <div class="w-6 h-6 rounded-full bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center text-[9px] font-bold text-gray-400 border border-gray-200">
                        <img v-if="s.photo_url" :src="s.photo_url" class="w-full h-full object-cover" />
                        <span v-else>{{ s.first_name?.[0] }}{{ s.last_name?.[0] }}</span>
                      </div>
                      <span class="truncate">
                        {{ s.first_name }} {{ s.last_name }}
                      </span>
                    </td>
                    <td class="py-2.5 text-gray-500 truncate">{{ s.grade_level || 'N/A' }}</td>
                    <td class="py-2.5 text-gray-500 truncate">{{ s.section || '-' }}</td>
                  </tr>
                  <tr v-if="studentList.length === 0">
                    <td colspan="5" class="py-6 text-center text-gray-400">
                      No matching students found in this section.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="totalPages > 1" class="flex items-center justify-between pt-3 mt-2 border-t border-gray-100 text-xs">
            <span class="text-gray-400">
              Page {{ currentPage }} of {{ totalPages }} ({{ studentList.length }} total)
            </span>
            <div class="flex items-center gap-1">
              <button
                type="button"
                @click="currentPage--"
                :disabled="currentPage === 1"
                class="px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
              >
                Previous
              </button>
              <button
                type="button"
                @click="currentPage++"
                :disabled="currentPage === totalPages"
                class="px-2.5 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showMarkDoneModal"
      class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4"
      @click.self="showMarkDoneModal = false"
    >
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-semibold text-gray-900">Mark as Done</h3>
          <button @click="showMarkDoneModal = false" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <p class="text-xs text-gray-600 mb-6 leading-relaxed">
          Are you sure you want to mark the student(s) as done? They will be disabled and excluded from future ID print batches.
        </p>

        <div class="flex gap-3">
          <button
            type="button"
            @click="showMarkDoneModal = false"
            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="markActiveAsDone"
            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-white bg-green-600 hover:bg-green-700 transition"
          >
            Yes, Mark as Done
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
