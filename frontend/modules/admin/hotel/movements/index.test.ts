import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    hotelMovementsResponse,
    installAdminGlobals,
    mountHotelMovementsPage,
} from '~/tests/modules/admin-page-helpers';

describe('admin hotel movements page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockResolvedValue(hotelMovementsResponse);
    });

    it('loads arrivals and departures for the selected range', async () => {
        const wrapper = await mountHotelMovementsPage();

        expect(apiGetMock).toHaveBeenCalledTimes(1);
        expect(apiGetMock.mock.calls[0]?.[0]).toContain('/api/admin/hotel/movements?');
        expect(wrapper.text()).toContain('Rex · Max');
        expect(wrapper.text()).toContain('Waldzimmer');
    });
});
