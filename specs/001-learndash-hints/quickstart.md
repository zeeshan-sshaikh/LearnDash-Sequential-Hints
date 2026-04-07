# Quickstart: LearnDash Sequential Hints Plugin

**Date**: 2026-04-01
**Branch**: `001-learndash-hints`

## Prerequisites

- WordPress 6.0+ installed locally (e.g., via Local, MAMP, or Docker)
- LearnDash LMS plugin installed and activated
- At least one quiz with questions created in LearnDash
- PHP 7.4+

## Installation

1. Copy the `learndash-hints-plugin/` directory into
   `wp-content/plugins/`.
2. Go to **Plugins > Installed Plugins** in WordPress admin.
3. Activate "LearnDash Sequential Hints".
   - If LearnDash is not active, you will see an error and the
     plugin will not activate.

## Adding Hints to Questions

1. Go to **LearnDash > Questions** and edit a question.
2. Scroll to the "Additional Hints" meta box.
3. Enter text in the **Hint 2** and **Hint 3** fields.
   - Leave empty to skip that hint in the sequence.
   - Hint 1 is managed by LearnDash's built-in hint field
     (in the question settings above).
4. Save the question.

## Testing the Hint Flow

1. Ensure the quiz has at least 3 questions, each with all
   3 hints filled in.
2. Open the quiz as a student (log in as a non-admin user or
   use a test student account).
3. Verify:
   - Only one "Show Hint" button is visible per question.
   - Clicking it reveals the hint and shows the next button.
   - The counter displays "Hints used: X/5".
   - After 5 total hints, all buttons disappear.

## Testing Quiz Saving Integration

1. Go to quiz settings and enable **Enable Quiz Saving**
   (set interval to 10 seconds).
2. Start the quiz as a student and use 2-3 hints.
3. Close the browser tab.
4. Re-open the quiz URL.
5. Verify:
   - Previously revealed hints are still visible.
   - The counter shows the correct count.
   - You can continue using hints up to the budget.

## Development Setup

```bash
# Clone the repo
git clone <repo-url>
cd learndash-hints-plugin

# The plugin is ready to use - no build step required.
# Copy to your local WordPress plugins directory:
cp -r . /path/to/wordpress/wp-content/plugins/learndash-hints-plugin/

# Activate via WP-CLI:
wp plugin activate learndash-hints-plugin
```

## File Overview

```
learndash-hints-plugin.php   # Main plugin bootstrap
includes/
  class-plugin.php           # Dependency check, init, hooks
  class-admin.php            # Meta box for Hint 2 & Hint 3
  class-frontend.php         # Quiz rendering & AJAX handlers
assets/
  css/admin.css              # Admin field styling
  css/quiz.css               # Frontend hint UI
  js/quiz-hints.js           # Sequential hint logic & counter
languages/                   # Translation files
```
