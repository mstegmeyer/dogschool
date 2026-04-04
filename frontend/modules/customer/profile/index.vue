<template>
<div class='space-y-6'>
    <h1 class='text-2xl font-bold text-slate-800 mb-6'>
        Mein Profil
    </h1>

    <ProfileFormCard
        :loading='loading'
        :form='form'
        :field-errors='fieldErrors'
        :form-error='formError'
        :saving='saving'
        @submit='saveProfile'
        @clear-field-error='clearFieldError'
    />

    <NotificationSettingsCard
        :loading='loading'
        :badge-color='notificationBadgeColor'
        :status-label='notificationStatusLabel'
        :alert-color='notificationAlertColor'
        :status-title='notificationStatusTitle'
        :status-description='notificationStatusDescription'
        :can-enable='canEnableNotifications'
        :can-disable='canDisableNotifications'
        :saving='notificationSaving'
        @enable='enableNotifications'
        @disable='disableNotifications'
    />
</div>
</template>

<script setup lang="ts">
import type { ProfileUpdatePayload } from '~/types';
import NotificationSettingsCard from './components/NotificationSettingsCard.vue';
import ProfileFormCard from './components/ProfileFormCard.vue';

definePageMeta({ layout: 'customer' });

const { user, fetchProfile } = useAuth();
const api = useApi();
const toast = useToast();
const {
    pushStatus,
    pushError,
    refreshStatus,
    enablePush,
    disablePush,
} = usePushNotifications();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError } = useFormFeedback();

const saving = ref(false);
const notificationSaving = ref(false);
const loading = ref(true);

const form = reactive({
    name: '',
    email: '',
    password: '',
    address: { street: '', postalCode: '', city: '' },
    bankAccount: { iban: '', bic: '', accountHolder: '' },
});

const notificationStatusLabel = computed(() => {
    switch (pushStatus.value) {
        case 'enabled':
            return 'Aktiv';
        case 'install-required':
            return 'Home-Bildschirm';
        case 'blocked':
            return 'Blockiert';
        case 'available':
            return 'Verfügbar';
        case 'error':
            return 'Fehler';
        default:
            return 'Nicht verfügbar';
    }
});

const notificationStatusTitle = computed(() => {
    switch (pushStatus.value) {
        case 'enabled':
            return 'Push-Mitteilungen sind aktiv';
        case 'install-required':
            return 'Installation auf dem Home-Bildschirm erforderlich';
        case 'blocked':
            return 'Benachrichtigungen wurden blockiert';
        case 'available':
            return 'Benachrichtigungen können aktiviert werden';
        case 'error':
            return 'Benachrichtigungen konnten nicht eingerichtet werden';
        default:
            return 'Web Push wird auf diesem Gerät nicht unterstützt';
    }
});

const notificationStatusDescription = computed(() => {
    if (pushError.value) {
        return pushError.value;
    }

    switch (pushStatus.value) {
        case 'enabled':
            return 'Dieses Gerät ist für Push-Mitteilungen registriert.';
        case 'install-required':
            return 'Auf dem iPhone funktioniert Web Push nur in der zum Home-Bildschirm hinzugefügten App.';
        case 'blocked':
            return 'Bitte erlaube Benachrichtigungen in den Browser- oder System-Einstellungen.';
        case 'available':
            return 'Du kannst Push-Mitteilungen für dieses Gerät direkt hier aktivieren.';
        case 'error':
            return 'Bitte versuche es erneut. Falls das Problem bleibt, melde dich kurz bei uns.';
        default:
            return 'Dieses Gerät oder dieser Browser unterstützt Web Push aktuell nicht.';
    }
});

const notificationBadgeColor = computed(() => {
    switch (pushStatus.value) {
        case 'enabled':
            return 'green';
        case 'install-required':
            return 'amber';
        case 'blocked':
        case 'error':
            return 'red';
        case 'available':
            return 'blue';
        default:
            return 'gray';
    }
});

const notificationAlertColor = computed(() => {
    switch (pushStatus.value) {
        case 'enabled':
            return 'green';
        case 'install-required':
            return 'amber';
        case 'blocked':
        case 'error':
            return 'red';
        case 'available':
            return 'blue';
        default:
            return 'gray';
    }
});

const canEnableNotifications = computed(() => ['available', 'install-required', 'error'].includes(pushStatus.value));
const canDisableNotifications = computed(() => pushStatus.value === 'enabled');

async function saveProfile(): Promise<void> {
    clearFormErrors();
    if (!form.name.trim()) {setFieldError('name', 'Bitte einen Namen angeben.');}
    if (!form.email.trim()) {setFieldError('email', 'Bitte eine E-Mail-Adresse angeben.');}
    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    saving.value = true;
    try {
        const payload: ProfileUpdatePayload = {
            name: form.name || undefined,
            email: form.email || undefined,
            address: form.address,
            bankAccount: form.bankAccount,
        };
        if (form.password) {payload.password = form.password;}
        await api.put('/api/customer/me', payload);
        await fetchProfile();
        toast.add({ title: 'Profil gespeichert', color: 'green' });
    } catch (cause) {
        applyApiError(cause, 'Das Profil konnte nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

async function enableNotifications(): Promise<void> {
    notificationSaving.value = true;
    try {
        const enabled = await enablePush('customer');
        if (enabled) {
            toast.add({ title: 'Benachrichtigungen aktiviert', color: 'green' });
        } else {
            toast.add({ title: notificationStatusTitle.value, description: notificationStatusDescription.value, color: 'amber' });
        }
    } catch {
        toast.add({ title: 'Benachrichtigungen konnten nicht aktiviert werden', color: 'red' });
    } finally {
        notificationSaving.value = false;
    }
}

async function disableNotifications(): Promise<void> {
    notificationSaving.value = true;
    try {
        const disabled = await disablePush('customer');
        toast.add({
            title: disabled ? 'Benachrichtigungen deaktiviert' : 'Keine aktive Registrierung gefunden',
            color: disabled ? 'green' : 'amber',
        });
    } catch {
        toast.add({ title: 'Benachrichtigungen konnten nicht deaktiviert werden', color: 'red' });
    } finally {
        notificationSaving.value = false;
    }
}

onMounted(async () => {
    try {
        await Promise.all([
            fetchProfile(),
            refreshStatus(),
        ]);

        if (user.value) {
            form.name = user.value.name || '';
            form.email = user.value.email || '';
            form.address.street = user.value.address?.street || '';
            form.address.postalCode = user.value.address?.postalCode || '';
            form.address.city = user.value.address?.city || '';
            form.bankAccount.iban = user.value.bankAccount?.iban || '';
            form.bankAccount.bic = user.value.bankAccount?.bic || '';
            form.bankAccount.accountHolder = user.value.bankAccount?.accountHolder || '';
        }
    } finally {
        loading.value = false;
    }
});
</script>
