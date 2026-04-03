import { AuthPage } from '../page-objects/AuthPage'
import { AdminShellPage } from '../page-objects/AdminShellPage'
import { CustomerShellPage } from '../page-objects/CustomerShellPage'
import { expect, test } from '../fixtures/test'

test('redirects unauthenticated users to the login page', async ({ page }) => {
  await page.goto('/customer')
  await expect(page).toHaveURL(/\/login$/)
  await expect(page.getByRole('heading', { name: 'Anmelden' })).toBeVisible()
})

test('logs a customer in through the UI and logs out again', async ({ page, manifest }) => {
  const authPage = new AuthPage(page)
  const customerShell = new CustomerShellPage(page)

  await authPage.loginCustomer(
    manifest.customers.customer_single_dog.email,
    manifest.customers.customer_single_dog.password,
  )

  await expect(page).toHaveURL(/\/customer$/)
  await expect(page.getByText('Hallo, Single E2E!')).toBeVisible()

  await customerShell.logout()
  await expect(page).toHaveURL(/\/login$/)
})

test('logs an admin in through the UI and logs out again', async ({ page, manifest }) => {
  const authPage = new AuthPage(page)
  const adminShell = new AdminShellPage(page)

  await authPage.loginAdmin(manifest.admin.username, manifest.admin.password)

  await expect(page).toHaveURL(/\/admin$/)
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible()

  await adminShell.logout()
  await expect(page).toHaveURL(/\/login$/)
})

test('registers a new customer through the UI', async ({ page }) => {
  const authPage = new AuthPage(page)
  const suffix = Date.now()
  const email = `playwright-${suffix}@example.com`
  const name = `Playwright ${suffix}`

  await authPage.register(name, email, 'playwright-pass')

  await expect(page).toHaveURL(/\/customer$/)
  await expect(page.getByText(`Hallo, ${name}!`)).toBeVisible()
})

test('redirects customer sessions away from the admin area', async ({ page, loginAsCustomer }) => {
  await loginAsCustomer('customer_single_dog')
  await page.goto('/admin')

  await expect(page).toHaveURL(/\/customer$/)
  await expect(page.getByText('Hallo, Single E2E!')).toBeVisible()
})

test.describe('mobile navigation', () => {
  test.use({ viewport: { width: 390, height: 844 } })

  test('opens the customer mobile navigation drawer and navigates to a section', async ({ page, loginAsCustomer }) => {
    const customerShell = new CustomerShellPage(page)

    await loginAsCustomer('customer_single_dog')
    await page.goto('/customer')
    await customerShell.openMobileMenu()

    await page.getByRole('link', { name: 'Meine Hunde' }).click()
    await expect(page).toHaveURL(/\/customer\/dogs$/)
    await expect(page.getByRole('heading', { name: 'Meine Hunde' })).toBeVisible()
  })

  test('opens the admin mobile navigation drawer and navigates to a section', async ({ page, loginAsAdmin }) => {
    const adminShell = new AdminShellPage(page)

    await loginAsAdmin()
    await page.goto('/admin')
    await adminShell.openMobileMenu()

    await page.getByRole('link', { name: 'Kunden' }).click()
    await expect(page).toHaveURL(/\/admin\/customers$/)
    await expect(page.getByRole('heading', { name: 'Kunden' })).toBeVisible()
  })
})
