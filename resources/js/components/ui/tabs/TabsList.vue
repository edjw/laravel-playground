<script setup lang="ts">
import { TabsList, type TabsListProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<TabsListProps & {
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <TabsList 
    v-bind="props" 
    :class="cn(
      'bg-muted text-muted-foreground inline-flex h-9 items-center justify-center rounded-lg p-1',
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>