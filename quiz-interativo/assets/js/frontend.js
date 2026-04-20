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
		// Negative delays start the animation already in-progress at different
		// offsets (0.65s ≈ 25% of the 2.6s cycle), avoiding the race condition
		// where positive delays are ignored on animations that already started.
		quiz.querySelectorAll('.qi-option-icon').forEach(function (icon, i) {
			var delay = '-' + (i * 0.65) + 's';
			icon.style.webkitAnimationDelay = delay;
			icon.style.animationDelay = delay;
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

			// Touch: trigger bounce animation on tap (hover doesn't fire on mobile).
			btn.addEventListener('touchstart', function () {
				var icon = btn.querySelector('.qi-option-icon');
				if (!icon) return;

				// Remove any previous animationend listener before adding a new one.
				// Without this, each tap that ends before the 0.6s animation completes
				// (e.g. when navigation happens) leaves an orphaned listener on the icon.
				// On the next visit to this page the count grows, causing memory leaks and
				// incorrect "qi-icon-bounce" removal at unexpected times.
				if (icon._bounceHandler) {
					icon.removeEventListener('animationend', icon._bounceHandler);
					icon._bounceHandler = null;
				}

				icon.classList.remove('qi-icon-bounce', 'qi-icon-pop');
				// Force reflow so removing + re-adding class restarts animation.
				void icon.offsetWidth;
				icon.classList.add('qi-icon-bounce');

				icon._bounceHandler = function () {
					icon.classList.remove('qi-icon-bounce');
					icon.removeEventListener('animationend', icon._bounceHandler);
					icon._bounceHandler = null;
				};
				icon.addEventListener('animationend', icon._bounceHandler);
			}, { passive: true });

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

		// Guard: $currentPage must exist (pages array could theoretically be stale).
		if (!$currentPage) return;

		// Hide current.
		$currentPage.classList.remove('qi-page-active');
		$currentPage.setAttribute('aria-hidden', 'true');

		// Restagger emoji delays BEFORE the page becomes visible.
		// Must happen while display:none so animations haven't started yet;
		// setting animationDelay on an already-running animation has no effect.
		$nextPage.querySelectorAll('.qi-option-icon').forEach(function (icon, i) {
			var delay = '-' + (i * 0.65) + 's';
			icon.style.webkitAnimationDelay = delay;
			icon.style.animationDelay = delay;
		});

		// Show next with animation.
		$nextPage.classList.add('qi-page-active', 'qi-slide-in');
		$nextPage.setAttribute('aria-hidden', 'false');

		// Remove any previous slide-in listener before adding a new one.
		// Without this, navigating to the same page multiple times stacks listeners:
		// each round-trip (1→2→1→2) adds another handler to page 2, causing
		// premature qi-slide-in removal and growing memory leaks.
		if ($nextPage._slideHandler) {
			$nextPage.removeEventListener('animationend', $nextPage._slideHandler);
		}
		$nextPage._slideHandler = function (e) {
			// Filter by target to ignore animationend events bubbled from child icons,
			// and by animationName to ensure we only react to the slide-in animation.
			if (e.target !== $nextPage) return;
			if (e.animationName !== 'qiSlideIn') return;
			$nextPage.classList.remove('qi-slide-in');
			$nextPage.removeEventListener('animationend', $nextPage._slideHandler);
			$nextPage._slideHandler = null;
		};
		$nextPage.addEventListener('animationend', $nextPage._slideHandler);

		quiz._currentPage = index;

		// Update progress bar.
		if (quiz._progressBar) {
			const pct = ((index + 1) / quiz._total) * 100;
			quiz._progressBar.style.width = pct + '%';
			quiz._progressWrap.setAttribute('aria-valuenow', String(index + 1));
		}
	}

	// Init on DOM ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

}());
