import { selectMenuOption } from '../helpers/ui';
import { expect, test } from '../fixtures/test';

test('customer requests a hotel booking and admin assigns, confirms, and sees the overviews', async ({
    page,
    loginAsAdmin,
    loginAsCustomer,
    manifest,
}) => {
    const customer = manifest.customers.customer_hotel_booking;
    const dogName = customer.dogNames[0];
    const storedHeight = customer.dogShoulderHeights[0];
    const roomName = 'E2E Rueckzugsort';

    await loginAsCustomer('customer_hotel_booking');
    await page.goto('/customer/hotel/bookings');

    await page.getByTestId('open-hotel-booking-request').click();
    const requestModal = page.getByTestId('request-hotel-booking-modal');
    await expect(requestModal).toBeVisible();

    await selectMenuOption(page, page.getByTestId('request-hotel-booking-dog'), dogName);
    await expect(requestModal).toContainText(`Gespeichert sind derzeit ${storedHeight} cm`);
    await page.getByTestId('request-hotel-booking-height').fill('60');
    await page.getByTestId('request-hotel-booking-start-at').fill('2026-04-06T14:00');
    await page.getByTestId('request-hotel-booking-end-at').fill('2026-04-07T08:00');

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/customer/hotel-bookings')
            && response.request().method() === 'POST'
            && response.status() === 201,
        ),
        page.getByTestId('submit-hotel-booking-request').click(),
    ]);

    await expect(requestModal).toHaveCount(0);
    await expect(page.locator('[data-testid^="hotel-booking-card-"]').filter({ hasText: dogName })).toBeVisible();

    await loginAsAdmin();
    await page.goto('/admin/hotel/rooms');

    await page.getByTestId('open-room-create').click();
    const roomModal = page.getByTestId('room-form-modal');
    await expect(roomModal).toBeVisible();
    await page.getByTestId('room-name').fill(roomName);
    await page.getByTestId('room-square-meters').fill('22');

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/hotel/rooms')
            && response.request().method() === 'POST'
            && response.ok(),
        ),
        roomModal.getByRole('button', { name: 'Anlegen' }).click(),
    ]);

    const roomCard = page.locator('[data-testid^="room-card-"]').filter({ hasText: roomName });
    await expect(roomCard).toBeVisible();
    await roomCard.getByRole('button', { name: 'Bearbeiten' }).click();
    await page.getByTestId('room-square-meters').fill('24');

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/hotel/rooms/')
            && response.request().method() === 'PUT'
            && response.ok(),
        ),
        roomModal.getByRole('button', { name: 'Speichern' }).click(),
    ]);

    await expect(roomCard).toContainText('24 m²');

    await page.goto('/admin/hotel/bookings');
    const bookingRow = page.locator('[data-testid^="hotel-booking-row-"]').filter({ hasText: dogName });
    await expect(bookingRow).toBeVisible();
    await bookingRow.getByRole('button', { name: 'Prüfen' }).click();

    const detailModal = page.getByTestId('hotel-booking-detail-modal');
    await expect(detailModal).toBeVisible();
    await selectMenuOption(page, page.getByTestId('hotel-booking-room-select'), `${roomName} · 24 m²`);

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/hotel/bookings/')
            && response.url().endsWith('/room')
            && response.request().method() === 'PUT'
            && response.ok(),
        ),
        page.getByTestId('assign-hotel-booking-room').click(),
    ]);

    await expect(detailModal).toContainText(roomName);

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/hotel/bookings/')
            && response.url().endsWith('/confirm')
            && response.request().method() === 'POST'
            && response.ok(),
        ),
        page.getByTestId('confirm-hotel-booking').click(),
    ]);

    await expect(detailModal).toHaveCount(0);
    await expect(bookingRow).toHaveCount(0);

    await page.goto('/admin/hotel/occupancy');
    await expect(page.getByText(roomName)).toBeVisible();
    await expect(page.getByText(dogName)).toBeVisible();

    await page.goto('/admin/hotel/movements');
    const arrivalCard = page.locator('[data-testid^="hotel-arrival-"]').filter({ hasText: `${dogName} · ${customer.name}` });
    const departureCard = page.locator('[data-testid^="hotel-departure-"]').filter({ hasText: `${dogName} · ${customer.name}` });

    await expect(arrivalCard).toBeVisible();
    await expect(arrivalCard).toContainText(roomName);
    await expect(departureCard).toBeVisible();
    await expect(departureCard).toContainText(roomName);
});

test('customer hotel request form validates invalid end handover times', async ({
    page,
    loginAsCustomer,
    manifest,
}) => {
    const customer = manifest.customers.customer_hotel_booking;
    const dogName = customer.dogNames[0];

    await loginAsCustomer('customer_hotel_booking');
    await page.goto('/customer/hotel/bookings');

    await page.getByTestId('open-hotel-booking-request').click();
    const requestModal = page.getByTestId('request-hotel-booking-modal');
    await expect(requestModal).toBeVisible();

    await selectMenuOption(page, page.getByTestId('request-hotel-booking-dog'), dogName);
    await expect(page.getByTestId('request-hotel-booking-height')).toBeVisible();
    await page.getByTestId('request-hotel-booking-height').fill('60');
    await page.getByTestId('request-hotel-booking-start-at').fill('2026-04-06T14:00');
    await page.getByTestId('request-hotel-booking-end-at').fill('2026-04-07T05:30');
    await page.getByTestId('submit-hotel-booking-request').click();

    await expect(requestModal).toContainText('Das Ende muss zwischen 06:00 und 22:00 Uhr liegen.');
    await expect(requestModal).toBeVisible();
});

test('admin filters confirmed hotel bookings and inspects assigned room details', async ({
    page,
    loginAsAdmin,
    manifest,
}) => {
    const confirmedCustomer = manifest.customers.customer_multi_dog;
    const confirmedDogName = confirmedCustomer.dogNames[0];
    const requestedDogName = manifest.customers.customer_contracts.dogNames[0];
    const roomName = manifest.hotelRooms.medium.name;

    await loginAsAdmin();
    await page.goto('/admin/hotel/bookings');

    await selectMenuOption(page, page.getByTestId('hotel-booking-state-filter'), 'Bestätigt');
    await page.getByTestId('hotel-booking-from-filter').fill('2026-04-06T00:00');
    await page.getByTestId('hotel-booking-to-filter').fill('2026-04-06T23:59');

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/hotel/bookings?')
            && response.url().includes('state=CONFIRMED')
            && response.url().includes('from=2026-04-06T00%3A00')
            && response.url().includes('to=2026-04-06T23%3A59')
            && response.request().method() === 'GET'
            && response.ok(),
        ),
        page.getByRole('button', { name: 'Aktualisieren' }).click(),
    ]);

    const confirmedRow = page.locator('[data-testid^="hotel-booking-row-"]')
        .filter({ hasText: confirmedDogName })
        .filter({ hasText: confirmedCustomer.name })
        .filter({ hasText: roomName });
    await expect(confirmedRow).toBeVisible();
    await expect(page.locator('[data-testid^="hotel-booking-row-"]').filter({ hasText: requestedDogName })).toHaveCount(0);

    await confirmedRow.getByRole('button', { name: 'Prüfen' }).click();

    const detailModal = page.getByTestId('hotel-booking-detail-modal');
    await expect(detailModal).toBeVisible();
    await expect(detailModal).toContainText(roomName);
    await expect(detailModal).toContainText('Verfügbar');

    await page.goto('/admin/hotel/movements');
    await page.getByLabel('Von').fill('2026-04-06T00:00');
    await page.getByLabel('Bis').fill('2026-04-06T23:59');
    await page.getByRole('button', { name: 'Aktualisieren' }).click();

    const arrivalCard = page.locator('[data-testid^="hotel-arrival-"]').filter({ hasText: `${confirmedDogName} · ${confirmedCustomer.name}` });
    const departureCard = page.locator('[data-testid^="hotel-departure-"]').filter({ hasText: `${confirmedDogName} · ${confirmedCustomer.name}` });

    await expect(arrivalCard).toBeVisible();
    await expect(arrivalCard).toContainText(roomName);
    await expect(departureCard).toBeVisible();
});
