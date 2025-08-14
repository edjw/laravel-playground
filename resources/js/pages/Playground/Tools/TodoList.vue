<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { execute, update } from '@/actions/App/Http/Controllers/PlaygroundController';
import type { PlaygroundTool, BreadcrumbItem } from '@/types';

interface Props {
    tool: PlaygroundTool;
}

interface Todo {
    id: string;
    text: string;
    completed: boolean;
    priority: 'low' | 'medium' | 'high';
    category: string;
    dueDate: string | null;
    created_at: string;
}

interface TodoState {
    todos: Todo[];
    filter: 'all' | 'active' | 'completed';
    sortBy: 'created' | 'priority' | 'dueDate';
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
];

// State management
const newTodoText = ref('');
const newTodoPriority = ref<'low' | 'medium' | 'high'>('medium');
const newTodoCategory = ref('');
const newTodoDueDate = ref('');
const isLoading = ref(false);
const filter = ref<'all' | 'active' | 'completed'>('all');
const sortBy = ref<'created' | 'priority' | 'dueDate'>('created');

// Todo state
const todoState = ref<TodoState>({
    todos: [],
    filter: 'all',
    sortBy: 'created'
});

// Computed properties
const filteredTodos = computed(() => {
    let filtered = todoState.value.todos;
    
    // Filter by completion status
    switch (filter.value) {
        case 'active':
            filtered = filtered.filter(todo => !todo.completed);
            break;
        case 'completed':
            filtered = filtered.filter(todo => todo.completed);
            break;
    }
    
    // Sort todos
    return filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'priority':
                const priorityOrder = { high: 3, medium: 2, low: 1 };
                return priorityOrder[b.priority] - priorityOrder[a.priority];
            case 'dueDate':
                if (!a.dueDate && !b.dueDate) return 0;
                if (!a.dueDate) return 1;
                if (!b.dueDate) return -1;
                return new Date(a.dueDate).getTime() - new Date(b.dueDate).getTime();
            default:
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
        }
    });
});

const stats = computed(() => {
    const total = todoState.value.todos.length;
    const completed = todoState.value.todos.filter(todo => todo.completed).length;
    const remaining = total - completed;
    const overdue = todoState.value.todos.filter(todo => 
        !todo.completed && 
        todo.dueDate && 
        new Date(todo.dueDate) < new Date()
    ).length;
    
    return { total, completed, remaining, overdue };
});

const categories = computed(() => {
    const cats = [...new Set(todoState.value.todos.map(todo => todo.category).filter(Boolean))];
    return cats.sort();
});

// Load saved data on mount
onMounted(async () => {
    if (props.tool.saved_data && Array.isArray(props.tool.saved_data)) {
        todoState.value = {
            todos: props.tool.saved_data,
            filter: 'all',
            sortBy: 'created'
        };
    }
});

// Save state when todos change
watch(
    () => todoState.value.todos,
    async (newTodos) => {
        try {
            const action = update(props.tool);
            await router.visit(action.url, {
                method: action.method,
                data: { saved_data: newTodos },
                preserveState: true,
                preserveScroll: true,
                only: ['tool'],
            });
        } catch (error) {
            console.error('Failed to save todos:', error);
        }
    },
    { deep: true }
);

// Todo operations
const addTodo = async () => {
    if (!newTodoText.value.trim()) return;
    
    isLoading.value = true;
    try {
        const newTodo: Todo = {
            id: Date.now().toString(),
            text: newTodoText.value.trim(),
            completed: false,
            priority: newTodoPriority.value,
            category: newTodoCategory.value.trim(),
            dueDate: newTodoDueDate.value || null,
            created_at: new Date().toISOString()
        };
        
        todoState.value.todos.push(newTodo);
        
        // Reset form
        newTodoText.value = '';
        newTodoCategory.value = '';
        newTodoDueDate.value = '';
        newTodoPriority.value = 'medium';
    } catch (error) {
        console.error('Failed to add todo:', error);
    } finally {
        isLoading.value = false;
    }
};

const toggleTodo = (todoId: string) => {
    const todo = todoState.value.todos.find(t => t.id === todoId);
    if (todo) {
        todo.completed = !todo.completed;
    }
};

const deleteTodo = (todoId: string) => {
    todoState.value.todos = todoState.value.todos.filter(t => t.id !== todoId);
};

const clearCompleted = () => {
    todoState.value.todos = todoState.value.todos.filter(todo => !todo.completed);
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high': return 'text-red-600 bg-red-50 border-red-200';
        case 'medium': return 'text-yellow-600 bg-yellow-50 border-yellow-200';
        case 'low': return 'text-green-600 bg-green-50 border-green-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};

const formatDueDate = (dueDate: string | null) => {
    if (!dueDate) return '';
    const date = new Date(dueDate);
    const now = new Date();
    const diffDays = Math.ceil((date.getTime() - now.getTime()) / (1000 * 3600 * 24));
    
    if (diffDays < 0) return `${Math.abs(diffDays)} days overdue`;
    if (diffDays === 0) return 'Due today';
    if (diffDays === 1) return 'Due tomorrow';
    return `Due in ${diffDays} days`;
};

const isDueToday = (dueDate: string | null) => {
    if (!dueDate) return false;
    const today = new Date().toDateString();
    return new Date(dueDate).toDateString() === today;
};

const isOverdue = (todo: Todo) => {
    if (!todo.dueDate || todo.completed) return false;
    return new Date(todo.dueDate) < new Date();
};

// Handle Enter key
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        addTodo();
    }
};
</script>

<template>
    <Head :title="tool.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Tool Header -->
            <div class="text-center space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">{{ tool.name }}</h1>
                <p v-if="tool.description" class="text-muted-foreground">{{ tool.description }}</p>
            </div>

            <!-- Add Todo Form -->
            <div class="bg-white border rounded-lg p-6 shadow-sm">
                <h2 class="text-lg font-semibold mb-4">Add New Todo</h2>
                <div class="space-y-4">
                    <div>
                        <input
                            v-model="newTodoText"
                            @keydown="handleKeydown"
                            type="text"
                            placeholder="What needs to be done?"
                            class="w-full px-4 py-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-lg"
                            :disabled="isLoading"
                        />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select 
                                v-model="newTodoPriority"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :disabled="isLoading"
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input
                                v-model="newTodoCategory"
                                type="text"
                                placeholder="e.g., Work, Personal"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                :disabled="isLoading"
                                list="categories"
                            />
                            <datalist id="categories">
                                <option v-for="cat in categories" :key="cat" :value="cat" />
                            </datalist>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                            <input
                                v-model="newTodoDueDate"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                :disabled="isLoading"
                            />
                        </div>
                    </div>
                    
                    <button
                        @click="addTodo"
                        :disabled="!newTodoText.trim() || isLoading"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-colors"
                    >
                        {{ isLoading ? 'Adding...' : 'Add Todo' }}
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div v-if="stats.total > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 border rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-800">{{ stats.remaining }}</div>
                    <div class="text-sm text-yellow-600">Remaining</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-800">{{ stats.completed }}</div>
                    <div class="text-sm text-green-600">Completed</div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-800">{{ stats.overdue }}</div>
                    <div class="text-sm text-red-600">Overdue</div>
                </div>
            </div>

            <!-- Filters & Sort -->
            <div v-if="todoState.todos.length > 0" class="bg-white border rounded-lg p-4 shadow-sm">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex gap-2">
                        <button
                            @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                        >
                            All
                        </button>
                        <button
                            @click="filter = 'active'"
                            :class="filter === 'active' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                        >
                            Active
                        </button>
                        <button
                            @click="filter = 'completed'"
                            :class="filter === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                        >
                            Completed
                        </button>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Sort by:</label>
                        <select 
                            v-model="sortBy"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="created">Date Created</option>
                            <option value="priority">Priority</option>
                            <option value="dueDate">Due Date</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Todo List -->
            <div class="bg-white border rounded-lg shadow-sm">
                <div v-if="todoState.todos.length === 0" class="p-12 text-center text-gray-500">
                    <div class="text-6xl mb-4">📝</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No todos yet</h3>
                    <p>Add your first todo above to get started!</p>
                </div>
                
                <div v-else-if="filteredTodos.length === 0" class="p-8 text-center text-gray-500">
                    <div class="text-4xl mb-2">🔍</div>
                    <p>No todos match the current filter.</p>
                </div>
                
                <div v-else class="divide-y">
                    <div 
                        v-for="todo in filteredTodos" 
                        :key="todo.id" 
                        :class="[
                            'p-4 transition-colors',
                            todo.completed ? 'bg-gray-50 hover:bg-gray-100' : 'hover:bg-gray-50',
                            isOverdue(todo) ? 'bg-red-50 border-l-4 border-red-400' : '',
                            isDueToday(todo.dueDate) && !todo.completed ? 'bg-yellow-50 border-l-4 border-yellow-400' : ''
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <button
                                @click="toggleTodo(todo.id)"
                                :class="[
                                    'w-5 h-5 rounded-full border-2 transition-colors flex-shrink-0 flex items-center justify-center mt-0.5',
                                    todo.completed 
                                        ? 'bg-green-500 border-green-500 hover:bg-green-600 hover:border-green-600' 
                                        : 'border-gray-300 hover:border-blue-500'
                                ]"
                                :disabled="isLoading"
                            >
                                <svg v-if="todo.completed" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span 
                                        :class="[
                                            'font-medium',
                                            todo.completed ? 'text-gray-500 line-through' : 'text-gray-900'
                                        ]"
                                    >
                                        {{ todo.text }}
                                    </span>
                                    
                                    <span 
                                        :class="[
                                            'px-2 py-0.5 text-xs font-medium rounded-full border',
                                            getPriorityColor(todo.priority)
                                        ]"
                                    >
                                        {{ todo.priority }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span v-if="todo.category" class="flex items-center gap-1">
                                        <span class="w-2 h-2 bg-blue-400 rounded-full"></span>
                                        {{ todo.category }}
                                    </span>
                                    
                                    <span v-if="todo.dueDate" :class="isOverdue(todo) ? 'text-red-600 font-medium' : ''">
                                        {{ formatDueDate(todo.dueDate) }}
                                    </span>
                                </div>
                            </div>
                            
                            <button
                                @click="deleteTodo(todo.id)"
                                class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors px-2 py-1"
                                :disabled="isLoading"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div v-if="stats.completed > 0" class="p-4 border-t bg-gray-50">
                    <button
                        @click="clearCompleted"
                        :disabled="isLoading"
                        class="text-red-600 hover:text-red-800 text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Clear Completed ({{ stats.completed }})
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>