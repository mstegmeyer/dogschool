import { selectMenuOption } from '../helpers/ui';
import { expect, test } from '../fixtures/test';

test('renders dashboard stats, opens notification details, and quick-books a subscribed date', async ({
    page,
    loginAsCustomer,
    manifest,
}) => {
    const quickBookDateId = manifest.courseDates.customer_dashboard_course.current;
    const customer = manifest.customers.customer_dashboard;

    await loginAsCustomer('customer_dashboard');
    await page.goto('/customer');

    await expect(page.getByRole('heading', { name: `Hallo, ${customer.name}!` })).toBeVisible();
    await expect(page.getByText('Guthaben (Credits)')).toBeVisible();
    await expect(page.getByText('Abonnierte Kurse')).toBeVisible();
    await expect(page.getByText('Registrierte Hunde')).toBeVisible();
    await expect(page.getByText('Dashboard quick booking').first()).toBeVisible();

    await page.getByTestId(`dashboard-notification-${manifest.notifications.pinnedGlobal}`).click();
    const notificationModal = page.getByTestId('dashboard-notification-modal');
    await expect(notificationModal).toBeVisible();
    await expect(notificationModal.getByRole('heading', { name: 'Wichtiger Wochenhinweis' })).toBeVisible();
    await page.keyboard.press('Escape');

    await selectMenuOption(page, page.getByTestId(`dashboard-dog-select-${quickBookDateId}`), customer.dogNames[0]!);
    await expect(page.getByTestId(`dashboard-book-${quickBookDateId}`)).toBeEnabled();
    await page.getByTestId(`dashboard-book-${quickBookDateId}`).click();

    await expect(page.getByText(`Gebucht für ${customer.dogNames[0]}`)).toBeVisible();
});
