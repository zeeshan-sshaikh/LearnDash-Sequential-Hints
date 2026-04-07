# Implementation Plan: LearnDash Sequential Hints

**Branch**: `001-learndash-hints` | **Date**: 2026-04-01 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/001-learndash-hints/spec.md`

## Summary

Build a WordPress plugin that extends LearnDash quiz questions with
up to 3 sequential hints per question (adding 2 extra hint fields to
the admin editor), enforces a quiz-wide budget of 5 hints with a
visible counter, and persists hint state via LearnDash's quiz saving
mechanism. The plugin hooks into LearnDash's existing infrastructure
without modifying core files.

## Technical Context

**Language/Version**: PHP 7.4+ (WordPress plugin), JavaScript (ES5+
jQuery)
**Primary Dependencies**: WordPress 6.0+, LearnDash LMS (WpProQuiz
embedded), jQuery (bundled with WordPress)
**Storage**: WordPress post meta (`_ldh_hint_2`, `_ldh_hint_3` on
`sfwd-question`), WordPress user meta (quiz hint state)
**Testing**: Manual testing against local WordPress + LearnDash
installation
**Target Platform**: WordPress websites with LearnDash LMS active
**Project Type**: WordPress plugin (add-on to LearnDash)
**Performance Goals**: < 1 second hint reveal (AJAX round-trip)
**Constraints**: No LearnDash core modifications, must work with
LearnDash quiz saving feature, all strings translatable
**Scale/Scope**: Single-site WordPress, standard quiz sizes (10-50
questions)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Evidence |
|-----------|--------|----------|
| I. LearnDash Compatibility First | PASS | Plugin uses WordPress hooks, post meta, and filters only. No core file modifications. Admin fields via `add_meta_box()`, frontend via `wp_enqueue_script()` + AJAX. |
| II. Sequential Disclosure | PASS | Frontend JS enforces one-button-at-a-time display. Server validates hint level before returning text. |
| III. Quiz-Wide Hint Budget | PASS | Server-side enforcement in AJAX handler. Client hides buttons when budget exhausted. Counter displayed via injected HTML. |
| IV. State Persistence | PASS | Hint state stored in user meta, integrated with `learndash_quiz_resume_data` filter for quiz saving compatibility. |
| V. Admin Simplicity | PASS | Two textarea fields added to existing question editor via meta box. No separate settings page. Empty hints handled gracefully. |
| WordPress Plugin Standards | PASS | Nonce verification, `sanitize_textarea_field()` on input, `wp_kses_post()` on output, text domain for i18n. |
| LearnDash Dependency | PASS | Activation hook checks for LearnDash. `plugins_loaded` hook verifies presence at runtime. |
| No Core Modifications | PASS | All integration via hooks, filters, and public APIs. |
| Internationalization | PASS | All user-facing strings use `__()` / `esc_html__()` with `learndash-hints` text domain. |
| Data Sanitization | PASS | Input sanitized on save, output escaped on render. AJAX nonces for all endpoints. |

**Gate result**: ALL PASS - no violations to justify.

## Project Structure

### Documentation (this feature)

```text
specs/001-learndash-hints/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── ajax-endpoints.md
└── tasks.md             # Phase 2 output (created by /speckit.tasks)
```

### Source Code (repository root)

```text
learndash-hints-plugin.php          # Main plugin file (bootstrap)
includes/
├── class-plugin.php                # Core: dependency check, init
├── class-admin.php                 # Admin: meta box, save hooks
└── class-frontend.php              # Frontend: rendering, AJAX, state
assets/
├── css/
│   ├── admin.css                   # Admin hint field styling
│   └── quiz.css                    # Frontend hint UI & counter
└── js/
    └── quiz-hints.js               # Sequential hint logic, counter, AJAX
languages/                          # Translation files (.pot)
```

**Structure Decision**: Single flat WordPress plugin structure. No
build tools or compilation needed. PHP classes loaded directly.
JavaScript is vanilla jQuery (no bundler). This matches the plugin's
scope and WordPress ecosystem conventions.

## Complexity Tracking

> No constitution violations to justify. Table intentionally empty.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| (none) | — | — |
