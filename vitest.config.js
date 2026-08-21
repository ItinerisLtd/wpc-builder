import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    include: ['assets/src/js/**/*.test.js'],
    setupFiles: ['./vitest.setup.js'],
  },
})
