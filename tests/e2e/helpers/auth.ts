import { randomUUID } from 'node:crypto'
import { request, type Page } from '@playwright/test'
import { promises as fs } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import type { CustomerPersona, E2eManifest } from '../fixtures/manifest'

interface StorageStateOriginEntry {
  name: string
  value: string
}

interface StorageStateFile {
  cookies: unknown[]
  origins: Array<{
    origin: string
    localStorage: StorageStateOriginEntry[]
  }>
}

const HELPERS_ROOT = path.dirname(fileURLToPath(import.meta.url))
const TESTS_ROOT = path.resolve(HELPERS_ROOT, '../..')
const AUTH_ROOT = path.join(TESTS_ROOT, '.auth')

async function ensureAuthRoot(): Promise<void> {
  await fs.mkdir(AUTH_ROOT, { recursive: true })
}

async function writeStorageState(fileName: string, origin: string, entries: StorageStateOriginEntry[]): Promise<string> {
  await ensureAuthRoot()

  const payload: StorageStateFile = {
    cookies: [],
    origins: [{ origin, localStorage: entries }],
  }

  const uniqueFileName = `${path.parse(fileName).name}-${randomUUID()}.json`
  const targetPath = path.join(AUTH_ROOT, uniqueFileName)
  await fs.writeFile(targetPath, JSON.stringify(payload, null, 2))

  return targetPath
}

async function requestToken(apiBaseURL: string, endpoint: string, body: Record<string, string>): Promise<string> {
  const api = await request.newContext({
    baseURL: apiBaseURL,
    extraHTTPHeaders: {
      'content-type': 'application/json',
    },
  })

  try {
    const response = await api.post(endpoint, { data: body })
    if (!response.ok()) {
      throw new Error(`Login failed for ${endpoint}: ${response.status()} ${response.statusText()}`)
    }

    const payload = await response.json() as { token?: string }
    if (!payload.token) {
      throw new Error(`Login for ${endpoint} did not return a token.`)
    }

    return payload.token
  } finally {
    await api.dispose()
  }
}

export async function createCustomerStorageState(
  manifest: E2eManifest,
  persona: CustomerPersona,
  frontendBaseURL: string,
  apiBaseURL: string,
): Promise<string> {
  const customer = manifest.customers[persona]
  const token = await requestToken(apiBaseURL, '/api/customer/login', {
    email: customer.email,
    username: customer.email,
    password: customer.password,
  })

  return writeStorageState(
    `${persona}.json`,
    new URL(frontendBaseURL).origin,
    [
      { name: 'auth:token', value: token },
      { name: 'auth:role', value: 'customer' },
    ],
  )
}

export async function createAdminStorageState(
  manifest: E2eManifest,
  frontendBaseURL: string,
  apiBaseURL: string,
): Promise<string> {
  const token = await requestToken(apiBaseURL, '/api/admin/login', {
    username: manifest.admin.username,
    password: manifest.admin.password,
  })

  return writeStorageState(
    'admin.json',
    new URL(frontendBaseURL).origin,
    [
      { name: 'auth:token', value: token },
      { name: 'auth:role', value: 'admin' },
    ],
  )
}

export async function primePageWithStorageState(page: Page, statePath: string, frontendBaseURL: string): Promise<void> {
  const rawState = await fs.readFile(statePath, 'utf8')
  const state = JSON.parse(rawState) as StorageStateFile
  const origin = new URL(frontendBaseURL).origin
  const localStorageEntries = state.origins.find(entry => entry.origin === origin)?.localStorage ?? []

  await page.addInitScript(({ entries }) => {
    localStorage.clear()
    for (const entry of entries) {
      localStorage.setItem(entry.name, entry.value)
    }
  }, { entries: localStorageEntries })
}
