import { expect, test } from '../fixtures/test'

test('admin dashboard page stays visually stable', async ({
  page,
  loginAsAdmin,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto('/admin')
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/dashboard.png', { fullPage: true })
})

test('admin customers list stays visually stable', async ({
  page,
  loginAsAdmin,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto('/admin/customers')
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/customers.png', { fullPage: true })
})

test('admin customer detail stays visually stable', async ({
  page,
  loginAsAdmin,
  manifest,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto(`/admin/customers/${manifest.customers.customer_profile.id}`)
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/customer-detail.png', { fullPage: true })
})

test('admin courses page stays visually stable', async ({
  page,
  loginAsAdmin,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto('/admin/courses')
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/courses.png', { fullPage: true })
})

test('admin calendar page stays visually stable', async ({
  page,
  loginAsAdmin,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto('/admin/calendar')
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/calendar.png', { fullPage: true })
})

test('admin notifications page stays visually stable', async ({
  page,
  loginAsAdmin,
  waitForVisualReady,
}) => {
  await loginAsAdmin()
  await page.goto('/admin/notifications')
  await waitForVisualReady()

  await expect(page).toHaveScreenshot('admin/notifications.png', { fullPage: true })
})
