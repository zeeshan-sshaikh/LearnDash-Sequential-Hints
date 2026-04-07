# Feature Specification: LearnDash Sequential Hints Plugin

**Feature Branch**: `001-learndash-hints`
**Created**: 2026-04-01
**Status**: Draft
**Input**: User description: "Create WordPress plugin that extends LearnDash quiz questions with up to 3 sequential hints per question, a quiz-wide hint budget of 5, a visible hint counter, and persistence via LearnDash quiz saving."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sequential Hint Reveal on Quiz Questions (Priority: P1)

A student is taking a LearnDash quiz and encounters a difficult question. They see a "Show Hint" button. When they click it, the first hint is revealed. A second "Show Hint" button now appears. They click it and see the second hint. A third button appears. They click it and see the third hint. No more hint buttons appear for this question.

**Why this priority**: This is the core mechanic of the entire plugin. Without sequential hint reveal, no other feature has meaning.

**Independent Test**: Can be fully tested by creating a quiz question with 3 hints, taking the quiz, and clicking hint buttons in order. Delivers the foundational hint experience.

**Acceptance Scenarios**:

1. **Given** a quiz question with 3 hints configured, **When** the student loads the question, **Then** only the first "Show Hint" button is visible.
2. **Given** the first hint button is visible, **When** the student clicks it, **Then** the first hint text is displayed and a second "Show Hint" button appears.
3. **Given** the second hint button is visible, **When** the student clicks it, **Then** the second hint text is displayed and a third "Show Hint" button appears.
4. **Given** the third hint button is visible, **When** the student clicks it, **Then** the third hint text is displayed and no further hint buttons appear.
5. **Given** a quiz question with only 2 hints configured (Hint 3 is empty), **When** the student uses both hints, **Then** no third hint button appears.
6. **Given** a quiz question with only the default hint configured (Hints 2 and 3 are empty), **When** the student uses the first hint, **Then** no further hint buttons appear.

---

### User Story 2 - Quiz-Wide Hint Budget and Counter (Priority: P2)

A student is taking a quiz with multiple questions. A counter displayed on the quiz shows "Hints used: 0/5". Each time they use a hint on any question, the counter increments. After using 5 hints total across different questions, the counter shows "Hints used: 5/5" and all remaining hint buttons across all questions are disabled or hidden.

**Why this priority**: The hint budget adds strategic depth and preserves assessment integrity. It depends on US1 (hint reveal) to function but is the key differentiating feature.

**Independent Test**: Can be tested by creating a quiz with at least 3 questions (each with 3 hints), using 5 hints across them, and verifying the counter updates and buttons disappear after 5.

**Acceptance Scenarios**:

1. **Given** a student starts a new quiz, **When** the quiz loads, **Then** a hint counter displays "Hints used: 0/5".
2. **Given** the counter shows "Hints used: 2/5", **When** the student uses a hint on any question, **Then** the counter updates to "Hints used: 3/5".
3. **Given** the counter shows "Hints used: 4/5", **When** the student uses one more hint, **Then** the counter updates to "Hints used: 5/5" and all hint buttons across all questions are hidden or disabled.
4. **Given** the counter shows "Hints used: 5/5", **When** the student navigates to a question with unused hints, **Then** no hint buttons are visible for that question.
5. **Given** a student starts a new quiz attempt (retake), **When** the quiz loads, **Then** the counter resets to "Hints used: 0/5".

---

### User Story 3 - Admin Configures Multiple Hints per Question (Priority: P3)

A site administrator edits a quiz question in the LearnDash admin panel. Below the existing Hint field, they see two additional fields: "Hint 2" and "Hint 3". They fill in all three hints and save. When students take the quiz, all three hints are available sequentially.

**Why this priority**: Without the admin being able to enter multiple hints, the sequential reveal has nothing to show. However, this is a backend/admin concern and can be developed somewhat independently of the front-end hint display.

**Independent Test**: Can be tested by editing a question in the admin, entering text in Hint 2 and Hint 3 fields, saving, and verifying the values persist on page reload.

**Acceptance Scenarios**:

1. **Given** an admin opens the Edit Question page for a LearnDash quiz question, **When** the page loads, **Then** "Hint 2" and "Hint 3" fields appear below the existing hint field.
2. **Given** the admin enters text in Hint 2 and Hint 3 fields, **When** they save the question, **Then** the hint values are stored and visible when the page is reloaded.
3. **Given** the admin leaves Hint 2 and Hint 3 empty, **When** they save, **Then** only the default hint is available to students during the quiz.
4. **Given** the admin fills Hint 2 but leaves Hint 3 empty, **When** a student takes the quiz, **Then** only Hint 1 and Hint 2 are available sequentially.

---

### User Story 4 - Hint State Persists Across Quiz Sessions (Priority: P4)

A student is taking a quiz with "Enable Quiz Saving" turned on. They use 3 hints across two questions, then leave the quiz. When they return and resume, the hints they already revealed are still visible, the counter shows "Hints used: 3/5", and they can continue using remaining hints.

**Why this priority**: Persistence is critical for long quizzes and prevents hint budget circumvention, but it depends on all other stories being functional first.

**Independent Test**: Can be tested by enabling quiz saving, using some hints, leaving the quiz, resuming, and verifying hint state is restored.

**Acceptance Scenarios**:

1. **Given** a student has used 3 hints and leaves the quiz (quiz saving enabled), **When** they resume the quiz later, **Then** the counter shows "Hints used: 3/5".
2. **Given** a student revealed Hint 1 and Hint 2 on a question before leaving, **When** they resume and navigate to that question, **Then** Hint 1 and Hint 2 are still visible, and the "Show Hint" button for Hint 3 is available (if budget allows).
3. **Given** a student used 5 hints before leaving, **When** they resume, **Then** the counter shows "Hints used: 5/5" and no hint buttons are available on any question.
4. **Given** quiz saving is NOT enabled, **When** a student leaves and returns to the quiz, **Then** the quiz starts fresh with the counter at "Hints used: 0/5".

---

### Edge Cases

- What happens when a question has no hints at all (not even the default hint)? The plugin shows no hint buttons for that question.
- What happens if the admin enters content in Hint 3 but leaves Hint 2 empty? Only Hint 1 is shown; Hint 3 is skipped since the sequence is broken at Hint 2.
- What happens if the global hint budget is reached mid-question (e.g., user used 4/5, reveals Hint 1, budget is now 5/5)? The next hint button for that question does not appear, even though Hints 2 and 3 are configured.
- What happens if LearnDash is deactivated while this plugin is active? The plugin deactivates gracefully with an admin notice and does not cause errors.
- What happens if a student opens the quiz in two browser tabs? The server-side state is authoritative; whichever tab saves last wins, and the other tab reflects the updated state on next interaction.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The plugin MUST add two additional hint input fields ("Hint 2" and "Hint 3") to the LearnDash Edit Question admin page, below the existing hint field.
- **FR-002**: The plugin MUST store each question's Hint 2 and Hint 3 values as question metadata, persisting across saves and page reloads.
- **FR-003**: The plugin MUST display only one hint button at a time per question during a quiz, revealing hints sequentially (Hint 1 first, then Hint 2, then Hint 3).
- **FR-004**: The plugin MUST skip hints that have empty content in the sequential flow (e.g., if Hint 2 is empty, no second button appears regardless of Hint 3's content).
- **FR-005**: The plugin MUST enforce a quiz-wide maximum of 5 hints per quiz attempt across all questions.
- **FR-006**: The plugin MUST display a persistent hint counter (e.g., "Hints used: X/5") visible to the student throughout the quiz.
- **FR-007**: The plugin MUST hide or disable all hint buttons across all questions once the quiz-wide hint budget of 5 is exhausted.
- **FR-008**: The plugin MUST reset the hint counter and all hint states when a new quiz attempt begins.
- **FR-009**: The plugin MUST integrate with LearnDash's "Enable Quiz Saving" feature to persist hint usage state (per-question reveal state and quiz-wide counter) when a student leaves and resumes a quiz.
- **FR-010**: The plugin MUST check for LearnDash's presence on activation and deactivate gracefully with an admin notice if LearnDash is not active.
- **FR-011**: All user-facing strings MUST be translatable.
- **FR-012**: All admin inputs MUST be sanitized on save and escaped on output.

### Key Entities

- **Question Hint Set**: Represents the collection of up to 3 hints for a single quiz question. Attributes: question ID, hint 1 text (existing LearnDash field), hint 2 text, hint 3 text.
- **Quiz Hint State**: Represents the hint usage state for a student's quiz attempt. Attributes: quiz attempt ID, student ID, total hints used (0-5), per-question hint reveal level (0-3 per question).
- **Hint Counter**: A display element showing the student their current hint usage relative to the quiz-wide maximum (e.g., "2/5").

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Admins can add up to 3 hints per question in under 30 seconds using the existing question editor.
- **SC-002**: Students see hints revealed one at a time with no more than 1 second delay between clicking the button and seeing the hint.
- **SC-003**: The hint counter accurately reflects the total hints used across all questions, with zero discrepancy after any sequence of hint usage and page navigation.
- **SC-004**: After reaching the quiz-wide limit of 5 hints, zero hint buttons are accessible on any question for the remainder of that quiz attempt.
- **SC-005**: When quiz saving is enabled, 100% of hint state (counter value and per-question reveal levels) is restored on quiz resume.
- **SC-006**: The plugin activates and deactivates cleanly with no errors, warnings, or data corruption on WordPress sites running the supported LearnDash version.

## Assumptions

- LearnDash LMS plugin is installed and active on the WordPress site (specific version provided via Google Drive link in task brief).
- The existing LearnDash hint field (Hint 1) continues to function as-is; this plugin extends it rather than replacing it.
- The quiz-wide hint budget of 5 is a fixed value for this version (not configurable by admin).
- The maximum of 3 hints per question is a fixed value for this version (not configurable by admin).
- Students access quizzes via standard web browsers with JavaScript enabled.
- The plugin targets single-site WordPress installations (multisite support is out of scope for v1).
- The "Enable Quiz Saving" feature in LearnDash provides hooks or data structures that can be extended to store additional state.
