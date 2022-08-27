<script setup lang="ts">
import ProgressBar from './ProgressBar.vue';
import { PaperClipIcon } from '@heroicons/vue/24/outline';

import { ref, nextTick } from 'vue';
import axios from 'axios';

const emit = defineEmits<{
  (e: 'file-uploaded', data: any): void;
  (e: 'error', data: any): void;
}>();

const props = withDefaults(
  defineProps<{
    action: string;
    fileKeyName?: string;
    errorKeyName?: string;
    token?: string;
  }>(),
  {
    fileKeyName: 'file',
    errorKeyName: 'error',
  },
);

const fileInputElement = ref<HTMLInputElement>();

const uploading = ref(false);
const uploadProgress = ref(0);

const onFileChosen = async () => {
  const file = fileInputElement.value?.files?.[0];
  if (file == null) {
    return;
  }

  const formData = new FormData();
  formData.append(props.fileKeyName, file);
  if (props.token != null) {
    formData.append('token', props.token);
  }

  uploading.value = true;
  uploadProgress.value = 0;

  await nextTick();

  await axios
    .post(props.action, formData, {
      onUploadProgress: (progressEvent) => {
        uploadProgress.value = Math.min(Math.max(progressEvent.loaded / progressEvent.total, 0), 1);
      },
    })
    .then((response) => {
      emit('file-uploaded', response.data);
    })
    .catch((error) => {
      emit('error', error.response.data);
    });
  uploading.value = false;
};
</script>

<template>
  <div class="flex flex-col items-center gap-4">
    <input
      ref="fileInputElement"
      :onchange="onFileChosen"
      type="file"
      id="file"
      class="peer sr-only"
    />
    <label
      for="file"
      class="btn gap-2 btn-primary peer-focus:ring-2 ring-primary ring-offset-2"
      :class="{ loading: uploading, 'btn-disabled': uploading }"
    >
      <PaperClipIcon class="w-6 h-6" />
      <span>Upload</span>
    </label>

    <ProgressBar :progress="uploadProgress" class="max-w-sm" v-if="uploading" />
  </div>
</template>
