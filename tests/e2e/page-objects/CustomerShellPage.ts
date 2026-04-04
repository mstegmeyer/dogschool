import { expect, type Page } from '@playwright/test'

export class CustomerShellPage {
  constructor(private readonly page: Page) {}

  async openMobileMenu(): Promise<void> {
    await this.page.getByTestId('customer-mobile-menu-toggle').click()
    await expect(this.page.getByTestId('customer-mobile-menu')).toBeVisible()
  }

  async logout(): Promise<void> {
    await this.page.getByTestId('customer-logout').first().click()
  }
}
