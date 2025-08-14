<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlaygroundTool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class RemovePlaygroundTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:playground-tool 
                            {slug? : The slug of the playground tool to remove}
                            {--keep-component : Keep the Vue component file}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a playground tool with migration, seeder cleanup, and optional component removal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get available tools for selection
        $tools = PlaygroundTool::all();
        
        if ($tools->isEmpty()) {
            $this->info('No playground tools found to remove.');
            return self::SUCCESS;
        }

        // Get tool slug
        $slug = $this->argument('slug');
        
        if (!$slug) {
            $options = $tools->mapWithKeys(fn($tool) => [$tool->slug => "{$tool->name} ({$tool->slug})"])->toArray();
            $slug = select(
                label: 'Which tool would you like to remove?',
                options: $options,
            );
        }

        // Find the tool
        $tool = PlaygroundTool::where('slug', $slug)->first();
        
        if (!$tool) {
            $this->error("Tool with slug '{$slug}' not found.");
            return self::FAILURE;
        }

        // Confirm removal
        if (!$this->option('force') && !confirm("Are you sure you want to remove '{$tool->name}' ({$tool->slug})?", false)) {
            $this->info('Removal cancelled.');
            return self::SUCCESS;
        }

        $this->info("Removing playground tool: {$tool->name}");

        // 1. Create migration to remove from database
        $this->createRemovalMigration($tool);

        // 2. Remove from seeder
        $this->removeFromSeeder($tool);

        // 3. Remove controller logic if it exists
        $this->removeControllerLogic($tool);

        // 4. Optionally remove Vue component
        if (!$this->option('keep-component')) {
            $this->removeVueComponent($tool);
        }

        // 5. Remove from local database
        $tool->delete();

        $this->info('Playground tool removal completed successfully!');
        $this->line('');
        $this->line('<comment>Next steps:</comment>');
        $this->line('1. Commit and push these changes');
        $this->line('2. The migration will run automatically on deployment');
        $this->line('3. The tool will be removed from all environments');

        return self::SUCCESS;
    }

    private function createRemovalMigration(PlaygroundTool $tool): void
    {
        $migrationName = "remove_{$tool->slug}_playground_tool";
        $className = str($migrationName)->studly()->toString();
        
        // Create migration
        Artisan::call('make:migration', [
            'name' => $migrationName,
            '--no-interaction' => true,
        ]);

        // Get the migration file (latest one created)
        $migrationFiles = collect(File::files(database_path('migrations')))
            ->filter(fn($file) => str_contains($file->getFilename(), $migrationName))
            ->sortByDesc(fn($file) => $file->getMTime())
            ->first();

        if ($migrationFiles) {
            $migrationPath = $migrationFiles->getPathname();
            
            $stub = <<<PHP
<?php

use App\Models\PlaygroundTool;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove the {$tool->name} tool
        PlaygroundTool::where('slug', '{$tool->slug}')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the tool if needed
        PlaygroundTool::firstOrCreate(
            ['slug' => '{$tool->slug}'],
            [
                'name' => '{$tool->name}',
                'slug' => '{$tool->slug}',
                'description' => '{$tool->description}',
                'icon' => '{$tool->icon}',
                'component_name' => '{$tool->component_name}',
                'configuration' => json_decode('{}', true),
                'is_active' => true,
                'user_id' => null,
            ]
        );
    }
};
PHP;

            File::put($migrationPath, $stub);
            $this->info("Created migration: {$migrationFiles->getFilename()}");
        }
    }

    private function removeFromSeeder(PlaygroundTool $tool): void
    {
        $seederPath = database_path('seeders/PlaygroundToolSeeder.php');
        $content = File::get($seederPath);

        // Remove the tool creation block
        $patterns = [
            // Match the specific tool block with various spacing
            "/\n\s*\/\/ Create the {$tool->name} tool.*?PlaygroundTool::firstOrCreate\([^;]*?\);\s*/s",
            "/\n\s*PlaygroundTool::firstOrCreate\(\s*\['slug' => '{$tool->slug}'\].*?\);\s*/s",
        ];

        $originalContent = $content;
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
            if ($content !== $originalContent) {
                break; // Found and removed
            }
        }

        if ($content !== $originalContent) {
            File::put($seederPath, $content);
            $this->info("Removed {$tool->name} from PlaygroundToolSeeder");
        } else {
            $this->warn("Could not find {$tool->name} in PlaygroundToolSeeder (may have been removed already)");
        }
    }

    private function removeControllerLogic(PlaygroundTool $tool): void
    {
        $controllerPath = app_path('Http/Controllers/PlaygroundController.php');
        $content = File::get($controllerPath);

        $originalContent = $content;
        
        // Remove case from match statement
        $casePattern = "/\s*'{$tool->slug}' => [^,]*,\s*/";
        $content = preg_replace($casePattern, '', $content);

        // Remove execute method if it exists
        $methodPattern = "/\n\s*private function execute{$tool->component_name}\([^{]*\{[^}]*(?:\{[^}]*\}[^}]*)*\}\s*/s";
        $content = preg_replace($methodPattern, '', $content);

        // Remove process method if it exists
        $processMethodPattern = "/\n\s*public function process{$tool->component_name}\([^{]*\{(?:[^{}]*|\{[^{}]*\})*\}\s*/s";
        $content = preg_replace($processMethodPattern, '', $content);

        if ($content !== $originalContent) {
            File::put($controllerPath, $content);
            $this->info("Removed controller logic for {$tool->name}");
        } else {
            $this->info("No controller logic found for {$tool->name} (already clean)");
        }
    }

    private function removeVueComponent(PlaygroundTool $tool): void
    {
        $componentPath = resource_path("js/pages/Playground/Tools/{$tool->component_name}.vue");
        
        if (File::exists($componentPath)) {
            if ($this->option('force') || confirm("Delete Vue component file: {$tool->component_name}.vue?", false)) {
                File::delete($componentPath);
                $this->info("Removed Vue component: {$tool->component_name}.vue");
            } else {
                $this->info("Kept Vue component file");
            }
        } else {
            $this->info("Vue component not found (already removed or never existed)");
        }
    }
}
