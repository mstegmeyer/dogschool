import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    apiPostMock,
    apiPutMock,
    installAdminGlobals,
    mountHotelRoomsPage,
    room,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin hotel rooms page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockResolvedValue({ items: [room] });
    });

    it('loads rooms, validates form data, creates, and updates rooms', async () => {
        const wrapper = await mountHotelRoomsPage();
        const list = wrapper.getComponent({ name: 'RoomList' });
        const modal = wrapper.getComponent({ name: 'RoomFormModal' });

        expect(list.props('rooms')).toHaveLength(1);

        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, any>;
        form.name = 'Wiesenblick';
        form.squareMeters = 16;

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/hotel/rooms', {
            name: 'Wiesenblick',
            squareMeters: 16,
        });

        await list.vm.$emit('edit', room);
        await flushPromises();
        form.name = 'Waldsuite';
        form.squareMeters = 18;

        apiPutMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(apiPutMock).toHaveBeenCalledWith('/api/admin/hotel/rooms/room-1', {
            name: 'Waldsuite',
            squareMeters: 18,
        });
    });
});
