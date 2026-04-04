import { promises as fs } from 'node:fs';
import { expect, test as base } from '@playwright/test';
import { createAdminStorageState, createCustomerStorageState, primePageWithStorageState } from '../helpers/auth';
import { freezeBrowserClock, installClipboardStub, installPushStub, waitForFontsAndNetwork } from '../helpers/browser';
import { readManifest, type CustomerPersona, type E2eManifest } from './manifest';

type Fixtures = {
    manifest: E2eManifest,
    loginAsCustomer: (persona: CustomerPersona) => Promise<string>,
    loginAsAdmin: () => Promise<string>,
    expectToast: (title: string | RegExp) => Promise<void>,
    stubClipboard: () => Promise<void>,
    stubPushApi: () => Promise<void>,
    waitForVisualReady: () => Promise<void>,
};

export const test = base.extend<Fixtures>({
    // Playwright fixtures require the first parameter to stay as an object pattern.
    // eslint-disable-next-line no-empty-pattern
    manifest: async ({}, use) => {
        await use(await readManifest());
    },

    page: async ({ page, manifest }, use) => {
        await freezeBrowserClock(page, manifest.fixedNow);
        await use(page);
    },

    loginAsCustomer: async ({ page, manifest, baseURL }, use) => {
        const createdStatePaths = new Set<string>();

        await use(async (persona: CustomerPersona) => {
            const statePath = await createCustomerStorageState(
                manifest,
                persona,
                baseURL!,
                process.env.PLAYWRIGHT_API_BASE_URL || 'http://127.0.0.1:9080',
            );

            createdStatePaths.add(statePath);
            await primePageWithStorageState(page, statePath, baseURL!);
            return statePath;
        });

        await Promise.all([...createdStatePaths].map(async statePath => {
            await fs.rm(statePath, { force: true });
        }));
    },

    loginAsAdmin: async ({ page, manifest, baseURL }, use) => {
        const createdStatePaths = new Set<string>();

        await use(async () => {
            const statePath = await createAdminStorageState(
                manifest,
                baseURL!,
                process.env.PLAYWRIGHT_API_BASE_URL || 'http://127.0.0.1:9080',
            );

            createdStatePaths.add(statePath);
            await primePageWithStorageState(page, statePath, baseURL!);
            return statePath;
        });

        await Promise.all([...createdStatePaths].map(async statePath => {
            await fs.rm(statePath, { force: true });
        }));
    },

    expectToast: async ({ page }, use) => {
        await use(async (title: string | RegExp) => {
            await expect(page.getByText(title, { exact: typeof title === 'string' })).toBeVisible({ timeout: 15_000 });
        });
    },

    stubClipboard: async ({ page }, use) => {
        await use(async () => {
            await installClipboardStub(page);
        });
    },

    stubPushApi: async ({ page }, use) => {
        await use(async () => {
            await installPushStub(page);
        });
    },

    waitForVisualReady: async ({ page }, use) => {
        await use(async () => {
            await waitForFontsAndNetwork(page);
        });
    },
});

export { expect };
