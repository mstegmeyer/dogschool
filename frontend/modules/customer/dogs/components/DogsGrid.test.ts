import { beforeEach, describe, expect, it } from 'vitest';
import DogsGrid from './DogsGrid.vue';
import {
    baseDog,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('DogsGrid', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('covers empty and populated dog states in the dogs grid', async () => {
        const wrapper = mountComponent(DogsGrid, {
            props: {
                loading: false,
                dogs: [],
            },
        });

        expect(wrapper.get('[data-testid="dogs-empty-state"]').exists()).toBe(true);
        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('add')).toHaveLength(1);

        await wrapper.setProps({ dogs: [baseDog] });
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.text()).toContain('Mix');
    });
});
