import { expect, test, type Page } from '@playwright/test'

function todayIso(): string {
  return new Date().toLocaleDateString('en-CA', { timeZone: 'Europe/Berlin' })
}

function parseIsoDate(value: string): Date {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}

function toIsoDate(value: Date): string {
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function weekMondayIso(value: string = todayIso()): string {
  const date = parseIsoDate(value)
  const day = date.getDay() === 0 ? 7 : date.getDay()
  date.setDate(date.getDate() - (day - 1))
  return toIsoDate(date)
}

function makeCourseDate(overrides: Record<string, unknown> = {}) {
  const monday = weekMondayIso()

  return {
    id: 'course-date-1',
    courseId: 'course-1',
    courseType: { code: 'JH', name: 'Junghund', recurrenceKind: 'RECURRING' },
    level: 3,
    date: monday,
    dayOfWeek: 1,
    startTime: '10:00',
    endTime: '11:00',
    trainer: null,
    cancelled: false,
    bookingCount: 0,
    createdAt: `${monday}T09:00:00+01:00`,
    booked: false,
    bookings: [],
    bookingWindowClosed: false,
    ...overrides,
  }
}

async function mockCustomerCalendarPage(
  page: Page,
  {
    dogs,
    courseDates,
  }: {
    dogs: Array<{ id: string; name: string }>
    courseDates: any[]
  },
) {
  let currentCourseDates = [...courseDates]

  await page.addInitScript(() => {
    localStorage.setItem('auth:token', 'playwright-token')
    localStorage.setItem('auth:role', 'customer')
  })

  await page.route('**/api/customer/calendar?week=*', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ items: currentCourseDates }),
    })
  })

  await page.route('**/api/customer/dogs', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ items: dogs }),
    })
  })

  await page.route('**/api/customer/calendar/subscription', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ path: '/api/customer/calendar/subscription.ics' }),
    })
  })

  await page.route('**/api/customer/calendar/course-dates/*/book', async (route) => {
    const body = JSON.parse(route.request().postData() ?? '{}')
    const dogId = body.dogId as string

    currentCourseDates = currentCourseDates.map((courseDate) => {
      if (courseDate.id !== 'course-multi') return courseDate

      const dog = dogs.find(candidate => candidate.id === dogId)

      return {
        ...courseDate,
        booked: true,
        bookings: [{
          id: 'booking-1',
          dogId,
          dogName: dog?.name ?? 'Hund',
        }],
      }
    })

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ creditBalance: 7 }),
    })
  })
}

test.describe('customer calendar booking controls', () => {
  test.use({ viewport: { width: 1600, height: 1000 } })

  test('keeps the single-dog booking action inside the weekly card bounds', async ({ page }) => {
    await mockCustomerCalendarPage(page, {
      dogs: [{ id: 'dog-1', name: 'Rex' }],
      courseDates: [
        makeCourseDate({ id: 'course-single', courseId: 'course-single', courseType: { code: 'MH', name: 'Mensch & Hund', recurrenceKind: 'RECURRING' }, level: 1 }),
      ],
    })

    await page.goto('/customer/calendar')

    const sidebar = page.locator('aside')

    await expect(sidebar).toHaveCount(1, { timeout: 15000 })
    await expect(sidebar.first()).toBeVisible({ timeout: 15000 })
    await expect(page.getByText('Mein Bereich')).toBeVisible({ timeout: 15000 })

    const card = page.locator('[data-course-date-id="course-single"]')
    const trigger = page.getByTestId('open-booking-course-single')

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

  test('opens a foreground booking modal for multiple dogs and completes the booking flow', async ({ page }) => {
    await mockCustomerCalendarPage(page, {
      dogs: [
        { id: 'dog-1', name: 'Rex' },
        { id: 'dog-2', name: 'Luna' },
      ],
      courseDates: [
        makeCourseDate({ id: 'course-multi', courseId: 'course-multi', courseType: { code: 'AP', name: 'Apportieren', recurrenceKind: 'RECURRING' }, level: 2 }),
      ],
    })

    await page.goto('/customer/calendar')

    await page.getByTestId('open-booking-course-multi').click()

    const modal = page.getByTestId('booking-modal')
    const select = page.getByTestId('booking-dog-select')

    await expect(modal).toBeVisible()
    await expect(select).toBeVisible()

    await select.selectOption('dog-2')
    await page.getByTestId('confirm-booking').click()

    await expect(modal).toBeHidden()
    await expect(page.locator('[data-course-date-id="course-multi"]')).toContainText('Gebucht')
    await expect(page.locator('[data-course-date-id="course-multi"]')).toContainText('Luna')
  })
})
