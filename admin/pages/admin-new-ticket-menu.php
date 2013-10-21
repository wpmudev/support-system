<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Admin_New_Ticket' ) ) {

	class MU_Support_Admin_New_Ticket_Menu extends MU_Support_Menu {

		/**
		 * Status of the screen
		 */
		private $view;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			$this->page_title = __( 'Add new ticket', INCSUB_SUPPORT_LANG_DOMAIN ); 
			$this->menu_title = __( 'Add new ticket', INCSUB_SUPPORT_LANG_DOMAIN );
			$this->capability = 'read';
			$this->menu_slug = 'add-new-ticket';
			$this->submenu = true;

			parent::__construct( false );

			$this->editing = false;

			add_action( 'admin_init', array( &$this, 'validate_form' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		}

		public function enqueue_scripts( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_enqueue_script( 'single-ticket-menu-js', INCSUB_SUPPORT_ASSETS_URL . 'js/single-ticket-menu.js', array(), '20130802' );
			}
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			if ( ! $this->editing ) {
				$this->current_ticket = array(
					'subject' 			=> '',
					'cat_id'			=> false,
					'ticket_priority'	=> false,
					'message'			=> ''
				);
			}
			else {
				if ( $this->is_error() )
					$this->render_errors();
			}

			$model = incsub_support_get_ticket_model();
			$categories = $model->get_ticket_categories();
			?>
				<form method="post" action="" enctype="multipart/form-data">
					<table class="form-table">
						
						<p><span class="description"><?php _e( '* All fields are required.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></p>
						<?php ob_start(); ?>
							<input type="text" name="subject" class="widefat" maxlength="100" value="<?php echo $this->current_ticket['subject']; ?>"><br/>
							<span class="description"><?php _e( '(max: 100 characters)', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
						<?php $this->render_row( __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

						<?php ob_start(); ?>
							<select name="category" id="category">
								<?php foreach ( $categories as $category ): ?>
									<option value="<?php echo $category['cat_id']; ?>" <?php selected( $category['cat_id'], $this->current_ticket['cat_id'] ); ?>><?php echo $category['cat_name']; ?></option>
								<?php endforeach; ?>
							</select>
						<?php $this->render_row( __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

						<?php ob_start(); ?>
							<select name="priority" id="priority">
								<?php foreach ( MU_Support_System::$ticket_priority as $key => $priority ): ?>
									<option value="<?php echo $key; ?>" <?php selected( $key, $this->current_ticket['ticket_priority'] ); ?>><?php echo $priority; ?></option>
								<?php endforeach; ?>
							</select>
						<?php $this->render_row( __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

						<?php ob_start(); ?>
							<?php wp_editor( $this->current_ticket['message'], 'message-text', array( 'media_buttons' => true ) ); ?>
						<?php $this->render_row( __( 'Problem description', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

						<?php
						// ATACHMENTS
							ob_start();
						?>				
						<ul id="attachments-list">
						
						</ul>			
							<button id="submit-new-attachment" class="button-secondary"><?php _e( 'Upload a new file', INCSUB_SUPPORT_LANG_DOMAIN ); ?></button>
							<?php
							$markup = ob_get_clean();
							$this->render_row( __( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ),  $markup ); 
						?>

						<?php do_action( 'support_new_ticket_fields', $this->current_ticket ); ?>
						
					
					</table>
					<p class="submit">
						<?php wp_nonce_field( 'add-new-ticket' ); ?>
						<?php submit_button( __( 'Submit new ticket', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
						<a href="<?php echo MU_Support_System::$admin_main_menu->get_permalink(); ?>" class="button-secondary"><?php _e( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>

		    		</p>
				</form>
			<?php
		   
		}

		/**
		 * Validates the form
		 * 
		 * @since 1.8
		 */
		public function validate_form() {
			if ( isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] && isset( $_POST['submit'] ) ) {

				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'add-new-ticket' ) )
					wp_die( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN );

				$this->editing = true;

				$this->current_ticket['subject'] = sanitize_text_field( stripslashes_deep( $_POST['subject'] ) );
				if ( empty( $this->current_ticket['subject'] ) )
					$this->add_error( 'subject', __( 'Subject must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );

				$this->current_ticket['cat_id'] = absint( $_POST['category'] );
				if ( ! $this->current_ticket['cat_id'] )
					 $this->add_error( 'category', __( 'Not valid category', INCSUB_SUPPORT_LANG_DOMAIN ) );

				$this->current_ticket['ticket_priority'] = absint( $_POST['priority'] );
				if ( ! array_key_exists( $this->current_ticket['ticket_priority'], MU_Support_System::$ticket_priority ) )
					 $this->add_error( 'ticket_priority', __( 'Not valid priority', INCSUB_SUPPORT_LANG_DOMAIN ) );

				$this->current_ticket['message'] = stripslashes_deep( $_POST['message-text'] );
				if ( empty( $this->current_ticket['message'] ) )
					$this->add_error( 'message', __( 'Message must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );

				$this->current_ticket['admin_id'] = 0;
				$model = incsub_support_get_ticket_model();
				$ticket_category = $model->get_ticket_category( $this->current_ticket['cat_id'] );
				$admin_id = 0;
				if ( ! empty( $ticket_category['user_id'] ) && $user = get_user_by( 'id', $ticket_category['user_id'] ) )
					$this->current_ticket['admin_id'] = $user->ID;

				if ( ! empty( $_FILES['attachments'] ) ) {
					$files_uploaded = MU_Support_System::upload_attachments( $_FILES['attachments'] );					

					if ( ! empty( $files_uploaded ) ) {
						$this->current_ticket['attachments'] = array();
						foreach( $files_uploaded as $file_uploaded ) {
							$this->current_ticket['attachments'][] = $file_uploaded['url'];
						}
					}
				}

				if ( ! $this->is_error() ) {

					$model = incsub_support_get_ticket_model();
					$ticket_id = $model->add_new_ticket( $this->current_ticket );

					if ( ! $ticket_id ) {
						$this->add_error( 'ticket-insert', __("Ticket Error: There was an error submitting your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN ) );
						return false;
					}

					do_action( 'support_new_ticket', $ticket_id, $this->current_ticket );
					
					// Current user data
					$user = get_userdata( get_current_user_id() );

					// First, a mail for the user that has just opened the ticket
					incsub_support_send_user_new_ticket_mail( $user, $ticket_id, $this->current_ticket );

					// Now, a mail for the main Administrator
					incsub_support_send_admin_new_ticket_mail( $user, $ticket_id, $this->current_ticket );

					wp_redirect( MU_Support_System::$admin_main_menu->get_permalink() );
				}

			}
		}

	}

}
