import { expect, type Locator, type Page } from '@playwright/test';

export class CustomerCalendarPage {
    constructor(private readonly page: Page) {}

    async goto(): Promise<void> {
        await this.page.goto('/customer/calendar');
        await expect(this.page.getByRole('heading', { name: 'Kalender' })).toBeVisible();
    }

    card(courseDateId: string): Locator {
        return this.page.locator(`[data-course-date-id="${courseDateId}"]`);
    }

    bookingTrigger(courseDateId: string): Locator {
        return this.page.getByTestId(`open-booking-${courseDateId}`);
    }

    detailTrigger(courseDateId: string): Locator {
        return this.page.getByTestId(`open-course-date-details-${courseDateId}`);
    }

    async openBooking(courseDateId: string): Promise<void> {
        await this.bookingTrigger(courseDateId).click();
        await expect(this.page.getByTestId('booking-modal')).toBeVisible();
    }

    async openDetails(courseDateId: string): Promise<void> {
        await this.detailTrigger(courseDateId).click();
        await expect(this.page.getByTestId('customer-calendar-detail-modal')).toBeVisible();
    }
}
