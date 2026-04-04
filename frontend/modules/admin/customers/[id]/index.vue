<template>
<div>
    <CustomerLoadingState v-if='loading' />

    <div v-else-if='customer'>
        <div class='flex items-center gap-3 mb-6'>
            <UButton
                icon='i-heroicons-arrow-left'
                variant='ghost'
                size='sm'
                to='/admin/customers'
            />
            <h1 class='text-2xl font-bold text-slate-800'>
                {{ customer.name }}
            </h1>
        </div>

        <div class='grid grid-cols-1 lg:grid-cols-3 gap-6'>
            <CustomerInfoCard :customer='customer' />

            <CreditAdjustCard
                :balance='creditBalance'
                :adjust-amount='adjustAmount'
                :adjust-description='adjustDescription'
                :amount-error="errorFor('amount')"
                :description-error="errorFor('description')"
                :form-error='formError'
                :saving='adjusting'
                @submit='adjustCredits'
                @clear-field-error='clearFieldError'
                @update:adjust-amount='adjustAmount = $event'
                @update:adjust-description='adjustDescription = $event'
            />
        </div>

        <UCard class='mt-6'>
            <template #header>
                <h3 class='font-semibold text-slate-800'>
                    Guthaben-Verlauf
                </h3>
            </template>
            <CreditHistoryList :entries='creditHistory' :columns='creditColumns' />
        </UCard>
    </div>
</div>
</template>

<script setup lang="ts">
import type { Customer, CreditTransaction } from '~/types';
import CreditHistoryList from '~/components/credits/CreditHistoryList.vue';
import CreditAdjustCard from './components/CreditAdjustCard.vue';
import CustomerInfoCard from './components/CustomerInfoCard.vue';
import CustomerLoadingState from './components/LoadingState.vue';

definePageMeta({ layout: 'admin' });

const route = useRoute();
const api = useApi();
const toast = useToast();
const { formError, fieldErrors, clearFormErrors, clearFieldError, setFieldError, setFormError, applyApiError, errorFor } = useFormFeedback();

const customer = ref<Customer | null>(null);
const creditBalance = ref(0);
const creditHistory = ref<CreditTransaction[]>([]);
const adjustAmount = ref<number | null>(null);
const adjustDescription = ref('');
const loading = ref(true);
const adjusting = ref(false);

const creditColumns = [
    { key: 'amount', label: 'Betrag' },
    { key: 'type', label: 'Typ' },
    { key: 'description', label: 'Beschreibung' },
    { key: 'createdAt', label: 'Datum' },
];

interface AdminCreditsResponse {
    balance: number,
    items: CreditTransaction[],
}

async function loadCredits(): Promise<void> {
    const res = await api.get<AdminCreditsResponse>(
        `/api/admin/credits?customerId=${route.params.id}`,
    );
    creditBalance.value = res.balance;
    creditHistory.value = res.items;
}

async function adjustCredits(): Promise<void> {
    clearFormErrors();
    if (adjustAmount.value === null || adjustAmount.value === 0) {setFieldError('amount', 'Bitte eine Korrektur ungleich 0 angeben.');}
    if (!adjustDescription.value.trim()) {setFieldError('description', 'Bitte eine Beschreibung angeben.');}
    if (Object.keys(fieldErrors.value).length > 0) {
        setFormError('Bitte prüfe die markierten Felder.');
        return;
    }

    adjusting.value = true;
    try {
        await api.post('/api/admin/credits/adjust', {
            customerId: route.params.id,
            amount: adjustAmount.value,
            description: adjustDescription.value,
        });
        adjustAmount.value = null;
        adjustDescription.value = '';
        toast.add({ title: 'Guthaben angepasst', color: 'green' });
        await loadCredits();
    } catch (cause) {
        applyApiError(cause, 'Die Korrektur konnte nicht gespeichert werden.');
    } finally {
        adjusting.value = false;
    }
}

onMounted(async () => {
    try {
        const [cust] = await Promise.all([
            api.get<Customer>(`/api/admin/customers/${route.params.id}`),
            loadCredits(),
        ]);
        customer.value = cust;
    } finally {
        loading.value = false;
    }
});
</script>
