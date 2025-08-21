<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useWorkoutData } from '@/composables/useWorkoutData';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, PlaygroundTool } from '@/types';
import type { WorkoutData } from '@/types/workout';
import { Head } from '@inertiajs/vue3';
import type { DateValue } from '@internationalized/date';
import { DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date';
import { CalendarIcon, Edit, Plus, Trash2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Props {
    tool: PlaygroundTool;
    savedData?: WorkoutData;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
];

// Initialize workout data composable - ensure we always have valid data structure
const initialData: WorkoutData = {
    exercises: props.savedData?.exercises || [],
    sessions: props.savedData?.sessions || [],
};
const {
    exercises,
    sortedExercises,
    isSaving,
    saveError,
    addExercise,
    updateExercise,
    deleteExercise,
    addSession,
    updateSession,
    deleteSession,
    getDefaultsForExercise,
    getHistoryForExercise,
    loadData,
} = useWorkoutData(initialData);

// Current UI state
const selectedExerciseId = ref<string>('');
const currentSession = ref({
    sets: [{ weight: undefined as number | undefined, reps: undefined as number | undefined }],
    notes: '',
});
const editingSession = ref<WorkoutSession | null>(null);
const sessionToDelete = ref<string | null>(null);
const showDeleteDialog = ref(false);
const editingExercise = ref<Exercise | null>(null);
const exerciseToDelete = ref<string | null>(null);
const showDeleteExerciseDialog = ref(false);

// Exercise form for adding new exercises
const exerciseForm = ref({
    name: '',
    type: 'weight' as 'weight' | 'bodyweight',
});

// Date picker
const df = new DateFormatter('en-GB', {
    dateStyle: 'long',
});
const editingSessionDate = ref<DateValue>();

// Load data on mount
onMounted(() => {
    if (props.savedData) {
        loadData(props.savedData);
    }
});

// Exercise selection handler
const onExerciseSelected = (exerciseId: string) => {
    selectedExerciseId.value = exerciseId;
    currentSession.value = {
        sets: [
            {
                weight: undefined,
                reps: undefined,
            },
        ],
        notes: '',
    };
};

// Add new exercise
const onAddExercise = () => {
    if (!exerciseForm.value.name.trim()) return;

    const newExercise = addExercise({
        name: exerciseForm.value.name.trim(),
        type: exerciseForm.value.type,
    });

    // Select the new exercise
    selectedExerciseId.value = newExercise.id;
    onExerciseSelected(newExercise.id);

    // Reset form
    exerciseForm.value = { name: '', type: 'weight' };
};

// Add set to current session
const addSet = () => {
    if (!selectedExerciseId.value) return;

    // Try to get defaults from the last session or current sets
    const defaults = getDefaultsForExercise(selectedExerciseId.value);
    const lastSet = currentSession.value.sets[currentSession.value.sets.length - 1];

    currentSession.value.sets.push({
        weight: lastSet?.weight || defaults.weight || undefined,
        reps: lastSet?.reps || defaults.reps || undefined,
    });
};

// Remove set from current session
const removeSet = (index: number) => {
    if (currentSession.value.sets.length > 1) {
        currentSession.value.sets.splice(index, 1);
    }
};

// Save current session
const saveSession = () => {
    if (!selectedExerciseId.value || currentSession.value.sets.length === 0) return;

    addSession({
        exercise_id: selectedExerciseId.value,
        sets: currentSession.value.sets.filter((set) => set.reps && set.reps > 0) as WorkoutSet[],
        notes: currentSession.value.notes.trim() || undefined,
        date: new Date().toISOString().split('T')[0],
    });

    // Reset current session with empty values
    currentSession.value = {
        sets: [
            {
                weight: undefined,
                reps: undefined,
            },
        ],
        notes: '',
    };
};

// Get selected exercise
import type { Exercise, WorkoutSession, WorkoutSet } from '@/types/workout';
const selectedExercise = ref<Exercise | null>(null);
const updateSelectedExercise = () => {
    if (selectedExerciseId.value) {
        selectedExercise.value = exercises.value.find((e) => e.id === selectedExerciseId.value) || null;
    } else {
        selectedExercise.value = null;
    }
};

// Watch for changes in selected exercise
import { watch } from 'vue';
watch(selectedExerciseId, updateSelectedExercise, { immediate: true });
watch(exercises, updateSelectedExercise);

// Helper function for display
const getLastSession = () => {
    if (!selectedExerciseId.value) return 'None';
    const lastSession = getHistoryForExercise(selectedExerciseId.value, 1, 1).sessions[0];
    if (!lastSession) return 'No previous sessions';

    const date = new Date(lastSession.date).toLocaleDateString();
    const exercise = selectedExercise.value;

    // Create detailed set information
    const setDetails = lastSession.sets
        .map((set) => {
            if (exercise?.type === 'weight' && set.weight) {
                return `${set.weight}kg × ${set.reps} reps`;
            }
            return `${set.reps} reps`;
        })
        .join(', ');

    return `${date}. ${setDetails}`;
};

// Edit session functionality
const startEditSession = (session: WorkoutSession) => {
    editingSession.value = { ...session };
    try {
        editingSessionDate.value = parseDate(session.date);
        console.log('Parsed date:', session.date, '->', editingSessionDate.value);
    } catch (error) {
        console.error('Error parsing date:', session.date, error);
    }
};

const cancelEditSession = () => {
    editingSession.value = null;
    editingSessionDate.value = undefined;
};

const saveEditSession = () => {
    if (!editingSession.value || !editingSessionDate.value) return;

    // Filter out empty sets (no reps)
    const validSets = editingSession.value.sets.filter((set) => set.reps && set.reps > 0);

    updateSession(editingSession.value.id, {
        ...editingSession.value,
        sets: validSets,
        notes: editingSession.value.notes?.trim() || undefined,
        date: editingSessionDate.value.toString(),
    });

    editingSession.value = null;
    editingSessionDate.value = undefined;
};

const confirmDeleteSession = (sessionId: string) => {
    sessionToDelete.value = sessionId;
    showDeleteDialog.value = true;
};

const executeDeleteSession = () => {
    const sessionIdToDelete = sessionToDelete.value;

    if (sessionIdToDelete) {
        deleteSession(sessionIdToDelete);
        sessionToDelete.value = null;
        showDeleteDialog.value = false;
    }
};

const cancelDeleteSession = () => {
    sessionToDelete.value = null;
    showDeleteDialog.value = false;
};

// Add set to editing session
const addSetToEdit = () => {
    if (!editingSession.value) return;

    const lastSet = editingSession.value.sets[editingSession.value.sets.length - 1];
    editingSession.value.sets.push({
        weight: lastSet?.weight || undefined,
        reps: lastSet?.reps ?? 1,
    });
};

// Remove set from editing session
const removeSetFromEdit = (index: number) => {
    if (!editingSession.value || editingSession.value.sets.length <= 1) return;
    editingSession.value.sets.splice(index, 1);
};

// Exercise edit functionality
const startEditExercise = (exercise: Exercise) => {
    editingExercise.value = { ...exercise };
};

const saveEditExercise = () => {
    if (!editingExercise.value || !editingExercise.value.name.trim()) return;

    updateExercise(editingExercise.value.id, {
        name: editingExercise.value.name.trim(),
        type: editingExercise.value.type,
    });

    // Force update of selected exercise to reflect changes immediately
    updateSelectedExercise();

    editingExercise.value = null;
};

// Exercise delete functionality
const confirmDeleteExercise = (exerciseId: string) => {
    exerciseToDelete.value = exerciseId;
    showDeleteExerciseDialog.value = true;
};

const executeDeleteExercise = () => {
    const exerciseIdToDelete = exerciseToDelete.value;

    if (exerciseIdToDelete) {
        // Clear selected exercise if it's the one being deleted
        if (selectedExerciseId.value === exerciseIdToDelete) {
            selectedExerciseId.value = '';
        }

        deleteExercise(exerciseIdToDelete);
        exerciseToDelete.value = null;
        showDeleteExerciseDialog.value = false;
    }
};

const cancelDeleteExercise = () => {
    exerciseToDelete.value = null;
    showDeleteExerciseDialog.value = false;
};
</script>

<template>
    <Head :title="tool.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Mobile-first container with proper touch targets -->
        <div class="mx-auto mt-4 max-w-4xl space-y-6 px-4 sm:px-6">
            <!-- Auto-save status indicator -->
            <div v-if="isSaving || saveError" class="fixed top-4 right-4 z-50">
                <div v-if="isSaving" class="rounded-lg bg-blue-100 px-3 py-2 text-sm text-blue-800 shadow-sm">Saving...</div>
                <div v-else-if="saveError" class="rounded-lg bg-red-100 px-3 py-2 text-sm text-red-800 shadow-sm">
                    {{ saveError }}
                </div>
            </div>

            <!-- Exercise Selection - Full view when no exercise selected -->
            <div v-if="!selectedExercise && exercises && exercises.length > 0" class="rounded-lg border bg-white p-4 shadow-sm sm:p-6">
                <h2 class="mb-4 text-lg font-semibold">Select Exercise</h2>

                <div class="flex items-center gap-2">
                    <Select v-model="selectedExerciseId" @update:model-value="(value) => value && onExerciseSelected(String(value))">
                        <SelectTrigger class="h-11 min-h-[44px] flex-1">
                            <SelectValue placeholder="Choose an exercise..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="exercise in sortedExercises" :key="exercise.value" :value="exercise.value">
                                {{ exercise.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Dialog>
                        <DialogTrigger as-child>
                            <Button
                                size="sm"
                                class="h-11 min-h-[44px] w-11 min-w-[44px] p-0 text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                                variant="outline"
                            >
                                <Plus :size="18" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] p-0 sm:max-w-[425px]">
                            <DialogHeader class="p-6 pb-0">
                                <DialogTitle>Add New Exercise</DialogTitle>
                                <DialogDescription> Create a new exercise to track your workouts. </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 overflow-y-auto px-6 py-4">
                                <form @submit.prevent="onAddExercise" class="space-y-4">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Name</label>
                                        <input
                                            v-model="exerciseForm.name"
                                            type="text"
                                            class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                            placeholder="e.g. Push-ups, Squats, Deadlift"
                                            required
                                        />
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Type</label>
                                        <select
                                            v-model="exerciseForm.type"
                                            class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        >
                                            <option value="weight">Weights</option>
                                            <option value="bodyweight">Bodyweight</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <DialogFooter class="p-6 pt-0">
                                <DialogClose as-child>
                                    <Button type="button" variant="outline" @click="exerciseForm = { name: '', type: 'weight' }"> Cancel </Button>
                                </DialogClose>
                                <DialogClose as-child>
                                    <Button type="button" @click="onAddExercise" :disabled="!exerciseForm.name.trim()"> Add Exercise </Button>
                                </DialogClose>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Compact Exercise Selection - When exercise is selected -->
            <div v-if="selectedExercise" class="rounded-lg border bg-gray-50 px-4 py-3">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <Select v-model="selectedExerciseId" @update:model-value="(value) => value && onExerciseSelected(String(value))">
                            <SelectTrigger class="h-9 min-h-[44px] flex-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="exercise in sortedExercises" :key="exercise.value" :value="exercise.value">
                                    {{ exercise.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Dialog>
                            <DialogTrigger as-child>
                                <Button
                                    size="sm"
                                    class="h-9 min-h-[44px] w-9 min-w-[44px] p-0 text-blue-600 hover:bg-blue-100 hover:text-blue-800"
                                    variant="ghost"
                                >
                                    <Plus :size="16" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] p-0 sm:max-w-[425px]">
                                <DialogHeader class="p-6 pb-0">
                                    <DialogTitle>Add New Exercise</DialogTitle>
                                    <DialogDescription> Create a new exercise to track your workouts. </DialogDescription>
                                </DialogHeader>
                                <div class="grid gap-4 overflow-y-auto px-6 py-4">
                                    <form @submit.prevent="onAddExercise" class="space-y-4">
                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Name</label>
                                            <input
                                                v-model="exerciseForm.name"
                                                type="text"
                                                class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                placeholder="e.g. Push-ups, Squats, Deadlift"
                                                required
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Type</label>
                                            <select
                                                v-model="exerciseForm.type"
                                                class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                            >
                                                <option value="weight">Weights</option>
                                                <option value="bodyweight">Bodyweight</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <DialogFooter class="p-6 pt-0">
                                    <DialogClose as-child>
                                        <Button type="button" variant="outline" @click="exerciseForm = { name: '', type: 'weight' }"> Cancel </Button>
                                    </DialogClose>
                                    <DialogClose as-child>
                                        <Button type="button" @click="onAddExercise" :disabled="!exerciseForm.name.trim()"> Add Exercise </Button>
                                    </DialogClose>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </div>

            <!-- Workout Recording -->
            <div v-if="selectedExercise" class="rounded-lg border bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">{{ selectedExercise.name }}</h2>
                        <div class="flex items-center gap-1">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="flex h-9 min-h-[44px] w-9 min-w-[44px] items-center justify-center p-0 text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                                        @click="startEditExercise(selectedExercise)"
                                    >
                                        <Edit :size="18" />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] p-0 sm:max-w-[425px]">
                                    <DialogHeader class="p-6 pb-0">
                                        <DialogTitle>Edit Exercise</DialogTitle>
                                        <DialogDescription> Make changes to your exercise here. </DialogDescription>
                                    </DialogHeader>
                                    <div class="grid gap-4 overflow-y-auto px-6 py-4" v-if="editingExercise">
                                        <form @submit.prevent="saveEditExercise" class="space-y-4">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Name</label>
                                                <input
                                                    v-model="editingExercise.name"
                                                    type="text"
                                                    class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                    placeholder="e.g. Push-ups, Squats, Deadlift"
                                                    required
                                                />
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Type</label>
                                                <select
                                                    v-model="editingExercise.type"
                                                    class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                                >
                                                    <option value="weight">Weights</option>
                                                    <option value="bodyweight">Bodyweight</option>
                                                </select>
                                            </div>
                                        </form>
                                    </div>
                                    <DialogFooter class="p-6 pt-0">
                                        <DialogClose as-child>
                                            <Button type="button" variant="outline" @click="editingExercise = null"> Cancel </Button>
                                        </DialogClose>
                                        <DialogClose as-child>
                                            <Button type="button" @click="saveEditExercise" :disabled="!editingExercise?.name.trim()">
                                                Save Changes
                                            </Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                            <button
                                @click="confirmDeleteExercise(selectedExercise.id)"
                                class="flex h-9 min-h-[44px] w-9 min-w-[44px] items-center justify-center rounded-md text-red-600 transition-colors hover:bg-red-50 hover:text-red-800"
                                title="Delete Exercise"
                            >
                                <Trash2 :size="18" />
                            </button>
                        </div>
                    </div>

                    <!-- Previous session info - now clearly underneath -->
                    <div class="mt-2 text-sm text-gray-600"><span class="font-medium">Last:</span> {{ getLastSession() }}</div>
                </div>

                <!-- Sets Recording -->
                <div class="space-y-4">
                    <div v-for="(set, index) in currentSession.sets" :key="index" class="flex items-end gap-3">
                        <span class="w-8 pb-3 text-sm font-medium">{{ index + 1 }}.</span>

                        <!-- Weight input (only for weight exercises) -->
                        <div v-if="selectedExercise.type === 'weight'" class="flex-1">
                            <label class="mb-1 block text-xs text-gray-600">Weight (kg)</label>
                            <input
                                v-model.number="set.weight"
                                type="number"
                                step="0.5"
                                min="0"
                                class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                placeholder=""
                            />
                        </div>

                        <!-- Reps input -->
                        <div class="flex-1">
                            <label class="mb-1 block text-xs text-gray-600">Reps</label>
                            <input
                                v-model.number="set.reps"
                                type="number"
                                min="1"
                                class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                placeholder=""
                            />
                        </div>

                        <!-- Remove set button -->
                        <button
                            v-if="currentSession.sets.length > 1"
                            @click="removeSet(index)"
                            class="flex h-11 min-h-[44px] w-11 min-w-[44px] items-center justify-center rounded-md border border-red-200 text-red-600 transition-colors hover:border-red-300 hover:text-red-800"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Add Set button -->
                    <div class="flex justify-center">
                        <button
                            @click="addSet"
                            class="h-11 min-h-[44px] rounded-md border border-dashed border-gray-300 px-6 py-2 text-gray-600 transition-colors hover:border-gray-400 hover:text-gray-700"
                        >
                            + Add Set
                        </button>
                    </div>
                </div>

                <!-- Session Notes -->
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">Add notes about this session</summary>
                    <div class="mt-2">
                        <textarea
                            v-model="currentSession.notes"
                            rows="2"
                            class="w-full resize-none rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            placeholder="Add notes about this session..."
                        />
                    </div>
                </details>

                <!-- Save Session Button -->
                <div class="mt-6 flex gap-3">
                    <button
                        @click="saveSession"
                        :disabled="currentSession.sets.length === 0 || currentSession.sets.every((s) => !s.reps || s.reps === 0)"
                        class="h-12 min-h-[44px] flex-1 rounded-md bg-green-600 px-4 py-2 font-medium text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Save
                    </button>
                    <Dialog v-if="selectedExercise && getHistoryForExercise(selectedExercise.id, 1, 1).sessions.length > 0">
                        <DialogTrigger as-child>
                            <button class="h-12 min-h-[44px] rounded-md bg-gray-600 px-4 py-2 text-white transition-colors hover:bg-gray-700">
                                History
                            </button>
                        </DialogTrigger>
                        <DialogContent class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] p-0 sm:max-w-[425px]">
                            <DialogHeader class="p-6 pb-0">
                                <DialogTitle>{{ selectedExercise.name }} History</DialogTitle>
                                <DialogDescription>Your previous workout sessions for this exercise</DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 overflow-y-auto px-6 py-4">
                                <div class="max-h-[60vh] space-y-3 overflow-y-auto">
                                    <div
                                        v-for="session in getHistoryForExercise(selectedExercise.id, 1, 10).sessions"
                                        :key="session.id"
                                        class="rounded-lg border p-3"
                                    >
                                        <!-- Session Header with Edit/Delete buttons -->
                                        <div class="mb-2 flex items-center justify-between">
                                            <span class="font-medium">{{ new Date(session.date).toLocaleDateString() }}</span>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="startEditSession(session)"
                                                    class="flex h-8 min-h-[44px] w-8 min-w-[44px] items-center justify-center rounded-md text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-800"
                                                    title="Edit Session"
                                                >
                                                    <Edit :size="16" />
                                                </button>
                                                <button
                                                    @click="confirmDeleteSession(session.id)"
                                                    class="flex h-8 min-h-[44px] w-8 min-w-[44px] items-center justify-center rounded-md text-red-600 transition-colors hover:bg-red-50 hover:text-red-800"
                                                    title="Delete Session"
                                                >
                                                    <Trash2 :size="16" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Session Details -->
                                        <div class="space-y-1 text-sm">
                                            <div v-for="(set, index) in session.sets" :key="index" class="flex justify-between">
                                                <span>Set {{ index + 1 }}:</span>
                                                <span>
                                                    <template v-if="selectedExercise.type === 'weight'"> {{ set.weight }}kg × </template>
                                                    {{ set.reps }} reps
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="session.notes" class="mt-2 text-sm text-gray-600 italic">"{{ session.notes }}"</div>
                                    </div>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Edit Session Dialog -->
            <div v-if="editingSession" class="bg-opacity-50 fixed inset-0 z-[60] flex items-center justify-center bg-black p-4">
                <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6">
                    <h3 class="mb-4 text-lg font-semibold">Edit Session</h3>

                    <form @submit.prevent="saveEditSession" class="space-y-4">
                        <!-- Date Editing -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Date</label>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        :class="
                                            cn(
                                                'h-12 min-h-[48px] w-full touch-manipulation justify-start px-4 py-3 text-left font-normal',
                                                !editingSessionDate && 'text-muted-foreground',
                                            )
                                        "
                                    >
                                        <CalendarIcon class="mr-2 h-5 w-5 flex-shrink-0" />
                                        {{ editingSessionDate ? df.format(editingSessionDate.toDate(getLocalTimeZone())) : 'Pick a date' }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="z-[70] w-auto p-0">
                                    <Calendar v-model="editingSessionDate" initial-focus />
                                </PopoverContent>
                            </Popover>
                        </div>

                        <!-- Sets Editing -->
                        <div class="space-y-4">
                            <div v-for="(set, index) in editingSession.sets" :key="index" class="flex items-end gap-3">
                                <span class="w-8 pb-3 text-sm font-medium">{{ index + 1 }}.</span>

                                <!-- Weight input (only for weight exercises) -->
                                <div v-if="selectedExercise?.type === 'weight'" class="flex-1">
                                    <label class="mb-1 block text-xs text-gray-600">Weight (kg)</label>
                                    <input
                                        v-model.number="set.weight"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>

                                <!-- Reps input -->
                                <div class="flex-1">
                                    <label class="mb-1 block text-xs text-gray-600">Reps</label>
                                    <input
                                        v-model.number="set.reps"
                                        type="number"
                                        min="1"
                                        class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>

                                <!-- Remove set button -->
                                <button
                                    v-if="editingSession.sets.length > 1"
                                    type="button"
                                    @click="removeSetFromEdit(index)"
                                    class="flex h-11 min-h-[44px] w-11 min-w-[44px] items-center justify-center rounded-md border border-red-200 text-red-600 transition-colors hover:border-red-300 hover:text-red-800"
                                >
                                    ×
                                </button>
                            </div>

                            <!-- Add Set button -->
                            <button
                                type="button"
                                @click="addSetToEdit"
                                class="h-11 min-h-[44px] w-full rounded-md border border-dashed border-gray-300 px-4 py-2 text-gray-600 transition-colors hover:border-gray-400 hover:text-gray-700"
                            >
                                + Add Set
                            </button>
                        </div>

                        <!-- Session Notes -->
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700">Notes (optional)</label>
                            <textarea
                                v-model="editingSession.notes"
                                rows="2"
                                class="w-full resize-none rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                placeholder="Add notes about this session..."
                            />
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                @click="cancelEditSession"
                                class="h-11 min-h-[44px] flex-1 rounded-md border border-gray-300 px-4 py-2 transition-colors hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editingSession.sets.every((s) => !s.reps || s.reps === 0)"
                                class="h-11 min-h-[44px] flex-1 rounded-md bg-green-600 px-4 py-2 text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!exercises || exercises.length === 0" class="py-12 text-center">
                <h3 class="mb-2 text-lg font-semibold">Ready to Track Your Workouts?</h3>
                <p class="mb-6 text-muted-foreground">Start by adding your first exercise.</p>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button class="h-12 min-h-[44px]"> Add Your First Exercise </Button>
                    </DialogTrigger>
                    <DialogContent class="max-h-[90dvh] grid-rows-[auto_minmax(0,1fr)_auto] p-0 sm:max-w-[425px]">
                        <DialogHeader class="p-6 pb-0">
                            <DialogTitle>Add New Exercise</DialogTitle>
                            <DialogDescription> Create your first exercise to start tracking workouts. </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 overflow-y-auto px-6 py-4">
                            <form @submit.prevent="onAddExercise" class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Name</label>
                                    <input
                                        v-model="exerciseForm.name"
                                        type="text"
                                        class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                        placeholder="e.g. Push-ups, Squats, Deadlift"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Exercise Type</label>
                                    <select
                                        v-model="exerciseForm.type"
                                        class="h-11 min-h-[44px] w-full rounded-md border px-3 py-2 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="weight">Weight</option>
                                        <option value="bodyweight">Bodyweight</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <DialogFooter class="p-6 pt-0">
                            <DialogClose as-child>
                                <Button type="button" variant="outline" @click="exerciseForm = { name: '', type: 'weight' }"> Cancel </Button>
                            </DialogClose>
                            <DialogClose as-child>
                                <Button type="button" @click="onAddExercise" :disabled="!exerciseForm.name.trim()"> Add Exercise </Button>
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <!-- Delete Session Confirmation Dialog -->
        <AlertDialog v-model:open="showDeleteDialog">
            <AlertDialogContent class="z-[60]">
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Workout Session?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete this workout session and all its data.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="cancelDeleteSession">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDeleteSession" class="bg-red-600 hover:bg-red-700"> Delete Session </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Delete Exercise Confirmation Dialog -->
        <AlertDialog v-model:open="showDeleteExerciseDialog">
            <AlertDialogContent class="z-[60]">
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Exercise?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete this exercise and ALL associated workout sessions.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="cancelDeleteExercise">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDeleteExercise" class="bg-red-600 hover:bg-red-700"> Delete Exercise </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
