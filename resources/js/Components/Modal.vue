<script setup>
import { watch } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: 'lg',
  },
  title: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close']);

const maxWidthClass = {
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl',
};

watch(
  () => props.show,
  (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
  }
);
</script>

<template>
  <Teleport to="body">
    <Transition leave-active-class="duration-200">
      <div v-show="show" class="fixed inset-0 overflow-y-auto z-50" @click.self="emit('close')">
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-show="show"
              class="fixed inset-0 bg-gray-500/75 transition-opacity"
              @click="emit('close')"
            />
          </Transition>

          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-show="show"
              :class="[
                maxWidthClass[maxWidth],
                'relative w-full transform overflow-hidden rounded-lg bg-white shadow-xl transition-all sm:my-8 sm:w-full max-h-[calc(100vh-2rem)] overflow-y-auto',
              ]"
            >
              <div v-if="title" class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900">{{ title }}</h3>
              </div>
              <div class="px-6 py-4">
                <slot />
              </div>
              <div
                v-if="$slots.footer"
                class="border-t border-gray-200 px-6 py-4 bg-gray-50 flex justify-end space-x-3"
              >
                <slot name="footer" />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
