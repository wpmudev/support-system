<?php

class Incsub_Support_Shortcodes {

	public $shortcodes = array();

	public function __construct() {
		$this->init();
		$this->register_shortcodes();

		$this->init_tiny_mce_button();
	}

	private function init() {
		$this->shortcodes = apply_filters( 'support_system_shortccodes', array(
			'support-system-tickets-index' => array( $this, 'render_tickets_index' ),
			'support-system-submit-ticket-form' => array( $this, 'render_submit_ticket_form' )
		) );

		add_action( 'template_redirect', array( $this, 'process_forms' ) );
	}

	public function register_shortcodes() {
		foreach ( $this->shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	private function start() {
		incsub_support()->query->query();
		echo '<div id="support-system">';
		ob_start();
	}

	public function render_tickets_index() {

		$this->start();
		
		if ( ! incsub_support_current_user_can( 'read_ticket' ) )
			return $this->end();

		if ( incsub_support_is_tickets_index() )
			incsub_support_get_template( 'index', 'tickets' );
		elseif ( incsub_support_is_single_ticket() )
			incsub_support_get_template( 'single', 'ticket' );

		return $this->end();
	}

	private function end() {
		echo '</div><div style="clear:both"></div>';
		return ob_get_clean();
	}

	public function process_forms() {
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

			$ticket = incsub_support_get_ticket_b( $ticket_id );
			
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			if ( $user_id != get_current_user_id() )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$args = array(
				'poster_id' => get_current_user_id(),
				'message' => $message
			);
			$result = incsub_support_insert_ticket_reply( $ticket_id, $args );

			if ( ! $result )
				wp_die( __( 'There was an error while processing the form, please try again later', INCSUB_SUPPORT_LANG_DOMAIN ) );

			
			$url = add_query_arg( 'support-system-reply-added', 'true' );
			$url = preg_replace( '/\#[a-zA-Z0-9\-]*$/', '', $url );
			$url .= '#support-system-reply-' . $result;
			wp_safe_redirect( $url );	
			exit;
			

		}

		if ( isset( $_POST['support-system-submit-ticket'] ) && incsub_support_current_user_can( 'insert_reply' ) ) {

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


			$args = array(
				'title' => $subject,
				'message' => $message,
			);
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

	function render_submit_ticket_form() {
		$this->start();

		if ( ! incsub_support()->query->is_single_ticket ) {
			?>
				<h2><?php _e( 'Submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
				<form method="post" id="support-system-ticket-form" action="#support-system-ticket-form-wrap">
					<input type="text" name="support-system-ticket-subject" value="" placeholder="<?php esc_attr_e( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"/>
					<?php incsub_support_editor( 'ticket' ); ?>
					<?php wp_nonce_field( 'support-system-submit-ticket-' . get_current_user_id() . '-' . get_current_blog_id() ); ?>
					<br/>
					<input type="submit" name="support-system-submit-ticket" class="button small" value="<?php esc_attr_e( 'Submit Ticket', INCSUB_SUPPORT_LANG_DOMAIN ); ?>" />
					<div class="support-system-attachments"></div>
				</form>
				
			<?php
		}
		return $this->end();
	}

	// TinyMCE buttons ( Thanks to Woocommerce Shortcodes plugin: https://wordpress.org/plugins/woocommerce-shortcodes/)
	function init_tiny_mce_button() {
		if ( apply_filters( 'support_system_add_editor_shortcodes', true ) ) {
			add_action( 'admin_head', array( $this, 'add_shortcode_button' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_editor_admin_scripts' ) );
		}
	}

	function add_shortcode_button() {
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_shortcode_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'register_shortcode_button' ) );
		}
	}

	public function add_shortcode_tinymce_plugin( $plugins ) {
		$plugins['incsub_support_shortcodes'] = INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/js/editor-shortcodes.js';
		return $plugins;
	}

	public function register_shortcode_button( $buttons ) {
		array_push( $buttons, '|', 'incsub_support_shortcodes' );
		return $buttons;
	}

	public function enqueue_editor_admin_scripts() {
		wp_enqueue_style( 'incsub-support-shortcodes', INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/css/editor-shortcodes.css' );
	}

}