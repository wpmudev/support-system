<?php

/**
 * Support Network Single Ticket Menu
 */

if ( ! class_exists( 'MU_Support_Network_Single_Ticket_Menu' ) ) {

	class MU_Support_Network_Single_Ticket_Menu extends MU_Support_Menu {

		private $ticket_id;

		private $current_ticket;
		private $ticket_details;
		private $editing = false;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct( $just_object = false,  $is_network = true, $capability = 'manage_network' ) {

			$this->includes();

			$this->page_title = __( 'Support Ticket', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __( 'Support Ticket', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = $capability;
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

			parent::__construct( $is_network, $just_object );

			$this->ticket_id = 0;
			if ( isset( $_GET['tid'] ) )
				$this->ticket_id = absint( $_GET['tid'] );

			$this->active_tab = 'details';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'details', 'history' ) ) )
				$this->active_tab = $_GET['view'];

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
			add_action( 'admin_init', array( &$this, 'validate_form' ) );
			add_action( 'wp_loaded', array( &$this, 'check_ticket_as_view' ) );
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
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/ticket-history-table.php');
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
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {



			if ( ! $this->editing ) {
				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				// We don't need a message when updating a ticket
				$this->current_ticket['message'] = '';
			}

			$model = MU_Support_System_Model::get_instance();
			if ( $model->is_ticket_archived( $this->current_ticket['ticket_id'] ) ) {
				?>
				<div class="error"><p><?php _e( 'This ticket has been closed', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
				<?php
			}

			?>
				<p><a class="button" href="<?php echo MU_Support_System::$network_main_menu->get_permalink() ?>"><?php echo '&larr; ' . __( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></p>
			<?php

			if ( 'history' == $this->active_tab ) {
				$this->the_ticket_history( $this->ticket_details );
				$this->the_ticket_form( $this->current_ticket );
			}
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
					<?php $this->render_row( __( 'Created On (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), date_i18n( get_option("date_format") . ' ' . get_option("time_format"), strtotime( $current_ticket['ticket_opened'] ), true ) ); ?>


					<?php $this->render_row( __( 'Reporting User', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['user_name'] ); ?>
					<?php $this->render_row( __( 'Last Reply From', INCSUB_SUPPORT_LANG_DOMAIN ), $current_ticket['last_user_reply'] ); ?>
					<?php $this->render_row( __( 'Last Updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), date_i18n( get_option("date_format") . ' ' . get_option("time_format"), strtotime( $current_ticket['ticket_updated'] ), true ) ); ?>

					<?php do_action( 'support_network_ticket_details_fields', $current_ticket ); ?>
					
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
				                $markup = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->user_nicename . '</a>';
				        }
						
						$this->render_row( __( 'Submitted from', INCSUB_SUPPORT_LANG_DOMAIN ), $markup ); ?>

					<?php 
						$super_admins = is_multisite() ? get_super_admins() : $this->get_admins();
						ob_start();
					?>
						<select name="super-admins">
							<option value="" <?php echo empty( $current_ticket['admin_name'] ) ? 'selected' : '';  ?>><?php _e( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN ); ?></option>
							<?php foreach ( $super_admins as $user_name ): ?>
								<option value="<?php echo esc_attr( $user_name ); ?>" <?php selected( $current_ticket['admin_name'], $user_name ); ?>><?php echo $user_name; ?></option>
							<?php endforeach; ?>
						</select>
					<?php
						$markup = ob_get_clean();
						$this->render_row( __( 'Staff Representative', INCSUB_SUPPORT_LANG_DOMAIN ),  $markup ); 
					?>

					<?php 
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
		 * Get all administrators in a blog
		 * 
		 * @return Array collection of Administrators
		 */
		private function get_admins() {
			$users = get_users( array( 'role' => 'administrator' ) );

			$administrators = array();
			foreach ( $users as $user ) {
				$administrators[] = $user->data->user_login;
			}
			return $administrators;
		}

		/**
		 * Renders the ticket history messages
		 * 
		 * @since 1.8
		 * 
		 * @param Array $ticket_details Ticket Details
		 */
		function the_ticket_history( $ticket_details ) {
			$ticket_history_table = new MU_Support_Ticket_History_Table( $ticket_details );
			$ticket_history_table->prepare_items();
			$ticket_history_table->display();
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
			$closed = $model->is_ticket_archived( $this->current_ticket['ticket_id'] );

			if ( $this->is_error() ) {
				
				?>
					<div class="error">
						<ul>
							<?php foreach ( $this->get_errors() as $error ): ?>
								<li><?php echo $error; ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php
			}

			?>	
				<form id="edit-ticket-form" action="" method="post">
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

								// RESPONSIBILITY
								ob_start();
							?>
								<select name="responsibility" id="responsibility">
									<?php if ( $current_ticket['admin_id'] == get_current_user_id() ): ?>
										<option selected="selected" value="keep"><?php _e("Keep Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
										<option value="punt"><?php _e("Give Up Responsibility To Allow Another Admin To Accept", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
									<?php else: ?>
										<option selected="selected" value="accept"><?php _e("Accept Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
										<?php if ( ! empty( $current_ticket['admin_id'] ) ): ?>
											<option value="help"><?php _e("Keep Current Admin, And Just Help Out With A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
										<?php endif; ?>
									<?php endif; ?>
								</select>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Ticket Responsibility', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);

								// MESSAGE
								ob_start();

							?>
								<span class="description"><?php _e("Please provide as much information as possible, so that the user can understand the solution/request.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span><br />
								<?php wp_editor( $current_ticket['message'], 'message-text', array( 'media_buttons' => true ) ); ?>
							<?php
								$markup = ob_get_clean();
								$this->render_row( 
									__( 'Add a reply', INCSUB_SUPPORT_LANG_DOMAIN ),  
									$markup
								);


								// CLOSE TICKET
								ob_start();
							?>
								<input type="checkbox" name="closeticket" id="closeticket" value="1" <?php checked( $closed ); ?>/> <strong><?php _e( 'Yes, close this ticket.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></strong><br />
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
		 * Chcks a ticket as viewed already by a super admin
		 * 
		 * @since 1.8
		 */
		public function check_ticket_as_view() {
			if ( is_super_admin() && isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] && isset( $_GET['tid'] ) ) {
				$ticket_id = absint( $_GET['tid'] );
				$model = MU_Support_System_Model::get_instance();
				$model->check_ticket_as_viewed( $ticket_id );
			}
		}

		/**
		 * Validates the form
		 * 
		 * @since 1.8
		 * 
		 */
		public function validate_form() {

			if ( isset( $_POST['submit'] ) && isset( $_POST['action'] ) && 'add-ticket-reply' == $_POST['action'] ) {

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

				if ( empty( $_POST['message-text'] ) ) {
					$this->add_error( 'message', __( 'Message cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
					$this->current_ticket['message'] = '';
				}
				else {
					$this->current_ticket['message'] = wpautop( stripslashes_deep( $_POST['message-text'] ) );
				}


				$this->current_ticket['cat_id'] = absint( $_POST['category'] );
				
				if ( array_key_exists( $_POST['priority'], MU_Support_System::$ticket_priority ) )
					$this->current_ticket['ticket_priority'] = $_POST['priority'];
				
					
				if ( in_array( $_POST['responsibility'], MU_Support_System::$responsibilities ) )
					$responsibility = $_POST['responsibility'];
				else
					$responsibility = 'accept';
				

				$status = isset( $_POST['closeticket'] ) ? 5 : 2;

				if ( ! $this->is_error() ) {
					$model = MU_Support_System_Model::get_instance();

					$response_id = $model->add_ticket_response( $this->ticket_id, $this->current_ticket['title'], $this->current_ticket['message'] );

					if ( ! $response_id )
						wp_die( 'Error while adding a new response, please try again.', INCSUB_SUPPORT_LANG_DOMAIN );
					
					$ticket_updated = $model->update_ticket_status(
						$this->ticket_id,
						$this->current_ticket['cat_id'],
						$this->current_ticket['ticket_priority'],
						$status,
						$responsibility
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

					$headers[] = 'MIME-Version: 1.0';
						$headers[] = 'From: ' . get_site_option( 'incsub_support_from_name', get_bloginfo('blogname') ) . ' <' . get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) . '>';
					$email_message = array(
						"to"		=> $user_reply_to->user_email,
						"subject"	=> __( "[#{$this->ticket_id}] ", INCSUB_SUPPORT_LANG_DOMAIN ) . $this->current_ticket['title'],
						"message"	=> $mail_content, // ends lang string
						"headers"	=> $headers
					); // ends array.

					wp_mail( $email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"] );

					// Getting a super admin email
					$admin_email = '';
					if ( ! empty( $admins ) ) {
						$admin_user = get_user_by( 'login', $admins[0] );
						$admin_email = $admin_user->user_email;
					}

					wp_mail( $admin_email, $email_message["subject"], $email_message["message"], $email_message["headers"] );

					// Redirecting to ticket history
					$link = remove_query_arg( 'view' );
					$link = add_query_arg( 'view', 'history', $link );
					wp_redirect( $link );
				}
				
			}
			elseif ( isset( $_POST['submit-details'] ) && isset( $_POST['action'] ) && 'update-ticket-details' == $_POST['action']  && isset( $_GET['page'] ) && $this->menu_slug == $_GET['page'] ) {

				if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-ticket-details' ) )
					wp_die( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN );

				if ( ! isset( $_POST['ticket-id'] ) || ! $ticket_id = absint( $_POST['ticket-id'] ) )
					return false;

				$this->ticket_details = $this->get_current_ticket_details( $this->ticket_id );
				$this->current_ticket = $this->ticket_details[0];

				$model = MU_Support_System_Model::get_instance();
				$possible_users = array_merge( is_multisite() ? get_super_admins() : $this->get_admins(), array( 'empty', '' ) );
				if ( isset( $_POST['super-admins'] ) && in_array( $_POST['super-admins'], $possible_users ) ) {
					$user = get_user_by( 'login', $_POST['super-admins'] );
					if ( is_object( $user ) )
						$model->update_ticket_field( $ticket_id, 'admin_id', $user->data->ID );
					else
						$model->update_ticket_field( $ticket_id, 'admin_id', 0 );
				}

				$priority = $this->current_ticket['ticket_priority'];
				if ( isset( $_POST['priority'] ) && array_key_exists( $_POST['priority'], MU_Support_System::$ticket_priority ) ) {
					$model->update_ticket_field( $ticket_id, 'ticket_priority', $_POST['priority'] );
					$this->current_ticket['ticket_priority'] = $_POST['priority'];
					$priority = $this->current_ticket['ticket_priority'];
				}

				$closed = $model->is_ticket_archived( $ticket_id );
				if ( isset( $_POST['close-ticket'] ) ) {
					$model->update_ticket_status( $ticket_id, $this->current_ticket['cat_id'], $priority, 5 );
					if ( ! $closed ) {
						// Was not closed, send an email to the user

						$user = get_userdata( $this->current_ticket['user_id'] );

						// Variables for the message
						if ( ! is_object( MU_Support_System::$admin_single_ticket_menu ) )
							$admin_menu = new MU_Support_Admin_Single_Ticket_Menu( true );
						else
							$admin_menu = MU_Support_System::$admin_single_ticket_menu;

						$visit_link = $admin_menu->get_permalink();

						$visit_link 		= add_query_arg(
							'tid',
							$ticket_id,
							$visit_link
						);

						// Email arguments
						$args = array(
							'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
							'title' 				=> $this->current_ticket['subject'],
							'ticket_url' 			=> $visit_link,
							'ticket_priority'		=> MU_Support_System::$ticket_priority[ $this->current_ticket['ticket_priority'] ]
						);
						$mail_content = incsub_get_closed_ticket_mail_content( $args );

						$headers[] = 'MIME-Version: 1.0';
						$headers[] = 'From: ' . get_site_option( 'incsub_support_from_name', get_bloginfo('blogname') ) . ' <' . get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) . '>';
						$email_message = array(
							"to"		=> $user->data->user_email,
							"subject"	=> __( "New Support Ticket: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $this->current_ticket['subject'],
							"message"	=> $mail_content, // ends lang string
							"headers"	=> $headers
						); // ends array.

						wp_mail( $email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"] );

					}
					
				}
				else {
					$model->update_ticket_status( $ticket_id, $this->current_ticket['cat_id'], $priority, 0 );
				}

			}

		}

	}

}
