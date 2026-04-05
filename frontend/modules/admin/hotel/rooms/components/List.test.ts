import { beforeEach, describe, expect, it } from 'vitest';
import RoomList from './List.vue';
import { installComponentGlobals, mountComponent } from '~/tests/components/component-test-helpers';
import { room } from '~/tests/modules/admin-page-helpers';

describe('RoomList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders hotel rooms and emits edit for the selected room', async () => {
        const wrapper = mountComponent(RoomList, {
            props: {
                loading: false,
                rooms: [room],
            },
        });

        expect(wrapper.text()).toContain('Waldzimmer');
        expect(wrapper.text()).toContain('14 m²');

        await wrapper.get('[data-testid="edit-room-room-1"]').trigger('click');

        expect(wrapper.emitted('edit')?.[0]).toEqual([room]);
    });
});
