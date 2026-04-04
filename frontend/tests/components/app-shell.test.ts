import { nextTick } from 'vue';
import { beforeEach, describe, expect, it } from 'vitest';
import App from '~/app.vue';
import AuthLayout from '~/layouts/auth.vue';
import DefaultLayout from '~/layouts/default.vue';
import AdminLayout from '~/layouts/admin.vue';
import CustomerLayout from '~/layouts/customer.vue';
import AppLogo from '~/components/branding/AppLogo.vue';
import AdminNavigationMenu from '~/modules/admin/AdminNavigationMenu.vue';
import CustomerNavigationMenu from '~/modules/customer/CustomerNavigationMenu.vue';
import {
    installComponentGlobals,
    logoutMock,
    mountComponent,
    navigateToMock,
    routeMockState,
} from './component-test-helpers';

describe('app shell components', () => {
    beforeEach(() => {
        installComponentGlobals();
    });

    it('selects the route-based layout in app.vue', () => {
        installComponentGlobals({ path: '/admin/courses' });

        const wrapper = mountComponent(App);

        expect(wrapper.get('[data-layout="admin"]').exists()).toBe(true);
    });

    it('respects explicit route meta layout in app.vue', () => {
        installComponentGlobals({ path: '/admin', meta: { layout: 'auth' } });

        const wrapper = mountComponent(App);

        expect(wrapper.get('[data-layout="auth"]').exists()).toBe(true);
    });

    it('renders default layout slots unchanged', () => {
        const wrapper = mountComponent(DefaultLayout, {
            slots: {
                default: '<div data-testid="default-slot">Inhalt</div>',
            },
        });

        expect(wrapper.get('[data-testid="default-slot"]').text()).toBe('Inhalt');
    });

    it('renders the auth layout chrome around the slot content', () => {
        const wrapper = mountComponent(AuthLayout, {
            slots: {
                default: '<form data-testid="auth-form">Login</form>',
            },
        });

        expect(wrapper.get('[data-testid="auth-form"]').text()).toBe('Login');
        expect(wrapper.text()).toContain('Hundeschule & Hundehotel');
        expect(wrapper.get('[data-testid="AppLogo-stub"]').exists()).toBe(true);
    });

    it('opens and closes the admin mobile menu and logs out', async () => {
        installComponentGlobals({ path: '/admin/dashboard' });
        const wrapper = mountComponent(AdminLayout, {
            slots: {
                default: '<div>Admin</div>',
            },
        });

        expect(wrapper.find('[data-testid="admin-mobile-menu"]').exists()).toBe(false);

        await wrapper.get('[data-testid="admin-mobile-menu-toggle"]').trigger('click');
        expect(wrapper.get('[data-testid="admin-mobile-menu"]').exists()).toBe(true);

        routeMockState.fullPath = '/admin/contracts';
        await nextTick();
        expect(wrapper.find('[data-testid="admin-mobile-menu"]').exists()).toBe(false);

        await wrapper.get('[data-testid="admin-logout"]').trigger('click');
        expect(logoutMock).toHaveBeenCalled();
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('opens and closes the customer mobile menu and shows the customer name', async () => {
        installComponentGlobals({ path: '/customer/dashboard' });
        const wrapper = mountComponent(CustomerLayout, {
            slots: {
                default: '<div>Kunde</div>',
            },
        });

        expect(wrapper.text()).toContain('Max');
        await wrapper.get('[data-testid="customer-mobile-menu-toggle"]').trigger('click');
        expect(wrapper.get('[data-testid="customer-mobile-menu"]').exists()).toBe(true);

        routeMockState.fullPath = '/customer/calendar';
        await nextTick();
        expect(wrapper.find('[data-testid="customer-mobile-menu"]').exists()).toBe(false);

        await wrapper.get('[data-testid="customer-logout"]').trigger('click');
        expect(logoutMock).toHaveBeenCalled();
        expect(navigateToMock).toHaveBeenCalledWith('/login');
    });

    it('emits navigate from the admin navigation menu when close-on-navigate is enabled', async () => {
        const wrapper = mountComponent(AdminNavigationMenu, {
            props: {
                variant: 'mobile',
                closeOnNavigate: true,
            },
        });

        expect(wrapper.text()).toContain('Dashboard');
        await wrapper.get('[data-testid="nav-link-Dashboard"]').trigger('click');

        expect(wrapper.emitted('navigate')).toHaveLength(1);
    });

    it('renders customer navigation sections and emits navigate on click', async () => {
        const wrapper = mountComponent(CustomerNavigationMenu, {
            props: {
                closeOnNavigate: true,
            },
        });

        expect(wrapper.text()).toContain('Allgemein');
        expect(wrapper.text()).toContain('Hundeschule');
        expect(wrapper.text()).toContain('Hundehotel');

        await wrapper.get('[data-testid="nav-link-Dashboard"]').trigger('click');
        expect(wrapper.emitted('navigate')).toHaveLength(1);
    });

    it('applies size and tone classes in the logo component', () => {
        const wrapper = mountComponent(AppLogo, {
            props: {
                tone: 'on-dark',
                size: 'lg',
            },
        });

        expect(wrapper.get('img').classes()).toContain('invert');
        expect(wrapper.get('img').classes()).toContain('h-14');
    });
});
