<script setup lang="ts">
import { TabsRoot, type TabsRootProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<TabsRootProps & {
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <TabsRoot v-bind="props" :class="cn('w-full', props.class)">
    <slot />
  </TabsRoot>
</template>