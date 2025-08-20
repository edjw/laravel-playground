# Workout Tracker Implementation Tasks

Generated from `requirements-workout-tracker.md` and `research-workout-tracker.md`

## Relevant Files

### Laravel Playground Tool Structure
- `resources/js/pages/Playground/Tools/WorkoutTracker.vue` - Main Vue component for the tool
- `resources/js/components/workout/ExerciseSelector.vue` - Combobox for exercise selection
- `resources/js/components/workout/WorkoutRecorder.vue` - Set recording interface
- `resources/js/components/workout/WorkoutHistory.vue` - Historical data table
- `resources/js/components/workout/SetInput.vue` - NumberField for weight/reps
- `resources/js/components/workout/ExerciseManager.vue` - Dialog for adding exercises

### Vue Components & Composables
- `resources/js/composables/useWorkoutData.ts` - Auto-save and data management logic
- `resources/js/types/workout.ts` - TypeScript interfaces for workout data

### Laravel Backend
- `app/Http/Controllers/PlaygroundController.php` - Existing controller (update method)
- `database/seeders/PlaygroundToolSeeder.php` - Add workout tracker tool entry
- `tests/Feature/WorkoutTrackerTest.php` - Feature tests for workout functionality

### Development & Tooling
- `tests/Feature/Playwright/WorkoutTrackerE2E.spec.js` - E2E mobile testing
- `package.json` - Add shadcn-vue components and dependencies

## Tasks

- [x] 1.0 Foundation & Tool Infrastructure Setup
  - [x] 1.1 [Confidence: 9/10] Create playground tool using `php artisan make:playground-tool "Workout Tracker" --icon=Dumbbell --description="Mobile-first personal fitness tracking with custom exercises and session recording"`
  - [x] 1.2 [Confidence: 8/10] Install required shadcn-vue components: `pnpx shadcn-vue@latest add combobox number-field table textarea badge` (Note: Used existing components + manual reka-ui components due to Tailwind v4 compatibility)
  - [x] 1.3 [Confidence: 9/10] Create TypeScript interfaces in `resources/js/types/workout.ts` for Exercise, WorkoutSet, WorkoutSession, and WorkoutData
  - [x] 1.4 [Confidence: 8/10] Set up auto-save composable `useWorkoutData.ts` with VueUse watchDebounced (500ms debounce)
  - [x] 1.5 [Confidence: 7/10] Configure mobile-first Tailwind CSS classes and ensure 44px minimum touch targets

- [ ] 2.0 Exercise Management System
  - [ ] 2.1 [Confidence: 8/10] Build ExerciseSelector.vue component using Combobox with search functionality and recent exercise prioritisation
  - [ ] 2.2 [Confidence: 8/10] Create ExerciseManager.vue dialog component for adding new exercises with name, type (weight/bodyweight), and notes
  - [ ] 2.3 [Confidence: 9/10] Implement exercise data structure in JSON format within existing UserToolData.saved_data column
  - [ ] 2.4 [Confidence: 7/10] Add exercise sorting algorithm (recent exercises first, then alphabetical) with computed properties
  - [ ] 2.5 [Confidence: 8/10] Implement exercise search/filtering functionality within the Combobox component

- [ ] 3.0 Workout Recording Interface
  - [ ] 3.1 [Confidence: 8/10] Build WorkoutRecorder.vue component with set-by-set recording interface
  - [ ] 3.2 [Confidence: 7/10] Create SetInput.vue component using NumberField with increment/decrement buttons for weight and reps
  - [ ] 3.3 [Confidence: 8/10] Handle bodyweight exercises (hide weight field when exercise type is 'bodyweight')
  - [ ] 3.4 [Confidence: 8/10] Implement session notes with collapsible textarea component
  - [ ] 3.5 [Confidence: 7/10] Add smart defaults from previous session data when selecting an exercise
  - [ ] 3.6 [Confidence: 8/10] Integrate auto-save functionality with visual feedback for save states

- [ ] 4.0 Historical Data & Mobile Optimisation
  - [ ] 4.1 [Confidence: 7/10] Build WorkoutHistory.vue component using shadcn-vue Table with pagination
  - [ ] 4.2 [Confidence: 8/10] Display historical sessions filtered by selected exercise with date, sets, weight, reps, and notes
  - [ ] 4.3 [Confidence: 9/10] Ensure mobile responsiveness for 320px+ screen widths using Tailwind CSS v4
  - [ ] 4.4 [Confidence: 8/10] Optimise touch interactions and ensure all buttons meet 44px minimum touch target requirements
  - [ ] 4.5 [Confidence: 7/10] Implement lazy loading for workout history to handle large datasets efficiently
  - [ ] 4.6 [Confidence: 6/10] Add skeleton loading states and progressive enhancement for mobile performance

- [ ] 5.0 Testing & Quality Assurance
  - [ ] 5.1 [Confidence: 8/10] Write Pest feature tests for exercise creation, editing, and data persistence
  - [ ] 5.2 [Confidence: 8/10] Create Pest tests for workout session recording and auto-save functionality
  - [ ] 5.3 [Confidence: 7/10] Implement Pest validation tests for form inputs and data integrity
  - [ ] 5.4 [Confidence: 6/10] Write Playwright E2E tests for complete mobile workout flow from exercise selection to recording
  - [ ] 5.5 [Confidence: 7/10] Test auto-save functionality under various network conditions and interruptions
  - [ ] 5.6 [Confidence: 8/10] Validate mobile performance meets <2 second load time and <30 second recording requirements
  - [ ] 5.7 [Confidence: 9/10] Run `vendor/bin/pint --dirty` for code formatting and ensure TypeScript compilation passes

## Risk Mitigation Notes

### Low Confidence Tasks (6-7/10)
- **Task 4.6**: Skeleton loading requires careful UX design - reference existing playground tool patterns
- **Task 5.4**: Mobile E2E testing needs real device validation beyond browser DevTools  
- **Task 2.4**: Exercise sorting algorithm complexity may impact performance with large datasets

### Anti-Pattern Warnings
- **Avoid excessive API calls**: Ensure 500ms debounce is properly implemented in auto-save
- **Don't use raw database queries**: Leverage existing UserToolData model and JSON storage
- **Avoid custom NumberField implementations**: Use shadcn-vue NumberField component consistently

### Performance Considerations
- **Task 3.6**: Auto-save visual feedback should not block UI interactions
- **Task 4.2**: Historical data pagination essential for users with extensive workout history
- **Task 4.5**: Lazy loading critical for mobile performance with large JSON datasets

## Standards to Follow

- Target audience: Junior developer familiar with Vue.js/Laravel/TypeScript stack
- Use British English throughout (colour, centre, realise)
- Follow existing Laravel playground tool patterns
- Use Vue 3 Composition API with `<script setup>` syntax
- Leverage shadcn-vue component library for consistency
- Implement mobile-first design with Tailwind CSS v4
- Ensure WCAG 2.1 AA accessibility compliance
- All data scoped to authenticated user via existing auth system
- Use existing `UserToolData.saved_data` JSON storage pattern
- Follow Laravel 12 file structure conventions

## Implementation Blueprint Reference

This task list implements the comprehensive research findings from `research-workout-tracker.md`, leveraging:

- **Existing playground infrastructure** for rapid development
- **Mobile-first approach** aligning with primary gym usage
- **Auto-save with debouncing** for excellent user experience
- **JSON storage strategy** avoiding additional migrations
- **shadcn-vue components** for consistent, accessible UI
- **Comprehensive testing strategy** ensuring quality and reliability

The implementation provides high confidence (8/10) for successful delivery within planned timeline, with moderate complexity that aligns well with existing technical capabilities.