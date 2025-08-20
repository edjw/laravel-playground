# Research: Workout Tracker Tool

## 1. Requirements Summary

**Core Functionality**: Mobile-first personal fitness tracking tool allowing users to create custom exercises, record workout sessions with flexible sets/reps/weights, and view historical performance data with maximum efficiency during workouts.

**Key User Interactions:**
- Exercise selection via searchable combobox showing recent exercises first
- Quick exercise creation with name and type (weight/bodyweight)
- Set-by-set recording using number fields with increment/decrement
- Historical data viewing with pagination
- Auto-save functionality with 500ms debouncing

**Integration Requirements:**
- Laravel playground tool integration using existing patterns
- Vue.js 3 with Composition API and TypeScript
- shadcn-vue component library usage
- User authentication and data scoping

**Success Criteria:**
- <30 seconds per exercise recording
- <3 taps to start recording
- <2 second historical data loading
- Mobile-responsive design for 320px+ screens

## 2. Codebase Analysis

### Similar Existing Features
**Playground Tool Pattern**: 
- Uses `PlaygroundController` with `show()`, `update()` methods
- Leverages `UserToolData` model for JSON data storage with user scoping
- Vue components in `resources/js/pages/Playground/Tools/`
- Auto-generated with `php artisan make:playground-tool`

**Component Patterns**:
- Vue 3 Composition API with `<script setup>` syntax
- TypeScript interfaces in `/types/index.d.ts`
- shadcn-vue components already integrated (`components.json` configured)
- Inertia.js for routing with Wayfinder type generation

**API Patterns**:
- RESTful endpoints following Laravel resource conventions
- JSON responses with proper validation using Form Requests
- User scoping on all data access via existing auth system

**Styling Approaches**:
- Tailwind CSS v4 with mobile-first approach
- shadcn-vue component library for consistent UI
- 44px minimum touch targets for accessibility

**Database Schema**:
- SQLite locally, PostgreSQL in production
- JSON data storage in `user_tool_data.saved_data` column
- Proper foreign key relationships and user scoping

## 3. External Research

### shadcn-vue Components
**Confirmed Available Components**:
- **Combobox**: For exercise selection with search functionality
- **NumberField**: Weight, reps, sets inputs with increment/decrement buttons
- **Table**: Historical data display with built-in pagination support
- **Dialog**: Add new exercise modal
- **Card**: Main layout structure
- **Button**: All interactive elements
- **Textarea**: Notes input

**Data Table Integration**:
- `@tanstack/vue-table` for advanced table functionality
- Built-in pagination, sorting, and filtering capabilities
- Mobile-responsive table design patterns

### VueUse Composables
**Debounced Auto-Save**:
- `watchDebounced()` for monitoring form changes with 500ms debounce
- `refDebounced()` for creating debounced reactive references
- `useDebounceFn()` for custom debounced functions

**Implementation Pattern**:
```typescript
import { watchDebounced } from '@vueuse/core'

watchDebounced(
  workoutData,
  async (newData) => {
    await router.put('/playground/tools/workout-tracker', {
      saved_data: newData
    })
  },
  { debounce: 500, maxWait: 2000 }
)
```

### Tailwind CSS v4 Mobile-First
- Mobile-first breakpoint system (`sm:`, `md:`, `lg:`)
- Container queries for component-based responsive design
- Touch-friendly utilities and spacing patterns
- Dark mode support with `dark:` variants

## 4. Implementation Blueprint

### Data Models and Interfaces
```typescript
interface Exercise {
  id: string
  name: string
  type: 'weight' | 'bodyweight'
  notes?: string
  created_at: string
}

interface WorkoutSet {
  weight?: number // kg, optional for bodyweight
  reps: number
}

interface WorkoutSession {
  id: string
  exercise_id: string
  date: string
  sets: WorkoutSet[]
  notes?: string
  created_at: string
}

interface WorkoutData {
  exercises: Exercise[]
  sessions: WorkoutSession[]
}
```

### Component Hierarchy
```
WorkoutTracker.vue (Main Component)
├── ExerciseSelector.vue (Combobox with search)
├── WorkoutRecorder.vue (Active session recording)
│   ├── SetInput.vue (NumberField components)
│   └── SessionNotes.vue (Collapsible textarea)
├── WorkoutHistory.vue (Table with pagination)
└── ExerciseManager.vue (Dialog for adding exercises)
```

### Database Design
**Utilising Existing Pattern**:
- Leverage `user_tool_data.saved_data` JSON column
- No additional migrations required
- User scoping via existing foreign key relationships

**JSON Structure**:
```json
{
  "exercises": [
    {
      "id": "uuid",
      "name": "Bench Press",
      "type": "weight",
      "notes": "Focus on form...",
      "created_at": "2025-01-20T10:00:00Z"
    }
  ],
  "sessions": [
    {
      "id": "uuid",
      "exercise_id": "uuid", 
      "date": "2025-01-20",
      "sets": [
        {"weight": 80, "reps": 10}
      ],
      "notes": "Felt strong today",
      "created_at": "2025-01-20T10:30:00Z"
    }
  ]
}
```

### File Structure Plan
```
resources/js/pages/Playground/Tools/
└── WorkoutTracker.vue

resources/js/components/workout/
├── ExerciseSelector.vue
├── WorkoutRecorder.vue  
├── WorkoutHistory.vue
├── SetInput.vue
├── SessionNotes.vue
└── ExerciseManager.vue

resources/js/composables/
└── useWorkoutData.ts

resources/js/types/
└── workout.ts
```

### Key Algorithms (Pseudocode)

**Exercise Prioritisation**:
```typescript
const sortedExercises = computed(() => {
  return exercises.value.sort((a, b) => {
    const aLastUsed = getLastUsedDate(a.id)
    const bLastUsed = getLastUsedDate(b.id)
    
    // Recent exercises first, then alphabetical
    if (aLastUsed && bLastUsed) {
      return bLastUsed.getTime() - aLastUsed.getTime()
    }
    if (aLastUsed) return -1
    if (bLastUsed) return 1
    return a.name.localeCompare(b.name)
  })
})
```

**Smart Defaults from Previous Session**:
```typescript
const getDefaultsForExercise = (exerciseId: string) => {
  const lastSession = sessions.value
    .filter(s => s.exercise_id === exerciseId)
    .sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime())[0]
    
  return lastSession?.sets[0] || { weight: null, reps: 1 }
}
```

## 5. Technology Stack Considerations

### Vue.js 3 Integration
- **Composition API** with `<script setup>` syntax for cleaner code
- **TypeScript** for compile-time error catching and better DX
- **Reactive state management** with computed properties and watchers
- **Inertia.js** for seamless server-client communication

### shadcn-vue Implementation
- **Combobox**: Exercise selection with built-in search functionality
- **NumberField**: Touch-friendly increment/decrement for weights and reps
- **Table**: Historical data with built-in sorting and pagination
- **Mobile-optimised components** with proper touch targets

### Laravel Backend
- **Existing playground tool patterns** for rapid development
- **Form Request validation** for data integrity
- **JSON storage** in existing user_tool_data structure
- **Auto-save endpoint** using existing update method

### Performance Optimisations
- **Debounced auto-save** (500ms) to reduce API calls
- **Lazy loading** for workout history table
- **Mobile-first CSS** for optimal loading on devices
- **Component-level optimisations** with proper Vue reactivity

## 6. Validation Strategy

### Unit Testing (Pest)
```php
it('can create workout exercises', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->put('/playground/tools/workout-tracker', [
        'saved_data' => [
            'exercises' => [
                ['name' => 'Bench Press', 'type' => 'weight', 'notes' => '']
            ]
        ]
    ]);
    
    $response->assertSuccessful();
});

it('validates exercise data properly', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->put('/playground/tools/workout-tracker', [
        'saved_data' => [
            'exercises' => [
                ['name' => '', 'type' => 'invalid']
            ]
        ]
    ]);
    
    $response->assertStatus(422);
});
```

### Component Testing (Vitest)
- **Exercise selector** prioritisation logic
- **Auto-save** debouncing behaviour  
- **Form validation** and error handling
- **Mobile responsive** component rendering

### E2E Testing (Playwright)
- **Complete mobile workflow** from exercise selection to recording
- **Auto-save functionality** during actual usage
- **Cross-device compatibility** testing
- **Touch interaction** validation

### TypeScript Validation
- **Compile-time checking** for data structure consistency
- **Interface definitions** for all workout-related types
- **Strict type checking** for component props and events

## 7. Known Gotchas & Anti-Patterns

### shadcn-vue Specific
- **NumberField mobile touch targets**: May need custom styling for 44px minimum
- **Combobox keyboard handling**: Different behaviour on mobile vs desktop
- **Table performance**: Large datasets may require virtual scrolling
- **Dialog mobile viewport**: Ensure proper handling when keyboard appears

### Vue 3 & Composition API
- **Deep reactive watching**: May trigger auto-save too frequently
- **Component lifecycle**: Handle auto-save during unmounting gracefully
- **Memory leaks**: Proper cleanup of watchers and composables

### Mobile Development
- **Touch target sizes**: Must maintain 44px minimum for accessibility
- **Viewport management**: Handle keyboard appearance/disappearance
- **Auto-save timing**: Balance between data safety and API load
- **Network connectivity**: Handle offline scenarios gracefully

### Laravel & Database
- **JSON payload size**: Large workout histories may approach limits
- **Concurrent access**: Multiple devices editing same data
- **Migration considerations**: Future schema changes with JSON storage
- **Query performance**: Searching within JSON fields

### Performance Anti-Patterns to Avoid
- **Excessive API calls**: Proper debouncing is critical
- **Large DOM rendering**: Implement virtual scrolling for history
- **Memory leaks**: Clean up reactive subscriptions
- **Unnecessary re-renders**: Use computed properties effectively

## 8. Dependencies & Libraries

### Required Packages (Already Available)
- **@inertiajs/vue3** (2.1.0) - Server-client communication
- **vue** (3.5.18) - Core framework
- **@vueuse/core** (implied from VueUse research) - Debounced composables
- **@tanstack/vue-table** (recommended for advanced table features)
- **tailwindcss** (4.1.1) - Styling framework

### shadcn-vue Components to Install
```bash
pnpm dlx shadcn-vue@latest add combobox
pnpm dlx shadcn-vue@latest add number-field  
pnpm dlx shadcn-vue@latest add table
pnpm dlx shadcn-vue@latest add dialog
pnpm dlx shadcn-vue@latest add textarea
```

### Performance Considerations
- **Bundle size impact**: shadcn-vue components are tree-shakeable
- **Mobile loading**: Critical CSS inlined, rest loaded progressively
- **API efficiency**: Debounced auto-save reduces server load

### Maintenance Strategy
- **Well-maintained libraries**: All dependencies actively maintained
- **Community support**: Large ecosystems for Vue 3 and Laravel
- **Security updates**: Regular dependency updates via automated tools
- **Documentation**: Comprehensive docs available for all major dependencies

## 9. Risk Assessment

### Technical Complexity: **6/10**
- **Moderate complexity** due to mobile-first requirements and real-time auto-save
- Standard Vue 3 patterns with established component library
- JSON data structure simpler than relational database design

### Integration Risk: **Low**
- **Existing playground tool pattern** provides clear integration path
- **UserToolData system** already handles JSON storage and user scoping
- **shadcn-vue components** already integrated in project

### Performance Risk: **Medium**
- **Mobile device considerations** for auto-save frequency
- **Large workout history** could impact table rendering performance
- **Debounced API calls** help mitigate server load concerns

### Timeline Confidence: **8/10**
- **Clear requirements** with defined acceptance criteria
- **Established patterns** to follow in existing codebase  
- **Well-documented component library** (shadcn-vue)

### Overall Confidence: **8/10**

## 10. Implementation Phases

### Phase 1: Core Functionality (Week 1)
1. **Create tool structure** using `php artisan make:playground-tool`
2. **Implement exercise management** (create, edit, delete)
3. **Build workout recording** with NumberField components
4. **Set up auto-save** with VueUse debouncing

**Quality Gates**: 
- Can create exercises and record basic workouts
- Auto-save works reliably with 500ms debounce
- Mobile touch targets are properly sized

### Phase 2: Polish & History (Week 2)
1. **Add workout history table** with pagination
2. **Implement exercise search** and recent sorting
3. **Add notes functionality** (exercise + session level)
4. **Mobile responsiveness optimisation**

**Quality Gates**:
- Full mobile workout flow works smoothly
- History table loads quickly with pagination
- Search prioritises recent exercises correctly

### Phase 3: Testing & Optimisation (Week 3)
1. **Comprehensive Pest feature tests**
2. **E2E mobile testing** with Playwright
3. **Performance optimisation** for large datasets
4. **Error handling** and offline scenarios

**Quality Gates**:
- All tests pass with >90% coverage
- Mobile performance meets 2-second load time requirement
- Graceful handling of network interruptions

## 11. Development Environment

### Required Tools and Setup
- **Laravel 12** with existing playground tool infrastructure
- **Vue 3 + TypeScript** development environment
- **shadcn-vue CLI** for component installation
- **Playwright** for E2E testing on mobile devices

### Local Development Considerations
- **Laravel Herd** provides automatic HTTPS for mobile testing
- **Mobile device testing** via browser DevTools and real devices
- **Hot module replacement** for rapid Vue component development

### Debugging Approaches
- **Vue DevTools** for component inspection
- **Laravel Telescope** for API request monitoring
- **Browser developer tools** for mobile device simulation
- **Network throttling** for testing auto-save under poor connections

### Performance Monitoring
- **Lighthouse audits** for mobile performance scoring
- **Core Web Vitals** tracking during development
- **API response time** monitoring for auto-save operations

## 12. Open Questions & Assumptions

### Technical Decisions Requiring Validation
- **Auto-save frequency**: 500ms debounce confirmed in requirements, but may need adjustment based on mobile testing
- **Pagination size**: Optimal number of historical sessions per page for mobile performance
- **Offline handling**: Requirements specify network interruption handling but implementation approach needs clarification

### User Experience Clarifications
- **Exercise deletion**: How to handle when historical session data exists (soft delete vs hard delete)
- **Data export**: Not in initial scope but database design should accommodate future CSV/JSON export
- **Cross-device sync**: Last-write-wins confirmed, but conflict detection UX needs definition

### Future Enhancement Considerations
- **Exercise photos**: Database design allows for future image attachment fields
- **Analytics**: JSON structure supports future addition of performance trend calculations
- **PWA capabilities**: Explicitly out of scope but architecture should not preclude future implementation

### Performance and Scale Assumptions
- **Maximum exercises per user**: Estimated 50-100 custom exercises based on personal fitness use case
- **Workout sessions per month**: Estimated 20-30 sessions for active users
- **Mobile browser priority**: Modern mobile browsers with ES2020+ support assumed

### Deployment Considerations
- **Database migration**: No additional migrations required due to JSON storage approach
- **Asset compilation**: Vite build process handles Vue component compilation automatically
- **CDN strategy**: Static assets served via existing Laravel configuration

## 13. Conclusion

This research provides comprehensive implementation context for the Workout Tracker tool. The combination of existing Laravel playground patterns, mature Vue 3 ecosystem, and mobile-optimised shadcn-vue components creates a solid foundation for rapid development.

**Key Success Factors**:
- Leveraging existing codebase patterns reduces implementation risk
- Mobile-first approach aligns with primary use case (gym usage)
- Auto-save with debouncing provides excellent user experience
- Comprehensive testing strategy ensures quality and reliability

**Next Steps**:
1. Begin Phase 1 implementation using `make:playground-tool` command
2. Install required shadcn-vue components
3. Set up mobile testing environment with real devices
4. Implement core exercise management and recording functionality

The research indicates high confidence (8/10) in successful implementation within the planned timeline, with moderate complexity that aligns well with the team's existing technical capabilities.