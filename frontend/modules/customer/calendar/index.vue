<template>
<div>
    <CalendarToolbar
        title='Kalender'
        :view-mode='viewMode'
        :range-label='calendarRangeLabel'
        @update:view-mode='viewMode = $event'
        @prev='prev'
        @next='next'
        @today='goToday'
    >
        <template #title-actions>
            <UButton
                color='blue'
                variant='soft'
                size='sm'
                icon='i-heroicons-link'
                label='Abonnieren'
                @click='showCalendarSubscription = true'
            />
        </template>
    </CalendarToolbar>

    <CustomerCalendarSubscriptionModal
        v-model='showCalendarSubscription'
        :calendar-subscription-url='calendarSubscriptionUrl'
        :calendar-subscription-webcal-url='calendarSubscriptionWebcalUrl'
        @copy='copyCalendarUrl'
        @open='openCalendarUrl'
    />

    <CustomerCalendarBookingModal
        v-model='bookingModalOpen'
        :course-date='bookingCourseDate'
        :dogs='dogs'
        :booking-dog-id='bookingDogId'
        :selected-dog-id='selectedBookingDogId'
        :booking-in-flight='bookingInFlight'
        @update:booking-dog-id='bookingDogId = $event'
        @cancel='closeBookingModal'
        @confirm='confirmBooking'
    />

    <AppSkeletonCalendar
        v-if='loading'
        :days="viewMode === 'week' ? 7 : 1"
        :cards-per-day="viewMode === 'week' ? 2 : 4"
        :columns-class="viewMode === 'week' ? 'grid grid-cols-1 gap-3 lg:grid-cols-7' : 'grid grid-cols-1 gap-3'"
        :day-class="viewMode === 'week' ? 'min-h-[180px]' : ''"
    />
    <AppCalendarTimeline
        v-else
        :days='visibleDays'
        :view-mode='viewMode'
        empty-label='–'
        :event-class='calendarCardClass'
    >
        <template #event='{ courseDate, condensed }'>
            <CustomerCalendarEventCard
                :course-date='courseDate'
                :condensed='condensed'
                :dogs='dogs'
                @open-booking='openBookingModal'
                @cancel-booking='cancelBooking'
            />
        </template>
    </AppCalendarTimeline>
</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { ApiListResponse, CalendarSubscriptionResponse, CourseDate, Dog } from '~/types';
import CustomerCalendarBookingModal from './components/BookingModal.vue';
import CustomerCalendarEventCard from './components/EventCard.vue';
import CustomerCalendarSubscriptionModal from './components/SubscriptionModal.vue';
import CalendarToolbar from '~/components/calendar/CalendarToolbar.vue';
import { useCalendarView } from '~/composables/useCalendarView';

const api = useApi();
const toast = useToast();
const runtimeConfig = useRuntimeConfig();
const { formatDate } = useHelpers();

const courseDates = ref<CourseDate[]>([]);
const dogs = ref<Dog[]>([]);
const calendarSubscriptionPath = ref('');
const showCalendarSubscription = ref(false);
const showBookingModal = ref(false);
const bookingCourseDate = ref<CourseDate | null>(null);
const bookingDogId = ref('');
const bookingInFlight = ref(false);
const loading = ref(true);

const {
    viewMode,
    currentMonday,
    weekStart,
    weekEnd,
    visibleDays,
    prev,
    next,
    goToday,
} = useCalendarView(courseDates);

const calendarRangeLabel = computed(() => (
    viewMode.value === 'day'
        ? formatDate(visibleDays.value[0]?.date ?? weekStart.value)
        : `${formatDate(weekStart.value)} – ${formatDate(weekEnd.value)}`
));

const calendarSubscriptionUrl = computed(() => {
    if (!calendarSubscriptionPath.value) {return '';}

    const baseUrl = runtimeConfig.public.apiBaseUrl || (import.meta.client ? window.location.origin : '');
    if (!baseUrl) {return calendarSubscriptionPath.value;}

    try {
        return new URL(calendarSubscriptionPath.value, baseUrl).toString();
    } catch {
        return calendarSubscriptionPath.value;
    }
});

const calendarSubscriptionWebcalUrl = computed(() => (
    calendarSubscriptionUrl.value.replace(/^https?/, 'webcal')
));

const selectedBookingDogId = computed(() => {
    if (dogs.value.length === 0) {return '';}
    if (dogs.value.length === 1) {return dogs.value[0]?.id ?? '';}
    return bookingDogId.value;
});

const bookingModalOpen = computed({
    get: () => showBookingModal.value,
    set: (open: boolean) => {
        if (open) {
            showBookingModal.value = true;
            return;
        }

        closeBookingModal();
    },
});

function calendarCardClass(courseDate: CourseDate): string {
    if (courseDate.cancelled) {return 'bg-red-50 border-red-200 opacity-60';}
    if (courseDate.booked) {return 'bg-komm-50/90 border-komm-200';}
    return 'bg-white/95 border-slate-200';
}

function openBookingModal(courseDate: CourseDate): void {
    if (dogs.value.length === 0 || courseDate.cancelled || courseDate.booked || courseDate.bookingWindowClosed) {return;}

    bookingCourseDate.value = courseDate;
    bookingDogId.value = dogs.value.length === 1 ? (dogs.value[0]?.id ?? '') : '';
    showBookingModal.value = true;
}

function closeBookingModal(): void {
    if (bookingInFlight.value) {return;}

    showBookingModal.value = false;
    bookingCourseDate.value = null;
    bookingDogId.value = '';
}

async function bookDate(courseDate: CourseDate, dogId = selectedBookingDogId.value): Promise<boolean> {
    if (!dogId) {
        toast.add({ title: 'Bitte zuerst einen Hund auswählen.', color: 'red' });
        return false;
    }

    try {
        await api.post(`/api/customer/calendar/course-dates/${courseDate.id}/book`, { dogId });
        toast.add({ title: 'Termin gebucht', color: 'green' });
        await loadCalendar();
        return true;
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Buchung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' });
        return false;
    }
}

async function confirmBooking(): Promise<void> {
    if (!bookingCourseDate.value || !selectedBookingDogId.value) {return;}

    bookingInFlight.value = true;
    try {
        const didBook = await bookDate(bookingCourseDate.value, selectedBookingDogId.value);
        if (didBook) {
            bookingInFlight.value = false;
            closeBookingModal();
            return;
        }
    } finally {
        bookingInFlight.value = false;
    }
}

async function cancelBooking(courseDate: CourseDate): Promise<void> {
    if (!courseDate.bookings?.length) {return;}

    const dogId = courseDate.bookings[0]?.dogId;
    if (!dogId) {return;}

    try {
        await api.del(`/api/customer/calendar/course-dates/${courseDate.id}/book?dogId=${dogId}`);
        toast.add({ title: 'Buchung storniert', color: 'amber' });
        await loadCalendar();
    } catch (cause) {
        toast.add({ title: extractApiErrorMessage(cause, 'Die Stornierung konnte nicht gespeichert werden.', { preferFieldSummary: false }), color: 'red' });
    }
}

async function loadCalendar(): Promise<void> {
    loading.value = true;
    try {
        const response = await api.get<ApiListResponse<CourseDate>>(`/api/customer/calendar?week=${currentMonday.value}`);
        courseDates.value = response.items;
    } finally {
        loading.value = false;
    }
}

async function loadCalendarSubscription(): Promise<void> {
    const response = await api.get<CalendarSubscriptionResponse>('/api/customer/calendar/subscription');
    calendarSubscriptionPath.value = response.path;
}

async function copyCalendarUrl(): Promise<void> {
    if (!calendarSubscriptionUrl.value || !navigator.clipboard) {return;}

    try {
        await navigator.clipboard.writeText(calendarSubscriptionUrl.value);
        toast.add({ title: 'Kalender-Link kopiert', color: 'green' });
    } catch {
        toast.add({ title: 'Link konnte nicht kopiert werden', color: 'red' });
    }
}

function openCalendarUrl(): void {
    if (!calendarSubscriptionWebcalUrl.value) {return;}
    window.location.href = calendarSubscriptionWebcalUrl.value;
}

watch(currentMonday, () => {
    void loadCalendar();
});

watch(showBookingModal, (open) => {
    if (open || bookingInFlight.value) {return;}

    bookingCourseDate.value = null;
    bookingDogId.value = '';
});

onMounted(async () => {
    const [, dogResponse] = await Promise.all([
        loadCalendar(),
        api.get<ApiListResponse<Dog>>('/api/customer/dogs'),
        loadCalendarSubscription(),
    ]);

    dogs.value = dogResponse.items;
});
</script>
