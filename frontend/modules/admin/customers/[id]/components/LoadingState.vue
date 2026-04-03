<template>
  <div>
    <div class="mb-6 flex items-center gap-3">
      <USkeleton class="h-8 w-8 rounded-md" />
      <USkeleton class="h-8 w-40 rounded-md" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <UCard class="lg:col-span-2">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-for="index in 7" :key="index" class="space-y-2">
            <USkeleton class="h-3 w-16 rounded-md" />
            <USkeleton class="h-4 w-full rounded-md" />
          </div>
        </div>
      </UCard>

      <UCard>
        <div class="space-y-4">
          <div class="text-center">
            <USkeleton class="mx-auto h-10 w-20 rounded-md" />
            <USkeleton class="mx-auto mt-2 h-3 w-16 rounded-md" />
          </div>
          <div v-for="index in 2" :key="index" class="space-y-2">
            <USkeleton class="h-4 w-24 rounded-md" />
            <USkeleton class="h-10 w-full rounded-md" />
          </div>
          <USkeleton class="h-9 w-full rounded-md" />
        </div>
      </UCard>
    </div>

    <UCard class="mt-6">
      <template #header>
        <USkeleton class="h-5 w-40 rounded-md" />
      </template>
      <AppSkeletonCollection
        :mobile-cards="4"
        :desktop-rows="6"
        :desktop-columns="4"
        :meta-columns="0"
        :content-lines="2"
        :show-badge="true"
      />
    </UCard>
  </div>
</template>
