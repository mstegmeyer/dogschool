import { chooseDropdownAction, selectMenuOption } from '../helpers/ui';
import { expect, test } from '../fixtures/test';

test('customer dialog surfaces stay visually stable', async ({
    page,
    loginAsCustomer,
    manifest,
    waitForVisualReady,
}) => {
    await loginAsCustomer('customer_multi_dog');
    await page.goto('/customer/calendar');

    await page.getByTestId(`open-booking-${manifest.courseDates.customer_multi_course.current}`).click();
    await waitForVisualReady();
    await expect(page.getByTestId('booking-modal')).toHaveScreenshot('dialogs/customer-booking-modal.png');

    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Abonnieren' }).click();
    await waitForVisualReady();
    await expect(page.getByTestId('calendar-subscription-modal')).toHaveScreenshot('dialogs/customer-subscription-modal.png');

    await page.keyboard.press('Escape');
    await page.goto('/customer/hotel/bookings');
    await page.getByTestId('open-hotel-booking-request').click();
    await selectMenuOption(
        page,
        page.getByTestId('request-hotel-booking-dog'),
        manifest.customers.customer_hotel_booking.dogNames[0],
    );
    await waitForVisualReady();
    await expect(page.getByTestId('request-hotel-booking-modal')).toHaveScreenshot('dialogs/customer-hotel-booking-modal.png');
});

test('admin dialog surfaces stay visually stable', async ({
    page,
    loginAsAdmin,
    manifest,
    waitForVisualReady,
}) => {
    await loginAsAdmin();

    await page.goto('/admin/courses');
    await page.getByRole('button', { name: 'Neuer Kurs' }).click();
    await waitForVisualReady();
    await expect(page.getByTestId('course-form-modal')).toHaveScreenshot('dialogs/admin-course-form-modal.png');

    await page.keyboard.press('Escape');
    await chooseDropdownAction(page, page.getByTestId(`course-actions-${manifest.courses.admin_archive_course}`), 'Archivieren');
    await waitForVisualReady();
    await expect(page.getByTestId('course-archive-modal')).toHaveScreenshot('dialogs/admin-course-archive-modal.png');

    await page.keyboard.press('Escape');
    await page.goto('/admin/calendar');
    await page.locator(`[data-course-date-id="${manifest.courseDates.admin_trainer_override_course.current}"]`).click();
    await waitForVisualReady();
    await expect(page.getByTestId('calendar-detail-modal')).toHaveScreenshot('dialogs/admin-calendar-detail-modal.png');
});
