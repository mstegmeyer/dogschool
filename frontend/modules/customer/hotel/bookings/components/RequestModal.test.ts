import { beforeEach, describe, expect, it } from 'vitest';
import HotelBookingRequestModal from './RequestModal.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';

describe('HotelBookingRequestModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('hides the shoulder height input until a dog is selected', () => {
        const wrapper = mountComponent(HotelBookingRequestModal, {
            props: {
                modelValue: true,
                dogOptions: [{ label: 'Luna', value: 'dog-1' }],
                selectedDogName: '',
                storedShoulderHeightCm: 48,
                form: {
                    dogId: '',
                    startAt: '',
                    endAt: '',
                    currentShoulderHeightCm: 48,
                },
                fieldErrors: {},
                formError: '',
                saving: false,
            },
        });

        expect(wrapper.find('[data-testid="request-hotel-booking-height"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Schulterhöhe');
    });

    it('renders booking inputs for the selected dog and emits interactions', async () => {
        const form = {
            dogId: 'dog-1',
            startAt: '',
            endAt: '',
            currentShoulderHeightCm: 48,
        };

        const wrapper = mountComponent(HotelBookingRequestModal, {
            props: {
                modelValue: true,
                dogOptions: [{ label: 'Luna', value: 'dog-1' }],
                selectedDogName: 'Luna',
                storedShoulderHeightCm: 48,
                form,
                fieldErrors: {
                    dogId: 'Bitte wählen',
                },
                formError: 'Bitte prüfe die markierten Felder.',
                saving: false,
            },
        });

        expect(wrapper.text()).toContain('Bitte die aktuelle Schulterhöhe von Luna prüfen.');
        expect(wrapper.text()).toContain('Gespeichert sind derzeit 48 cm.');
        expect(wrapper.text()).toContain('Bitte prüfe die markierten Felder.');

        await wrapper.get('[data-testid="request-hotel-booking-dog"]').setValue('dog-1');
        await wrapper.get('[data-testid="request-hotel-booking-height"]').setValue('52');
        await wrapper.get('[data-testid="request-hotel-booking-start-at"]').setValue('2026-04-05T08:00');
        await wrapper.get('[data-testid="request-hotel-booking-end-at"]').setValue('2026-04-06T10:00');
        await wrapper.get('form').trigger('submit.prevent');
        await wrapper.get('button[type="button"]').trigger('click');

        expect(form).toEqual({
            dogId: 'dog-1',
            startAt: '2026-04-05T08:00',
            endAt: '2026-04-06T10:00',
            currentShoulderHeightCm: 52,
        });
        expect(wrapper.emitted('clear-field-error')).toEqual([
            ['dogId'],
            ['currentShoulderHeightCm'],
            ['startAt'],
            ['endAt'],
        ]);
        expect(wrapper.emitted('submit')).toHaveLength(1);
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
