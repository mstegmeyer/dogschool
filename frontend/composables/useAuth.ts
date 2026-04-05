import { storeToRefs } from 'pinia';
import { useAuthStore } from '~/stores/auth';
import { resolvePinia } from '~/stores/pinia';

export const useAuth = () => {
    const store = useAuthStore(resolvePinia());
    const {
        token,
        role,
        user,
        isAuthenticated,
        isAdmin,
        isCustomer,
    } = storeToRefs(store);

    return {
        token,
        role,
        user,
        isAuthenticated,
        isAdmin,
        isCustomer,
        persist: store.persist,
        hydrate: store.hydrate,
        loginAdmin: store.loginAdmin,
        loginCustomer: store.loginCustomer,
        register: store.register,
        fetchProfile: store.fetchProfile,
        logout: store.logout,
    };
};
