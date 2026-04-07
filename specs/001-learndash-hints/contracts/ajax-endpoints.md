# AJAX Contracts: LearnDash Sequential Hints Plugin

**Date**: 2026-04-01
**Branch**: `001-learndash-hints`

## Endpoint: Use Hint

**WordPress AJAX Action**: `ldh_use_hint`
**Hooks**: `wp_ajax_ldh_use_hint` (logged-in users only)
**Method**: POST

### Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | yes | `ldh_use_hint` |
| `nonce` | string | yes | Value from `ldhQuizData.nonce` |
| `quiz_id` | integer | yes | LearnDash quiz post ID |
| `question_id` | integer | yes | LearnDash question post ID |

### Success Response (HTTP 200)

```json
{
  "success": true,
  "data": {
    "hint_text": "<p>The hint content HTML</p>",
    "hint_level": 2,
    "total_used": 3,
    "next_hint_available": true,
    "budget_exhausted": false
  }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `hint_text` | string | Sanitized HTML of the revealed hint |
| `hint_level` | integer | Which hint was just revealed (1, 2, or 3) |
| `total_used` | integer | Updated quiz-wide hint count (0-5) |
| `next_hint_available` | boolean | Whether another hint exists for this question AND budget allows |
| `budget_exhausted` | boolean | Whether total_used has reached 5 |

### Error Response (HTTP 200, success=false)

```json
{
  "success": false,
  "data": {
    "code": "budget_exhausted",
    "message": "Hint budget exhausted for this quiz."
  }
}
```

**Error Codes**:

| Code | Meaning |
|------|---------|
| `invalid_nonce` | Nonce verification failed |
| `not_logged_in` | User is not authenticated |
| `invalid_params` | Missing or invalid quiz_id/question_id |
| `budget_exhausted` | Quiz-wide hint limit (5) reached |
| `no_hint_available` | No more hints for this question |
| `invalid_question` | Question does not belong to quiz |

### Server-Side Logic

1. Verify nonce against `ldh_quiz_nonce`.
2. Verify user is logged in (`wp_get_current_user()`).
3. Load current hint state from user meta.
4. Check `total_used < 5` (budget check).
5. Determine next hint level for the question from state.
6. Load hint text: level 1 = WpProQuiz tip, level 2 = `_ldh_hint_2`,
   level 3 = `_ldh_hint_3`.
7. Verify hint text is non-empty.
8. Update state: increment `total_used`, set question level.
9. Save state to user meta.
10. Return hint text and updated counters.

---

## Endpoint: Get Hint State

**WordPress AJAX Action**: `ldh_get_hint_state`
**Hooks**: `wp_ajax_ldh_get_hint_state` (logged-in users only)
**Method**: POST

### Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | yes | `ldh_get_hint_state` |
| `nonce` | string | yes | Value from `ldhQuizData.nonce` |
| `quiz_id` | integer | yes | LearnDash quiz post ID |

### Success Response

```json
{
  "success": true,
  "data": {
    "total_used": 3,
    "budget_exhausted": false,
    "questions": {
      "101": {
        "reveal_level": 2,
        "hints": ["First hint", "Second hint"]
      },
      "102": {
        "reveal_level": 1,
        "hints": ["Only hint"]
      }
    }
  }
}
```

### Server-Side Logic

1. Verify nonce and user authentication.
2. Load hint state from user meta for the given quiz.
3. For each question with reveals > 0, include the revealed
   hint texts (so they can be re-displayed on resume).
4. Return state with all revealed hints and counter.

---

## Endpoint: Reset Hint State

**WordPress AJAX Action**: `ldh_reset_hint_state`
**Hooks**: `wp_ajax_ldh_reset_hint_state` (logged-in users only)
**Method**: POST

### Request

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `action` | string | yes | `ldh_reset_hint_state` |
| `nonce` | string | yes | Value from `ldhQuizData.nonce` |
| `quiz_id` | integer | yes | LearnDash quiz post ID |

### Success Response

```json
{
  "success": true,
  "data": {
    "total_used": 0,
    "budget_exhausted": false
  }
}
```

### Server-Side Logic

1. Verify nonce and user authentication.
2. Delete user meta `_ldh_quiz_hint_state_{quiz_id}`.
3. Return fresh state.
