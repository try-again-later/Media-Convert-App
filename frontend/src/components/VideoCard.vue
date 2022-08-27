<script setup lang="ts">
import { Video } from '@root/Api';
import { formatRelative } from 'date-fns';
import { enGB } from 'date-fns/esm/locale';

defineProps<{
  video: Video;
}>();

defineEmits<{
  (e: 'video-deleted', key: string): void;
}>();
</script>

<template>
  <article class="bg-gray-50 p-4 rounded-md ring-2 ring-gray-200 flex gap-4">
    <img
      :src="video.thumbnailUrl ?? ''"
      alt="Thumbnail"
      aria-hidden="true"
      class="rounded bg-gray-300 self-start max-w-[8rem] max-h-[8rem] ring-1 ring-gray-100"
    />
    <div class="flex flex-col">
      <h2 class="text-lg font-semibold break-all">{{ video.originalName }}</h2>
      <a :href="video.url" class="link link-primary" target="_blank">Original video</a>
      <p class="text-sm mt-auto">
        Expires {{ formatRelative(video.expiresAt, new Date(), { locale: enGB }) }}
      </p>
    </div>
    <button
      type="button"
      class="btn btn-secondary text-secondary-content ml-auto self-center"
      @click="$emit('video-deleted', video.key)"
    >
      Delete
    </button>
  </article>
</template>
