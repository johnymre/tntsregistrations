<script setup>
import { computed } from 'vue'
import IdCardFace from '../../components/IdCardFace.vue'

const props = defineProps({
  template: Object,
  students: Array,
})

const RENDER_WIDTH = 340
const TRUE_WIDTH_PX = 2.125 * 96
const SCALE = TRUE_WIDTH_PX / RENDER_WIDTH

function formatDate(d) {
  if (!d) return null
  const date = new Date(d)
  if (Number.isNaN(date.getTime())) return null
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function valuesFor(student) {
  return {
    full_name: `${student.first_name} ${student.last_name}`,
    grade_level: student.grade_level,
    section: student.section,
    adviser: student.adviser,
    school_year: student.school_year,
    address: student.address,
    birthday: formatDate(student.birthday),
    parent_name: student.parent_name,
    parent_address: student.parent_address,
    parent_contact_number: student.parent_contact_number,
    photo_url: student.photo_url,
  }
}

function printPage() {
  window.print()
}

const chunks = computed(() => {
  const size = 4
  const result = []
  for (let i = 0; i < props.students.length; i += size) {
    result.push(props.students.slice(i, i + size))
  }
  return result
})

function rangeLabel(index) {
  const start = index * 4 + 1
  const end = Math.min((index + 1) * 4, props.students.length)
  return `${start}-${end}`
}
</script>

<template>
  <div class="print-root">
    <div class="toolbar">
      <button @click="printPage">Print All IDs</button>
      <p>Set your printer to Long / 8.5" x 13" paper, <strong>landscape orientation</strong>. 4 students per sheet (front row on top, matching back row directly below). Cut each column and pair front with the back beneath it.</p>
    </div>

    <template v-for="(chunk, i) in chunks" :key="i">
      <p class="sheet-label">Students {{ rangeLabel(i) }} — front (top row) / back (bottom row)</p>
      <div class="sheet">
        <div class="grid">
          <!-- Row 1: fronts -->
          <div v-for="student in chunk" :key="'front-' + student.id" class="card-slot">
            <IdCardFace
              :image="template.front_image_url"
              :fields="template.front_layout"
              :values="valuesFor(student)"
              :width="RENDER_WIDTH"
              :interactive="false"
              :style="{ transform: `scale(${SCALE})`, transformOrigin: 'top left' }"
            />
          </div>

          <!-- Row 2: backs -->
          <div v-for="student in chunk" :key="'back-' + student.id" class="card-slot">
            <IdCardFace
              :image="template.back_image_url"
              :fields="template.back_layout"
              :values="valuesFor(student)"
              :width="RENDER_WIDTH"
              :interactive="false"
              :style="{ transform: `scale(${SCALE})`, transformOrigin: 'top left' }"
            />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style>
@page { size: 13in 8.5in; margin: 0.4in 0; }
.print-root { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; }
.toolbar { padding: 16px; text-align: center; background: #fff; border-bottom: 1px solid #e5e7eb; }
.toolbar button { background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.toolbar p { margin: 8px 0 0; font-size: 12px; color: #6b7280; }
.sheet-label { text-align: center; font-size: 11px; color: #9ca3af; padding-top: 8px; margin: 0; }
.sheet { width: 13in; height: 8.5in; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff; margin: 20px auto; box-shadow: 0 1px 6px rgba(0,0,0,0.15); }
.grid { display: grid; grid-template-columns: repeat(4, 2.125in); grid-template-rows: repeat(2, 3.375in); gap: 0.5in; }
.card-slot { width: 2.125in; height: 3.375in; overflow: hidden; position: relative; box-shadow: 0 1px 4px rgba(0,0,0,0.15); }

@media print {
  .print-root { background: #fff; }
  .toolbar { display: none; }
  .sheet-label { display: none; }
  .sheet { margin: 0; box-shadow: none; border: 1px solid #F8F8F8; page-break-after: always; }
  .sheet:last-child { page-break-after: auto; }
  .card-slot { box-shadow: none; border: 1px solid #F8F8F8; }
}
</style>