<template>
<div class='space-y-6'>
    <div>
        <h1 class='text-2xl font-bold text-slate-800'>
            Preise
        </h1>
        <p class='mt-1 text-sm text-slate-500'>
            Diese Werte steuern die automatische Preisberechnung für Schule, HUTA und Hundehotel.
        </p>
    </div>

    <UCard v-if='form'>
        <form class='space-y-8' @submit.prevent='savePricing'>
            <section class='space-y-4'>
                <div>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        Hundeschule
                    </h2>
                    <p class='text-sm text-slate-500'>
                        Monatspreise je Trainingshäufigkeit.
                    </p>
                </div>
                <div class='grid gap-4 md:grid-cols-2 xl:grid-cols-3'>
                    <UFormGroup label='1x / Woche' :error='fieldErrors.schoolOneCoursePrice'>
                        <UInput v-model='form.schoolOneCoursePrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='2x / Woche je Termin' :error='fieldErrors.schoolTwoCoursesUnitPrice'>
                        <UInput v-model='form.schoolTwoCoursesUnitPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='3x / Woche je Termin' :error='fieldErrors.schoolThreeCoursesUnitPrice'>
                        <UInput v-model='form.schoolThreeCoursesUnitPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='4x / Woche je Termin' :error='fieldErrors.schoolFourCoursesUnitPrice'>
                        <UInput v-model='form.schoolFourCoursesUnitPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='5+ / Woche je Termin' :error='fieldErrors.schoolAdditionalCoursesUnitPrice'>
                        <UInput v-model='form.schoolAdditionalCoursesUnitPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Anmeldegebühr' :error='fieldErrors.schoolRegistrationFee'>
                        <UInput v-model='form.schoolRegistrationFee' type='number' step='0.01' />
                    </UFormGroup>
                </div>
            </section>

            <section class='space-y-4'>
                <div>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        HUTA & Hotel
                    </h2>
                    <p class='text-sm text-slate-500'>
                        Automatisch berechnete Basispreise und Zuschläge.
                    </p>
                </div>
                <div class='grid gap-4 md:grid-cols-2 xl:grid-cols-3'>
                    <UFormGroup label='HUTA Nebensaison / Tag' :error='fieldErrors.daycareOffSeasonDailyPrice'>
                        <UInput v-model='form.daycareOffSeasonDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='HUTA Hauptsaison / Tag' :error='fieldErrors.daycarePeakSeasonDailyPrice'>
                        <UInput v-model='form.daycarePeakSeasonDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Hotel / Kalendertag' :error='fieldErrors.hotelDailyPrice'>
                        <UInput v-model='form.hotelDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Servicepauschale / Aufenthalt' :error='fieldErrors.hotelServiceFee'>
                        <UInput v-model='form.hotelServiceFee' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Reiseschutz Tage 1-7' :error='fieldErrors.hotelTravelProtectionBaseFee'>
                        <UInput v-model='form.hotelTravelProtectionBaseFee' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Reiseschutz ab Tag 8 / Tag' :error='fieldErrors.hotelTravelProtectionAdditionalDailyFee'>
                        <UInput v-model='form.hotelTravelProtectionAdditionalDailyFee' type='number' step='0.01' />
                    </UFormGroup>
                </div>
            </section>

            <section class='space-y-4'>
                <div>
                    <h2 class='text-lg font-semibold text-slate-800'>
                        Referenzpreise für manuelle Extras
                    </h2>
                    <p class='text-sm text-slate-500'>
                        Diese Werte werden nicht automatisch berechnet, dienen aber als Orientierung im Admin-Review.
                    </p>
                </div>
                <div class='grid gap-4 md:grid-cols-2 xl:grid-cols-3'>
                    <UFormGroup label='Einzelzimmer HUTA / Tag' :error='fieldErrors.hotelSingleRoomDaycareDailyPrice'>
                        <UInput v-model='form.hotelSingleRoomDaycareDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Einzelzimmer Hotel / Tag' :error='fieldErrors.hotelSingleRoomHotelDailyPrice'>
                        <UInput v-model='form.hotelSingleRoomHotelDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Läufigkeit / Tag' :error='fieldErrors.hotelHeatCycleDailyPrice'>
                        <UInput v-model='form.hotelHeatCycleDailyPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Medikamentengabe / Gabe' :error='fieldErrors.hotelMedicationPerAdministrationPrice'>
                        <UInput v-model='form.hotelMedicationPerAdministrationPrice' type='number' step='0.01' />
                    </UFormGroup>
                    <UFormGroup label='Futtermittelzusätze / Gabe' :error='fieldErrors.hotelSupplementPerAdministrationPrice'>
                        <UInput v-model='form.hotelSupplementPerAdministrationPrice' type='number' step='0.01' />
                    </UFormGroup>
                </div>
            </section>

            <section class='space-y-4'>
                <div class='flex items-center justify-between gap-3'>
                    <div>
                        <h2 class='text-lg font-semibold text-slate-800'>
                            Hauptsaisonen HUTA
                        </h2>
                        <p class='text-sm text-slate-500'>
                            Diese Zeiträume steuern automatisch den HUTA-Hauptsaisonpreis.
                        </p>
                    </div>
                    <UButton variant='soft' label='Zeitraum hinzufügen' @click='addPeakSeason' />
                </div>

                <UAlert
                    v-if='fieldErrors.hotelPeakSeasons'
                    color='red'
                    variant='soft'
                    icon='i-heroicons-exclamation-triangle'
                    :title='fieldErrors.hotelPeakSeasons'
                />

                <div class='space-y-3'>
                    <div
                        v-for='(season, index) in form.hotelPeakSeasons'
                        :key='season.id || index'
                        class='grid gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4 md:grid-cols-[1fr_1fr_auto]'
                    >
                        <UFormGroup label='Start'>
                            <UInput v-model='season.startDate' type='date' />
                        </UFormGroup>
                        <UFormGroup label='Ende'>
                            <UInput v-model='season.endDate' type='date' />
                        </UFormGroup>
                        <div class='flex items-end'>
                            <UButton
                                color='red'
                                variant='soft'
                                label='Entfernen'
                                :disabled='form.hotelPeakSeasons.length <= 1'
                                @click='removePeakSeason(index)'
                            />
                        </div>
                    </div>
                </div>
            </section>

            <UAlert
                v-if='formError'
                color='red'
                variant='soft'
                icon='i-heroicons-exclamation-triangle'
                :title='formError'
            />

            <div class='flex justify-end'>
                <UButton type='submit' :loading='saving' label='Preise speichern' />
            </div>
        </form>
    </UCard>
</div>
</template>

<script setup lang="ts">
import type { PricingConfig } from '~/types';

definePageMeta({ layout: 'admin' });

const api = useApi();
const toast = useToast();
const { formError, fieldErrors, clearFormErrors, applyApiError } = useFormFeedback();

const form = ref<PricingConfig | null>(null);
const saving = ref(false);

function clonePricingConfig(config: PricingConfig): PricingConfig {
    return {
        ...config,
        hotelPeakSeasons: config.hotelPeakSeasons.map(season => ({ ...season })),
    };
}

function addPeakSeason(): void {
    if (!form.value) {
        return;
    }

    form.value.hotelPeakSeasons.push({
        startDate: '',
        endDate: '',
    });
}

function removePeakSeason(index: number): void {
    if (!form.value || form.value.hotelPeakSeasons.length <= 1) {
        return;
    }

    form.value.hotelPeakSeasons.splice(index, 1);
}

async function loadPricing(): Promise<void> {
    form.value = clonePricingConfig(await api.get<PricingConfig>('/api/admin/pricing'));
}

async function savePricing(): Promise<void> {
    if (!form.value) {
        return;
    }

    clearFormErrors();
    saving.value = true;
    try {
        const updated = await api.put<PricingConfig>('/api/admin/pricing', {
            ...form.value,
            hotelPeakSeasons: form.value.hotelPeakSeasons.map(season => ({
                startDate: season.startDate,
                endDate: season.endDate,
            })),
        });
        form.value = clonePricingConfig(updated);
        toast.add({ title: 'Preise gespeichert', color: 'green' });
    } catch (cause) {
        applyApiError(cause, 'Die Preise konnten nicht gespeichert werden.');
    } finally {
        saving.value = false;
    }
}

onMounted(() => {
    void loadPricing();
});
</script>
