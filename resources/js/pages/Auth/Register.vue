<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)

function submit(): void {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div class="min-h-screen bg-gray-200 flex items-center justify-center p-6">
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-xl overflow-hidden grid md:grid-cols-2">
      <!-- Left: illustration panel -->
      <div class="hidden md:flex relative bg-blue-600 items-center justify-center overflow-hidden px-10 py-12">
        <div class="absolute w-[420px] h-[420px] rounded-full bg-blue-500/40"></div>
        <div class="absolute w-[300px] h-[300px] rounded-full bg-blue-400/30"></div>
        <div class="relative w-full max-w-sm">
          <svg class="absolute -right-2 top-1/2 -translate-y-1/2 w-24 h-40 text-blue-300/50" viewBox="0 0 100 160" fill="none">
            <path d="M90 20 H50 V140 H90" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <path d="M50 80 H10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          </svg>
          <div class="absolute -right-2 top-2 w-11 h-11 rounded-full bg-white shadow-lg flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#36C5F0" d="M9.5 15.5a2.5 2.5 0 11-2.5-2.5h2.5v2.5z"/><path fill="#2EB67D" d="M10.5 15.5a2.5 2.5 0 015 0v6.5a2.5 2.5 0 01-5 0v-6.5z"/><path fill="#ECB22E" d="M14.5 8.5a2.5 2.5 0 112.5 2.5h-2.5V8.5z"/><path fill="#E01E5A" d="M13.5 8.5a2.5 2.5 0 01-5 0V2a2.5 2.5 0 015 0v6.5z"/></svg>
          </div>
          <div class="absolute -right-6 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-white shadow-lg flex items-center justify-center">
            <div class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>
            </div>
          </div>
          <div class="absolute -right-2 bottom-2 w-11 h-11 rounded-full bg-white shadow-lg flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 18 18">
              <path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84a4.14 4.14 0 0 1-1.8 2.72v2.26h2.91c1.7-1.57 2.69-3.88 2.69-6.62z"/>
              <path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.91-2.26c-.81.54-1.84.86-3.05.86-2.34 0-4.33-1.58-5.04-3.71H.96v2.33A9 9 0 0 0 9 18z"/>
              <path fill="#FBBC05" d="M3.96 10.71a5.4 5.4 0 0 1 0-3.42V4.96H.96a9 9 0 0 0 0 8.08l3-2.33z"/>
              <path fill="#EA4335" d="M9 3.58c1.32 0 2.51.46 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0A9 9 0 0 0 .96 4.96l3 2.33C4.67 5.16 6.66 3.58 9 3.58z"/>
            </svg>
          </div>
          <div class="relative mr-16 ml-auto bg-white rounded-2xl shadow-2xl p-3 w-64">
            <div class="flex gap-1.5 mb-3">
              <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
              <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
            </div>
            <div class="h-2 w-2/3 bg-gray-200 rounded mb-1"></div>
            <div class="h-2 w-1/2 bg-gray-100 rounded mb-3"></div>
            <div v-for="i in 3" :key="i" class="flex items-center gap-2 py-1.5">
              <div class="w-6 h-6 rounded-full bg-gray-200 shrink-0"></div>
              <div class="h-2 flex-1 bg-gray-100 rounded" :style="{ maxWidth: i === 2 ? '70%' : '90%' }"></div>
            </div>
          </div>
        </div>
        <div class="absolute bottom-12 inset-x-10 text-center">
          <h2 class="text-white text-xl font-bold mb-1">Connect with every application.</h2>
          <p class="text-blue-100 text-sm mb-5">Everything you need in an easily customizable dashboard.</p>
          <div class="flex items-center justify-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-white"></span>
            <span class="w-2 h-2 rounded-full bg-blue-300"></span>
            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
          </div>
        </div>
      </div>

      <!-- Right: form -->
      <div class="px-10 py-12 sm:px-14 flex flex-col justify-center">
        <!-- Logo -->
        <div class="flex items-center gap-2 mb-8">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 1L4 14L14 27L24 14L14 1Z" fill="#3B82F6"/>
            <path d="M14 1L24 14L14 27" fill="#F59E0B"/>
          </svg>
          <span class="text-xl font-bold text-gray-900">NovaSyncer</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Create an Account</h1>
        <p class="text-sm text-gray-500 mb-6">Get started for free. Select method to sign up:</p>

        <form @submit.prevent="submit" class="space-y-3">
          <!-- Name -->
          <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <input
              v-model="form.name"
              type="text"
              placeholder="Full name"
              class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
            />
          </div>
          <p v-if="form.errors.name" class="text-xs text-red-500 -mt-2">{{ form.errors.name }}</p>

          <!-- Email -->
          <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            <input
              v-model="form.email"
              type="email"
              placeholder="Email"
              class="w-full rounded-xl border border-gray-200 pl-10 pr-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
            />
          </div>
          <p v-if="form.errors.email" class="text-xs text-red-500 -mt-2">{{ form.errors.email }}</p>

          <!-- Password -->
          <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Password"
              class="w-full rounded-xl border border-gray-200 pl-10 pr-10 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <svg v-if="showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
          <p v-if="form.errors.password" class="text-xs text-red-500 -mt-2">{{ form.errors.password }}</p>

          <!-- Confirm Password -->
          <div class="relative">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <input
              v-model="form.password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              placeholder="Confirm password"
              class="w-full rounded-xl border border-gray-200 pl-10 pr-10 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500"
            />
            <button
              type="button"
              @click="showConfirmPassword = !showConfirmPassword"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              <svg v-if="showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </button>
          </div>
          <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 -mt-2">{{ form.errors.password_confirmation }}</p>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full mt-2 py-3 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition"
          >
            {{ form.processing ? 'Creating account...' : 'Create account' }}
          </button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-6">
          Already have an account?
          <a href="/login" class="text-blue-600 font-medium hover:text-blue-700">Log in</a>
        </p>
      </div>
    </div>
  </div>
</template>
