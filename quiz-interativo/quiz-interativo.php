<?php
/**
 * Plugin Name: Quiz Interativo
 * Plugin URI:  https://meusite.com/quiz-interativo
 * Description: Crie quizzes interativos em formato de funil para páginas de conversão. Insira com o shortcode [quiz_interativo id="1"].
 * Version:     1.3.9
 * Author:      Seu Nome
 * Author URI:  https://meusite.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quiz-interativo
 * Domain Path: /languages
 *
 * @package QuizInterativo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'QUIZ_INTERATIVO_VERSION', '1.3.9' );
define( 'QUIZ_INTERATIVO_PATH', plugin_dir_path( __FILE__ ) );
define( 'QUIZ_INTERATIVO_URL', plugin_dir_url( __FILE__ ) );
define( 'QUIZ_INTERATIVO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
final class Quiz_Interativo {

	/** @var Quiz_Interativo|null */
	private static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies(): void {
		require_once QUIZ_INTERATIVO_PATH . 'admin/class-quiz-cpt.php';
		require_once QUIZ_INTERATIVO_PATH . 'admin/class-quiz-admin.php';
		require_once QUIZ_INTERATIVO_PATH . 'admin/class-quiz-ajax.php';
		require_once QUIZ_INTERATIVO_PATH . 'frontend/class-quiz-shortcode.php';
	}

	private function init_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_shortcode( 'quiz_interativo', array( 'Quiz_Interativo_Shortcode', 'render' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_qi_save_quiz', array( 'Quiz_Interativo_Ajax', 'save_quiz' ) );
		add_action( 'wp_ajax_qi_get_quiz', array( 'Quiz_Interativo_Ajax', 'get_quiz' ) );
		add_action( 'wp_ajax_qi_delete_quiz', array( 'Quiz_Interativo_Ajax', 'delete_quiz' ) );
		add_action( 'wp_ajax_qi_toggle_status', array( 'Quiz_Interativo_Ajax', 'toggle_status' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'quiz-interativo', false, dirname( QUIZ_INTERATIVO_BASENAME ) . '/languages' );
	}

	public function register_post_types(): void {
		Quiz_Interativo_CPT::register();
	}

	public function add_admin_menu(): void {
		Quiz_Interativo_Admin::add_menu();
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, 'quiz-interativo' ) === false ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'quiz-interativo-admin',
			QUIZ_INTERATIVO_URL . 'assets/css/admin.css',
			array(),
			QUIZ_INTERATIVO_VERSION
		);

		wp_enqueue_script(
			'quiz-interativo-admin',
			QUIZ_INTERATIVO_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			QUIZ_INTERATIVO_VERSION,
			true
		);

		wp_localize_script(
			'quiz-interativo-admin',
			'quizInterativoAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'quiz_interativo_nonce' ),
				'strings'   => array(
					'confirmDelete'   => __( 'Tem certeza que deseja excluir este quiz?', 'quiz-interativo' ),
					'confirmDelPage'  => __( 'Tem certeza que deseja excluir esta página?', 'quiz-interativo' ),
					'confirmDelOpt'   => __( 'Tem certeza que deseja excluir esta opção?', 'quiz-interativo' ),
					'saved'           => __( 'Quiz salvo com sucesso!', 'quiz-interativo' ),
					'error'           => __( 'Erro ao salvar. Tente novamente.', 'quiz-interativo' ),
					'selectImage'     => __( 'Selecionar Imagem', 'quiz-interativo' ),
					'useImage'        => __( 'Usar esta imagem', 'quiz-interativo' ),
				),
			)
		);
	}

	public function enqueue_frontend_assets(): void {
		// Always load CSS (lightweight); JS only when shortcode is present.
		wp_enqueue_style(
			'quiz-interativo-frontend',
			QUIZ_INTERATIVO_URL . 'assets/css/frontend.css',
			array(),
			QUIZ_INTERATIVO_VERSION
		);

		// JS only on pages that actually have the shortcode.
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'quiz_interativo' ) ) {
			wp_enqueue_script(
				'quiz-interativo-frontend',
				QUIZ_INTERATIVO_URL . 'assets/js/frontend.js',
				array(),
				QUIZ_INTERATIVO_VERSION,
				true
			);
		}
	}
}

/**
 * Activation / deactivation hooks.
 */
register_activation_hook( __FILE__, 'quiz_interativo_activate' );
register_deactivation_hook( __FILE__, 'quiz_interativo_deactivate' );

function quiz_interativo_activate(): void {
	Quiz_Interativo_CPT::register();
	flush_rewrite_rules();

	// Create default options.
	if ( ! get_option( 'quiz_interativo_options' ) ) {
		add_option(
			'quiz_interativo_options',
			array(
				'btn_color'        => '#2ecc40',
				'btn_hover_color'  => '#27ae35',
				'btn_text_color'   => '#ffffff',
				'bg_color'         => '#ffffff',
				'border_radius'    => '50',
				'max_width'        => '700',
				'box_shadow'       => '1',
				'site_name'        => '',
				'btn_height'       => 72,
			)
		);
	}
}

function quiz_interativo_deactivate(): void {
	flush_rewrite_rules();
}

// Boot.
add_action( 'plugins_loaded', array( 'Quiz_Interativo', 'instance' ) );
