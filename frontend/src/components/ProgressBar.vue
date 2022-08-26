<script setup lang="ts">
import { Ref, ref, watch } from 'vue';
import { Tween, Easing } from '@tweenjs/tween.js';

// takes a floating point number from 0 to 1 as the progress
const props = withDefaults(
  defineProps<{
    progress: number;
    delay?: number;
  }>(),
  {
    delay: 200,
  },
);

const tweenedProgress = ref(0);
const progressTweener = ref<Tween<Ref<number>>>();

watch(
  () => props.progress,
  () => {
    if (100 * props.progress < tweenedProgress.value) {
      tweenedProgress.value = props.progress;
    }

    progressTweener.value?.stop();

    const newProgress = Math.round(props.progress * 100);
    progressTweener.value = new Tween(tweenedProgress)
      .to({ value: newProgress }, props.delay)
      .easing(Easing.Quadratic.Out)
      .start();
  },
);
</script>

<template>
  <progress :value="tweenedProgress" class="progress progress-primary" max="100"></progress>
</template>
