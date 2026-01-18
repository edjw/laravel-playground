<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlaygroundTool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class MakePlaygroundTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:playground-tool 
                            {name? : The name of the playground tool}
                            {--icon= : Icon name from Lucide}
                            {--description= : Tool description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new playground tool with Vue component and database entry';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get tool details
        $name = $this->argument('name') ?? text(
            label: 'What is the name of your playground tool?',
            placeholder: 'e.g. QR Code Generator',
            required: true
        );

        $slug = str($name)->slug()->toString();
        $componentName = str($name)->studly()->toString();

        $description = $this->option('description') ?? text(
            label: 'Description (optional)',
            placeholder: 'Brief description of what this tool does'
        );

        $icon = $this->option('icon') ?? select(
            label: 'Choose an icon',
            options: [
                'Beaker' => 'Beaker (chemistry/experiments)',
                'Wrench' => 'Wrench (tools/utilities)',
                'Code' => 'Code (programming)',
                'Zap' => 'Zap (fast/powerful)',
                'Terminal' => 'Terminal (CLI tools)',
                'Settings' => 'Settings (configuration)',
                'FileText' => 'FileText (text processing)',
                'Image' => 'Image (image tools)',
                'Calculator' => 'Calculator (math tools)',
                'Globe' => 'Globe (web tools)',
            ],
            default: 'Beaker'
        );

        // Check if tool already exists
        if (PlaygroundTool::where('slug', $slug)->exists()) {
            $this->error("A playground tool with slug '{$slug}' already exists!");

            return self::FAILURE;
        }

        // Check if Vue component already exists
        $componentPath = resource_path("js/pages/Playground/Tools/{$componentName}.vue");
        if (File::exists($componentPath)) {
            $this->error("Vue component '{$componentName}.vue' already exists!");

            return self::FAILURE;
        }

        // Create Vue component
        $this->createVueComponent($componentName, $name, $slug);

        // Create database entry
        $this->createDatabaseEntry($name, $slug, $componentName, $description, $icon);

        // Ask to update controller
        $addedController = false;
        if (confirm('Would you like to add a server-side execution method to the controller? (Choose "no" for client-side processing)')) {
            $this->updateController($slug, $componentName);
            $addedController = true;
        }

        // Update seeder for deployment
        $this->updateSeeder($name, $slug, $componentName, $description, $icon);

        $this->info('Playground tool created successfully!');
        $this->line("• Vue component: {$componentPath}");
        $this->line("• Database entry created for: {$name}");
        $this->line('• Seeder updated for deployment');
        $this->line("• URL: /playground/tools/{$slug}");

        if ($addedController) {
            $this->line('');
            $this->line("<comment>Note: For server-side processing, you'll need to add this route to web.php:</comment>");
            $this->line("<info>Route::post('/playground/tools/{$slug}/process', [PlaygroundController::class, 'process{$componentName}'])->name('playground.process.{$slug}');</info>");
        }

        return self::SUCCESS;
    }

    private function createVueComponent(string $componentName, string $name, string $slug): void
    {
        $stub = <<<'VUE'
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { update, execute } from '@/actions/App/Http/Controllers/PlaygroundController';
import type { PlaygroundTool, BreadcrumbItem } from '@/types';

interface Props {
    tool: PlaygroundTool;
    savedData?: ToolResult[];
    processResult?: any; // Result from server-side processing
}

interface ToolResult {
    id: string;
    input: string;
    output: any;
    label: string;
    timestamp: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
];

// Tool state
const input = ref('');
const output = ref<any>(null);
const isProcessing = ref(false);

// Result saving
const savedResults = ref<ToolResult[]>([]);
const showSaveDialog = ref(false);
const saveLabel = ref('');

// Load saved results on mount
onMounted(() => {
    if (props.savedData && Array.isArray(props.savedData)) {
        savedResults.value = props.savedData;
    }
    
    // Check for server-side processing result
    if (props.processResult) {
        output.value = props.processResult;
    }
});

// Save results when they change
watch(
    () => savedResults.value,
    async (newResults) => {
        try {
            const action = update(props.tool);
            await router.put(action.url, { 
                saved_data: newResults 
            }, {
                preserveState: true,
                preserveScroll: true,
                only: ['savedData'],
            });
        } catch (error) {
            console.error('Failed to save results:', error);
        }
    },
    { deep: true }
);

// Process input - choose client-side OR server-side processing
const processTool = async () => {
    if (!input.value.trim()) return;
    
    isProcessing.value = true;
    
    try {
        // OPTION 1: Client-side processing (recommended for simple operations)
        const result = {
            inputLength: input.value.length,
            wordCount: input.value.trim().split(/\s+/).length,
            processed: input.value.toUpperCase(), // Example transformation
            message: 'Tool processing completed successfully'
        };
        
        output.value = result;
        
        // OPTION 2: Server-side processing (uncomment if you added controller method and route)
        // Pure Inertia approach - server redirects back with result
        // router.post(`/playground/tools/${props.tool.slug}/process`, {
        //     input: input.value,
        // }, {
        //     preserveState: true,
        //     onSuccess: (page) => {
        //         // Server redirects back with result in page props
        //         if (page.props.processResult) {
        //             output.value = page.props.processResult;
        //         }
        //     },
        //     onError: () => {
        //         output.value = { error: 'Processing failed' };
        //     }
        // });
        
    } catch (error) {
        output.value = { 
            error: 'An error occurred while processing your input.' 
        };
    } finally {
        isProcessing.value = false;
    }
};

// Result saving functions
const openSaveDialog = () => {
    if (output.value && !output.value.error) {
        showSaveDialog.value = true;
        saveLabel.value = `Result for: ${input.value.substring(0, 30)}...`;
    }
};

const saveResult = () => {
    if (output.value && !output.value.error && saveLabel.value.trim()) {
        const newResult: ToolResult = {
            id: Date.now().toString(),
            input: input.value,
            output: output.value,
            label: saveLabel.value.trim(),
            timestamp: new Date().toISOString(),
        };
        
        savedResults.value.unshift(newResult);
        showSaveDialog.value = false;
        saveLabel.value = '';
    }
};

const loadResult = (result: ToolResult) => {
    input.value = result.input;
    output.value = result.output;
};

const deleteResult = (resultId: string) => {
    savedResults.value = savedResults.value.filter(r => r.id !== resultId);
};

const clearAllResults = () => {
    savedResults.value = [];
};

// Computed properties
const stats = computed(() => ({
    totalResults: savedResults.value.length,
    lastUsed: savedResults.value.length > 0 
        ? new Date(savedResults.value[0].timestamp).toLocaleDateString()
        : null,
}));
</script>

<template>
    <Head :title="tool.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Tool Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">{{ tool.name }}</h1>
                <p v-if="tool.description" class="text-muted-foreground">{{ tool.description }}</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Tool Interface -->
                <div class="lg:col-span-2 bg-white border rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Tool Interface</h2>
                    
                    <div class="space-y-4">
                        <!-- Input Section -->
                        <div class="space-y-2">
                            <label for="input" class="text-sm font-medium">Input:</label>
                            <textarea
                                id="input"
                                v-model="input"
                                class="w-full h-32 p-3 border rounded-md resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                placeholder="Enter your input here..."
                            />
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <button
                                @click="processTool"
                                :disabled="!input.trim() || isProcessing"
                                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                {{ isProcessing ? 'Processing...' : 'Process' }}
                            </button>
                            <button
                                @click="openSaveDialog"
                                :disabled="!output || output.error"
                                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Save
                            </button>
                        </div>
                        
                        <!-- Output Section -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Output:</label>
                            <div v-if="output" class="p-4 border rounded-md bg-gray-50">
                                <div v-if="output.error" class="text-red-600">
                                    {{ output.error }}
                                </div>
                                <pre v-else class="whitespace-pre-wrap text-sm">{{ JSON.stringify(output, null, 2) }}</pre>
                            </div>
                            <div v-else class="p-4 border rounded-md bg-gray-50 text-muted-foreground">
                                Process your input to see results here...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saved Results -->
                <div class="bg-white border rounded-lg p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Saved Results</h2>
                        <button
                            v-if="savedResults.length > 0"
                            @click="clearAllResults"
                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                        >
                            Clear All
                        </button>
                    </div>

                    <!-- Statistics -->
                    <div v-if="savedResults.length > 0" class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="text-sm space-y-1">
                            <div><strong>Total:</strong> {{ stats.totalResults }}</div>
                            <div v-if="stats.lastUsed"><strong>Last used:</strong> {{ stats.lastUsed }}</div>
                        </div>
                    </div>

                    <!-- Results List -->
                    <div v-if="savedResults.length === 0" class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">🛠️</div>
                        <p>No saved results yet.</p>
                        <p class="text-sm">Process input and save results!</p>
                    </div>

                    <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                        <div
                            v-for="result in savedResults"
                            :key="result.id"
                            class="p-3 border rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm text-gray-900 truncate">
                                        {{ result.label }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1 truncate">
                                        {{ result.input.substring(0, 50) }}{{ result.input.length > 50 ? '...' : '' }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ new Date(result.timestamp).toLocaleDateString() }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 ml-2">
                                    <button
                                        @click="loadResult(result)"
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium px-2 py-1 rounded"
                                    >
                                        Load
                                    </button>
                                    <button
                                        @click="deleteResult(result.id)"
                                        class="text-red-600 hover:text-red-800 text-xs font-medium px-2 py-1 rounded"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Dialog -->
            <div v-if="showSaveDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold mb-4">Save Result</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Input</label>
                            <div class="p-2 bg-gray-100 rounded-md text-gray-700 text-sm max-h-20 overflow-y-auto">
                                {{ input }}
                            </div>
                        </div>
                        
                        <div>
                            <label for="save-label" class="block text-sm font-medium text-gray-700 mb-1">Label</label>
                            <input
                                id="save-label"
                                v-model="saveLabel"
                                type="text"
                                placeholder="Enter a label for this result"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            />
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button
                            @click="showSaveDialog = false"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="saveResult"
                            :disabled="!saveLabel.trim()"
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
VUE;

        $componentPath = resource_path("js/pages/Playground/Tools/{$componentName}.vue");
        File::put($componentPath, $stub);

        $this->info("Created Vue component: {$componentPath}");
    }

    private function createDatabaseEntry(string $name, string $slug, string $componentName, ?string $description, string $icon): void
    {
        PlaygroundTool::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon' => $icon,
            'component_name' => $componentName,
            'configuration' => [],
            'is_active' => true,
            'user_id' => null, // System tool
        ]);

        $this->info("Created database entry for: {$name}");
    }

    private function updateController(string $slug, string $componentName): void
    {
        $controllerPath = app_path('Http/Controllers/PlaygroundController.php');
        $content = File::get($controllerPath);

        // Check if the case already exists
        if (str_contains($content, "'{$slug}' =>")) {
            $this->info('Controller case already exists, skipping...');

            return;
        }

        // Add case to match statement - be more specific about the match
        $newCase = "            '{$slug}' => \$this->execute".$componentName.'($request),';

        // Find the exact match statement and add our case before default
        $pattern = '/(\$result = match \(\$tool->slug\) \{[^}]*?)(            default => \[\'error\' => \'Tool not implemented\'\],)/s';

        if (preg_match($pattern, $content)) {
            $content = preg_replace(
                $pattern,
                "$1$newCase\n$2",
                $content
            );
        }

        // Check if the method already exists
        if (str_contains($content, "execute{$componentName}(")) {
            $this->info('Controller method already exists, skipping...');
            File::put($controllerPath, $content); // Still save the case update

            return;
        }

        // Add method before the last closing brace of the class - pure Inertia approach
        $newMethod = "\n    public function process{$componentName}(Request \$request, PlaygroundTool \$tool)\n    {\n        // Ensure tool is active\n        if (!\$tool->is_active) {\n            abort(404);\n        }\n        \n        \$input = \$request->input('input', '');\n        \n        try {\n            // TODO: Implement your server-side tool logic here\n            // Example processing\n            \$processed = strtoupper(\$input); // Replace with your logic\n            \n            \$result = [\n                'input' => \$input,\n                'processed' => \$processed,\n                'message' => 'Processing completed successfully',\n                'timestamp' => now()->toISOString(),\n            ];\n            \n            // Pure Inertia: redirect back to tool page with result as prop\n            return redirect()->route('playground.show', \$tool)\n                ->with('processResult', \$result);\n                \n        } catch (Exception \$e) {\n            \$errorResult = [\n                'error' => 'Processing failed: ' . \$e->getMessage(),\n                'input' => \$input,\n            ];\n            \n            return redirect()->route('playground.show', \$tool)\n                ->with('processResult', \$errorResult);\n        }\n    }";

        // Find the position of the last closing brace and insert before it
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false) {
            $content = substr_replace($content, $newMethod."\n}", $lastBracePos, 1);
        }

        File::put($controllerPath, $content);

        $this->info('Added placeholder method to PlaygroundController');
    }

    private function updateSeeder(string $name, string $slug, string $componentName, ?string $description, string $icon): void
    {
        $seederPath = database_path('seeders/PlaygroundToolSeeder.php');
        $content = File::get($seederPath);

        $newToolEntry = <<<PHP

        // Create the {$name} tool
        PlaygroundTool::firstOrCreate(
            ['slug' => '{$slug}'],
            [
                'name' => '{$name}',
                'slug' => '{$slug}',
                'description' => '{$description}',
                'icon' => '{$icon}',
                'component_name' => '{$componentName}',
                'configuration' => [],
                'is_active' => true,
                'user_id' => null, // System tool
            ]
        );
PHP;

        // Insert the new tool before the closing brace and curly brace of the run method
        $content = str_replace(
            '    }'."\n".'}',
            $newToolEntry."\n".'    }'."\n".'}',
            $content
        );

        File::put($seederPath, $content);

        $this->info('Updated PlaygroundToolSeeder for deployment');
    }
}
