<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupLabel, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type PlaygroundTool } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Beaker, Braces, Palette, Type } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed } from 'vue';

const page = usePage();

// No main nav items needed - tools will be the primary navigation

const playgroundTools = computed(() => page.props.playground_tools as PlaygroundTool[]);

// Icon mapping for dynamic component resolution
const iconMap = {
    Type,
    Braces,
    Palette,
    Beaker,
};

// Helper to resolve icon component from string
const getIconComponent = (iconName: string) => {
    return iconMap[iconName as keyof typeof iconMap] || Beaker;
};

// No footer nav items needed
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('playground.index')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup v-if="playgroundTools.length > 0" class="px-2 py-0">
                <SidebarGroupLabel>Tools</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="tool in playgroundTools" :key="tool.id">
                        <SidebarMenuButton as-child :is-active="page.url.includes(`/playground/tools/${tool.slug}`)" :tooltip="tool.name">
                            <Link :href="`/playground/tools/${tool.slug}`">
                                <component :is="getIconComponent(tool.icon)" />
                                <span>{{ tool.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
