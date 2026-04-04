import { beforeEach, describe, expect, it } from 'vitest';
import AdminCourseArchiveModal from './ArchiveModal.vue';
import {
    baseCourse,
    installComponentGlobals,
    mountComponent,
} from '~/tests/components/component-test-helpers';

describe('AdminCourseArchiveModal', () => {
    beforeEach(() => {
        installComponentGlobals({ path: '/admin' });
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
});
