# LearnDash Sequential Hints

Empower your LearnDash quizzes with a professional tiered hint system. Encourage critical thinking by providing up to 3 sequential hints per question, controlled by a global quiz-wide hint budget and a real-time counter for students.

## 🚀 Features

- **Sequential Hint Reveal**: Show hints one at a time. The next hint only becomes available after the previous one is viewed.
- **Three-Tier Guidance**: Admins can configure up to 3 hints for every quiz question.
- **Global Hint Budget**: Enforce a quiz-wide limit (default 5 hints) across all questions to prevent over-reliance on hints.
- **Real-time Counter**: A visible "Hints used: X/5" counter keeps students informed of their remaining budget.
- **Session Persistence**: Integrates with LearnDash "Quiz Saving" to remember hint usage and reveal states even if a student leaves and returns.
- **Admin Control**: Seamlessly integrated into the LearnDash question editor.

## 📋 Prerequisites

- **WordPress**: 5.8 or higher
- **LearnDash LMS**: 4.0 or higher
- **PHP**: 7.4 or higher

## 🛠️ Installation

1. Download the latest release as a `.zip` file.
2. In your WordPress dashboard, go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the downloaded file.
4. **Activate** the plugin.
5. Ensure **LearnDash LMS** is active, as this plugin extends its functionality.

## 📖 How to Use

### For Administrators
1. Navigate to **LearnDash LMS > Questions**.
2. Edit any question.
3. In the "Hint" section, you'll see new fields for **Hint 2** and **Hint 3**.
4. Fill in the hints and save.

### For Students
1. Start a quiz.
2. Click the **"Show Hint"** button to see the first hint.
3. If more hints are available, a new button will appear sequentially.
4. Keep an eye on the **Hint Counter** at the top of the quiz. Once you reach 5 hints, no further hints will be available for the entire quiz attempt.

## 💻 Development

This plugin is structured following WordPress best practices:
- `/assets`: CSS and JavaScript files for frontend/admin.
- `/includes`: Core logic and PHP classes.
- `/languages`: Translation files.
- `/specs`: Detailed feature specifications and data models.

## 📄 License

This project is licensed under the GPL-2.0+ License.
