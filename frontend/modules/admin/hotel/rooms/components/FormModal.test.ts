import { beforeEach, describe, expect, it } from 'vitest';
import RoomFormModal from './FormModal.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';

describe('RoomFormModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders create mode and emits form interactions', async () => {
        const wrapper = mountComponent(RoomFormModal, {
            props: {
                modelValue: true,
                editing: false,
                form: { name: '', squareMeters: 0 },
                fieldErrors: { name: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        expect(wrapper.text()).toContain('Raum anlegen');

        await wrapper.get('[data-testid="room-name"]').setValue('Wiesenblick');
        await wrapper.get('[data-testid="room-square-meters"]').setValue('16');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['name']);
        expect(wrapper.emitted('clear-field-error')?.[1]).toEqual(['squareMeters']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders edit mode and allows closing the modal', async () => {
        const wrapper = mountComponent(RoomFormModal, {
            props: {
                modelValue: true,
                editing: true,
                form: { name: 'Waldzimmer', squareMeters: 12 },
                fieldErrors: {},
                formError: 'Fehler',
                saving: false,
            },
        });

        expect(wrapper.text()).toContain('Raum bearbeiten');
        expect(wrapper.text()).toContain('Fehler');

        await wrapper.get('button[type="button"]').trigger('click');

        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
