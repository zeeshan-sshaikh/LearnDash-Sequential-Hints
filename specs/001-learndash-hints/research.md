# Research: LearnDash Sequential Hints Plugin

**Date**: 2026-04-01
**Branch**: `001-learndash-hints`

## Decision Log

### 1. Question Storage & Hint Meta Keys

**Decision**: Store Hint 2 and Hint 3 as WordPress post meta on the
`sfwd-question` post type using keys `_ldh_hint_2` and `_ldh_hint_3`.

**Rationale**: LearnDash questions are stored as `sfwd-question` custom
post types. The existing Hint 1 is managed by the embedded WpProQuiz
library (via `isTipEnabled()` / `getTipMsg()` on the question model).
Using `_ldh_` prefixed post meta keeps our data separate from
LearnDash core, avoids collisions, and follows WordPress conventions
(underscore prefix hides meta from the custom fields UI).

**Alternatives considered**:
- Storing in WpProQuiz tables directly: rejected because modifying
  LearnDash's internal tables violates the constitution's no-core-
  modification principle and risks breaking on updates.
- Using a custom database table: rejected as unnecessary; post meta
  is sufficient for string data per question.

### 2. Admin UI Integration Point

**Decision**: Add a WordPress meta box on the `sfwd-question` edit
screen using `add_meta_box()` hooked to `admin_init`, with save
handled via `save_post_sfwd-question`.

**Rationale**: LearnDash uses its own settings framework
(`Learndash_Admin_Question_Edit`) for the question editor. However,
the standard WordPress meta box API is the safest integration point
because it does not depend on internal LearnDash class structure that
may change between versions. The meta box appears below the LearnDash
fields and is clearly labeled "Additional Hints".

**Alternatives considered**:
- Hooking into `learndash_settings_field` filter: rejected because
  this filter's signature and behavior are not publicly documented
  and may change without notice.
- Adding fields via `admin_footer` JS injection: rejected as fragile
  and not following WordPress standards.

### 3. Frontend Hint Display Approach

**Decision**: Override LearnDash's default hint rendering via PHP
output filters and inject custom JavaScript that intercepts hint
button clicks. The JS manages sequential reveal and budget tracking
client-side, with server-side AJAX for persistence.

**Rationale**: LearnDash's quiz frontend is powered by the WpProQuiz
jQuery plugin (`wpProQuizFront`). Key selectors:
- Hint button: `.wpProQuiz_TipButton` (input[type=button])
- Hint content: `.wpProQuiz_tipp` (div, hidden by default)

The existing system supports only one hint per question. Our plugin
must:
1. Hide the default tip button initially (CSS/JS).
2. Inject our own sequential hint buttons and content divs.
3. Pass Hint 2 and Hint 3 data to the frontend via
   `wp_localize_script()`.

**Alternatives considered**:
- Modifying WpProQuiz JS directly: rejected (core modification).
- Using only CSS to hide/show: rejected because sequential logic
  requires JS state management.

### 4. Quiz-Wide Hint State Storage

**Decision**: Track hint state (per-question reveal level + total
hints used) in the user's quiz attempt data via `learndash_quiz_resume_data`
filter and AJAX calls to a custom endpoint.

**Rationale**: LearnDash's "Enable Quiz Saving" feature periodically
saves quiz progress. The `learndash_quiz_resume_data` filter allows
us to inject additional data into the saved state. On resume, we read
this data back to restore hint visibility and the counter.

For real-time tracking during the quiz (before auto-save triggers),
we use an AJAX endpoint (`wp_ajax_ldh_use_hint`) that:
1. Validates the nonce and user.
2. Increments the hint count in a transient or user meta keyed by
   quiz attempt.
3. Returns the updated count and next-hint availability.

**Alternatives considered**:
- Client-side only (localStorage): rejected because it does not
  survive browser/device changes and can be manipulated.
- Custom database table for attempt state: rejected as overkill;
  user meta keyed by quiz attempt ID is sufficient.

### 5. Plugin File Structure

**Decision**: Follow standard WordPress plugin structure with class-
based organization.

```
learndash-hints-plugin/
├── learndash-hints-plugin.php     # Main plugin file (bootstrap)
├── includes/
│   ├── class-plugin.php           # Core plugin class
│   ├── class-admin.php            # Admin meta box & save logic
│   └── class-frontend.php         # Frontend rendering & AJAX
├── assets/
│   ├── css/
│   │   ├── admin.css              # Admin hint fields styling
│   │   └── quiz.css               # Frontend hint UI styling
│   └── js/
│       └── quiz-hints.js          # Frontend hint logic
└── languages/                     # Translation files
```

**Rationale**: Minimal structure matching the plugin's scope. No
separate settings page needed (constitution principle V). Class-based
for namespace isolation without requiring PHP namespaces (broader
compatibility).

### 6. Dependency Checking

**Decision**: Use `plugins_loaded` hook to verify LearnDash is active
via `is_plugin_active('sfwd-lms/sfwd_lms.php')`. On activation, use
`register_activation_hook` to `wp_die()` if LearnDash is missing.

**Rationale**: The `plugins_loaded` hook fires after all plugins are
loaded, ensuring LearnDash's presence can be reliably checked. The
activation hook provides an immediate block if someone tries to
activate without LearnDash.

### 7. Text Domain & Internationalization

**Decision**: Use text domain `learndash-hints` matching the plugin
slug. Load translations via `load_plugin_textdomain()` on
`plugins_loaded`.

**Rationale**: WordPress translation tools require text domain to
match the plugin slug. All user-facing strings use `__()`,
`esc_html__()`, etc.

### 8. LearnDash Question Post Type

**Decision**: Use `sfwd-question` as the post type for meta box
registration and post meta operations.

**Rationale**: LearnDash stores questions as `sfwd-question` custom
post type. This is the correct post type for `add_meta_box()` and
`save_post` hooks. Note: some documentation references `ld-question`
but `sfwd-question` is the actual registered post type in current
LearnDash versions.
