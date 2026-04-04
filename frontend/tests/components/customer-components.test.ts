import { beforeEach, describe, expect, it } from 'vitest';
import CustomerNotificationsList from '~/modules/customer/notifications/components/NotificationsList.vue';
import ContractsList from '~/modules/customer/contracts/components/ContractsList.vue';
import RequestModal from '~/modules/customer/contracts/components/RequestModal.vue';
import DogsGrid from '~/modules/customer/dogs/components/DogsGrid.vue';
import AddDogModal from '~/modules/customer/dogs/components/AddDogModal.vue';
import NextWeeklyGrantsCard from '~/modules/customer/credits/components/NextWeeklyGrantsCard.vue';
import CustomerCoursesLoadingState from '~/modules/customer/courses/components/LoadingState.vue';
import CustomerCourseGroupSection from '~/modules/customer/courses/components/CourseGroupSection.vue';
import CustomerCourseDetailModal from '~/modules/customer/courses/components/DetailModal.vue';
import CustomerCalendarEventCard from '~/modules/customer/calendar/components/EventCard.vue';
import CustomerCalendarBookingModal from '~/modules/customer/calendar/components/BookingModal.vue';
import CustomerCalendarSubscriptionModal from '~/modules/customer/calendar/components/SubscriptionModal.vue';
import UpcomingDatesCard from '~/modules/customer/dashboard/components/UpcomingDatesCard.vue';
import NotificationsCard from '~/modules/customer/dashboard/components/NotificationsCard.vue';
import NotificationDetailModal from '~/modules/customer/dashboard/components/NotificationDetailModal.vue';
import OverviewStats from '~/modules/customer/dashboard/components/OverviewStats.vue';
import NotificationSettingsCard from '~/modules/customer/profile/components/NotificationSettingsCard.vue';
import ProfileFormCard from '~/modules/customer/profile/components/ProfileFormCard.vue';
import {
    baseBookedCourseDate,
    baseContract,
    baseCourse,
    baseDog,
    baseNotification,
    installComponentGlobals,
    makeCourseDate,
    makeCourseGroup,
    makeCustomerCourseDetail,
    makeNotification,
    mountComponent,
    nextWeeklyGrant,
    profileForm,
} from './component-test-helpers';

describe('customer leaf components', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/customer' });
    });

    it('renders customer notifications with the detail component content', () => {
        const wrapper = mountComponent(CustomerNotificationsList, {
            props: {
                loading: false,
                notifications: [makeNotification({ isPinned: true })],
            },
        });

        expect(wrapper.get('[data-testid="notification-card-notification-1"]').exists()).toBe(true);
        expect(wrapper.getComponent({ name: 'AppNotificationDetail' }).props('notification')).toMatchObject({ id: 'notification-1' });
    });

    it('renders contracts and their date range in the contracts list', () => {
        const wrapper = mountComponent(ContractsList, {
            props: {
                loading: false,
                contracts: [baseContract],
            },
        });

        expect(wrapper.text()).toContain('ACTIVE');
        expect(wrapper.text()).toContain('89.00 EUR');
        expect(wrapper.text()).toContain('formatted:2026-04-01');
    });

    it('renders the request modal and emits normalization and submission', async () => {
        const wrapper = mountComponent(RequestModal, {
            props: {
                modelValue: true,
                dogOptions: [{ label: baseDog.name, value: baseDog.id }],
                form: {
                    dogId: baseDog.id,
                    coursesPerWeek: 2,
                    startDate: '2026-05-01',
                },
                fieldErrors: { dogId: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="request-contract-start-date"]').setValue('2026-06-01');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Vertrag anfragen');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['startDate']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('covers empty and populated dog states in the dogs grid', async () => {
        const wrapper = mountComponent(DogsGrid, {
            props: {
                loading: false,
                dogs: [],
            },
        });

        expect(wrapper.get('[data-testid="dogs-empty-state"]').exists()).toBe(true);
        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('add')).toHaveLength(1);

        await wrapper.setProps({ dogs: [baseDog] });
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.text()).toContain('Mix');
    });

    it('renders the add dog modal and clears field errors on input', async () => {
        const wrapper = mountComponent(AddDogModal, {
            props: {
                modelValue: true,
                form: {
                    name: '',
                    race: '',
                    gender: '',
                    color: '',
                },
                fieldErrors: { name: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[placeholder="z.B. Bella"]').setValue('Nala');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Hund hinzufügen');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['name']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders next weekly grant hints', () => {
        const wrapper = mountComponent(NextWeeklyGrantsCard, {
            props: {
                loading: false,
                items: [nextWeeklyGrant],
            },
        });

        expect(wrapper.text()).toContain('Nächste Gutschriften');
        expect(wrapper.text()).toContain('+2 Credits');
        expect(wrapper.text()).toContain('formatted:2026-04-08T08:00:00+02:00');
    });

    it('renders the customer courses loading state skeletons', () => {
        const wrapper = mountComponent(CustomerCoursesLoadingState);

        expect(wrapper.findAll('textarea').length).toBe(0);
        expect(wrapper.text()).toContain('skeleton');
    });

    it('renders course groups and emits a subscription action', async () => {
        const wrapper = mountComponent(CustomerCourseGroupSection, {
            props: {
                group: makeCourseGroup(),
                variant: 'available',
                subscribedIds: new Set<string>(),
            },
        });

        await wrapper.get('[data-testid="course-available-subscription-action-course-1"]').trigger('click');

        expect(wrapper.text()).toContain('Dienstag');
        expect(wrapper.text()).toContain('Abonnieren');
        expect(wrapper.emitted('subscribe')).toHaveLength(1);
    });

    it('renders the customer course detail modal with dates and notification history', async () => {
        const wrapper = mountComponent(CustomerCourseDetailModal, {
            props: {
                modelValue: true,
                course: baseCourse,
                courseDetail: makeCustomerCourseDetail(),
                loading: false,
            },
        });

        await wrapper.get('button[aria-label="Schließen"]').trigger('click');

        expect(wrapper.text()).toContain('Nächste Termine');
        expect(wrapper.text()).toContain('Mitteilungsverlauf');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });

    it('renders booked customer calendar cards and emits cancellation', async () => {
        const wrapper = mountComponent(CustomerCalendarEventCard, {
            props: {
                courseDate: baseBookedCourseDate,
                condensed: false,
                dogs: [baseDog],
            },
        });

        await wrapper.get('button').trigger('click');

        expect(wrapper.text()).toContain('Gebucht');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.emitted('cancel-booking')).toHaveLength(1);
    });

    it('renders the booking modal and emits the selected dog and confirmation', async () => {
        const wrapper = mountComponent(CustomerCalendarBookingModal, {
            props: {
                modelValue: true,
                courseDate: makeCourseDate(),
                dogs: [baseDog, { ...baseDog, id: 'dog-2', name: 'Milo' }],
                bookingDogId: '',
                selectedDogId: 'dog-2',
                bookingInFlight: false,
            },
        });

        await wrapper.get('[data-testid="booking-dog-select"]').setValue('dog-2');
        await wrapper.get('[data-testid="confirm-booking"]').trigger('click');

        expect(wrapper.text()).toContain('Buchung');
        expect(wrapper.emitted('update:bookingDogId')?.[0]).toEqual(['dog-2']);
        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });

    it('renders the calendar subscription modal and emits copy and open actions', async () => {
        const wrapper = mountComponent(CustomerCalendarSubscriptionModal, {
            props: {
                modelValue: true,
                calendarSubscriptionUrl: 'https://example.test/calendar.ics',
                calendarSubscriptionWebcalUrl: 'webcal://example.test/calendar.ics',
            },
        });

        await wrapper.get('[data-testid="copy-calendar-url"]').trigger('click');
        await wrapper.get('[data-testid="open-calendar-url"]').trigger('click');

        expect(wrapper.text()).toContain('Kalender abonnieren');
        expect(wrapper.emitted('copy')).toHaveLength(1);
        expect(wrapper.emitted('open')).toHaveLength(1);
    });

    it('renders upcoming dates with booked and open booking states', async () => {
        const wrapper = mountComponent(UpcomingDatesCard, {
            props: {
                loading: false,
                upcomingDates: [
                    baseBookedCourseDate,
                    makeCourseDate({ id: 'course-date-open', booked: false, subscribed: true, bookingWindowClosed: false }),
                ],
                dogs: [baseDog],
                dogOptions: [{ label: baseDog.name, value: baseDog.id }],
                dogIdByCourseDate: { 'course-date-open': baseDog.id },
                bookingInProgress: null,
            },
        });

        await wrapper.get('[data-testid="dashboard-book-course-date-open"]').trigger('click');

        expect(wrapper.text()).toContain('Gebucht für Luna');
        expect(wrapper.emitted('book')).toHaveLength(1);
    });

    it('renders dashboard notifications and emits the selected item', async () => {
        const wrapper = mountComponent(NotificationsCard, {
            props: {
                loading: false,
                notifications: [makeNotification({ isPinned: true, isGlobal: true })],
            },
        });

        await wrapper.get('[data-testid="dashboard-notification-notification-1"]').trigger('click');

        expect(wrapper.text()).toContain('Alle Kurse');
        expect(wrapper.emitted('select')).toHaveLength(1);
    });

    it('renders the dashboard notification detail modal and closes it', async () => {
        const wrapper = mountComponent(NotificationDetailModal, {
            props: {
                modelValue: true,
                notification: baseNotification,
            },
        });

        await wrapper.get('button[aria-label="Mitteilung schliessen"]').trigger('click');

        expect(wrapper.text()).toContain('Mitteilung');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
    });

    it('renders the overview stats cards', () => {
        const wrapper = mountComponent(OverviewStats, {
            props: {
                loading: false,
                creditBalance: 4,
                subscribedCourseCount: 3,
                dogCount: 1,
            },
        });

        expect(wrapper.text()).toContain('Guthaben (Credits)');
        expect(wrapper.text()).toContain('Abonnierte Kurse');
        expect(wrapper.text()).toContain('Registrierte Hunde');
    });

    it('renders notification settings and emits enable and disable actions', async () => {
        const wrapper = mountComponent(NotificationSettingsCard, {
            props: {
                loading: false,
                badgeColor: 'green',
                statusLabel: 'Aktiv',
                alertColor: 'green',
                statusTitle: 'Push aktiv',
                statusDescription: 'Du erhältst Hinweise.',
                canEnable: true,
                canDisable: false,
                saving: false,
            },
        });

        await wrapper.get('[data-testid="enable-notifications"]').trigger('click');
        await wrapper.setProps({ canEnable: false, canDisable: true });
        await wrapper.get('[data-testid="disable-notifications"]').trigger('click');

        expect(wrapper.text()).toContain('Benachrichtigungen');
        expect(wrapper.emitted('enable')).toHaveLength(1);
        expect(wrapper.emitted('disable')).toHaveLength(1);
    });

    it('renders the profile form card and emits field clearing and submit', async () => {
        const wrapper = mountComponent(ProfileFormCard, {
            props: {
                loading: false,
                form: { ...profileForm },
                fieldErrors: { name: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input').setValue('Maximilian');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Passwort ändern');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['name']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
