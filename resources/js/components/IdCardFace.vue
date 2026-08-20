<script setup lang="ts">
import { computed } from 'vue';

interface Field {
    id: number | string;
    type: string;
    key: string;
    label: string;
    text?: string;
    xPct?: number;
    yPct?: number;
    widthPct?: number;
    fontSize?: number;
    bold?: boolean;
    color?: string;
    align?: 'left' | 'center' | 'right';
    borderRadius?: number;
    centered?: boolean;
}

const props = defineProps({
    image: String,
    fields: { type: Array as () => Field[], default: () => [] },
    values: {
        type: Object as () => Record<string, string | null>,
        default: () => ({}),
    },
    width: { type: Number, default: 340 },
    interactive: { type: Boolean, default: false },
    selectedId: [Number, String],
});

const emit = defineEmits(['field-mousedown', 'resize-mousedown', 'delete']);

const cardHeight = computed(() => props.width * 1.586);

function getFieldValue(f: Field): string | null {
    if (f.type === 'custom') {
        return f.text ?? '';
    }

    if (f.key === 'full_name') {
        return props.values.full_name || 'STUDENT NAME';
    }

    if (f.key === 'photo') {
        return props.values.photo_url || null;
    }

    return props.values[f.key] || f.label;
}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md select-none"
        :style="{ width: width + 'px', height: cardHeight + 'px' }"
    >
        <!-- Card Background -->
        <img
            v-if="image"
            :src="image"
            class="pointer-events-none absolute inset-0 h-full w-full object-cover"
            alt="Card Background"
        />
        <div
            v-else
            class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-gray-300"
        >
            No Background Image
        </div>

        <!-- Render Fields -->
        <div
            v-for="f in fields"
            :key="f.id"
            class="absolute cursor-grab transition-shadow active:cursor-grabbing"
            :class="{
                'rounded-sm ring-2 ring-blue-500 ring-offset-1':
                    interactive && selectedId === f.id,
                'hover:ring-1 hover:ring-blue-300':
                    interactive && selectedId !== f.id,
            }"
            :style="{
                left: f.xPct + '%',
                top: f.yPct + '%',
                width:
                    f.type === 'photo'
                        ? f.widthPct
                            ? f.widthPct + '%'
                            : '30%'
                        : f.widthPct
                          ? f.widthPct + '%'
                          : '80%',
                transform: f.centered ? 'translateX(-50%)' : 'none',
            }"
            @mousedown="interactive && emit('field-mousedown', $event, f)"
        >
            <!-- Square Photo Element -->
            <template v-if="f.type === 'photo'">
                <div
                    class="flex aspect-square w-full items-center justify-center overflow-hidden border border-gray-300 bg-gray-100"
                    :style="{ borderRadius: (f.borderRadius || 0) + '%' }"
                >
                    <img
                        v-if="getFieldValue(f)"
                        :src="getFieldValue(f)!"
                        class="h-full w-full object-cover"
                    />
                    <span v-else class="text-[10px] font-medium text-gray-400"
                        >PHOTO</span
                    >
                </div>
            </template>

            <!-- Text Box Element -->
            <template v-else>
                <p
                    class="w-full leading-tight break-words whitespace-normal"
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
                    class="absolute -top-2 -right-2 z-10 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white shadow hover:bg-red-600"
                >
                    ✕
                </button>
                <div
                    class="absolute top-1/2 -right-1.5 z-10 h-3 w-3 -translate-y-1/2 cursor-ew-resize rounded-full border-2 border-white bg-blue-600 shadow"
                    @mousedown.stop="emit('resize-mousedown', $event, f)"
                ></div>
            </template>
        </div>
    </div>
</template>
