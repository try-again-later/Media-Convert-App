<script setup lang="ts">
import { PaperClipIcon } from '@heroicons/vue/24/outline';
import Retry from './components/Retry/RetryComponent.vue';
import ProgressBar from './components/ProgressBar.vue';

import { nextTick, ref } from 'vue';
import { Status } from './components/Retry/RetryComponent';
import axios from 'axios';

const props = defineProps<{
  websocketsServer: string;
  apiServer: string;
}>();

const connectionStatus = ref<Status>('first-try');
const connection = ref<WebSocket>();

const retryConnection = async () => {
  connection.value = await new Promise<WebSocket>((resolve, reject) => {
    const webSocket = new WebSocket(props.websocketsServer);

    const webSocketTimeout = setTimeout(() => {
      webSocket.close();
    }, 10_000);

    webSocket.onopen = () => {
      clearTimeout(webSocketTimeout);
      console.log('Established connection through websockets!');
      resolve(webSocket);
    };

    webSocket.onerror = () => {
      clearTimeout(webSocketTimeout);
      console.error('Failed to connect to the websockets server...');
      webSocket.close();
      reject();
    };

    webSocket.onclose = () => {
      clearTimeout(webSocketTimeout);
      console.log('Closed connection to the websockets server...');
      if (connectionStatus.value == 'first-try' || connectionStatus.value == 'success') {
        connectionStatus.value = 'waiting';
      }
      reject();
    };
  });
};

const fileInput = ref<HTMLInputElement>();

const uploadingFile = ref(false);
const fileUploadProgress = ref(0);

const onFileChosen = async () => {
  const file = fileInput.value?.files?.[0];
  if (file == null) {
    return;
  }

  const formData = new FormData();
  formData.append('file', file);

  uploadingFile.value = true;
  fileUploadProgress.value = 0;

  await nextTick();

  await axios
    .post(`${props.apiServer}/upload`, formData, {
      onUploadProgress: (progressEvent) => {
        fileUploadProgress.value = Math.min(
          Math.max(progressEvent.loaded / progressEvent.total, 0),
          1,
        );
      },
    })
    .then(() => {
      uploadingFile.value = false;
    })
    .catch(() => {
      uploadingFile.value = false;
    });
};
</script>

<template>
  <main class="p-4">
    <h1 class="text-3xl font-semibold text-center mb-12">Media Convert App</h1>

    <section class="flex flex-col items-center gap-8">
      <h2 class="sr-only">Upload new file</h2>

      <div class="flex justify-center">
        <input
          ref="fileInput"
          :onchange="onFileChosen"
          type="file"
          id="file"
          class="peer sr-only"
        />
        <label
          for="file"
          class="btn gap-2 btn-primary"
          :class="{ loading: uploadingFile, 'btn-disabled': uploadingFile }"
        >
          <PaperClipIcon class="w-6 h-6" />
          <span>Upload</span>
        </label>
      </div>

      <ProgressBar :progress="fileUploadProgress" class="max-w-sm" />
    </section>

    <aside class="toast">
      <Retry :workload="retryConnection" v-model:status="connectionStatus">
        <template #error>Failed to connect to the server.</template>
        <template #retrying>Trying to connect to the server...</template>
        <template #success>Successfully connected!</template>
      </Retry>
    </aside>

    <button class="btn btn-primary" @click="connection?.send('hey')">Test</button>
  </main>
</template>
