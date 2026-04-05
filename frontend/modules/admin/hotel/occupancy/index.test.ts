import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiGetMock,
    hotelOccupancyResponse,
    installAdminGlobals,
    mountHotelOccupancyPage,
} from '~/tests/modules/admin-page-helpers';

describe('admin hotel occupancy page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockResolvedValue(hotelOccupancyResponse);
    });

    it('loads occupancy data and renders room usage details', async () => {
        const wrapper = await mountHotelOccupancyPage();

        expect(apiGetMock).toHaveBeenCalledTimes(1);
        expect(apiGetMock.mock.calls[0]?.[0]).toContain('/api/admin/hotel/occupancy?');
        expect(wrapper.text()).toContain('Waldzimmer');
        expect(wrapper.text()).toContain('11 m²');
        expect(wrapper.text()).toContain('Rex, Luna');
    });
});
