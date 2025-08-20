import { ref, computed } from 'vue'
import { watchDebounced } from '@vueuse/core'
import { router } from '@inertiajs/vue3'
import type { 
  WorkoutData, 
  Exercise, 
  WorkoutSession, 
  ExerciseOption,
  WorkoutHistoryPage 
} from '@/types/workout'

export function useWorkoutData(initialData: WorkoutData = { exercises: [], sessions: [] }) {
  // Reactive state
  const workoutData = ref<WorkoutData>(initialData)
  const isLoading = ref(false)
  const isSaving = ref(false)
  const lastSaveTime = ref<Date | null>(null)
  const saveError = ref<string | null>(null)

  // Computed properties
  const exercises = computed(() => workoutData.value.exercises)
  const sessions = computed(() => workoutData.value.sessions)

  // Get exercises sorted by recent usage
  const sortedExercises = computed((): ExerciseOption[] => {
    return exercises.value
      .map(exercise => {
        const lastSession = getLastSessionForExercise(exercise.id)
        return {
          value: exercise.id,
          label: exercise.name,
          type: exercise.type,
          lastUsed: lastSession?.date
        }
      })
      .sort((a, b) => {
        // Recent exercises first, then alphabetical
        if (a.lastUsed && b.lastUsed) {
          return new Date(b.lastUsed).getTime() - new Date(a.lastUsed).getTime()
        }
        if (a.lastUsed) return -1
        if (b.lastUsed) return 1
        return a.label.localeCompare(b.label)
      })
  })

  // Auto-save with debouncing (500ms)
  watchDebounced(
    workoutData,
    async (newData) => {
      await saveData(newData)
    },
    { 
      debounce: 500, 
      maxWait: 2000,
      deep: true 
    }
  )

  // Save function
  async function saveData(data: WorkoutData) {
    try {
      isSaving.value = true
      saveError.value = null

      await router.put('/playground/tools/workout-tracker', {
        saved_data: data as any
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          lastSaveTime.value = new Date()
        },
        onError: (errors) => {
          console.error('Auto-save failed:', errors)
          saveError.value = 'Failed to save workout data'
        }
      })
    } catch (error) {
      console.error('Auto-save error:', error)
      saveError.value = 'Network error while saving'
    } finally {
      isSaving.value = false
    }
  }

  // Exercise management functions
  function addExercise(exercise: Omit<Exercise, 'id' | 'created_at'>) {
    const newExercise: Exercise = {
      id: generateId(),
      created_at: new Date().toISOString(),
      ...exercise
    }
    workoutData.value.exercises.push(newExercise)
    return newExercise
  }

  function updateExercise(id: string, updates: Partial<Exercise>) {
    const index = workoutData.value.exercises.findIndex(e => e.id === id)
    if (index !== -1) {
      workoutData.value.exercises[index] = {
        ...workoutData.value.exercises[index],
        ...updates,
        updated_at: new Date().toISOString()
      }
    }
  }

  function deleteExercise(id: string) {
    // Remove exercise
    workoutData.value.exercises = workoutData.value.exercises.filter(e => e.id !== id)
    // Remove associated sessions
    workoutData.value.sessions = workoutData.value.sessions.filter(s => s.exercise_id !== id)
  }

  function getExerciseById(id: string): Exercise | undefined {
    return workoutData.value.exercises.find(e => e.id === id)
  }

  // Session management functions
  function addSession(session: Omit<WorkoutSession, 'id' | 'created_at'>) {
    const newSession: WorkoutSession = {
      id: generateId(),
      created_at: new Date().toISOString(),
      ...session,
      date: session.date || new Date().toISOString().split('T')[0] // Default to today
    }
    workoutData.value.sessions.push(newSession)
    return newSession
  }

  function updateSession(id: string, updates: Partial<WorkoutSession>) {
    const index = workoutData.value.sessions.findIndex(s => s.id === id)
    if (index !== -1) {
      workoutData.value.sessions[index] = {
        ...workoutData.value.sessions[index],
        ...updates,
        updated_at: new Date().toISOString()
      }
    }
  }

  function deleteSession(id: string) {
    workoutData.value.sessions = workoutData.value.sessions.filter(s => s.id !== id)
  }

  function getSessionsForExercise(exerciseId: string): WorkoutSession[] {
    return workoutData.value.sessions
      .filter(s => s.exercise_id === exerciseId)
      .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())
  }

  function getLastSessionForExercise(exerciseId: string): WorkoutSession | undefined {
    return getSessionsForExercise(exerciseId)[0]
  }

  function getHistoryForExercise(exerciseId: string, page = 1, limit = 10): WorkoutHistoryPage {
    const allSessions = getSessionsForExercise(exerciseId)
    const startIndex = (page - 1) * limit
    const endIndex = startIndex + limit
    
    return {
      sessions: allSessions.slice(startIndex, endIndex),
      hasMore: endIndex < allSessions.length,
      total: allSessions.length
    }
  }

  // Get smart defaults from previous session
  function getDefaultsForExercise(exerciseId: string) {
    const lastSession = getLastSessionForExercise(exerciseId)
    const exercise = getExerciseById(exerciseId)
    
    if (lastSession && lastSession.sets.length > 0) {
      const lastSet = lastSession.sets[0]
      return {
        weight: exercise?.type === 'weight' ? lastSet.weight || 0 : undefined,
        reps: lastSet.reps || 1
      }
    }
    
    return {
      weight: exercise?.type === 'weight' ? 0 : undefined,
      reps: 1
    }
  }

  // Utility functions
  function generateId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  }

  // Reset all data (for testing or clearing)
  function resetData() {
    workoutData.value = { exercises: [], sessions: [] }
  }

  // Load data (for initialisation)
  function loadData(data: WorkoutData) {
    workoutData.value = data
    lastSaveTime.value = new Date()
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
    loadData
  }
}