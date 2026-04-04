import { beforeEach, describe, expect, it } from 'vitest';
import AdminCalendarDetailModal from './DetailModal.vue';
import {
    baseTrainer,
    installComponentGlobals,
    makeCourseDate,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCalendarDetailModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the admin calendar detail modal with cancellation controls', async () => {
        const wrapper = mountComponent(AdminCalendarDetailModal, {
            props: {
                modelValue: true,
                selectedDate: makeCourseDate({
                    trainer: baseTrainer,
                    trainerOverridden: true,
                    courseTrainer: { id: 'trainer-2', fullName: 'Standard Trainer' },
                    bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                    subscriberCount: 3,
                }),
                trainerOptions: [
                    { label: 'Standard vom Kurs verwenden', value: '' },
                    { label: baseTrainer.fullName, value: baseTrainer.id },
                ],
                selectedTrainerId: '',
                savingTrainer: false,
                cancelling: false,
                cancelNotify: true,
                cancelNotifyTitle: 'Ausfall',
                cancelNotifyMessage: 'Heute kein Training',
            },
        });

        await wrapper.get('[data-testid="save-calendar-trainer"]').trigger('click');
        await wrapper.get('[data-testid="cancel-calendar-date"]').trigger('click');

        expect(wrapper.text()).toContain('Standard für den Kurs: Standard Trainer');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.emitted('save-trainer')).toHaveLength(1);
        expect(wrapper.emitted('cancel-date')).toHaveLength(1);
    });
});
