export default defineNuxtPlugin(() => {
    const auth = useAuth();
    const { syncPushRegistration } = usePushNotifications();

    watch(
        () => ({ token: auth.token.value, role: auth.role.value }),
        async ({ token, role }) => {
            if (!token || role === null) {
                return;
            }

            await syncPushRegistration(role);
        },
        { immediate: true },
    );
});
