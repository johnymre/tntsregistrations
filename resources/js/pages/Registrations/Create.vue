<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { reactive, ref, onUnmounted } from 'vue'
import { watchEffect } from 'vue'

const page = usePage()
watchEffect(() => {
  if (page.props.flash?.success) {
    // Handle success message
  }
})

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  address: '',
  birthday: '',
  parent_name: '',
  parent_address: '',
  parent_contact_number: '',
  photo: null,
})

const submitted = reactive({ value: false })
const sameAddress = reactive({ value: false })
const photoPreview = ref(null)
const fileInput = ref(null)

// Webcam State & Refs
const showWebcamModal = ref(false)
const videoRef = ref(null)
const canvasRef = ref(null)
const mediaStream = ref(null)
const isCameraActive = ref(false)

async function openWebcamModal() {
  showWebcamModal.value = true

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { width: { ideal: 640 }, height: { ideal: 640 }, facingMode: 'user' },
      audio: false,
    })
    mediaStream.value = stream

    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }

    isCameraActive.value = true
  } catch (err) {
    alert('Unable to access camera. Please allow camera permissions in your browser or use file upload instead.')
    closeWebcamModal()
  }
}

function stopWebcam() {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach((track) => track.stop())
    mediaStream.value = null
  }

  isCameraActive.value = false
}

function closeWebcamModal() {
  stopWebcam()
  showWebcamModal.value = false
}

function capturePhoto() {
  if (!videoRef.value || !canvasRef.value) {
return
}

  const video = videoRef.value
  const canvas = canvasRef.value
  const context = canvas.getContext('2d')

  const size = Math.min(video.videoWidth, video.videoHeight) || 400
  canvas.width = size
  canvas.height = size

  const startX = (video.videoWidth - size) / 2
  const startY = (video.videoHeight - size) / 2
  context.drawImage(video, startX, startY, size, size, 0, 0, size, size)

  canvas.toBlob((blob) => {
    if (!blob) {
return
}

    const file = new File([blob], `webcam-${Date.now()}.jpg`, { type: 'image/jpeg' })
    
    form.photo = file
    form.clearErrors('photo')

    if (photoPreview.value) {
URL.revokeObjectURL(photoPreview.value)
}

    photoPreview.value = URL.createObjectURL(blob)

    closeWebcamModal()
  }, 'image/jpeg', 0.9)
}

function syncParentAddress() {
  if (sameAddress.value) {
form.parent_address = form.address
}
}

function onPhotoChange(event) {
  const file = event.target.files?.[0] ?? null
  form.photo = file
  form.clearErrors('photo')

  if (photoPreview.value) {
URL.revokeObjectURL(photoPreview.value)
}

  photoPreview.value = file ? URL.createObjectURL(file) : null
}

function removePhoto() {
  form.photo = null

  if (photoPreview.value) {
URL.revokeObjectURL(photoPreview.value)
}

  photoPreview.value = null

  if (fileInput.value) {
fileInput.value.value = ''
}
}

function validate() {
  form.clearErrors()

  const required = {
    first_name: 'First name is required',
    last_name: 'Last name is required',
    address: 'Address is required',
    birthday: 'Birthday is required',
    parent_name: "Parent/guardian name is required",
    parent_address: "Parent/guardian address is required",
    parent_contact_number: "Parent/guardian contact number is required",
  }

  const clientErrors = {}

  for (const [field, message] of Object.entries(required)) {
    if (!String(form[field] ?? '').trim()) {
clientErrors[field] = message
}
  }

  if (form.parent_contact_number && !/^[0-9+\-\s()]{7,20}$/.test(form.parent_contact_number)) {
    clientErrors.parent_contact_number = 'Enter a valid contact number'
  }

  if (form.birthday) {
    const date = new Date(form.birthday)

    if (Number.isNaN(date.getTime()) || date > new Date()) {
      clientErrors.birthday = 'Enter a valid birthday'
    }
  }

  if (form.photo && !/^image\/(jpeg|png|webp)$/.test(form.photo.type)) {
    clientErrors.photo = 'Photo must be a JPG, PNG, or WEBP file'
  }

  if (form.photo && form.photo.size > 20 * 1024 * 1024) {
    clientErrors.photo = 'Photo must be under 20MB'
  }

  if (Object.keys(clientErrors).length) {
    form.setError(clientErrors)

    return false
  }

  return true
}

function submit() {
  if (!validate()) {
return
}

  form.post('/registrations', {
    onSuccess: () => {
      submitted.value = true
    },
    forceFormData: true,
  })
}

function startNewRegistration() {
  form.first_name = ''
  form.middle_name = ''
  form.last_name = ''
  form.address = ''
  form.birthday = ''
  form.parent_name = ''
  form.parent_address = ''
  form.parent_contact_number = ''
  form.photo = null
  form.clearErrors()
  removePhoto()
  sameAddress.value = false
  submitted.value = false
}

onUnmounted(() => {
  stopWebcam()
})
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-sm border border-gray-200">
      <!-- Header -->
      <div class="flex items-start gap-4 px-8 pt-8 pb-6 border-b border-gray-100">
        <div class="w-11 h-11 rounded-full border border-gray-200 flex items-center justify-center shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
          </svg>
        </div>
        <div>
          <h1 class="text-lg font-semibold text-gray-900">Student Registration</h1>
          <p class="text-sm text-gray-500 mt-0.5">Fill in the student's details and a parent or guardian's information.</p>
        </div>
      </div>

      <form v-if="!submitted.value" @submit.prevent="submit" class="px-8 py-6 space-y-8">
        <!-- Student section -->
        <section>
          <h2 class="text-sm font-semibold text-gray-800 mb-4">Student information</h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">First name</label>
              <input
                v-model="form.first_name"
                autocomplete="off"
                type="text"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.first_name ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Middle name <span class="text-gray-400 font-normal">(optional)</span>
              </label>
              <input
                v-model="form.middle_name"
                autocomplete="off"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Last name</label>
              <input
                v-model="form.last_name"
                autocomplete="off"
                type="text"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.last_name ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
            </div>
          </div>

          <!-- Photo Section -->
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Photo <span class="text-gray-400 font-normal">(optional, JPG/PNG/WEBP, max 20MB)</span>
            </label>
            <div class="flex items-center gap-4">
              <div class="w-16 h-16 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center shrink-0">
                <img v-if="photoPreview" :src="photoPreview" alt="" class="w-full h-full object-cover" />
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                </svg>
              </div>

              <div class="flex flex-wrap items-center gap-2">
                <label class="cursor-pointer px-3.5 py-2 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 transition">
                  {{ photoPreview ? 'Change file' : 'Upload photo' }}
                  <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPhotoChange" />
                </label>

                <button
                  type="button"
                  @click="openWebcamModal"
                  class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium text-blue-600 border border-blue-200 bg-blue-50/50 hover:bg-blue-100/50 transition"
                >
                  <svg class="w-4 h-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                  </svg>
                  <span>Take photo</span>
                </button>


                <button v-if="photoPreview" type="button" @click="removePhoto" class="text-sm text-gray-500 hover:text-red-500 transition px-2">
                  Remove
                </button>
              </div>
            </div>
            <p v-if="form.errors.photo" class="text-xs text-red-500 mt-1.5">{{ form.errors.photo }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
              <input
                v-model="form.address"
                autocomplete="off"
                @input="syncParentAddress"
                type="text"
                placeholder="House no., street, barangay, city"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.address ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.address" class="text-xs text-red-500 mt-1">{{ form.errors.address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Birthday</label>
              <input
                v-model="form.birthday"
                type="date"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.birthday ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.birthday" class="text-xs text-red-500 mt-1">{{ form.errors.birthday }}</p>
            </div>
          </div>
        </section>

        <div class="border-t border-gray-100"></div>

        <!-- Parent section -->
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-800">Parent / guardian information</h2>
            <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer select-none">
              <input
                v-model="sameAddress.value"
                @change="syncParentAddress"
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500/40"
              />
              Same as student address
            </label>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Parent / guardian name</label>
              <input
                v-model="form.parent_name"
                autocomplete="off"
                type="text"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.parent_name ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.parent_name" class="text-xs text-red-500 mt-1">{{ form.errors.parent_name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Parent / guardian address</label>
              <input
                v-model="form.parent_address"
                autocomplete="off"
                :disabled="sameAddress.value"
                type="text"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-400"
                :class="form.errors.parent_address ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.parent_address" class="text-xs text-red-500 mt-1">{{ form.errors.parent_address }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact number</label>
              <input
                v-model="form.parent_contact_number"
                autocomplete="off"
                type="tel"
                placeholder="09xx xxx xxxx"
                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
                :class="form.errors.parent_contact_number ? 'border-red-400' : 'border-gray-300'"
              />
              <p v-if="form.errors.parent_contact_number" class="text-xs text-red-500 mt-1">{{ form.errors.parent_contact_number }}</p>
            </div>
          </div>
        </section>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100 -mx-8 px-8 pt-6">
          <button
            type="button"
            @click="startNewRegistration"
            class="px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50 transition"
          >
            Clear
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition"
          >
            {{ form.processing ? 'Submitting…' : 'Submit registration' }}
          </button>
        </div>
      </form>

      <!-- Success state -->
      <div v-else class="px-8 py-14 text-center">
        <div class="w-12 h-12 mx-auto rounded-full bg-blue-50 flex items-center justify-center mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>
        <h2 class="text-base font-semibold text-gray-900">Registration submitted</h2>
        <p class="text-sm text-gray-500 mt-1">We've received the student's registration details.</p>
        <button
          type="button"
          @click="startNewRegistration"
          class="mt-6 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition"
        >
          New registration
        </button>
      </div>
    </div>

    <!-- Webcam Capture Modal -->
    <div
      v-if="showWebcamModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4"
      @click.self="closeWebcamModal"
    >
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-900">Take Photo</h3>
          <button @click="closeWebcamModal" class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-black border border-gray-200 shadow-inner mb-4">
          <video ref="videoRef" autoplay playsinline class="w-full h-full object-cover -scale-x-100"></video>
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="closeWebcamModal"
            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-gray-700 border border-gray-200 hover:bg-gray-50 transition"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="capturePhoto"
            class="flex-1 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition shadow"
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
