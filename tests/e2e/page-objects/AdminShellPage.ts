import { expect, type Page } from '@playwright/test';

export class AdminShellPage {
    constructor(private readonly page: Page) {}

    async openMobileMenu(): Promise<void> {
        await this.page.getByTestId('admin-mobile-menu-toggle').click();
        await expect(this.page.getByTestId('admin-mobile-menu')).toBeVisible();
    }

    async logout(): Promise<void> {
        await this.page.getByTestId('admin-logout').first().click();
    }
}
