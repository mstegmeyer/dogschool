import { createPinia, getActivePinia, setActivePinia, type Pinia } from 'pinia';

let fallbackPinia: Pinia | null = null;

export function resolvePinia(): Pinia {
    const activePinia = getActivePinia();
    if (activePinia) {
        return activePinia;
    }

    if (fallbackPinia === null) {
        fallbackPinia = createPinia();
    }

    setActivePinia(fallbackPinia);

    return fallbackPinia;
}
