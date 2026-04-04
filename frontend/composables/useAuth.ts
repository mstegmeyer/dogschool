import type { AuthRole, Customer } from '~/types'

export const useAuth = () => {
  const token = useState<string | null>('auth:token', () => null)
  const role = useState<AuthRole | null>('auth:role', () => null)
  const user = useState<Customer | null>('auth:user', () => null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => role.value === 'admin')
  const isCustomer = computed(() => role.value === 'customer')

  function persist() {
    if (import.meta.client) {
      if (token.value) {
        localStorage.setItem('auth:token', token.value)
        localStorage.setItem('auth:role', role.value || '')
      } else {
        localStorage.removeItem('auth:token')
        localStorage.removeItem('auth:role')
      }
    }
  }

  function hydrate() {
    if (import.meta.client) {
      const savedToken = localStorage.getItem('auth:token')
      const savedRole = localStorage.getItem('auth:role')
      if (savedToken && savedRole) {
        token.value = savedToken
        role.value = savedRole as AuthRole
      }
    }
  }

  async function loginAdmin(username: string, password: string) {
    const data = await $fetch<{ token: string }>('/api/admin/login', {
      method: 'POST',
      body: { username, password },
    })
    token.value = data.token
    role.value = 'admin'
    persist()
  }

  async function loginCustomer(email: string, password: string) {
    const data = await $fetch<{ token: string }>('/api/customer/login', {
      method: 'POST',
      body: { email, username: email, password },
    })
    token.value = data.token
    role.value = 'customer'
    persist()
    await fetchProfile()
  }

  async function register(payload: { email: string; password: string; name?: string }) {
    await $fetch('/api/customer/register', {
      method: 'POST',
      body: payload,
    })
    await loginCustomer(payload.email, payload.password)
  }

  async function fetchProfile() {
    if (!token.value || role.value !== 'customer') return
    try {
      user.value = await $fetch<Customer>('/api/customer/me', {
        headers: { Authorization: `Bearer ${token.value}` },
      })
    } catch {
      // profile fetch failed – token may be expired
    }
  }

  function logout() {
    token.value = null
    role.value = null
    user.value = null
    persist()
  }

  return {
    token, role, user,
    isAuthenticated, isAdmin, isCustomer,
    loginAdmin, loginCustomer, register, fetchProfile,
    logout, hydrate,
  }
}
