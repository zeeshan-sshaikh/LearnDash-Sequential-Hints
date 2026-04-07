<!-- Sync Impact Report
  Version change: N/A → 1.0.0 (initial ratification)
  Modified principles: N/A (initial)
  Added sections:
    - Core Principles (5 principles)
    - WordPress & LearnDash Constraints
    - Development Workflow
    - Governance
  Removed sections: N/A
  Templates requiring updates:
    - .specify/templates/plan-template.md ✅ no updates needed (generic)
    - .specify/templates/spec-template.md ✅ no updates needed (generic)
    - .specify/templates/tasks-template.md ✅ no updates needed (generic)
  Follow-up TODOs: None
-->

# LearnDash Hints Plugin Constitution

## Core Principles

### I. LearnDash Compatibility First

All features MUST extend LearnDash's existing quiz and question
infrastructure rather than replacing or bypassing it. The plugin MUST:

- Hook into LearnDash's question editor to add hint fields (Hint 2,
  Hint 3) alongside the existing default Hint 1 field.
- Use LearnDash's front-end quiz rendering pipeline (JavaScript and
  PHP filters/actions) to inject sequential hint behavior.
- Integrate with LearnDash's "Enable Quiz Saving" feature so that
  hint usage state persists when a user leaves and resumes a quiz.
- Never modify LearnDash core files; all integration MUST happen
  through WordPress hooks, filters, and the LearnDash API.

**Rationale**: The plugin is an add-on. Breaking compatibility with
LearnDash updates would render it unmaintainable and unusable.

### II. Sequential Disclosure

Hints MUST be revealed one at a time in strict order. The plugin MUST
enforce:

- Only the first hint button is visible when a question loads.
- The second hint button appears only after the first hint is used.
- The third hint button appears only after the second hint is used.
- Once all available hints for a question are used, no further hint
  buttons are shown for that question.

No configuration option may allow hints to be shown out of order or
all at once. This sequencing is a non-negotiable UX constraint.

**Rationale**: Sequential disclosure prevents users from consuming all
hints instantly, encouraging genuine problem-solving effort.

### III. Quiz-Wide Hint Budget

A hard cap of 5 hints MUST be enforced across the entire quiz session.
The plugin MUST:

- Track the total number of hints used across all questions in the
  current quiz attempt.
- Display a visible counter to the user showing hints used out of the
  maximum (e.g., "Hints used: 2/5").
- Disable all hint buttons across all questions once 5 hints have
  been consumed, regardless of per-question availability.
- Reset the counter to zero when a new quiz attempt begins.

**Rationale**: The hint budget forces users to be strategic about
which questions they use hints on, preserving assessment integrity.

### IV. State Persistence

Hint usage state MUST survive page reloads and quiz resumption. The
plugin MUST:

- Persist per-question hint reveal state and the quiz-wide hint
  counter via LearnDash's quiz saving mechanism.
- On quiz resume, restore the exact hint visibility state: hints
  already used remain visible, the next sequential hint button is
  shown (if budget allows), and the counter reflects prior usage.
- Store state server-side (not only in browser local storage) to
  prevent circumvention.

**Rationale**: Without persistence, users could reset their hint
budget by refreshing the page, defeating the budget constraint.

### V. Admin Simplicity

The admin experience MUST be minimal and intuitive. The plugin MUST:

- Add exactly two additional hint fields (Hint 2, Hint 3) to the
  existing LearnDash Edit Question page, directly below the existing
  Hint 1 field.
- Not require separate settings pages or complex configuration for
  basic operation.
- Gracefully handle questions where admins leave Hint 2 or Hint 3
  empty: only populate hints are shown, and the sequential flow
  skips empty hints.

**Rationale**: Site administrators already know the LearnDash question
editor. Keeping the admin interface familiar reduces onboarding
friction and support burden.

## WordPress & LearnDash Constraints

- **WordPress Plugin Standards**: The plugin MUST follow WordPress
  coding standards (PHP, JS, CSS) and pass WordPress plugin review
  guidelines for enqueue, sanitization, escaping, and nonce usage.
- **LearnDash Dependency**: The plugin MUST check for LearnDash's
  presence and version on activation. If LearnDash is not active or
  is below the minimum supported version, the plugin MUST deactivate
  gracefully with an admin notice.
- **No Core Modifications**: All integration MUST use actions,
  filters, and public APIs. Direct database table creation is
  acceptable only if LearnDash's existing meta tables are
  insufficient.
- **Internationalization**: All user-facing strings MUST be
  translatable using WordPress i18n functions (`__()`, `_e()`,
  `esc_html__()`).
- **Data Sanitization**: All admin inputs MUST be sanitized on save
  (`sanitize_text_field`, `wp_kses_post`) and escaped on output
  (`esc_html`, `wp_kses_post`).

## Development Workflow

- **Branch Strategy**: Feature branches off `main`; PRs require
  review before merge.
- **Testing**: Manual testing against a local WordPress installation
  with LearnDash active. Automated tests are encouraged but not
  gated given the WordPress/LearnDash dependency complexity.
- **Commit Discipline**: Each commit MUST represent a single logical
  change. Commit messages MUST follow conventional format
  (`feat:`, `fix:`, `docs:`, `chore:`).
- **Code Review Focus**: Reviewers MUST verify LearnDash hook usage,
  hint sequencing logic, and budget enforcement correctness.

## Governance

This constitution is the authoritative source for architectural
decisions and non-negotiable constraints for the LearnDash Hints
Plugin. All implementation work, code reviews, and design decisions
MUST comply with these principles.

- **Amendments**: Any change to this constitution MUST be documented
  with a version bump, rationale, and migration plan if the change
  affects existing code.
- **Versioning**: Constitution versions follow semantic versioning
  (MAJOR.MINOR.PATCH). Adding principles = MINOR bump; removing or
  redefining principles = MAJOR bump; clarifications = PATCH bump.
- **Compliance**: Every PR MUST be checked against applicable
  principles before merge. Violations MUST be resolved or justified
  in the Complexity Tracking table of the implementation plan.

**Version**: 1.0.0 | **Ratified**: 2026-04-01 | **Last Amended**: 2026-04-01
