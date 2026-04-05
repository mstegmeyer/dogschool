import { mount } from '@vue/test-utils';
import { computed, ref } from 'vue';
import { vi } from 'vitest';
import { futureDateTimeLocalStubValue, toDateTimeLocalStubValue } from '../helpers/date-time-local';
import { createFormFeedbackState, uiPageStubs } from '../nuxt/ui-test-stubs';
import { createCalendarTimelineStub, flushPromises, installNuxtGlobals, namedStub } from '../nuxt/page-test-utils';

export const apiGetMock = vi.fn();
export const apiPostMock = vi.fn();
export const apiPutMock = vi.fn();
export const apiDelMock = vi.fn();
export const toastAddMock = vi.fn();
export const fetchProfileMock = vi.fn();
export const refreshStatusMock = vi.fn();
export const enablePushMock = vi.fn();
export const disablePushMock = vi.fn();
export const navigateToMock = vi.fn();

export const userRef = ref<any>(null);
export const pushStatusRef = ref<'enabled' | 'install-required' | 'blocked' | 'available' | 'error' | 'unsupported'>('available');
export const pushErrorRef = ref('');

export const dog = {
    id: 'dog-1',
    name: 'Luna',
    color: 'brown',
    gender: 'female',
    race: 'Mix',
    shoulderHeightCm: 48,
};

export const hotelBooking = {
    id: 'hotel-booking-1',
    customerId: 'customer-1',
    customerName: 'Max',
    dogId: 'dog-1',
    dogName: 'Luna',
    dogShoulderHeightCm: 48,
    roomId: 'room-1',
    roomName: 'Waldzimmer',
    startAt: '2026-04-05T08:00:00+02:00',
    endAt: '2026-04-06T18:00:00+02:00',
    pricingKind: 'HOTEL',
    billableDays: 2,
    includesTravelProtection: false,
    totalPrice: '123.50',
    quotedTotalPrice: '123.50',
    serviceFee: '7.50',
    travelProtectionPrice: '0.00',
    state: 'REQUESTED',
    customerComment: null,
    adminComment: null,
    pricingSnapshot: {
        lineItems: [],
    },
    createdAt: '2026-04-01T10:00:00+02:00',
    availableRooms: [],
};

export const course = {
    id: 'course-1',
    dayOfWeek: 3,
    startTime: '10:00',
    endTime: '11:00',
    durationMinutes: 60,
    type: { code: 'AGI', name: 'Agility', recurrenceKind: 'RECURRING' },
    level: 1,
    trainer: null,
    comment: null,
    archived: false,
    subscriberCount: 1,
    subscribers: [{ id: 'customer-1', name: 'Max' }],
};

export const courseDate = {
    id: 'course-date-1',
    courseId: 'course-1',
    courseType: { code: 'AGI', name: 'Agility', recurrenceKind: 'RECURRING' },
    level: 1,
    date: '2026-04-04',
    dayOfWeek: 6,
    startTime: '09:00',
    endTime: '10:00',
    trainer: null,
    cancelled: false,
    bookingCount: 0,
    createdAt: '2026-04-01T10:00:00+02:00',
    booked: false,
    bookings: [],
    subscribed: true,
    bookingWindowClosed: false,
};

export const bookedCourseDate = {
    ...courseDate,
    id: 'course-date-2',
    booked: true,
    bookings: [{ id: 'booking-1', dogId: 'dog-1' }],
};

export const notification = {
    id: 'notification-1',
    title: 'Wichtig',
    message: 'Bitte Wasser mitbringen.',
    authorName: 'Lea',
    authorId: 'trainer-1',
    isGlobal: false,
    courses: [],
    courseIds: [],
    pinnedUntil: null,
    isPinned: false,
    createdAt: '2026-04-01T09:00:00+02:00',
};

export const contract = {
    id: 'contract-1',
    contractGroupId: 'group-1',
    version: 1,
    dogId: 'dog-1',
    dogName: 'Luna',
    customerId: 'customer-1',
    customerName: 'Max',
    startDate: '2026-05-01',
    endDate: null,
    price: '79.00',
    quotedMonthlyPrice: '79.00',
    priceMonthly: '79.00',
    registrationFee: '149.00',
    firstInvoiceTotal: '228.00',
    type: 'PERPETUAL',
    coursesPerWeek: 2,
    state: 'REQUESTED',
    customerComment: null,
    adminComment: null,
    pricingSnapshot: {
        lineItems: [],
    },
    createdAt: '2026-04-01T10:00:00+02:00',
};

export const creditTransaction = {
    id: 'tx-1',
    amount: 2,
    type: 'WEEKLY_GRANT',
    description: 'Weekly grant',
    courseDateId: null,
    contractId: null,
    weekRef: '2026-W14',
    createdAt: '2026-04-01T10:00:00+02:00',
};

export function installCustomerGlobals() {
    installNuxtGlobals();
    vi.stubGlobal('useApi', () => ({
        get: apiGetMock,
        post: apiPostMock,
        put: apiPutMock,
        del: apiDelMock,
    }));
    vi.stubGlobal('useToast', () => ({
        add: toastAddMock,
    }));
    vi.stubGlobal('useAuth', () => ({
        user: userRef,
        fetchProfile: fetchProfileMock,
    }));
    vi.stubGlobal('usePushNotifications', () => ({
        pushStatus: pushStatusRef,
        pushError: pushErrorRef,
        refreshStatus: refreshStatusMock,
        enablePush: enablePushMock,
        disablePush: disablePushMock,
    }));
    vi.stubGlobal('useHelpers', () => ({
        todayIso: () => '2026-04-04',
        formatDate: (value: string) => `formatted:${value}`,
        formatDateTime: (value: string) => `formatted:${value}`,
        toMonthStartIso: (value: string) => value.slice(0, 8) + '01',
        isFirstOfMonth: (value: string) => value.endsWith('-01'),
        firstDayOfNextMonthIso: () => '2026-05-01',
        hotelBookingStateLabel: (value: string) => value,
        hotelBookingStateColor: () => 'amber',
        hotelPricingKindLabel: (value: string) => value === 'DAYCARE' ? 'HUTA' : value === 'HOTEL' ? 'Hundehotel' : value,
        formatMoney: (value: string | null | undefined) => `${value ?? '0.00'} EUR`,
        formatSquareMeters: (value: number) => `${value} m²`,
        toDateTimeLocalValue: (value: string | Date) => toDateTimeLocalStubValue(value),
        futureDateTimeLocalValue: (offsetHours: number, roundToHour = false) => futureDateTimeLocalStubValue(offsetHours, roundToHour),
    }));
    vi.stubGlobal('useRuntimeConfig', () => ({
        public: { apiBaseUrl: 'https://api.example.test' },
    }));
    vi.stubGlobal('useFormFeedback', () => createFormFeedbackState());
    vi.stubGlobal('extractApiErrorMessage', (cause: unknown, fallback: string) =>
        cause instanceof Error ? cause.message : fallback,
    );
    vi.stubGlobal('navigateTo', navigateToMock);
    Object.defineProperty(globalThis.navigator, 'clipboard', {
        value: { writeText: vi.fn().mockResolvedValue(undefined) },
        configurable: true,
    });
}

export async function mountProfilePage() {
    const Page = (await import('~/modules/customer/profile/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                ProfileFormCard: namedStub(
                    'ProfileFormCard',
                    ['loading', 'form', 'fieldErrors', 'formError', 'saving'],
                    ['submit', 'clear-field-error'],
                ),
                NotificationSettingsCard: namedStub(
                    'NotificationSettingsCard',
                    ['loading', 'badgeColor', 'statusLabel', 'alertColor', 'statusTitle', 'statusDescription', 'canEnable', 'canDisable', 'saving'],
                    ['enable', 'disable'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountDashboardPage() {
    const Page = (await import('~/modules/customer/dashboard/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                UpcomingDatesCard: namedStub(
                    'UpcomingDatesCard',
                    ['loading', 'upcomingDates', 'dogs', 'dogOptions', 'dogIdByCourseDate', 'bookingInProgress'],
                    ['update:dog-id', 'book'],
                ),
                NotificationsCard: namedStub('NotificationsCard', ['loading', 'notifications'], ['select']),
                OverviewStats: namedStub('OverviewStats', ['loading', 'creditBalance', 'subscribedCourseCount', 'dogCount']),
                NotificationDetailModal: namedStub('NotificationDetailModal', ['modelValue', 'notification'], ['update:modelValue']),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountCoursesPage() {
    const Page = (await import('~/modules/customer/courses/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                CustomerCourseGroupSection: namedStub(
                    'CustomerCourseGroupSection',
                    ['group', 'variant', 'subscribedIds'],
                    ['select', 'subscribe', 'unsubscribe'],
                ),
                CustomerCourseDetailModal: namedStub(
                    'CustomerCourseDetailModal',
                    ['modelValue', 'course', 'courseDetail', 'loading'],
                    ['update:modelValue'],
                ),
                CustomerCoursesLoadingState: namedStub('CustomerCoursesLoadingState'),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountCalendarPage() {
    vi.doMock('~/composables/useCalendarView', () => ({
        useCalendarView: (courseDatesRef: { value: unknown[] }) => {
            const viewMode = ref<'day' | 'week'>('week');
            const currentMonday = ref('2026-03-30');
            const weekStart = computed(() => '2026-03-30');
            const weekEnd = computed(() => '2026-04-05');
            const visibleDays = computed(() => [{
                date: '2026-04-04',
                label: 'Sa',
                dateShort: '04.04.',
                isToday: true,
                courseDates: courseDatesRef.value,
            }]);

            return {
                viewMode,
                currentMonday,
                weekStart,
                weekEnd,
                visibleDays,
                prev: vi.fn(),
                next: vi.fn(),
                goToday: vi.fn(),
            };
        },
    }));

    const Page = (await import('~/modules/customer/calendar/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                AppCalendarTimeline: createCalendarTimelineStub(),
                AppSkeletonCalendar: namedStub('AppSkeletonCalendar'),
                CustomerCalendarEventCard: namedStub(
                    'CustomerCalendarEventCard',
                    ['courseDate', 'condensed', 'dogs'],
                    ['open-booking', 'cancel-booking'],
                ),
                CustomerCalendarBookingModal: namedStub(
                    'CustomerCalendarBookingModal',
                    ['modelValue', 'courseDate', 'dogs', 'bookingDogId', 'selectedDogId', 'bookingInFlight'],
                    ['update:booking-dog-id', 'cancel', 'confirm', 'update:modelValue'],
                ),
                CustomerCalendarSubscriptionModal: namedStub(
                    'CustomerCalendarSubscriptionModal',
                    ['modelValue', 'calendarSubscriptionUrl', 'calendarSubscriptionWebcalUrl'],
                    ['copy', 'open', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountHotelBookingsPage() {
    const Page = (await import('~/modules/customer/hotel/bookings/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                HotelBookingsList: namedStub('HotelBookingsList', ['loading', 'bookings', 'busyId'], ['accept', 'decline', 'resubmit']),
                HotelBookingRequestModal: namedStub(
                    'HotelBookingRequestModal',
                    ['modelValue', 'dogOptions', 'selectedDogName', 'storedShoulderHeightCm', 'form', 'fieldErrors', 'formError', 'saving', 'previewLoading', 'preview'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountContractsPage() {
    const Page = (await import('~/modules/customer/contracts/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                ContractsList: namedStub('ContractsList', ['loading', 'contracts', 'busyId'], ['accept', 'decline', 'resubmit']),
                RequestModal: namedStub(
                    'RequestModal',
                    ['modelValue', 'dogOptions', 'form', 'fieldErrors', 'formError', 'saving', 'previewLoading', 'preview'],
                    ['submit', 'cancel', 'normalize-start-date', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountDogsPage() {
    const Page = (await import('~/modules/customer/dogs/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                DogsGrid: namedStub('DogsGrid', ['loading', 'dogs'], ['add']),
                AddDogModal: namedStub(
                    'AddDogModal',
                    ['modelValue', 'form', 'fieldErrors', 'formError', 'saving'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountCreditsPage() {
    const Page = (await import('~/modules/customer/credits/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                CreditBalanceSummary: namedStub('CreditBalanceSummary', ['loading', 'balance']),
                NextWeeklyGrantsCard: namedStub('NextWeeklyGrantsCard', ['loading', 'items']),
                CreditHistoryList: namedStub('CreditHistoryList', ['entries', 'columns', 'emptyLabel']),
                AppSkeletonCollection: namedStub('AppSkeletonCollection'),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountNotificationsPage() {
    const Page = (await import('~/modules/customer/notifications/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                NotificationsList: namedStub('NotificationsList', ['loading', 'notifications']),
            },
        },
    });
    await flushPromises();
    return wrapper;
}
