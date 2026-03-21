import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref, computed as vueComputed, type Ref } from 'vue'

const fetchMock = vi.fn()
const navigateToMock = vi.fn()

const store: Record<string, string> = {}
const mockLocalStorage = {
  getItem: (key: string) => store[key] ?? null,
  setItem: (key: string, value: string) => { store[key] = value },
  removeItem: (key: string) => { delete store[key] },
  clear: () => { Object.keys(store).forEach(k => delete store[k]) },
}

const stateStore: Record<string, Ref> = {}

vi.stubGlobal('localStorage', mockLocalStorage)
vi.stubGlobal('useState', (key: string, init?: () => unknown) => {
  if (!stateStore[key]) {
    stateStore[key] = ref(init?.() ?? null)
  }
  return stateStore[key]
})
vi.stubGlobal('computed', vueComputed)
vi.stubGlobal('navigateTo', navigateToMock)
vi.stubGlobal('$fetch', fetchMock)

Object.defineProperty(import.meta, 'client', { value: true, writable: true })

import { useAuth } from '../../composables/useAuth'

describe('useAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockLocalStorage.clear()
    Object.keys(stateStore).forEach(k => { stateStore[k].value = null })
  })

  it('starts unauthenticated', () => {
    const { isAuthenticated, isAdmin, isCustomer, token, role } = useAuth()
    expect(isAuthenticated.value).toBe(false)
    expect(isAdmin.value).toBe(false)
    expect(isCustomer.value).toBe(false)
    expect(token.value).toBeNull()
    expect(role.value).toBeNull()
  })

  it('loginAdmin sets token and admin role', async () => {
    fetchMock.mockResolvedValueOnce({ token: 'admin-jwt-token' })

    const { loginAdmin, token, role, isAdmin, isAuthenticated } = useAuth()
    await loginAdmin('admin', 'password')

    expect(fetchMock).toHaveBeenCalledWith('/api/admin/login', {
      method: 'POST',
      body: { username: 'admin', password: 'password' },
    })
    expect(token.value).toBe('admin-jwt-token')
    expect(role.value).toBe('admin')
    expect(isAdmin.value).toBe(true)
    expect(isAuthenticated.value).toBe(true)
  })

  it('loginCustomer sets token, customer role, and fetches profile', async () => {
    fetchMock
      .mockResolvedValueOnce({ token: 'customer-jwt' })
      .mockResolvedValueOnce({ id: '1', name: 'Max', email: 'max@example.com' })

    const { loginCustomer, token, role, isCustomer, user } = useAuth()
    await loginCustomer('max@example.com', 'secret')

    expect(token.value).toBe('customer-jwt')
    expect(role.value).toBe('customer')
    expect(isCustomer.value).toBe(true)
    expect(user.value).toEqual({ id: '1', name: 'Max', email: 'max@example.com' })
  })

  it('logout clears all auth state', async () => {
    fetchMock.mockResolvedValueOnce({ token: 'jwt' })

    const { loginAdmin, logout, token, role, isAuthenticated } = useAuth()
    await loginAdmin('admin', 'pass')
    expect(isAuthenticated.value).toBe(true)

    logout()
    expect(token.value).toBeNull()
    expect(role.value).toBeNull()
    expect(isAuthenticated.value).toBe(false)
  })

  it('persist stores auth in localStorage', async () => {
    fetchMock.mockResolvedValueOnce({ token: 'persist-token' })

    const { loginAdmin } = useAuth()
    await loginAdmin('admin', 'pass')

    expect(mockLocalStorage.getItem('auth:token')).toBe('persist-token')
    expect(mockLocalStorage.getItem('auth:role')).toBe('admin')
  })

  it('logout clears localStorage', async () => {
    fetchMock.mockResolvedValueOnce({ token: 'temp' })

    const { loginAdmin, logout } = useAuth()
    await loginAdmin('admin', 'pass')
    logout()

    expect(mockLocalStorage.getItem('auth:token')).toBeNull()
    expect(mockLocalStorage.getItem('auth:role')).toBeNull()
  })

  it('hydrate restores auth from localStorage', () => {
    mockLocalStorage.setItem('auth:token', 'saved-token')
    mockLocalStorage.setItem('auth:role', 'customer')

    const { hydrate, token, role } = useAuth()
    hydrate()

    expect(token.value).toBe('saved-token')
    expect(role.value).toBe('customer')
  })

  it('register calls register endpoint and then logs in', async () => {
    fetchMock
      .mockResolvedValueOnce({})
      .mockResolvedValueOnce({ token: 'new-user-jwt' })
      .mockResolvedValueOnce({ id: '2', name: 'New', email: 'new@example.com' })

    const { register, token, isCustomer } = useAuth()
    await register({ email: 'new@example.com', password: 'pass', name: 'New' })

    expect(fetchMock).toHaveBeenCalledWith('/api/customer/register', {
      method: 'POST',
      body: { email: 'new@example.com', password: 'pass', name: 'New' },
    })
    expect(token.value).toBe('new-user-jwt')
    expect(isCustomer.value).toBe(true)
  })

  it('fetchProfile handles errors gracefully', async () => {
    fetchMock
      .mockResolvedValueOnce({ token: 'jwt' })
      .mockRejectedValueOnce(new Error('Network error'))

    const { loginCustomer, user } = useAuth()
    await loginCustomer('c@example.com', 'pass')

    expect(user.value).toBeNull()
  })

  it('fetchProfile does nothing without token', async () => {
    const { fetchProfile } = useAuth()
    await fetchProfile()
    expect(fetchMock).not.toHaveBeenCalled()
  })
})
