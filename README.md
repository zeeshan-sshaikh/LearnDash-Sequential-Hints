# LearnDash Sequential Hints

[![License](https://img.shields.io/badge/License-GPL%202.0-blue.svg)](https://opensource.org/licenses/GPL-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

Empower your LearnDash quizzes with a professional tiered hint system. Encourage critical thinking by providing up to 3 sequential hints per question, controlled by a global quiz-wide hint budget and a real-time counter for students.

## 🚀 Features

- **Sequential Hint Reveal**: Show hints one at a time. The next hint only becomes available after the previous one is viewed.
- **Three-Tier Guidance**: Admins can configure up to 3 hints for every quiz question.
- **Global Hint Budget**: Enforce a quiz-wide limit (default 5 hints) across all questions to prevent over-reliance on hints.
- **Real-time Counter**: A visible "Hints used: X/5" counter keeps students informed of their remaining budget.
- **Session Persistence**: Integrates with LearnDash "Quiz Saving" to remember hint usage and reveal states even if a student leaves and returns.
- **Admin Control**: Seamlessly integrated into the LearnDash question editor.

## 📋 Prerequisites

- **WordPress**: 6.0 or higher
- **LearnDash LMS**: 4.0 or higher
- **PHP**: 7.4 or higher
- **Web Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)

## 🛠️ Installation

### Standard Installation
1. Download the latest release as a `.zip` file.
2. In your WordPress dashboard, go to **Plugins > Add New**.
3. Click **Upload Plugin** and select the downloaded file.
4. **Activate** the plugin.
5. Ensure **LearnDash LMS** is active, as this plugin extends its functionality.

### Developer Installation
```bash
# Clone the repository
git clone https://github.com/zeeshan-sshaikh/LearnDash-Sequential-Hints.git
cd LearnDash-Sequential-Hints

# The plugin is ready to use - no build step required.
# Copy to your local WordPress plugins directory:
cp -r . /path/to/wordpress/wp-content/plugins/learndash-sequential-hints/
```

## 📖 How to Use

### For Administrators
1. Navigate to **LearnDash LMS > Questions**.
2. Edit any question.
3. In the "Hint" section, you'll see new fields for **Hint 2** and **Hint 3**.
4. Fill in the hints and save.
   * *Note: Hint 1 is the standard LearnDash hint field.*

### For Students
1. Start a quiz.
2. Click the **"Show Hint"** button to see the first hint.
3. If more hints are available, a new button will appear sequentially.
4. Keep an eye on the **Hint Counter** at the top of the quiz. Once you reach the limit (default 5), no further hints will be available for the entire quiz attempt.

## 💻 Project Structure

```
learndash-hints-plugin/
├── assets/                 # CSS and JavaScript
│   ├── css/                # Admin and frontend styling
│   └── js/                 # Sequential hint logic & counter
├── includes/               # Core PHP logic
│   ├── class-plugin.php    # Bootstrap & dependencies
│   ├── class-admin.php     # Admin meta boxes
│   └── class-frontend.php  # AJAX & frontend rendering
├── languages/              # Translation files (.pot)
├── specs/                  # Technical documentation & specifications
└── learndash-hints-plugin.php # Main plugin file
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL-2.0+ License - see the [LICENSE](LICENSE) file for details.

## 🌟 Support

If you like this project, please give it a ⭐ on GitHub!
