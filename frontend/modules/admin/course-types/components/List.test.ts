import { beforeEach, describe, expect, it } from 'vitest';
import CourseTypesList from './List.vue';
import {
    baseCourse,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CourseTypesList', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the course types list and emits edit and delete actions', async () => {
        const wrapper = mountComponent(CourseTypesList, {
            props: {
                loading: false,
                courseTypes: [baseCourse.type],
                columns: [
                    { key: 'code', label: 'Code' },
                    { key: 'name', label: 'Name' },
                    { key: 'recurrenceKind', label: 'Wiederholung' },
                    { key: 'actions', label: '' },
                ],
            },
        });

        await wrapper.get('[data-testid="edit-course-type-mobile-course-type-1"]').trigger('click');
        await wrapper.get('[data-testid="delete-course-type-mobile-course-type-1"]').trigger('click');

        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.text()).toContain('Wiederkehrend');
        expect(wrapper.emitted('edit')).toHaveLength(1);
        expect(wrapper.emitted('delete')).toHaveLength(1);
    });
});
