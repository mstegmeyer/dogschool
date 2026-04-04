import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    installCustomerGlobals,
    mountNotificationsPage,
    notification,
} from './customer-page-helpers';

describe('customer notifications page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installCustomerGlobals();
        apiGetMock.mockResolvedValue({ items: [notification] });
    });

    it('loads notifications and forwards them to the list component', async () => {
        const wrapper = await mountNotificationsPage();
        expect(wrapper.getComponent({ name: 'NotificationsList' }).props('notifications')).toHaveLength(1);
    });
});
