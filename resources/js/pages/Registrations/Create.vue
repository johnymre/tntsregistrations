<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { onUnmounted, reactive, ref, watchEffect } from 'vue';

const page = usePage();

watchEffect(() => {
    const flash = page.props.flash as { success?: string } | undefined;

    if (flash?.success) {
        // Handle success message
    }
});

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    address: '',
    birthday: '',
    parent_name: '',
    parent_address: '',
    parent_contact_number: '',
    photo: null as File | null,
});

const submitted = reactive({ value: false });
const sameAddress = reactive({ value: false });
const photoPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

// Webcam State & Refs
const showWebcamModal = ref(false);
const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const mediaStream = ref<MediaStream | null>(null);
const isCameraActive = ref(false);

async function openWebcamModal() {
    showWebcamModal.value = true;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 640 },
                facingMode: 'user',
            },
            audio: false,
        });

        mediaStream.value = stream;

        if (videoRef.value) {
            videoRef.value.srcObject = stream;
        }

        isCameraActive.value = true;
    } catch {
        alert(
            'Unable to access camera. Please allow camera permissions in your browser or use file upload instead.',
        );

        closeWebcamModal();
    }
}

function stopWebcam() {
    if (mediaStream.value) {
        mediaStream.value.getTracks().forEach((track) => track.stop());
        mediaStream.value = null;
    }

    isCameraActive.value = false;
}

function closeWebcamModal() {
    stopWebcam();

    showWebcamModal.value = false;
}

function capturePhoto() {
    if (!videoRef.value || !canvasRef.value) {
        return;
    }

    const video = videoRef.value;
    const canvas = canvasRef.value;
    const context = canvas.getContext('2d');
    const size = Math.min(video.videoWidth, video.videoHeight) || 400;

    canvas.width = size;
    canvas.height = size;

    const startX = (video.videoWidth - size) / 2;
    const startY = (video.videoHeight - size) / 2;

    if (context) {
        context.drawImage(video, startX, startY, size, size, 0, 0, size, size);
    }

    canvas.toBlob(
        (blob) => {
            if (!blob) {
                return;
            }

            const file = new File([blob], `webcam-${Date.now()}.jpg`, {
                type: 'image/jpeg',
            });

            form.photo = file;
            form.clearErrors('photo');

            if (photoPreview.value) {
                URL.revokeObjectURL(photoPreview.value);
            }

            photoPreview.value = URL.createObjectURL(blob);

            closeWebcamModal();
        },
        'image/jpeg',
        0.9,
    );
}

function syncParentAddress() {
    if (sameAddress.value) {
        form.parent_address = form.address;
    }
}

function onPhotoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    form.photo = file;
    form.clearErrors('photo');

    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }

    photoPreview.value = file ? URL.createObjectURL(file) : null;
}

function removePhoto() {
    form.photo = null;

    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }

    photoPreview.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function validate() {
    form.clearErrors();

    const required: Record<string, string> = {
        first_name: 'First name is required',
        last_name: 'Last name is required',
        address: 'Address is required',
        birthday: 'Birthday is required',
        parent_name: 'Parent/guardian name is required',
        parent_address: 'Parent/guardian address is required',
        parent_contact_number: 'Parent/guardian contact number is required',
    };

    const clientErrors: Record<string, string> = {};

    for (const [field, message] of Object.entries(required)) {
        if (!String(form[field as keyof typeof form] ?? '').trim()) {
            clientErrors[field] = message;
        }
    }

    if (
        form.parent_contact_number &&
        !/^[0-9+\-\s()]{7,20}$/.test(form.parent_contact_number)
    ) {
        clientErrors.parent_contact_number = 'Enter a valid contact number';
    }

    if (form.birthday) {
        const date = new Date(form.birthday);

        if (Number.isNaN(date.getTime()) || date > new Date()) {
            clientErrors.birthday = 'Enter a valid birthday';
        }
    }

    if (form.photo && !/^image\/(jpeg|png|webp)$/.test(form.photo.type)) {
        clientErrors.photo = 'Photo must be a JPG, PNG, or WEBP file';
    }

    if (form.photo && form.photo.size > 20 * 1024 * 1024) {
        clientErrors.photo = 'Photo must be under 20MB';
    }

    if (Object.keys(clientErrors).length) {
        form.setError(clientErrors);

        return false;
    }

    return true;
}

function submit() {
    if (!validate()) {
        return;
    }

    form.post('/registrations', {
        onSuccess: () => {
            submitted.value = true;
        },
        forceFormData: true,
    });
}

function startNewRegistration() {
    form.first_name = '';
    form.middle_name = '';
    form.last_name = '';
    form.address = '';
    form.birthday = '';
    form.parent_name = '';
    form.parent_address = '';
    form.parent_contact_number = '';
    form.photo = null;
    form.clearErrors();

    removePhoto();

    sameAddress.value = false;
    submitted.value = false;
}

onUnmounted(() => {
    stopWebcam();
});
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-10"
    >
        <div
            class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white shadow-sm"
        >
            <!-- Header -->
            <div
                class="flex items-start gap-4 border-b border-gray-100 px-8 pt-8 pb-6"
            >
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-gray-200"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-600"
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
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">
                        Student Registration
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500">
                        Fill in the student's details and a parent or guardian's
                        information.
                    </p>
                </div>
            </div>

            <form
                v-if="!submitted.value"
                @submit.prevent="submit"
                class="space-y-8 px-8 py-6"
            >
                <!-- Student section -->
                <section>
                    <h2 class="mb-4 text-sm font-semibold text-gray-800">
                        Student information
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >First name</label
                            >
                            <input
                                v-model="form.first_name"
                                autocomplete="off"
                                type="text"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.first_name
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.first_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.first_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                            >
                                Middle name
                                <span class="font-normal text-gray-400"
                                    >(optional)</span
                                >
                            </label>
                            <input
                                v-model="form.middle_name"
                                autocomplete="off"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Last name</label
                            >
                            <input
                                v-model="form.last_name"
                                autocomplete="off"
                                type="text"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.last_name
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.last_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.last_name }}
                            </p>
                        </div>
                    </div>

                    <!-- Photo Section -->
                    <div class="mt-4">
                        <label
                            class="mb-1.5 block text-sm font-medium text-gray-700"
                        >
                            Photo
                            <span class="font-normal text-gray-400"
                                >(optional, JPG/PNG/WEBP, max 20MB)</span
                            >
                        </label>
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-gray-100"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt=""
                                    class="h-full w-full object-cover"
                                />
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-gray-300"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"
                                    />
                                </svg>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <label
                                    class="cursor-pointer rounded-lg border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    {{
                                        photoPreview
                                            ? 'Change file'
                                            : 'Upload photo'
                                    }}
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden"
                                        @change="onPhotoChange"
                                    />
                                </label>
                                <button
                                    type="button"
                                    @click="openWebcamModal"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50/50 px-3.5 py-2 text-sm font-medium text-blue-600 transition hover:bg-blue-100/50"
                                >
                                    <svg
                                        class="h-4 w-4 shrink-0 text-blue-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.75"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"
                                        />
                                    </svg>
                                    <span>Take photo</span>
                                </button>
                                <button
                                    v-if="photoPreview"
                                    type="button"
                                    @click="removePhoto"
                                    class="px-2 text-sm text-gray-500 transition hover:text-red-500"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <p
                            v-if="form.errors.photo"
                            class="mt-1.5 text-xs text-red-500"
                        >
                            {{ form.errors.photo }}
                        </p>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Address</label
                            >
                            <input
                                v-model="form.address"
                                autocomplete="off"
                                @input="syncParentAddress"
                                type="text"
                                placeholder="House no., street, barangay, city"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.address
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.address"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.address }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Birthday</label
                            >
                            <input
                                v-model="form.birthday"
                                type="date"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.birthday
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.birthday"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.birthday }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="border-t border-gray-100"></div>

                <!-- Parent section -->
                <section>
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-800">
                            Parent / guardian information
                        </h2>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-gray-500 select-none"
                        >
                            <input
                                v-model="sameAddress.value"
                                @change="syncParentAddress"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500/40"
                            />
                            Same as student address
                        </label>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Parent / guardian name</label
                            >
                            <input
                                v-model="form.parent_name"
                                autocomplete="off"
                                type="text"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.parent_name
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.parent_name"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.parent_name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Parent / guardian address</label
                            >
                            <input
                                v-model="form.parent_address"
                                autocomplete="off"
                                :disabled="sameAddress.value"
                                type="text"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400"
                                :class="
                                    form.errors.parent_address
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.parent_address"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.parent_address }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-gray-700"
                                >Contact number</label
                            >
                            <input
                                v-model="form.parent_contact_number"
                                autocomplete="off"
                                type="tel"
                                placeholder="09xx xxx xxxx"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none"
                                :class="
                                    form.errors.parent_contact_number
                                        ? 'border-red-400'
                                        : 'border-gray-300'
                                "
                            />
                            <p
                                v-if="form.errors.parent_contact_number"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ form.errors.parent_contact_number }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div
                    class="-mx-8 flex items-center justify-end gap-3 border-t border-gray-100 px-8 pt-2 pt-6"
                >
                    <button
                        type="button"
                        @click="startNewRegistration"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    >
                        Clear
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{
                            form.processing
                                ? 'Submitting...'
                                : 'Submit registration'
                        }}
                    </button>
                </div>
            </form>

            <!-- Success state -->
            <div v-else class="px-8 py-14 text-center">
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 text-blue-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4.5 12.75l6 6 9-13.5"
                        />
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-900">
                    Registration submitted
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    We've received the student's registration details.
                </p>
                <button
                    type="button"
                    @click="startNewRegistration"
                    class="mt-6 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    New registration
                </button>
            </div>
        </div>

        <!-- Webcam Capture Modal -->
        <div
            v-if="showWebcamModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
            @click.self="closeWebcamModal"
        >
            <div
                class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">
                        Take Photo
                    </h3>
                    <button
                        @click="closeWebcamModal"
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
                    class="relative mb-4 aspect-square w-full overflow-hidden rounded-2xl border border-gray-200 bg-black shadow-inner"
                >
                    <video
                        ref="videoRef"
                        autoplay
                        playsinline
                        class="h-full w-full -scale-x-100 object-cover"
                    ></video>
                </div>
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="closeWebcamModal"
                        class="flex-1 rounded-xl border border-gray-200 py-2.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="capturePhoto"
                        class="flex-1 rounded-xl bg-blue-600 py-2.5 text-xs font-semibold text-white shadow transition hover:bg-blue-700"
                    >
                        Capture Photo
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Canvas -->
        <canvas ref="canvasRef" class="hidden"></canvas>
    </div>
</template>
