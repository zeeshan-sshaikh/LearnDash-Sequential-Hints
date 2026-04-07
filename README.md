# LearnDash Sequential Hints

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![LearnDash](https://img.shields.io/badge/LearnDash-4.0%2B-green.svg)](https://www.learndash.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%202.0-blue.svg)](LICENSE)

**LearnDash Sequential Hints** is a professional WordPress plugin that enhances LearnDash quizzes by introducing a tiered, strategic hint system. It encourages critical thinking by providing students with up to three sequential hints per question, all while maintaining assessment integrity through a global quiz-wide hint budget.

---

## 🚀 Key Features

### 1. Sequential Hint Reveal
Hints are revealed one by one. The next hint in the sequence only becomes available after the previous one has been viewed. This prevents "hint dumping" and encourages students to work through problems step-by-step.

### 2. Three-Tier Guidance
Administrators can configure up to **three unique hints** for every quiz question. 
- **Hint 1:** The standard LearnDash hint.
- **Hint 2 & 3:** Additional layers of guidance added by this plugin.

### 3. Global Hint Budget
To prevent over-reliance on assistance, the plugin enforces a **global budget of 5 hints** per quiz attempt. Once a student uses 5 hints across any combination of questions, all remaining hint buttons are automatically disabled.

### 4. Real-time Progress Tracking
A persistent, live counter (e.g., "Hints used: 2/5") is displayed at the top of the quiz, keeping students informed of their remaining resources.

### 5. Session Persistence
Fully integrated with LearnDash's **"Quiz Saving"** feature. If a student leaves a quiz and returns later, their revealed hints and remaining budget are perfectly restored.

---

## 📋 Prerequisites

Before installing, ensure your environment meets the following requirements:
- **WordPress**: 6.0 or higher
- **LearnDash LMS**: 4.0 or higher (Active)
- **PHP**: 7.4 or higher
- **Browser**: Modern browsers with JavaScript enabled (Chrome, Firefox, Safari, Edge)

---

## 🛠️ Installation

### 📥 Standard Installation (Easy)
1. **Download** the latest release `.zip` file from the [Releases](https://github.com/zeeshan-sshaikh/LearnDash-Sequential-Hints/releases) page.
2. In your WordPress Dashboard, go to **Plugins > Add New**.
3. Click **Upload Plugin** at the top.
4. Choose the downloaded `.zip` file and click **Install Now**.
5. **Activate** the plugin.

### 💻 Developer Installation (Git)
```bash
# Navigate to your WordPress plugins directory
cd wp-content/plugins/

# Clone the repository
git clone https://github.com/zeeshan-sshaikh/LearnDash-Sequential-Hints.git learndash-sequential-hints

# No build steps required. Activate via WordPress Dashboard or WP-CLI
wp plugin activate learndash-sequential-hints
```

---

## 📖 How It Works: Instructions

### For Administrators: Setting Up Hints
1. Navigate to **LearnDash LMS > Questions**.
2. Edit an existing question or create a new one.
3. Locate the **"Hint"** section (standard LearnDash field). This is your **Hint 1**.
4. Scroll down to the **"Additional Hints"** meta box provided by this plugin.
5. Enter your content for **Hint 2** and **Hint 3**.
   - *Note: If you leave Hint 2 empty, Hint 3 will be ignored to maintain the sequence.*
6. **Save** the question.

### For Students: Using Hints
1. Start a quiz as usual.
2. Look for the **"Hints used: 0/5"** counter at the top of the quiz interface.
3. Click the **"Show Hint"** button on any question to see the first hint.
4. If more hints are available, a new button will appear for that question.
5. Once the counter reaches **5/5**, all "Show Hint" buttons will disappear across the entire quiz.

---

## 🔍 Technical Architecture

This plugin follows WordPress and LearnDash best practices for performance and stability:

- **Data Storage:** Uses WordPress `post_meta` for question hints and `user_meta` for real-time tracking of quiz attempts.
- **AJAX Driven:** Hint reveals are handled via secure AJAX calls (`wp_ajax_ldh_use_hint`) to ensure a smooth user experience without page reloads.
- **Security:** Rigorous input sanitization (`sanitize_textarea_field`) and output escaping (`wp_kses_post`) are applied throughout.
- **Localization:** Fully translation-ready with `.pot` files included in the `/languages` directory.

### Project Directory Structure
```text
learndash-hints-plugin/
├── assets/                 # CSS and JavaScript
│   ├── css/                # Frontend & Admin UI styling
│   └── js/                 # Sequential logic & AJAX handlers
├── includes/               # Core PHP Logic
│   ├── class-plugin.php    # Plugin bootstrap & dependency checks
│   ├── class-admin.php     # Question editor meta boxes
│   └── class-frontend.php  # AJAX endpoints & quiz rendering
├── languages/              # Translation files
├── specs/                  # Detailed technical specifications
└── learndash-hints-plugin.php # Main entry point
```

---

## 🤝 Contributing

We welcome contributions! Whether it's reporting a bug, suggesting a feature, or submitting a pull request, please see our [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

1. Fork the Project.
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`).
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the Branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

---

## 📄 License

Distributed under the **GPL-2.0+ License**. See [LICENSE](LICENSE) for more information.

---

## 🌟 Support & Feedback

If you find this plugin useful, please consider:
- Giving the project a ⭐ on GitHub.
- Opening an [Issue](https://github.com/zeeshan-sshaikh/LearnDash-Sequential-Hints/issues) for any bugs or feature requests.

*Maintained by [Zeeshan Shaikh](https://github.com/zeeshan-sshaikh)*
