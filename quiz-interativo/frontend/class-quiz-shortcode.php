<?php
/**
 * Shortcode renderer for the interactive quiz.
 *
 * @package QuizInterativo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quiz_Interativo_Shortcode {

	/**
	 * Render [quiz_interativo id="X"] shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public static function render( array $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'quiz_interativo' );
		$id   = absint( $atts['id'] );

		if ( ! $id ) {
			return '';
		}

		$quiz = get_post( $id );
		if ( ! $quiz || 'quiz_interativo' !== $quiz->post_type ) {
			return '';
		}

		// Only render active quizzes on the frontend.
		$status = get_post_meta( $id, '_qi_status', true );
		if ( 'inactive' === $status && ! current_user_can( 'manage_options' ) ) {
			return '';
		}

		$pages        = get_post_meta( $id, '_qi_pages', true );
		$global_opts  = wp_parse_args(
			(array) get_option( 'quiz_interativo_options', array() ),
			array(
				'btn_color'       => '#2ecc40',
				'btn_hover_color' => '#27ae35',
				'btn_text_color'  => '#ffffff',
				'bg_color'        => '#ffffff',
				'border_radius'   => 50,
				'max_width'       => 700,
				'box_shadow'      => '1',
			)
		);

		if ( empty( $pages ) || ! is_array( $pages ) ) {
			return '';
		}

		ob_start();
		self::render_quiz( $id, $pages, $global_opts, $quiz->post_title );
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------
	 * Full quiz HTML
	 * ------------------------------------------------------------------ */

	private static function render_quiz( int $id, array $pages, array $global_opts, string $quiz_title ): void {
		$shadow_class = $global_opts['box_shadow'] ? ' qi-shadow' : '';
		?>
		<div
			class="qi-quiz<?php echo esc_attr( $shadow_class ); ?>"
			id="qi-quiz-<?php echo esc_attr( $id ); ?>"
			data-quiz-id="<?php echo esc_attr( $id ); ?>"
			style="max-width:<?php echo esc_attr( $global_opts['max_width'] ); ?>px;"
			role="main"
			aria-label="<?php echo esc_attr( $quiz_title ); ?>"
		>
			<?php foreach ( $pages as $page_index => $page ) :
				self::render_page( $id, $page_index, $page, $global_opts );
			endforeach; ?>
		</div>

		<?php
		// Inline CSS variables scoped to this quiz instance.
		$css_id = 'qi-quiz-' . $id;
		echo '<style>';
		echo '#' . esc_attr( $css_id ) . '{';
		echo '--qi-bg:' . esc_attr( $global_opts['bg_color'] ) . ';';
		echo '--qi-btn:' . esc_attr( $global_opts['btn_color'] ) . ';';
		echo '--qi-btn-hover:' . esc_attr( $global_opts['btn_hover_color'] ) . ';';
		echo '--qi-btn-text:' . esc_attr( $global_opts['btn_text_color'] ) . ';';
		echo '--qi-radius:' . esc_attr( $global_opts['border_radius'] ) . 'px;';
		echo '}';
		echo '</style>';
	}

	/* ------------------------------------------------------------------
	 * Single page HTML
	 * ------------------------------------------------------------------ */

	private static function render_page( int $quiz_id, int $page_index, array $page, array $global_opts ): void {
		$is_first   = 0 === $page_index;
		$page_class = 'qi-page' . ( $is_first ? ' qi-page-active' : '' );
		$page_style = 'background-color:' . esc_attr( $page['bg_color'] ?: $global_opts['bg_color'] ) . ';';

		// Per-page CSS variables.
		$btn_color       = $page['btn_color'] ?: $global_opts['btn_color'];
		$btn_hover_color = $page['btn_hover_color'] ?: $global_opts['btn_hover_color'];
		$btn_text_color  = $page['btn_text_color'] ?: $global_opts['btn_text_color'];
		$page_style     .= '--qi-btn:' . esc_attr( $btn_color ) . ';';
		$page_style     .= '--qi-btn-hover:' . esc_attr( $btn_hover_color ) . ';';
		$page_style     .= '--qi-btn-text:' . esc_attr( $btn_text_color ) . ';';
		?>
		<div
			class="<?php echo esc_attr( $page_class ); ?>"
			data-page="<?php echo esc_attr( $page_index ); ?>"
			style="<?php echo esc_attr( $page_style ); ?>"
			aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>"
		>
			<?php if ( ! empty( $page['image'] ) ) : ?>
			<div class="qi-page-image">
				<img
					src="<?php echo esc_url( $page['image'] ); ?>"
					alt="<?php echo esc_attr( $page['title'] ?: '' ); ?>"
					loading="<?php echo $is_first ? 'eager' : 'lazy'; ?>"
					decoding="async"
					width="700"
					height="300"
				>
			</div>
			<?php endif; ?>

			<div class="qi-page-content">
				<?php if ( ! empty( $page['title'] ) ) : ?>
				<h2 class="qi-page-title"><?php echo esc_html( $page['title'] ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $page['subtitle'] ) ) : ?>
				<p class="qi-page-subtitle"><?php echo esc_html( $page['subtitle'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $page['options'] ) ) : ?>
				<div class="qi-options" role="list">
					<?php foreach ( $page['options'] as $opt_index => $opt ) :
						self::render_option( $quiz_id, $page_index, $opt_index, $opt, $global_opts );
					endforeach; ?>
				</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $page['disclaimer'] ) || ! empty( $page['policy_links'] ) ) : ?>
			<footer class="qi-page-footer">
				<?php if ( ! empty( $page['disclaimer'] ) ) : ?>
				<p class="qi-disclaimer"><?php echo wp_kses_post( $page['disclaimer'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $page['policy_links'] ) && is_array( $page['policy_links'] ) ) : ?>
				<nav class="qi-policy-links" aria-label="<?php esc_attr_e( 'Links de política', 'quiz-interativo' ); ?>">
					<?php foreach ( $page['policy_links'] as $idx => $pl ) :
						if ( empty( $pl['label'] ) && empty( $pl['url'] ) ) {
							continue;
						}
						if ( $idx > 0 ) { echo ' <span aria-hidden="true">&bull;</span> '; }
					?>
					<a href="<?php echo esc_url( $pl['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $pl['label'] ); ?>
					</a>
					<?php endforeach; ?>
				</nav>
				<?php endif; ?>
			</footer>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Option button HTML
	 * ------------------------------------------------------------------ */

	private static function render_option( int $quiz_id, int $page_index, int $opt_index, array $opt, array $global_opts ): void {
		if ( empty( $opt['text'] ) ) {
			return;
		}

		$size_class = 'large' === ( $opt['btn_size'] ?? 'large' ) ? 'qi-btn-lg'
			: ( 'medium' === $opt['btn_size'] ? 'qi-btn-md' : 'qi-btn-sm' );

		$custom_color = ! empty( $opt['btn_color'] ) ? $opt['btn_color'] : '';
		$style_attr   = $custom_color ? 'style="--qi-btn:' . esc_attr( $custom_color ) . ';"' : '';

		$action_type   = $opt['action_type'] ?? 'page';
		$action_page   = (int) ( $opt['action_page'] ?? 0 );
		$action_url    = $opt['action_url'] ?? '';
		$action_target = $opt['action_target'] ?? '_self';

		$data_attrs = '';
		if ( 'page' === $action_type && $action_page > 0 ) {
			// Pages are 1-indexed in the admin but 0-indexed in data array.
			$data_attrs = 'data-action="page" data-target="' . esc_attr( $action_page - 1 ) . '"';
		} elseif ( 'url' === $action_type && $action_url ) {
			$data_attrs = 'data-action="url" data-url="' . esc_url( $action_url ) . '" data-target="' . esc_attr( $action_target ) . '"';
		}
		?>
		<div class="qi-option-wrap" role="listitem">
			<button
				type="button"
				class="qi-option <?php echo esc_attr( $size_class ); ?>"
				<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above ?>
				<?php echo $data_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped above ?>
				aria-label="<?php echo esc_attr( $opt['text'] ); ?>"
			>
				<span class="qi-option-text"><?php echo esc_html( $opt['text'] ); ?></span>
				<?php if ( ! empty( $opt['icon'] ) ) : ?>
				<span class="qi-option-icon" aria-hidden="true"><?php echo esc_html( $opt['icon'] ); ?></span>
				<?php endif; ?>
			</button>
		</div>
		<?php
	}
}
