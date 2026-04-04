import { selectMenuOption, visibleByTestId } from '../helpers/ui'
import { expect, test } from '../fixtures/test'

test('creates global and scoped notifications and can edit and delete seeded entries', async ({
  page,
  loginAsAdmin,
  manifest,
}) => {
  const createdSuffix = Date.now().toString().slice(-6)

  await loginAsAdmin()
  await page.goto('/admin/notifications')

  await page.getByRole('button', { name: 'Neue Mitteilung' }).click()
  await expect(page.getByTestId('notification-form-modal')).toBeVisible()
  await page.getByTestId('notification-global-toggle').click()
  await page.getByTestId('notification-title').fill(`Playwright global ${createdSuffix}`)
  await page.getByTestId('notification-message').fill('Globale Test-Mitteilung fuer die E2E-Suite.')
  await page.getByTestId('notification-pinned-until').fill(manifest.week.nextMonday)
  const [createGlobalResponse] = await Promise.all([
    page.waitForResponse(response =>
      response.url().includes('/api/admin/notifications')
      && response.request().method() === 'POST'
      && response.status() === 201,
    ),
    page.getByTestId('save-notification').click(),
  ])
  const createdGlobalNotification = await createGlobalResponse.json() as { id: string }
  await expect(visibleByTestId(page, `notification-row-${createdGlobalNotification.id}`)).toContainText(`Playwright global ${createdSuffix}`)

  await page.getByRole('button', { name: 'Neue Mitteilung' }).click()
  await selectMenuOption(page, page.getByTestId('notification-course-select'), 'Apportieren · Montag 12:00')
  await page.getByTestId('notification-title').fill(`Playwright scoped ${createdSuffix}`)
  await page.getByTestId('notification-message').fill('Kursgebundene Test-Mitteilung.')
  const [createScopedResponse] = await Promise.all([
    page.waitForResponse(response =>
      response.url().includes('/api/admin/notifications')
      && response.request().method() === 'POST'
      && response.status() === 201,
    ),
    page.getByTestId('save-notification').click(),
  ])
  const createdScopedNotification = await createScopedResponse.json() as { id: string }
  await expect(visibleByTestId(page, `notification-row-${createdScopedNotification.id}`)).toContainText(`Playwright scoped ${createdSuffix}`)

  await page.getByTestId(`edit-notification-${manifest.notifications.edit}`).click()
  await page.getByTestId('notification-title').fill('Bearbeitete Mitteilung')
  await page.getByTestId('save-notification').click()
  await expect(visibleByTestId(page, `notification-row-${manifest.notifications.edit}`)).toContainText('Bearbeitete Mitteilung')

  await page.getByTestId(`delete-notification-${manifest.notifications.delete}`).click()
  await expect(page.getByTestId(`notification-row-${manifest.notifications.delete}`)).toHaveCount(0)
  await expect(page.getByTestId(`notification-card-${manifest.notifications.delete}`)).toHaveCount(0)
})
