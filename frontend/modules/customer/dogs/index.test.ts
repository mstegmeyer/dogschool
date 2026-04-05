import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    dog,
    installCustomerGlobals,
    mountDogsPage,
} from '~/tests/modules/customer-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('customer dogs page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockResolvedValue({ items: [dog] });
    });

    it('loads dogs, validates adding a dog, and submits the form', async () => {
        const wrapper = await mountDogsPage();
        const modal = wrapper.getComponent({ name: 'AddDogModal' });

        expect(wrapper.getComponent({ name: 'DogsGrid' }).props('dogs')).toHaveLength(1);

        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, string>;
        form.name = 'Bella';
        form.race = 'Labrador';
        form.gender = 'female';
        form.color = 'black';
        form.shoulderHeightCm = 52;

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/customer/dogs', {
            name: 'Bella',
            race: 'Labrador',
            gender: 'female',
            color: 'black',
            shoulderHeightCm: 52,
        });
    });
});
