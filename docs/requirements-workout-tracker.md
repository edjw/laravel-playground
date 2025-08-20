# PRD: Workout Tracker Tool

## 1. Introduction/Overview

The Workout Tracker is a mobile-first playground tool for personal fitness tracking. It allows users to create custom exercise types, record workout sessions with flexible sets/reps/weights, and view historical performance data. The tool prioritises simplicity and efficiency for users who are tired and sweaty during workouts.

**Problem Statement:** Current workout tracking requires either complex apps with too many features or manual note-taking that's cumbersome during workouts. Users need a simple, fast way to record their personal workout data without pre-populated exercises they don't use.

**Goal Statement:** Provide a streamlined, mobile-optimised interface that allows users to quickly record workout data with minimal typing and maximum efficiency, while maintaining a complete history of their progress.

## 2. Goals & Success Metrics

### Primary Goals
- Enable quick workout recording during gym sessions (< 30 seconds per exercise)
- Provide clear historical context when starting new workout sessions
- Maintain 100% user data privacy (no sharing, no defaults)
- Ensure excellent mobile usability for tired users

### Success Metrics
- Users can select an exercise and start recording within 3 taps
- Historical data loads within 2 seconds
- Zero data loss with auto-save functionality
- Mobile-responsive design works perfectly on phone screens

### Timeline
- Complete implementation within current development cycle
- Ready for personal use immediately upon completion

## 3. User Stories

### Primary User Flows

**As a gym user, I want to quickly select a previous exercise so that I can see what I did last time and plan my current session.**
- Given I open the workout tracker
- When I tap the exercise selector
- Then I see my recent exercises first in a searchable list
- And I can see what weights/reps I used previously

**As a gym user, I want to add a new exercise type so that I can track exercises not in my current list.**
- Given I'm selecting an exercise
- When I tap the "+" button next to the selector
- Then I can quickly create a new exercise with a name and type (weight/bodyweight)
- And it immediately appears in my exercise list

**As a gym user, I want to record my workout sets one by one so that I can track my actual performance during the session.**
- Given I've selected an exercise
- When I add each set with weight/reps using number inputs
- Then the data is automatically saved
- And I can continue adding more sets as needed

**As a gym user, I want to see my exercise history so that I can track my progress over time.**
- Given I've selected an exercise I've done before
- When I view the history section
- Then I see a simple table of all previous sessions
- And I can see dates, weights, reps, and any notes I made

### Secondary User Stories

**As a user, I want to add notes to exercises so that I can remember important details about form or technique.**
- Notes can be added at exercise level (general notes) and session level (today's specific notes)
- Notes are hidden in collapsible sections to avoid UI clutter

**As a user, I want my data to persist automatically so that I never lose workout data due to phone issues.**
- All data auto-saves with 500ms debouncing
- No manual save actions required

## 4. Functional Requirements

### Exercise Management
1. **Must** allow users to create custom exercise types with name and type (weight/bodyweight)
2. **Must** display recent exercises first in selection interface
3. **Must** allow editing exercise names and notes
4. **Should** support exercise-level notes for technique reminders
5. **Could** support exercise favouriting/pinning for frequently used exercises

### Exercise Selection Interface
6. **Must** use Combobox component for selecting from existing exercises
7. **Must** provide separate "+" button for adding new exercises
8. **Must** show exercise type badges (weight/bodyweight) in selection list
9. **Must** support search/filtering in exercise selection
10. **Should** prioritise recently used exercises in the list

### Workout Recording
11. **Must** support recording sets individually during workout
12. **Must** use NumberField components with increment/decrement for numeric inputs
13. **Must** support weight in kg only (no lbs conversion)
14. **Must** support exercises without weights (bodyweight exercises)
15. **Must** allow flexible set recording (different weights/reps per set)
16. **Should** remember previous session data as smart defaults
17. **Should** support session-level notes
18. **Could** provide quick preset buttons for common weight increments

### Historical Data
19. **Must** display previous sessions for selected exercise type
20. **Must** show historical data in simple table format
21. **Must** include pagination for exercises with many sessions
22. **Should** show date, weight, reps, sets, and notes for each session
23. **Could** highlight personal bests or progress trends

### Data Persistence
24. **Must** auto-save all data with debounced updates (500ms)
25. **Must** scope all data to authenticated user only
26. **Must** work with SQLite locally and PostgreSQL in production
27. **Must** handle network interruptions gracefully
28. **Should** provide visual feedback for save states

## 5. Non-Goals (Out of Scope)

### Explicitly Excluded Features
- **PWA capabilities** (offline access, home screen install)
- **Pre-populated exercise database** - users create all their own exercises
- **Multiple workout sessions per day** - single session tracking only
- **Exercise categories or muscle group organisation** - flat list approach
- **Weight unit conversion** (lbs/kg) - kg only
- **Voice input** for adding sets
- **Social features** or data sharing
- **Exercise instruction videos or images**
- **Workout planning or scheduling**
- **Nutrition tracking**
- **Body weight or measurement tracking**

### Future Considerations
- Exercise analytics and progress charts (later phase)
- Export functionality (CSV/JSON) (later phase)
- Exercise templates or routines (later phase)

## 6. Technical Considerations

### Laravel Integration
- Follow existing playground tool patterns using `php artisan make:playground-tool`
- Integrate with existing `UserToolData` system for data storage
- Use existing authentication and user scoping
- Follow Laravel 12 file structure conventions

### Vue.js Implementation
- Use Vue 3 with Composition API and `<script setup>` syntax
- Leverage existing shadcn-vue component library
- Follow existing code style and patterns in the codebase
- Use TypeScript for type safety

### API Design
- RESTful endpoints following Laravel resource conventions
- JSON API responses with proper error handling
- Implement proper validation using Laravel Form Requests
- Use Laravel's built-in pagination for historical data

### Database Design
- Two main tables: `workout_types` and `workout_sessions`
- Proper foreign key relationships and constraints
- User scoping on all data access
- Optimised queries for mobile performance

## 7. Design Considerations

### Mobile-First Approach
- Touch targets minimum 44px for accessibility
- Large, easy-to-tap buttons and inputs
- Optimised for one-handed use during workouts
- Clear visual hierarchy with adequate spacing

### shadcn-vue Component Usage
- **Combobox**: Exercise selection with search functionality
- **NumberField**: Weight, reps, sets inputs with increment/decrement
- **Card**: Main layout structure
- **Table**: Historical data display
- **Dialog**: Add new exercise modal
- **Badge**: Exercise type indicators
- **Button**: All interactive elements
- **Textarea**: Notes input (collapsible)

### Responsive Design
- Mobile-first CSS with progressive enhancement
- Test on actual mobile devices, not just browser DevTools
- Ensure usability on small screens (320px width minimum)
- Use appropriate font sizes for readability during exercise

### Accessibility Requirements
- WCAG 2.1 AA compliance
- Proper semantic HTML structure
- Adequate colour contrast ratios
- Screen reader compatible
- Keyboard navigation support

## 8. Developer Experience Requirements

### Error Handling Strategy
- Graceful degradation for network issues
- Clear, user-friendly error messages
- Automatic retry for failed save operations
- Visual indicators for connection status

### Logging Requirements
- Log all API requests and responses for debugging
- Track auto-save success/failure rates
- Monitor component performance on mobile devices
- Log user interaction patterns for UX improvements

### Development Tooling
- TypeScript for compile-time error catching
- ESLint and Prettier for code consistency
- Vue DevTools compatibility
- Mobile browser debugging support

### Debugging Information
- Clear error boundaries in Vue components
- Detailed logging for data synchronisation issues
- Performance monitoring for mobile interactions
- Network request/response debugging

## 9. Dependency Strategy

### Preferred Libraries and Frameworks
- **shadcn-vue**: UI component library (already installed)
- **@tanstack/vue-table**: For historical data tables with pagination
- **VueUse**: For composable utilities like debounced auto-save
- **Zod**: Runtime validation (already in project)

### Performance Considerations
- Minimise bundle size impact on mobile loading
- Lazy load historical data tables
- Debounce auto-save to reduce API calls
- Optimise images and icons for mobile bandwidth

### Maintenance Burden
- Use well-maintained, popular libraries only
- Follow Vue.js and Laravel community best practices
- Avoid custom implementations where standard solutions exist
- Keep dependencies up to date with security patches

## 10. Observability Requirements

### Event Tracking
- Exercise creation and selection events
- Workout session completion events
- Auto-save success/failure events
- Mobile interaction patterns (taps, swipes)

### Performance Metrics
- Page load times on mobile devices
- API response times for critical operations
- Auto-save operation timing and success rates
- Component render performance

### User Analytics
- Most frequently used exercises
- Average session duration
- Feature usage patterns
- Error rates and recovery success

### Monitoring Needs
- API endpoint health monitoring
- Database query performance
- Mobile device compatibility tracking
- User session error rates

## 11. Edge Cases & Error Handling

### Network and Connectivity
- **Scenario**: User loses network during workout recording
- **Handling**: Queue operations locally, sync when connection returns
- **User Experience**: Show offline indicator, auto-retry with visual feedback

### Data Validation Errors
- **Scenario**: Invalid numeric input (negative weights, zero reps)
- **Handling**: Client-side validation with immediate feedback
- **User Experience**: Highlight invalid fields with clear error messages

### Concurrent Data Access
- **Scenario**: User has app open in multiple tabs/devices
- **Handling**: Last-write-wins with conflict detection
- **User Experience**: Show warning if data conflict detected

### Storage Limitations
- **Scenario**: Very large workout history causing performance issues
- **Handling**: Implement pagination and data archiving
- **User Experience**: Smooth loading with skeleton screens

### Mobile Device Constraints
- **Scenario**: Low memory devices or slow processors
- **Handling**: Optimise component rendering and data loading
- **User Experience**: Progressive loading with performance budgets

## 12. Acceptance Criteria

### Core Functionality
- [ ] User can create new exercise types with name and type selection
- [ ] Exercise selection shows recent exercises first with search capability
- [ ] User can record workout sessions with flexible sets/reps/weights
- [ ] Historical data displays correctly with pagination
- [ ] All data auto-saves within 500ms of user input
- [ ] Notes can be added at exercise and session levels

### Mobile User Experience
- [ ] All touch targets are minimum 44px and easily tappable
- [ ] Interface works correctly on screens as small as 320px width
- [ ] NumberField inputs work smoothly with increment/decrement
- [ ] Combobox selection is fast and responsive on mobile
- [ ] Loading states provide clear feedback during operations

### Data Integrity
- [ ] No data loss during network interruptions
- [ ] All user data is properly scoped and private
- [ ] Database constraints prevent invalid data states
- [ ] Auto-save operations are reliable and performant

### Performance Standards
- [ ] Initial page load under 2 seconds on 3G connection
- [ ] Exercise selection responds within 100ms
- [ ] Historical data loads within 2 seconds
- [ ] Auto-save operations complete within 500ms

### Quality Gates
- [ ] All Pest tests pass with >90% coverage
- [ ] TypeScript compilation with no errors
- [ ] ESLint and Prettier validation passes
- [ ] Manual testing on actual mobile devices confirms usability

## 13. Open Questions

### Technical Decisions Requiring Validation
- Should we implement offline-first architecture or rely on auto-save with retry?
- What's the optimal pagination size for workout history on mobile?
- Should we cache exercise lists locally or always fetch from server?

### User Experience Clarifications
- How should we handle exercise deletion when historical data exists?
- Should there be a confirmation step for recording sets or trust auto-save?
- What's the ideal debounce timing for auto-save (500ms confirmed)?

### Future Enhancement Considerations
- How important is data export functionality for initial release?
- Should we plan for exercise photo attachments in database design?
- What analytics would be most valuable for improving the tool?

### Performance and Scale
- What's the expected maximum number of exercises per user?
- How many workout sessions per month should we optimise for?
- Are there specific mobile browsers we need to prioritise?