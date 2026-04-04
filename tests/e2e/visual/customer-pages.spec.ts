import { expect, test } from '../fixtures/test';

test('customer dashboard page stays visually stable', async ({
    page,
    loginAsCustomer,
    waitForVisualReady,
}) => {
    await loginAsCustomer('customer_multi_dog');
    await page.goto('/customer');
    await waitForVisualReady();

    await expect(page).toHaveScreenshot('customer/dashboard.png', { fullPage: true });
});

test('customer calendar page stays visually stable', async ({
    page,
    loginAsCustomer,
    waitForVisualReady,
}) => {
    await loginAsCustomer('customer_multi_dog');
    await page.goto('/customer/calendar');
    await waitForVisualReady();

    await expect(page).toHaveScreenshot('customer/calendar.png', { fullPage: true });
});

test('customer profile page stays visually stable', async ({
    page,
    loginAsCustomer,
    stubPushApi,
    waitForVisualReady,
}) => {
    await stubPushApi();
    await loginAsCustomer('customer_profile');
    await page.goto('/customer/profile');
    await waitForVisualReady();

    await expect(page).toHaveScreenshot('customer/profile.png', { fullPage: true });
});

test('customer courses page stays visually stable', async ({
    page,
    loginAsCustomer,
    waitForVisualReady,
}) => {
    await loginAsCustomer('customer_contracts');
    await page.goto('/customer/courses');
    await waitForVisualReady();

    await expect(page).toHaveScreenshot('customer/courses.png', { fullPage: true });
});

test('customer credits page stays visually stable', async ({
    page,
    loginAsCustomer,
    waitForVisualReady,
}) => {
    await loginAsCustomer('customer_profile');
    await page.goto('/customer/credits');
    await waitForVisualReady();

    await expect(page).toHaveScreenshot('customer/credits.png', { fullPage: true });
});
