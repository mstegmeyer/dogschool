import { expect, type Locator, type Page } from '@playwright/test'

async function clickFirstVisible(candidates: Locator[]): Promise<void> {
  for (const candidate of candidates) {
    const visible = await candidate.first().isVisible().catch(() => false)
    if (!visible) continue

    await candidate.first().click()
    return
  }

  throw new Error('No visible candidate could be clicked.')
}

export async function selectMenuOption(page: Page, trigger: Locator, optionLabel: string): Promise<void> {
  await trigger.click()

  await clickFirstVisible([
    page.getByRole('option', { name: optionLabel, exact: true }),
    page.getByRole('button', { name: optionLabel, exact: true }),
    page.locator('[role="option"]').filter({ hasText: optionLabel }),
    page.locator('li').filter({ hasText: optionLabel }),
    page.locator('div[role="menuitem"]').filter({ hasText: optionLabel }),
    page.locator('span').filter({ hasText: optionLabel }),
  ])
}

export async function chooseDropdownAction(page: Page, trigger: Locator, actionLabel: string): Promise<void> {
  await trigger.click()

  await clickFirstVisible([
    page.getByRole('menuitem', { name: actionLabel, exact: true }),
    page.getByRole('button', { name: actionLabel, exact: true }),
    page.locator('span').filter({ hasText: actionLabel }),
  ])
}

export async function expectHeading(page: Page, title: string | RegExp): Promise<void> {
  await expect(page.getByRole('heading', { name: title })).toBeVisible()
}

export function visibleByTestId(page: Page, testId: string): Locator {
  return page.locator(`[data-testid="${testId}"]:visible`)
}

export async function waitForToast(page: Page, title: string | RegExp): Promise<void> {
  await expect(page.getByText(title, { exact: typeof title === 'string' })).toBeVisible({ timeout: 15_000 })
}
