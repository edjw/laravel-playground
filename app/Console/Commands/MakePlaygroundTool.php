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
        if (confirm('Would you like to add a placeholder execution method to the controller?')) {
            $this->updateController($slug, $componentName);
        }

        // Update seeder for deployment
        $this->updateSeeder($name, $slug, $componentName, $description, $icon);

        $this->info('Playground tool created successfully!');
        $this->line("• Vue component: {$componentPath}");
        $this->line("• Database entry created for: {$name}");
        $this->line("• Seeder updated for deployment");
        $this->line("• URL: /playground/tools/{$slug}");

        return self::SUCCESS;
    }

    private function createVueComponent(string $componentName, string $name, string $slug): void
    {
        $stub = <<<'VUE'
<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute } from '@/actions/App/Http/Controllers/PlaygroundController';
import type { PlaygroundTool, BreadcrumbItem } from '@/types';

interface Props {
    tool: PlaygroundTool;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
];

// Tool state
const input = ref('');
const result = ref<any>(null);
const isLoading = ref(false);

// Execute tool logic
const executeTool = async () => {
    if (!input.value.trim()) return;
    
    isLoading.value = true;
    try {
        const response = await execute(props.tool, {
            input: input.value,
        });
        result.value = response;
    } catch (error) {
        console.error('Tool execution error:', error);
        result.value = { error: 'An error occurred while processing your request.' };
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head :title="tool.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Tool Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">{{ tool.name }}</h1>
                <p v-if="tool.description" class="text-muted-foreground">{{ tool.description }}</p>
            </div>

            <!-- Tool Interface -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Input Section -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Input</h2>
                    <div class="space-y-2">
                        <label for="input" class="text-sm font-medium">Enter your data:</label>
                        <textarea
                            id="input"
                            v-model="input"
                            class="w-full h-32 p-3 border rounded-md resize-none"
                            placeholder="Enter your input here..."
                        />
                    </div>
                    <button
                        @click="executeTool"
                        :disabled="!input.trim() || isLoading"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isLoading ? 'Processing...' : 'Process' }}
                    </button>
                </div>

                <!-- Output Section -->
                <div class="space-y-4">
                    <h2 class="text-xl font-semibold">Output</h2>
                    <div v-if="result" class="p-4 border rounded-md bg-gray-50">
                        <div v-if="result.error" class="text-red-600">
                            {{ result.error }}
                        </div>
                        <pre v-else class="whitespace-pre-wrap text-sm">{{ JSON.stringify(result, null, 2) }}</pre>
                    </div>
                    <div v-else class="p-4 border rounded-md bg-gray-50 text-muted-foreground">
                        Process your input to see results here...
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
            'saved_data' => [],
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
        if (strpos($content, "'{$slug}' =>") !== false) {
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
        if (strpos($content, "execute{$componentName}(") !== false) {
            $this->info('Controller method already exists, skipping...');
            File::put($controllerPath, $content); // Still save the case update
            return;
        }

        // Add method before the last closing brace of the class
        $newMethod = "\n    private function execute{$componentName}(Request \$request): array\n    {\n        \$input = \$request->input('input', '');\n        \n        // TODO: Implement your tool logic here\n        return [\n            'processed' => \$input,\n            'message' => 'Tool logic not yet implemented',\n        ];\n    }";

        // Find the position of the last closing brace and insert before it
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos !== false) {
            $content = substr_replace($content, $newMethod . "\n}", $lastBracePos, 1);
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
                'saved_data' => [],
                'is_active' => true,
                'user_id' => \$adminUser->id,
            ]
        );
PHP;

        // Insert the new tool before the closing brace and curly brace of the run method
        $content = str_replace(
            '    }' . "\n" . '}',
            $newToolEntry . "\n" . '    }' . "\n" . '}',
            $content
        );

        File::put($seederPath, $content);

        $this->info('Updated PlaygroundToolSeeder for deployment');
    }
}
