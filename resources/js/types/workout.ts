export interface Exercise {
    id: string;
    name: string;
    type: 'weight' | 'bodyweight';
    notes?: string;
    created_at: string;
    updated_at?: string;
}

export interface WorkoutSet {
    weight?: number; // kg, optional for bodyweight exercises
    reps: number;
}

export interface WorkoutSession {
    id: string;
    exercise_id: string;
    date: string; // ISO date string
    sets: WorkoutSet[];
    notes?: string;
    created_at: string;
    updated_at?: string;
}

export interface WorkoutData {
    exercises: Exercise[];
    sessions: WorkoutSession[];
}

// Helper types for forms and components
export interface ExerciseFormData {
    name: string;
    type: 'weight' | 'bodyweight';
    notes?: string;
}

export interface SessionFormData {
    exercise_id: string;
    sets: WorkoutSet[];
    notes?: string;
}

// Type for the auto-save payload
export interface WorkoutToolData {
    saved_data: WorkoutData;
}

// Type for exercise selection in combobox
export interface ExerciseOption {
    value: string;
    label: string;
    type: 'weight' | 'bodyweight';
    lastUsed?: string;
}

// Type for historical data with pagination
export interface WorkoutHistoryPage {
    sessions: WorkoutSession[];
    hasMore: boolean;
    total: number;
}
