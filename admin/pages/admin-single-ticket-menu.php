<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Admin_Single_Ticket_Menu' ) ) {

	class MU_Support_Admin_Single_Ticket_Menu extends MU_Support_Menu {

		/**
		 * Status of the screen
		 */
		private $view;

		private $editing = false;

		private $updated = false;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			$this->includes();

			$this->page_title = __( 'Support Ticket', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __( 'Support Ticket', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = 'read';
			$this->menu_slug = 'single-ticket-manager';
			$this->submenu = true;
			$this->active_tab = 'details';
			$this->tabs = array(
				array(
					'slug' => 'details',
					'link' => add_query_arg( 'view', 'details' ),
					'label' => 'Ticket details'
				),
				array(
					'slug' => 'history',
					'link' => add_query_arg( 'view', 'history' ),
					'label' => 'Ticket history'
				)
			);

			parent::__construct( false );

			$this->ticket_id = 0;
			if ( isset( $_GET['tid'] ) )
				$this->ticket_id = absint( $_GET['tid'] );

			$this->active_tab = 'details';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'details', 'history' ) ) )
				$this->active_tab = $_GET['view'];

			if ( isset( $_GET['updated'] ) && 'true' == $_GET['updated'] )
				$this->updated = true;



			add_action( 'admin_init', array( &$this, 'validate_form' ) );
			add_filter( 'admin_title', array( &$this, 'set_page_title' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

		}

		/**
		 * Sets the page title
		 * 
		 * @since 1.8
		 * 
		 * @param String $title of the curren screen given by WP
		 * 
		 * @return String $title the new title
		 **/
		function set_page_title( $title ) {
			if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == $this->menu_slug ) {	
				return $this->page_title . $title;
			}
			else
				return $title;
		}

		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/ticket-history-table.php');
		}

		/**
		 * Enqueues the styles for the current screen
		 * 
		 * @since 1.8
		 * 
		 * @param String $hook The hook of the current screen givven by WP
		 **/
		public function enqueue_styles( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_register_style( 'single-ticket-menu', INCSUB_SUPPORT_ASSETS_URL . 'css/single-ticket-menu.css', array(), '20130402' );
				wp_enqueue_style( 'single-ticket-menu' );
			}
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			$model = MU_Support_System_Model::get_instance();
			if ( ! $model->is_current_blog_ticket( $this->ticket_id ) )
				wp_die( 'You do not have enough permissions to see the ticket', INCSUB_SUPPORT_LANG_DOMAIN );

			if ( ! $this->editing ) {
				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				// We don't need a message when updating a ticket
				$this->current_ticket['message'] = '';
			}

			?>
				<p><a class="button" href="<?php echo MU_Support_System::$admin_main_menu->get_permalink() ?>"><?php echo '&larr; ' . __( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></p>
			<?php

		
			if ( 'history' == $this->active_tab )
				$this->the_ticket_history( $this->ticket_details );
			else
				$this->the_ticket_details( $this->current_ticket );
		   
		}


		/**
		 * Renders a ticket details
		 * 
		 * @since 1.8
		 * 
		 * @param Array $current_ticket Current ticket Array
		 */
		private function the_ticket_details( $current_ticket ) {
			?>
				<table class="form-table">
					<h3><?php echo __( 'Ticket Subject', INCSUB_SUPPORT_LANG_DOMAIN ) . ': ' .  stripslashes_deep( $current_ticket['title'] ); ?></h3>
					<?php $this->render_row( 'Current Status', MU_Support_System::$ticket_status[ $current_ticket['ticket_status'] ] ); ?>
					<?php $this->render_row( 'Created On (GMT)', date_i18n( get_option("date_format") . ' ' . get_option("time_format"), strtotime( $current_ticket['ticket_opened'] ), true ) ); ?>


					<?php $this->render_row( __( 'Reporting User', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['user_name'] ); ?>
					<?php $this->render_row( __( 'Last Reply From', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['last_user_reply'] ); ?>
					<?php $this->render_row( __( 'Last Updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), date_i18n( get_option("date_format") . ' ' . get_option("time_format"), strtotime( $current_ticket['ticket_updated'] ), true ) ); ?>
					
					<?php 
						$blog_details = get_blog_details( $current_ticket['blog_id'] );
						if ( ! $blog_details ) {
							$markup = __( 'Unknown', INCSUB_SUPPORT_LANG_DOMAIN );
						}
						else {						
							$blog_address = get_blogaddress_by_id( $current_ticket['blog_id'] );
							$markup = '<a href="' . $blog_address . '">' . $blog_details->blogname . '</a>';
						}
						
						$this->render_row( 'Submitted from', $markup ); ?>

					<?php 
						$markup = ( ! empty( $current_ticket['admin_name'] ) ) ? $current_ticket['admin_name'] : __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN );
						$this->render_row( 'Staff Representative',  $markup ); 
					?>
				</table>
			<?php
		}


		/**
		 * Renders the ticket history messages
		 * 
		 * @since 1.8
		 * 
		 * @param Array $ticket_details Ticket Details
		 */
		function the_ticket_history( $ticket_details ) {

			if ( $this->is_error() ) {
				$this->render_errors();
			}
			elseif ( ! $this->is_error() && $this->updated ) {
				?>
					<div class="updated"><p><?php _e( 'Message added to ticket successfully', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
				<?php
			}

			$ticket_history_table = new MU_Support_Ticket_History_Table( $ticket_details, false );
			$ticket_history_table->prepare_items();
			$ticket_history_table->display();

			$this->the_ticket_form( $this->current_ticket );
		}

		/**
		 * Renders the ticket Edit Form
		 * 
		 * @since 1.8
		 * 
		 * @param Array $ticket_details Ticket Details
		 */
		function the_ticket_form( $current_ticket ) {

			$model = MU_Support_System_Model::get_instance();
			$categories = $model->get_ticket_categories();

			

			?>	
				<form id="edit-ticket-form" action="" method="post">
					<h2><?php _e( 'Add a new response', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
					<table class="form-table">
						<?php 

								// SUBJECT
								$response = ( ! $this->is_error( 'title' ) && ! $this->editing ) ? 'Re: ' : '';
								$markup = '<input type="text" name="subject" size="60" class="widefat" maxlength="100" value="' . $response . esc_attr( stripslashes_deep( $current_ticket['title'] ) ) . '">';
								$this->render_row( 
									__( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);

								// CATEGORY
								ob_start();
							?>
								<select name="category" id="category">
									<?php foreach ( $categories as $category ): ?>
										<option value="<?php echo $category['cat_id']; ?>" <?php selected( $category['cat_id'], $current_ticket['cat_id'] ); ?>><?php echo $category['cat_name']; ?></option>
									<?php endforeach; ?>
								</select>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);

								// PRIORITY
								ob_start();
							?>
								<select name="priority" id="priority">
									<?php foreach ( MU_Support_System::$ticket_priority as $key => $priority ): ?>
										<option value="<?php echo $key; ?>" <?php selected( $key, $current_ticket['ticket_priority'] ); ?>><?php echo $priority; ?></option>
									<?php endforeach; ?>
								</select>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);

								// MESSAGE
								ob_start();

							?>
								<span class="description"><?php _e("Please provide as much information as possible, so that the user can understand the solution/request.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span><br />
								<?php wp_editor( $current_ticket['message'], 'text-message' ); ?>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Add a reply', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);


								// CLOSE TICKET
								ob_start();
							?>
								<input type="checkbox" name="closeticket" id="closeticket" value="1" /> <strong><?php _e( 'Yes, close this ticket.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></strong><br />
								<span class="description"><?php _e("Once a ticket is closed, users can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__("Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN),  
									$markup
								);
							?>
						<input type="hidden" name="action" value="add-ticket-reply">
						<?php wp_nonce_field( 'edit-ticket' ); ?>
					</table>
					<p class="submit">
						<?php submit_button( __( 'Update ticket', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
					</p>
				</form>
			<?php

		}

		/**
		 * Gets the current ticket details
		 * 
		 * @since 1.8
		 * 
		 * @return Array Current ticket details
		 */
		private function get_current_ticket_details( $ticket_id ) {

			$model = MU_Support_System_Model::get_instance();
			$ticket_details = $model->get_ticket_details( $ticket_id );	
			
			if ( empty( $ticket_details ) )
				wp_die( __( "The ticket you're trying to find does not exist.", INCSUB_SUPPORT_LANG_DOMAIN ) );

			return $ticket_details; 
		}

		/**
		 * Validates the form
		 * 
		 * @since 1.8
		 */
		public function validate_form() {
			
			if ( isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] && isset( $_POST['action'] ) && 'add-ticket-reply' == $_POST['action'] ) {

				$model = MU_Support_System_Model::get_instance();
				if ( ! $model->is_current_blog_ticket( $this->ticket_id ) )
					wp_die( 'You do not have enough permissions to edit the ticket', INCSUB_SUPPORT_LANG_DOMAIN );

				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				$this->editing = true;

				if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-ticket' ) )
					wp_die( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN );

				$title = sanitize_text_field( stripslashes_deep( $_POST['subject'] ) );
				if ( empty( $title ) ) {
					$this->add_error( 'title', __( 'Title cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
					$this->current_ticket['title'] = 'Re: ' . $this->current_ticket['title'];
				}
				else {
					$this->current_ticket['title'] = $title;
				}

				if ( empty( $_POST['text-message'] ) ) {
					$this->add_error( 'message', __( 'Message cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
					$this->current_ticket['message'] = '';
				}
				else {
					$this->current_ticket['message'] = wpautop( stripslashes_deep( $_POST['text-message'] ) );
				}

				$this->current_ticket['cat_id'] = absint( $_POST['category'] );
				
				if ( array_key_exists( $_POST['priority'], MU_Support_System::$ticket_priority ) )
					$this->current_ticket['ticket_priority'] = $_POST['priority'];

				$status = isset( $_POST['closeticket'] ) ? 5 : 3;

				if ( ! $this->is_error() ) {

					// Adding a new response (ticket message)
					$response_id = $model->add_ticket_response( $this->ticket_id, $this->current_ticket['title'], $this->current_ticket['message'] );

					if ( ! $response_id )
						wp_die( 'Error while adding a new response, please try again.', INCSUB_SUPPORT_LANG_DOMAIN );
					
					// We need to update the ticket status
					$ticket_updated = $model->update_ticket_status(
						$this->ticket_id,
						$this->current_ticket['cat_id'],
						$this->current_ticket['ticket_priority'],
						$status
					);

					if ( ! $ticket_updated )
						wp_die( 'Error while setting the ticket status, please try with another response.', INCSUB_SUPPORT_LANG_DOMAIN );

					$user = get_userdata( get_current_user_id() );

					// Administrator mail
					$visit_link = remove_query_arg( 'view' );
					$args = array(
						'title'				=> $this->current_ticket['title'],
						'ticket_status'		=> MU_Support_System::$ticket_status[$status],
						'ticket_priority'	=> MU_Support_System::$ticket_priority[ $this->current_ticket['ticket_priority'] ],
						'visit_link'		=> $visit_link,
						'ticket_message'	=> $this->current_ticket['message'],
						'user_nicename'		=> $user->user_nicename,
						'site_name'			=> get_site_option( 'site_name' )
					);

					$mail_content = incsub_support_get_ticketadmin_mail_content( $args );

					$reply_to_id = $model->get_ticket_user_id( $this->ticket_id );
					$user_reply_to = get_userdata( $reply_to_id );
					$email_message = array(
						"to"		=> $user_reply_to->user_email,
						"subject"	=> __( "[#{$this->ticket_id}] ", INCSUB_SUPPORT_LANG_DOMAIN ) . $this->current_ticket['title'],
						"message"	=> $mail_content, // ends lang string
						"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option( 'incsub_support_from_name', get_bloginfo('blogname') ) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",
					); // ends array.

					wp_mail( $email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"] );

					$this->updated = true;

					$link = add_query_arg( 'updated', 'true' );
					wp_redirect( $link );

				}


			}
			

		}

	}

}
