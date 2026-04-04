import { beforeEach, describe, expect, it } from 'vitest';
import AdminNotificationsList from './NotificationsList.vue';
import {
    installComponentGlobals,
    makeNotification,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminNotificationsList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders admin notifications with pinned and global badges', async () => {
        const wrapper = mountComponent(AdminNotificationsList, {
            props: {
                loading: false,
                notifications: [
                    makeNotification({ isPinned: true, isGlobal: true, pinnedUntil: '2026-04-10' }),
                    makeNotification({ id: 'notification-2', title: 'Zweite Mitteilung', authorName: null }),
                ],
                columns: [
                    { key: 'createdAt', label: 'Datum' },
                    { key: 'pinnedUntil', label: 'Angepinnt' },
                    { key: 'title', label: 'Titel' },
                    { key: 'courses', label: 'Kurse' },
                    { key: 'message', label: 'Text' },
                    { key: 'authorName', label: 'Autor' },
                    { key: 'actions', label: '' },
                ],
                resultSummary: '2 Mitteilungen',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalNotifications: 2,
            },
        });

        await wrapper.get('[data-testid="edit-notification-mobile-notification-1"]').trigger('click');
        await wrapper.get('[data-testid="delete-notification-mobile-notification-1"]').trigger('click');

        expect(wrapper.text()).toContain('Alle Kurse');
        expect(wrapper.text()).toContain('formatted:2026-04-10');
        expect(wrapper.emitted('edit')).toHaveLength(1);
        expect(wrapper.emitted('delete')).toHaveLength(1);
    });
});
