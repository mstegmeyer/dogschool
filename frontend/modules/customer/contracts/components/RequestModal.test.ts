import { beforeEach, describe, expect, it } from 'vitest';
import RequestModal from './RequestModal.vue';
import {
    baseDog,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('RequestModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the request modal and emits normalization and submission', async () => {
        const wrapper = mountComponent(RequestModal, {
            props: {
                modelValue: true,
                dogOptions: [{ label: baseDog.name, value: baseDog.id }],
                form: {
                    dogId: baseDog.id,
                    coursesPerWeek: 2,
                    startDate: '2026-05-01',
                },
                fieldErrors: { dogId: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="request-contract-start-date"]').setValue('2026-06-01');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Vertrag anfragen');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['startDate']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
