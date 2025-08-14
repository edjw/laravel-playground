<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute, update } from '@/actions/App/Http/Controllers/PlaygroundController';
import type { PlaygroundTool, BreadcrumbItem } from '@/types';

interface Props {
    tool: PlaygroundTool;
}

interface CalculatorResult {
    id: string;
    result: number;
    expression: string;
    label: string;
    timestamp: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
];

// Calculator state
const display = ref('0');
const previousValue = ref(0);
const operation = ref('');
const waitingForOperand = ref(false);
const isLoading = ref(false);
const lastResult = ref<any>(null);
const expression = ref('');

// Result saving
const savedResults = ref<CalculatorResult[]>([]);
const showSaveDialog = ref(false);
const saveLabel = ref('');

// Load saved results on mount
onMounted(async () => {
    if (props.tool.saved_data && Array.isArray(props.tool.saved_data)) {
        savedResults.value = props.tool.saved_data;
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
                only: ['tool'],
            });
        } catch (error) {
            console.error('Failed to save results:', error);
        }
    },
    { deep: true }
);

// Calculator operations
const inputDigit = (digit: string) => {
    if (waitingForOperand.value) {
        display.value = digit;
        waitingForOperand.value = false;
    } else {
        display.value = display.value === '0' ? digit : display.value + digit;
    }
};

const inputDot = () => {
    if (waitingForOperand.value) {
        display.value = '0.';
        waitingForOperand.value = false;
    } else if (display.value.indexOf('.') === -1) {
        display.value += '.';
    }
};

const clear = () => {
    display.value = '0';
    previousValue.value = 0;
    operation.value = '';
    waitingForOperand.value = false;
    expression.value = '';
    lastResult.value = null;
};

const performOperation = (nextOperation: string) => {
    const inputValue = parseFloat(display.value);

    if (previousValue.value === 0) {
        previousValue.value = inputValue;
    } else if (operation.value) {
        const currentValue = previousValue.value || 0;
        calculate(currentValue, inputValue, operation.value);
    }

    waitingForOperand.value = true;
    operation.value = nextOperation;
};

const calculate = (firstValue: number, secondValue: number, op: string) => {
    let result: number;
    let error: string | null = null;
    
    try {
        switch (op) {
            case 'add':
                result = firstValue + secondValue;
                break;
            case 'subtract':
                result = firstValue - secondValue;
                break;
            case 'multiply':
                result = firstValue * secondValue;
                break;
            case 'divide':
                if (secondValue === 0) {
                    error = 'Cannot divide by zero';
                    break;
                }
                result = firstValue / secondValue;
                break;
            case 'power':
                result = Math.pow(firstValue, secondValue);
                break;
            case 'percentage':
                result = (firstValue / 100) * secondValue;
                break;
            default:
                error = 'Unknown operation';
        }
        
        if (error) {
            display.value = 'Error';
            lastResult.value = { error };
        } else {
            display.value = result!.toString();
            previousValue.value = result!;
            lastResult.value = {
                result: result!,
                operation: op,
                operands: [firstValue, secondValue],
                formatted: formatOperation(op, firstValue, secondValue, result!)
            };
        }
    } catch (error) {
        display.value = 'Error';
        lastResult.value = { error: 'Calculation failed' };
    }
};

const performCalculation = () => {
    const inputValue = parseFloat(display.value);

    if (previousValue.value && operation.value) {
        calculate(previousValue.value, inputValue, operation.value);
        operation.value = '';
        previousValue.value = 0;
        waitingForOperand.value = true;
    }
};

// Format operation display
const formatOperation = (operation: string, num1: number, num2: number, result: number): string => {
    switch (operation) {
        case 'add':
            return `${num1} + ${num2} = ${result}`;
        case 'subtract':
            return `${num1} - ${num2} = ${result}`;
        case 'multiply':
            return `${num1} × ${num2} = ${result}`;
        case 'divide':
            return `${num1} ÷ ${num2} = ${result}`;
        case 'power':
            return `${num1}^${num2} = ${result}`;
        case 'sqrt':
            return `√${num1} = ${result}`;
        case 'percentage':
            return `${num1}% of ${num2} = ${result}`;
        default:
            return `${num1} ${operation} ${num2} = ${result}`;
    }
};

// Advanced operations
const performAdvancedOperation = (op: string) => {
    const inputValue = parseFloat(display.value);
    let result: number;
    let error: string | null = null;
    
    try {
        switch (op) {
            case 'sqrt':
                if (inputValue < 0) {
                    error = 'Cannot calculate square root of negative number';
                    break;
                }
                result = Math.sqrt(inputValue);
                break;
            default:
                error = 'Unknown operation';
        }
        
        if (error) {
            display.value = 'Error';
            lastResult.value = { error };
        } else {
            display.value = result!.toString();
            lastResult.value = {
                result: result!,
                operation: op,
                operands: [inputValue],
                formatted: formatOperation(op, inputValue, 0, result!)
            };
        }
    } catch (error) {
        display.value = 'Error';
        lastResult.value = { error: 'Calculation failed' };
    }
};

// Expression calculation
const calculateExpression = () => {
    if (!expression.value.trim()) return;
    
    try {
        // Clean expression - only allow numbers, basic operators, parentheses, and decimal points
        const cleanExpression = expression.value.replace(/[^0-9+\-*/().\s]/g, '');
        
        if (cleanExpression !== expression.value) {
            display.value = 'Error';
            lastResult.value = { error: 'Invalid characters in expression' };
            return;
        }
        
        // Evaluate the expression safely
        const result = Function(`"use strict"; return (${cleanExpression})`)();
        
        if (typeof result !== 'number' || !isFinite(result)) {
            display.value = 'Error';
            lastResult.value = { error: 'Invalid expression' };
        } else {
            display.value = result.toString();
            lastResult.value = {
                result,
                expression: cleanExpression,
                formatted: `${cleanExpression} = ${result}`
            };
        }
    } catch (error) {
        display.value = 'Error';
        lastResult.value = { error: 'Invalid expression' };
    }
};

// Result saving functions
const openSaveDialog = () => {
    if (lastResult.value && !lastResult.value.error) {
        showSaveDialog.value = true;
        saveLabel.value = lastResult.value.formatted || `Result: ${display.value}`;
    }
};

const saveResult = () => {
    if (lastResult.value && !lastResult.value.error && saveLabel.value.trim()) {
        const newResult: CalculatorResult = {
            id: Date.now().toString(),
            result: parseFloat(display.value),
            expression: lastResult.value.formatted || display.value,
            label: saveLabel.value.trim(),
            timestamp: new Date().toISOString(),
        };
        
        savedResults.value.unshift(newResult);
        showSaveDialog.value = false;
        saveLabel.value = '';
    }
};

const loadResult = (result: CalculatorResult) => {
    display.value = result.result.toString();
    previousValue.value = 0;
    operation.value = '';
    waitingForOperand.value = false;
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
    averageResult: savedResults.value.length > 0 
        ? savedResults.value.reduce((sum, r) => sum + r.result, 0) / savedResults.value.length
        : 0,
    maxResult: savedResults.value.length > 0 
        ? Math.max(...savedResults.value.map(r => r.result))
        : 0,
    minResult: savedResults.value.length > 0 
        ? Math.min(...savedResults.value.map(r => r.result))
        : 0,
}));

// Keyboard support
const handleKeydown = (event: KeyboardEvent) => {
    const key = event.key;
    
    if (/[0-9]/.test(key)) {
        inputDigit(key);
    } else if (key === '.') {
        inputDot();
    } else if (key === '+') {
        performOperation('add');
    } else if (key === '-') {
        performOperation('subtract');
    } else if (key === '*') {
        performOperation('multiply');
    } else if (key === '/') {
        event.preventDefault();
        performOperation('divide');
    } else if (key === 'Enter' || key === '=') {
        event.preventDefault();
        performCalculation();
    } else if (key === 'Escape' || key === 'c' || key === 'C') {
        clear();
    }
};

// Add keyboard listener
onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});
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
                <!-- Calculator -->
                <div class="lg:col-span-2 bg-white border rounded-lg p-6 shadow-sm">
                    <h2 class="text-lg font-semibold mb-4">Calculator</h2>
                    
                    <!-- Display -->
                    <div class="bg-gray-900 text-white p-4 rounded-lg mb-4 text-right">
                        <div class="text-3xl font-mono" :class="{ 'opacity-50': isLoading }">
                            {{ isLoading ? 'Calculating...' : display }}
                        </div>
                        <div v-if="lastResult && lastResult.formatted" class="text-sm text-gray-400 mt-1">
                            {{ lastResult.formatted }}
                        </div>
                        <div v-if="lastResult && lastResult.error" class="text-sm text-red-400 mt-1">
                            {{ lastResult.error }}
                        </div>
                    </div>

                    <!-- Expression Input -->
                    <div class="mb-4">
                        <div class="flex gap-2">
                            <input
                                v-model="expression"
                                type="text"
                                placeholder="Enter expression (e.g., 2 + 3 * 4)"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                @keydown.enter="calculateExpression"
                            />
                            <button
                                @click="calculateExpression"
                                :disabled="!expression.trim() || isLoading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Calculate
                            </button>
                        </div>
                    </div>

                    <!-- Calculator Buttons -->
                    <div class="grid grid-cols-5 gap-2">
                        <!-- First Row -->
                        <button @click="clear" class="col-span-2 px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-300 text-gray-700 hover:bg-gray-400">Clear</button>
                        <button @click="performAdvancedOperation('sqrt')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">√</button>
                        <button @click="performOperation('power')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">x^y</button>
                        <button @click="performOperation('divide')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">÷</button>

                        <!-- Second Row -->
                        <button @click="inputDigit('7')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">7</button>
                        <button @click="inputDigit('8')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">8</button>
                        <button @click="inputDigit('9')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">9</button>
                        <button @click="performOperation('percentage')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">%</button>
                        <button @click="performOperation('multiply')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">×</button>

                        <!-- Third Row -->
                        <button @click="inputDigit('4')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">4</button>
                        <button @click="inputDigit('5')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">5</button>
                        <button @click="inputDigit('6')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">6</button>
                        <button @click="performCalculation" class="row-span-2 px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-green-600 text-white hover:bg-green-700">=</button>
                        <button @click="performOperation('subtract')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">-</button>

                        <!-- Fourth Row -->
                        <button @click="inputDigit('1')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">1</button>
                        <button @click="inputDigit('2')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">2</button>
                        <button @click="inputDigit('3')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">3</button>
                        <!-- Empty cell for equals button -->
                        <div></div>
                        <button @click="performOperation('add')" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-blue-100 text-blue-900 hover:bg-blue-200">+</button>

                        <!-- Fifth Row -->
                        <button @click="inputDigit('0')" class="col-span-2 px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">0</button>
                        <button @click="inputDot" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 text-gray-900 hover:bg-gray-200">.</button>
                        <button @click="openSaveDialog" :disabled="!lastResult || lastResult.error" class="px-4 py-3 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">Save</button>
                        <!-- Empty cell -->
                        <div></div>
                    </div>

                    <div class="mt-4 text-sm text-gray-600">
                        <p><strong>Keyboard shortcuts:</strong> Numbers, +, -, *, /, Enter/= (calculate), Esc/C (clear)</p>
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
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>Total:</strong> {{ stats.totalResults }}</div>
                            <div><strong>Average:</strong> {{ stats.averageResult.toFixed(2) }}</div>
                            <div><strong>Max:</strong> {{ stats.maxResult }}</div>
                            <div><strong>Min:</strong> {{ stats.minResult }}</div>
                        </div>
                    </div>

                    <!-- Results List -->
                    <div v-if="savedResults.length === 0" class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">🧮</div>
                        <p>No saved results yet.</p>
                        <p class="text-sm">Perform calculations and save them!</p>
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
                                    <div class="text-xs text-gray-600 mt-1">
                                        {{ result.expression }}
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Result</label>
                            <div class="p-2 bg-gray-100 rounded-md text-gray-700">
                                {{ lastResult?.formatted || display }}
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