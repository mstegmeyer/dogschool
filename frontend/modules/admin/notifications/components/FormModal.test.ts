import { beforeEach, describe, expect, it } from 'vitest';
import NotificationFormModal from './FormModal.vue';
import {
    adminNotificationForm,
    baseCourse,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('NotificationFormModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the notification form modal and submits the payload', async () => {
        const wrapper = mountComponent(NotificationFormModal, {
            props: {
                modelValue: true,
                editing: true,
                form: { ...adminNotificationForm },
                courseOptions: [{ label: 'Agility', value: baseCourse.id }],
                fieldErrors: { title: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="notification-title"]').setValue('Neuer Titel');
        await wrapper.get('[data-testid="notification-message"]').setValue('Neue Nachricht');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Mitteilung bearbeiten');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['title']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
