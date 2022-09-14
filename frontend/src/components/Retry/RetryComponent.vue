<script setup lang="ts">
import { Status, Workload } from '@components/Retry/RetryComponent';

import StatusIcon from '@components/Retry/StatusIcon.vue';
import Spinner from '@components/icons/Spinner.vue';

import { useIntervalFn } from '@vueuse/core';
import { ref, watch, onMounted } from 'vue';

const emit = defineEmits<{
  (e: 'update:status', value: Status): void;
}>();

const props = withDefaults(
  defineProps<{
    workload: Workload<any>;
    status: Status;
    initialShown?: boolean;
    maxTimeLeft?: number;
    initialTimeLeft?: number;
    nextTimeCoefficient?: number;
  }>(),
  {
    initialShown: false,
    maxTimeLeft: 30,
    initialTimeLeft: 3,
    nextTimeCoefficient: 1.5,
  },
);

const shown = ref(props.initialShown);

const timeLeft = ref(props.initialTimeLeft);
const previousTimeLeft = ref(timeLeft.value);

onMounted(async () => {
  if (props.status == 'first-try') {
    await props
      .workload()
      .then(async () => {
        emit('update:status', 'success');
      })
      .catch(async () => {
        emit('update:status', 'waiting');
      });
  }
});

const { pause: timerPause, resume: timerResume } = useIntervalFn(async () => {
  if (props.status == 'success' || props.status == 'first-try') {
    timerPause();
    return;
  }

  timeLeft.value = Math.max(timeLeft.value - 1, 0);

  if (timeLeft.value == 0) {
    timerPause();
    emit('update:status', 'retrying');
  }
}, 1000);

watch(
  () => props.status,
  async (status, prevStatus) => {
    if (status == 'first-try') {
      return;
    }

    if (prevStatus == 'success' || prevStatus == 'first-try') {
      timerResume();
    }

    if (status == 'waiting' || status == 'retrying') {
      shown.value = true;
    }

    if (status == 'retrying' || status == 'success') {
      timerPause();
    }

    if (status == 'waiting' && prevStatus != 'success' && prevStatus != 'first-try') {
      timeLeft.value = Math.min(
        Math.ceil(previousTimeLeft.value * props.nextTimeCoefficient),
        props.maxTimeLeft,
      );
      previousTimeLeft.value = timeLeft.value;

      timerResume();
    }

    if (status == 'success') {
      timeLeft.value = props.initialTimeLeft;
      previousTimeLeft.value = props.initialTimeLeft;
    }

    if (status == 'retrying') {
      await props
        .workload()
        .then(async () => {
          emit('update:status', 'success');
        })
        .catch(async () => {
          emit('update:status', 'waiting');
        });
    }
  },
);
</script>

<template>
  <transition>
    <article
      v-cloak
      v-show="shown"
      class="alert shadow-md"
      :class="{
        'alert-error': props.status == 'waiting',
        'alert-success': props.status == 'success',
      }"
    >
      <div>
        <StatusIcon :status="props.status" />

        <div>
          <h3 class="font-bold">
            <template v-if="props.status == 'waiting'">
              <slot name="error">Error...</slot>
            </template>
            <template v-else-if="props.status == 'retrying'">
              <slot name="retrying">Retrying...</slot>
            </template>
            <template v-else-if="props.status == 'success'">
              <slot name="success">Success!</slot>
            </template>
          </h3>

          <template v-if="props.status == 'waiting'">
            <p class="text-sm">
              Retrying after <span class="font-semibold">{{ timeLeft }}</span> seconds...
            </p>
          </template>
        </div>
      </div>

      <div class="flex-none">
        <button v-if="props.status == 'success'" @click="shown = false" class="btn btn-sm">
          Close
        </button>
        <button
          v-else
          class="btn btn-sm gap-2"
          :class="{ 'btn-disabled': status == 'retrying' }"
          @click="emit('update:status', 'retrying')"
        >
          <Spinner class="w-4 h-4" />
          <span>Retry now</span>
        </button>
      </div>
    </article>
  </transition>
</template>

<style>
.v-enter-active,
.v-leave-active {
  transition: all 0.2s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
