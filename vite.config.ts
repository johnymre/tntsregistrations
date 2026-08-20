import { wayfinder } from '@laravel/vite-plugin-wayfinder'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { defineConfig } from 'vite'

export default defineConfig(({ command }) => ({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
    }),
    vue(),
    // Only run wayfinder during development (vite serve)
    command === 'serve' ? wayfinder() : null,
  ].filter(Boolean),
}))
