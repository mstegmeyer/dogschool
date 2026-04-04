import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCourseGroupSection from './CourseGroupSection.vue';
import {
    installComponentGlobals,
    makeCourseGroup,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCourseGroupSection', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders course groups and emits a subscription action', async () => {
        const wrapper = mountComponent(CustomerCourseGroupSection, {
            props: {
                group: makeCourseGroup(),
                variant: 'available',
                subscribedIds: new Set<string>(),
            },
        });

        await wrapper.get('[data-testid="course-available-subscription-action-course-1"]').trigger('click');

        expect(wrapper.text()).toContain('Dienstag');
        expect(wrapper.text()).toContain('Abonnieren');
        expect(wrapper.emitted('subscribe')).toHaveLength(1);
    });
});
