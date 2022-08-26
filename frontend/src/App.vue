<script setup lang="ts">
import Retry from '@components/Retry/RetryComponent.vue';
import FileUpload from '@components/FileUpload.vue';
import { useWebSocket } from '@composables/useWebSocket';

const props = defineProps<{
  websocketsServer: string;
  apiServer: string;
}>();

const {
  webSocket,
  status: webSocketStatus,
  connect: webSocketConnect,
} = useWebSocket(props.websocketsServer);

const out = console;
</script>

<template>
  <main class="p-4">
    <h1 class="text-2xl font-semibold text-center mb-6">Videos Resize App</h1>

    <section class="flex flex-col gap-4">
      <h2 class="sr-only">Upload new file</h2>

      <FileUpload
        :action="`${props.apiServer}/upload`"
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
