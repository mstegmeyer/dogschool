import { mount } from '@vue/test-utils';
import { computed, defineComponent, h, reactive, ref } from 'vue';
import { vi } from 'vitest';
import { type Component } from 'vue';
import {
    type MountingOptions,
    type VueWrapper,
} from '@vue/test-utils';
import { trainer, course, archivedCourse, activeContract, pendingContract, customerRecord, notification, todayCourseDate } from '../modules/admin-page-helpers';
import { bookedCourseDate, creditTransaction, dog } from '../modules/customer-page-helpers';
import { installNuxtGlobals, namedStub } from '../nuxt/page-test-utils';
import {
    NuxtLinkStub,
    UAlertStub,
    UBadgeStub,
    UButtonGroupStub,
    UIconStub,
    UModalStub,
    UPaginationStub,
    USkeletonStub,
    UTabsStub,
    createFormFeedbackState,
    uiPageStubs,
} from '../nuxt/ui-test-stubs';

type ComponentMountOptions = Omit<MountingOptions<any>, 'global'> & {
    global?: MountingOptions<any>['global'],
};

export const baseTrainer = trainer;
export const baseCourse = course;
export const baseArchivedCourse = archivedCourse;
export const baseContract = activeContract;
export const basePendingContract = pendingContract;
export const baseCustomer = customerRecord;
export const baseNotification = notification;
export const baseCourseDate = todayCourseDate;
export const baseBookedCourseDate = {
    ...bookedCourseDate,
    trainer: trainer,
    courseType: todayCourseDate.courseType,
    level: todayCourseDate.level,
};
export const baseDog = dog;
export const baseCreditTransaction = creditTransaction;

export const nextWeeklyGrant = {
    contractId: 'contract-1',
    dogName: dog.name,
    amount: 2,
    nextGrantAt: '2026-04-08T08:00:00+02:00',
    pendingGrantThisWeek: true,
};

export const courseTypeForm = {
    code: 'AGI',
    name: 'Agility',
    recurrenceKind: 'RECURRING',
};

export const recurrenceOptions = [
    { label: 'Wiederkehrend', value: 'RECURRING' },
    { label: 'Einmalig', value: 'ONE_TIME' },
];

export const adminNotificationForm = {
    courseIds: ['course-1'],
    title: 'Hinweis',
    message: 'Bitte pünktlich sein.',
    isGlobal: false,
    pinnedUntil: '2026-04-10',
};

export const courseForm = {
    typeCode: 'AGI',
    dayOfWeek: 2,
    level: 1,
    startTime: '10:00',
    endTime: '11:00',
    trainerId: trainer.id,
    comment: 'Bring treats',
};

export const profileForm = {
    name: 'Max Mustermann',
    email: 'max@example.com',
    password: '',
    address: {
        street: 'Hauptstr. 1',
        postalCode: '12345',
        city: 'Berlin',
    },
    bankAccount: {
        iban: 'DE1234567890',
        bic: 'BANKDEFFXXX',
        accountHolder: 'Max Mustermann',
    },
};

export const routeMockState = reactive({
    path: '/',
    fullPath: '/',
    params: {} as Record<string, string>,
    meta: {} as Record<string, unknown>,
});

export function setRoute(path: string, params: Record<string, string> = {}, meta: Record<string, unknown> = {}): void {
    routeMockState.path = path;
    routeMockState.fullPath = path;
    routeMockState.params = params;
    routeMockState.meta = meta;
}

export const navigateToMock = vi.fn();
export const logoutMock = vi.fn();
export const fetchProfileMock = vi.fn();
export const toastAddMock = vi.fn();
export const apiGetMock = vi.fn();
export const apiPostMock = vi.fn();
export const apiPutMock = vi.fn();
export const apiDelMock = vi.fn();
export const refreshStatusMock = vi.fn();
export const enablePushMock = vi.fn();
export const disablePushMock = vi.fn();
export const clipboardWriteMock = vi.fn().mockResolvedValue(undefined);
export const pushStatusRef = ref<'enabled' | 'install-required' | 'blocked' | 'available' | 'error' | 'unsupported'>('available');
export const pushErrorRef = ref('');
export const userRef = ref({
    id: baseCustomer.id,
    name: baseCustomer.name,
    email: baseCustomer.email,
});

export const UTextareaStub = defineComponent({
    name: 'u-textarea-stub',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: String, default: '' },
        placeholder: { type: String, default: '' },
        rows: { type: Number, default: 3 },
    },
    setup(props, { attrs, emit }) {
        return () => h('textarea', {
            ...attrs,
            value: props.modelValue,
            placeholder: props.placeholder,
            rows: props.rows,
            onInput: (event: Event) => emit('update:modelValue', (event.target as HTMLTextAreaElement).value),
        });
    },
});

export const UCardStub = defineComponent({
    name: 'u-card-stub',
    inheritAttrs: false,
    setup(_, { attrs, slots }) {
        return () => h('div', attrs, [
            slots.header?.(),
            slots.default?.(),
            slots.footer?.(),
        ]);
    },
});

export const UFormGroupStub = defineComponent({
    name: 'u-form-group-stub',
    inheritAttrs: false,
    props: {
        label: { type: String, default: '' },
        error: { type: String, default: '' },
        help: { type: String, default: '' },
    },
    setup(props, { attrs, slots }) {
        return () => h('label', attrs, [
            props.label ? h('span', props.label) : null,
            slots.default?.(),
            props.help ? h('small', props.help) : null,
            props.error ? h('p', { class: 'field-error' }, props.error) : null,
        ]);
    },
});

export const UInputStub = defineComponent({
    name: 'u-input-stub',
    inheritAttrs: false,
    emits: ['update:modelValue', 'change'],
    props: {
        modelValue: { type: [String, Number], default: '' },
        modelModifiers: { type: Object, default: () => ({}) },
        type: { type: String, default: 'text' },
        placeholder: { type: String, default: '' },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
    },
    setup(props, { attrs, emit }) {
        return () => h('input', {
            ...attrs,
            value: props.modelValue,
            type: props.type,
            placeholder: props.placeholder,
            disabled: props.disabled,
            readonly: props.readonly,
            onInput: (event: Event) => {
                const target = event.target as HTMLInputElement;
                const value = props.modelModifiers?.number && target.value !== '' ? Number(target.value) : target.value;
                emit('update:modelValue', value);
            },
            onChange: (event: Event) => emit('change', event),
        });
    },
});

export const UButtonStub = defineComponent({
    name: 'u-button-stub',
    inheritAttrs: false,
    emits: ['click'],
    props: {
        label: { type: String, default: '' },
        type: { type: String, default: 'button' },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
    },
    setup(props, { attrs, emit, slots }) {
        return () => h('button', {
            ...attrs,
            type: props.type,
            disabled: props.disabled,
            'data-loading': String(props.loading),
            onClick: (event: Event) => emit('click', event),
        }, slots.default?.() ?? props.label);
    },
});

export const USelectMenuStub = defineComponent({
    name: 'u-select-menu-stub',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: [String, Array], default: '' },
        options: { type: Array, default: () => [] },
        placeholder: { type: String, default: '' },
        valueAttribute: { type: String, default: 'value' },
        multiple: { type: Boolean, default: false },
    },
    setup(props, { attrs, emit }) {
        return () => h('select', {
            ...attrs,
            value: props.modelValue,
            multiple: props.multiple,
            onChange: (event: Event) => emit('update:modelValue', (event.target as HTMLSelectElement).value),
        }, [
            h('option', { value: '' }, props.placeholder || 'Auswählen'),
            ...props.options.map((option: any) => h('option', {
                value: option[props.valueAttribute],
            }, option.label)),
        ]);
    },
});

export const UToggleStub = defineComponent({
    name: 'u-toggle-stub',
    inheritAttrs: false,
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: Boolean, default: false },
    },
    setup(props, { attrs, emit }) {
        return () => h('input', {
            ...attrs,
            type: 'checkbox',
            checked: props.modelValue,
            onChange: (event: Event) => emit('update:modelValue', (event.target as HTMLInputElement).checked),
        });
    },
});

export const UDividerStub = defineComponent({
    name: 'u-divider-stub',
    props: {
        label: { type: String, default: '' },
    },
    setup(props) {
        return () => h('div', { class: 'u-divider-stub' }, props.label);
    },
});

export const UTooltipStub = defineComponent({
    name: 'u-tooltip-stub',
    props: {
        text: { type: String, default: '' },
    },
    setup(props, { slots }) {
        return () => h('div', { title: props.text }, slots.default?.());
    },
});

export const UVerticalNavigationStub = defineComponent({
    name: 'u-vertical-navigation-stub',
    emits: ['click'],
    props: {
        links: { type: Array, default: () => [] },
    },
    setup(props, { emit }) {
        return () => h('nav', (props.links as Array<{ label: string }>).map(link => h('button', {
            type: 'button',
            'data-testid': `nav-link-${link.label}`,
            onClick: () => emit('click'),
        }, link.label)));
    },
});

export const UDropdownStub = defineComponent({
    name: 'u-dropdown-stub',
    props: {
        items: { type: Array, default: () => [] },
    },
    setup(props, { slots }) {
        const flattenedItems = computed(() =>
            (props.items as any[]).flatMap(group => Array.isArray(group) ? group : [group]),
        );

        return () => h('div', [
            slots.default?.(),
            ...flattenedItems.value.map((item: { label: string; click?: () => void }) => h('button', {
                type: 'button',
                onClick: () => item.click?.(),
            }, item.label)),
        ]);
    },
});

export const UTableStub = defineComponent({
    name: 'u-table-stub',
    emits: ['select', 'update:sort'],
    props: {
        columns: { type: Array, default: () => [] },
        rows: { type: Array, default: () => [] },
        sort: { type: Object, default: undefined },
    },
    setup(props, { slots, emit }) {
        return () => h('div', { class: 'u-table-stub' }, (props.rows as any[]).map((row, rowIndex) => h('div', {
            'data-testid': `table-row-${rowIndex}`,
            onClick: () => emit('select', row),
        }, (props.columns as Array<{ key: string }>).map(column => {
            const slot = slots[`${column.key}-data`];
            return h('div', { 'data-testid': `table-cell-${column.key}-${rowIndex}` }, slot
                ? slot({ row })
                : String((row as Record<string, unknown>)[column.key] ?? ''));
        }))));
    },
});

export const USlideoverStub = defineComponent({
    name: 'u-slideover-stub',
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: Boolean, default: false },
    },
    setup(props, { slots }) {
        return () => props.modelValue
            ? h('div', { class: 'u-slideover-stub' }, slots.default?.())
            : null;
    },
});

export const NuxtLayoutStub = defineComponent({
    name: 'nuxt-layout-stub',
    props: {
        name: { type: String, default: 'default' },
    },
    setup(props, { slots }) {
        return () => h('div', { 'data-layout': props.name }, slots.default?.());
    },
});

export const NuxtPageStub = defineComponent({
    name: 'nuxt-page-stub',
    setup() {
        return () => h('div', { 'data-testid': 'nuxt-page-stub' });
    },
});

export const ClientOnlyStub = defineComponent({
    name: 'client-only-stub',
    setup(_, { slots }) {
        return () => slots.default?.();
    },
});

export const componentStubs = {
    ...uiPageStubs,
    AppLogo: namedStub('AppLogo', ['tone', 'size']),
    AppNotificationDetail: namedStub('AppNotificationDetail', ['notification', 'maxVisibleCourses']),
    AppSkeletonCalendar: namedStub('AppSkeletonCalendar'),
    ClientOnly: ClientOnlyStub,
    NuxtLayout: NuxtLayoutStub,
    NuxtLink: NuxtLinkStub,
    NuxtPage: NuxtPageStub,
    UAlert: UAlertStub,
    UBadge: UBadgeStub,
    UButton: UButtonStub,
    UButtonGroup: UButtonGroupStub,
    UCard: UCardStub,
    UDivider: UDividerStub,
    UDropdown: UDropdownStub,
    UFormGroup: UFormGroupStub,
    UIcon: UIconStub,
    UInput: UInputStub,
    UModal: UModalStub,
    UPagination: UPaginationStub,
    USelectMenu: USelectMenuStub,
    USkeleton: USkeletonStub,
    USlideover: USlideoverStub,
    UTabs: UTabsStub,
    UTable: UTableStub,
    UTextarea: UTextareaStub,
    UToggle: UToggleStub,
    UTooltip: UTooltipStub,
    UVerticalNavigation: UVerticalNavigationStub,
};

export function installComponentGlobals(options: {
    path?: string,
    params?: Record<string, string>,
    meta?: Record<string, unknown>,
    user?: { id: string; name: string; email: string },
} = {}) {
    vi.clearAllMocks();
    installNuxtGlobals();

    setRoute(options.path ?? '/', options.params, options.meta);
    userRef.value = options.user ?? {
        id: baseCustomer.id,
        name: baseCustomer.name,
        email: baseCustomer.email,
    };
    pushStatusRef.value = 'available';
    pushErrorRef.value = '';

    apiGetMock.mockResolvedValue({ items: [] });
    apiPostMock.mockResolvedValue({});
    apiPutMock.mockResolvedValue({});
    apiDelMock.mockResolvedValue({});
    fetchProfileMock.mockResolvedValue(undefined);
    refreshStatusMock.mockResolvedValue(undefined);
    enablePushMock.mockResolvedValue(undefined);
    disablePushMock.mockResolvedValue(undefined);

    vi.stubGlobal('useRoute', () => routeMockState);
    vi.stubGlobal('navigateTo', navigateToMock);
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
        logout: logoutMock,
        fetchProfile: fetchProfileMock,
        token: ref('jwt-token'),
        role: ref(routeMockState.path.startsWith('/admin') ? 'ADMIN' : 'CUSTOMER'),
        isAuthenticated: computed(() => true),
        isAdmin: computed(() => routeMockState.path.startsWith('/admin')),
        isCustomer: computed(() => routeMockState.path.startsWith('/customer')),
    }));
    vi.stubGlobal('usePushNotifications', () => ({
        pushStatus: pushStatusRef,
        pushError: pushErrorRef,
        refreshStatus: refreshStatusMock,
        enablePush: enablePushMock,
        disablePush: disablePushMock,
    }));
    vi.stubGlobal('useRuntimeConfig', () => ({
        public: {
            apiBaseUrl: 'https://api.example.test',
        },
    }));
    vi.stubGlobal('useFormFeedback', () => createFormFeedbackState());
    vi.stubGlobal('extractApiErrorMessage', (cause: unknown, fallback: string) =>
        cause instanceof Error ? cause.message : fallback,
    );
    vi.stubGlobal('useHelpers', () => ({
        todayIso: () => '2026-04-04',
        getWeekMonday: () => '2026-03-30',
        dayName: (dayOfWeek: number) => ['', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'][dayOfWeek] || '',
        levelLabel: (level: number) => `Stufe ${level}`,
        formatDate: (value: string) => `formatted:${value}`,
        formatDateTime: (value: string) => `formatted:${value}`,
        formatCourseTitleWithLevel: (name: string | null | undefined, level: number | null | undefined) =>
            `${name ?? 'Kurs'}${typeof level === 'number' ? ` L${level}` : ''}`,
        formatNotificationCourse: (courseValue: { typeName?: string | null; dayOfWeek: number; startTime: string }) =>
            `${courseValue.typeName ?? 'Kurs'}-${courseValue.dayOfWeek}-${courseValue.startTime}`,
        formatNotificationCourses: (courses: Array<{ typeName?: string | null; dayOfWeek: number; startTime: string }>) =>
            courses.map(courseValue => `${courseValue.typeName ?? 'Kurs'}-${courseValue.dayOfWeek}-${courseValue.startTime}`).join(', '),
        contractStateLabel: (state: string) => state,
        contractStateColor: (state: string) => state === 'ACTIVE' ? 'green' : 'gray',
        hotelBookingStateLabel: (state: string) => state,
        hotelBookingStateColor: (state: string) => state === 'CONFIRMED' ? 'green' : state === 'REQUESTED' ? 'amber' : 'red',
        formatContractMonthlyPrice: (price: string) => `${price} EUR`,
        formatSquareMeters: (value: number) => `${value} m²`,
        creditTypeLabel: (type: string) => type,
        toMonthEndIso: (value: string) => value.slice(0, 8) + '30',
        isLastOfMonth: (value: string) => value.endsWith('-30'),
        toMonthStartIso: (value: string) => value.slice(0, 8) + '01',
        isFirstOfMonth: (value: string) => value.endsWith('-01'),
        firstDayOfNextMonthIso: () => '2026-05-01',
    }));

    Object.defineProperty(globalThis.navigator, 'clipboard', {
        value: { writeText: clipboardWriteMock },
        configurable: true,
    });

    return {
        apiGetMock,
        apiPostMock,
        apiPutMock,
        apiDelMock,
        clipboardWriteMock,
        disablePushMock,
        enablePushMock,
        fetchProfileMock,
        logoutMock,
        navigateToMock,
        refreshStatusMock,
        routeMockState,
        toastAddMock,
    };
}

export function mountComponent<T extends Component>(component: T, options: ComponentMountOptions = {}): VueWrapper<any> {
    return mount(component as any, {
        ...options,
        global: {
            ...(options.global ?? {}),
            stubs: {
                ...componentStubs,
                ...(options.global?.stubs ?? {}),
            },
        },
    });
}

export function makeCourseDate(overrides: Record<string, unknown> = {}) {
    return {
        ...baseCourseDate,
        trainer: baseTrainer,
        bookings: [],
        subscriberCount: 2,
        subscribers: [{ id: baseCustomer.id, name: baseCustomer.name }],
        ...overrides,
    };
}

export function makeNotification(overrides: Record<string, unknown> = {}) {
    return {
        ...baseNotification,
        courses: [
            { id: baseCourse.id, typeCode: baseCourse.type.code, typeName: baseCourse.type.name, dayOfWeek: baseCourse.dayOfWeek, startTime: baseCourse.startTime, endTime: baseCourse.endTime },
        ],
        courseIds: [baseCourse.id],
        ...overrides,
    };
}

export function makeCustomerCourseDetail(overrides: Record<string, unknown> = {}) {
    return {
        upcomingDates: [makeCourseDate()],
        notifications: [makeNotification()],
        ...overrides,
    };
}

export function makeCourseGroup(overrides: Record<string, unknown> = {}) {
    return {
        dayOfWeek: baseCourse.dayOfWeek,
        courses: [baseCourse],
        ...overrides,
    };
}

export function makeCreditColumns() {
    return [
        { key: 'amount', label: 'Betrag' },
        { key: 'type', label: 'Typ' },
        { key: 'description', label: 'Beschreibung' },
        { key: 'createdAt', label: 'Datum' },
    ];
}
