<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute } from '@/actions/App/Http/Controllers/PlaygroundController';
import { Palette, Copy, Check, Shuffle, RefreshCw } from 'lucide-vue-next';
import type { PlaygroundTool, ColorPaletteResult, BreadcrumbItem } from '@/types';

interface Props {
    tool: PlaygroundTool;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Playground',
        href: '/playground',
    },
    {
        title: props.tool.name,
        href: `/playground/tools/${props.tool.slug}`,
    },
];

const baseColor = ref('#3B82F6');
const result = ref<ColorPaletteResult>({
    palette: {
        primary: '#3B82F6',
        lighter: '',
        darker: '',
        complement: '',
        triad1: '',
        triad2: '',
    },
});
const isGenerating = ref(false);
const copiedColor = ref<string | null>(null);

async function generatePalette() {
    isGenerating.value = true;
    
    try {
        const response = await fetch(execute.url(props.tool), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ base_color: baseColor.value }),
        });
        
        const data = await response.json();
        result.value = data;
    } catch (error) {
        console.error('Error generating palette:', error);
    } finally {
        isGenerating.value = false;
    }
}

async function copyColor(color: string) {
    try {
        await navigator.clipboard.writeText(color);
        copiedColor.value = color;
        setTimeout(() => {
            copiedColor.value = null;
        }, 2000);
    } catch (error) {
        console.error('Failed to copy color:', error);
    }
}

function randomColor() {
    const colors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
        '#F8C471', '#82E0AA', '#F1948A', '#85929E', '#A569BD'
    ];
    baseColor.value = colors[Math.floor(Math.random() * colors.length)];
    generatePalette();
}

// Generate initial palette
generatePalette();

type PaletteKey = keyof ColorPaletteResult['palette'];

interface ColorInfo {
    key: PaletteKey;
    label: string;
    description: string;
}

const colorInfo: ColorInfo[] = [
    { key: 'primary', label: 'Primary', description: 'Your base color' },
    { key: 'lighter', label: 'Lighter', description: 'Lighter variant for backgrounds' },
    { key: 'darker', label: 'Darker', description: 'Darker variant for text/borders' },
    { key: 'complement', label: 'Complement', description: 'Opposite on color wheel' },
    { key: 'triad1', label: 'Triad 1', description: 'First triadic harmony' },
    { key: 'triad2', label: 'Triad 2', description: 'Second triadic harmony' },
];
</script>

<template>
    <Head :title="tool.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Palette class="h-8 w-8 text-primary" />
                        {{ tool.name }}
                    </h1>
                    <p class="text-muted-foreground">{{ tool.description || 'Generate harmonious color palettes' }}</p>
                </div>
                <Button variant="outline" @click="randomColor">
                    <Shuffle class="mr-2 h-4 w-4" />
                    Random Color
                </Button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Color Input -->
                <Card>
                    <CardHeader>
                        <CardTitle>Base Color</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="base-color">Pick a color</Label>
                            <div class="flex gap-2">
                                <Input
                                    id="base-color"
                                    v-model="baseColor"
                                    type="color"
                                    class="w-full h-12 p-1 border-2"
                                />
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="hex-input">Hex Code</Label>
                            <Input
                                id="hex-input"
                                v-model="baseColor"
                                type="text"
                                placeholder="#3B82F6"
                                class="font-mono"
                            />
                        </div>
                        
                        <Button @click="generatePalette" :disabled="isGenerating" class="w-full">
                            <RefreshCw :class="{ 'animate-spin': isGenerating }" class="mr-2 h-4 w-4" />
                            {{ isGenerating ? 'Generating...' : 'Generate Palette' }}
                        </Button>
                    </CardContent>
                </Card>
                
                <!-- Color Palette -->
                <div class="lg:col-span-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Generated Palette</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="info in colorInfo"
                                    :key="info.key"
                                    class="group relative"
                                >
                                    <div class="space-y-3">
                                        <div 
                                            class="h-24 rounded-lg border-2 border-border cursor-pointer transition-all hover:scale-105 hover:shadow-lg"
                                            :style="{ backgroundColor: result.palette[info.key] }"
                                            @click="copyColor(result.palette[info.key])"
                                        >
                                            <div class="flex items-center justify-center h-full opacity-0 group-hover:opacity-100 transition-opacity">
                                                <div class="bg-black/20 backdrop-blur-sm rounded-full p-2">
                                                    <Check v-if="copiedColor === result.palette[info.key]" 
                                                           class="h-4 w-4 text-white" />
                                                    <Copy v-else class="h-4 w-4 text-white" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="font-semibold text-sm">{{ info.label }}</h3>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    class="h-6 px-2"
                                                    @click="copyColor(result.palette[info.key])"
                                                >
                                                    <Check v-if="copiedColor === result.palette[info.key]" 
                                                           class="h-3 w-3" />
                                                    <Copy v-else class="h-3 w-3" />
                                                </Button>
                                            </div>
                                            <p class="text-xs text-muted-foreground">{{ info.description }}</p>
                                            <p class="font-mono text-xs bg-muted px-2 py-1 rounded">
                                                {{ result.palette[info.key] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Color Preview Row -->
                            <div class="mt-6">
                                <h3 class="font-semibold mb-3">Preview</h3>
                                <div class="grid grid-cols-6 h-16 rounded-lg overflow-hidden border-2 border-border">
                                    <div
                                        v-for="info in colorInfo"
                                        :key="`preview-${info.key}`"
                                        class="cursor-pointer hover:scale-110 transition-transform origin-bottom"
                                        :style="{ backgroundColor: result.palette[info.key] }"
                                        :title="`${info.label}: ${result.palette[info.key]}`"
                                        @click="copyColor(result.palette[info.key])"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>