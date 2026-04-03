<template>
  <div class="space-y-4">
    <div class="flex gap-2">
      <USkeleton class="h-9 w-36 rounded-md" />
      <USkeleton class="h-9 w-28 rounded-md" />
    </div>
    <div
      v-for="index in 3"
      :key="index"
      class="overflow-hidden rounded-lg border border-slate-200 bg-white"
    >
      <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-3 py-2">
        <USkeleton class="h-4 w-24 rounded-md" />
        <USkeleton class="h-4 w-14 rounded-md" />
      </div>
      <div class="space-y-3 p-3">
        <div v-for="row in 3" :key="row" class="rounded-lg border border-slate-100 p-3">
          <USkeleton class="h-4 w-32 rounded-md" />
          <USkeleton class="mt-2 h-3 w-24 rounded-md" />
          <div class="mt-3 grid grid-cols-2 gap-3">
            <USkeleton class="h-3 w-full rounded-md" />
            <USkeleton class="h-3 w-full rounded-md" />
          </div>
          <USkeleton class="mt-4 h-9 w-full rounded-md" />
        </div>
      </div>
    </div>
  </div>
</template>
