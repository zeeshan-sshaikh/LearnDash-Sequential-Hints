<?php
/**
 * Frontend: quiz hint rendering, AJAX handlers, state management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LearnDash_Hints_Frontend {

	const MAX_BUDGET = 5;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_ldh_use_hint', array( $this, 'ajax_use_hint' ) );
		add_action( 'wp_ajax_ldh_get_hint_state', array( $this, 'ajax_get_hint_state' ) );
		add_action( 'wp_ajax_ldh_reset_hint_state', array( $this, 'ajax_reset_hint_state' ) );

		// Quiz saving integration.
		add_filter( 'learndash_quiz_resume_data', array( $this, 'inject_hint_state_into_resume' ), 10, 3 );
	}

	/**
	 * Enqueue frontend assets on quiz pages with hint data.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_learndash_quiz_page() ) {
			return;
		}

		$quiz_id = get_the_ID();

		wp_enqueue_style(
			'ldh-quiz-style',
			LDH_PLUGIN_URL . 'assets/css/quiz.css',
			array(),
			LDH_VERSION
		);

		wp_enqueue_script(
			'ldh-quiz-hints',
			LDH_PLUGIN_URL . 'assets/js/quiz-hints.js',
			array( 'jquery' ),
			LDH_VERSION,
			true
		);

		$questions_data = $this->collect_question_hints( $quiz_id );

		$user_id       = get_current_user_id();
		$initial_state = null;
		if ( $user_id ) {
			// Check if quiz is being resumed (LearnDash quiz saving) or is a new attempt.
			$is_resume = $this->is_quiz_resume( $user_id, $quiz_id );

			if ( $is_resume ) {
				$initial_state = $this->get_hint_state( $user_id, $quiz_id );
			} else {
				// New attempt: clear any old hint state.
				delete_user_meta( $user_id, '_ldh_quiz_hint_state_' . $quiz_id );
				$initial_state = null;
			}
		}

		wp_localize_script( 'ldh-quiz-hints', 'ldhQuizData', array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'ldh_quiz_nonce' ),
			'quizId'        => $quiz_id,
			'maxBudget'     => self::MAX_BUDGET,
			'questions'     => array_values( $questions_data ),
			'initialState'  => $initial_state,
			'i18n'          => array(
				'showHint'  => esc_html__( 'Show Hint', 'learndash-hints' ),
				'hintsUsed' => esc_html__( 'Hints used:', 'learndash-hints' ),
			),
		) );
	}

	/**
	 * Collect all non-empty hints for questions in a quiz.
	 *
	 * Returns an ordered array of question data, each containing:
	 *   - postId: WordPress question post ID (for AJAX calls)
	 *   - hints: array of non-empty hint texts in sequential order
	 *
	 * @param int $quiz_id The quiz post ID.
	 * @return array Ordered array of question hint data.
	 */
	private function collect_question_hints( $quiz_id ) {
		$result = array();

		// Get the WpProQuiz internal quiz ID.
		$pro_quiz_id = $this->get_pro_quiz_id( $quiz_id );
		if ( ! $pro_quiz_id ) {
			return $result;
		}

		// Load questions via WpProQuiz mapper (preserves quiz question order).
		if ( ! class_exists( 'WpProQuiz_Model_QuestionMapper' ) ) {
			return $result;
		}

		$mapper        = new WpProQuiz_Model_QuestionMapper();
		$pro_questions = $mapper->fetchAll( $pro_quiz_id );

		if ( empty( $pro_questions ) ) {
			return $result;
		}

		foreach ( $pro_questions as $pro_question ) {
			$question_hints = array();

			// Hint 1: from WpProQuiz tip message.
			if ( $pro_question->isTipEnabled() ) {
				$tip = $pro_question->getTipMsg();
				if ( ! empty( $tip ) ) {
					$question_hints[] = $tip;
				}
			}

			// Find the WordPress post ID for this WpProQuiz question.
			$question_post_id = $this->get_question_post_id( $pro_question->getId() );

			if ( $question_post_id ) {
				// Hint 2: from our plugin meta.
				$hint_2 = get_post_meta( $question_post_id, '_ldh_hint_2', true );
				if ( ! empty( $hint_2 ) && ! empty( $question_hints ) ) {
					$question_hints[] = $hint_2;
				}

				// Hint 3: from our plugin meta (only if Hint 2 was non-empty).
				$hint_3 = get_post_meta( $question_post_id, '_ldh_hint_3', true );
				if ( ! empty( $hint_3 ) && count( $question_hints ) >= 2 ) {
					$question_hints[] = $hint_3;
				}
			}

			// Always include the entry (even if no hints) to maintain index alignment with DOM.
			$result[] = array(
				'postId' => $question_post_id ? $question_post_id : 0,
				'hints'  => $question_hints,
			);
		}

		return $result;
	}

	/**
	 * Get the WpProQuiz internal quiz ID from a LearnDash quiz post.
	 *
	 * @param int $quiz_id WordPress quiz post ID.
	 * @return int|false Pro quiz ID, or false if not found.
	 */
	private function get_pro_quiz_id( $quiz_id ) {
		// Method 1: Direct meta key.
		$pro_quiz_id = get_post_meta( $quiz_id, 'quiz_pro_id', true );
		if ( ! empty( $pro_quiz_id ) ) {
			return (int) $pro_quiz_id;
		}

		// Method 2: From serialized quiz settings.
		$quiz_settings = get_post_meta( $quiz_id, '_sfwd-quiz', true );
		if ( is_array( $quiz_settings ) ) {
			if ( isset( $quiz_settings['sfwd-quiz_quiz_pro'] ) && ! empty( $quiz_settings['sfwd-quiz_quiz_pro'] ) ) {
				return (int) $quiz_settings['sfwd-quiz_quiz_pro'];
			}
		}

		return false;
	}

	/**
	 * Find the WordPress sfwd-question post ID for a WpProQuiz question ID.
	 *
	 * @param int $pro_question_id WpProQuiz internal question ID.
	 * @return int Post ID, or 0 if not found.
	 */
	private function get_question_post_id( $pro_question_id ) {
		// Use LearnDash helper if available.
		if ( function_exists( 'learndash_get_question_post_by_pro_id' ) ) {
			$post = learndash_get_question_post_by_pro_id( $pro_question_id );
			if ( $post && isset( $post->ID ) ) {
				return $post->ID;
			}
		}

		// Fallback: query by meta.
		$posts = get_posts( array(
			'post_type'      => 'sfwd-question',
			'posts_per_page' => 1,
			'meta_key'       => 'question_pro_id',
			'meta_value'     => $pro_question_id,
			'fields'         => 'ids',
		) );

		return ! empty( $posts ) ? $posts[0] : 0;
	}

	/**
	 * Get hint state for a user's quiz attempt.
	 */
	public function get_hint_state( $user_id, $quiz_id ) {
		$state = get_user_meta( $user_id, '_ldh_quiz_hint_state_' . $quiz_id, true );

		if ( empty( $state ) || ! is_array( $state ) ) {
			return array(
				'quiz_id'    => (int) $quiz_id,
				'total_used' => 0,
				'started_at' => time(),
				'questions'  => array(),
			);
		}

		return $state;
	}

	/**
	 * Save hint state for a user's quiz attempt.
	 */
	public function save_hint_state( $user_id, $quiz_id, $state ) {
		update_user_meta( $user_id, '_ldh_quiz_hint_state_' . $quiz_id, $state );
	}

	/**
	 * AJAX handler: use a hint on a question.
	 */
	public function ajax_use_hint() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ldh_quiz_nonce' ) ) {
			wp_send_json_error( array( 'code' => 'invalid_nonce', 'message' => __( 'Invalid security token.', 'learndash-hints' ) ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'code' => 'not_logged_in', 'message' => __( 'You must be logged in.', 'learndash-hints' ) ) );
		}

		$quiz_id     = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;

		if ( ! $quiz_id || ! $question_id ) {
			wp_send_json_error( array( 'code' => 'invalid_params', 'message' => __( 'Missing quiz or question ID.', 'learndash-hints' ) ) );
		}

		// Load current state.
		$state = $this->get_hint_state( $user_id, $quiz_id );

		// Budget check.
		if ( $state['total_used'] >= self::MAX_BUDGET ) {
			wp_send_json_error( array( 'code' => 'budget_exhausted', 'message' => __( 'Hint budget exhausted for this quiz.', 'learndash-hints' ) ) );
		}

		// Determine next hint level for this question.
		$current_level = isset( $state['questions'][ $question_id ] ) ? (int) $state['questions'][ $question_id ] : 0;
		$next_level    = $current_level + 1;

		// Load hint text for the next level.
		$hint_text = $this->get_hint_text( $question_id, $next_level );

		if ( empty( $hint_text ) ) {
			wp_send_json_error( array( 'code' => 'no_hint_available', 'message' => __( 'No more hints available for this question.', 'learndash-hints' ) ) );
		}

		// Update state.
		$state['total_used']++;
		$state['questions'][ $question_id ] = $next_level;

		$this->save_hint_state( $user_id, $quiz_id, $state );

		// Determine if next hint is available.
		$next_hint_text      = $this->get_hint_text( $question_id, $next_level + 1 );
		$next_hint_available = ! empty( $next_hint_text ) && $state['total_used'] < self::MAX_BUDGET;
		$budget_exhausted    = $state['total_used'] >= self::MAX_BUDGET;

		wp_send_json_success( array(
			'hint_text'           => wp_kses_post( $hint_text ),
			'hint_level'          => $next_level,
			'total_used'          => $state['total_used'],
			'next_hint_available' => $next_hint_available,
			'budget_exhausted'    => $budget_exhausted,
		) );
	}

	/**
	 * Get hint text for a question at a specific level.
	 *
	 * @param int $question_id WordPress question post ID.
	 * @param int $level       Hint level (1, 2, or 3).
	 * @return string Hint text, or empty if not available.
	 */
	private function get_hint_text( $question_id, $level ) {
		switch ( $level ) {
			case 1:
				return $this->get_pro_quiz_tip( $question_id );
			case 2:
				return (string) get_post_meta( $question_id, '_ldh_hint_2', true );
			case 3:
				return (string) get_post_meta( $question_id, '_ldh_hint_3', true );
			default:
				return '';
		}
	}

	/**
	 * Get the WpProQuiz tip message for a question post.
	 *
	 * @param int $question_id WordPress question post ID.
	 * @return string Tip text, or empty if not available.
	 */
	private function get_pro_quiz_tip( $question_id ) {
		$pro_question_id = get_post_meta( $question_id, 'question_pro_id', true );
		if ( empty( $pro_question_id ) ) {
			return '';
		}

		if ( ! class_exists( 'WpProQuiz_Model_QuestionMapper' ) ) {
			return '';
		}

		$mapper       = new WpProQuiz_Model_QuestionMapper();
		$pro_question = $mapper->fetchById( (int) $pro_question_id );

		if ( $pro_question && $pro_question->isTipEnabled() ) {
			$tip = $pro_question->getTipMsg();
			return ! empty( $tip ) ? $tip : '';
		}

		return '';
	}

	/**
	 * Inject hint state into LearnDash quiz resume data.
	 */
	public function inject_hint_state_into_resume( $resume_data, $quiz_id, $user_id ) {
		if ( ! is_array( $resume_data ) ) {
			$resume_data = array();
		}

		$state = $this->get_hint_state( $user_id, $quiz_id );
		$resume_data['ldh_hint_state'] = $state;

		return $resume_data;
	}

	/**
	 * AJAX handler: get current hint state with revealed hint texts (for quiz resume).
	 */
	public function ajax_get_hint_state() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ldh_quiz_nonce' ) ) {
			wp_send_json_error( array( 'code' => 'invalid_nonce', 'message' => __( 'Invalid security token.', 'learndash-hints' ) ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'code' => 'not_logged_in', 'message' => __( 'You must be logged in.', 'learndash-hints' ) ) );
		}

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		if ( ! $quiz_id ) {
			wp_send_json_error( array( 'code' => 'invalid_params', 'message' => __( 'Missing quiz ID.', 'learndash-hints' ) ) );
		}

		$state = $this->get_hint_state( $user_id, $quiz_id );

		$questions_data = array();
		if ( ! empty( $state['questions'] ) ) {
			foreach ( $state['questions'] as $question_id => $reveal_level ) {
				$reveal_level = (int) $reveal_level;
				if ( $reveal_level <= 0 ) {
					continue;
				}

				$hints = array();
				for ( $level = 1; $level <= $reveal_level; $level++ ) {
					$text = $this->get_hint_text( (int) $question_id, $level );
					if ( ! empty( $text ) ) {
						$hints[] = wp_kses_post( $text );
					}
				}

				$questions_data[ $question_id ] = array(
					'reveal_level' => $reveal_level,
					'hints'        => $hints,
				);
			}
		}

		wp_send_json_success( array(
			'total_used'       => $state['total_used'],
			'budget_exhausted' => $state['total_used'] >= self::MAX_BUDGET,
			'questions'        => $questions_data,
		) );
	}

	/**
	 * AJAX handler: reset hint state (for new quiz attempt).
	 */
	public function ajax_reset_hint_state() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ldh_quiz_nonce' ) ) {
			wp_send_json_error( array( 'code' => 'invalid_nonce', 'message' => __( 'Invalid security token.', 'learndash-hints' ) ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'code' => 'not_logged_in', 'message' => __( 'You must be logged in.', 'learndash-hints' ) ) );
		}

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		if ( ! $quiz_id ) {
			wp_send_json_error( array( 'code' => 'invalid_params', 'message' => __( 'Missing quiz ID.', 'learndash-hints' ) ) );
		}

		delete_user_meta( $user_id, '_ldh_quiz_hint_state_' . $quiz_id );

		wp_send_json_success( array(
			'total_used'       => 0,
			'budget_exhausted' => false,
		) );
	}

	/**
	 * Check if the current quiz page load is a resume (saved progress exists)
	 * rather than a fresh new attempt.
	 *
	 * @param int $user_id User ID.
	 * @param int $quiz_id Quiz post ID.
	 * @return bool True if resuming saved progress.
	 */
	private function is_quiz_resume( $user_id, $quiz_id ) {
		// Check if LearnDash quiz saving/resume is enabled and has saved data.
		$pro_quiz_id = $this->get_pro_quiz_id( $quiz_id );
		if ( ! $pro_quiz_id ) {
			return false;
		}

		// LearnDash stores resume data in user meta keyed by pro quiz ID.
		$resume_data = get_user_meta( $user_id, 'learndash_quiz_resume_' . $pro_quiz_id, true );
		if ( ! empty( $resume_data ) ) {
			return true;
		}

		// Also check alternate key format.
		$resume_data = get_user_meta( $user_id, 'quiz_resume_' . $pro_quiz_id, true );
		if ( ! empty( $resume_data ) ) {
			return true;
		}

		return false;
	}

	private function is_learndash_quiz_page() {
		return is_singular( 'sfwd-quiz' );
	}
}
