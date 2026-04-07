<?php
/**
 * Plugin Name: LearnDash Sequential Hints
 * Plugin URI: https://github.com/zeeshan-sshaikh/LearnDash-Sequential-Hints
 * Description: Adds professional tiered hints (up to 3 per question) to LearnDash quizzes with a quiz-wide hint budget and real-time counter.
 * Version: 1.0.0
 * Author: Zeeshan
 * License: GPL-2.0+
 * Requires Plugins: sfwd-lms
 * Requires PHP: 7.4
 * Text Domain: learndash-hints
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LDH_PLUGIN_FILE', __FILE__ );
define( 'LDH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LDH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LDH_VERSION', '1.0.0' );

require_once LDH_PLUGIN_DIR . 'includes/class-plugin.php';

new LearnDash_Hints_Plugin();
