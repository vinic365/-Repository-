/**
 * Quiz Interativo – Frontend JS
 * Vanilla JS, no jQuery dependency.
 * Handles multi-step quiz navigation with page transitions.
 */
(function () {
	'use strict';

	/**
	 * Initialize all quizzes on the page.
	 */
	function initAll() {
		document.querySelectorAll('.qi-quiz').forEach(initQuiz);
	}

	/**
	 * Initialize a single quiz instance.
	 * @param {HTMLElement} quiz
	 */
	function initQuiz(quiz) {
		const pages   = Array.from(quiz.querySelectorAll('.qi-page'));
		const options = quiz.querySelectorAll('.qi-option');
		const total   = pages.length;

		if (!pages.length) return;

		// Lock page scroll – quiz is fixed/fullscreen.
		document.documentElement.classList.add('qi-noscroll');
		document.body.classList.add('qi-noscroll');

		// Ensure only first page is active on load.
		pages.forEach(function (page, i) {
			if (i === 0) {
				page.classList.add('qi-page-active');
				page.setAttribute('aria-hidden', 'false');
			} else {
				page.classList.remove('qi-page-active');
				page.setAttribute('aria-hidden', 'true');
			}
		});

		// Stagger emoji float animations so they don't all move in sync.
		quiz.querySelectorAll('.qi-option-icon').forEach(function (icon, i) {
			icon.style.animationDelay = (i * 0.4) + 's';
		});

		// Add optional progress bar.
		if (total > 1) {
			const progressWrap = document.createElement('div');
			progressWrap.className = 'qi-progress';
			progressWrap.setAttribute('role', 'progressbar');
			progressWrap.setAttribute('aria-valuenow', '1');
			progressWrap.setAttribute('aria-valuemin', '1');
			progressWrap.setAttribute('aria-valuemax', String(total));
			progressWrap.setAttribute('aria-label', 'Progresso do quiz');

			const progressBar = document.createElement('div');
			progressBar.className = 'qi-progress-bar';
			progressBar.style.width = (1 / total * 100) + '%';

			progressWrap.appendChild(progressBar);
			quiz.insertBefore(progressWrap, quiz.firstChild);

			quiz._progressBar  = progressBar;
			quiz._progressWrap = progressWrap;
		}

		quiz._pages        = pages;
		quiz._currentPage  = 0;
		quiz._total        = total;

		// Bind option clicks.
		options.forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				handleOptionClick(quiz, btn);
			});

			// Keyboard: Enter / Space
			btn.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					handleOptionClick(quiz, btn);
				}
			});
		});
	}

	/**
	 * Handle option click: navigate or redirect.
	 * @param {HTMLElement} quiz
	 * @param {HTMLElement} btn
	 */
	function handleOptionClick(quiz, btn) {
		const action = btn.dataset.action;

		if (action === 'url') {
			const url    = btn.dataset.url;
			const target = btn.dataset.target || '_self';
			if (url) {
				// Brief visual feedback before navigation.
				btn.classList.add('qi-loading');
				setTimeout(function () {
					if (target === '_blank') {
						window.open(url, '_blank', 'noopener,noreferrer');
						btn.classList.remove('qi-loading');
					} else {
						window.location.href = url;
					}
				}, 150);
			}
			return;
		}

		if (action === 'page') {
			const targetIndex = parseInt(btn.dataset.target, 10);
			if (!isNaN(targetIndex) && targetIndex >= 0 && targetIndex < quiz._total) {
				navigateTo(quiz, targetIndex);
			}
		}
	}

	/**
	 * Navigate to a specific page index.
	 * @param {HTMLElement} quiz
	 * @param {number}      index
	 */
	function navigateTo(quiz, index) {
		const pages   = quiz._pages;
		const current = quiz._currentPage;

		if (index === current) return;

		const $currentPage = pages[current];
		const $nextPage    = pages[index];

		if (!$nextPage) return;

		// Hide current.
		$currentPage.classList.remove('qi-page-active');
		$currentPage.setAttribute('aria-hidden', 'true');

		// Show next with animation.
		$nextPage.classList.add('qi-page-active', 'qi-slide-in');
		$nextPage.setAttribute('aria-hidden', 'false');

		// Remove animation class after it ends.
		$nextPage.addEventListener('animationend', function handler() {
			$nextPage.classList.remove('qi-slide-in');
			$nextPage.removeEventListener('animationend', handler);
		});

		quiz._currentPage = index;

		// Update progress bar.
		if (quiz._progressBar) {
			const pct = ((index + 1) / quiz._total) * 100;
			quiz._progressBar.style.width = pct + '%';
			quiz._progressWrap.setAttribute('aria-valuenow', String(index + 1));
		}

		// Restagger emoji delays for new page.
		$nextPage.querySelectorAll('.qi-option-icon').forEach(function (icon, i) {
			icon.style.animationDelay = (i * 0.4) + 's';
		});
	}

	// Init on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

}());
