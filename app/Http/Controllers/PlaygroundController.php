<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlaygroundTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlaygroundController extends Controller
{
    public function index(): Response
    {
        $tools = PlaygroundTool::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Playground/Index', [
            'tools' => $tools,
        ]);
    }

    public function show(PlaygroundTool $tool): Response
    {
        return Inertia::render("Playground/Tools/{$tool->component_name}", [
            'tool' => $tool,
        ]);
    }

    public function update(PlaygroundTool $tool, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'saved_data' => ['nullable', 'array'],
            'configuration' => ['nullable', 'array'],
        ]);

        $tool->update($validated);

        return response()->json(['tool' => $tool]);
    }

    public function execute(PlaygroundTool $tool, Request $request): JsonResponse
    {
        $result = match ($tool->slug) {
            'word-counter' => $this->executeWordCounter($request),
            'json-formatter' => $this->executeJsonFormatter($request),
            'color-palette' => $this->executeColorPalette($request),
            default => ['error' => 'Tool not implemented'],
        };

        return response()->json($result);
    }

    private function executeWordCounter(Request $request): array
    {
        $text = $request->input('text', '');

        return [
            'words' => str_word_count($text),
            'characters' => strlen($text),
            'characters_no_spaces' => strlen(str_replace(' ', '', $text)),
            'lines' => substr_count($text, "\n") + 1,
            'paragraphs' => count(array_filter(explode("\n\n", $text), fn ($p) => trim($p) !== '')),
        ];
    }

    private function executeJsonFormatter(Request $request): array
    {
        try {
            $jsonString = $request->input('json', '{}');
            $json = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

            return [
                'formatted' => json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'minified' => json_encode($json, JSON_UNESCAPED_SLASHES),
                'valid' => true,
                'size_original' => strlen($jsonString),
                'size_formatted' => strlen(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)),
                'size_minified' => strlen(json_encode($json, JSON_UNESCAPED_SLASHES)),
            ];
        } catch (\JsonException $e) {
            return [
                'error' => $e->getMessage(),
                'valid' => false,
            ];
        }
    }

    private function executeColorPalette(Request $request): array
    {
        $baseColor = $request->input('base_color', '#3B82F6');

        // Simple color palette generation (in a real app, you'd use a proper color theory library)
        $hex = ltrim($baseColor, '#');
        $rgb = sscanf($hex, '%02x%02x%02x');
        [$r, $g, $b] = $rgb;

        return [
            'palette' => [
                'primary' => $baseColor,
                'lighter' => sprintf('#%02x%02x%02x', min(255, $r + 40), min(255, $g + 40), min(255, $b + 40)),
                'darker' => sprintf('#%02x%02x%02x', max(0, $r - 40), max(0, $g - 40), max(0, $b - 40)),
                'complement' => sprintf('#%02x%02x%02x', 255 - $r, 255 - $g, 255 - $b),
                'triad1' => sprintf('#%02x%02x%02x', $g, $b, $r),
                'triad2' => sprintf('#%02x%02x%02x', $b, $r, $g),
            ],
        ];
    }
}
