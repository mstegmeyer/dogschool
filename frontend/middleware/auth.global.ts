export default defineNuxtRouteMiddleware((to) => {
  const { isAuthenticated, isAdmin, isCustomer, hydrate } = useAuth()

  if (!isAuthenticated.value) {
    hydrate()
  }

  const publicPaths = ['/login', '/register']

  if (publicPaths.includes(to.path)) {
    if (isAuthenticated.value) {
      return navigateTo(isAdmin.value ? '/admin' : '/customer')
    }
    return
  }

  if (to.path === '/') {
    if (isAuthenticated.value) {
      return navigateTo(isAdmin.value ? '/admin' : '/customer')
    }
    return navigateTo('/login')
  }

  if (!isAuthenticated.value) {
    return navigateTo('/login')
  }

  if (to.path.startsWith('/admin') && !isAdmin.value) {
    return navigateTo('/customer')
  }

  if (to.path.startsWith('/customer') && !isCustomer.value) {
    return navigateTo('/admin')
  }
})
