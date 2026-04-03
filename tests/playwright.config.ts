import { defineConfig, devices } from '@playwright/test'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const TESTS_ROOT = path.dirname(fileURLToPath(import.meta.url))
const REPO_ROOT = path.resolve(TESTS_ROOT, '..')
const FRONTEND_ROOT = path.join(REPO_ROOT, 'frontend')
const BACKEND_ROOT = path.join(REPO_ROOT, 'backend')
const FRONTEND_BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:4174'
const API_BASE_URL = process.env.PLAYWRIGHT_API_BASE_URL || 'http://127.0.0.1:9080'
const WEB_PUSH_VAPID_PUBLIC_KEY = process.env.NUXT_PUBLIC_WEB_PUSH_VAPID_PUBLIC_KEY || 'BEl6XPSEi2R7gmJPMfeRr0pA6Bv9CzM0_lV4rWuieKTPV7RaH59RUW2KIiMzFxLpy0X58F3RDeo63HgNbUsVTN8'
const E2E_DATABASE_URL = `sqlite:////${path.join(BACKEND_ROOT, 'var', 'data_e2e.db').replace(/\\/g, '/')}`
const E2E_JWT_PRIVATE_KEY = path.join(BACKEND_ROOT, 'var', 'jwt_e2e', 'private.pem')
const E2E_JWT_PUBLIC_KEY = path.join(BACKEND_ROOT, 'var', 'jwt_e2e', 'public.pem')
const E2E_FIXED_NOW = process.env.APP_FIXED_NOW || '2026-04-06T09:00:00+02:00'

function shellQuote(value: string): string {
  return `'${value.replace(/'/g, `'\\''`)}'`
}

const backendEnv = [
  'APP_ENV=e2e',
  'APP_DEBUG=0',
  `APP_SECRET=${shellQuote('e2e-secret')}`,
  `DATABASE_URL=${shellQuote(E2E_DATABASE_URL)}`,
  `JWT_SECRET_KEY=${shellQuote(E2E_JWT_PRIVATE_KEY)}`,
  `JWT_PUBLIC_KEY=${shellQuote(E2E_JWT_PUBLIC_KEY)}`,
  `JWT_PASSPHRASE=${shellQuote('change-me')}`,
  `APP_FIXED_NOW=${shellQuote(E2E_FIXED_NOW)}`,
].join(' ')

export default defineConfig({
  testDir: path.join(TESTS_ROOT, 'e2e'),
  outputDir: path.join(TESTS_ROOT, 'test-results'),
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI
    ? [['github'], ['html', { open: 'never', outputFolder: path.join(TESTS_ROOT, 'playwright-report') }]]
    : [['list'], ['html', { open: 'never', outputFolder: path.join(TESTS_ROOT, 'playwright-report') }]],
  snapshotPathTemplate: path.join(TESTS_ROOT, 'snapshots', '{projectName}', '{arg}{ext}'),
  use: {
    ...devices['Desktop Chrome'],
    baseURL: FRONTEND_BASE_URL,
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
    colorScheme: 'light',
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'functional',
      testMatch: /functional\/.*\.spec\.ts$/,
      use: {
        viewport: { width: 1440, height: 1024 },
      },
    },
    {
      name: 'visual',
      testMatch: /visual\/.*\.spec\.ts$/,
      use: {
        viewport: { width: 1440, height: 1024 },
        launchOptions: {
          args: ['--font-render-hinting=medium'],
        },
      },
    },
  ],
  webServer: [
    {
      command: `${backendEnv} php bin/console app:e2e:reset && ${backendEnv} php -S 127.0.0.1:9080 -t public public/e2e-router.php`,
      cwd: BACKEND_ROOT,
      url: `${API_BASE_URL}/api/customer/me`,
      reuseExistingServer: !process.env.CI,
      timeout: 180_000,
    },
    {
      command: [
        'NUXT_TELEMETRY_DISABLED=1',
        `API_PROXY_TARGET=${API_BASE_URL}`,
        `NUXT_PUBLIC_API_BASE_URL=${API_BASE_URL}`,
        `NUXT_PUBLIC_WEB_PUSH_VAPID_PUBLIC_KEY=${WEB_PUSH_VAPID_PUBLIC_KEY}`,
        'npm run build',
        '&&',
        'NUXT_TELEMETRY_DISABLED=1',
        `API_PROXY_TARGET=${API_BASE_URL}`,
        `NUXT_PUBLIC_API_BASE_URL=${API_BASE_URL}`,
        `NUXT_PUBLIC_WEB_PUSH_VAPID_PUBLIC_KEY=${WEB_PUSH_VAPID_PUBLIC_KEY}`,
        'npm run preview -- --host=127.0.0.1 --port=4174',
      ].join(' '),
      cwd: FRONTEND_ROOT,
      url: `${FRONTEND_BASE_URL}/login`,
      reuseExistingServer: !process.env.CI,
      timeout: 240_000,
    },
  ],
})
