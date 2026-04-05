import { beforeEach, describe, expect, it } from 'vitest';
import AddDogModal from './AddDogModal.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AddDogModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the add dog modal and clears field errors on input', async () => {
        const wrapper = mountComponent(AddDogModal, {
            props: {
                modelValue: true,
                form: {
                    name: '',
                    race: '',
                    gender: '',
                    color: '',
                    shoulderHeightCm: 0,
                },
                fieldErrors: { name: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[placeholder="z.B. Bella"]').setValue('Nala');
        await wrapper.get('[data-testid="add-dog-height"]').setValue('47');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Hund hinzufügen');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['name']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
