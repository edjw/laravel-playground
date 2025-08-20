<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlaygroundTool;
use Exception;
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
        // Ensure tool is active
        if (!$tool->is_active) {
            abort(404);
        }

        $userData = $tool->getOrCreateUserData(auth()->user());

        return Inertia::render("Playground/Tools/{$tool->component_name}", [
            'tool' => $tool,
            'savedData' => $userData->saved_data,
        ]);
    }

    public function update(PlaygroundTool $tool, Request $request)
    {
        // Ensure tool is active
        if (!$tool->is_active) {
            abort(404);
        }

        $validated = $request->validate([
            'saved_data' => ['nullable', 'array'],
            'configuration' => ['nullable', 'array'],
        ]);

        if (isset($validated['saved_data'])) {
            $userData = $tool->getOrCreateUserData(auth()->user());
            $userData->update(['saved_data' => $validated['saved_data']]);
        }

        if (isset($validated['configuration'])) {
            $tool->update(['configuration' => $validated['configuration']]);
        }

        // Always redirect back (Inertia standard)
        return redirect()->back();
    }

    public function execute(PlaygroundTool $tool, Request $request): JsonResponse
    {
        // Ensure tool is active
        if (!$tool->is_active) {
            abort(404);
        }

        // No tools currently implemented
        return response()->json(['error' => 'Tool not implemented']);
    }
    public function processWorkoutTracker(Request $request, PlaygroundTool $tool)
    {
        // Ensure tool is active
        if (!$tool->is_active) {
            abort(404);
        }
        
        $input = $request->input('input', '');
        
        try {
            // TODO: Implement your server-side tool logic here
            // Example processing
            $processed = strtoupper($input); // Replace with your logic
            
            $result = [
                'input' => $input,
                'processed' => $processed,
                'message' => 'Processing completed successfully',
                'timestamp' => now()->toISOString(),
            ];
            
            // Pure Inertia: redirect back to tool page with result as prop
            return redirect()->route('playground.show', $tool)
                ->with('processResult', $result);
                
        } catch (Exception $e) {
            $errorResult = [
                'error' => 'Processing failed: ' . $e->getMessage(),
                'input' => $input,
            ];
            
            return redirect()->route('playground.show', $tool)
                ->with('processResult', $errorResult);
        }
    }
}