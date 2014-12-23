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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );

		add_action( 'wp_enqueue_scripts', array( &$this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts' ) );
		add_action( 'wp_footer', array( &$this, 'enqueue_scripts' ) );
	}

	public function register_styles() {
		$stylesheet = apply_filters( 'support_system_front_stylesheet', false );
		if ( $stylesheet )
			wp_register_style( 'support-system', $stylesheet, array(), INCSUB_SUPPORT_PLUGIN_VERSION );
	}

	public function register_scripts() {
		wp_register_script( 'support-system', INCSUB_SUPPORT_PLUGIN_URL . '/assets/js/support-system.js', array( 'jquery' ), INCSUB_SUPPORT_PLUGIN_VERSION, true );
		wp_register_script( 'support-system-init', INCSUB_SUPPORT_PLUGIN_URL . '/assets/js/support-system-init.js', array( 'support-system' ), INCSUB_SUPPORT_PLUGIN_VERSION, true );

		$l10n = array(
			'button_text' => __( 'Add files...', INCSUB_SUPPORT_LANG_DOMAIN ),
			'remove_file_title' => __( 'Remove file', INCSUB_SUPPORT_LANG_DOMAIN ),
			'remove_link_text' => __( 'Remove file', INCSUB_SUPPORT_LANG_DOMAIN )
		);
		wp_localize_script( 'support-system-init', 'support_system_i18n', $l10n );
	}

	public function enqueue_scripts() {
		if ( is_support_system() ) {
			wp_enqueue_script( 'support-system-init' );
			wp_enqueue_style( 'support-system' );
		}
	}

	public function register_shortcodes() {
		foreach ( $this->shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	private function start() {
		echo '<div id="support-system">';
		ob_start();
	}

	public function render_tickets_index() {

		$this->start();

		if ( ! incsub_support_current_user_can( 'read_ticket' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'You must <a href="%s">log in</a> to get support', INCSUB_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'You don\'t have enough permissions to get support', INCSUB_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_tickets_list_message', $message );
			?>
				<div class="support-system-alert warning">
					<?php echo $message; ?>
				</div>
			<?php
			return $this->end();
		}

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

				if ( ! empty( $files_uploaded ) ) {
					$args['attachments'] = wp_list_pluck( $files_uploaded, 'url' );
				}
			}

			$result = incsub_support_insert_ticket_reply( $ticket_id, $args );

			if ( ! $result )
				wp_die( __( 'There was an error while processing the form, please try again later', INCSUB_SUPPORT_LANG_DOMAIN ) );

			
			$url = add_query_arg( 'support-system-reply-added', 'true' );
			$url = preg_replace( '/\#[a-zA-Z0-9\-]*$/', '', $url );
			$url .= '#support-system-reply-' . $result;
			wp_safe_redirect( $url );	
			exit;
			

		}

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

	function render_submit_ticket_form( $atts ) {
		$this->start();

		if ( ! incsub_support_current_user_can( 'insert_ticket' ) ) {
			if ( ! is_user_logged_in() )
				$message = sprintf( __( 'You must <a href="%s">log in</a> to submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN ), wp_login_url( get_permalink() ) );
			else
				$message = __( 'You don\'t have enough permissions to submit a new ticket', INCSUB_SUPPORT_LANG_DOMAIN );
			
			$message = apply_filters( 'support_system_not_allowed_submit_ticket_form_message', $message );
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
			add_filter( 'mce_external_languages', array( $this, 'add_tinymce_i18n' ) );
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

	public function add_tinymce_i18n( $i18n ) {
		$i18n['support_system_shortcodes'] = INCSUB_SUPPORT_PLUGIN_DIR . '/admin/inc/tinymce-shortcodes-i18n.php';

		return $i18n;
	}

}