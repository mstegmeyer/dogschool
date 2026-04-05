import { expect, test } from '../fixtures/test';

test('shows the admin dashboard stats, open contract requests, and today schedule', async ({
    page,
    loginAsAdmin,
}) => {
    await loginAsAdmin();
    await page.goto('/admin');

    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByText('Aktive Kurse / Woche')).toBeVisible();
    await expect(page.getByText('Aktive Verträge')).toBeVisible();
    await expect(page.getByText('Monatlicher Vertragswert')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Offene Vertragsanfragen' })).toBeVisible();
    await expect(page.getByText('Alle anzeigen')).toBeVisible();
    await expect(page.getByText('Preis prüfen')).toHaveCount(0);
    await expect(page.getByRole('heading', { name: 'Heutige Termine' })).toBeVisible();
});

test('searches customers, uses pagination, opens the detail page, and adjusts credits', async ({
    page,
    loginAsAdmin,
    manifest,
}) => {
    await loginAsAdmin();
    await page.goto('/admin/customers');

    await expect(page.getByText(/^1–20 von \d+ Kunden$/)).toBeVisible();
    await page.getByRole('button', { name: '2' }).click();
    await expect(page.getByText(/^\d+–\d+ von \d+ Kunden$/)).toBeVisible();

    await page.getByRole('button', { name: '1' }).click();
    await page.getByTestId('customer-search').fill(manifest.customers.customer_fill_02.name);

    const customerRow = page.getByRole('row', { name: new RegExp(manifest.customers.customer_fill_02.name) });
    await expect(customerRow).toBeVisible();
    await customerRow.click();

    await expect(page.getByRole('heading', { name: manifest.customers.customer_fill_02.name })).toBeVisible();

    await page.getByRole('button', { name: 'Guthaben anpassen' }).click();
    await expect(page.getByText('Bitte eine Korrektur ungleich 0 angeben.')).toBeVisible();

    await page.getByLabel('Korrektur').fill('2');
    await page.getByLabel('Beschreibung').fill('Playwright Korrektur');
    await page.getByRole('button', { name: 'Guthaben anpassen' }).click();

    await expect(page.getByRole('cell', { name: 'Playwright Korrektur' })).toBeVisible();
});
