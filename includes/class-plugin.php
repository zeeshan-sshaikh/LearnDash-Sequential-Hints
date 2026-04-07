<?php
/**
 * Core plugin class: dependency check, initialization, i18n.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LearnDash_Hints_Plugin {

	public function __construct() {
		register_activation_hook( LDH_PLUGIN_FILE, array( $this, 'on_activation' ) );
		register_deactivation_hook( LDH_PLUGIN_FILE, array( $this, 'on_deactivation' ) );

		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function on_activation() {
		if ( ! $this->is_learndash_active() ) {
			wp_die(
				esc_html__( 'LearnDash Sequential Hints requires LearnDash LMS to be installed and activated.', 'learndash-hints' ),
				esc_html__( 'Plugin Activation Error', 'learndash-hints' ),
				array( 'back_link' => true )
			);
		}
	}

	public function on_deactivation() {
		// No cleanup needed; preserve user hint data.
	}

	public function init() {
		load_plugin_textdomain( 'learndash-hints', false, dirname( plugin_basename( LDH_PLUGIN_FILE ) ) . '/languages' );

		if ( ! $this->is_learndash_active() ) {
			add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
			return;
		}

		require_once LDH_PLUGIN_DIR . 'includes/class-admin.php';
		require_once LDH_PLUGIN_DIR . 'includes/class-frontend.php';

		new LearnDash_Hints_Admin();
		new LearnDash_Hints_Frontend();
	}

	private function is_learndash_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'sfwd-lms/sfwd_lms.php' );
	}

	public function dependency_notice() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'LearnDash Sequential Hints requires LearnDash LMS to be installed and activated.', 'learndash-hints' );
		echo '</p></div>';
	}
}
