import { beforeEach, describe, expect, it } from 'vitest';
import ProfileFormCard from './ProfileFormCard.vue';
import {
    installComponentGlobals,
    mountComponent,
    profileForm,
} from '~/tests/components/component-test-helpers';

describe('ProfileFormCard', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders the profile form card and emits field clearing and submit', async () => {
        const wrapper = mountComponent(ProfileFormCard, {
            props: {
                loading: false,
                form: { ...profileForm },
                fieldErrors: { name: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input').setValue('Maximilian');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Passwort ändern');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['name']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
