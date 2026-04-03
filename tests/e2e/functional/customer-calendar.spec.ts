import { CustomerCalendarPage } from '../page-objects/CustomerCalendarPage'
import { expect, test } from '../fixtures/test'

test('keeps the single-dog booking action inside the weekly card bounds', async ({
  page,
  loginAsCustomer,
  manifest,
}) => {
  const calendarPage = new CustomerCalendarPage(page)
  const courseDateId = manifest.courseDates.customer_single_course.current

  await loginAsCustomer('customer_single_dog')
  await calendarPage.goto()

  const card = calendarPage.card(courseDateId)
  const trigger = calendarPage.bookingTrigger(courseDateId)

  await expect(card).toBeVisible()
  await expect(trigger).toBeVisible()
  await expect(trigger).toHaveText('Buchen')

  const cardBox = await card.boundingBox()
  const triggerBox = await trigger.boundingBox()

  expect(cardBox).not.toBeNull()
  expect(triggerBox).not.toBeNull()
  expect(triggerBox!.x).toBeGreaterThanOrEqual(cardBox!.x)
  expect(triggerBox!.x + triggerBox!.width).toBeLessThanOrEqual(cardBox!.x + cardBox!.width + 1)
  expect(triggerBox!.y + triggerBox!.height).toBeLessThanOrEqual(cardBox!.y + cardBox!.height + 1)
})

test('books a multi-dog course through the modal flow', async ({
  page,
  loginAsCustomer,
  manifest,
}) => {
  const calendarPage = new CustomerCalendarPage(page)
  const courseDateId = manifest.courseDates.customer_multi_course.current

  await loginAsCustomer('customer_calendar_multi')
  await calendarPage.goto()
  await calendarPage.openBooking(courseDateId)

  await page.getByTestId('booking-dog-select').selectOption({ label: 'Balu' })
  await page.getByTestId('confirm-booking').click()

  await expect(page.getByTestId('booking-modal')).not.toBeVisible()
  await expect(calendarPage.card(courseDateId)).toContainText('Gebucht')
  await expect(calendarPage.card(courseDateId)).toContainText('Balu')
})

test('cancels an existing booking from the calendar card', async ({
  page,
  loginAsCustomer,
  manifest,
}) => {
  const calendarPage = new CustomerCalendarPage(page)
  const courseDateId = manifest.courseDates.customer_booked_course.current

  await loginAsCustomer('customer_calendar_booked')
  await calendarPage.goto()

  await expect(calendarPage.card(courseDateId)).toContainText('Gebucht')
  await calendarPage.card(courseDateId).getByRole('button', { name: /Storno|Stornieren/ }).click()

  await expect(calendarPage.card(courseDateId).getByRole('button', { name: 'Buchen' })).toBeVisible()
  await expect(calendarPage.card(courseDateId)).not.toContainText('Gebucht')
})

test('copies the ICS subscription link from the subscription modal', async ({
  page,
  loginAsCustomer,
  stubClipboard,
}) => {
  await stubClipboard()
  await loginAsCustomer('customer_single_dog')
  await page.goto('/customer/calendar')

  await page.getByRole('button', { name: 'Abonnieren' }).click()
  await expect(page.getByTestId('calendar-subscription-modal')).toBeVisible()
  await page.getByTestId('copy-calendar-url').click()

  const clipboardWrites = await page.evaluate<string[]>(() => {
    return (window as Window & { __clipboardWrites?: string[] }).__clipboardWrites ?? []
  })

  expect(clipboardWrites).toHaveLength(1)
  expect(clipboardWrites[0]).toMatch(/\/api\/calendar\/customer\/.+\.ics$/)
})
