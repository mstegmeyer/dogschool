<template>
<UCard class='lg:col-span-2'>
    <template #header>
        <h3 class='font-semibold text-slate-800'>
            Kundendaten
        </h3>
    </template>
    <div class='grid grid-cols-1 gap-4 text-sm sm:grid-cols-2'>
        <div>
            <p class='text-xs text-slate-400'>
                Name
            </p>
            <p class='font-medium text-slate-700'>
                {{ customer.name }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                E-Mail
            </p>
            <p class='break-all font-medium text-slate-700'>
                {{ customer.email }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                Straße
            </p>
            <p class='font-medium text-slate-700'>
                {{ customer.address.street || '–' }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                Ort
            </p>
            <p class='font-medium text-slate-700'>
                {{ customer.address.postalCode }} {{ customer.address.city || '–' }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                IBAN
            </p>
            <p class='break-all font-medium text-slate-700'>
                {{ customer.bankAccount.iban || '–' }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                Kontoinhaber
            </p>
            <p class='font-medium text-slate-700'>
                {{ customer.bankAccount.accountHolder || '–' }}
            </p>
        </div>
        <div>
            <p class='text-xs text-slate-400'>
                Registriert
            </p>
            <p class='font-medium text-slate-700'>
                {{ formatDate(customer.createdAt) }}
            </p>
        </div>
    </div>
</UCard>
</template>

<script setup lang="ts">
import type { Customer } from '~/types';

defineProps<{
    customer: Customer,
}>();

const { formatDate } = useHelpers();
</script>
