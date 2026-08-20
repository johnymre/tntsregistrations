<script setup>
import { computed } from 'vue'

const props = defineProps({
  image: String,
  fields: { type: Array, default: () => [] },
  values: { type: Object, default: () => ({}) },
  width: { type: Number, default: 340 },
  interactive: { type: Boolean, default: false },
  selectedId: [Number, String],
})

const emit = defineEmits(['field-mousedown', 'resize-mousedown', 'delete'])

const cardHeight = computed(() => props.width * 1.586)

function getFieldValue(f) {
  if (f.type === 'custom') return f.text
  if (f.key === 'full_name') return props.values.full_name || 'STUDENT NAME'
  if (f.key === 'photo') return props.values.photo_url || null
  return props.values[f.key] || f.label
}
</script>

<template>
  <div
    class="relative overflow-hidden rounded-2xl border border-gray-200 shadow-md bg-white select-none"
    :style="{ width: width + 'px', height: cardHeight + 'px' }"
  >
    <!-- Card Background -->
    <img
      v-if="image"
      :src="image"
      class="absolute inset-0 w-full h-full object-cover pointer-events-none"
      alt="Card Background"
    />
    <div v-else class="absolute inset-0 flex items-center justify-center text-gray-300 text-xs font-semibold">
      No Background Image
    </div>

    <!-- Render Fields -->
    <div
      v-for="f in fields"
      :key="f.id"
      class="absolute transition-shadow cursor-grab active:cursor-grabbing"
      :class="{
        'ring-2 ring-blue-500 ring-offset-1 rounded-sm': interactive && selectedId === f.id,
        'hover:ring-1 hover:ring-blue-300': interactive && selectedId !== f.id
      }"
      :style="{
        left: f.xPct + '%',
        top: f.yPct + '%',
        width: f.type === 'photo' ? (f.widthPct ? f.widthPct + '%' : '30%') : (f.widthPct ? f.widthPct + '%' : '80%'),
        transform: f.centered ? 'translateX(-50%)' : 'none',
      }"
      @mousedown="interactive && emit('field-mousedown', $event, f)"
    >
      <!-- Square Photo Element -->
      <template v-if="f.type === 'photo'">
        <div
          class="w-full aspect-square bg-gray-100 border border-gray-300 overflow-hidden flex items-center justify-center"
          :style="{ borderRadius: (f.borderRadius || 0) + '%' }"
        >
          <img
            v-if="getFieldValue(f)"
            :src="getFieldValue(f)"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-[10px] text-gray-400 font-medium">PHOTO</span>
        </div>
      </template>

      <!-- Text Box Element -->
      <template v-else>
        <p
          class="w-full leading-tight whitespace-normal break-words"
          :style="{
            fontSize: (f.fontSize || 14) + 'px',
            fontWeight: f.bold ? '700' : '400',
            color: f.color || '#111827',
            textAlign: f.align || 'left',
            lineHeight: '1.25',
          }"
        >
          {{ getFieldValue(f) }}
        </p>
      </template>

      <!-- Resize & Delete Handles -->
      <template v-if="interactive && selectedId === f.id">
        <button
          type="button"
          @click.stop="emit('delete', f)"
          class="absolute -top-2 -right-2 w-4 h-4 bg-red-500 text-white rounded-full flex items-center justify-center text-[10px] shadow hover:bg-red-600 z-10"
        >
          ✕
        </button>

        <div
          class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-blue-600 rounded-full border-2 border-white shadow cursor-ew-resize z-10"
          @mousedown.stop="emit('resize-mousedown', $event, f)"
        ></div>
      </template>
    </div>
  </div>
</template>
