<script setup lang="ts">
import { show as showTool } from '@/actions/App/Http/Controllers/PlaygroundController';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PlaygroundTool } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Beaker } from 'lucide-vue-next';

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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Ed Playground</h1>
                </div>
            </div>

            <div v-if="tools.length === 0" class="flex min-h-[400px] flex-col items-center justify-center text-center">
                <Beaker class="mb-4 h-16 w-16 text-muted-foreground" />
                <h3 class="text-lg font-semibold">No tools yet</h3>
                <p class="mb-4 text-muted-foreground">
                    Use the artisan command to create playground tools:
                    <code class="rounded bg-muted px-2 py-1 text-sm">php artisan playground:make-tool</code>
                </p>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="tool in tools" :key="tool.id" class="transition-shadow hover:shadow-md">
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
                            <Link :href="showTool.url(tool.slug)"> Open Tool </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
