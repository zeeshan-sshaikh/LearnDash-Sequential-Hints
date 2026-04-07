# Data Model: LearnDash Sequential Hints Plugin

**Date**: 2026-04-01
**Branch**: `001-learndash-hints`

## Entities

### 1. Question Hint Set

Extends the existing LearnDash question with additional hint fields.

**Storage**: WordPress post meta on `sfwd-question` post type.

| Field | Meta Key | Type | Constraints |
|-------|----------|------|-------------|
| Question ID | (post ID) | integer | Existing LearnDash question |
| Hint 1 | (managed by LearnDash/WpProQuiz) | string | Existing field, not modified |
| Hint 2 | `_ldh_hint_2` | string | Optional, max length per WP default |
| Hint 3 | `_ldh_hint_3` | string | Optional, max length per WP default |

**Validation Rules**:
- Hint 2 and Hint 3 are optional (empty string = no hint).
- Content is sanitized via `sanitize_textarea_field()` on save.
- Content is escaped via `wp_kses_post()` on output (allows basic HTML).
- If Hint 2 is empty, Hint 3 is ignored in the sequential flow
  regardless of its content.

**Relationships**:
- Belongs to one `sfwd-question` post (1:1 via post meta).
- Indirectly related to a quiz via the question's `_quizId` meta.

---

### 2. Quiz Hint State

Tracks hint usage for a specific user's quiz attempt.

**Storage**: Injected into LearnDash's quiz resume data via the
`learndash_quiz_resume_data` filter. Additionally stored as user meta
for real-time AJAX tracking.

**User Meta Key**: `_ldh_quiz_hint_state_{quiz_id}`

| Field | Key (in state object) | Type | Constraints |
|-------|-----------------------|------|-------------|
| Total Hints Used | `total_used` | integer | 0-5, enforced |
| Per-Question Levels | `questions` | object | Map of question_id → reveal_level (0-3) |
| Quiz ID | `quiz_id` | integer | LearnDash quiz post ID |
| Started At | `started_at` | integer | Unix timestamp of quiz start |

**Example State Object**:
```json
{
  "quiz_id": 42,
  "total_used": 3,
  "started_at": 1743500000,
  "questions": {
    "101": 2,
    "102": 1,
    "103": 0
  }
}
```

**Validation Rules**:
- `total_used` MUST NOT exceed 5 (server-side enforced).
- Each question's reveal level MUST NOT exceed the number of
  non-empty hints configured for that question (server-side).
- State is created when the first hint is used in a quiz attempt.
- State is deleted/reset when a new quiz attempt begins.

**State Transitions**:
```
[No State] → first hint used → {total_used: 1, questions: {qid: 1}}
                                        ↓
                              hint used on same question
                                        ↓
                              {total_used: 2, questions: {qid: 2}}
                                        ↓
                              hint used on different question
                                        ↓
                              {total_used: 3, questions: {qid: 2, qid2: 1}}
                                        ↓
                              ... continues until total_used = 5
                                        ↓
                              [Budget Exhausted] → all buttons hidden
```

**Relationships**:
- Belongs to one user (via user meta).
- References one quiz (via `quiz_id`).
- References multiple questions (via `questions` map keys).

---

### 3. Frontend Hint Data (Passed to JavaScript)

Data passed from PHP to JavaScript via `wp_localize_script()`.

| Field | JS Key | Type | Source |
|-------|--------|------|--------|
| AJAX URL | `ajaxUrl` | string | `admin_url('admin-ajax.php')` |
| Nonce | `nonce` | string | `wp_create_nonce('ldh_quiz_nonce')` |
| Max Budget | `maxBudget` | integer | 5 (constant) |
| Quiz ID | `quizId` | integer | Current quiz post ID |
| Current State | `initialState` | object | Quiz Hint State (or null) |
| Question Hints | `questionHints` | object | Map of question_id → array of hint texts |

**Example `questionHints` structure**:
```json
{
  "101": ["First hint text", "Second hint text", "Third hint text"],
  "102": ["Only one hint for this question"],
  "103": ["Hint A", "Hint B"]
}
```

Only non-empty hints are included in the array. The array length
determines how many sequential hints are available per question.

---

## Data Flow

### Hint Reveal Flow
1. Student clicks "Show Hint" button on question.
2. JavaScript sends AJAX POST to `wp_ajax_ldh_use_hint`.
3. Server validates: nonce, user login, budget not exceeded,
   question has hint at requested level.
4. Server updates user meta state (increment `total_used`,
   set question reveal level).
5. Server returns: hint text, updated `total_used`,
   `next_hint_available` boolean, `budget_exhausted` boolean.
6. JavaScript displays hint, updates counter, shows/hides
   next button.

### Quiz Resume Flow
1. LearnDash saves quiz progress (periodic auto-save).
2. `learndash_quiz_resume_data` filter fires → plugin injects
   hint state into saved data.
3. Student leaves and returns.
4. LearnDash restores quiz progress.
5. Plugin reads hint state from restored data via
   `wp_localize_script()` as `initialState`.
6. JavaScript restores: previously revealed hints visible,
   correct next button shown, counter at saved value.

### New Attempt Flow
1. Student starts a new quiz attempt.
2. Plugin detects new attempt (no existing state or explicit
   reset trigger).
3. User meta for hint state is cleared/reset.
4. JavaScript initializes with `initialState: null`,
   counter at 0/5.
