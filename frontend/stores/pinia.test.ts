import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, getActivePinia, setActivePinia } from 'pinia';

async function loadModule() {
    const mod = await import('./pinia');
    return mod.resolvePinia;
}

describe('resolvePinia', () => {
    beforeEach(() => {
        vi.resetModules();
        setActivePinia(undefined);
    });

    it('creates and reuses a fallback Pinia instance when no active store exists', async () => {
        const resolvePinia = await loadModule();

        const first = resolvePinia();
        const second = resolvePinia();

        expect(first).toBe(second);
        expect(getActivePinia()).toBe(first);
    });

    it('returns the active Pinia instance when one is already set', async () => {
        const activePinia = createPinia();
        setActivePinia(activePinia);
        const resolvePinia = await loadModule();

        expect(resolvePinia()).toBe(activePinia);
    });
});
