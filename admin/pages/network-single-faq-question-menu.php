<?php

/**
 * Support Network Single Ticket Menu
 */

if ( ! class_exists( 'MU_Support_Network_Single_FAQ_Question_Menu' ) ) {

	class MU_Support_Network_Single_FAQ_Question_Menu extends MU_Support_Menu {

		private $faq_id;
		private $ticket_id = false;

		private $current_faq;

		private $editing = false;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			$this->action = 'edit';
			if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ) ) )
				$this->action = $_GET['action'];

			if ( 'new' == $this->action ) {
				$this->page_title = __( 'Add new FAQ Question', INCSUB_SUPPORT_LANG_DOMAIN); 
				$this->menu_title = __( 'Add new FAQ Question', INCSUB_SUPPORT_LANG_DOMAIN);
			}
			else {
				$this->page_title = __( 'Edit FAQ Question', INCSUB_SUPPORT_LANG_DOMAIN); 
				$this->menu_title = __( 'Edit FAQ Question', INCSUB_SUPPORT_LANG_DOMAIN); 
			}
			$this->capability = 'manage_network';
			$this->menu_slug = 'single-faq-question';
			$this->submenu = true;


			parent::__construct();

			$this->faq_id = 0;
			if ( isset( $_GET['fid'] ) )
				$this->faq_id = absint( $_GET['fid'] );

			if ( isset( $_GET['tid'] ) )
				$this->ticket_id = absint( $_GET['tid'] );

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
			add_action( 'admin_init', array( &$this, 'validate_form' ) );
			add_filter( 'admin_title', array( &$this, 'set_page_title' ), 10, 1 );

		}

		function set_page_title( $title ) {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $this->menu_slug ) {	
				return $this->page_title . $title;
			}
			else
				return $title;
		}

		public function enqueue_styles( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_register_style( 'single-ticket-menu', INCSUB_SUPPORT_ASSETS_URL . 'css/single-ticket-menu.css', array(), '20130402' );
				wp_enqueue_style( 'single-ticket-menu' );
			}
		}


		/**
		 * Gets the current FAQ Question details
		 * 
		 * @since 1.8
		 * 
		 * @return Array Current FAQ Question details
		 */
		private function get_current_faq_question_details( $faq_id ) {

			if ( 'edit' == $this->action ) {
				// Editing a FAQ
				$model = MU_Support_System_Model::get_instance();
				$faq_details = $model->get_faq_details( $faq_id );	
				
				if ( empty( $faq_details ) )
					wp_die( __( "The question you're trying to find does not exist.", INCSUB_SUPPORT_LANG_DOMAIN ) );
			}
			else {
				// Creating a new FAQ
				$faq_details = array(
					'faq_id'	=> false,
					'cat_id'	=> false,
					'question'	=> '',
					'answer'	=> ''
				);
				if ( $this->ticket_id ) {
					// Creating a new FAQ from a ticket message
					$model = MU_Support_System_Model::get_instance();
					$ticket = $model->get_ticket_message_details( $this->ticket_id );
					if ( $ticket ) {
						$faq_details['question'] = trim( preg_replace( '/^Re:/i', '', $ticket['subject'], 1 ) );
						$faq_details['answer'] = wpautop( $ticket['message'] );
					}					
				}
			}

			return $faq_details; 
		}


		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			if ( ! $this->editing )
				$this->current_faq = $this->get_current_faq_question_details( $this->faq_id );

			$this->the_faq_form( $this->current_faq );

		}

		/**
		 * Renders a ticket details
		 * 
		 * @since 1.8
		 * 
		 * @param Array $current_ticket Current ticket Array
		 */
		private function the_faq_form( $current_faq ) {

			$model = MU_Support_System_Model::get_instance();
			$categories = $model->get_faq_categories();

			if ( $this->is_error() ) {
				$this->render_errors();
			}
			elseif ( ! $this->is_error() && isset( $_GET['updated'] ) ) {
				?>
					<div class="updated">
						<p><?php _e( 'FAQ question updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
					</div>
				<?php
			}
			elseif ( ! $this->is_error() && isset( $_GET['created'] ) ) {
				?>
					<div class="updated">
						<p><?php _e( 'FAQ question created', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
					</div>
				<?php
			}
			?>
				<form method="post" action="">

					<table class="form-table">
						<?php ob_start(); ?>
							<input type="text" value="<?php echo stripslashes_deep( $current_faq['question'] ); ?>" class="widefat" name="question" id="question">
						<?php $this->render_row( 'Question', ob_get_clean() ); ?>

						<?php ob_start(); ?>
							<select name="category" id="category">
								<?php foreach ( $categories as $category ): ?>
									<option <?php selected( $current_faq['cat_id'], $category['cat_id'] ); ?> value="<?php echo $category['cat_id']; ?>"><?php echo $category['cat_name']; ?></option>
								<?php endforeach; ?>
							</select>
						<?php $this->render_row( 'FAQ Category', ob_get_clean() ); ?>

						<?php ob_start(); ?>
							<?php wp_editor( $current_faq['answer'], 'answer', array( 'media_buttons' => true ) ); ?> 
						<?php $this->render_row( 'Answer', ob_get_clean() ); ?>

					</table>
					<p class="submit">
						<?php submit_button( __( 'Submit', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
						<a href="<?php echo MU_Support_System::$network_faq_manager_menu->get_permalink(); ?>" class="button-secondary"><?php _e( 'Back to FAQs list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
						<?php wp_nonce_field( 'edit-faq-question' ); ?>
						<input type="hidden" name="action" value="edit-faq-question">
					</p>
				</form>
			<?php
		}


		/**
		 * Validates the form
		 * 
		 * @since 1.8
		 * 
		 */
		public function validate_form() {

			if ( isset( $_POST['submit'] ) && isset( $_POST['action'] ) && 'edit-faq-question' == $_POST['action'] ) {

				if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] , 'edit-faq-question' ) )
					wp_die( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN );

				$this->current_faq = $this->get_current_faq_question_details( $this->faq_id );

				$this->editing = true;

				if ( empty( $_POST['question'] ) ) {
					$this->add_error( 'question', __( 'Question cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
				}
				else {
					$this->current_faq['question'] = sanitize_text_field( $_POST['question'] );
				}

				$this->current_faq['answer'] = wpautop( stripslashes_deep( $_POST['answer'] ) );

				$this->current_faq['cat_id'] = absint( $_POST['category'] );				

				if ( ! $this->is_error() ) {
					$model = MU_Support_System_Model::get_instance();
					if ( 'edit' == $this->action ) {
						$model->update_faq_question(
							$this->faq_id,
							$this->current_faq['question'],
							$this->current_faq['answer'],
							$this->current_faq['cat_id']
						);
						$link = $this->get_permalink();
						$link = add_query_arg( 'fid', $this->current_faq['faq_id'], $link );
						$link = add_query_arg( 'updated','true', $link );
						wp_redirect( $link );
					}
					else {
						$new_id = $model->add_new_faq_question(
							$this->current_faq['question'],
							$this->current_faq['answer'],
							$this->current_faq['cat_id']
						);

						if ( ! $new_id ) 
							wp_die( __( 'Error', INCSUB_SUPPORT_LANG_DOMAIN ) );
						else {
							$link = $this->get_permalink();
							$link = add_query_arg( 'fid', $new_id, $link );
							$link = add_query_arg( 'created', 'true', $link );
							wp_redirect( $link );
						}

					}

					
				}

			}

		}

	}

}
