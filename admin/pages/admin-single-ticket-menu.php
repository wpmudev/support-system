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
		public function __construct( $just_object = false ) {

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
					'label' => __( 'Ticket details', INCSUB_SUPPORT_LANG_DOMAIN )
				),
				array(
					'slug' => 'history',
					'link' => add_query_arg( 'view', 'history' ),
					'label' => __( 'Update ticket', INCSUB_SUPPORT_LANG_DOMAIN )
				)
			);

			parent::__construct( false, $just_object );

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
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
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

		public function enqueue_scripts( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_enqueue_script( 'single-ticket-menu-js', INCSUB_SUPPORT_ASSETS_URL . 'js/single-ticket-menu.js', array(), '20130802' );
			}
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

			if ( ! current_user_can( 'manage_options' ) ) {
				$privacy = MU_Support_System::$settings['incsub_ticket_privacy'];
				if ( 'requestor' == $privacy  && $model->get_ticket_user_id( $this->ticket_id ) != get_current_user_id() ) {
					wp_die( 'You do not have enough permissions to see the ticket', INCSUB_SUPPORT_LANG_DOMAIN );
				}
			}

			if ( ! $this->editing ) {
				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				// We don't need a message when updating a ticket
				$this->current_ticket['message'] = '';
			}

			$closed = $model->is_ticket_archived( $this->current_ticket['ticket_id'] );
			if ( $closed ) {
				if ( ! current_user_can( 'manage_options' ) )
					$message =  __( 'This ticket has been closed. Please, contact a Super Admin if you want to reopen it.', INCSUB_SUPPORT_LANG_DOMAIN );
				else
					$message =  __( 'This ticket has been closed.', INCSUB_SUPPORT_LANG_DOMAIN );

				?>
				<div class="error"><p><?php echo $message; ?></p></div>
				<?php
			}

			?>
				<p><a class="button" href="<?php echo MU_Support_System::$admin_main_menu->get_permalink() ?>"><?php echo '&larr; ' . __( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></p>
			<?php

		
			if ( 'history' == $this->active_tab )
				$this->the_ticket_history( $this->ticket_details, $closed );
			else {
				$this->the_ticket_details( $this->current_ticket );
			}
		   
		}


		/**
		 * Renders a ticket details
		 * 
		 * @since 1.8
		 * 
		 * @param Array $current_ticket Current ticket Array
		 */
		private function the_ticket_details( $current_ticket ) {

			$model = MU_Support_System_Model::get_instance();
			?>
			<form method="post" action="">
				<table class="form-table">
					<h3><?php echo __( 'Ticket Subject', INCSUB_SUPPORT_LANG_DOMAIN ) . ': ' .  stripslashes_deep( $current_ticket['title'] ); ?></h3>
					<?php $this->render_row( __( 'Current Status', INCSUB_SUPPORT_LANG_DOMAIN ), MU_Support_System::$ticket_status[ $current_ticket['ticket_status'] ] ); ?>
					<?php $this->render_row( __( 'Created On (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), get_date_from_gmt( $current_ticket['ticket_opened'], get_option("date_format") ." ". get_option("time_format") ) ); ?>


					<?php $this->render_row( __( 'Reporting User', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['user_name'] ); ?>
					<?php $this->render_row( __( 'Last Reply From', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['last_user_reply'] ); ?>
					<?php $this->render_row( __( 'Last Updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), get_date_from_gmt( $current_ticket['ticket_updated'], get_option("date_format") ." ". get_option("time_format") ) ); ?>

					<?php do_action( 'support_ticket_details_fields', $current_ticket ); ?>
					
					<?php 
						$markup = __( 'Unknown', INCSUB_SUPPORT_LANG_DOMAIN );
						if ( is_multisite() ) {
				            $blog_details = get_blog_details( $current_ticket['blog_id'] );
				            
				            if ( ! empty( $blog_details ) ) {
				                $blog_address = get_blogaddress_by_id( $current_ticket['blog_id'] );
								$markup = '<a href="' . $blog_address . '">' . $blog_details->blogname . '</a>';
							}
				        }
				        else {
				            $user = get_userdata( $current_ticket['user_id'] );
				            if ( ! empty( $user ) )
				                $markup = $user->user_nicename;
				        }
						
						$this->render_row( __( 'Submitted from', INCSUB_SUPPORT_LANG_DOMAIN ), $markup );

						$markup = ( ! empty( $current_ticket['admin_name'] ) ) ? $current_ticket['admin_name'] : __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN );
						$this->render_row( __( 'Staff Representative', INCSUB_SUPPORT_LANG_DOMAIN ),  $markup ); 
					?>

					<?php 
						if ( current_user_can( 'manage_options' ) ):
							$current_priority = $current_ticket['ticket_priority'];
							ob_start();
						?>
							<select name="priority">
								<?php foreach ( MU_Support_System::$ticket_priority as $key => $priority ): ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_priority, $key ); ?>><?php echo $priority; ?></option>
								<?php endforeach; ?>
							</select>
						<?php
							$markup = ob_get_clean();
							$this->render_row( __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),  $markup ); 

							$ticket_closed = $model->is_ticket_archived( absint( $this->current_ticket['ticket_id'] ) );
							ob_start();
						?>
							<input name="close-ticket" type="checkbox" <?php checked( $ticket_closed ); ?> />
						<?php
							$markup = ob_get_clean();
							$this->render_row( '<strong>' . __( 'Ticket closed', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong>',  $markup ); 
						endif;
					?>
				</table>
				<?php wp_nonce_field( 'update-ticket-details' ); ?>
				<input type="hidden" name="action" value="update-ticket-details">
				<input type="hidden" name="ticket-id" value="<?php echo $current_ticket['ticket_id']; ?>">
				<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ),  'primary', 'submit-details' ); ?>
			</form>
			<?php
		}


		/**
		 * Renders the ticket history messages
		 * 
		 * @since 1.8
		 * 
		 * @param Array $ticket_details Ticket Details
		 */
		function the_ticket_history( $ticket_details, $closed = false ) {

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

			$model = MU_Support_System_Model::get_instance();

			if ( is_super_admin() || current_user_can( 'manage_options' ) || ( $model->get_ticket_user_id( $this->current_ticket['ticket_id'] ) == get_current_user_id() ) ) {
				if ( ! $closed )
					$this->the_ticket_form( $this->current_ticket );
			}
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
				<form id="edit-ticket-form" action="" method="post" enctype="multipart/form-data">
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
								<?php wp_editor( $current_ticket['message'], 'text-message', array( 'media_buttons' => true ) ); ?>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Add a reply', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);


								// CLOSE TICKET
								if ( current_user_can( 'manage_options' ) ):
									ob_start();
								?>
									<input type="checkbox" name="closeticket" id="closeticket" value="1" /> <strong><?php _e( 'Yes, close this ticket.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></strong><br />
									<span class="description"><?php _e("Once a ticket is closed, users can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
								<?php
									$markup = ob_get_clean();
									$this->render_row( 
										__( "Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN ),  
										$markup
									);
								endif;
								?>

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
			
			if ( isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] && isset( $_POST['action'] ) && 'add-ticket-reply' == $_POST['action']  && isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] ) {


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

					// Adding a new response (ticket message)
					$response_id = $model->add_ticket_response( $this->ticket_id, $this->current_ticket['title'], $this->current_ticket['message'], $this->current_ticket['attachments'] );

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


					$current_user_id = get_current_user_id();
					$user = get_userdata( get_current_user_id() );

					if ( empty( $this->current_ticket['admin_id'] ) ) {
						// Ticket not assigned to any staff
						// Send to ticket creator & Main Super Admin
						$super_admin = MU_Support_System::get_main_admin_details();
						$creator = get_userdata( $this->current_ticket['user_id'] );

						incsub_support_send_user_reply_mail( $creator, $user, $this->ticket_id, $this->current_ticket );

						incsub_support_send_admin_reply_mail( $super_admin, $user, $this->ticket_id, $this->current_ticket );

					}
					else {
						
						if ( $current_user_id == absint( $this->current_ticket['admin_id'] ) ) {
							// Response by assigned staff
							// Send to creator
							$creator = get_userdata( $this->current_ticket['user_id'] );

							incsub_support_send_user_reply_mail( $creator, $user, $this->ticket_id, $this->current_ticket );
							
						}
						elseif ( $current_user_id == absint( $this->current_ticket['user_id'] ) ) {
							// Response by creator
							// Send to Staff
							$staff = get_userdata( $this->current_ticket['admin_id'] );

							incsub_support_send_admin_reply_mail( $staff, $user, $this->ticket_id, $this->current_ticket );
						}
						else {
							// Response by none of them
							// Send to Creator & Staff
							$staff = get_userdata( $this->current_ticket['admin_id'] );
							$creator = get_userdata( $this->current_ticket['user_id'] );

							incsub_support_send_user_reply_mail( $creator, $user, $this->ticket_id, $this->current_ticket );
							incsub_support_send_admin_reply_mail( $staff, $user, $this->ticket_id, $this->current_ticket );

						}
					}

					// Ticket has been closed? Then send an additional email to the user
					if ( 5 == $status ) {
						incsub_support_send_user_closed_mail( $this->ticket_id );
					}

					$link = add_query_arg( 'updated', 'true' );
					wp_redirect( $link );

				}


			}
			elseif ( isset( $_POST['submit-details'] ) && isset( $_POST['action'] ) && 'update-ticket-details' == $_POST['action'] && isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] ) {

				if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-ticket-details' ) )
					wp_die( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN );

				if ( ! isset( $_POST['ticket-id'] ) || ! $ticket_id = absint( $_POST['ticket-id'] ) )
					return false;

				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				$model = MU_Support_System_Model::get_instance();

				$priority = $this->current_ticket['ticket_priority'];
				if ( isset( $_POST['priority'] ) && array_key_exists( $_POST['priority'], MU_Support_System::$ticket_priority ) ) {
					$model->update_ticket_field( $ticket_id, 'ticket_priority', $_POST['priority'] );
					$this->current_ticket['ticket_priority'] = $_POST['priority'];
					$priority = $this->current_ticket['ticket_priority'];
				}

				$closed = $model->is_ticket_archived( $ticket_id );
				if ( isset( $_POST['close-ticket'] ) ) {
					if ( ! $closed ) {
						// Was not closed, send an email to the user
						incsub_support_send_user_closed_mail( $ticket_id );
					}

					$model->update_ticket_status( $ticket_id, $this->current_ticket['cat_id'], $priority, 5 );
				}
				else {
					$model->update_ticket_status( $ticket_id, $this->current_ticket['cat_id'], $priority, 0 );
				}

			}
			

		}

	}

}
