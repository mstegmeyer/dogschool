<template>
<NuxtLayout :name='layoutName'>
    <NuxtPage />
</NuxtLayout>
</template>

<script setup lang="ts">
const route = useRoute();

const layoutName = computed(() => {
    if (typeof route.meta.layout === 'string') {
        return route.meta.layout;
    }

    if (route.path.startsWith('/admin')) {
        return 'admin';
    }

    if (route.path.startsWith('/customer')) {
        return 'customer';
    }

    if (route.path === '/login' || route.path === '/register') {
        return 'auth';
    }

    return 'default';
});
</script>
