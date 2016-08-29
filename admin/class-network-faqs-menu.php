<?php

class Incsub_Support_Network_FAQ_Menu extends Incsub_Support_Admin_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );


		
	}


	public function add_menu() {		
		parent::add_submenu_page(
			'ticket-manager',
			__( 'FAQ Manager', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'FAQ Manager', INCSUB_SUPPORT_LANG_DOMAIN ), 
			is_multisite() ? 'manage_network' : 'manage_options'
		);

		add_action( 'load-' . $this->page_id, array( $this, 'set_filters' ) );

	}

	public function set_filters() {
		// FAQs table filters
		add_filter( 'support_system_faqs_table_query_args', array( $this, 'set_faqs_table_query_args' ) );

		add_filter( 'support_system_faqs_table_menu_url', array( $this, 'get_menu_url' ) );

		if ( ! isset( $_GET['action'] ) && incsub_support_current_user_can( 'insert_faq' ) )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_faq_link' ) );

		if ( isset( $_GET['action'] ) && isset( $_GET['fid'] ) && 'edit' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', '__return_empty_string' );

		if ( isset( $_GET['action'] ) && 'add' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', array( $this, 'add_new_faq_title' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'mu-support-faq-css', INCSUB_SUPPORT_PLUGIN_URL . 'admin/assets/css/support-faqs-menu.css', array(), '20130402' );
	}

	public function add_new_faq_title( $title ) {
		return '<h2>' . esc_html( __( 'Add new FAQ', INCSUB_SUPPORT_LANG_DOMAIN ) ) . '</h2>';
	}

	public function add_new_faq_link( $title ) {
		$add_new_link = add_query_arg( 'action', 'add', $this->get_menu_url() );
		return '<h2>'. $this->get_menu_title() . ' <a href="' . esc_url( $add_new_link ) . '" class="add-new-h2">' . esc_html__( 'Add new FAQ', INCSUB_SUPPORT_LANG_DOMAIN ) . '</a></h2>';
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'incsub_support_faqs_per_page' == $option ) 
			return $value;

		return $status;
	}

	public function render_inner_page() {

		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;

		if ( 'edit' == $action && isset( $_GET['fid'] ) ) {
			$faq = incsub_support_get_faq( $_GET['fid'] );
			if ( ! $faq )
				wp_die( __( 'The FAQ does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );
			
			if ( isset( $_POST['category'] ) && $selected_category = incsub_support_get_faq_category( absint( $_POST['category'] ) ) )
				$category = $selected_category;
			else
				$category = $faq->cat_id;

			// Categories dropdown
			$categories_dropdown = incsub_support_faq_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $category,
					'name' => 'category'
				) 
			);

			$question = '';
			if ( ! empty( $_POST['question'] ) )
				$question = strip_tags( stripslashes_deep( $_POST['question'] ) );
			else
				$question = $faq->question;

			$answer = '';
			if ( ! empty( $_POST['answer'] ) )
				$answer = wp_kses_post( stripslashes_deep( $_POST['answer'] ) );
			else
				$answer = $faq->answer;

			$list_menu_url = $this->get_menu_url();

			include_once( 'views/edit-faq.php' );

		}
		else if ( 'add' == $action ) {
			if ( isset( $_POST['category'] ) && $selected_category = incsub_support_get_faq_category( absint( $_POST['category'] ) ) ) {
				$category = $selected_category;
			}
			else {
				$category = incsub_support_get_default_faq_category();
			}

			// Categories dropdown
			$categories_dropdown = incsub_support_faq_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $category->cat_id,
					'name' => 'category'
				) 
			);

			$question = '';
			if ( ! empty( $_POST['question'] ) ) {
				$question = stripslashes_deep( $_POST['question'] );
			}
			elseif( ! empty( $_REQUEST['tid'] ) ) {
				$ticket = incsub_support_get_ticket( $_REQUEST['tid'] );
				if ( $ticket )
					$question = $ticket->title;
			}

			$answer = '';
			if ( ! empty( $_POST['answer'] ) ) {
				$answer = stripslashes_deep( $_POST['answer'] );
			}
			elseif ( ! empty( $_REQUEST['rid'] ) ) {
				$reply = incsub_support_get_ticket_reply( $_REQUEST['rid'] );
				if ( $reply )
					$answer = $reply->message;
			}

			$list_menu_url = $this->get_menu_url();

			include_once( 'views/add-new-faq.php' );

		}
		else {
			include_once( 'inc/class-table-faqs.php' );

			$table = new Incsub_Support_FAQS_Table();
			$table->prepare_items();

			include_once( 'views/network-faqs.php' );
		}

	}

	public function on_load() {
		// Add screen options
		add_screen_option( 'per_page', array( 'label' => __( 'FAQs per page', INCSUB_SUPPORT_LANG_DOMAIN ), 'default' => 20, 'option' => 'incsub_support_faqs_per_page' ) );

		// Check filtering
		if ( isset( $_POST['filter_action'] ) || ! empty( $_POST['s'] ) ) {

			$filters = array(
				'category' => false,
				's' => false
			);

			$url = false;

			if ( ! empty( $_REQUEST['faq-cat'] ) && $cat_id = absint( $_REQUEST['faq-cat'] ) )
				$filters['category'] = $cat_id;


			if ( ! empty( $_REQUEST['s'] ) )
				$filters['s'] = stripslashes_deep( $_REQUEST['s'] );

			$url = $_SERVER['REQUEST_URI'];
			foreach ( $filters as $key => $value ) {
				if ( $value === false )
					$url = remove_query_arg( $key, $url );
				else
					$url = add_query_arg( $key, $value, $url );
			}

			wp_redirect( $url );
			exit();
			
		}

		// Adding a new FAQ?
		if ( isset( $_POST['submit-new-faq'] ) ) {
			check_admin_referer( 'add-new-faq' );

			$args = array();

			if ( empty( $_POST['answer'] ) )
				add_settings_error( 'support_system_submit_new_faq', 'empty_message', __( 'FAQ answer must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$args['answer'] = wpautop( stripslashes_deep( $_POST['answer'] ) );

			$question = strip_tags( stripslashes_deep( $_POST['question'] ) );
			if ( empty( $question ) )
				add_settings_error( 'support_system_submit_new_faq', 'empty_question', __( 'FAQ question must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$args['question'] = $question;

			$category = incsub_support_get_faq_category( absint( $_POST['category'] ) );
			if ( ! $category ) {
				add_settings_error( 'support_system_submit_new_faq', 'wrong_category', __( 'Selected category is not a valid one', INCSUB_SUPPORT_LANG_DOMAIN ) );
			}
			else {
				$args['cat_id'] = $category->cat_id;
			}

			if ( ! get_settings_errors( 'support_system_submit_new_faq' ) ) {
				$result = incsub_support_insert_faq( $args );
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'support_system_submit_new_faq', 'insert_error', $result->get_error_message() );
				}
				else {
					wp_redirect( $this->get_menu_url() );
					exit();
				}
			}
		}

		if ( isset( $_POST['submit-edit-faq'] ) ) {
			$faq_id = $_POST['faq-id'];
			check_admin_referer( 'edit-faq-' . $faq_id );

			$args = array();

			if ( empty( $_POST['answer'] ) )
				add_settings_error( 'support_system_submit_edit_faq', 'empty_message', __( 'FAQ answer must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$args['answer'] = wpautop( stripslashes_deep( $_POST['answer'] ) );

			$question = strip_tags( stripslashes_deep( $_POST['question'] ) );
			if ( empty( $question ) )
				add_settings_error( 'support_system_submit_edit_faq', 'empty_question', __( 'FAQ question must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$args['question'] = $question;

			$category = incsub_support_get_faq_category( absint( $_POST['category'] ) );
			if ( ! $category ) {
				add_settings_error( 'support_system_submit_edit_faq', 'wrong_category', __( 'Selected category is not a valid one', INCSUB_SUPPORT_LANG_DOMAIN ) );
			}
			else {
				$args['cat_id'] = $category->cat_id;
			}

			if ( ! get_settings_errors( 'support_system_submit_edit_faq' ) ) {
				$result = incsub_support_update_faq( $faq_id, $args );
				if ( is_wp_error( $result ) ) {
					add_settings_error( 'support_system_submit_edit_faq', 'insert_error', $result->get_error_message() );
				}
				else {
					wp_redirect( $this->get_menu_url() );
					exit();
				}
			}
		}
	}


	public function set_faqs_table_query_args( $args ) {

		$category = $this->get_filter( 'category' );
		$s = $this->get_filter( 's' );

		$args['s'] = $s;
		$args['category'] = $category;

		return $args;
	}

	private function get_filter( $slug ) {
		if ( ! isset( $_REQUEST[ $slug ] ) )
			return false;

		return $_REQUEST[ $slug ];
	}


}