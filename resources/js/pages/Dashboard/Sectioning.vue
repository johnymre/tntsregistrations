<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';

interface Section {
    id: number;
    name: string;
    school_year?: string | null;
    grade_level: string;
    strand?: string | null;
    adviser_name?: string | null;
    room?: string | null;
    enrolled_count: number;
    capacity: number;
}

interface Student {
    id: number;
    first_name: string;
    last_name: string;
    section?: string | null;
}

const props = withDefaults(
    defineProps<{
        sections?: Section[];
        students?: Student[];
    }>(),
    {
        sections: () => [],
        students: () => [],
    },
);

const gradeFilter = ref('All');

const grades = [
    'Grade 7',
    'Grade 8',
    'Grade 9',
    'Grade 10',
    'Grade 11',
    'Grade 12',
];

const gradeOptions = ['7', '8', '9', '10', '11', '12'];

/*
|--------------------------------------------------------------------------
| Create Section
|--------------------------------------------------------------------------
*/

const showModal = ref(false);

const form = useForm({
    name: '',
    school_year: '',
    grade_level: 'Grade 7',
    strand: '',
    adviser_name: '',
    room: '',
    capacity: 40,
});

function openCreateModal(): void {
    form.reset();
    form.grade_level = 'Grade 7';
    form.capacity = 40;
    form.clearErrors();

    showModal.value = true;
}

function submitSection(): void {
    form.post('/sectioning', {
        preserveScroll: true,

        onSuccess: () => {
            showModal.value = false;

            form.reset();
            form.grade_level = 'Grade 7';
            form.capacity = 40;
        },
    });
}

/*
|--------------------------------------------------------------------------
| Manage Students
|--------------------------------------------------------------------------
*/

const showManageModal = ref(false);
const activeSection = ref<Section | null>(null);

const unassignedList = ref<Student[]>([]);
const assignedList = ref<Student[]>([]);

const searchUnassigned = ref('');
const searchAssigned = ref('');

const studentForm = useForm<{ student_ids: number[] }>({
    student_ids: [],
});

function openManageStudents(section: Section): void {
    activeSection.value = section;

    searchUnassigned.value = '';
    searchAssigned.value = '';

    unassignedList.value = props.students.filter(
        (student: Student) => !student.section,
    );

    assignedList.value = props.students.filter(
        (student: Student) => student.section === section.name,
    );

    studentForm.clearErrors();

    showManageModal.value = true;
}

function addStudent(student: Student): void {
    if (
        activeSection.value &&
        assignedList.value.length >= activeSection.value.capacity
    ) {
        return;
    }

    unassignedList.value = unassignedList.value.filter(
        (item: Student) => item.id !== student.id,
    );

    assignedList.value.push(student);
}

function removeStudent(student: Student): void {
    assignedList.value = assignedList.value.filter(
        (item: Student) => item.id !== student.id,
    );

    unassignedList.value.push(student);
}

function saveStudentAssignments(): void {
    if (!activeSection.value) {
        return;
    }

    studentForm.student_ids = assignedList.value.map(
        (student: Student) => student.id,
    );

    studentForm.post(`/sectioning/${activeSection.value.id}/students`, {
        preserveScroll: true,

        onSuccess: () => {
            showManageModal.value = false;
        },
    });
}

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

const filteredUnassigned = computed(() => {
    const query = searchUnassigned.value.toLowerCase().trim();

    if (!query) {
        return unassignedList.value;
    }

    return unassignedList.value.filter((student: Student) =>
        `${student.first_name} ${student.last_name}`
            .toLowerCase()
            .includes(query),
    );
});

const filteredAssigned = computed(() => {
    const query = searchAssigned.value.toLowerCase().trim();

    if (!query) {
        return assignedList.value;
    }

    return assignedList.value.filter((student: Student) =>
        `${student.first_name} ${student.last_name}`
            .toLowerCase()
            .includes(query),
    );
});

const filteredSections = computed(() => {
    return props.sections.filter((section: Section) => {
        return (
            gradeFilter.value === 'All' ||
            section.grade_level === gradeFilter.value
        );
    });
});

function isFull(section: Section): boolean {
    return section.enrolled_count >= section.capacity;
}
</script>

<template>
    <AppLayout active="Sectioning">
        <!-- Header -->
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Section Management
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Manage class sections, advisers, and student capacities for
                    Tanza National Trade School.
                </p>
            </div>

            <button
                type="button"
                @click="openCreateModal"
                class="flex shrink-0 items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"
                    />
                </svg>

                Create New Section
            </button>
        </div>

        <!-- Filters -->
        <div
            class="mb-6 flex items-center gap-2 rounded-2xl border border-gray-100 bg-white p-2"
        >
            <button
                type="button"
                @click="gradeFilter = 'All'"
                class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                :class="
                    gradeFilter === 'All'
                        ? 'bg-blue-600 text-white'
                        : 'text-gray-500 hover:bg-gray-50'
                "
            >
                All Sections
            </button>

            <div class="mx-1 h-6 w-px bg-gray-100"></div>

            <select
                v-model="gradeFilter"
                class="rounded-xl border-none bg-gray-50 px-3.5 py-2 text-sm font-medium text-gray-600 focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
            >
                <option value="All">All Grades</option>

                <option v-for="grade in grades" :key="grade" :value="grade">
                    {{ grade }}
                </option>
            </select>
        </div>

        <!-- Section Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="section in filteredSections"
                :key="section.id"
                class="flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white"
            >
                <div
                    class="relative h-20 overflow-hidden bg-gradient-to-br from-blue-50 to-blue-100 px-5 pt-4"
                >
                    <div
                        class="absolute -top-4 -right-4 h-20 w-20 rounded-full bg-blue-200/40"
                    ></div>

                    <span
                        class="relative mb-1.5 inline-block rounded-md bg-gray-900 px-2 py-1 text-[10px] font-bold tracking-wide text-white"
                    >
                        {{ section.grade_level }}
                    </span>

                    <h3 class="relative text-lg font-bold text-gray-900">
                        {{ section.name }}
                    </h3>
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <div class="mb-4 space-y-3">
                        <div class="flex items-center gap-2.5">
                            <svg
                                class="h-4 w-4 shrink-0 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"
                                />
                            </svg>

                            <div>
                                <p
                                    class="text-[10px] leading-tight tracking-wide text-gray-400 uppercase"
                                >
                                    Adviser
                                </p>

                                <p
                                    class="text-sm leading-tight font-medium text-gray-900"
                                >
                                    {{ section.adviser_name || '-' }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="section.school_year"
                            class="text-xs text-gray-500"
                        >
                            School Year:
                            <span class="font-medium text-gray-800">
                                {{ section.school_year }}
                            </span>
                        </div>

                        <div v-if="section.room" class="text-xs text-gray-500">
                            Room:
                            <span class="font-medium text-gray-800">
                                {{ section.room }}
                            </span>
                        </div>

                        <div
                            v-if="section.strand"
                            class="text-xs text-gray-500"
                        >
                            Strand:
                            <span class="font-medium text-gray-800">
                                {{ section.strand }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div
                            class="mb-1.5 flex items-center justify-between text-xs"
                        >
                            <span
                                :class="
                                    isFull(section)
                                        ? 'font-semibold text-blue-600'
                                        : 'text-gray-400'
                                "
                            >
                                {{
                                    isFull(section)
                                        ? 'Status: Full'
                                        : 'Enrollment Capacity'
                                }}
                            </span>

                            <span class="font-semibold text-gray-900">
                                {{ section.enrolled_count }}/{{
                                    section.capacity
                                }}
                            </span>
                        </div>

                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-gray-100"
                        >
                            <div
                                class="h-full rounded-full transition-all"
                                :class="
                                    isFull(section)
                                        ? 'bg-gray-900'
                                        : 'bg-blue-500'
                                "
                                :style="{
                                    width:
                                        Math.min(
                                            100,
                                            (section.enrolled_count /
                                                section.capacity) *
                                                100,
                                        ) + '%',
                                }"
                            ></div>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <button
                            type="button"
                            @click="openManageStudents(section)"
                            class="w-full rounded-xl bg-blue-600 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                        >
                            Manage Students
                        </button>
                    </div>
                </div>
            </div>

            <!-- Add Another Section -->
            <button
                type="button"
                @click="openCreateModal"
                class="flex min-h-[280px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 py-10 transition hover:border-blue-300 hover:bg-blue-50/30"
            >
                <div
                    class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100"
                >
                    <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4.5v15m7.5-7.5h-15"
                        />
                    </svg>
                </div>

                <p class="text-sm font-semibold text-gray-900">
                    Add Another Section
                </p>

                <p class="mt-0.5 text-xs text-gray-400">
                    Create a new class for the semester
                </p>
            </button>
        </div>

        <!-- Create Section Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div
                class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
            >
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">
                        Create New Section
                    </h3>

                    <button
                        type="button"
                        @click="showModal = false"
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

                <form @submit.prevent="submitSection" class="space-y-4">
                    <!-- Section Name -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Section Name
                        </label>

                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Diamond, Einstein"
                            required
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- School Year -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            School Year
                        </label>

                        <input
                            v-model="form.school_year"
                            type="text"
                            placeholder="e.g. 2026-2027"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.school_year"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.school_year }}
                        </p>
                    </div>

                    <!-- Grade Level -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Grade Level
                        </label>

                        <select
                            v-model="form.grade_level"
                            required
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        >
                            <option
                                v-for="grade in gradeOptions"
                                :key="grade"
                                :value="'Grade ' + grade"
                            >
                                Grade {{ grade }}
                            </option>
                        </select>

                        <p
                            v-if="form.errors.grade_level"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.grade_level }}
                        </p>
                    </div>

                    <!-- Strand -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Strand
                        </label>

                        <input
                            v-model="form.strand"
                            type="text"
                            placeholder="e.g. ICT, STEM, ABM"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.strand"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.strand }}
                        </p>
                    </div>

                    <!-- Adviser -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Adviser Name
                        </label>

                        <input
                            v-model="form.adviser_name"
                            type="text"
                            placeholder="e.g. Maria Santos"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.adviser_name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.adviser_name }}
                        </p>
                    </div>

                    <!-- Room -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Room
                        </label>

                        <input
                            v-model="form.room"
                            type="text"
                            placeholder="e.g. Room 201"
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.room"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.room }}
                        </p>
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-gray-700"
                        >
                            Capacity
                        </label>

                        <input
                            v-model.number="form.capacity"
                            type="number"
                            min="1"
                            required
                            class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                        />

                        <p
                            v-if="form.errors.capacity"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.capacity }}
                        </p>
                    </div>

                    <div class="flex gap-3 pt-3">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="flex-1 rounded-xl border border-gray-200 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                        >
                            {{
                                form.processing
                                    ? 'Creating...'
                                    : 'Create Section'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Students Modal -->
        <div
            v-if="showManageModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-4xl flex-col rounded-2xl bg-white p-6 shadow-xl"
            >
                <div
                    class="mb-4 flex items-center justify-between border-b border-gray-100 pb-4"
                >
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Manage Students - {{ activeSection?.name }}
                        </h3>

                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ activeSection?.grade_level }}
                            | Capacity:
                            {{ assignedList.length }}/{{
                                activeSection?.capacity
                            }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="showManageModal = false"
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

                <div
                    class="mb-6 grid flex-1 grid-cols-1 gap-6 overflow-y-auto pr-1 md:grid-cols-2"
                >
                    <!-- Unassigned -->
                    <div
                        class="flex flex-col rounded-2xl border border-gray-100 bg-gray-50 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h4
                                class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >
                                Unassigned Students
                            </h4>

                            <span class="text-xs font-bold text-gray-400">
                                {{ unassignedList.length }}
                            </span>
                        </div>

                        <input
                            v-model="searchUnassigned"
                            type="text"
                            placeholder="Search unassigned..."
                            class="mb-3 w-full rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                        />

                        <div
                            class="max-h-[320px] flex-1 overflow-y-auto rounded-xl border border-gray-100 bg-white"
                        >
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="sticky top-0 border-b border-gray-100 bg-gray-50"
                                >
                                    <tr class="text-gray-400">
                                        <th class="px-3 py-2 font-medium">
                                            Student
                                        </th>

                                        <th
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="student in filteredUnassigned"
                                        :key="student.id"
                                        class="transition hover:bg-gray-50/80"
                                    >
                                        <td
                                            class="px-3 py-2 font-medium text-gray-800"
                                        >
                                            {{ student.first_name }}
                                            {{ student.last_name }}
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <button
                                                type="button"
                                                @click="addStudent(student)"
                                                :disabled="
                                                    !!activeSection &&
                                                    assignedList.length >=
                                                        activeSection.capacity
                                                "
                                                class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                            >
                                                Add
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredUnassigned.length === 0">
                                        <td
                                            colspan="2"
                                            class="py-6 text-center text-gray-400"
                                        >
                                            No unassigned students
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Assigned -->
                    <div
                        class="flex flex-col rounded-2xl border border-blue-100/60 bg-blue-50/40 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <h4
                                class="text-xs font-semibold tracking-wider text-blue-700 uppercase"
                            >
                                Students in {{ activeSection?.name }}
                            </h4>

                            <span class="text-xs font-bold text-blue-600">
                                {{ assignedList.length }}
                            </span>
                        </div>

                        <input
                            v-model="searchAssigned"
                            type="text"
                            placeholder="Search section students..."
                            class="mb-3 w-full rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs focus:ring-2 focus:ring-blue-500/30 focus:outline-none"
                        />

                        <div
                            class="max-h-[320px] flex-1 overflow-y-auto rounded-xl border border-gray-100 bg-white"
                        >
                            <table class="w-full text-left text-xs">
                                <thead
                                    class="sticky top-0 border-b border-gray-100 bg-gray-50"
                                >
                                    <tr class="text-gray-400">
                                        <th class="px-3 py-2 font-medium">
                                            Student
                                        </th>

                                        <th
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-50">
                                    <tr
                                        v-for="student in filteredAssigned"
                                        :key="student.id"
                                        class="transition hover:bg-gray-50/80"
                                    >
                                        <td
                                            class="px-3 py-2 font-medium text-gray-800"
                                        >
                                            {{ student.first_name }}
                                            {{ student.last_name }}
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <button
                                                type="button"
                                                @click="removeStudent(student)"
                                                class="rounded-lg border border-red-200 px-3 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredAssigned.length === 0">
                                        <td
                                            colspan="2"
                                            class="py-6 text-center text-gray-400"
                                        >
                                            No students added to this section
                                            yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <p
                    v-if="studentForm.errors.student_ids"
                    class="mb-3 text-sm text-red-500"
                >
                    {{ studentForm.errors.student_ids }}
                </p>

                <div
                    class="flex items-center justify-end gap-3 border-t border-gray-100 pt-3"
                >
                    <button
                        type="button"
                        @click="showManageModal = false"
                        class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="saveStudentAssignments"
                        :disabled="studentForm.processing"
                        class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                    >
                        {{
                            studentForm.processing
                                ? 'Saving...'
                                : 'Save Changes'
                        }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
