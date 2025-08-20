<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import type { PlaygroundTool, BreadcrumbItem } from '@/types'
import type { WorkoutData } from '@/types/workout'
import { useWorkoutData } from '@/composables/useWorkoutData'

interface Props {
    tool: PlaygroundTool
    savedData?: WorkoutData
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Playground', href: '/playground' },
    { title: props.tool.name, href: `/playground/tools/${props.tool.slug}` },
]

// Initialize workout data composable
const initialData: WorkoutData = props.savedData || { exercises: [], sessions: [] }
const {
    exercises,
    sortedExercises,
    isSaving,
    saveError,
    addExercise,
    addSession,
    getDefaultsForExercise,
    getHistoryForExercise,
    loadData
} = useWorkoutData(initialData)

// Current UI state
const selectedExerciseId = ref<string | null>(null)
const currentSession = ref({
    sets: [{ weight: undefined as number | undefined, reps: 1 }],
    notes: ''
})
const showExerciseManager = ref(false)
const showHistory = ref(false)

// Exercise form for adding new exercises
const exerciseForm = ref({
    name: '',
    type: 'weight' as 'weight' | 'bodyweight',
    notes: ''
})

// Load data on mount
onMounted(() => {
    if (props.savedData) {
        loadData(props.savedData)
    }
})

// Exercise selection handler
const onExerciseSelected = (exerciseId: string) => {
    selectedExerciseId.value = exerciseId
    const defaults = getDefaultsForExercise(exerciseId)
    currentSession.value = {
        sets: [{
            weight: defaults.weight,
            reps: defaults.reps
        }],
        notes: ''
    }
    showHistory.value = false
}

// Add new exercise
const onAddExercise = () => {
    if (!exerciseForm.value.name.trim()) return
    
    const newExercise = addExercise({
        name: exerciseForm.value.name.trim(),
        type: exerciseForm.value.type,
        notes: exerciseForm.value.notes.trim() || undefined
    })
    
    // Select the new exercise
    selectedExerciseId.value = newExercise.id
    onExerciseSelected(newExercise.id)
    
    // Reset form and close dialog
    exerciseForm.value = { name: '', type: 'weight', notes: '' }
    showExerciseManager.value = false
}

// Add set to current session
const addSet = () => {
    const lastSet = currentSession.value.sets[currentSession.value.sets.length - 1]
    currentSession.value.sets.push({
        weight: lastSet?.weight,
        reps: lastSet?.reps || 1
    })
}

// Remove set from current session
const removeSet = (index: number) => {
    if (currentSession.value.sets.length > 1) {
        currentSession.value.sets.splice(index, 1)
    }
}

// Save current session
const saveSession = () => {
    if (!selectedExerciseId.value || currentSession.value.sets.length === 0) return
    
    addSession({
        exercise_id: selectedExerciseId.value,
        sets: currentSession.value.sets.filter(set => set.reps > 0),
        notes: currentSession.value.notes.trim() || undefined,
        date: new Date().toISOString().split('T')[0]
    })
    
    // Reset current session with smart defaults
    const defaults = getDefaultsForExercise(selectedExerciseId.value)
    currentSession.value = {
        sets: [{
            weight: defaults.weight,
            reps: defaults.reps
        }],
        notes: ''
    }
}

// Get selected exercise
import type { Exercise } from '@/types/workout'
const selectedExercise = ref<Exercise | null>(null)
const updateSelectedExercise = () => {
    if (selectedExerciseId.value) {
        selectedExercise.value = exercises.value.find(e => e.id === selectedExerciseId.value) || null
    } else {
        selectedExercise.value = null
    }
}

// Watch for changes in selected exercise
import { watch } from 'vue'
watch(selectedExerciseId, updateSelectedExercise, { immediate: true })
watch(exercises, updateSelectedExercise)

// Helper function for display
const getLastSession = () => {
    if (!selectedExerciseId.value) return 'None'
    const lastSession = getHistoryForExercise(selectedExerciseId.value, 1, 1).sessions[0]
    if (!lastSession) return 'No previous sessions'
    
    const date = new Date(lastSession.date).toLocaleDateString()
    const sets = lastSession.sets.length
    const exercise = selectedExercise.value
    
    if (exercise?.type === 'weight' && lastSession.sets[0]?.weight) {
        return `${date} - ${sets} sets at ${lastSession.sets[0].weight}kg`
    }
    return `${date} - ${sets} sets`
}
</script>

<template>
    <Head :title="tool.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Mobile-first container with proper touch targets -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 space-y-6">
            <!-- Tool Header -->
            <div class="text-center space-y-2">
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">{{ tool.name }}</h1>
                <p v-if="tool.description" class="text-muted-foreground text-sm sm:text-base">{{ tool.description }}</p>
            </div>

            <!-- Auto-save status indicator -->
            <div v-if="isSaving || saveError" class="fixed top-4 right-4 z-50">
                <div v-if="isSaving" class="bg-blue-100 text-blue-800 px-3 py-2 rounded-lg shadow-sm text-sm">
                    Saving...
                </div>
                <div v-else-if="saveError" class="bg-red-100 text-red-800 px-3 py-2 rounded-lg shadow-sm text-sm">
                    {{ saveError }}
                </div>
            </div>

            <!-- Exercise Selection -->
            <div class="bg-white border rounded-lg p-4 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                    <h2 class="text-lg font-semibold">Select Exercise</h2>
                    <button
                        @click="showExerciseManager = true"
                        class="w-full sm:w-auto h-11 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium min-h-[44px]"
                    >
                        + Add Exercise
                    </button>
                </div>

                <!-- Exercise Selector (Simplified for now) -->
                <div class="space-y-3">
                    <select
                        v-model="selectedExerciseId"
                        @change="selectedExerciseId && onExerciseSelected(selectedExerciseId)"
                        class="w-full h-11 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden min-h-[44px]"
                    >
                        <option value="">Choose an exercise...</option>
                        <option 
                            v-for="exercise in sortedExercises" 
                            :key="exercise.value" 
                            :value="exercise.value"
                        >
                            {{ exercise.label }} {{ exercise.type === 'bodyweight' ? '(Bodyweight)' : '' }}
                        </option>
                    </select>

                    <!-- Previous session info -->
                    <div v-if="selectedExercise" class="text-sm text-muted-foreground">
                        Last session: {{ getLastSession() }}
                    </div>
                </div>
            </div>

            <!-- Workout Recording -->
            <div v-if="selectedExercise" class="bg-white border rounded-lg p-4 sm:p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">{{ selectedExercise.name }}</h2>
                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                        {{ selectedExercise.type }}
                    </span>
                </div>

                <!-- Sets Recording -->
                <div class="space-y-4">
                    <div v-for="(set, index) in currentSession.sets" :key="index" class="flex items-center gap-3">
                        <span class="text-sm font-medium w-8">{{ index + 1 }}.</span>
                        
                        <!-- Weight input (only for weight exercises) -->
                        <div v-if="selectedExercise.type === 'weight'" class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">Weight (kg)</label>
                            <input
                                v-model.number="set.weight"
                                type="number"
                                step="0.5"
                                min="0"
                                class="w-full h-11 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden min-h-[44px]"
                                placeholder="0"
                            />
                        </div>

                        <!-- Reps input -->
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">Reps</label>
                            <input
                                v-model.number="set.reps"
                                type="number"
                                min="1"
                                class="w-full h-11 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden min-h-[44px]"
                                placeholder="1"
                            />
                        </div>

                        <!-- Remove set button -->
                        <button
                            v-if="currentSession.sets.length > 1"
                            @click="removeSet(index)"
                            class="h-11 w-11 text-red-600 hover:text-red-800 border border-red-200 hover:border-red-300 rounded-md transition-colors min-h-[44px] min-w-[44px]"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Add Set button -->
                    <button
                        @click="addSet"
                        class="w-full h-11 px-4 py-2 border border-dashed border-gray-300 text-gray-600 rounded-md hover:border-gray-400 hover:text-gray-700 transition-colors min-h-[44px]"
                    >
                        + Add Set
                    </button>
                </div>

                <!-- Session Notes -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (optional)</label>
                    <textarea
                        v-model="currentSession.notes"
                        rows="2"
                        class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden resize-none"
                        placeholder="Add notes about this session..."
                    />
                </div>

                <!-- Save Session Button -->
                <div class="flex gap-3 mt-6">
                    <button
                        @click="saveSession"
                        :disabled="currentSession.sets.length === 0 || currentSession.sets.every(s => s.reps === 0)"
                        class="flex-1 h-12 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium min-h-[44px]"
                    >
                        Save Session
                    </button>
                    <button
                        @click="showHistory = !showHistory"
                        class="h-12 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors min-h-[44px]"
                    >
                        History
                    </button>
                </div>
            </div>

            <!-- Workout History -->
            <div v-if="showHistory && selectedExercise" class="bg-white border rounded-lg p-4 sm:p-6 shadow-sm">
                <h3 class="text-lg font-semibold mb-4">{{ selectedExercise.name }} History</h3>
                
                <div class="space-y-3">
                    <div 
                        v-for="session in getHistoryForExercise(selectedExercise.id, 1, 10).sessions" 
                        :key="session.id"
                        class="p-3 border rounded-lg"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-medium">{{ new Date(session.date).toLocaleDateString() }}</span>
                            <span class="text-sm text-gray-600">{{ session.sets.length }} sets</span>
                        </div>
                        <div class="text-sm space-y-1">
                            <div v-for="(set, index) in session.sets" :key="index" class="flex justify-between">
                                <span>Set {{ index + 1 }}:</span>
                                <span>
                                    <template v-if="selectedExercise.type === 'weight'">
                                        {{ set.weight }}kg × 
                                    </template>
                                    {{ set.reps }} reps
                                </span>
                            </div>
                        </div>
                        <div v-if="session.notes" class="text-sm text-gray-600 mt-2 italic">
                            "{{ session.notes }}"
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exercise Manager Dialog -->
            <div v-if="showExerciseManager" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4">Add New Exercise</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Exercise Name</label>
                            <input
                                v-model="exerciseForm.name"
                                type="text"
                                class="w-full h-11 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden min-h-[44px]"
                                placeholder="e.g. Bench Press"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Exercise Type</label>
                            <select
                                v-model="exerciseForm.type"
                                class="w-full h-11 px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden min-h-[44px]"
                            >
                                <option value="weight">Weight Exercise</option>
                                <option value="bodyweight">Bodyweight Exercise</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                            <textarea
                                v-model="exerciseForm.notes"
                                rows="2"
                                class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden resize-none"
                                placeholder="Exercise notes..."
                            />
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button
                            @click="showExerciseManager = false"
                            class="flex-1 h-11 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors min-h-[44px]"
                        >
                            Cancel
                        </button>
                        <button
                            @click="onAddExercise"
                            :disabled="!exerciseForm.name.trim()"
                            class="flex-1 h-11 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors min-h-[44px]"
                        >
                            Add Exercise
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="exercises.length === 0" class="text-center py-12">
                <div class="text-6xl mb-4">💪</div>
                <h3 class="text-lg font-semibold mb-2">Ready to Track Your Workouts?</h3>
                <p class="text-muted-foreground mb-6">Start by adding your first exercise.</p>
                <button
                    @click="showExerciseManager = true"
                    class="h-12 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium min-h-[44px]"
                >
                    Add Your First Exercise
                </button>
            </div>
        </div>
    </AppLayout>
</template>