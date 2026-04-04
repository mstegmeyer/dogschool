import { beforeEach, describe, expect, it } from 'vitest';
import CustomerCoursesLoadingState from './LoadingState.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerCoursesLoadingState', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the customer courses loading state skeletons', () => {
        const wrapper = mountComponent(CustomerCoursesLoadingState);

        expect(wrapper.findAll('textarea').length).toBe(0);
        expect(wrapper.text()).toContain('skeleton');
    });
});
