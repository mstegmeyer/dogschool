import { defineComponent, h, ref } from 'vue';

type FieldErrors = Record<string, string>;

export function createFormFeedbackState() {
    const fieldErrors = ref<FieldErrors>({});
    const formError = ref('');

    return {
        formError,
        clearFormErrors() {
            fieldErrors.value = {};
            formError.value = '';
        },
        clearFieldError(field: string) {
            const nextErrors = { ...fieldErrors.value };
            delete nextErrors[field];
            fieldErrors.value = nextErrors;
        },
        setFieldError(field: string, message: string) {
            fieldErrors.value = {
                ...fieldErrors.value,
                [field]: message,
            };
        },
        setFormError(message: string) {
            formError.value = message;
        },
        applyApiError(cause: unknown, fallback: string) {
            const message = cause instanceof Error ? cause.message : fallback;
            formError.value = message || fallback;
        },
        errorFor(field: string) {
            return fieldErrors.value[field] || '';
        },
    };
}

export const UCardStub = defineComponent({
    name: 'u-card-stub',
    setup(_, { slots }) {
        return () => h('div', [
            slots.header?.(),
            slots.default?.(),
            slots.footer?.(),
        ]);
    },
});

export const UFormGroupStub = defineComponent({
    name: 'u-form-group-stub',
    props: {
        label: { type: String, default: '' },
        error: { type: String, default: '' },
        help: { type: String, default: '' },
    },
    setup(props, { slots }) {
        return () => h('label', [
            props.label ? h('span', props.label) : null,
            slots.default?.(),
            props.help ? h('small', props.help) : null,
            props.error ? h('p', { class: 'field-error' }, props.error) : null,
        ]);
    },
});

export const UInputStub = defineComponent({
    name: 'u-input-stub',
    emits: ['update:modelValue', 'change'],
    props: {
        modelValue: { type: [String, Number], default: '' },
        modelModifiers: { type: Object, default: () => ({}) },
        type: { type: String, default: 'text' },
        placeholder: { type: String, default: '' },
        disabled: { type: Boolean, default: false },
        readonly: { type: Boolean, default: false },
    },
    setup(props, { emit }) {
        return () => h('input', {
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
    emits: ['click'],
    props: {
        label: { type: String, default: '' },
        type: { type: String, default: 'button' },
        disabled: { type: Boolean, default: false },
        loading: { type: Boolean, default: false },
    },
    setup(props, { emit, slots }) {
        return () => h('button', {
            type: props.type,
            disabled: props.disabled,
            'data-loading': String(props.loading),
            onClick: (event: Event) => emit('click', event),
        }, slots.default?.() ?? props.label);
    },
});

export const UAlertStub = defineComponent({
    name: 'u-alert-stub',
    props: {
        title: { type: String, default: '' },
        description: { type: String, default: '' },
    },
    setup(props) {
        return () => h('div', [props.title, props.description].filter(Boolean).join(' '));
    },
});

export const UTabsStub = defineComponent({
    name: 'u-tabs-stub',
    emits: ['change'],
    props: {
        items: { type: Array, default: () => [] },
    },
    setup(props, { emit, slots }) {
        const activeIndex = ref(0);

        return () => h('div', [
            h('div', props.items.map((item: any, index: number) => h('button', {
                type: 'button',
                onClick: () => {
                    activeIndex.value = index;
                    emit('change', index);
                },
            }, item.label))),
            slots.item?.({ item: props.items[activeIndex.value] }),
        ]);
    },
});

export const UModalStub = defineComponent({
    name: 'u-modal-stub',
    props: {
        modelValue: { type: Boolean, default: false },
    },
    setup(props, { slots }) {
        return () => props.modelValue ? h('div', slots.default?.()) : null;
    },
});

export const USelectMenuStub = defineComponent({
    name: 'u-select-menu-stub',
    emits: ['update:modelValue'],
    props: {
        modelValue: { type: String, default: '' },
        options: { type: Array, default: () => [] },
        placeholder: { type: String, default: '' },
        valueAttribute: { type: String, default: 'value' },
    },
    setup(props, { emit }) {
        return () => h('select', {
            value: props.modelValue,
            onChange: (event: Event) => emit('update:modelValue', (event.target as HTMLSelectElement).value),
        }, [
            h('option', { value: '' }, props.placeholder || 'Auswählen'),
            ...props.options.map((option: any) => h('option', {
                value: option[props.valueAttribute],
            }, option.label)),
        ]);
    },
});

export const UBadgeStub = defineComponent({
    name: 'u-badge-stub',
    setup(_, { slots }) {
        return () => h('span', slots.default?.());
    },
});

export const UIconStub = defineComponent({
    name: 'u-icon-stub',
    setup() {
        return () => h('i');
    },
});

export const NuxtLinkStub = defineComponent({
    name: 'nuxt-link-stub',
    props: {
        to: { type: String, default: '' },
    },
    setup(props, { slots }) {
        return () => h('a', { href: props.to }, slots.default?.());
    },
});

export const uiPageStubs = {
    AppSkeletonCollection: defineComponent({
        name: 'app-skeleton-collection-stub',
        setup() {
            return () => h('div', 'loading');
        },
    }),
    Alert: UAlertStub,
    Badge: UBadgeStub,
    Button: UButtonStub,
    ButtonGroup: defineComponent({
        name: 'button-group-stub',
        setup(_, { slots }) {
            return () => h('div', slots.default?.());
        },
    }),
    Card: UCardStub,
    FormGroup: UFormGroupStub,
    Icon: UIconStub,
    Input: UInputStub,
    Modal: UModalStub,
    NuxtLink: NuxtLinkStub,
    SelectMenu: USelectMenuStub,
    Tabs: UTabsStub,
    UAlert: UAlertStub,
    UBadge: UBadgeStub,
    UButton: UButtonStub,
    UCard: UCardStub,
    UFormGroup: UFormGroupStub,
    UIcon: UIconStub,
    UInput: UInputStub,
    UModal: UModalStub,
    USelectMenu: USelectMenuStub,
    UTabs: UTabsStub,
};
