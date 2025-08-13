<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute } from '@/actions/App/Http/Controllers/PlaygroundController';
import { Code, Check, AlertCircle, Copy, FileCode, Minimize, Maximize } from 'lucide-vue-next';
import type { PlaygroundTool, JsonFormatterResult, BreadcrumbItem } from '@/types';

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

const inputJson = ref('');
const result = ref<JsonFormatterResult>({
    valid: false,
});
const isProcessing = ref(false);
const copiedFormat = ref<'formatted' | 'minified' | null>(null);

async function formatJson() {
    if (!inputJson.value.trim()) {
        result.value = { valid: false };
        return;
    }

    isProcessing.value = true;
    
    try {
        const response = await fetch(execute.url(props.tool), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ json: inputJson.value }),
        });
        
        const data = await response.json();
        result.value = data;
    } catch (error) {
        console.error('Error formatting JSON:', error);
        result.value = {
            valid: false,
            error: 'Network error occurred',
        };
    } finally {
        isProcessing.value = false;
    }
}

async function copyToClipboard(text: string, format: 'formatted' | 'minified') {
    try {
        await navigator.clipboard.writeText(text);
        copiedFormat.value = format;
        setTimeout(() => {
            copiedFormat.value = null;
        }, 2000);
    } catch (error) {
        console.error('Failed to copy to clipboard:', error);
    }
}

function clearJson() {
    inputJson.value = '';
    result.value = { valid: false };
}

const sampleJson = `{
  "name": "AI Playground",
  "version": "1.0.0",
  "tools": [
    {
      "id": 1,
      "name": "Word Counter",
      "active": true,
      "features": ["count", "analyze", "statistics"]
    },
    {
      "id": 2,
      "name": "JSON Formatter",
      "active": true,
      "features": ["format", "minify", "validate"]
    }
  ],
  "settings": {
    "theme": "dark",
    "autoSave": true,
    "timeout": 5000
  }
}`;

function loadSample() {
    inputJson.value = sampleJson;
    formatJson();
}
</script>

<template>
    <Head :title="tool.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Code class="h-8 w-8 text-primary" />
                        {{ tool.name }}
                    </h1>
                    <p class="text-muted-foreground">{{ tool.description || 'Format, validate, and minify JSON' }}</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="loadSample">
                        Load Sample
                    </Button>
                    <Button variant="outline" @click="clearJson" :disabled="!inputJson.trim()">
                        Clear
                    </Button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Input Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileCode class="h-5 w-5" />
                            Input JSON
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <Textarea 
                                v-model="inputJson"
                                @input="formatJson"
                                placeholder="Paste your JSON here..."
                                class="min-h-[400px] font-mono text-sm resize-none"
                            />
                            
                            <div v-if="result.valid === false && result.error" 
                                 class="flex items-start gap-2 p-3 bg-destructive/10 border border-destructive/20 rounded-md">
                                <AlertCircle class="h-4 w-4 text-destructive mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-sm font-medium text-destructive">Invalid JSON</p>
                                    <p class="text-sm text-destructive/80">{{ result.error }}</p>
                                </div>
                            </div>
                            
                            <div v-else-if="result.valid" 
                                 class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-md dark:bg-green-950 dark:border-green-800">
                                <Check class="h-4 w-4 text-green-600 dark:text-green-400" />
                                <p class="text-sm font-medium text-green-800 dark:text-green-200">Valid JSON</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <!-- Output Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Output</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="!result.valid" class="flex items-center justify-center h-[400px] text-muted-foreground">
                            <div class="text-center">
                                <Code class="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>Enter valid JSON to see formatted output</p>
                            </div>
                        </div>
                        
                        <Tabs v-else default-value="formatted" class="w-full">
                            <TabsList class="grid w-full grid-cols-2">
                                <TabsTrigger value="formatted" class="flex items-center gap-2">
                                    <Maximize class="h-4 w-4" />
                                    Formatted
                                </TabsTrigger>
                                <TabsTrigger value="minified" class="flex items-center gap-2">
                                    <Minimize class="h-4 w-4" />
                                    Minified
                                </TabsTrigger>
                            </TabsList>
                            
                            <TabsContent value="formatted" class="space-y-4">
                                <div class="relative">
                                    <Textarea 
                                        :value="result.formatted || ''"
                                        readonly
                                        class="min-h-[350px] font-mono text-sm resize-none"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        class="absolute top-2 right-2"
                                        @click="copyToClipboard(result.formatted || '', 'formatted')"
                                    >
                                        <Check v-if="copiedFormat === 'formatted'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </Button>
                                </div>
                                
                                <div class="text-sm text-muted-foreground">
                                    Size: {{ result.size_formatted?.toLocaleString() }} characters
                                </div>
                            </TabsContent>
                            
                            <TabsContent value="minified" class="space-y-4">
                                <div class="relative">
                                    <Textarea 
                                        :value="result.minified || ''"
                                        readonly
                                        class="min-h-[350px] font-mono text-sm resize-none"
                                    />
                                    <Button 
                                        size="sm" 
                                        variant="outline" 
                                        class="absolute top-2 right-2"
                                        @click="copyToClipboard(result.minified || '', 'minified')"
                                    >
                                        <Check v-if="copiedFormat === 'minified'" class="h-4 w-4" />
                                        <Copy v-else class="h-4 w-4" />
                                    </Button>
                                </div>
                                
                                <div class="text-sm text-muted-foreground">
                                    Size: {{ result.size_minified?.toLocaleString() }} characters
                                    <span v-if="result.size_original && result.size_minified">
                                        ({{ Math.round(((result.size_original - result.size_minified) / result.size_original) * 100) }}% smaller)
                                    </span>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>