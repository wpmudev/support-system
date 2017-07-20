<?php

class Incsub_Support_Tickets_Index_Shortcode extends Incsub_Support_Shortcode {
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'process_form' ) );
		if ( !is_admin() ) {
			add_shortcode( 'support-system-tickets-index', array( $this, 'render' ) );
		}
	}

	public function process_form() {
		if ( isset( $_POST['submit-ticket-details'] ) && incsub_support_current_user_can( 'update_ticket' ) ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			$ticket = incsub_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				return;

			$action = 'submit-ticket-details-' . $ticket_id; 
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$category = absint( $_POST['ticket-cat'] );
			$priority = absint( $_POST['ticket-priority'] );

			$args = array(
				'cat_id' => $category,
				'ticket_priority' => $priority
			);

			$staff_name = $_POST['ticket-staff'];
			$possible_users = MU_Support_System::get_super_admins();
			if ( in_array( $staff_name, $possible_users ) ) {
				$user = get_user_by( 'login', $staff_name );
				if ( $user )
					$args['admin_id'] = $user->data->ID;
			}

			if ( empty( $staff_name ) )
				$args['admin_id'] = 0;

			$result = incsub_support_update_ticket( $ticket_id, $args );

			if ( $result ) {
				$url = add_query_arg( 'ticket-details-updated', 'true' );
				wp_redirect( $url );
				exit;
			}

			
		}

		if ( isset( $_POST['submit-close-ticket'] ) && incsub_support_current_user_can( 'close_ticket', incsub_support_get_the_ticket_id() ) ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			$ticket = incsub_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				return;

			$action = 'submit-close-ticket-' . $ticket_id; 
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			if ( empty( $_POST['close-ticket'] ) )
				incsub_support_restore_ticket_previous_status( $ticket_id );
			else
				incsub_support_close_ticket( $ticket_id );

			$url = add_query_arg( 'ticket-closed-updated', 'true' );
			wp_redirect( $url );
			exit;
		}

		if ( isset( $_POST['support-system-submit-reply'] ) && incsub_support_current_user_can( 'insert_reply' ) ) {

			// Submitting a new reply from the front
			$fields = array_map( 'absint', $_POST['support-system-reply-fields'] );

			$ticket_id = $fields['ticket'];
			$user_id = $fields['user'];
			$blog_id = $fields['blog'];

			$action = 'support-system-submit-reply-' . $ticket_id . '-' . $user_id . '-' . $blog_id;
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$message = $_POST['support-system-reply-message'];

			if ( empty( $message ) )
				wp_die( __( 'The reply message cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$ticket = incsub_support_get_ticket( $ticket_id );
			
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			if ( $user_id != get_current_user_id() )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$args = array(
				'poster_id' => get_current_user_id(),
				'message' => $message
			);

			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = incsub_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded['result'], 'url' );
				}
				elseif ( $files_uploaded['error'] && ! empty( $files_uploaded['result'] ) ) {
					$error_message = '<ul>';
					foreach ( $files_uploaded['result'] as $error ) {
						$error_message .= '<li>' . $error . '</li>';			
					}
					$error_message .= '</ul>';
					wp_die( $error_message );
				}
			}

			$result = incsub_support_insert_ticket_reply( $ticket_id, $args );

			if ( ! $result )
				wp_die( __( 'There was an error while processing the form, please try again later', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$ticket = incsub_support_get_ticket( $ticket_id );

			if ( $ticket->admin_id && $ticket->admin_id === get_current_user_id() && $ticket->user_id != $ticket->admin_id ) {
				$status = 2;
			}
			elseif ( ! $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->ticket_status != 0 ) {
				$status = 1;
			}
			elseif ( ! $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->ticket_status == 0 ) {
				$status = 0;
			}
			elseif ( $ticket->admin_id && $ticket->user_id === get_current_user_id() && $ticket->user_id != $ticket->admin_id ) {
				$status = 3;
			}
			elseif ( $ticket->admin_id && $ticket->admin_id === get_current_user_id() ) {
				$status = 1;
			}
			else {
				$status = $ticket->ticket_status;
			}

			
			if ( $status != $ticket->ticket_status )
				incsub_support_ticket_transition_status( $ticket->ticket_id, $status );				

			
			$url = add_query_arg( 'support-system-reply-added', 'true' );
			$url = preg_replace( '/\#[a-zA-Z0-9\-]*$/', '', $url );
			$url .= '#support-system-reply-' . $result;
			wp_safe_redirect( $url );	
			exit;
			

		}
	}

	public function render( $atts ) {
		$this->start();

		if ( ! incsub_support_current_user_can( 'read_ticket' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'You must <a href="%s">log in</a> to get support', INCSUB_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'You don\'t have enough permissions to get support', INCSUB_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_tickets_list_message', $message, 'ticket-index' );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			return $this->end();
		}

		if ( incsub_support_is_tickets_page() )
			incsub_support_get_template( 'index', 'tickets' );
		elseif ( incsub_support_is_single_ticket() )
			incsub_support_get_template( 'single', 'ticket' );

		return $this->end();
	}
}