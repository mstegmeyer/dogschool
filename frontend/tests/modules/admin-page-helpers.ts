import { mount } from '@vue/test-utils';
import { vi } from 'vitest';
import { futureDateTimeLocalStubValue, toDateTimeLocalStubValue } from '../helpers/date-time-local';
import { createFormFeedbackState, uiPageStubs } from '../nuxt/ui-test-stubs';
import { flushPromises, installNuxtGlobals, namedStub } from '../nuxt/page-test-utils';

export const apiGetMock = vi.fn();
export const apiPostMock = vi.fn();
export const apiPutMock = vi.fn();
export const apiDelMock = vi.fn();
export const toastAddMock = vi.fn();
export const navigateToMock = vi.fn();

export const recurrenceCourseType = {
    id: 'course-type-1',
    code: 'AGI',
    name: 'Agility',
    recurrenceKind: 'RECURRING',
};

export const trainer = {
    id: 'trainer-1',
    username: 'lea',
    fullName: 'Lea',
    phone: null,
};

export const course = {
    id: 'course-1',
    dayOfWeek: 2,
    startTime: '10:00',
    endTime: '11:00',
    durationMinutes: 60,
    type: recurrenceCourseType,
    level: 1,
    trainer,
    comment: 'Bring treats',
    archived: false,
    subscriberCount: 2,
    subscribers: [{ id: 'customer-1', name: 'Max' }],
};

export const archivedCourse = {
    ...course,
    id: 'course-archived',
    archived: true,
};

export const notification = {
    id: 'notification-1',
    title: 'Hinweis',
    message: 'Bitte pünktlich sein.',
    authorName: 'Lea',
    authorId: 'trainer-1',
    isGlobal: false,
    courses: [],
    courseIds: ['course-1'],
    pinnedUntil: '2026-04-10T23:59:59',
    isPinned: true,
    createdAt: '2026-04-01T10:00:00+02:00',
};

export const room = {
    id: 'room-1',
    name: 'Waldzimmer',
    squareMeters: 14,
    createdAt: '2026-04-01T10:00:00+02:00',
};

export const hotelBooking = {
    id: 'hotel-booking-1',
    customerId: 'customer-1',
    customerName: 'Max',
    dogId: 'dog-1',
    dogName: 'Rex',
    dogShoulderHeightCm: 62,
    roomId: null,
    roomName: null,
    startAt: '2026-04-05T08:00:00+02:00',
    endAt: '2026-04-06T10:00:00+02:00',
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
    availableRooms: [
        {
            roomId: 'room-1',
            roomName: 'Waldzimmer',
            squareMeters: 14,
            available: true,
            requiredSquareMeters: 8,
            peakRequiredSquareMeters: 8,
            remainingSquareMeters: 6,
            segments: [],
        },
    ],
};

export const pricingConfig = {
    id: 'pricing-config-1',
    schoolOneCoursePrice: '89.00',
    schoolTwoCoursesUnitPrice: '80.00',
    schoolThreeCoursesUnitPrice: '76.00',
    schoolFourCoursesUnitPrice: '71.00',
    schoolAdditionalCoursesUnitPrice: '67.00',
    schoolRegistrationFee: '149.00',
    daycareOffSeasonDailyPrice: '39.00',
    daycarePeakSeasonDailyPrice: '46.00',
    hotelDailyPrice: '58.00',
    hotelServiceFee: '7.50',
    hotelTravelProtectionBaseFee: '49.00',
    hotelTravelProtectionAdditionalDailyFee: '11.00',
    hotelSingleRoomDaycareDailyPrice: '20.00',
    hotelSingleRoomHotelDailyPrice: '29.00',
    hotelHeatCycleDailyPrice: '6.00',
    hotelMedicationPerAdministrationPrice: '3.50',
    hotelSupplementPerAdministrationPrice: '3.50',
    hotelPeakSeasons: [
        { id: 'season-1', startDate: '2026-06-29', endDate: '2026-09-13' },
    ],
    createdAt: '2026-01-01T00:00:00+01:00',
    updatedAt: '2026-01-01T00:00:00+01:00',
};

export const hotelOccupancyResponse = {
    from: '2026-04-05T09:00:00+02:00',
    to: '2026-04-06T09:00:00+02:00',
    items: [
        {
            room,
            peakRequiredSquareMeters: 11,
            segments: [
                {
                    startAt: '2026-04-05T10:00:00+02:00',
                    endAt: '2026-04-05T12:00:00+02:00',
                    usedSquareMeters: 11,
                    freeSquareMeters: 3,
                    bookingCount: 2,
                    dogNames: ['Rex', 'Luna'],
                },
            ],
            bookings: [hotelBooking],
        },
    ],
};

export const hotelMovementsResponse = {
    from: '2026-04-05T09:00:00+02:00',
    to: '2026-04-06T09:00:00+02:00',
    arrivals: [
        {
            ...hotelBooking,
            roomId: 'room-1',
            roomName: 'Waldzimmer',
            state: 'CONFIRMED',
        },
    ],
    departures: [
        {
            ...hotelBooking,
            roomId: 'room-1',
            roomName: 'Waldzimmer',
            state: 'CONFIRMED',
        },
    ],
};

export const activeContract = {
    id: 'contract-1',
    contractGroupId: 'group-1',
    version: 1,
    dogId: 'dog-1',
    dogName: 'Rex',
    customerId: 'customer-1',
    customerName: 'Max',
    startDate: '2026-04-01',
    endDate: null,
    price: '89.00',
    quotedMonthlyPrice: '89.00',
    priceMonthly: '89.00',
    registrationFee: '149.00',
    firstInvoiceTotal: '238.00',
    type: 'PERPETUAL',
    coursesPerWeek: 1,
    state: 'ACTIVE',
    customerComment: null,
    adminComment: null,
    pricingSnapshot: {
        lineItems: [],
    },
    createdAt: '2026-03-01T10:00:00+02:00',
};

export const pendingContract = {
    ...activeContract,
    id: 'contract-2',
    state: 'REQUESTED',
    price: '79.00',
    quotedMonthlyPrice: '79.00',
    priceMonthly: '79.00',
    firstInvoiceTotal: '228.00',
};

export const customerRecord = {
    id: 'customer-1',
    name: 'Max',
    email: 'max@example.com',
    createdAt: '2026-04-01T10:00:00+02:00',
    address: { street: null, postalCode: '12345', city: 'Berlin', country: null },
    bankAccount: { iban: null, bic: null, accountHolder: null },
};

export const todayCourseDate = {
    id: 'course-date-1',
    courseId: 'course-1',
    courseType: { code: 'AGI', name: 'Agility', recurrenceKind: 'RECURRING' },
    level: 1,
    date: '2026-04-04',
    dayOfWeek: 6,
    startTime: '09:00',
    endTime: '10:00',
    trainer: trainer,
    cancelled: false,
    bookingCount: 1,
    createdAt: '2026-04-01T10:00:00+02:00',
    booked: false,
    bookings: [],
    subscriberCount: 2,
    subscribers: [{ id: 'customer-1', name: 'Max' }],
};

export function installAdminGlobals() {
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
    vi.stubGlobal('navigateTo', navigateToMock);
    vi.stubGlobal('useHelpers', () => ({
        dayName: (dayOfWeek: number) => ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'][dayOfWeek] || '',
        todayIso: () => '2026-04-04',
        formatDate: (value: string) => `formatted:${value}`,
        formatDateTime: (value: string) => `formatted:${value}`,
        toMonthEndIso: (value: string) => value.slice(0, 8) + '30',
        isLastOfMonth: (value: string) => value.endsWith('-30'),
        getWeekMonday: () => '2026-03-30',
        contractStateLabel: (value: string) => value,
        contractStateColor: (value: string) => value === 'ACTIVE' ? 'green' : value === 'PENDING_CUSTOMER_APPROVAL' ? 'amber' : 'gray',
        formatContractMonthlyPrice: (price: string) => `${price} EUR`,
        hotelBookingStateLabel: (value: string) => value,
        hotelBookingStateColor: () => 'amber',
        hotelPricingKindLabel: (value: string) => value === 'DAYCARE' ? 'HUTA' : value === 'HOTEL' ? 'Hundehotel' : value,
        formatMoney: (value: string | null | undefined) => `${value ?? '0.00'} EUR`,
        formatSquareMeters: (value: number) => `${value} m²`,
        toDateTimeLocalValue: (value: string | Date) => toDateTimeLocalStubValue(value),
        futureDateTimeLocalValue: (offsetHours: number, roundToHour = false) => futureDateTimeLocalStubValue(offsetHours, roundToHour),
    }));
    vi.stubGlobal('useFormFeedback', () => createFormFeedbackState());
    vi.stubGlobal('extractApiErrorMessage', (cause: unknown, fallback: string) =>
        cause instanceof Error ? cause.message : fallback,
    );
}

export async function mountCourseTypesPage() {
    const Page = (await import('~/modules/admin/course-types/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                CourseTypesList: namedStub('CourseTypesList', ['loading', 'courseTypes', 'columns'], ['edit', 'delete']),
                CourseTypeFormModal: namedStub(
                    'CourseTypeFormModal',
                    ['modelValue', 'editing', 'form', 'recurrenceOptions', 'fieldErrors', 'formError', 'saving'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountNotificationsPage() {
    const Page = (await import('~/modules/admin/notifications/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                NotificationsList: namedStub(
                    'NotificationsList',
                    ['loading', 'notifications', 'columns', 'resultSummary', 'showPagination', 'currentPage', 'pageSize', 'totalNotifications'],
                    ['edit', 'delete', 'update:current-page'],
                ),
                NotificationFormModal: namedStub(
                    'NotificationFormModal',
                    ['modelValue', 'editing', 'form', 'courseOptions', 'fieldErrors', 'formError', 'saving'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountContractsPage() {
    const Page = (await import('~/modules/admin/contracts/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                ContractsTable: namedStub(
                    'ContractsTable',
                    ['loading', 'contracts', 'sort', 'columns', 'resultSummary', 'showPagination', 'currentPage', 'pageSize', 'totalContracts'],
                    ['review', 'cancel', 'update:sort', 'update:current-page'],
                ),
                CancelModal: namedStub(
                    'CancelModal',
                    ['modelValue', 'contract', 'endDate', 'endDateError', 'formError', 'saving'],
                    ['cancel', 'submit', 'normalize-end-date', 'clear-end-date-error', 'update:end-date', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountCoursesPage() {
    const Page = (await import('~/modules/admin/courses/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                AdminCourseTable: namedStub('AdminCourseTable', ['courses', 'sort'], ['edit', 'toggle-archive', 'update:sort']),
                AdminCourseListMobile: namedStub('AdminCourseListMobile', ['courses'], ['edit', 'toggle-archive']),
                AdminCourseFormModal: namedStub(
                    'AdminCourseFormModal',
                    ['modelValue', 'editingCourse', 'form', 'dayOptions', 'trainerOptions', 'showScheduleHint', 'scheduleHintText', 'formError', 'fieldErrors', 'saving'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
                AdminCourseArchiveModal: namedStub(
                    'AdminCourseArchiveModal',
                    ['modelValue', 'course', 'removeFromDate', 'minDate', 'error', 'archiving'],
                    ['cancel', 'confirm', 'update:remove-from-date', 'update:modelValue'],
                ),
                AppSkeletonCollection: namedStub('AppSkeletonCollection'),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountCustomersPage() {
    const Page = (await import('~/modules/admin/customers/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                CustomersList: namedStub(
                    'CustomersList',
                    ['loading', 'customers', 'sort', 'columns', 'resultSummary', 'showPagination', 'currentPage', 'pageSize', 'totalCustomers'],
                    ['select', 'update:sort', 'update:current-page'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountDashboardPage() {
    const Page = (await import('~/modules/admin/dashboard/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                StatsGrid: namedStub('StatsGrid', ['loading', 'stats']),
                PendingContractsCard: namedStub('PendingContractsCard', ['loading', 'count', 'contracts']),
                TodayScheduleCard: namedStub('TodayScheduleCard', ['loading', 'courseDates']),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountHotelRoomsPage() {
    const Page = (await import('~/modules/admin/hotel/rooms/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                RoomList: namedStub('RoomList', ['loading', 'rooms'], ['edit']),
                RoomFormModal: namedStub(
                    'RoomFormModal',
                    ['modelValue', 'editing', 'form', 'fieldErrors', 'formError', 'saving'],
                    ['submit', 'cancel', 'clear-field-error', 'update:modelValue'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountHotelAdminBookingsPage() {
    const Page = (await import('~/modules/admin/hotel/bookings/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
                HotelBookingsTable: namedStub(
                    'HotelBookingsTable',
                    ['loading', 'bookings', 'resultSummary', 'showPagination', 'currentPage', 'pageSize', 'totalBookings'],
                    ['open', 'update:current-page'],
                ),
                HotelBookingDetailModal: namedStub(
                    'HotelBookingDetailModal',
                    ['modelValue', 'booking', 'selectedRoomId', 'roomOptions', 'assigning', 'confirming', 'declining', 'finalPrice', 'adminComment', 'pricingConfig'],
                    ['assign-room', 'confirm', 'decline', 'cancel', 'update:selected-room-id', 'update:final-price', 'update:admin-comment', 'update:model-value'],
                ),
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountHotelOccupancyPage() {
    const Page = (await import('~/modules/admin/hotel/occupancy/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
            },
        },
    });
    await flushPromises();
    return wrapper;
}

export async function mountHotelMovementsPage() {
    const Page = (await import('~/modules/admin/hotel/movements/index.vue')).default;
    const wrapper = mount(Page, {
        global: {
            stubs: {
                ...uiPageStubs,
            },
        },
    });
    await flushPromises();
    return wrapper;
}
