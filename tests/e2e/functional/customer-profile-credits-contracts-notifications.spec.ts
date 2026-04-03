import { selectMenuOption } from '../helpers/ui'
import { expect, test } from '../fixtures/test'

test('renders and saves the profile form and exercises push registration toggles', async ({
  page,
  loginAsCustomer,
  stubPushApi,
}) => {
  await stubPushApi()
  await loginAsCustomer('customer_profile')
  await page.goto('/customer/profile')

  await expect(page.getByTestId('profile-form-card')).toBeVisible()
  await expect(page.getByLabel('Name')).toHaveValue('Profile E2E')
  await expect(page.getByLabel('IBAN')).toHaveValue('DE89370400440532013000')

  await page.getByLabel('Straße').fill('Playwrightweg 42')
  await page.getByTestId('save-profile').click()
  await expect(page.getByLabel('Straße')).toHaveValue('Playwrightweg 42')

  await page.getByTestId('enable-notifications').click()
  await expect(page.getByText('Push-Mitteilungen sind aktiv')).toBeVisible()
  await expect(page.getByTestId('disable-notifications')).toBeVisible()

  await page.getByTestId('disable-notifications').click()
  await expect(page.getByText('Benachrichtigungen können aktiviert werden')).toBeVisible()
  await expect(page.getByTestId('enable-notifications')).toBeVisible()
})

test('shows balance, next weekly grants, and the transaction history', async ({ page, loginAsCustomer }) => {
  await loginAsCustomer('customer_profile')
  await page.goto('/customer/credits')

  await expect(page.getByRole('heading', { name: 'Guthaben' })).toBeVisible()
  await expect(page.getByText('Nächste Gutschriften')).toBeVisible()
  await expect(page.getByRole('cell', { name: 'Profil-Bonus' })).toBeVisible()
  await expect(page.getByText('Transaktionsverlauf')).toBeVisible()
})

test('validates and submits a new contract request', async ({
  page,
  loginAsCustomer,
  manifest,
}) => {
  await loginAsCustomer('customer_fill_01')
  await page.goto('/customer/contracts')

  await page.getByRole('button', { name: 'Vertrag anfragen' }).click()
  await expect(page.getByTestId('request-contract-modal')).toBeVisible()

  await page.getByRole('button', { name: 'Anfragen' }).click()
  await expect(page.getByText('Bitte einen Hund auswählen.')).toBeVisible()

  await selectMenuOption(page, page.getByTestId('request-contract-dog'), manifest.customers.customer_fill_01.dogNames[0])
  await page.getByLabel('Kurse pro Woche').fill('3')
  await page.getByTestId('request-contract-start-date').fill('2026-05-01')
  await page.getByRole('button', { name: 'Anfragen' }).click()

  await expect(page.getByTestId('request-contract-modal')).toHaveCount(0)
  await expect(page.getByText('Angefragt')).toBeVisible()
})

test('renders pinned, global, and course-scoped notifications', async ({ page, loginAsCustomer, manifest }) => {
  await loginAsCustomer('customer_multi_dog')
  await page.goto('/customer/notifications')

  const pinnedNotification = page.getByTestId(`notification-card-${manifest.notifications.pinnedGlobal}`)
  await expect(pinnedNotification.getByRole('heading', { name: 'Wichtiger Wochenhinweis' })).toBeVisible()
  await expect(pinnedNotification.getByText('Alle Kurse')).toBeVisible()

  const courseNotification = page.getByTestId(`notification-card-${manifest.notifications.courseScoped}`)
  await expect(courseNotification.getByRole('heading', { name: 'Apportieren verlegt' })).toBeVisible()
  await expect(courseNotification.getByText('Apportieren · Montag 12:00')).toBeVisible()
})
