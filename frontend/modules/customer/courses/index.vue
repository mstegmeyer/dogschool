<template>
<div>
    <h1 class='mb-4 text-2xl font-bold text-slate-800'>
        Kurse
    </h1>

    <CustomerCoursesLoadingState v-if='loading' />
    <UTabs v-else :items='tabs'>
        <template #item='{ item }'>
            <div class='pt-3'>
                <div v-if="item.key === 'available'" class='space-y-4'>
                    <CustomerCourseGroupSection
                        v-for='group in groupedAvailable'
                        :key='group.dayOfWeek'
                        :group='group'
                        variant='available'
                        :subscribed-ids='subscribedIds'
                        @select='openCourseDetail'
                        @subscribe='subscribe'
                        @unsubscribe='unsubscribe'
                    />
                    <p v-if='availableCourses.length === 0' class='py-8 text-center text-sm text-slate-400'>
                        Keine Kurse verfügbar.
                    </p>
                </div>

                <div v-else>
                    <div v-if='subscribedCourses.length === 0' class='py-8 text-center text-sm text-slate-400'>
                        Du hast noch keine Kurse abonniert.
                    </div>
                    <div v-else class='space-y-4'>
                        <CustomerCourseGroupSection
                            v-for='group in groupedSubscribed'
                            :key='group.dayOfWeek'
                            :group='group'
                            variant='subscribed'
                            :subscribed-ids='subscribedIds'
                            @select='openCourseDetail'
                            @subscribe='subscribe'
                            @unsubscribe='unsubscribe'
                        />
                    </div>
                </div>
            </div>
        </template>
    </UTabs>

    <CustomerCourseDetailModal
        v-model='showCourseDetailModal'
        :course='selectedCourse'
        :course-detail='selectedCourseDetail'
        :loading='courseDetailLoading'
    />
</div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { ApiListResponse, Course, CustomerCourseDetailResponse } from '~/types';
import CustomerCourseDetailModal from './components/DetailModal.vue';
import CustomerCourseGroupSection from './components/CourseGroupSection.vue';
import CustomerCoursesLoadingState from './components/LoadingState.vue';

const api = useApi();
const toast = useToast();

const availableCourses = ref<Course[]>([]);
const subscribedCourses = ref<Course[]>([]);
const loading = ref(true);
const showCourseDetailModal = ref(false);
const selectedCourse = ref<Course | null>(null);
const selectedCourseDetail = ref<CustomerCourseDetailResponse | null>(null);
const courseDetailLoading = ref(false);
let detailRequestId = 0;

const tabs = [
    { key: 'available', label: 'Verfügbare Kurse' },
    { key: 'subscribed', label: 'Meine Kurse' },
];

function sortByDayThenTime(courses: Course[]): Course[] {
    return [...courses].sort((left, right) => {
        if (left.dayOfWeek !== right.dayOfWeek) {
            return left.dayOfWeek - right.dayOfWeek;
        }
        return left.startTime.localeCompare(right.startTime);
    });
}

function groupByWeekday(courses: Course[]): Array<{ dayOfWeek: number; courses: Course[] }> {
    const sorted = sortByDayThenTime(courses);
    const groups = new Map<number, Course[]>();

    for (const course of sorted) {
        const list = groups.get(course.dayOfWeek) ?? [];
        list.push(course);
        groups.set(course.dayOfWeek, list);
    }

    return [...groups.entries()]
        .sort((left, right) => left[0] - right[0])
        .map(([dayOfWeek, groupCourses]) => ({ dayOfWeek, courses: groupCourses }));
}

const groupedAvailable = computed(() => groupByWeekday(availableCourses.value));
const groupedSubscribed = computed(() => groupByWeekday(subscribedCourses.value));
const subscribedIds = computed(() => new Set(subscribedCourses.value.map(course => course.id)));

async function openCourseDetail(course: Course): Promise<void> {
    selectedCourse.value = course;
    showCourseDetailModal.value = true;
    selectedCourseDetail.value = null;
    courseDetailLoading.value = true;

    const requestId = ++detailRequestId;

    try {
        const detail = await api.get<CustomerCourseDetailResponse>(`/api/customer/courses/${course.id}/detail`);
        if (requestId !== detailRequestId) {
            return;
        }
        selectedCourseDetail.value = detail;
    } catch {
        if (requestId !== detailRequestId) {
            return;
        }

        toast.add({
            title: 'Kursdetails konnten nicht geladen werden',
            description: 'Bitte versuche es gleich noch einmal.',
            color: 'red',
        });
        showCourseDetailModal.value = false;
    } finally {
        if (requestId === detailRequestId) {
            courseDetailLoading.value = false;
        }
    }
}

async function subscribe(course: Course): Promise<void> {
    await api.post(`/api/customer/courses/${course.id}/subscribe`);
    toast.add({ title: `${course.type?.name} abonniert`, color: 'green' });
    await loadCourses();
}

async function unsubscribe(course: Course): Promise<void> {
    await api.del(`/api/customer/courses/${course.id}/subscribe`);
    toast.add({ title: `${course.type?.name} abbestellt`, color: 'amber' });
    await loadCourses();
}

async function loadCourses(): Promise<void> {
    loading.value = true;
    try {
        const [allResponse, subscribedResponse] = await Promise.all([
            api.get<ApiListResponse<Course>>('/api/customer/courses'),
            api.get<ApiListResponse<Course>>('/api/customer/courses/subscribed'),
        ]);

        availableCourses.value = allResponse.items;
        subscribedCourses.value = subscribedResponse.items;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void loadCourses();
});

watch(showCourseDetailModal, (isOpen) => {
    if (isOpen) {
        return;
    }

    detailRequestId += 1;
    courseDetailLoading.value = false;
    selectedCourse.value = null;
    selectedCourseDetail.value = null;
});
</script>
