<script setup lang="ts">
import { TabsContent, type TabsContentProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<TabsContentProps & {
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <TabsContent 
    v-bind="props" 
    :class="cn(
      'ring-offset-background focus-visible:ring-ring mt-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
      props.class,
    )"
  >
    <slot />
  </TabsContent>
</template>