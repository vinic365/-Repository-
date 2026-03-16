<?php
/**
 * AJAX handlers for quiz CRUD operations.
 *
 * @package QuizInterativo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quiz_Interativo_Ajax {

	private static function verify_nonce(): void {
		if ( ! check_ajax_referer( 'quiz_interativo_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Nonce inválido.', 'quiz-interativo' ) ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permissão negada.', 'quiz-interativo' ) ), 403 );
		}
	}

	/**
	 * Save (create or update) a quiz.
	 */
	public static function save_quiz(): void {
		self::verify_nonce();

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$status  = isset( $_POST['status'] ) && 'active' === $_POST['status'] ? 'active' : 'inactive';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'O nome do quiz é obrigatório.', 'quiz-interativo' ) ) );
		}

		// Sanitize pages.
		$raw_pages = isset( $_POST['pages'] ) ? wp_unslash( $_POST['pages'] ) : array();
		$pages     = self::sanitize_pages( $raw_pages );

		$post_data = array(
			'post_title'  => $title,
			'post_type'   => 'quiz_interativo',
			'post_status' => 'publish',
		);

		if ( $quiz_id ) {
			$post_data['ID'] = $quiz_id;
			$result          = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$quiz_id = (int) $result;
		update_post_meta( $quiz_id, '_qi_pages', $pages );
		update_post_meta( $quiz_id, '_qi_status', $status );

		wp_send_json_success(
			array(
				'quiz_id'  => $quiz_id,
				'message'  => __( 'Quiz salvo com sucesso!', 'quiz-interativo' ),
				'redirect' => admin_url( 'admin.php?page=quiz-interativo-new&quiz_id=' . $quiz_id ),
			)
		);
	}

	/**
	 * Get quiz data for the editor.
	 */
	public static function get_quiz(): void {
		self::verify_nonce();

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$quiz    = get_post( $quiz_id );

		if ( ! $quiz || 'quiz_interativo' !== $quiz->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Quiz não encontrado.', 'quiz-interativo' ) ) );
		}

		$pages  = get_post_meta( $quiz_id, '_qi_pages', true );
		$status = get_post_meta( $quiz_id, '_qi_status', true );

		wp_send_json_success(
			array(
				'quiz_id' => $quiz_id,
				'title'   => $quiz->post_title,
				'status'  => $status ?: 'active',
				'pages'   => is_array( $pages ) ? $pages : array(),
			)
		);
	}

	/**
	 * Delete a quiz.
	 */
	public static function delete_quiz(): void {
		self::verify_nonce();

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$quiz    = get_post( $quiz_id );

		if ( ! $quiz || 'quiz_interativo' !== $quiz->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Quiz não encontrado.', 'quiz-interativo' ) ) );
		}

		$result = wp_delete_post( $quiz_id, true );

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Erro ao excluir o quiz.', 'quiz-interativo' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Quiz excluído com sucesso!', 'quiz-interativo' ) ) );
	}

	/**
	 * Toggle quiz status.
	 */
	public static function toggle_status(): void {
		self::verify_nonce();

		$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
		$quiz    = get_post( $quiz_id );

		if ( ! $quiz || 'quiz_interativo' !== $quiz->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Quiz não encontrado.', 'quiz-interativo' ) ) );
		}

		$current = get_post_meta( $quiz_id, '_qi_status', true ) ?: 'active';
		$new     = 'active' === $current ? 'inactive' : 'active';
		update_post_meta( $quiz_id, '_qi_status', $new );

		wp_send_json_success( array( 'status' => $new ) );
	}

	/* ------------------------------------------------------------------
	 * Sanitize helpers
	 * ------------------------------------------------------------------ */

	private static function sanitize_pages( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();
		foreach ( $raw as $page ) {
			if ( ! is_array( $page ) ) {
				continue;
			}

			$clean_page = array(
				'title'           => sanitize_text_field( $page['title'] ?? '' ),
				'subtitle'        => sanitize_text_field( $page['subtitle'] ?? '' ),
				'image'           => esc_url_raw( $page['image'] ?? '' ),
				'bg_color'        => sanitize_hex_color( $page['bg_color'] ?? '#ffffff' ) ?: '#ffffff',
				'btn_color'       => sanitize_hex_color( $page['btn_color'] ?? '#2ecc40' ) ?: '#2ecc40',
				'btn_hover_color' => sanitize_hex_color( $page['btn_hover_color'] ?? '#27ae35' ) ?: '#27ae35',
				'btn_text_color'  => sanitize_hex_color( $page['btn_text_color'] ?? '#ffffff' ) ?: '#ffffff',
				'disclaimer'      => wp_kses_post( $page['disclaimer'] ?? '' ),
				'policy_links'    => self::sanitize_policy_links( $page['policy_links'] ?? array() ),
				'options'         => self::sanitize_options( $page['options'] ?? array() ),
			);

			$clean[] = $clean_page;
		}

		return $clean;
	}

	private static function sanitize_options( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();
		foreach ( $raw as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}

			$action_type = in_array( $opt['action_type'] ?? 'page', array( 'page', 'url' ), true ) ? $opt['action_type'] : 'page';

			$clean[] = array(
				'text'          => sanitize_text_field( $opt['text'] ?? '' ),
				'icon'          => sanitize_text_field( $opt['icon'] ?? '' ),
				'btn_color'     => sanitize_hex_color( $opt['btn_color'] ?? '' ),
				'btn_size'      => in_array( $opt['btn_size'] ?? 'large', array( 'small', 'medium', 'large' ), true ) ? $opt['btn_size'] : 'large',
				'action_type'   => $action_type,
				'action_page'   => absint( $opt['action_page'] ?? 0 ),
				'action_url'    => esc_url_raw( $opt['action_url'] ?? '' ),
				'action_target' => in_array( $opt['action_target'] ?? '_self', array( '_self', '_blank' ), true ) ? $opt['action_target'] : '_self',
			);
		}

		return $clean;
	}

	private static function sanitize_policy_links( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();
		foreach ( $raw as $pl ) {
			if ( ! is_array( $pl ) ) {
				continue;
			}
			$label = sanitize_text_field( $pl['label'] ?? '' );
			$url   = esc_url_raw( $pl['url'] ?? '' );
			if ( $label || $url ) {
				$clean[] = array( 'label' => $label, 'url' => $url );
			}
		}

		return $clean;
	}
}
