<?php
/**
 * Admin panel: menus, pages, and rendering.
 *
 * @package QuizInterativo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Quiz_Interativo_Admin {

	public static function add_menu(): void {
		add_menu_page(
			__( 'Quiz Interativo', 'quiz-interativo' ),
			__( 'Quiz Interativo', 'quiz-interativo' ),
			'manage_options',
			'quiz-interativo',
			array( __CLASS__, 'render_list_page' ),
			'dashicons-clipboard',
			30
		);

		add_submenu_page(
			'quiz-interativo',
			__( 'Todos os Quizzes', 'quiz-interativo' ),
			__( 'Todos os Quizzes', 'quiz-interativo' ),
			'manage_options',
			'quiz-interativo',
			array( __CLASS__, 'render_list_page' )
		);

		add_submenu_page(
			'quiz-interativo',
			__( 'Novo Quiz', 'quiz-interativo' ),
			__( 'Novo Quiz', 'quiz-interativo' ),
			'manage_options',
			'quiz-interativo-new',
			array( __CLASS__, 'render_edit_page' )
		);

		add_submenu_page(
			'quiz-interativo',
			__( 'Configurações', 'quiz-interativo' ),
			__( 'Configurações', 'quiz-interativo' ),
			'manage_options',
			'quiz-interativo-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/* ------------------------------------------------------------------
	 * List page
	 * ------------------------------------------------------------------ */

	public static function render_list_page(): void {
		$quizzes = get_posts(
			array(
				'post_type'      => 'quiz_interativo',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<div class="wrap qi-wrap">
			<h1 class="qi-page-title">
				<span class="dashicons dashicons-clipboard"></span>
				<?php esc_html_e( 'Quiz Interativo', 'quiz-interativo' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=quiz-interativo-new' ) ); ?>" class="qi-btn qi-btn-primary qi-btn-sm">
					+ <?php esc_html_e( 'Novo Quiz', 'quiz-interativo' ); ?>
				</a>
			</h1>

			<?php if ( empty( $quizzes ) ) : ?>
				<div class="qi-empty-state">
					<span class="dashicons dashicons-clipboard qi-empty-icon"></span>
					<p><?php esc_html_e( 'Nenhum quiz criado ainda.', 'quiz-interativo' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=quiz-interativo-new' ) ); ?>" class="qi-btn qi-btn-primary">
						<?php esc_html_e( 'Criar primeiro quiz', 'quiz-interativo' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="qi-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Nome', 'quiz-interativo' ); ?></th>
							<th><?php esc_html_e( 'ID', 'quiz-interativo' ); ?></th>
							<th><?php esc_html_e( 'Páginas', 'quiz-interativo' ); ?></th>
							<th><?php esc_html_e( 'Status', 'quiz-interativo' ); ?></th>
							<th><?php esc_html_e( 'Shortcode', 'quiz-interativo' ); ?></th>
							<th><?php esc_html_e( 'Ações', 'quiz-interativo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $quizzes as $quiz ) :
							$pages  = get_post_meta( $quiz->ID, '_qi_pages', true );
							$pages  = is_array( $pages ) ? $pages : array();
							$status = get_post_meta( $quiz->ID, '_qi_status', true );
							$status = $status ?: 'active';
						?>
						<tr data-id="<?php echo esc_attr( $quiz->ID ); ?>">
							<td><strong><?php echo esc_html( $quiz->post_title ); ?></strong></td>
							<td><?php echo esc_html( $quiz->ID ); ?></td>
							<td><?php echo count( $pages ); ?></td>
							<td>
								<span class="qi-status qi-status-<?php echo esc_attr( $status ); ?>">
									<?php echo 'active' === $status ? esc_html__( 'Ativo', 'quiz-interativo' ) : esc_html__( 'Inativo', 'quiz-interativo' ); ?>
								</span>
							</td>
							<td>
								<code class="qi-shortcode" onclick="this.select()">[quiz_interativo id="<?php echo esc_attr( $quiz->ID ); ?>"]</code>
							</td>
							<td class="qi-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=quiz-interativo-new&quiz_id=' . $quiz->ID ) ); ?>" class="qi-btn qi-btn-secondary qi-btn-xs">
									<?php esc_html_e( 'Editar', 'quiz-interativo' ); ?>
								</a>
								<button class="qi-btn qi-btn-danger qi-btn-xs qi-delete-quiz" data-id="<?php echo esc_attr( $quiz->ID ); ?>">
									<?php esc_html_e( 'Excluir', 'quiz-interativo' ); ?>
								</button>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Edit page
	 * ------------------------------------------------------------------ */

	public static function render_edit_page(): void {
		$quiz_id   = isset( $_GET['quiz_id'] ) ? absint( $_GET['quiz_id'] ) : 0;
		$quiz      = $quiz_id ? get_post( $quiz_id ) : null;
		$quiz_data = array(
			'title'  => $quiz ? $quiz->post_title : '',
			'status' => 'active',
			'pages'  => array(),
		);

		if ( $quiz ) {
			$saved_pages  = get_post_meta( $quiz_id, '_qi_pages', true );
			$saved_status = get_post_meta( $quiz_id, '_qi_status', true );

			$quiz_data['pages']  = is_array( $saved_pages ) ? $saved_pages : array();
			$quiz_data['status'] = $saved_status ?: 'active';
		}

		$page_title = $quiz_id
			? __( 'Editar Quiz', 'quiz-interativo' )
			: __( 'Novo Quiz', 'quiz-interativo' );
		?>
		<div class="wrap qi-wrap qi-editor-wrap">
			<h1 class="qi-page-title">
				<span class="dashicons dashicons-clipboard"></span>
				<?php echo esc_html( $page_title ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=quiz-interativo' ) ); ?>" class="qi-btn qi-btn-secondary qi-btn-sm">
					&larr; <?php esc_html_e( 'Voltar', 'quiz-interativo' ); ?>
				</a>
			</h1>

			<div id="qi-editor" data-quiz-id="<?php echo esc_attr( $quiz_id ); ?>">

				<!-- Basic settings -->
				<div class="qi-card qi-settings-card">
					<h2><?php esc_html_e( 'Configurações do Quiz', 'quiz-interativo' ); ?></h2>
					<div class="qi-row">
						<div class="qi-col">
							<label for="qi-quiz-title"><?php esc_html_e( 'Nome do Quiz', 'quiz-interativo' ); ?> <span class="required">*</span></label>
							<input type="text" id="qi-quiz-title" class="qi-input" value="<?php echo esc_attr( $quiz_data['title'] ); ?>" placeholder="<?php esc_attr_e( 'Ex: Quiz de Roblox', 'quiz-interativo' ); ?>" required>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Status', 'quiz-interativo' ); ?></label>
							<div class="qi-toggle-wrap">
								<label class="qi-toggle">
									<input type="checkbox" id="qi-quiz-status" <?php checked( $quiz_data['status'], 'active' ); ?>>
									<span class="qi-toggle-slider"></span>
								</label>
								<span class="qi-toggle-label" id="qi-status-label">
									<?php echo 'active' === $quiz_data['status'] ? esc_html__( 'Ativo', 'quiz-interativo' ) : esc_html__( 'Inativo', 'quiz-interativo' ); ?>
								</span>
							</div>
						</div>
					</div>

					<?php if ( $quiz_id ) : ?>
					<div class="qi-shortcode-info">
						<label><?php esc_html_e( 'Shortcode', 'quiz-interativo' ); ?></label>
						<code class="qi-shortcode" onclick="this.select()">[quiz_interativo id="<?php echo esc_attr( $quiz_id ); ?>"]</code>
						<p class="qi-hint"><?php esc_html_e( 'Copie e cole este shortcode em qualquer página ou post.', 'quiz-interativo' ); ?></p>
					</div>
					<?php endif; ?>
				</div>

				<!-- Pages -->
				<div class="qi-card">
					<div class="qi-card-header">
						<h2><?php esc_html_e( 'Páginas do Quiz', 'quiz-interativo' ); ?></h2>
						<button type="button" id="qi-add-page" class="qi-btn qi-btn-primary">
							+ <?php esc_html_e( 'Adicionar Página', 'quiz-interativo' ); ?>
						</button>
					</div>
					<p class="qi-hint"><?php esc_html_e( 'Arraste as páginas para reordenar. Cada página pode ter múltiplas opções.', 'quiz-interativo' ); ?></p>

					<div id="qi-pages-container">
						<?php
						foreach ( $quiz_data['pages'] as $page_index => $page ) {
							self::render_page_block( $page_index, $page );
						}
						?>
					</div>

					<div id="qi-empty-pages" style="<?php echo ! empty( $quiz_data['pages'] ) ? 'display:none' : ''; ?>">
						<p class="qi-empty-notice"><?php esc_html_e( 'Nenhuma página adicionada. Clique em "Adicionar Página" para começar.', 'quiz-interativo' ); ?></p>
					</div>
				</div>

				<!-- Save -->
				<div class="qi-save-bar">
					<div id="qi-save-message" class="qi-save-message"></div>
					<button type="button" id="qi-save-quiz" class="qi-btn qi-btn-primary qi-btn-lg">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Salvar Quiz', 'quiz-interativo' ); ?>
					</button>
				</div>
			</div>

			<!-- Page template (hidden, cloned by JS) -->
			<?php self::render_page_block( '__PAGE_INDEX__', array(), true ); ?>

			<!-- Option template (hidden, cloned by JS) -->
			<?php self::render_option_block( '__PAGE_INDEX__', '__OPT_INDEX__', array(), true ); ?>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Page block partial
	 * ------------------------------------------------------------------ */

	public static function render_page_block( $page_index, array $page = array(), bool $template = false ): void {
		$defaults = array(
			'title'              => '',
			'subtitle'           => '',
			'site_name'          => '',
			'image'              => '',
			'bg_color'           => '#ffffff',
			'btn_color'          => '#2ecc40',
			'btn_hover_color'    => '#27ae35',
			'btn_text_color'     => '#ffffff',
			'disclaimer'         => '',
			'policy_links'       => array(),
			'options'            => array(),
		);
		$page     = wp_parse_args( $page, $defaults );

		$wrap_class = 'qi-page-block' . ( $template ? ' qi-template qi-page-template' : '' );
		$wrap_style = $template ? 'display:none' : '';
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>" data-page-index="<?php echo esc_attr( $page_index ); ?>" style="<?php echo esc_attr( $wrap_style ); ?>">
			<div class="qi-page-header">
				<div class="qi-page-drag-handle" title="<?php esc_attr_e( 'Arrastar para reordenar', 'quiz-interativo' ); ?>">
					<span class="dashicons dashicons-move"></span>
				</div>
				<h3 class="qi-page-title-label">
					<span class="dashicons dashicons-admin-page"></span>
					<?php esc_html_e( 'Página', 'quiz-interativo' ); ?> <span class="qi-page-num"><?php echo is_int( $page_index ) ? ( $page_index + 1 ) : 1; ?></span>
				</h3>
				<div class="qi-page-actions">
					<button type="button" class="qi-btn qi-btn-icon qi-toggle-page" title="<?php esc_attr_e( 'Expandir/Recolher', 'quiz-interativo' ); ?>">
						<span class="dashicons dashicons-arrow-up-alt2"></span>
					</button>
					<button type="button" class="qi-btn qi-btn-danger qi-btn-icon qi-delete-page" title="<?php esc_attr_e( 'Excluir página', 'quiz-interativo' ); ?>">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>

			<div class="qi-page-body">
				<!-- Content -->
				<div class="qi-section">
					<h4><?php esc_html_e( 'Conteúdo', 'quiz-interativo' ); ?></h4>
					<div class="qi-row">
						<div class="qi-col">
							<label><?php esc_html_e( 'Título', 'quiz-interativo' ); ?></label>
							<input type="text" class="qi-input qi-field-title" value="<?php echo esc_attr( $page['title'] ); ?>" placeholder="<?php esc_attr_e( 'Selecione uma opção', 'quiz-interativo' ); ?>">
						</div>
					</div>
					<div class="qi-row">
						<div class="qi-col">
							<label><?php esc_html_e( 'Subtítulo', 'quiz-interativo' ); ?></label>
							<input type="text" class="qi-input qi-field-subtitle" value="<?php echo esc_attr( $page['subtitle'] ); ?>" placeholder="<?php esc_attr_e( 'Ao clicar você continua em nosso site...', 'quiz-interativo' ); ?>">
						</div>
					</div>
					<div class="qi-row">
						<div class="qi-col">
							<label><?php esc_html_e( 'Nome do Site (acima da imagem)', 'quiz-interativo' ); ?></label>
							<input type="text" class="qi-input qi-field-site-name" value="<?php echo esc_attr( $page['site_name'] ); ?>" placeholder="meusite.com">
							<span class="qi-hint"><?php esc_html_e( 'Deixe em branco para ocultar.', 'quiz-interativo' ); ?></span>
						</div>
					</div>
					<div class="qi-row">
						<div class="qi-col">
							<label><?php esc_html_e( 'Imagem do Topo (Banner)', 'quiz-interativo' ); ?></label>
							<div class="qi-image-picker">
								<div class="qi-image-preview" style="<?php echo $page['image'] ? '' : 'display:none'; ?>">
									<img src="<?php echo esc_url( $page['image'] ); ?>" alt="">
								</div>
								<input type="hidden" class="qi-field-image" value="<?php echo esc_url( $page['image'] ); ?>">
								<button type="button" class="qi-btn qi-btn-secondary qi-select-image">
									<?php esc_html_e( 'Selecionar Imagem', 'quiz-interativo' ); ?>
								</button>
								<?php if ( $page['image'] ) : ?>
								<button type="button" class="qi-btn qi-btn-link qi-remove-image">
									<?php esc_html_e( 'Remover', 'quiz-interativo' ); ?>
								</button>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Visual -->
				<div class="qi-section">
					<h4><?php esc_html_e( 'Personalização Visual', 'quiz-interativo' ); ?></h4>
					<div class="qi-row qi-row-colors">
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor de Fundo', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" class="qi-color qi-field-bg-color" value="<?php echo esc_attr( $page['bg_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $page['bg_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor dos Botões', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" class="qi-color qi-field-btn-color" value="<?php echo esc_attr( $page['btn_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $page['btn_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor Hover Botão', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" class="qi-color qi-field-btn-hover-color" value="<?php echo esc_attr( $page['btn_hover_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $page['btn_hover_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor do Texto Botão', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" class="qi-color qi-field-btn-text-color" value="<?php echo esc_attr( $page['btn_text_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $page['btn_text_color'] ); ?>">
							</div>
						</div>
					</div>
				</div>

				<!-- Options -->
				<div class="qi-section">
					<div class="qi-section-header">
						<h4><?php esc_html_e( 'Opções de Resposta', 'quiz-interativo' ); ?></h4>
						<button type="button" class="qi-btn qi-btn-secondary qi-btn-sm qi-add-option">
							+ <?php esc_html_e( 'Adicionar Opção', 'quiz-interativo' ); ?>
						</button>
					</div>
					<div class="qi-options-container">
						<?php
						if ( ! empty( $page['options'] ) ) {
							foreach ( $page['options'] as $opt_index => $opt ) {
								self::render_option_block( $page_index, $opt_index, $opt );
							}
						}
						?>
					</div>
					<div class="qi-empty-options" style="<?php echo ! empty( $page['options'] ) ? 'display:none' : ''; ?>">
						<p class="qi-empty-notice"><?php esc_html_e( 'Nenhuma opção. Adicione pelo menos uma opção.', 'quiz-interativo' ); ?></p>
					</div>
				</div>

				<!-- Disclaimer -->
				<div class="qi-section qi-section-collapse">
					<div class="qi-section-toggle">
						<h4><?php esc_html_e( 'Aviso Legal (Disclaimer)', 'quiz-interativo' ); ?></h4>
						<button type="button" class="qi-btn qi-btn-icon qi-toggle-section"><span class="dashicons dashicons-arrow-down-alt2"></span></button>
					</div>
					<div class="qi-section-content" style="display:none">
						<div class="qi-row">
							<div class="qi-col">
								<label><?php esc_html_e( 'Texto do Disclaimer', 'quiz-interativo' ); ?></label>
								<textarea class="qi-input qi-textarea qi-field-disclaimer" rows="4" placeholder="<?php esc_attr_e( 'Aviso Legal: O site oferece uma experiência de quiz interativo...', 'quiz-interativo' ); ?>"><?php echo esc_textarea( $page['disclaimer'] ); ?></textarea>
							</div>
						</div>
						<div class="qi-row">
							<div class="qi-col">
								<label><?php esc_html_e( 'Links de Política', 'quiz-interativo' ); ?></label>
								<div class="qi-policy-links">
									<?php
									$policy_links = is_array( $page['policy_links'] ) ? $page['policy_links'] : array();
									if ( ! empty( $policy_links ) ) {
										foreach ( $policy_links as $pl_index => $pl ) {
											self::render_policy_link( $page_index, $pl_index, $pl );
										}
									}
									?>
								</div>
								<button type="button" class="qi-btn qi-btn-link qi-add-policy-link">
									+ <?php esc_html_e( 'Adicionar Link', 'quiz-interativo' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

			</div><!-- .qi-page-body -->
		</div><!-- .qi-page-block -->
		<?php
	}

	/* ------------------------------------------------------------------
	 * Option block partial
	 * ------------------------------------------------------------------ */

	public static function render_option_block( $page_index, $opt_index, array $opt = array(), bool $template = false ): void {
		$defaults = array(
			'text'         => '',
			'icon'         => '',
			'icon_type'    => 'emoji',
			'icon_image'   => '',
			'btn_color'    => '',
			'btn_size'     => 'large',
			'action_type'  => 'page',
			'action_page'  => '',
			'action_url'   => '',
			'action_target'=> '_self',
		);
		$opt = wp_parse_args( $opt, $defaults );

		$wrap_class = 'qi-option-block' . ( $template ? ' qi-template qi-option-template' : '' );
		$wrap_style = $template ? 'display:none' : '';
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>" data-page-index="<?php echo esc_attr( $page_index ); ?>" data-opt-index="<?php echo esc_attr( $opt_index ); ?>" style="<?php echo esc_attr( $wrap_style ); ?>">
			<div class="qi-option-header">
				<span class="dashicons dashicons-move qi-option-drag"></span>
				<strong><?php esc_html_e( 'Opção', 'quiz-interativo' ); ?> <span class="qi-opt-num"><?php echo is_int( $opt_index ) ? ( $opt_index + 1 ) : 1; ?></span></strong>
				<button type="button" class="qi-btn qi-btn-danger qi-btn-icon qi-delete-option">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="qi-option-body">
				<div class="qi-row">
					<div class="qi-col">
						<label><?php esc_html_e( 'Texto da Opção', 'quiz-interativo' ); ?></label>
						<input type="text" class="qi-input qi-field-opt-text" value="<?php echo esc_attr( $opt['text'] ); ?>" placeholder="<?php esc_attr_e( 'Ex: OPÇÃO 1', 'quiz-interativo' ); ?>">
					</div>
					<div class="qi-col qi-col-sm">
						<label><?php esc_html_e( 'Ícone / Emoji', 'quiz-interativo' ); ?></label>
						<input type="text" class="qi-input qi-field-opt-icon" value="<?php echo esc_attr( $opt['icon'] ); ?>" placeholder="🚀">
					</div>
					<div class="qi-col qi-col-sm">
						<label><?php esc_html_e( 'Tamanho do Botão', 'quiz-interativo' ); ?></label>
						<select class="qi-input qi-field-opt-size">
							<?php foreach ( array( 'small' => __( 'Pequeno', 'quiz-interativo' ), 'medium' => __( 'Médio', 'quiz-interativo' ), 'large' => __( 'Grande', 'quiz-interativo' ) ) as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $opt['btn_size'], $val ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="qi-col qi-col-sm">
						<label><?php esc_html_e( 'Cor do Botão (opcional)', 'quiz-interativo' ); ?></label>
						<div class="qi-color-wrap">
							<input type="color" class="qi-color qi-field-opt-color" value="<?php echo esc_attr( $opt['btn_color'] ?: '#2ecc40' ); ?>">
							<input type="text" class="qi-color-text" value="<?php echo esc_attr( $opt['btn_color'] ); ?>" placeholder="<?php esc_attr_e( 'Padrão da página', 'quiz-interativo' ); ?>">
						</div>
					</div>
				</div>
				<div class="qi-row">
					<div class="qi-col qi-col-sm">
						<label><?php esc_html_e( 'Ação ao Clicar', 'quiz-interativo' ); ?></label>
						<select class="qi-input qi-field-opt-action-type">
							<option value="page" <?php selected( $opt['action_type'], 'page' ); ?>><?php esc_html_e( 'Ir para outra página', 'quiz-interativo' ); ?></option>
							<option value="url" <?php selected( $opt['action_type'], 'url' ); ?>><?php esc_html_e( 'Abrir URL', 'quiz-interativo' ); ?></option>
						</select>
					</div>
					<div class="qi-col qi-action-page" style="<?php echo 'url' === $opt['action_type'] ? 'display:none' : ''; ?>">
						<label><?php esc_html_e( 'Página de Destino (número)', 'quiz-interativo' ); ?></label>
						<input type="number" min="1" class="qi-input qi-field-opt-action-page" value="<?php echo esc_attr( $opt['action_page'] ); ?>" placeholder="<?php esc_attr_e( 'Ex: 2', 'quiz-interativo' ); ?>">
					</div>
					<div class="qi-col qi-action-url" style="<?php echo 'page' === $opt['action_type'] ? 'display:none' : ''; ?>">
						<label><?php esc_html_e( 'URL de Destino', 'quiz-interativo' ); ?></label>
						<input type="url" class="qi-input qi-field-opt-action-url" value="<?php echo esc_url( $opt['action_url'] ); ?>" placeholder="https://meusite.com/oferta">
					</div>
					<div class="qi-col qi-col-sm qi-action-url-target" style="<?php echo 'page' === $opt['action_type'] ? 'display:none' : ''; ?>">
						<label><?php esc_html_e( 'Abrir em', 'quiz-interativo' ); ?></label>
						<select class="qi-input qi-field-opt-action-target">
							<option value="_self" <?php selected( $opt['action_target'], '_self' ); ?>><?php esc_html_e( 'Mesma aba', 'quiz-interativo' ); ?></option>
							<option value="_blank" <?php selected( $opt['action_target'], '_blank' ); ?>><?php esc_html_e( 'Nova aba', 'quiz-interativo' ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Policy link partial
	 * ------------------------------------------------------------------ */

	public static function render_policy_link( $page_index, $pl_index, array $pl = array() ): void {
		$defaults = array( 'label' => '', 'url' => '' );
		$pl       = wp_parse_args( $pl, $defaults );
		?>
		<div class="qi-policy-link-row">
			<input type="text" class="qi-input qi-field-policy-label" value="<?php echo esc_attr( $pl['label'] ); ?>" placeholder="<?php esc_attr_e( 'Termos de Uso', 'quiz-interativo' ); ?>">
			<input type="url" class="qi-input qi-field-policy-url" value="<?php echo esc_url( $pl['url'] ); ?>" placeholder="https://meusite.com/termos">
			<button type="button" class="qi-btn qi-btn-danger qi-btn-icon qi-remove-policy-link">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Settings page
	 * ------------------------------------------------------------------ */

	public static function render_settings_page(): void {
		if ( isset( $_POST['qi_save_settings'] ) && check_admin_referer( 'qi_settings' ) ) {
			$options = array(
				'btn_color'       => sanitize_hex_color( $_POST['qi_btn_color'] ?? '#2ecc40' ),
				'btn_hover_color' => sanitize_hex_color( $_POST['qi_btn_hover_color'] ?? '#27ae35' ),
				'btn_text_color'  => sanitize_hex_color( $_POST['qi_btn_text_color'] ?? '#ffffff' ),
				'bg_color'        => sanitize_hex_color( $_POST['qi_bg_color'] ?? '#ffffff' ),
				'border_radius'   => absint( $_POST['qi_border_radius'] ?? 50 ),
				'max_width'       => absint( $_POST['qi_max_width'] ?? 700 ),
				'box_shadow'      => isset( $_POST['qi_box_shadow'] ) ? '1' : '0',
			);
			update_option( 'quiz_interativo_options', $options );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configurações salvas!', 'quiz-interativo' ) . '</p></div>';
		}

		$opts = wp_parse_args(
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
		?>
		<div class="wrap qi-wrap">
			<h1 class="qi-page-title">
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Configurações Globais', 'quiz-interativo' ); ?>
			</h1>

			<form method="post" class="qi-settings-form">
				<?php wp_nonce_field( 'qi_settings' ); ?>

				<div class="qi-card">
					<h2><?php esc_html_e( 'Aparência Padrão', 'quiz-interativo' ); ?></h2>
					<p class="qi-hint"><?php esc_html_e( 'Estas cores servem como padrão. Cada página do quiz pode sobrescrever individualmente.', 'quiz-interativo' ); ?></p>

					<div class="qi-row qi-row-colors">
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor dos Botões', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" name="qi_btn_color" value="<?php echo esc_attr( $opts['btn_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $opts['btn_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor Hover dos Botões', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" name="qi_btn_hover_color" value="<?php echo esc_attr( $opts['btn_hover_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $opts['btn_hover_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor do Texto dos Botões', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" name="qi_btn_text_color" value="<?php echo esc_attr( $opts['btn_text_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $opts['btn_text_color'] ); ?>">
							</div>
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Cor de Fundo', 'quiz-interativo' ); ?></label>
							<div class="qi-color-wrap">
								<input type="color" name="qi_bg_color" value="<?php echo esc_attr( $opts['bg_color'] ); ?>">
								<input type="text" class="qi-color-text" value="<?php echo esc_attr( $opts['bg_color'] ); ?>">
							</div>
						</div>
					</div>

					<div class="qi-row">
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Borda Arredondada dos Botões (px)', 'quiz-interativo' ); ?></label>
							<input type="number" name="qi_border_radius" class="qi-input" min="0" max="100" value="<?php echo esc_attr( $opts['border_radius'] ); ?>">
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Largura Máxima do Quiz (px)', 'quiz-interativo' ); ?></label>
							<input type="number" name="qi_max_width" class="qi-input" min="300" max="1200" value="<?php echo esc_attr( $opts['max_width'] ); ?>">
						</div>
						<div class="qi-col qi-col-sm">
							<label><?php esc_html_e( 'Sombra no Quiz', 'quiz-interativo' ); ?></label>
							<label class="qi-toggle" style="margin-top:8px">
								<input type="checkbox" name="qi_box_shadow" <?php checked( $opts['box_shadow'], '1' ); ?>>
								<span class="qi-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="qi-save-bar">
					<button type="submit" name="qi_save_settings" class="qi-btn qi-btn-primary qi-btn-lg">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'Salvar Configurações', 'quiz-interativo' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}
}
