/* global jQuery, quizInterativoAdmin */
(function ($) {
	'use strict';

	const Admin = {

		init: function () {
			this.bindEvents();
			this.initSortable();
			this.initColorPickers();
			this.syncPageNumbers();
		},

		/* --------------------------------------------------
		 * Event bindings
		 * -------------------------------------------------- */
		bindEvents: function () {
			// Save quiz
			$(document).on('click', '#qi-save-quiz', Admin.saveQuiz);

			// Status toggle label
			$(document).on('change', '#qi-quiz-status', function () {
				$('#qi-status-label').text(
					$(this).is(':checked')
						? quizInterativoAdmin.strings.saved.replace('!', '') && 'Ativo'
						: 'Inativo'
				);
				const label = $(this).is(':checked') ? 'Ativo' : 'Inativo';
				$('#qi-status-label').text(label);
			});

			// Add page
			$(document).on('click', '#qi-add-page', Admin.addPage);

			// Delete page
			$(document).on('click', '.qi-delete-page', Admin.deletePage);

			// Toggle page collapse
			$(document).on('click', '.qi-toggle-page', Admin.togglePage);

			// Add option
			$(document).on('click', '.qi-add-option', Admin.addOption);

			// Delete option
			$(document).on('click', '.qi-delete-option', Admin.deleteOption);

			// Delete quiz (list page)
			$(document).on('click', '.qi-delete-quiz', Admin.deleteQuiz);

			// Action type change
			$(document).on('change', '.qi-field-opt-action-type', Admin.onActionTypeChange);

			// Image picker
			$(document).on('click', '.qi-select-image', Admin.openMediaUploader);
			$(document).on('click', '.qi-remove-image', Admin.removeImage);

			// Add policy link
			$(document).on('click', '.qi-add-policy-link', Admin.addPolicyLink);
			$(document).on('click', '.qi-remove-policy-link', Admin.removePolicyLink);

			// Collapsible disclaimer section
			$(document).on('click', '.qi-section-toggle', function () {
				const $content = $(this).closest('.qi-section-collapse').find('.qi-section-content');
				$content.slideToggle(200);
				$(this).find('.dashicons')
					.toggleClass('dashicons-arrow-down-alt2', $content.is(':visible'))
					.toggleClass('dashicons-arrow-up-alt2', !$content.is(':visible'));
			});

			// Color input sync
			$(document).on('input', '.qi-color-wrap input[type="color"]', function () {
				$(this).siblings('.qi-color-text').val($(this).val());
			});
			$(document).on('change', '.qi-color-text', function () {
				const val = $(this).val();
				if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
					$(this).siblings('input[type="color"]').val(val);
				}
			});
		},

		/* --------------------------------------------------
		 * Save quiz
		 * -------------------------------------------------- */
		saveQuiz: function () {
			const $btn = $('#qi-save-quiz');
			const $msg = $('#qi-save-message');
			const quizId = $('#qi-editor').data('quiz-id') || 0;
			const title  = $('#qi-quiz-title').val().trim();

			if (!title) {
				$('#qi-quiz-title').focus().addClass('qi-input-error');
				$msg.removeClass('qi-success').addClass('qi-error').text('O nome do quiz é obrigatório.');
				return;
			}

			$('#qi-quiz-title').removeClass('qi-input-error');

			const pages = Admin.collectPages();
			const data  = {
				action:   'qi_save_quiz',
				nonce:    quizInterativoAdmin.nonce,
				quiz_id:  quizId,
				title:    title,
				status:   $('#qi-quiz-status').is(':checked') ? 'active' : 'inactive',
				pages:    pages,
			};

			$btn.prop('disabled', true).text('Salvando...');
			$msg.removeClass('qi-success qi-error').text('');

			$.post(quizInterativoAdmin.ajaxUrl, data)
				.done(function (res) {
					if (res.success) {
						$msg.addClass('qi-success').text(res.data.message || quizInterativoAdmin.strings.saved);
						// Update URL if new quiz was created.
						if (!quizId && res.data.quiz_id) {
							history.replaceState(null, '', res.data.redirect);
							$('#qi-editor').data('quiz-id', res.data.quiz_id);
						}
					} else {
						$msg.addClass('qi-error').text(res.data.message || quizInterativoAdmin.strings.error);
					}
				})
				.fail(function () {
					$msg.addClass('qi-error').text(quizInterativoAdmin.strings.error);
				})
				.always(function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Salvar Quiz');
					setTimeout(function () { $msg.text(''); }, 4000);
				});
		},

		/* --------------------------------------------------
		 * Collect pages data
		 * -------------------------------------------------- */
		collectPages: function () {
			const pages = [];
			$('#qi-pages-container .qi-page-block:not(.qi-template)').each(function () {
				const $page = $(this);
				const page  = {
					title:           $page.find('.qi-field-title').val(),
					subtitle:        $page.find('.qi-field-subtitle').val(),
					site_name:       $page.find('.qi-field-site-name').val(),
					btn_height:      $page.find('.qi-field-btn-height').val() || 72,
					image:           $page.find('.qi-field-image').val(),
					bg_color:        $page.find('.qi-field-bg-color').val(),
					btn_color:       $page.find('.qi-field-btn-color').val(),
					btn_hover_color: $page.find('.qi-field-btn-hover-color').val(),
					btn_text_color:  $page.find('.qi-field-btn-text-color').val(),
					disclaimer:      $page.find('.qi-field-disclaimer').val(),
					policy_links:    [],
					options:         [],
				};

				// Collect policy links
				$page.find('.qi-policy-link-row').each(function () {
					page.policy_links.push({
						label: $(this).find('.qi-field-policy-label').val(),
						url:   $(this).find('.qi-field-policy-url').val(),
					});
				});

				// Collect options
				$page.find('.qi-option-block:not(.qi-template)').each(function () {
					const $opt = $(this);
					page.options.push({
						text:          $opt.find('.qi-field-opt-text').val(),
						icon:          $opt.find('.qi-field-opt-icon').val(),
						btn_color:     $opt.find('.qi-field-opt-color').val(),
						btn_size:      $opt.find('.qi-field-opt-size').val(),
						action_type:   $opt.find('.qi-field-opt-action-type').val(),
						action_page:   $opt.find('.qi-field-opt-action-page').val(),
						action_url:    $opt.find('.qi-field-opt-action-url').val(),
						action_target: $opt.find('.qi-field-opt-action-target').val(),
					});
				});

				pages.push(page);
			});
			return pages;
		},

		/* --------------------------------------------------
		 * Add page
		 * -------------------------------------------------- */
		addPage: function () {
			const $template = $('.qi-page-template').first();
			if (!$template.length) return;

			const $container = $('#qi-pages-container');
			const index      = $container.children('.qi-page-block:not(.qi-template)').length;
			const $clone     = $template.clone();

			$clone.removeClass('qi-template qi-page-template')
				.removeAttr('style')
				.attr('data-page-index', index);

			$clone.find('.qi-page-num').text(index + 1);
			$container.append($clone);

			$('#qi-empty-pages').hide();
			Admin.initSortableOptions($clone);
			Admin.syncPageNumbers();

			// Scroll to new page
			$('html,body').animate({ scrollTop: $clone.offset().top - 80 }, 300);
		},

		/* --------------------------------------------------
		 * Delete page
		 * -------------------------------------------------- */
		deletePage: function () {
			if (!confirm(quizInterativoAdmin.strings.confirmDelPage)) return;
			$(this).closest('.qi-page-block').remove();
			Admin.syncPageNumbers();
			const remaining = $('#qi-pages-container .qi-page-block:not(.qi-template)').length;
			if (!remaining) $('#qi-empty-pages').show();
		},

		/* --------------------------------------------------
		 * Toggle page collapse
		 * -------------------------------------------------- */
		togglePage: function () {
			const $block = $(this).closest('.qi-page-block');
			$block.toggleClass('qi-collapsed');
			const $icon = $(this).find('.dashicons');
			if ($block.hasClass('qi-collapsed')) {
				$icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
			} else {
				$icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
			}
		},

		/* --------------------------------------------------
		 * Add option
		 * -------------------------------------------------- */
		addOption: function () {
			const $page  = $(this).closest('.qi-page-block');
			const $tmpl  = $('.qi-option-template').first();
			if (!$tmpl.length) return;

			const $container = $page.find('.qi-options-container');
			const optIndex   = $container.children('.qi-option-block:not(.qi-template)').length;
			const $clone     = $tmpl.clone();

			$clone.removeClass('qi-template qi-option-template')
				.removeAttr('style')
				.attr('data-opt-index', optIndex);

			$clone.find('.qi-opt-num').text(optIndex + 1);
			$container.append($clone);
			$page.find('.qi-empty-options').hide();
			Admin.syncOptionNumbers($page);
		},

		/* --------------------------------------------------
		 * Delete option
		 * -------------------------------------------------- */
		deleteOption: function () {
			if (!confirm(quizInterativoAdmin.strings.confirmDelOpt)) return;
			const $page = $(this).closest('.qi-page-block');
			$(this).closest('.qi-option-block').remove();
			Admin.syncOptionNumbers($page);
			if (!$page.find('.qi-option-block:not(.qi-template)').length) {
				$page.find('.qi-empty-options').show();
			}
		},

		/* --------------------------------------------------
		 * Delete quiz (list)
		 * -------------------------------------------------- */
		deleteQuiz: function () {
			if (!confirm(quizInterativoAdmin.strings.confirmDelete)) return;
			const $btn   = $(this);
			const quizId = $btn.data('id');

			$.post(quizInterativoAdmin.ajaxUrl, {
				action:   'qi_delete_quiz',
				nonce:    quizInterativoAdmin.nonce,
				quiz_id:  quizId,
			}).done(function (res) {
				if (res.success) {
					$btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
				} else {
					alert(res.data.message || quizInterativoAdmin.strings.error);
				}
			});
		},

		/* --------------------------------------------------
		 * Action type toggle
		 * -------------------------------------------------- */
		onActionTypeChange: function () {
			const $opt    = $(this).closest('.qi-option-block');
			const isPage  = $(this).val() === 'page';
			$opt.find('.qi-action-page').toggle(isPage);
			$opt.find('.qi-action-url, .qi-action-url-target').toggle(!isPage);
		},

		/* --------------------------------------------------
		 * Media uploader
		 * -------------------------------------------------- */
		openMediaUploader: function () {
			const $btn     = $(this);
			const $picker  = $btn.closest('.qi-image-picker');
			const frame    = wp.media({
				title:    quizInterativoAdmin.strings.selectImage,
				button:   { text: quizInterativoAdmin.strings.useImage },
				multiple: false,
				library:  { type: 'image' },
			});

			frame.on('select', function () {
				const attachment = frame.state().get('selection').first().toJSON();
				const url        = attachment.sizes?.medium?.url || attachment.url;

				$picker.find('.qi-field-image').val(attachment.url);
				$picker.find('.qi-image-preview img').attr('src', url);
				$picker.find('.qi-image-preview').show();

				if (!$picker.find('.qi-remove-image').length) {
					$btn.after('<button type="button" class="qi-btn qi-btn-link qi-remove-image">Remover</button>');
				}
			});

			frame.open();
		},

		removeImage: function () {
			const $picker = $(this).closest('.qi-image-picker');
			$picker.find('.qi-field-image').val('');
			$picker.find('.qi-image-preview').hide().find('img').attr('src', '');
			$(this).remove();
		},

		/* --------------------------------------------------
		 * Policy links
		 * -------------------------------------------------- */
		addPolicyLink: function () {
			const $container = $(this).siblings('.qi-policy-links');
			const html = `<div class="qi-policy-link-row">
				<input type="text" class="qi-input qi-field-policy-label" placeholder="Termos de Uso">
				<input type="url" class="qi-input qi-field-policy-url" placeholder="https://meusite.com/termos">
				<button type="button" class="qi-btn qi-btn-danger qi-btn-icon qi-remove-policy-link">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>`;
			$container.append(html);
		},

		removePolicyLink: function () {
			$(this).closest('.qi-policy-link-row').remove();
		},

		/* --------------------------------------------------
		 * Sortable (pages)
		 * -------------------------------------------------- */
		initSortable: function () {
			if (!$.fn.sortable) return;
			$('#qi-pages-container').sortable({
				handle:       '.qi-page-drag-handle',
				items:        '.qi-page-block:not(.qi-template)',
				placeholder:  'qi-page-block-placeholder',
				tolerance:    'pointer',
				update:       Admin.syncPageNumbers,
			});
			// Init option sortable for existing pages
			$('.qi-page-block:not(.qi-template)').each(function () {
				Admin.initSortableOptions($(this));
			});
		},

		initSortableOptions: function ($page) {
			if (!$.fn.sortable) return;
			$page.find('.qi-options-container').sortable({
				handle:      '.qi-option-drag',
				items:       '.qi-option-block:not(.qi-template)',
				placeholder: 'qi-option-block-placeholder',
				tolerance:   'pointer',
				update:      function () { Admin.syncOptionNumbers($page); },
			});
		},

		/* --------------------------------------------------
		 * Sync numbers
		 * -------------------------------------------------- */
		syncPageNumbers: function () {
			$('#qi-pages-container .qi-page-block:not(.qi-template)').each(function (i) {
				$(this).attr('data-page-index', i).find('.qi-page-num').first().text(i + 1);
			});
		},

		syncOptionNumbers: function ($page) {
			$page.find('.qi-option-block:not(.qi-template)').each(function (i) {
				$(this).attr('data-opt-index', i).find('.qi-opt-num').first().text(i + 1);
			});
		},

		/* --------------------------------------------------
		 * Color pickers (sync already done via delegation)
		 * -------------------------------------------------- */
		initColorPickers: function () {
			// Already handled by delegated events.
		},
	};

	$(function () {
		Admin.init();
	});

}(jQuery));
