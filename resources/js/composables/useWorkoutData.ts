import type { Exercise, ExerciseOption, WorkoutData, WorkoutHistoryPage, WorkoutSession } from '@/types/workout';
import { router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';

export function useWorkoutData(initialData: WorkoutData = { exercises: [], sessions: [] }) {
    // Reactive state - ensure exercises and sessions are always arrays
    const workoutData = ref<WorkoutData>({
        exercises: initialData?.exercises || [],
        sessions: initialData?.sessions || [],
    });
    const isLoading = ref(false);
    const isSaving = ref(false);
    const lastSaveTime = ref<Date | null>(null);
    const saveError = ref<string | null>(null);

    // Computed properties
    const exercises = computed(() => workoutData.value.exercises);
    const sessions = computed(() => workoutData.value.sessions);

    // Get exercises sorted by recent usage
    const sortedExercises = computed((): ExerciseOption[] => {
        if (!exercises.value || !Array.isArray(exercises.value)) {
            return [];
        }

        return exercises.value
            .map((exercise) => {
                const lastSession = getLastSessionForExercise(exercise.id);
                return {
                    value: exercise.id,
                    label: exercise.name,
                    type: exercise.type,
                    lastUsed: lastSession?.date,
                };
            })
            .sort((a, b) => {
                // Recent exercises first, then alphabetical
                if (a.lastUsed && b.lastUsed) {
                    return new Date(b.lastUsed).getTime() - new Date(a.lastUsed).getTime();
                }
                if (a.lastUsed) return -1;
                if (b.lastUsed) return 1;
                return a.label.localeCompare(b.label);
            });
    });

    // Auto-save with debouncing (500ms) - only save if data has actually changed from initial
    const hasDataChanged = ref(false);

    watchDebounced(
        workoutData,
        async (newData) => {
            if (hasDataChanged.value) {
                await saveData(newData);
            }
        },
        {
            debounce: 500,
            maxWait: 2000,
            deep: true,
        },
    );

    // Save function
    async function saveData(data: WorkoutData) {
        try {
            isSaving.value = true;
            saveError.value = null;

            await router.put(
                '/playground/tools/workout-tracker',
                {
                    saved_data: data as any,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        lastSaveTime.value = new Date();
                    },
                    onError: (errors) => {
                        console.error('Auto-save failed:', errors);
                        saveError.value = 'Failed to save workout data';
                    },
                },
            );
        } catch (error) {
            console.error('Auto-save error:', error);
            saveError.value = 'Network error while saving';
        } finally {
            isSaving.value = false;
        }
    }

    // Exercise management functions
    function addExercise(exercise: Omit<Exercise, 'id' | 'created_at'>) {
        const newExercise: Exercise = {
            id: generateId(),
            created_at: new Date().toISOString(),
            ...exercise,
        };

        // Ensure exercises array exists and is an array
        if (!workoutData.value.exercises || !Array.isArray(workoutData.value.exercises)) {
            workoutData.value.exercises = [];
        }

        workoutData.value.exercises.push(newExercise);
        hasDataChanged.value = true;
        return newExercise;
    }

    function updateExercise(id: string, updates: Partial<Exercise>) {
        if (!workoutData.value.exercises || !Array.isArray(workoutData.value.exercises)) {
            return;
        }

        const index = workoutData.value.exercises.findIndex((e) => e.id === id);
        if (index !== -1) {
            workoutData.value.exercises[index] = {
                ...workoutData.value.exercises[index],
                ...updates,
                updated_at: new Date().toISOString(),
            };
            hasDataChanged.value = true;
        }
    }

    function deleteExercise(id: string) {
        // Remove exercise
        if (workoutData.value.exercises && Array.isArray(workoutData.value.exercises)) {
            workoutData.value.exercises = workoutData.value.exercises.filter((e) => e.id !== id);
            hasDataChanged.value = true;
        }
        // Remove associated sessions
        if (workoutData.value.sessions && Array.isArray(workoutData.value.sessions)) {
            workoutData.value.sessions = workoutData.value.sessions.filter((s) => s.exercise_id !== id);
            hasDataChanged.value = true;
        }
    }

    function getExerciseById(id: string): Exercise | undefined {
        if (!workoutData.value.exercises || !Array.isArray(workoutData.value.exercises)) {
            return undefined;
        }

        return workoutData.value.exercises.find((e) => e.id === id);
    }

    // Session management functions
    function addSession(session: Omit<WorkoutSession, 'id' | 'created_at'>) {
        const newSession: WorkoutSession = {
            id: generateId(),
            created_at: new Date().toISOString(),
            ...session,
            date: session.date || new Date().toISOString().split('T')[0], // Default to today
        };

        // Ensure sessions array exists and is an array
        if (!workoutData.value.sessions || !Array.isArray(workoutData.value.sessions)) {
            workoutData.value.sessions = [];
        }

        workoutData.value.sessions.push(newSession);
        hasDataChanged.value = true;
        return newSession;
    }

    function updateSession(id: string, updates: Partial<WorkoutSession>) {
        if (!workoutData.value.sessions || !Array.isArray(workoutData.value.sessions)) {
            return;
        }

        const index = workoutData.value.sessions.findIndex((s) => s.id === id);
        if (index !== -1) {
            workoutData.value.sessions[index] = {
                ...workoutData.value.sessions[index],
                ...updates,
                updated_at: new Date().toISOString(),
            };
            hasDataChanged.value = true;
        }
    }

    function deleteSession(id: string) {
        if (workoutData.value.sessions && Array.isArray(workoutData.value.sessions)) {
            workoutData.value.sessions = workoutData.value.sessions.filter((s) => s.id !== id);
            hasDataChanged.value = true;
        }
    }

    function getSessionsForExercise(exerciseId: string): WorkoutSession[] {
        if (!workoutData.value.sessions || !Array.isArray(workoutData.value.sessions)) {
            return [];
        }

        return workoutData.value.sessions
            .filter((s) => s.exercise_id === exerciseId)
            .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
    }

    function getLastSessionForExercise(exerciseId: string): WorkoutSession | undefined {
        return getSessionsForExercise(exerciseId)[0];
    }

    function getHistoryForExercise(exerciseId: string, page = 1, limit = 10): WorkoutHistoryPage {
        const allSessions = getSessionsForExercise(exerciseId);
        const startIndex = (page - 1) * limit;
        const endIndex = startIndex + limit;

        return {
            sessions: allSessions.slice(startIndex, endIndex),
            hasMore: endIndex < allSessions.length,
            total: allSessions.length,
        };
    }

    // Get smart defaults from previous session
    function getDefaultsForExercise(exerciseId: string) {
        const lastSession = getLastSessionForExercise(exerciseId);
        const exercise = getExerciseById(exerciseId);

        if (lastSession && lastSession.sets.length > 0) {
            const lastSet = lastSession.sets[0];
            return {
                weight: exercise?.type === 'weight' ? lastSet.weight || 0 : undefined,
                reps: lastSet.reps || 1,
            };
        }

        return {
            weight: exercise?.type === 'weight' ? 0 : undefined,
            reps: 1,
        };
    }

    // Utility functions
    function generateId(): string {
        return `${Date.now()}-${Math.random().toString(36).substring(2, 11)}`;
    }

    // Reset all data (for testing or clearing)
    function resetData() {
        workoutData.value = { exercises: [], sessions: [] };
        hasDataChanged.value = true;
    }

    // Load data (for initialisation) - don't trigger auto-save for initial load
    function loadData(data: WorkoutData) {
        workoutData.value = {
            exercises: data?.exercises || [],
            sessions: data?.sessions || [],
        };
        hasDataChanged.value = false; // Reset flag since this is initial data
        lastSaveTime.value = new Date();
    }

    return {
        // State
        workoutData,
        exercises,
        sessions,
        sortedExercises,
        isLoading,
        isSaving,
        lastSaveTime,
        saveError,

        // Exercise functions
        addExercise,
        updateExercise,
        deleteExercise,
        getExerciseById,

        // Session functions
        addSession,
        updateSession,
        deleteSession,
        getSessionsForExercise,
        getLastSessionForExercise,
        getHistoryForExercise,
        getDefaultsForExercise,

        // Utility functions
        saveData,
        resetData,
        loadData,
    };
}
