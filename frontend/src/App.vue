<script setup lang="ts">
import Retry from '@components/Retry/RetryComponent.vue';
import VideoCard from '@components/VideoCard.vue';
import FileUpload from '@components/FileUpload.vue';
import { useWebSocket } from '@composables/useWebSocket';

import { Api, Video } from '@root/Api';

import { onMounted, ref } from 'vue';
import { useStorage } from '@vueuse/core';
import axios, { AxiosError } from 'axios';
import to from 'await-to-js';

const props = defineProps<{
  websocketsServer: string;
  apiServer: string;
}>();

const api = new Api(props.apiServer);

const { status: webSocketStatus, connect: webSocketConnect } = useWebSocket(props.websocketsServer);

const userToken = useStorage<string>('user-token', null);

const videos = ref<Video[]>([]);

onMounted(async () => {
  if (userToken.value == null || !(await api.authCheck(userToken.value))) {
    const [_, fetchedUserToken] = await to(api.auth());
    if (fetchedUserToken == null) {
      throw new Error('Failed to authenticate.');
    }
    userToken.value = fetchedUserToken;
  }

  const [_, fetchedVideos] = await to(api.videos(userToken.value));
  if (fetchedVideos == null) {
    throw new Error('Failed to fetch videos.');
  }
  videos.value = fetchedVideos.sort(Video.sortByDate);
});

const onFileUploaded = (data: any) => {
  videos.value.push(Video.parseFromJson(data));
  videos.value.sort((a, b) => b.expiresAt.getTime() - a.expiresAt.getTime());
};

const deleteFile = (key: string) => {
  const deletedVideos = videos.value.splice(
    videos.value.findIndex((video) => video.key == key),
    1,
  );

  for (const deletedVideo of deletedVideos) {
  }
};

const out = console;
</script>

<template>
  <main class="p-4 flex flex-col gap-8">
    <h1 class="text-2xl font-semibold text-center">Videos resize app</h1>

    <section class="flex flex-col gap-4">
      <h2 class="sr-only">Upload new file</h2>

      <FileUpload
        :action="api.uploadUrl()"
        :token="userToken"
        @error="(error) => out.error(error)"
        @file-uploaded="onFileUploaded"
      />
    </section>

    <section>
      <h2 class="sr-only">Uploaded videos</h2>

      <TransitionGroup name="list" tag="div" class="">
        <VideoCard
          :video="video"
          v-for="video in videos"
          :key="video.key"
          @video-deleted="deleteFile"
          class="mb-4"
        />
      </TransitionGroup>
    </section>

    <aside class="toast">
      <Retry :workload="webSocketConnect" v-model:status="webSocketStatus">
        <template #error>Failed to connect to the server.</template>
        <template #retrying>Trying to connect to the server...</template>
        <template #success>Successfully connected!</template>
      </Retry>
    </aside>
  </main>
</template>

<style>
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(250px);
}

.list-leave-active {
  position: absolute;
}
</style>
