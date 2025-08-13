<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { show as showTool } from '@/actions/App/Http/Controllers/PlaygroundController';
import { Beaker } from 'lucide-vue-next';
import type { PlaygroundTool, BreadcrumbItem } from '@/types';

interface Props {
    tools: PlaygroundTool[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Playground',
        href: '/playground',
    },
];
</script>

<template>
    <Head title="AI Playground" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">AI Playground</h1>
                    <p class="text-muted-foreground">Build and test tools with AI assistance</p>
                </div>
            </div>
            
            <div v-if="tools.length === 0" class="flex flex-col items-center justify-center min-h-[400px] text-center">
                <Beaker class="h-16 w-16 text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold">No tools yet</h3>
                <p class="text-muted-foreground mb-4">Use the artisan command to create playground tools: <code class="text-sm bg-muted px-2 py-1 rounded">php artisan playground:make-tool</code></p>
            </div>
            
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card v-for="tool in tools" :key="tool.id" class="hover:shadow-md transition-shadow">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <component :is="tool.icon" class="h-5 w-5 text-primary" />
                            <CardTitle>{{ tool.name }}</CardTitle>
                        </div>
                        <CardDescription v-if="tool.description">
                            {{ tool.description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button as-child variant="outline" class="w-full">
                            <Link :href="showTool.url(tool.slug)">
                                Open Tool
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>