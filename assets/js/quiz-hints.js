(function ($) {
	'use strict';

	var hintsUsed = 0;
	var maxBudget = 5;
	var quizId = 0;
	var initialized = false;

	$(document).ready(function () {
		if (typeof ldhQuizData === 'undefined') {
			return;
		}

		maxBudget = parseInt(ldhQuizData.maxBudget, 10) || 5;
		quizId = parseInt(ldhQuizData.quizId, 10) || 0;

		// Try to initialize immediately if quiz elements exist.
		tryInit();

		// Also watch for quiz elements appearing later (LearnDash may render after DOM ready).
		observeQuizReady();
	});

	/**
	 * Attempt initialization if quiz list items are present.
	 */
	function tryInit() {
		if (initialized) {
			return;
		}

		var $listItems = $('.wpProQuiz_listItem');
		if ($listItems.length === 0) {
			return;
		}

		initialized = true;

		// Reset hints for fresh state (initialState is null on new attempts).
		if (!ldhQuizData.initialState || !ldhQuizData.initialState.total_used) {
			hintsUsed = 0;
		} else {
			hintsUsed = parseInt(ldhQuizData.initialState.total_used, 10);
		}

		// Mark quiz wrapper so CSS can target it.
		$('.wpProQuiz_content').addClass('ldh-active');

		// Hide default WpProQuiz hint buttons and tip divs.
		$('.wpProQuiz_TipButton').hide();
		$('.wpProQuiz_tipp').hide();

		// Inject the hint counter.
		initCounter();

		// If resuming with prior hint usage, fetch full state from server.
		if (ldhQuizData.initialState && ldhQuizData.initialState.total_used > 0) {
			restoreFromServer();
		} else {
			initHintButtons();
		}

		// If budget already exhausted, hide all buttons.
		if (hintsUsed >= maxBudget) {
			$('.ldh-hint-btn').remove();
			$('.ldh-hint-counter').addClass('ldh-budget-exhausted');
		}
	}

	/**
	 * Watch for quiz content to appear in the DOM.
	 * LearnDash sometimes renders quiz questions after a "Start Quiz" click.
	 */
	function observeQuizReady() {
		if (initialized) {
			return;
		}

		// Poll for quiz list items (simple and reliable).
		var attempts = 0;
		var maxAttempts = 60; // 30 seconds max.
		var pollInterval = setInterval(function () {
			attempts++;
			if (initialized || attempts >= maxAttempts) {
				clearInterval(pollInterval);
				return;
			}
			tryInit();
		}, 500);

		// Also listen for LearnDash quiz start events.
		$(document).on('learndash-quiz-started', function () {
			setTimeout(tryInit, 300);
		});

		// Listen for wpProQuiz init.
		$(document).on('wpProQuiz_initComplete', function () {
			setTimeout(tryInit, 300);
		});
	}

	/**
	 * Restore hint state from server (for quiz resume).
	 */
	function restoreFromServer() {
		$.ajax({
			url: ldhQuizData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ldh_get_hint_state',
				nonce: ldhQuizData.nonce,
				quiz_id: quizId
			},
			success: function (response) {
				if (response.success && response.data) {
					var serverState = response.data;
					hintsUsed = parseInt(serverState.total_used, 10) || 0;

					if (serverState.questions) {
						ldhQuizData.initialState = ldhQuizData.initialState || {};
						ldhQuizData.initialState.questions = {};

						for (var qid in serverState.questions) {
							if (serverState.questions.hasOwnProperty(qid)) {
								ldhQuizData.initialState.questions[qid] = serverState.questions[qid].reveal_level;
							}
						}
					}

					initHintButtons(serverState.questions);
					updateCounter();

					if (hintsUsed >= maxBudget) {
						$('.ldh-hint-btn').remove();
						$('.ldh-hint-counter').addClass('ldh-budget-exhausted');
					}
				} else {
					initHintButtons();
				}
			},
			error: function () {
				initHintButtons();
			}
		});
	}

	/**
	 * Inject the hint counter element into the quiz.
	 */
	function initCounter() {
		// Avoid duplicate counters.
		if ($('.ldh-hint-counter').length > 0) {
			return;
		}

		var label = (ldhQuizData.i18n && ldhQuizData.i18n.hintsUsed) ? ldhQuizData.i18n.hintsUsed : 'Hints used:';
		var $counter = $('<div class="ldh-hint-counter">' + label + ' ' + hintsUsed + '/' + maxBudget + '</div>');

		var $quizContent = $('.wpProQuiz_content');
		if ($quizContent.length) {
			var $questionList = $quizContent.find('.wpProQuiz_list').first();
			if ($questionList.length) {
				$questionList.before($counter);
			} else {
				$quizContent.prepend($counter);
			}
		}
	}

	/**
	 * Initialize hint buttons for all questions with hints.
	 * Uses index-based matching: ldhQuizData.questions[i] corresponds to .wpProQuiz_listItem[i].
	 *
	 * @param {Object} serverQuestions Optional server-returned question data with hint texts (for resume).
	 */
	function initHintButtons(serverQuestions) {
		if (!ldhQuizData.questions || !ldhQuizData.questions.length) {
			return;
		}

		$('.wpProQuiz_listItem').each(function (index) {
			var $questionItem = $(this);

			// Skip if already initialized.
			if ($questionItem.find('.ldh-hint-container').length > 0) {
				return;
			}

			if (index >= ldhQuizData.questions.length) {
				return;
			}

			var questionData = ldhQuizData.questions[index];
			var postId = parseInt(questionData.postId, 10) || 0;
			var hints = questionData.hints;

			if (!hints || hints.length === 0 || !postId) {
				return;
			}

			// Determine current reveal level from initial state.
			var revealLevel = 0;
			if (ldhQuizData.initialState && ldhQuizData.initialState.questions &&
				ldhQuizData.initialState.questions[postId]) {
				revealLevel = parseInt(ldhQuizData.initialState.questions[postId], 10);
			}

			// Create a container for hints.
			var $hintContainer = $('<div class="ldh-hint-container" data-question-id="' + postId + '"></div>');

			// Show previously revealed hints.
			var revealedHints = hints;
			if (serverQuestions && serverQuestions[postId] && serverQuestions[postId].hints) {
				revealedHints = serverQuestions[postId].hints;
			}

			for (var i = 0; i < revealLevel && i < revealedHints.length; i++) {
				$hintContainer.append(
					'<div class="ldh-hint-content">' + revealedHints[i] + '</div>'
				);
			}

			// Show next hint button if more hints available and budget allows.
			if (revealLevel < hints.length && hintsUsed < maxBudget) {
				$hintContainer.append(createHintButton(postId));
			}

			// Insert after the question response area.
			var $responseArea = $questionItem.find('.wpProQuiz_response');
			if ($responseArea.length) {
				$responseArea.after($hintContainer);
			} else {
				$questionItem.append($hintContainer);
			}
		});
	}

	/**
	 * Create a hint button element.
	 */
	function createHintButton(questionId) {
		var label = (ldhQuizData.i18n && ldhQuizData.i18n.showHint) ? ldhQuizData.i18n.showHint : 'Show Hint';
		return $('<button type="button" class="ldh-hint-btn" data-question-id="' + questionId + '">' + label + '</button>');
	}

	/**
	 * Handle hint button click via AJAX.
	 */
	$(document).on('click', '.ldh-hint-btn', function (e) {
		e.preventDefault();

		var $button = $(this);
		var questionId = $button.data('question-id');

		if (!questionId || hintsUsed >= maxBudget) {
			return;
		}

		$button.prop('disabled', true);

		$.ajax({
			url: ldhQuizData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'ldh_use_hint',
				nonce: ldhQuizData.nonce,
				quiz_id: quizId,
				question_id: questionId
			},
			success: function (response) {
				if (response.success) {
					var data = response.data;
					var $container = $button.closest('.ldh-hint-container');

					$('<div class="ldh-hint-content">' + data.hint_text + '</div>').insertBefore($button);
					$button.remove();

					hintsUsed = parseInt(data.total_used, 10);
					updateCounter();

					if (data.budget_exhausted) {
						$('.ldh-hint-btn').remove();
					} else if (data.next_hint_available) {
						$container.append(createHintButton(questionId));
					}
				} else {
					$button.prop('disabled', false);
				}
			},
			error: function () {
				$button.prop('disabled', false);
			}
		});
	});

	/**
	 * Update the hint counter display.
	 */
	function updateCounter() {
		var label = (ldhQuizData.i18n && ldhQuizData.i18n.hintsUsed) ? ldhQuizData.i18n.hintsUsed : 'Hints used:';
		var $counter = $('.ldh-hint-counter');
		$counter.text(label + ' ' + hintsUsed + '/' + maxBudget);

		if (hintsUsed >= maxBudget) {
			$counter.addClass('ldh-budget-exhausted');
		} else {
			$counter.removeClass('ldh-budget-exhausted');
		}
	}

})(jQuery);
