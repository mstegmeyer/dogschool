import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCourseDetailModal from './DetailModal.vue';
import {
    baseCourse,
    installComponentGlobals,
    makeCustomerCourseDetail,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCourseDetailModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the customer course detail modal with dates and notification history', async () => {
        const wrapper = mountComponent(CustomerCourseDetailModal, {
            props: {
                modelValue: true,
                course: baseCourse,
                courseDetail: makeCustomerCourseDetail(),
                loading: false,
            },
        });

        await wrapper.get('button[aria-label="Schließen"]').trigger('click');

        expect(wrapper.text()).toContain('Nächste Termine');
        expect(wrapper.text()).toContain('Mitteilungsverlauf');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });
});
