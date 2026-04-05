import { chooseDropdownAction, selectMenuOption, visibleByTestId } from '../helpers/ui';
import { expect, test } from '../fixtures/test';

function extractTotalCount(summary: string, singularLabel: string, pluralLabel: string): number {
    const paginatedMatch = summary.match(new RegExp(`von (\\d+) ${pluralLabel}$`));
    if (paginatedMatch) {
        return Number.parseInt(paginatedMatch[1] ?? '0', 10);
    }

    const singlePageMatch = summary.match(new RegExp(`^(\\d+) (${singularLabel}|${pluralLabel})$`));
    if (singlePageMatch) {
        return Number.parseInt(singlePageMatch[1] ?? '0', 10);
    }

    throw new Error(`Could not extract total count from summary "${summary}".`);
}

test('creates, edits, and deletes course types', async ({
    page,
    loginAsAdmin,
    manifest,
}) => {
    const createdSuffix = Date.now().toString().slice(-6);
    const createdName = `Playwright Kursart ${createdSuffix}`;

    await loginAsAdmin();
    await page.goto('/admin/course-types');

    await page.getByRole('button', { name: 'Neue Kursart' }).click();
    await expect(page.getByTestId('course-type-form-modal')).toBeVisible();
    await page.getByLabel('Kürzel').fill(`PW${createdSuffix}`);
    await page.getByLabel('Name').fill(createdName);
    const [createCourseTypeResponse] = await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/course-types')
      && response.request().method() === 'POST'
      && response.status() === 201,
        ),
        page.getByRole('button', { name: 'Speichern' }).click(),
    ]);
    const createdCourseType = await createCourseTypeResponse.json() as { id: string };

    await expect(visibleByTestId(page, `course-type-row-${createdCourseType.id}`)).toContainText(createdName);

    await page.getByTestId(`edit-course-type-${manifest.courseTypes.course_type_edit.id}`).click();
    await page.getByLabel('Name').fill('E2E Editiert');
    await page.getByRole('button', { name: 'Speichern' }).click();
    await expect(visibleByTestId(page, `course-type-row-${manifest.courseTypes.course_type_edit.id}`)).toContainText('E2E Editiert');

    await page.getByTestId(`delete-course-type-${manifest.courseTypes.course_type_delete.id}`).click();
    await expect(page.getByTestId(`delete-course-type-${manifest.courseTypes.course_type_delete.id}`)).toHaveCount(0);
    await expect(page.getByTestId(`delete-course-type-mobile-${manifest.courseTypes.course_type_delete.id}`)).toHaveCount(0);
});

test('filters, paginates, creates, edits, archives, and unarchives courses', async ({
    page,
    loginAsAdmin,
    manifest,
}) => {
    await loginAsAdmin();
    await page.goto('/admin/courses');

    const resultSummary = page.locator('p').filter({ hasText: /Kursen?$|von \d+ Kursen$/ }).last();
    const initialTotal = extractTotalCount((await resultSummary.textContent()) ?? '', 'Kurs', 'Kursen');

    await expect(page.getByText(/^1–20 von \d+ Kursen$/)).toBeVisible();
    await page.getByRole('button', { name: '2' }).click();
    await expect(page.getByText(/^\d+–\d+ von \d+ Kursen$/)).toBeVisible();
    await page.getByRole('button', { name: '1' }).click();

    await page.getByRole('button', { name: 'Neuer Kurs' }).click();
    await expect(page.getByTestId('course-form-modal')).toBeVisible();
    await selectMenuOption(page, page.getByTestId('course-form-type-code'), 'E2E Editierbar (E2EEDIT)');
    await selectMenuOption(page, page.getByTestId('course-form-day-of-week'), 'Samstag');
    await page.getByTestId('course-form-level').fill('3');
    await page.getByTestId('course-form-start-time').fill('08:00');
    await page.getByTestId('course-form-end-time').fill('09:00');
    await selectMenuOption(page, page.getByTestId('course-form-trainer'), 'Manuela');
    await page.getByTestId('course-form-comment').fill('Playwright erstellt');
    const [createCourseResponse] = await Promise.all([
        page.waitForResponse(response =>
            response.url().includes('/api/admin/courses')
      && response.request().method() === 'POST'
      && response.status() === 201,
        ),
        page.getByTestId('save-course').click(),
    ]);
    const createdCourse = await createCourseResponse.json() as { id: string; comment: string | null };
    expect(createdCourse.comment).toBe('Playwright erstellt');
    await expect(resultSummary).toContainText(`${initialTotal + 1} Kurs`);

    await chooseDropdownAction(page, page.getByTestId(`course-actions-${manifest.courses.admin_edit_course}`), 'Bearbeiten');
    await page.getByTestId('course-form-comment').fill('Playwright aktualisiert');
    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes(`/api/admin/courses/${manifest.courses.admin_edit_course}`)
      && response.request().method() === 'PUT'
      && response.ok(),
        ),
        page.getByTestId('save-course').click(),
    ]);
    await chooseDropdownAction(page, page.getByTestId(`course-actions-${manifest.courses.admin_edit_course}`), 'Bearbeiten');
    await expect(page.getByTestId('course-form-comment')).toHaveValue('Playwright aktualisiert');
    await page.getByRole('button', { name: 'Abbrechen' }).click();
    await expect(page.getByTestId('course-form-modal')).toHaveCount(0);

    await chooseDropdownAction(page, page.getByTestId(`course-actions-${manifest.courses.admin_archive_course}`), 'Archivieren');
    await expect(page.getByTestId('course-archive-modal')).toBeVisible();
    await page.getByTestId('archive-remove-from-date').fill(manifest.week.nextMonday);
    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes(`/api/admin/courses/${manifest.courses.admin_archive_course}/archive`)
      && response.request().method() === 'POST'
      && response.ok(),
        ),
        page.getByTestId('confirm-course-archive').click(),
    ]);
    await expect(page.getByTestId('course-archive-modal')).toHaveCount(0);

    await selectMenuOption(page, page.getByTestId('course-archive-filter'), 'Archiviert');
    await expect(page.getByTestId(`course-row-${manifest.courses.admin_archive_course}`)).toBeVisible();
    await Promise.all([
        page.waitForResponse(response =>
            response.url().includes(`/api/admin/courses/${manifest.courses.admin_unarchive_course}/unarchive`)
      && response.request().method() === 'POST'
      && response.ok(),
        ),
        chooseDropdownAction(page, page.getByTestId(`course-actions-${manifest.courses.admin_unarchive_course}`), 'Reaktivieren'),
    ]);
    await expect(page.getByTestId(`course-row-${manifest.courses.admin_unarchive_course}`)).toHaveCount(0);
});
