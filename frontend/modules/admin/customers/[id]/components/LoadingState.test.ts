import { beforeEach, describe, expect, it } from 'vitest';
import CustomerLoadingState from './LoadingState.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('CustomerLoadingState', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the admin customer loading state', () => {
        const wrapper = mountComponent(CustomerLoadingState);

        expect(wrapper.text()).toContain('loading');
    });
});
