import { beforeEach, describe, expect, it } from 'vitest';
import AdminCourseFormModal from './FormModal.vue';
import {
    baseTrainer,
    courseForm,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCourseFormModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the course form modal including the schedule hint', async () => {
        const wrapper = mountComponent(AdminCourseFormModal, {
            props: {
                modelValue: true,
                editingCourse: true,
                form: { ...courseForm },
                dayOptions: [{ label: 'Dienstag', value: 2 }],
                courseTypeOptions: [{ label: 'Agility (AGI)', value: 'AGI' }],
                trainerOptions: [{ label: 'Lea', value: baseTrainer.id }],
                showScheduleHint: true,
                scheduleHintText: 'Terminserie wird angepasst.',
                formError: '',
                fieldErrors: { comment: 'Hinweis' },
                saving: false,
            },
        });

        await wrapper.get('[data-testid="course-form-comment"]').setValue('Neuer Kommentar');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Terminserie wird angepasst.');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['comment']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
