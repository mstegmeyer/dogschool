import { beforeEach, describe, expect, it } from 'vitest';
import CourseTypeFormModal from '~/modules/admin/course-types/components/FormModal.vue';
import CourseTypesList from '~/modules/admin/course-types/components/List.vue';
import ContractsTable from '~/modules/admin/contracts/components/ContractsTable.vue';
import CancelModal from '~/modules/admin/contracts/components/CancelModal.vue';
import NotificationFormModal from '~/modules/admin/notifications/components/FormModal.vue';
import AdminNotificationsList from '~/modules/admin/notifications/components/NotificationsList.vue';
import StatsGrid from '~/modules/admin/dashboard/components/StatsGrid.vue';
import PendingContractsCard from '~/modules/admin/dashboard/components/PendingContractsCard.vue';
import TodayScheduleCard from '~/modules/admin/dashboard/components/TodayScheduleCard.vue';
import AdminCourseListMobile from '~/modules/admin/courses/components/ListMobile.vue';
import AdminCourseTable from '~/modules/admin/courses/components/CourseTable.vue';
import AdminCourseFormModal from '~/modules/admin/courses/components/FormModal.vue';
import AdminCourseArchiveModal from '~/modules/admin/courses/components/ArchiveModal.vue';
import AdminCalendarEventCard from '~/modules/admin/calendar/components/EventCard.vue';
import AdminCalendarDetailModal from '~/modules/admin/calendar/components/DetailModal.vue';
import CustomersList from '~/modules/admin/customers/components/List.vue';
import CustomerLoadingState from '~/modules/admin/customers/[id]/components/LoadingState.vue';
import CustomerInfoCard from '~/modules/admin/customers/[id]/components/CustomerInfoCard.vue';
import CreditAdjustCard from '~/modules/admin/customers/[id]/components/CreditAdjustCard.vue';
import {
    adminNotificationForm,
    baseArchivedCourse,
    baseContract,
    baseCourse,
    baseCustomer,
    basePendingContract,
    baseTrainer,
    courseForm,
    courseTypeForm,
    installComponentGlobals,
    makeCourseDate,
    makeNotification,
    mountComponent,
    recurrenceOptions,
} from './component-test-helpers';

describe('admin leaf components', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
    });

    it('renders the course type form modal and clears field errors on input', async () => {
        const wrapper = mountComponent(CourseTypeFormModal, {
            props: {
                modelValue: true,
                editing: false,
                form: { ...courseTypeForm },
                recurrenceOptions,
                fieldErrors: { code: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[placeholder="z.B. AGI"]').setValue('OB');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Neue Kursart');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['code']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders the course types list and emits edit and delete actions', async () => {
        const wrapper = mountComponent(CourseTypesList, {
            props: {
                loading: false,
                courseTypes: [baseCourse.type],
                columns: [
                    { key: 'code', label: 'Code' },
                    { key: 'name', label: 'Name' },
                    { key: 'recurrenceKind', label: 'Wiederholung' },
                    { key: 'actions', label: '' },
                ],
            },
        });

        await wrapper.get('[data-testid="edit-course-type-mobile-course-type-1"]').trigger('click');
        await wrapper.get('[data-testid="delete-course-type-mobile-course-type-1"]').trigger('click');

        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.text()).toContain('Wiederkehrend');
        expect(wrapper.emitted('edit')).toHaveLength(1);
        expect(wrapper.emitted('delete')).toHaveLength(1);
    });

    it('renders contract actions in the contracts table and emits the row events', async () => {
        const wrapper = mountComponent(ContractsTable, {
            props: {
                loading: false,
                contracts: [basePendingContract, baseContract],
                sort: { column: 'createdAt', direction: 'desc' },
                columns: [
                    { key: 'participant', label: 'Teilnehmer' },
                    { key: 'state', label: 'Status' },
                    { key: 'actions', label: '' },
                ],
                resultSummary: '2 Verträge',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalContracts: 2,
            },
        });

        await wrapper.get('[data-testid="approve-contract-mobile-contract-2"]').trigger('click');
        await wrapper.get('[data-testid="cancel-contract-mobile-contract-1"]').trigger('click');

        expect(wrapper.text()).toContain('Max');
        expect(wrapper.text()).toContain('79.00 EUR');
        expect(wrapper.emitted('approve')).toHaveLength(1);
        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });

    it('updates the cancel modal end date and emits submit', async () => {
        const wrapper = mountComponent(CancelModal, {
            props: {
                modelValue: true,
                contract: baseContract,
                endDate: '2026-04-30',
                endDateError: '',
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="contract-end-date"]').setValue('2026-05-31');
        await wrapper.get('[data-testid="confirm-contract-cancel"]').trigger('click');

        expect(wrapper.text()).toContain('Vertrag kündigen?');
        expect(wrapper.emitted('update:endDate')?.[0]).toEqual(['2026-05-31']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders the notification form modal and submits the payload', async () => {
        const wrapper = mountComponent(NotificationFormModal, {
            props: {
                modelValue: true,
                editing: true,
                form: { ...adminNotificationForm },
                courseOptions: [{ label: 'Agility', value: baseCourse.id }],
                fieldErrors: { title: 'Pflichtfeld' },
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('[data-testid="notification-title"]').setValue('Neuer Titel');
        await wrapper.get('[data-testid="notification-message"]').setValue('Neue Nachricht');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Mitteilung bearbeiten');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['title']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders admin notifications with pinned and global badges', async () => {
        const wrapper = mountComponent(AdminNotificationsList, {
            props: {
                loading: false,
                notifications: [
                    makeNotification({ isPinned: true, isGlobal: true, pinnedUntil: '2026-04-10' }),
                    makeNotification({ id: 'notification-2', title: 'Zweite Mitteilung', authorName: null }),
                ],
                columns: [
                    { key: 'createdAt', label: 'Datum' },
                    { key: 'pinnedUntil', label: 'Angepinnt' },
                    { key: 'title', label: 'Titel' },
                    { key: 'courses', label: 'Kurse' },
                    { key: 'message', label: 'Text' },
                    { key: 'authorName', label: 'Autor' },
                    { key: 'actions', label: '' },
                ],
                resultSummary: '2 Mitteilungen',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalNotifications: 2,
            },
        });

        await wrapper.get('[data-testid="edit-notification-mobile-notification-1"]').trigger('click');
        await wrapper.get('[data-testid="delete-notification-mobile-notification-1"]').trigger('click');

        expect(wrapper.text()).toContain('Alle Kurse');
        expect(wrapper.text()).toContain('formatted:2026-04-10');
        expect(wrapper.emitted('edit')).toHaveLength(1);
        expect(wrapper.emitted('delete')).toHaveLength(1);
    });

    it('renders dashboard stats', () => {
        const wrapper = mountComponent(StatsGrid, {
            props: {
                loading: false,
                stats: [
                    { label: 'Kunden', value: 12, icon: 'i-users', bgClass: 'bg-sand-100', iconClass: 'text-komm-700' },
                    { label: 'Kurse', value: 8, icon: 'i-course', bgClass: 'bg-blue-100', iconClass: 'text-blue-700' },
                    { label: 'Termine', value: 5, icon: 'i-calendar', bgClass: 'bg-green-100', iconClass: 'text-green-700' },
                ],
            },
        });

        expect(wrapper.text()).toContain('Kunden');
        expect(wrapper.text()).toContain('12');
        expect(wrapper.text()).toContain('Termine');
    });

    it('renders pending contracts with contract pricing', () => {
        const wrapper = mountComponent(PendingContractsCard, {
            props: {
                loading: false,
                count: 1,
                contracts: [basePendingContract],
            },
        });

        expect(wrapper.text()).toContain('Offene Vertragsanfragen');
        expect(wrapper.text()).toContain('79.00 EUR');
        expect(wrapper.text()).toContain('Angefragt');
    });

    it('renders today schedule rows with booking and cancellation state', () => {
        const wrapper = mountComponent(TodayScheduleCard, {
            props: {
                loading: false,
                courseDates: [
                    makeCourseDate({
                        bookingCount: 1,
                        bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                        cancelled: true,
                    }),
                ],
            },
        });

        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.text()).toContain('1');
        expect(wrapper.text()).toContain('Abgesagt');
    });

    it('renders the mobile course list and emits archive toggles', async () => {
        const wrapper = mountComponent(AdminCourseListMobile, {
            props: {
                courses: [baseArchivedCourse],
            },
        });

        await wrapper.get('[data-testid="toggle-archive-course-course-archived"]').trigger('click');

        expect(wrapper.text()).toContain('Archiviert');
        expect(wrapper.text()).toContain('Lea');
        expect(wrapper.emitted('toggle-archive')).toHaveLength(1);
    });

    it('renders the course table and exposes dropdown row actions', async () => {
        const wrapper = mountComponent(AdminCourseTable, {
            props: {
                courses: [baseCourse],
                sort: { column: 'dayOfWeek', direction: 'asc' },
            },
        });

        await wrapper.get('button').trigger('click');
        await wrapper.get('button:nth-of-type(2)').trigger('click');

        expect(wrapper.text()).toContain('Dienstag');
        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.emitted('edit')).toHaveLength(1);
    });

    it('renders the course form modal including the schedule hint', async () => {
        const wrapper = mountComponent(AdminCourseFormModal, {
            props: {
                modelValue: true,
                editingCourse: true,
                form: { ...courseForm },
                dayOptions: [{ label: 'Dienstag', value: 2 }],
                trainerOptions: [{ label: 'Lea', value: baseTrainer.id }],
                showScheduleHint: true,
                scheduleHintText: 'Terminserie wird angepasst.',
                formError: '',
                fieldErrors: { comment: 'Hinweis' },
                saving: false,
            },
        });

        await wrapper.get('[data-testid="course-form-comment"]').setValue('Neuer Kommentar');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Terminserie wird angepasst.');
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['comment']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('renders the archive modal course summary and emits confirmation', async () => {
        const wrapper = mountComponent(AdminCourseArchiveModal, {
            props: {
                modelValue: true,
                course: baseCourse,
                removeFromDate: '2026-04-30',
                minDate: '2026-04-01',
                error: '',
                archiving: false,
            },
        });

        await wrapper.get('[data-testid="archive-remove-from-date"]').setValue('2026-05-31');
        await wrapper.get('[data-testid="confirm-course-archive"]').trigger('click');

        expect(wrapper.text()).toContain('Agility');
        expect(wrapper.text()).toContain('formatted:2026-04-30');
        expect(wrapper.emitted('update:removeFromDate')?.[0]).toEqual(['2026-05-31']);
        expect(wrapper.emitted('confirm')).toHaveLength(1);
    });

    it('renders the admin calendar event card with booking and subscriber counts', () => {
        const wrapper = mountComponent(AdminCalendarEventCard, {
            props: {
                courseDate: makeCourseDate({
                    bookingCount: 1,
                    bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                    subscriberCount: 4,
                }),
                condensed: false,
            },
        });

        expect(wrapper.text()).toContain('Agility L1');
        expect(wrapper.text()).toContain('1');
        expect(wrapper.text()).toContain('4');
    });

    it('renders the admin calendar detail modal with cancellation controls', async () => {
        const wrapper = mountComponent(AdminCalendarDetailModal, {
            props: {
                modelValue: true,
                selectedDate: makeCourseDate({
                    trainer: baseTrainer,
                    trainerOverridden: true,
                    courseTrainer: { id: 'trainer-2', fullName: 'Standard Trainer' },
                    bookings: [{ id: 'booking-1', dogName: 'Luna', customerName: 'Max' }],
                    subscriberCount: 3,
                }),
                trainerOptions: [
                    { label: 'Standard vom Kurs verwenden', value: '' },
                    { label: baseTrainer.fullName, value: baseTrainer.id },
                ],
                selectedTrainerId: '',
                savingTrainer: false,
                cancelling: false,
                cancelNotify: true,
                cancelNotifyTitle: 'Ausfall',
                cancelNotifyMessage: 'Heute kein Training',
            },
        });

        await wrapper.get('[data-testid="save-calendar-trainer"]').trigger('click');
        await wrapper.get('[data-testid="cancel-calendar-date"]').trigger('click');

        expect(wrapper.text()).toContain('Standard für den Kurs: Standard Trainer');
        expect(wrapper.text()).toContain('Luna');
        expect(wrapper.emitted('save-trainer')).toHaveLength(1);
        expect(wrapper.emitted('cancel-date')).toHaveLength(1);
    });

    it('renders customers and emits row selection', async () => {
        const wrapper = mountComponent(CustomersList, {
            props: {
                loading: false,
                customers: [baseCustomer],
                sort: { column: 'createdAt', direction: 'desc' },
                columns: [
                    { key: 'name', label: 'Name' },
                    { key: 'email', label: 'E-Mail' },
                    { key: 'createdAt', label: 'Seit' },
                    { key: 'address', label: 'Ort' },
                ],
                resultSummary: '1 Kunde',
                showPagination: true,
                currentPage: 1,
                pageSize: 20,
                totalCustomers: 1,
            },
        });

        await wrapper.get('[data-testid="customer-row-customer-1"]').trigger('click');

        expect(wrapper.text()).toContain('max@example.com');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
        expect(wrapper.emitted('select')).toHaveLength(1);
    });

    it('renders the admin customer loading state', () => {
        const wrapper = mountComponent(CustomerLoadingState);

        expect(wrapper.text()).toContain('loading');
    });

    it('renders customer info details with formatted registration date', () => {
        const wrapper = mountComponent(CustomerInfoCard, {
            props: {
                customer: baseCustomer,
            },
        });

        expect(wrapper.text()).toContain('Kundendaten');
        expect(wrapper.text()).toContain('formatted:2026-04-01T10:00:00+02:00');
    });

    it('normalizes credit adjustments and emits field clearing', async () => {
        const wrapper = mountComponent(CreditAdjustCard, {
            props: {
                balance: 4,
                adjustAmount: null,
                adjustDescription: '',
                amountError: '',
                descriptionError: '',
                formError: '',
                saving: false,
            },
        });

        await wrapper.get('input[type="number"]').setValue('5');
        await wrapper.get('input[placeholder="Grund der Korrektur"]').setValue('Sondergutschrift');
        await wrapper.get('form').trigger('submit.prevent');

        expect(wrapper.text()).toContain('Guthaben');
        expect(wrapper.emitted('update:adjustAmount')?.[0]).toEqual([5]);
        expect(wrapper.emitted('clear-field-error')?.[0]).toEqual(['amount']);
        expect(wrapper.emitted('submit')).toHaveLength(1);
    });
});
