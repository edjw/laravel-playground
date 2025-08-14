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

        $result = match ($tool->slug) {
            'todo-list' => $this->executeTodoList($request),
            'calculator' => $this->executeCalculator($request),
            'text-transformer' => $this->executeTextTransformer($request),
            default => ['error' => 'Tool not implemented'],
        };

        return response()->json($result);
    }

    private function executeTodoList(Request $request): array
    {
        $action = $request->input('action', 'get');
        $todos = $request->input('todos', []);
        
        // This method is actually not used by the frontend anymore
        // The frontend uses the update() method directly to save to saved_data
        // But keeping this for potential API usage
        
        switch ($action) {
            case 'add':
                $newTodo = [
                    'id' => uniqid(),
                    'text' => $request->input('text', ''),
                    'completed' => false,
                    'priority' => $request->input('priority', 'medium'),
                    'category' => $request->input('category', ''),
                    'dueDate' => $request->input('dueDate'),
                    'created_at' => now()->toISOString(),
                ];
                $todos[] = $newTodo;
                break;
                
            case 'toggle':
                $todoId = $request->input('todoId');
                foreach ($todos as &$todo) {
                    if ($todo['id'] === $todoId) {
                        $todo['completed'] = !$todo['completed'];
                        break;
                    }
                }
                break;
                
            case 'delete':
                $todoId = $request->input('todoId');
                $todos = array_filter($todos, fn($todo) => $todo['id'] !== $todoId);
                $todos = array_values($todos);
                break;
                
            case 'clear_completed':
                $todos = array_filter($todos, fn($todo) => !$todo['completed']);
                $todos = array_values($todos);
                break;
        }
        
        $total = count($todos);
        $completed = count(array_filter($todos, fn($todo) => $todo['completed']));
        
        return [
            'todos' => $todos,
            'stats' => [
                'total' => $total,
                'completed' => $completed,
                'remaining' => $total - $completed,
            ],
        ];
    }

    private function executeCalculator(Request $request): array
    {
        $operation = $request->input('operation');
        $num1 = $request->input('num1', 0);
        $num2 = $request->input('num2', 0);
        $expression = $request->input('expression', '');
        
        if ($expression) {
            // Handle complex expression evaluation
            try {
                // Simple expression evaluator (for security, we only allow basic math operations)
                $expression = preg_replace('/[^0-9+\-*\/().\s]/', '', $expression);
                $result = eval("return $expression;");
                
                return [
                    'result' => $result,
                    'expression' => $expression,
                    'formatted' => "$expression = $result"
                ];
            } catch (Exception $e) {
                return [
                    'error' => 'Invalid expression',
                    'expression' => $expression
                ];
            }
        }
        
        // Handle basic operations
        switch ($operation) {
            case 'add':
                $result = $num1 + $num2;
                break;
            case 'subtract':
                $result = $num1 - $num2;
                break;
            case 'multiply':
                $result = $num1 * $num2;
                break;
            case 'divide':
                if ($num2 == 0) {
                    return ['error' => 'Cannot divide by zero'];
                }
                $result = $num1 / $num2;
                break;
            case 'power':
                $result = pow($num1, $num2);
                break;
            case 'sqrt':
                if ($num1 < 0) {
                    return ['error' => 'Cannot calculate square root of negative number'];
                }
                $result = sqrt($num1);
                break;
            case 'percentage':
                $result = ($num1 / 100) * $num2;
                break;
            default:
                return ['error' => 'Unknown operation'];
        }
        
        return [
            'result' => $result,
            'operation' => $operation,
            'operands' => [$num1, $num2],
            'formatted' => $this->formatOperation($operation, $num1, $num2, $result)
        ];
    }
    
    private function formatOperation(string $operation, float $num1, float $num2, float $result): string
    {
        switch ($operation) {
            case 'add':
                return "$num1 + $num2 = $result";
            case 'subtract':
                return "$num1 - $num2 = $result";
            case 'multiply':
                return "$num1 × $num2 = $result";
            case 'divide':
                return "$num1 ÷ $num2 = $result";
            case 'power':
                return "$num1^$num2 = $result";
            case 'sqrt':
                return "√$num1 = $result";
            case 'percentage':
                return "$num1% of $num2 = $result";
            default:
                return "$num1 $operation $num2 = $result";
        }
    }
}