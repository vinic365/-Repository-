<?php
/**
 * Custom Post Type registration for quizzes.
 *
 * @package QuizInterativo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quiz_Interativo_CPT {

	public static function register(): void {
		register_post_type(
			'quiz_interativo',
			array(
				'labels'              => array(
					'name'               => __( 'Quizzes', 'quiz-interativo' ),
					'singular_name'      => __( 'Quiz', 'quiz-interativo' ),
					'add_new'            => __( 'Novo Quiz', 'quiz-interativo' ),
					'add_new_item'       => __( 'Adicionar Novo Quiz', 'quiz-interativo' ),
					'edit_item'          => __( 'Editar Quiz', 'quiz-interativo' ),
					'new_item'           => __( 'Novo Quiz', 'quiz-interativo' ),
					'view_item'          => __( 'Ver Quiz', 'quiz-interativo' ),
					'search_items'       => __( 'Buscar Quizzes', 'quiz-interativo' ),
					'not_found'          => __( 'Nenhum quiz encontrado', 'quiz-interativo' ),
					'not_found_in_trash' => __( 'Nenhum quiz na lixeira', 'quiz-interativo' ),
					'menu_name'          => __( 'Quiz Interativo', 'quiz-interativo' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'show_in_rest'        => false,
			)
		);
	}
}
