import { beforeEach, describe, expect, it } from 'vitest';
import AdminCourseListMobile from './ListMobile.vue';
import {
    baseArchivedCourse,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCourseListMobile', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the mobile course list and emits archive toggles', async () => {
        const wrapper = mountComponent(AdminCourseListMobile, {
            props: {
                courses: [baseArchivedCourse],
            },
        });

        await wrapper.get('[data-testid="toggle-archive-course-course-archived"]').trigger('click');

        expect(wrapper.text()).toContain('Archiviert');
        expect(wrapper.text()).toContain('Lea');
        expect(wrapper.emitted('toggle-archive')).toHaveLength(1);
    });
});
