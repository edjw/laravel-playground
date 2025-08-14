<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlaygroundTool;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ListPlaygroundTools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list:playground-tools 
                            {--active : Only show active tools}
                            {--inactive : Only show inactive tools}
                            {--user= : Filter by user ID}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all playground tools with their details';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = PlaygroundTool::query()->with('user:id,name,email');

        // Apply filters
        if ($this->option('active')) {
            $query->where('is_active', true);
        } elseif ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        if ($userId = $this->option('user')) {
            $query->where('user_id', $userId);
        }

        $tools = $query->orderBy('name')->get();

        if ($tools->isEmpty()) {
            if ($this->option('json')) {
                $this->info('[]');
            } else {
                $this->info('No playground tools found.');
            }
            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->outputJson($tools);
        } else {
            $this->outputTable($tools);
        }

        return self::SUCCESS;
    }

    private function outputJson($tools): void
    {
        $data = $tools->map(function (PlaygroundTool $tool) {
            return [
                'id' => $tool->id,
                'name' => $tool->name,
                'slug' => $tool->slug,
                'description' => $tool->description,
                'icon' => $tool->icon,
                'component_name' => $tool->component_name,
                'is_active' => $tool->is_active,
                'user' => $tool->user ? [
                    'id' => $tool->user->id,
                    'name' => $tool->user->name,
                    'email' => $tool->user->email,
                ] : null,
                'created_at' => $tool->created_at?->toISOString(),
                'updated_at' => $tool->updated_at?->toISOString(),
            ];
        });

        $this->info(json_encode($data, JSON_PRETTY_PRINT));
    }

    private function outputTable($tools): void
    {
        $this->info("Found {$tools->count()} playground tool(s):");
        $this->newLine();

        $headers = ['ID', 'Name', 'Slug', 'Status', 'Icon', 'Component', 'Owner', 'Created'];
        $rows = [];

        foreach ($tools as $tool) {
            $rows[] = [
                $tool->id,
                $tool->name,
                $tool->slug,
                $tool->is_active ? '🟢 Active' : '🔴 Inactive',
                $tool->icon,
                $tool->component_name,
                $tool->user ? "{$tool->user->name} ({$tool->user->email})" : 'System',
                $tool->created_at?->format('M j, Y'),
            ];
        }

        $this->table($headers, $rows);

        // Summary stats
        $activeCount = $tools->where('is_active', true)->count();
        $inactiveCount = $tools->where('is_active', false)->count();
        $systemTools = $tools->where('user_id', null)->count();
        $userTools = $tools->where('user_id', '!=', null)->count();

        $this->newLine();
        $this->info("Summary:");
        $this->info("• Active: {$activeCount}, Inactive: {$inactiveCount}");
        $this->info("• System tools: {$systemTools}, User tools: {$userTools}");

        if ($tools->isNotEmpty()) {
            $this->newLine();
            $this->info("Tool URLs:");
            foreach ($tools->where('is_active', true) as $tool) {
                $this->info("• {$tool->name}: /playground/tools/{$tool->slug}");
            }
        }
    }
}
