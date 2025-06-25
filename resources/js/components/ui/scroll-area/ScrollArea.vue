<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps({
  class: {
    type: String,
    default: '',
  },
  viewportClass: {
    type: String,
    default: '',
  },
  scrollbarClass: {
    type: String,
    default: '',
  },
  thumbClass: {
    type: String,
    default: '',
  },
  orientation: {
    type: String,
    default: 'vertical',
    validator: (value: string) => ['vertical', 'horizontal', 'both'].includes(value),
  },
});

const scrollbarOrientation = computed(() => {
  if (props.orientation === 'both') return ['vertical', 'horizontal'];
  return [props.orientation];
});
</script>

<template>
  <div :class="cn('relative overflow-hidden', props.class)">
    <div
      :class="[
        'h-full w-full overflow-auto',
        viewportClass,
      ]"
    >
      <slot />
    </div>
    
    <template v-for="orientation in scrollbarOrientation" :key="orientation">
      <div
        :class="[
          'flex touch-none select-none transition-colors',
          orientation === 'vertical' ? 'h-full w-2.5 border-l border-l-transparent p-[1px]' : '',
          orientation === 'horizontal' ? 'h-2.5 flex-col border-t border-t-transparent p-[1px]' : '',
          scrollbarClass,
        ]"
      >
        <div
          :class="[
            'relative flex-1 rounded-full bg-border',
            orientation === 'vertical' ? 'w-full' : 'h-full',
            thumbClass,
          ]"
        />
      </div>
    </template>
  </div>
</template>
