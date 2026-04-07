<?php
/**
 * Admin: meta box for Hint 2 & Hint 3 on question editor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LearnDash_Hints_Admin {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_sfwd-question', array( $this, 'save_hint_fields' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'sfwd-question' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'ldh-admin-style',
			LDH_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LDH_VERSION
		);
	}

	public function register_meta_box() {
		add_meta_box(
			'ldh_additional_hints',
			esc_html__( 'Additional Hints', 'learndash-hints' ),
			array( $this, 'render_meta_box' ),
			'sfwd-question',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( 'ldh_save_hints_' . $post->ID, 'ldh_hints_nonce' );

		$hint_2 = get_post_meta( $post->ID, '_ldh_hint_2', true );
		$hint_3 = get_post_meta( $post->ID, '_ldh_hint_3', true );
		?>
		<div class="ldh-hint-fields">
			<p>
				<label for="ldh_hint_2"><strong><?php echo esc_html__( 'Hint 2', 'learndash-hints' ); ?></strong></label>
				<br />
				<textarea id="ldh_hint_2" name="ldh_hint_2" class="large-text" rows="3"><?php echo esc_textarea( $hint_2 ); ?></textarea>
				<span class="description"><?php echo esc_html__( 'This hint will be shown after Hint 1 is used.', 'learndash-hints' ); ?></span>
			</p>
			<p>
				<label for="ldh_hint_3"><strong><?php echo esc_html__( 'Hint 3', 'learndash-hints' ); ?></strong></label>
				<br />
				<textarea id="ldh_hint_3" name="ldh_hint_3" class="large-text" rows="3"><?php echo esc_textarea( $hint_3 ); ?></textarea>
				<span class="description"><?php echo esc_html__( 'This hint will be shown after Hint 2 is used.', 'learndash-hints' ); ?></span>
			</p>
		</div>
		<?php
	}

	public function save_hint_fields( $post_id, $post ) {
		// Skip autosave and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['ldh_hints_nonce'] ) || ! wp_verify_nonce( $_POST['ldh_hints_nonce'], 'ldh_save_hints_' . $post_id ) ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize and save.
		$hint_2 = isset( $_POST['ldh_hint_2'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ldh_hint_2'] ) ) : '';
		$hint_3 = isset( $_POST['ldh_hint_3'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ldh_hint_3'] ) ) : '';

		update_post_meta( $post_id, '_ldh_hint_2', $hint_2 );
		update_post_meta( $post_id, '_ldh_hint_3', $hint_3 );
	}
}
