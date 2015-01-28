<?php

class Incsub_Support_Submit_Ticket_Form_Shortcode extends Incsub_Support_Shortcode {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'process_form' ) );
		add_shortcode( 'support-system-submit-ticket-form', array( $this, 'render' ) );
	}

	public function process_form() {
		if ( isset( $_POST['support-system-submit-ticket'] ) && incsub_support_current_user_can( 'insert_ticket' ) ) {

			$user_id = get_current_user_id();
			$blog_id = get_current_blog_id();

			$action = 'support-system-submit-ticket-' . $user_id . '-' . $blog_id;
			if ( ! wp_verify_nonce( $_POST['_wpnonce'], $action ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$subject = $_POST['support-system-ticket-subject'];
			if ( empty( $subject ) )
				wp_die( __( 'Please, insert a subject for the ticket', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$message = $_POST['support-system-ticket-message'];
			if ( empty( $message ) )
				wp_die( __( 'Please, insert a message for the ticket', INCSUB_SUPPORT_LANG_DOMAIN ) );

			if ( isset(  $_POST['support-system-ticket-priority'] ) )
				$priority = absint( $_POST['support-system-ticket-priority'] );
			else
				$priority = 0;

			$args = array(
				'title' => $subject,
				'message' => $message,
				'priority' => $priority
			);

			if ( ! empty( $_FILES['support-attachment'] ) ) {
				$files_uploaded = incsub_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

				if ( ! empty( $files_uploaded ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded, 'url' );
				}
			}

			if ( isset( $_POST['support-system-ticket-category'] ) && absint( $_POST['support-system-ticket-category'] ) ) {
				$args['cat_id'] = absint( $_POST['support-system-ticket-category'] );
			}

			$args['blog_id'] = $blog_id;
			if ( ! empty( $_POST['support-system-ticket-blog'] ) ) {
				$blog_id = absint( $_POST['support-system-ticket-blog'] );
				$list = wp_list_pluck( get_blogs_of_user( $user_id ), 'userblog_id' );
				if ( in_array( $blog_id, $list ) )
					$args['blog_id'] = $blog_id;
			}

			$ticket_id = incsub_support_insert_ticket( $args );

			if ( is_wp_error( $ticket_id ) )
				wp_die( $ticket_id->get_error_message() );

			$redirect_to = incsub_support_get_support_page_url();
			if ( $redirect_to ) {
				wp_redirect( add_query_arg( 'tid', $ticket_id, $redirect_to ) );
				exit;
			}

		}
	}

	public function render( $atts ) {
		$this->start();

		if ( ! incsub_support_current_user_can( 'insert_ticket' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'You must <a href="%s">log in</a> to submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'You don\'t have enough permissions to submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_submit_ticket_form_message', $message, 'ticket-form' );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			return $this->end();
		}

		$defaults = array(
			'blog_field' => true,
			'priority_field' => true,
			'category_field' => true
		);

		$atts = wp_parse_args( $atts, $defaults );
		extract( $atts );

		$blog_field = (bool)$blog_field;

		if ( ! incsub_support()->query->is_single_ticket ) {
			?>
				<h2><?php _e( 'Submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
				<form method="post" id="support-system-ticket-form" action="#support-system-ticket-form-wrap" enctype="multipart/form-data">
					
					<input type="text" name="support-system-ticket-subject" value="" placeholder="<?php esc_attr_e( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"/>
					<br/>

					<?php if ( $priority_field ): ?>
						<?php incsub_support_priority_dropdown( array( 'name' => 'support-system-ticket-priority', 'echo' => true ) ); ?><br/>
					<?php endif; ?>

					<?php if ( $category_field ): ?>
						<?php incsub_support_ticket_categories_dropdown( array( 'name' => 'support-system-ticket-category', 'echo' => true ) ); ?><br/>
					<?php endif; ?>
					
					<br/>
					<?php if ( $blog_field && is_multisite() ): ?>
						<label for="support-system-ticket-blog">
							<?php _e( 'Are you reporting a ticket for a specific site?', INCSUB_SUPPORT_LANG_DOMAIN ); ?>
							<?php incsub_support_user_sites_dropdown( array( 'name' => 'support-system-ticket-blog', 'echo' => true ) ); ?>
						</label>
					<?php endif; ?>
					
					<div class="support-system-attachments"></div>

					<?php incsub_support_editor( 'ticket' ); ?>
					<?php wp_nonce_field( 'support-system-submit-ticket-' . get_current_user_id() . '-' . get_current_blog_id() ); ?>
					<br/>

					<input type="submit" name="support-system-submit-ticket" class="button small" value="<?php esc_attr_e( 'Submit Ticket', INCSUB_SUPPORT_LANG_DOMAIN ); ?>" />
					
				</form>
				
			<?php
		}
		return $this->end();
	}
}