<script setup lang="ts">
import { Status } from './RetryComponent';
import { ExclamationTriangleIcon, CheckBadgeIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
  status: Status;
}>();
</script>

<template>
  <template v-if="props.status == 'waiting'">
    <ExclamationTriangleIcon class="w-6 h-6" />
  </template>
  <template v-else-if="props.status == 'success'">
    <CheckBadgeIcon class="w-6 h-6" />
  </template>
  <template v-else-if="props.status == 'retrying'">
    <ArrowPathIcon class="w-6 h-6" />
  </template>
</template>
