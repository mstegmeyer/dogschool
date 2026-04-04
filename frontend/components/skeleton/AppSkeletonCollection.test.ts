import { beforeEach, describe, expect, it } from 'vitest';
import AppSkeletonCollection from './AppSkeletonCollection.vue';
import {
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AppSkeletonCollection', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('renders configurable skeleton collection cards and desktop rows', () => {
        const wrapper = mountComponent(AppSkeletonCollection, {
            props: {
                mobileCards: 2,
                desktopRows: 3,
                desktopColumns: 4,
                metaColumns: 2,
                showActions: true,
            },
        });

        expect(wrapper.findAll('[data-testid="skeleton-mobile-card"]')).toHaveLength(2);
        expect(wrapper.findAll('[data-testid="skeleton-desktop-row"]')).toHaveLength(3);
    });
});
