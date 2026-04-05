import { selectMenuOption, visibleByTestId } from '../helpers/ui';
import { expect, test } from '../fixtures/test';

test('shows the empty dog state, validates the form, and adds a dog', async ({
    page,
    loginAsCustomer,
}) => {
    await loginAsCustomer('customer_empty');
    await page.goto('/customer/dogs');

    await expect(page.getByTestId('dogs-empty-state')).toContainText('Du hast noch keinen Hund registriert.');
    await page.getByRole('button', { name: 'Hund hinzufügen' }).click();
    const addDogModal = page.getByTestId('add-dog-modal');
    const dogNameInput = page.getByLabel('Name');
    await expect(addDogModal).toBeVisible();

    await addDogModal.getByRole('button', { name: 'Hinzufügen' }).click();
    await expect.poll(async () => dogNameInput.evaluate((input: HTMLInputElement) => input.matches(':invalid'))).toBe(true);

    await dogNameInput.fill('Nori');
    await page.getByLabel('Rasse').fill('Labrador');
    await selectMenuOption(page, page.getByText('Auswählen'), 'Hündin');
    await page.getByLabel('Farbe').fill('Schwarz');
    await page.getByTestId('add-dog-height').fill('47');
    await addDogModal.getByRole('button', { name: 'Hinzufügen' }).click();

    await expect(addDogModal).toHaveCount(0);
    await expect(page.getByText('Nori')).toBeVisible();
    await expect(page.getByText('Schulterhöhe: 47 cm')).toBeVisible();
});

test('opens course details and can unsubscribe and resubscribe a subscribed course', async ({
    page,
    loginAsCustomer,
    manifest,
}) => {
    const courseId = manifest.courses.customer_contracts_course;

    await loginAsCustomer('customer_contracts');
    await page.goto('/customer/courses');

    await page.getByRole('tab', { name: 'Meine Kurse' }).click();
    await page.getByTestId(`course-subscribed-row-${courseId}`).click();
    await expect(page.getByText('Nächste Termine')).toBeVisible();
    await expect(page.getByText('Mitteilungsverlauf')).toBeVisible();
    await page.keyboard.press('Escape');

    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes(`/api/customer/courses/${courseId}/subscribe`)
      && response.request().method() === 'DELETE'
      && response.ok(),
        ),
        visibleByTestId(page, `course-subscribed-subscription-action-${courseId}`).click(),
    ]);
    await expect(visibleByTestId(page, `course-subscribed-subscription-action-${courseId}`)).toHaveCount(0);

    await page.getByRole('tab', { name: 'Verfügbare Kurse' }).click();
    await expect(visibleByTestId(page, `course-available-subscription-action-${courseId}`)).toBeVisible();
    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes(`/api/customer/courses/${courseId}/subscribe`)
      && response.request().method() === 'POST'
      && response.ok(),
        ),
        visibleByTestId(page, `course-available-subscription-action-${courseId}`).click(),
    ]);
    await expect(visibleByTestId(page, `course-available-subscription-action-${courseId}`)).toContainText('Abbestellen');
    await page.getByRole('tab', { name: 'Meine Kurse' }).click();
    await expect(page.getByTestId(`course-subscribed-row-${courseId}`)).toBeVisible();
    await expect(visibleByTestId(page, `course-subscribed-subscription-action-${courseId}`)).toBeVisible();
});
