import { beforeEach, describe, expect, it } from 'vitest';
import CourseTypeFormModal from './FormModal.vue';
import {
    courseTypeForm,
    installComponentGlobals,
    mountComponent,
    recurrenceOptions,
} from '~/tests/components/component-test-helpers';

describe('CourseTypeFormModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the course type form modal and clears field errors on input', async () => {
        const wrapper = mountComponent(CourseTypeFormModal, {
            props: {
                modelValue: true,
                editing: false,
                form: { ...courseTypeForm },
                recurrenceOptions,
                fieldErrors: { code: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[placeholder="z.B. AGI"]').setValue('OB');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Neue Kursart');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['code']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
