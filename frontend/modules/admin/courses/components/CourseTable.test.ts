import { beforeEach, describe, expect, it } from 'vitest';
import AdminCourseTable from './CourseTable.vue';
import {
    baseCourse,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCourseTable', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the course table and exposes dropdown row actions', async () => {
        const wrapper = mountComponent(AdminCourseTable, {
            props: {
                courses: [baseCourse],
                sort: { column: 'dayOfWeek', direction: 'asc' },
            },
        });

        await wrapper.get('button').trigger('click');
        await wrapper.get('button:nth-of-type(2)').trigger('click');

        expect(wrapper.text()).toContain('Dienstag');
        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.emitted('edit')).toHaveLength(1);
    });
});
