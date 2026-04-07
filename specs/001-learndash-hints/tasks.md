# Tasks: LearnDash Sequential Hints Plugin

**Input**: Design documents from `/specs/001-learndash-hints/`
**Prerequisites**: plan.md (required), spec.md (required), research.md, data-model.md, contracts/ajax-endpoints.md

**Tests**: Not explicitly requested in the feature specification. Test tasks are omitted.

**Organization**: Tasks grouped by user story. Admin meta box (Hint 2 & 3 save/load) is in Foundational because it blocks all frontend user stories.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- WordPress plugin at repository root
- PHP classes in `includes/`
- Static assets in `assets/css/` and `assets/js/`
- Translations in `languages/`

---

## Phase 1: Setup

**Purpose**: Create plugin directory structure and bootstrap file

- [x] T001 Create plugin directory structure: `includes/`, `assets/css/`, `assets/js/`, `languages/`
- [x] T002 Create main plugin bootstrap file `learndash-hints-plugin.php` with WordPress plugin header (`Plugin Name: LearnDash Sequential Hints`, `Requires Plugins: sfwd-lms`, `Text Domain: learndash-hints`, `Version: 1.0.0`), define constants (`LDH_PLUGIN_FILE`, `LDH_PLUGIN_DIR`, `LDH_PLUGIN_URL`, `LDH_VERSION`), require `includes/class-plugin.php`, and instantiate `LearnDash_Hints_Plugin`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core plugin infrastructure that MUST be complete before ANY user story can be implemented

**CRITICAL**: No user story work can begin until this phase is complete

- [x] T003 Implement `LearnDash_Hints_Plugin` class in `includes/class-plugin.php`: LearnDash dependency check on `plugins_loaded` (verify `sfwd-lms/sfwd_lms.php` is active), `register_activation_hook` that calls `wp_die()` if LearnDash missing, `register_deactivation_hook` for cleanup, `load_plugin_textdomain()` for i18n with domain `learndash-hints`, conditionally instantiate `LearnDash_Hints_Admin` and `LearnDash_Hints_Frontend` classes, require class files
- [x] T004 Implement `LearnDash_Hints_Admin` class in `includes/class-admin.php`: register meta box `ldh_additional_hints` on `sfwd-question` post type via `add_meta_box()` hooked to `add_meta_boxes`, render callback with `wp_nonce_field('ldh_save_hints_' . $post->ID, 'ldh_hints_nonce')` and two textarea fields for `_ldh_hint_2` and `_ldh_hint_3` with labels using `esc_html__()`, save handler on `save_post_sfwd-question` that verifies nonce, checks `current_user_can('edit_post', $post_id)`, skips autosave/revision, sanitizes via `sanitize_textarea_field()`, and calls `update_post_meta()` for both hint keys
- [x] T005 [P] Create scaffold `LearnDash_Hints_Frontend` class in `includes/class-frontend.php`: constructor registers `wp_enqueue_scripts` hook (calls enqueue method), registers three AJAX actions (`wp_ajax_ldh_use_hint`, `wp_ajax_ldh_get_hint_state`, `wp_ajax_ldh_reset_hint_state`) pointing to placeholder methods that return `wp_send_json_error('Not implemented')`, add `is_learndash_quiz_page()` helper that checks `is_singular('sfwd-quiz')`

**Checkpoint**: Plugin activates, admin can see and save Hint 2 & 3 fields on question editor

---

## Phase 3: User Story 1 - Sequential Hint Reveal (Priority: P1) MVP

**Goal**: Students see hints one at a time per question, clicking to reveal each sequentially

**Independent Test**: Create a quiz question with 3 hints, take quiz as student, click hint buttons and verify sequential reveal

### Implementation for User Story 1

- [x] T006 [US1] Implement hint data collection in `includes/class-frontend.php`: in the enqueue method, when on a quiz page, query all `sfwd-question` posts belonging to the current quiz, for each question collect hint 1 (from WpProQuiz `_tipMsg` meta or LearnDash hint field), hint 2 (`_ldh_hint_2`), hint 3 (`_ldh_hint_3`), build `questionHints` map (question_id → array of non-empty hint texts), pass to JS via `wp_localize_script('ldh-quiz-hints', 'ldhQuizData', [...])` including `ajaxUrl`, `nonce` (via `wp_create_nonce('ldh_quiz_nonce')`), `quizId`, `maxBudget` (5), and `questionHints`
- [x] T007 [US1] Implement `ldh_use_hint` AJAX handler in `includes/class-frontend.php`: verify nonce against `ldh_quiz_nonce`, verify user is logged in, validate `quiz_id` and `question_id` from `$_POST`, load current hint state from user meta `_ldh_quiz_hint_state_{quiz_id}`, determine next hint level for the question, check hint level does not exceed available hints for that question, load hint text (level 1 from WpProQuiz tip, level 2 from `_ldh_hint_2`, level 3 from `_ldh_hint_3`), verify hint text is non-empty, update state (increment `total_used`, set question reveal level), save state via `update_user_meta()`, return JSON with `hint_text` (escaped via `wp_kses_post()`), `hint_level`, `total_used`, `next_hint_available`, `budget_exhausted`
- [x] T008 [US1] Create `assets/js/quiz-hints.js`: jQuery document-ready wrapper, on init hide all default `.wpProQuiz_TipButton` elements and `.wpProQuiz_tipp` divs, for each question listed in `ldhQuizData.questionHints` inject a custom `<button class="ldh-hint-btn">` labeled with translatable "Show Hint" text, on `.ldh-hint-btn` click send AJAX POST to `ldhQuizData.ajaxUrl` with action `ldh_use_hint` and question/quiz IDs, on success insert hint text into a new `<div class="ldh-hint-content">` below the button, remove clicked button, if `response.data.next_hint_available` is true inject next `ldh-hint-btn`, if false show no more buttons for that question
- [x] T009 [P] [US1] Create `assets/css/quiz.css`: style `.ldh-hint-btn` as a styled button matching LearnDash quiz UI (padding, border-radius, background color), style `.ldh-hint-content` with left border accent, padding, background tint, margin-bottom for visual separation between revealed hints, hide `.wpProQuiz_TipButton` and `.wpProQuiz_tipp` elements when plugin is active (`.ldh-active .wpProQuiz_TipButton { display: none; }`)

**Checkpoint**: Sequential hint reveal works on quiz questions. One hint shown at a time, next button appears after each reveal.

---

## Phase 4: User Story 2 - Quiz-Wide Hint Budget and Counter (Priority: P2)

**Goal**: Enforce a maximum of 5 hints per quiz attempt with a visible counter

**Independent Test**: Create quiz with 3+ questions each having 3 hints, use 5 hints across questions, verify counter updates and all buttons disappear at 5/5

### Implementation for User Story 2

- [x] T010 [US2] Add hint state management helpers to `includes/class-frontend.php`: add `get_hint_state($user_id, $quiz_id)` method that reads user meta `_ldh_quiz_hint_state_{quiz_id}` and returns decoded array (or default `{total_used: 0, questions: {}, quiz_id: $quiz_id, started_at: time()}`), add `save_hint_state($user_id, $quiz_id, $state)` method that writes to user meta, update `ldh_use_hint` handler to check `$state['total_used'] < 5` before proceeding (return error with code `budget_exhausted` if not), pass `initialState` in `wp_localize_script()` data so JS knows current state on page load
- [x] T011 [US2] Add counter and budget enforcement to `assets/js/quiz-hints.js`: on init inject `<div class="ldh-hint-counter">Hints used: 0/5</div>` into the quiz wrapper (before first question or in quiz header area), maintain local `hintsUsed` variable (initialized from `ldhQuizData.initialState.total_used` or 0), after each successful hint AJAX response update `hintsUsed` from `response.data.total_used` and update counter text, if `response.data.budget_exhausted` is true remove all `.ldh-hint-btn` elements across all questions, on init if `ldhQuizData.initialState` exists and `total_used >= maxBudget` then hide all hint buttons immediately
- [x] T012 [US2] Implement `ldh_reset_hint_state` AJAX handler in `includes/class-frontend.php`: verify nonce and user authentication, delete user meta `_ldh_quiz_hint_state_{quiz_id}`, return JSON with `total_used: 0` and `budget_exhausted: false`, also add detection in the enqueue method for new quiz attempts (if quiz has no saved progress, call reset internally)

**Checkpoint**: Counter shows and updates, hints stop at 5/5, new attempts reset to 0/5

---

## Phase 5: User Story 3 - Admin Hint Configuration UI (Priority: P3)

**Goal**: Admin sees well-styled Hint 2 and Hint 3 fields on the question editor

**Independent Test**: Edit a question in admin, see styled hint fields, enter text, save, reload, verify values persist

### Implementation for User Story 3

- [x] T013 [US3] Create `assets/css/admin.css`: style the `#ldh_additional_hints` meta box textarea fields to match LearnDash's existing hint field appearance (full width, consistent font, label spacing), add descriptive help text below each field ("This hint will be shown after Hint 1 is used"), add visual numbering/labels for Hint 2 and Hint 3
- [x] T014 [US3] Add conditional admin asset enqueuing to `includes/class-admin.php`: hook `admin_enqueue_scripts`, check `$hook_suffix` is `post.php` or `post-new.php`, check `get_current_screen()->post_type` is `sfwd-question`, enqueue `assets/css/admin.css` with handle `ldh-admin-style` and version `LDH_VERSION`

**Checkpoint**: Admin hint fields are visually polished and styled consistently with LearnDash

---

## Phase 6: User Story 4 - Hint State Persists Across Quiz Sessions (Priority: P4)

**Goal**: Hint usage state survives when student leaves and resumes a quiz with quiz saving enabled

**Independent Test**: Enable quiz saving, use 3 hints, leave quiz, resume, verify counter shows 3/5 and previously revealed hints are visible

### Implementation for User Story 4

- [x] T015 [US4] Implement `learndash_quiz_resume_data` filter integration in `includes/class-frontend.php`: add filter on `learndash_quiz_resume_data`, in the callback inject the current hint state (from user meta) into the resume data array under key `ldh_hint_state`, on quiz resume (when LearnDash restores saved data), read `ldh_hint_state` back from the resume data and update user meta so it's current, pass the restored state as `initialState` in `wp_localize_script()`
- [x] T016 [US4] Implement `ldh_get_hint_state` AJAX handler in `includes/class-frontend.php`: verify nonce and user authentication, load hint state from user meta for the given quiz, for each question with `reveal_level > 0` load the actual hint texts (so they can be re-displayed on resume), return JSON per the contract in `contracts/ajax-endpoints.md` with `total_used`, `budget_exhausted`, and `questions` map including `reveal_level` and `hints` array
- [x] T017 [US4] Add quiz resume restore logic to `assets/js/quiz-hints.js`: on init check if `ldhQuizData.initialState` is not null and has `total_used > 0`, if so call `ldh_get_hint_state` AJAX to get full state with hint texts, for each question in the response with `reveal_level > 0` inject the previously revealed hint text divs (`.ldh-hint-content`), if the question has more hints available AND budget allows show the next `.ldh-hint-btn`, set counter to restored `total_used` value, if `budget_exhausted` is true hide all remaining hint buttons

**Checkpoint**: Full persistence works with LearnDash quiz saving. Hints survive browser close and resume.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final validation, i18n, and edge case handling

- [x] T018 [P] Generate i18n `.pot` template file in `languages/learndash-hints.pot` by running `wp i18n make-pot . languages/learndash-hints.pot` or manually creating the POT file with all translatable strings from PHP and JS files
- [x] T019 Verify edge case handling across `includes/class-frontend.php` and `assets/js/quiz-hints.js`: question with no hints shows no buttons, question with Hint 3 filled but Hint 2 empty skips to end after Hint 1, budget reached mid-question stops next button from appearing, LearnDash deactivation triggers admin notice and plugin disables gracefully
- [x] T020 [P] Run full quickstart.md validation (requires local WordPress + LearnDash): install plugin on local WordPress with LearnDash, create quiz with 3+ questions having varying hint counts, test complete flow as student (sequential reveal, counter, budget cap, resume)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **US1 (Phase 3)**: Depends on Foundational - core hint reveal
- **US2 (Phase 4)**: Depends on US1 - budget extends hint reveal logic
- **US3 (Phase 5)**: Depends on Foundational only - admin UI is independent of frontend
- **US4 (Phase 6)**: Depends on US1 + US2 - persistence wraps around existing state
- **Polish (Phase 7)**: Depends on all user stories being complete

### User Story Dependencies

- **US1 (P1)**: Requires Foundational (Phase 2) complete. Admin must be able to save hints.
- **US2 (P2)**: Requires US1 (Phase 3) complete. Budget logic extends the hint reveal JS and PHP.
- **US3 (P3)**: Requires Foundational (Phase 2) only. Can run in parallel with US1/US2 if needed.
- **US4 (P4)**: Requires US1 + US2 complete. Persistence wraps around the full hint state system.

### Within Each User Story

- PHP backend before JavaScript frontend
- Data collection/helpers before AJAX handlers
- AJAX handlers before JS that calls them
- CSS can be parallel with JS (different files)

### Parallel Opportunities

- T005 can run in parallel with T003 and T004 (scaffold vs implementation)
- T009 can run in parallel with T006, T007, T008 (CSS vs JS/PHP)
- T013 can run in parallel with Phase 3 or Phase 4 (admin CSS is independent)
- T018 and T020 can run in parallel with T019

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (admin fields + plugin bootstrap)
3. Complete Phase 3: US1 - Sequential Hint Reveal
4. **STOP and VALIDATE**: Test hint reveal independently
5. Demo: "hints appear one at a time per question"

### Incremental Delivery

1. Setup + Foundational → Plugin activates, admin can save hints
2. Add US1 → Sequential hint reveal works → Demo (MVP!)
3. Add US2 → Budget cap + counter → Demo
4. Add US3 → Polished admin UI → Demo
5. Add US4 → Full persistence with quiz saving → Demo
6. Polish → i18n, edge cases, validation → Release ready

### File Ownership Map

| File | Created In | Modified In |
|------|-----------|-------------|
| `learndash-hints-plugin.php` | T002 | — |
| `includes/class-plugin.php` | T003 | — |
| `includes/class-admin.php` | T004 | T014 |
| `includes/class-frontend.php` | T005 | T006, T007, T010, T012, T015, T016 |
| `assets/js/quiz-hints.js` | T008 | T011, T017 |
| `assets/css/quiz.css` | T009 | — |
| `assets/css/admin.css` | T013 | — |
| `languages/learndash-hints.pot` | T018 | — |

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- The admin meta box (T004) is in Foundational because all frontend stories need stored hint data
- US3 in Phase 5 covers admin CSS/UX polish; the save/load functionality is in T004
- `includes/class-frontend.php` is the most modified file; tasks T006→T007→T010→T012→T015→T016 must be sequential
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
