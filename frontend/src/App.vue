<script setup lang="ts">
import Retry from '@components/Retry/RetryComponent.vue';
import FileUpload from '@components/FileUpload.vue';
import { useWebSocket } from '@composables/useWebSocket';
import { onMounted } from 'vue';
import { useStorage } from '@vueuse/core';
import axios, { AxiosError } from 'axios';

const props = defineProps<{
  websocketsServer: string;
  apiServer: string;
}>();

const {
  webSocket,
  status: webSocketStatus,
  connect: webSocketConnect,
} = useWebSocket(props.websocketsServer);

const userToken = useStorage('user-token', '');

onMounted(async () => {
  if (userToken.value == '') {
    await axios
      .post(`${props.apiServer}/auth`)
      .then((response) => {
        const userTokenData = response.data;
        userToken.value = userTokenData['token'];
      })
      .catch(() => {
        console.error('Failed to authenticate!');
      });
  }

  await axios
    .get(`${props.apiServer}/videos?token=${userToken.value}`)
    .then((response) => {
      console.log('videos: ', response.data);
    })
    .catch(() => {
      console.error('Failed to fetch videos!');
    });
});

const out = console;
</script>

<template>
  <main class="p-4">
    <h1 class="text-2xl font-semibold text-center mb-6">Videos Resize App</h1>

    <section class="flex flex-col gap-4">
      <h2 class="sr-only">Upload new file</h2>

      <FileUpload
        :action="`${props.apiServer}/upload`"
        :token="userToken"
        @error="(error) => out.error(error)"
        @file-uploaded="(response) => out.log(response)"
      />

      <button class="btn btn-primary self-center" @click="webSocket?.send('hey')">
        Send message to websockets
      </button>
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
