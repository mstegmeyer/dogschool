import { selectMenuOption, visibleByTestId } from '../helpers/ui'
import { expect, test } from '../fixtures/test'

test('updates calendar trainer overrides and handles cancel/reactivate flows', async ({
  page,
  loginAsAdmin,
  manifest,
}) => {
  const trainerOverrideDate = manifest.courseDates.admin_trainer_override_course.current
  const cancelCurrentDate = manifest.courseDates.admin_cancel_course.current
  const cancelNextDate = manifest.courseDates.admin_cancel_course.next
  const reactivateDate = manifest.courseDates.admin_reactivate_course.current
  const detailModal = page.getByTestId('calendar-detail-modal')

  await loginAsAdmin()
  await page.goto('/admin/calendar')

  await page.locator(`[data-course-date-id="${trainerOverrideDate}"]`).click()
  await expect(detailModal).toBeVisible()
  await selectMenuOption(page, page.getByTestId('calendar-detail-trainer'), 'Manuela')
  await page.getByTestId('save-calendar-trainer').click()
  await expect(detailModal).toContainText('Manuela')
  await expect(page.getByTestId('save-calendar-trainer')).toBeDisabled()
  await page.keyboard.press('Escape')
  await expect(detailModal).toHaveCount(0)

  await page.locator(`[data-course-date-id="${cancelCurrentDate}"]`).click()
  await page.getByTestId('cancel-calendar-date').click()
  await expect(page.locator(`[data-course-date-id="${cancelCurrentDate}"]`)).toContainText('Abgesagt')

  await page.getByTestId('calendar-next').click()
  await page.locator(`[data-course-date-id="${cancelNextDate}"]`).click()
  await page.getByTestId('calendar-cancel-notify').click()
  await page.getByTestId('calendar-cancel-title').fill('Playwright Absage')
  await page.getByTestId('calendar-cancel-message').fill('Bitte einmal auf die nächste Woche ausweichen.')
  await page.getByTestId('cancel-calendar-date').click()
  await expect(page.locator(`[data-course-date-id="${cancelNextDate}"]`)).toContainText('Abgesagt')

  await page.getByTestId('calendar-today').click()
  await page.locator(`[data-course-date-id="${reactivateDate}"]`).click()
  await page.getByTestId('reactivate-calendar-date').click()
  await expect(page.locator(`[data-course-date-id="${reactivateDate}"]`)).not.toContainText('Abgesagt')
})

test('filters contract states, approves, declines, and cancels contracts', async ({
  page,
  loginAsAdmin,
  manifest,
}) => {
  await loginAsAdmin()
  await page.goto('/admin/contracts')

  await selectMenuOption(page, page.getByTestId('contract-state-filter'), 'Angefragt')
  await visibleByTestId(page, `approve-contract-${manifest.contracts.approve}`).click()
  await expect(visibleByTestId(page, `approve-contract-${manifest.contracts.approve}`)).toHaveCount(0)
  await visibleByTestId(page, `decline-contract-${manifest.contracts.decline}`).click()
  await expect(visibleByTestId(page, `decline-contract-${manifest.contracts.decline}`)).toHaveCount(0)

  await selectMenuOption(page, page.getByTestId('contract-state-filter'), 'Aktiv')
  await visibleByTestId(page, `cancel-contract-${manifest.contracts.cancel}`).click()
  await expect(page.getByTestId('contract-cancel-modal')).toBeVisible()

  const contractEndDateInput = visibleByTestId(page, 'contract-end-date')
  await contractEndDateInput.fill('2026-04-15')
  await contractEndDateInput.press('Tab')
  await expect(contractEndDateInput).toHaveValue('2026-04-30')
  await page.getByTestId('confirm-contract-cancel').click()
  await expect(page.getByTestId('contract-cancel-modal')).toHaveCount(0)
  await selectMenuOption(page, page.getByTestId('contract-state-filter'), 'Gekündigt')
  await expect(
    page.getByRole('row', { name: new RegExp(`${manifest.customers.customer_contract_cancel.name}.*30\\.04\\.2026`) }),
  ).toBeVisible()
})
