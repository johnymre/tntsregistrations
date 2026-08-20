<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import IdCardFace from '../../components/IdCardFace.vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface Field {
    id: number | string;
    key: string;
    label: string;
    type: string;
    text?: string;
    xPct?: number;
    yPct?: number;
    fontSize?: number;
    bold?: boolean;
    color?: string;
    widthPct?: number;
    align?: 'left' | 'center' | 'right';
    borderRadius?: number;
}

interface Student {
    id: number;
    first_name: string;
    last_name: string;
    grade_level?: string;
    section?: string;
    adviser?: string;
    adviser_name?: string;
    school_year?: string;
    address?: string;
    birthday?: string;
    parent_name?: string;
    parent_address?: string;
    parent_contact_number?: string;
    photo_url?: string;
}

interface Template {
    front_image_url?: string;
    back_image_url?: string;
    front_layout?: Field[];
    back_layout?: Field[];
}

const props = withDefaults(
    defineProps<{
        template?: Template;
        sample?: Student | null;
        students?: Student[] | { data: Student[] };
        done_student_ids?: number[];
        grades?: string[];
        sections?: string[];
        filters?: {
            grade?: string;
            section?: string;
            status?: string;
            search?: string;
        };
        counts?: { pending: number; completed: number; total: number };
    }>(),
    {
        template: () => ({}),
        sample: null,
        students: () => [],
        done_student_ids: () => [],
        grades: () => [],
        sections: () => [],
        filters: () => ({
            grade: '',
            section: '',
            status: 'pending',
            search: '',
        }),
        counts: () => ({ pending: 0, completed: 0, total: 0 }),
    },
);

const page = usePage();

const CARD_WIDTH = 340;
const PHOTO_SIZE_PCT = (1 / 2.125) * 100;
const ITEMS_PER_PAGE = 20;

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
    {
        key: 'parent_contact_number',
        label: 'Parents/Guardians Contact Number',
        type: 'text',
    },
    { key: 'photo', label: 'Photo', type: 'photo' },
];

const frontImage = computed(
    () => (page.props.template as Template)?.front_image_url,
);
const backImage = computed(
    () => (page.props.template as Template)?.back_image_url,
);

const front = ref<Field[]>(
    props.template?.front_layout?.length
        ? JSON.parse(JSON.stringify(props.template.front_layout))
        : [],
);
const back = ref<Field[]>(
    props.template?.back_layout?.length
        ? JSON.parse(JSON.stringify(props.template.back_layout))
        : [],
);

const activeSample = ref<Student | null>(props.sample);
const selected = ref<Field | null>(null);
const saving = ref(false);

const frontCardEl = ref<{ $el: HTMLElement } | null>(null);
const backCardEl = ref<{ $el: HTMLElement } | null>(null);

const dragState = ref<{
    side: 'front' | 'back';
    id: number | string;
    moved: boolean;
} | null>(null);
const resizeState = ref<{
    side: 'front' | 'back';
    id: number | string;
    startX: number;
    startWidthPct: number;
} | null>(null);

const selectedGradeFilter = ref(
    props.filters?.grade || props.grades?.[0] || '',
);
const selectedSectionFilter = ref(
    props.filters?.section || props.sections?.[0] || '',
);
const statusFilter = ref(props.filters?.status || 'pending');
const studentSearch = ref(props.filters?.search || '');

const currentPage = ref(1);
const checkedStudentIds = ref(new Set<number>());
const showMarkDoneModal = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.grade !== undefined) {
                selectedGradeFilter.value = newFilters.grade;
            }

            if (newFilters.section !== undefined) {
                selectedSectionFilter.value = newFilters.section;
            }

            if (newFilters.status !== undefined) {
                statusFilter.value = newFilters.status;
            }
        }
    },
    { deep: true },
);

function fetchFilteredData(): void {
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
        },
    );
}

function onGradeChange(): void {
    selectedSectionFilter.value = '';
    fetchFilteredData();
}

function onSectionOrStatusChange(): void {
    currentPage.value = 1;
    fetchFilteredData();
}

watch(studentSearch, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchFilteredData();
    }, 300);
});

const studentList = computed<Student[]>(() => {
    if (Array.isArray(props.students)) {
        return props.students;
    }

    return props.students?.data || [];
});

const dbDoneStudentIds = computed(
    () => new Set((props.done_student_ids || []).map(Number)),
);
const totalPages = computed(
    () => Math.ceil(studentList.value.length / ITEMS_PER_PAGE) || 1,
);

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * ITEMS_PER_PAGE;

    return studentList.value.slice(start, start + ITEMS_PER_PAGE);
});

const checkedDoneIds = computed(() =>
    Array.from(checkedStudentIds.value).filter((id) =>
        dbDoneStudentIds.value.has(id),
    ),
);
const checkedPendingIds = computed(() =>
    Array.from(checkedStudentIds.value).filter(
        (id) => !dbDoneStudentIds.value.has(id),
    ),
);

const isAllSelected = computed(() => {
    if (paginatedStudents.value.length === 0) {
        return false;
    }

    return paginatedStudents.value.every((s) =>
        checkedStudentIds.value.has(s.id),
    );
});

const printUrl = computed(() => {
    if (checkedPendingIds.value.length > 0) {
        return `/id-maker/print?ids=${checkedPendingIds.value.join(',')}`;
    }

    return '#';
});

const printButtonText = computed(
    () => `Print Selected IDs (${checkedPendingIds.value.length})`,
);

function formatDate(d?: string): string | null {
    if (!d) {
        return null;
    }

    const date = new Date(d);

    return Number.isNaN(date.getTime())
        ? null
        : date.toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          });
}

const previewValues = computed<Record<string, string | null>>(() => ({
    full_name: activeSample.value
        ? `${activeSample.value.first_name} ${activeSample.value.last_name}`
        : null,
    grade_level: activeSample.value?.grade_level ?? null,
    section: activeSample.value?.section ?? null,
    adviser:
        activeSample.value?.adviser ?? activeSample.value?.adviser_name ?? null,
    school_year: activeSample.value?.school_year ?? null,
    address: activeSample.value?.address ?? null,
    birthday: formatDate(activeSample.value?.birthday),
    parent_name: activeSample.value?.parent_name ?? null,
    parent_address: activeSample.value?.parent_address ?? null,
    parent_contact_number: activeSample.value?.parent_contact_number ?? null,
    photo_url: activeSample.value?.photo_url ?? null,
}));

function clamp(v: number, min: number, max: number): number {
    return Math.max(min, Math.min(max, v));
}

function isInsideRect(event: MouseEvent, rect: DOMRect): boolean {
    return (
        event.clientX >= rect.left &&
        event.clientX <= rect.right &&
        event.clientY >= rect.top &&
        event.clientY <= rect.bottom
    );
}

function selectStudent(s: Student): void {
    activeSample.value = s;
}

function toggleSelectAll(): void {
    if (isAllSelected.value) {
        paginatedStudents.value.forEach((s) =>
            checkedStudentIds.value.delete(s.id),
        );
    } else {
        paginatedStudents.value.forEach((s) =>
            checkedStudentIds.value.add(s.id),
        );
    }
}

function toggleStudentCheck(studentId: number): void {
    if (checkedStudentIds.value.has(studentId)) {
        checkedStudentIds.value.delete(studentId);
    } else {
        checkedStudentIds.value.add(studentId);
    }
}

function confirmMarkDone(): void {
    showMarkDoneModal.value = true;
}

function markActiveAsDone(): void {
    const idsToMark =
        checkedPendingIds.value.length > 0
            ? checkedPendingIds.value
            : activeSample.value &&
                !dbDoneStudentIds.value.has(activeSample.value.id)
              ? [activeSample.value.id]
              : [];

    if (idsToMark.length === 0) {
        return;
    }

    router.post(
        '/id-maker/mark-done',
        { student_ids: idsToMark },
        {
            preserveScroll: true,
            onSuccess: () => {
                checkedStudentIds.value.clear();
                showMarkDoneModal.value = false;
            },
        },
    );
}

function unmarkSelectedAsDone(): void {
    const idsToUnmark =
        checkedDoneIds.value.length > 0
            ? checkedDoneIds.value
            : activeSample.value &&
                dbDoneStudentIds.value.has(activeSample.value.id)
              ? [activeSample.value.id]
              : [];

    if (idsToUnmark.length === 0) {
        return;
    }

    router.post(
        '/id-maker/unmark-done',
        { student_ids: idsToUnmark },
        {
            preserveScroll: true,
            onSuccess: () => {
                checkedStudentIds.value.clear();
            },
        },
    );
}

function remove(f: Field): void {
    front.value = front.value.filter((x) => x.id !== f.id);
    back.value = back.value.filter((x) => x.id !== f.id);

    if (selected.value?.id === f.id) {
        selected.value = null;
    }
}

function onPanelDragStart(
    event: DragEvent,
    field: { key: string; label: string; type: string },
): void {
    if (event.dataTransfer) {
        event.dataTransfer.setData(
            'text/plain',
            JSON.stringify({
                mode: 'new',
                key: field.key,
                label: field.label,
                type: field.type,
            }),
        );
    }
}

function onDrop(event: DragEvent, side: 'front' | 'back'): void {
    if (!event.dataTransfer) {
        return;
    }

    const raw = event.dataTransfer.getData('text/plain');

    if (!raw) {
        return;
    }

    const data = JSON.parse(raw);

    if (data.mode !== 'new') {
        return;
    }

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const xPct = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 96);
    const yPct = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 96);

    const targetArr = side === 'front' ? front : back;
    const newField: Field = {
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
    };

    targetArr.value.push(newField);
    selected.value = newField;
}

function addCustomText(): void {
    const text = window.prompt('Enter custom text');

    if (!text) {
        return;
    }

    const field: Field = {
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
    };
    front.value.push(field);
    selected.value = field;
}

function startFieldDrag(
    event: MouseEvent,
    side: 'front' | 'back',
    field: Field,
): void {
    event.stopPropagation();
    dragState.value = { side, id: field.id, moved: false };
    window.addEventListener('mousemove', onFieldDragMove);
    window.addEventListener('mouseup', onFieldDragEnd);
}

function onFieldDragMove(event: MouseEvent): void {
    if (!dragState.value) {
        return;
    }

    dragState.value.moved = true;

    const frontRect = frontCardEl.value?.$el?.getBoundingClientRect();
    const backRect = backCardEl.value?.$el?.getBoundingClientRect();

    let targetSide: 'front' | 'back' | null = null;
    let rect: DOMRect | null = null;

    if (frontRect && isInsideRect(event, frontRect)) {
        targetSide = 'front';
        rect = frontRect;
    } else if (backRect && isInsideRect(event, backRect)) {
        targetSide = 'back';
        rect = backRect;
    }

    if (!targetSide || !rect) {
        return;
    }

    const xPct = clamp(((event.clientX - rect.left) / rect.width) * 100, 0, 96);
    const yPct = clamp(((event.clientY - rect.top) / rect.height) * 100, 0, 96);

    const arrays = { front, back };
    const currentSide = dragState.value.side;
    const sourceArr = arrays[currentSide];
    const field = sourceArr.value.find((f) => f.id === dragState.value!.id);

    if (!field) {
        return;
    }

    field.xPct = xPct;
    field.yPct = yPct;

    if (targetSide !== currentSide) {
        sourceArr.value = sourceArr.value.filter(
            (f) => f.id !== dragState.value!.id,
        );
        arrays[targetSide].value.push(field);
        dragState.value.side = targetSide;
    }
}

function onFieldDragEnd(): void {
    window.removeEventListener('mousemove', onFieldDragMove);
    window.removeEventListener('mouseup', onFieldDragEnd);

    if (dragState.value && !dragState.value.moved) {
        const arr = dragState.value.side === 'front' ? front.value : back.value;
        const field = arr.find((f) => f.id === dragState.value!.id);

        if (field) {
            selected.value = field;
        }
    }

    dragState.value = null;
}

function startResize(
    event: MouseEvent,
    side: 'front' | 'back',
    field: Field,
): void {
    resizeState.value = {
        side,
        id: field.id,
        startX: event.clientX,
        startWidthPct:
            field.widthPct || (field.type === 'photo' ? PHOTO_SIZE_PCT : 80),
    };
    window.addEventListener('mousemove', onResizeMove);
    window.addEventListener('mouseup', onResizeEnd);
}

function onResizeMove(event: MouseEvent): void {
    const state = resizeState.value;

    if (!state) {
        return;
    } // TypeScript now knows 'state' is NOT null below this line

    const cardEl =
        state.side === 'front' ? frontCardEl.value?.$el : backCardEl.value?.$el;

    if (!cardEl) {
        return;
    }

    const rect = cardEl.getBoundingClientRect();
    const deltaPct = ((event.clientX - state.startX) / rect.width) * 100;
    const arr = state.side === 'front' ? front.value : back.value;
    const field = arr.find((f) => f.id === state.id);

    if (!field) {
        return;
    }

    field.widthPct = clamp(state.startWidthPct + deltaPct, 15, 100);
}

function onResizeEnd(): void {
    window.removeEventListener('mousemove', onResizeMove);
    window.removeEventListener('mouseup', onResizeEnd);
    resizeState.value = null;
}

function uploadBg(side: 'front' | 'back', event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    const form = new FormData();
    form.append('image', file);

    router.post(`/id-maker/background/${side}`, form, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
    });

    target.value = '';
}

function saveTemplate(): void {
    saving.value = true;
    router.post(
        '/id-maker',
        {
            front_layout: front.value,
            back_layout: back.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

onUnmounted(() => {
    window.removeEventListener('mousemove', onFieldDragMove);
    window.removeEventListener('mouseup', onFieldDragEnd);
    window.removeEventListener('mousemove', onResizeMove);
    window.removeEventListener('mouseup', onResizeEnd);

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<template>
    <AppLayout active="ID Maker App">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">ID Card Designer</h1>
            <p class="mt-1 text-sm text-gray-500">
                Upload card backgrounds, drag fields onto them, then print IDs
                for every student.
            </p>
        </div>

        <div class="flex items-start gap-8" @click="selected = null">
            <div class="flex shrink-0 flex-wrap gap-6">
                <div @click.stop>
                    <div
                        class="mb-2 flex w-[340px] items-center justify-between"
                    >
                        <p class="text-sm font-semibold text-gray-700">
                            Front Layout
                        </p>
                        <label
                            class="cursor-pointer text-xs font-medium text-blue-600 hover:underline"
                        >
                            Change Front Image
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="uploadBg('front', $event)"
                            />
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
                            :selected-id="selected?.id ?? undefined"
                            @field-mousedown="
                                (e: MouseEvent, f: Field) =>
                                    startFieldDrag(e, 'front', f)
                            "
                            @resize-mousedown="
                                (e: MouseEvent, f: Field) =>
                                    startResize(e, 'front', f)
                            "
                            @delete="remove"
                        />
                    </div>
                </div>

                <div @click.stop>
                    <div
                        class="mb-2 flex w-[340px] items-center justify-between"
                    >
                        <p class="text-sm font-semibold text-gray-700">
                            Back Layout
                        </p>
                        <label
                            class="cursor-pointer text-xs font-medium text-blue-600 hover:underline"
                        >
                            Change Back Image
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="uploadBg('back', $event)"
                            />
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
                            :selected-id="selected?.id ?? undefined"
                            @field-mousedown="
                                (e: MouseEvent, f: Field) =>
                                    startFieldDrag(e, 'back', f)
                            "
                            @resize-mousedown="
                                (e: MouseEvent, f: Field) =>
                                    startResize(e, 'back', f)
                            "
                            @delete="remove"
                        />
                    </div>
                </div>
            </div>

            <div class="flex min-w-0 flex-1 gap-6" @click.stop>
                <div class="w-60 shrink-0">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">
                        Database Fields
                    </h3>
                    <div class="mb-4 space-y-2">
                        <div
                            v-for="f in AVAILABLE_FIELDS"
                            :key="f.key"
                            draggable="true"
                            @dragstart="onPanelDragStart($event, f)"
                            class="cursor-grab rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 transition select-none hover:border-blue-300 hover:bg-blue-50"
                            v-text="'{{ ' + f.label + ' }}'"
                        ></div>
                    </div>
                    <button
                        @click="addCustomText"
                        class="mb-6 w-full rounded-lg border border-dashed border-blue-300 py-2 text-sm font-medium text-blue-600 transition hover:bg-blue-50"
                    >
                        + Add Custom Text
                    </button>

                    <!-- Selected Field Editor Controls -->
                    <div
                        v-if="selected"
                        class="mb-4 space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-3 text-xs"
                    >
                        <p class="truncate font-semibold text-gray-700">
                            {{ selected.label }}
                        </p>
                        <template v-if="selected.type !== 'photo'">
                            <label
                                class="flex items-center justify-between gap-2"
                            >
                                Font Size
                                <input
                                    type="number"
                                    v-model.number="selected.fontSize"
                                    class="w-16 rounded border border-gray-200 px-1.5 py-1 text-center"
                                />
                            </label>
                            <label
                                class="flex cursor-pointer items-center justify-between gap-2"
                            >
                                Bold Font
                                <input
                                    type="checkbox"
                                    v-model="selected.bold"
                                    class="rounded border-gray-300 text-blue-600"
                                />
                            </label>
                            <div>
                                <div
                                    class="mb-1.5 flex items-center justify-between"
                                >
                                    <span>Text Box Width</span>
                                    <span class="font-mono text-gray-400"
                                        >{{ selected.widthPct || 80 }}%</span
                                    >
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
                                        class="rounded-lg border py-1.5 text-[11px] font-medium transition"
                                        :class="
                                            selected.align === 'left' ||
                                            !selected.align
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'
                                        "
                                    >
                                        Left
                                    </button>
                                    <button
                                        type="button"
                                        @click="selected.align = 'center'"
                                        class="rounded-lg border py-1.5 text-[11px] font-medium transition"
                                        :class="
                                            selected.align === 'center'
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'
                                        "
                                    >
                                        Center
                                    </button>
                                    <button
                                        type="button"
                                        @click="selected.align = 'right'"
                                        class="rounded-lg border py-1.5 text-[11px] font-medium transition"
                                        :class="
                                            selected.align === 'right'
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-gray-200 text-gray-600 hover:bg-gray-100'
                                        "
                                    >
                                        Right
                                    </button>
                                </div>
                            </div>
                            <label
                                class="flex items-center justify-between gap-2"
                            >
                                Text Color
                                <input
                                    type="color"
                                    v-model="selected.color"
                                    class="h-6 w-8 cursor-pointer rounded border border-gray-200"
                                />
                            </label>
                        </template>
                        <template v-else>
                            <div>
                                <div
                                    class="mb-1.5 flex items-center justify-between"
                                >
                                    <span>Photo Size</span>
                                    <span class="font-mono text-gray-400"
                                        >{{ selected.widthPct || 30 }}%</span
                                    >
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
                                <div
                                    class="mb-1.5 flex items-center justify-between"
                                >
                                    <span>Corner Radius</span>
                                    <span class="font-mono text-gray-400"
                                        >{{ selected.borderRadius || 0 }}%</span
                                    >
                                </div>
                                <input
                                    type="range"
                                    min="0"
                                    max="50"
                                    v-model.number="selected.borderRadius"
                                    class="w-full cursor-pointer accent-blue-600"
                                />
                            </div>
                        </template>
                    </div>

                    <button
                        @click="saveTemplate"
                        :disabled="saving"
                        class="mb-2 w-full rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                    >
                        {{ saving ? 'Saving...' : 'Save Template' }}
                    </button>
                    <a
                        :href="printUrl"
                        target="_blank"
                        :class="[
                            'block w-full rounded-xl border border-gray-200 py-2.5 text-center text-sm font-semibold transition',
                            checkedPendingIds.length === 0
                                ? 'pointer-events-none cursor-not-allowed bg-gray-50 text-gray-400 opacity-50'
                                : 'text-gray-700 hover:bg-gray-50',
                        ]"
                    >
                        {{ printButtonText }}
                    </a>
                </div>

                <div
                    class="flex min-w-0 flex-1 flex-col justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div>
                        <div
                            class="mb-3 flex items-center justify-between gap-2"
                        >
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">
                                    Registered Students
                                </h3>
                                <p class="text-[11px] text-gray-400">
                                    {{ counts.completed }}/{{ counts.total }}
                                    Done
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    v-if="
                                        checkedDoneIds.length > 0 ||
                                        (activeSample &&
                                            dbDoneStudentIds.has(
                                                activeSample.id,
                                            ))
                                    "
                                    type="button"
                                    @click="unmarkSelectedAsDone"
                                    class="flex items-center gap-1.5 rounded-xl bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 shadow-sm transition hover:bg-amber-200"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"
                                        />
                                    </svg>
                                    Re-enable
                                    {{
                                        checkedDoneIds.length > 1
                                            ? `(${checkedDoneIds.length})`
                                            : ''
                                    }}
                                </button>
                                <button
                                    type="button"
                                    @click="confirmMarkDone"
                                    :disabled="
                                        checkedPendingIds.length === 0 &&
                                        (!activeSample ||
                                            dbDoneStudentIds.has(
                                                activeSample.id,
                                            ))
                                    "
                                    class="flex items-center gap-1.5 rounded-xl bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-50"
                                >
                                    <svg
                                        class="h-3.5 w-3.5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4.5 12.75l6 6 9-13.5"
                                        />
                                    </svg>
                                    Mark as Done
                                    {{
                                        checkedPendingIds.length > 1
                                            ? `(${checkedPendingIds.length})`
                                            : ''
                                    }}
                                </button>
                            </div>
                        </div>

                        <!-- Status Tabs -->
                        <div
                            class="mb-3 flex items-center rounded-xl bg-gray-100 p-1 text-xs font-semibold"
                        >
                            <button
                                type="button"
                                @click="
                                    statusFilter = 'pending';
                                    onSectionOrStatusChange();
                                "
                                :class="[
                                    'flex-1 rounded-lg py-1.5 text-center transition',
                                    statusFilter === 'pending'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-900',
                                ]"
                            >
                                In Progress ({{ counts.pending }})
                            </button>
                            <button
                                type="button"
                                @click="
                                    statusFilter = 'completed';
                                    onSectionOrStatusChange();
                                "
                                :class="[
                                    'flex-1 rounded-lg py-1.5 text-center transition',
                                    statusFilter === 'completed'
                                        ? 'bg-white text-green-700 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-900',
                                ]"
                            >
                                Completed ({{ counts.completed }})
                            </button>
                            <button
                                type="button"
                                @click="
                                    statusFilter = 'all';
                                    onSectionOrStatusChange();
                                "
                                :class="[
                                    'flex-1 rounded-lg py-1.5 text-center transition',
                                    statusFilter === 'all'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-900',
                                ]"
                            >
                                All ({{ counts.total }})
                            </button>
                        </div>

                        <!-- Grade & Section Dropdowns -->
                        <div class="mb-3 flex flex-col gap-2">
                            <div class="relative w-full">
                                <svg
                                    class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
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
                                    v-model="studentSearch"
                                    type="text"
                                    placeholder="Search student name..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-1.5 pr-3 pl-8 text-xs placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <select
                                    v-model="selectedGradeFilter"
                                    @change="onGradeChange"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-medium text-gray-600 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                                >
                                    <option
                                        v-for="g in grades"
                                        :key="g"
                                        :value="g"
                                    >
                                        {{ g }}
                                    </option>
                                </select>
                                <select
                                    v-model="selectedSectionFilter"
                                    @change="onSectionOrStatusChange"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-medium text-gray-600 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                                >
                                    <option
                                        v-for="sec in sections"
                                        :key="sec"
                                        :value="sec"
                                    >
                                        {{ sec }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="max-h-[520px] overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-gray-100 text-gray-400"
                                    >
                                        <th class="w-6 pb-2 pl-1">
                                            <input
                                                type="checkbox"
                                                :checked="isAllSelected"
                                                @change="toggleSelectAll"
                                                title="Select / Deselect Page"
                                                class="cursor-pointer rounded border-gray-300 text-green-600 focus:ring-green-500/30"
                                            />
                                        </th>
                                        <th
                                            class="w-8 pb-2 text-center font-medium"
                                        >
                                            #
                                        </th>
                                        <th class="pb-2 font-medium">
                                            Student Name
                                        </th>
                                        <th class="pb-2 font-medium">Grade</th>
                                        <th class="pb-2 font-medium">
                                            Section
                                        </th>
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
                                                  : 'text-gray-700 hover:bg-blue-50/50',
                                        ]"
                                    >
                                        <td
                                            class="py-2.5 pr-2 pl-1"
                                            @click.stop
                                        >
                                            <input
                                                type="checkbox"
                                                :checked="
                                                    checkedStudentIds.has(s.id)
                                                "
                                                @change="
                                                    toggleStudentCheck(s.id)
                                                "
                                                class="cursor-pointer rounded border-gray-300 text-green-600 focus:ring-green-500/30"
                                            />
                                        </td>
                                        <td
                                            class="py-2.5 text-center font-medium text-gray-400"
                                        >
                                            {{
                                                (currentPage - 1) *
                                                    ITEMS_PER_PAGE +
                                                index +
                                                1
                                            }}
                                        </td>
                                        <td
                                            class="flex items-center gap-2 py-2.5"
                                        >
                                            <div
                                                class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-gray-100 text-[9px] font-bold text-gray-400"
                                            >
                                                <img
                                                    v-if="s.photo_url"
                                                    :src="s.photo_url"
                                                    class="h-full w-full object-cover"
                                                />
                                                <span v-else
                                                    >{{ s.first_name?.[0]
                                                    }}{{
                                                        s.last_name?.[0]
                                                    }}</span
                                                >
                                            </div>
                                            <span class="truncate">
                                                {{ s.first_name }}
                                                {{ s.last_name }}
                                            </span>
                                        </td>
                                        <td
                                            class="truncate py-2.5 text-gray-500"
                                        >
                                            {{ s.grade_level || 'N/A' }}
                                        </td>
                                        <td
                                            class="truncate py-2.5 text-gray-500"
                                        >
                                            {{ s.section || '-' }}
                                        </td>
                                    </tr>
                                    <tr v-if="studentList.length === 0">
                                        <td
                                            colspan="5"
                                            class="py-6 text-center text-gray-400"
                                        >
                                            No matching students found in this
                                            section.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        v-if="totalPages > 1"
                        class="mt-2 flex items-center justify-between border-t border-gray-100 pt-3 text-xs"
                    >
                        <span class="text-gray-400">
                            Page {{ currentPage }} of {{ totalPages }} ({{
                                studentList.length
                            }}
                            total)
                        </span>
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                @click="currentPage--"
                                :disabled="currentPage === 1"
                                class="rounded-lg border border-gray-200 px-2.5 py-1 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                @click="currentPage++"
                                :disabled="currentPage === totalPages"
                                class="rounded-lg border border-gray-200 px-2.5 py-1 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Done Modal -->
        <div
            v-if="showMarkDoneModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            @click.self="showMarkDoneModal = false"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">
                        Mark as Done
                    </h3>
                    <button
                        @click="showMarkDoneModal = false"
                        class="text-gray-400 transition hover:text-gray-600"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <p class="mb-6 text-xs leading-relaxed text-gray-600">
                    Are you sure you want to mark the student(s) as done? They
                    will be disabled and excluded from future ID print batches.
                </p>
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="showMarkDoneModal = false"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="markActiveAsDone"
                        class="flex-1 rounded-xl bg-green-600 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700"
                    >
                        Yes, Mark as Done
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
