import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    apiDelMock,
    apiGetMock,
    apiPostMock,
    installAdminGlobals,
    mountCourseTypesPage,
    recurrenceCourseType,
} from '~/tests/modules/admin-page-helpers';
import { flushPromises } from '~/tests/nuxt/page-test-utils';

describe('admin course types page', () => {
    beforeEach(() => {
        vi.resetModules();
        vi.clearAllMocks();
        installAdminGlobals();
        apiGetMock.mockResolvedValue({ items: [recurrenceCourseType] });
    });

    it('loads course types, validates the form, saves, and deletes', async () => {
        const wrapper = await mountCourseTypesPage();
        const list = wrapper.getComponent({ name: 'CourseTypesList' });
        const modal = wrapper.getComponent({ name: 'CourseTypeFormModal' });

        expect(apiGetMock).toHaveBeenCalledWith('/api/admin/course-types');
        expect(list.props('courseTypes')).toHaveLength(1);

        await wrapper.get('button').trigger('click');
        await modal.vm.$emit('submit');
        await flushPromises();
        expect(modal.props('formError')).toBe('Bitte prüfe die markierten Felder.');

        const form = modal.props('form') as Record<string, string>;
        form.code = 'OBI';
        form.name = 'Obedience';
        form.recurrenceKind = 'ONE_TIME';

        apiPostMock.mockResolvedValue({});
        await modal.vm.$emit('submit');
        await flushPromises();

        expect(apiPostMock).toHaveBeenCalledWith('/api/admin/course-types', {
            code: 'OBI',
            name: 'Obedience',
            recurrenceKind: 'ONE_TIME',
        });

        apiDelMock.mockResolvedValue({});
        await list.vm.$emit('delete', recurrenceCourseType);
        await flushPromises();
        expect(apiDelMock).toHaveBeenCalledWith('/api/admin/course-types/course-type-1');
    });
});
