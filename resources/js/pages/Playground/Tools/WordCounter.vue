<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute } from '@/actions/App/Http/Controllers/PlaygroundController';
import { FileText, Hash, Type, AlignLeft, FileType } from 'lucide-vue-next';
import type { PlaygroundTool, WordCounterStats, BreadcrumbItem } from '@/types';

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

const text = ref('');
const stats = ref<WordCounterStats>({
    words: 0,
    characters: 0,
    characters_no_spaces: 0,
    lines: 1,
    paragraphs: 0,
});
const isAnalyzing = ref(false);

const isEmpty = computed(() => text.value.trim() === '');

async function analyze() {
    if (isEmpty.value) {
        stats.value = {
            words: 0,
            characters: 0,
            characters_no_spaces: 0,
            lines: 1,
            paragraphs: 0,
        };
        return;
    }

    isAnalyzing.value = true;
    
    try {
        const response = await fetch(execute.url(props.tool), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ text: text.value }),
        });
        
        const data = await response.json();
        stats.value = data;
    } catch (error) {
        console.error('Error analyzing text:', error);
    } finally {
        isAnalyzing.value = false;
    }
}

function clearText() {
    text.value = '';
    analyze();
}

const sampleText = `Welcome to the AI Playground!

This is a sample text to demonstrate the Word Counter tool. You can replace this text with your own content to analyze.

The tool will count:
- Words
- Characters (with and without spaces)
- Lines
- Paragraphs

Try pasting some text and see the statistics update in real-time!`;

function loadSample() {
    text.value = sampleText;
    analyze();
}
</script>

<template>
    <Head :title="tool.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <FileText class="h-8 w-8 text-primary" />
                        {{ tool.name }}
                    </h1>
                    <p class="text-muted-foreground">{{ tool.description || 'Analyze text statistics' }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="loadSample">
                        Load Sample
                    </Button>
                    <Button variant="outline" @click="clearText" :disabled="isEmpty">
                        Clear
                    </Button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Enter Text</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea 
                                v-model="text"
                                @input="analyze"
                                placeholder="Start typing or paste your text here..."
                                class="min-h-[400px] resize-none"
                            />
                        </CardContent>
                    </Card>
                </div>
                
                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Hash class="h-5 w-5" />
                                Statistics
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <Type class="h-4 w-4 text-muted-foreground" />
                                    <span>Words</span>
                                </div>
                                <span class="font-semibold text-lg">{{ stats.words.toLocaleString() }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <FileType class="h-4 w-4 text-muted-foreground" />
                                    <span>Characters</span>
                                </div>
                                <span class="font-semibold text-lg">{{ stats.characters.toLocaleString() }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <FileType class="h-4 w-4 text-muted-foreground" />
                                    <span>Characters (no spaces)</span>
                                </div>
                                <span class="font-semibold text-lg">{{ stats.characters_no_spaces.toLocaleString() }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <AlignLeft class="h-4 w-4 text-muted-foreground" />
                                    <span>Lines</span>
                                </div>
                                <span class="font-semibold text-lg">{{ stats.lines.toLocaleString() }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <AlignLeft class="h-4 w-4 text-muted-foreground" />
                                    <span>Paragraphs</span>
                                </div>
                                <span class="font-semibold text-lg">{{ stats.paragraphs.toLocaleString() }}</span>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card v-if="!isEmpty">
                        <CardHeader>
                            <CardTitle>Reading Time</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-center">
                                <div class="text-2xl font-bold">{{ Math.ceil(stats.words / 200) }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ Math.ceil(stats.words / 200) === 1 ? 'minute' : 'minutes' }}
                                </div>
                                <p class="text-xs text-muted-foreground mt-2">
                                    Based on 200 words per minute
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>