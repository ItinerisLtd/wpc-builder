import { defineConfig, devices } from '@playwright/test'
import { STORAGE_STATE_PATH } from './tests/E2E/storage-state.js'

export default defineConfig({
  testDir: 'tests/E2E/specs',
  fullyParallel: false,
  // Every fixture field defaults to OptionType::THEME_MOD (src/Config.php),
  // so all four specs' Customizer publishes read-modify-write the same
  // serialised theme_mods row against the one shared wp-env site. Running
  // spec files concurrently risks a lost-update race between them.
  workers: 1,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  reporter: 'list',
  globalSetup: './tests/E2E/global-setup.js',
  use: {
    baseURL: process.env.WP_BASE_URL ?? 'http://localhost:8889',
    storageState: STORAGE_STATE_PATH,
    // 'retain-on-failure' discards the trace entirely for a passed test,
    // so UI mode (which uses this same trace) has nothing to show for a
    // green run. Full traces locally for that debugging experience; CI
    // keeps the smaller failure-only artifact.
    trace: process.env.CI ? 'retain-on-failure' : 'on',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
})
