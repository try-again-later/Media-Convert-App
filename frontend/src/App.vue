<script setup lang="ts">
import { PaperClipIcon } from '@heroicons/vue/outline';
import { onMounted, ref } from 'vue';

const props = defineProps<{
  websocketsServer: string;
  apiServer: string;
}>();

// const connection = ref<WebSocket>();

// onMounted(() => {
//   connection.value = new WebSocket(props.websocketsServer);
//   connection.value.onopen = () => {
//     console.log('Established connection through websockets!');
//   };
// });

const fileInput = ref<HTMLInputElement>();

const onFileChosen = async () => {
  const file = fileInput.value?.files?.[0];
  if (file == null) {
    return;
  }

  const formData = new FormData();
  formData.append('file', file);
  const uploadResult = await fetch(props.apiServer + '/upload', {
    method: 'POST',
    mode: 'cors',
    body: formData,
  });

  if (!uploadResult.ok) {
    console.error(':(');
  }
};
</script>

<template>
  <main class="p-4">
    <h1 class="text-3xl font-semibold text-center mb-12">Media Convert App</h1>

    <section>
      <h2 class="sr-only">Upload new file</h2>

      <div class="flex justify-center">
        <input
          ref="fileInput"
          :onchange="onFileChosen"
          type="file"
          id="file"
          class="peer sr-only"
        />
        <label for="file" class="btn gap-2 btn-primary">
          <PaperClipIcon class="w-6 h-6" />
          <span>Upload a new image</span>
        </label>
      </div>
    </section>
  </main>
</template>
