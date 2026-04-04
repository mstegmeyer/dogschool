import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiDelMock,
    apiGetMock,
    apiPostMock,
    apiPutMock,
    course,
    installAdminGlobals,
    mountNotificationsPage,
    notification,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin notifications page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockImplementation((url: string) => {
            if (url.startsWith('/api/admin/notifications?')) {
                return Promise.resolve({
                    items: [notification],
                    pagination: { total: 1, pages: 1, page: 1, limit: 20 },
                });
            }

            if (url === '/api/admin/courses?archived=false') {
                return Promise.resolve({ items: [course] });
            }

            return Promise.reject(new Error(`Unhandled GET ${url}`));
        });
    });

    it('loads notifications, validates create, updates existing items, and deletes', async () => {
        const wrapper = await mountNotificationsPage();
        const list = wrapper.getComponent({ name: 'NotificationsList' });
        const modal = wrapper.getComponent({ name: 'NotificationFormModal' });

        expect(list.props('notifications')).toHaveLength(1);
        expect(modal.props('courseOptions')).toEqual([{ label: 'Agility · Dienstag 10:00', value: 'course-1' }]);

        await wrapper.get('button').trigger('click');
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, unknown>;
        form.isGlobal = true;
        form.title = 'Wetterwarnung';
        form.message = 'Heute bitte wetterfest kommen.';
        form.pinnedUntil = '2026-04-15';

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/notifications', {
            title: 'Wetterwarnung',
            message: 'Heute bitte wetterfest kommen.',
            courseIds: [],
            pinnedUntil: '2026-04-15T23:59:59',
        });

        apiPutMock.mockResolvedValue({});
        await list.vm.$emit('edit', notification);
        await flushPromises();

        const editForm = modal.props('form') as Record<string, unknown>;
        editForm.message = 'Bitte zehn Minuten früher kommen.';
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPutMock).toHaveBeenCalledWith('/api/admin/notifications/notification-1', {
            title: 'Hinweis',
            message: 'Bitte zehn Minuten früher kommen.',
            courseIds: ['course-1'],
            pinnedUntil: '2026-04-10T23:59:59',
        });

        apiDelMock.mockResolvedValue({});
        await list.vm.$emit('delete', notification);
        await flushPromises();
        expect(apiDelMock).toHaveBeenCalledWith('/api/admin/notifications/notification-1');
    });
});
