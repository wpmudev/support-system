<?php

class Incsub_Support_Network_Menu extends Incsub_Support_Admin_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	public function enqueue_styles( $page_id ) {
		if ( $page_id === $this->page_id )
			wp_enqueue_style( 'support-menu-styles', INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/css/support-menu.css' );
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'incsub_support_tickets_per_page' == $option ) 
			return $value;

		return $status;
	}


	public function add_menu() {
		parent::add_menu_page(
			__( 'Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'Support', INCSUB_SUPPORT_LANG_DOMAIN ), 
			'manage_network',
			'dashicons-sos'
		);

		if ( isset( $_GET['action'] ) && isset( $_GET['tid'] ) && 'edit' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', '__return_empty_string' );

	}

	public function on_load() {

		// Add screen options
		add_screen_option( 'per_page', array( 'label' => __( 'Tickets per page', INCSUB_SBE_LANG_DOMAIN ), 'default' => 20, 'option' => 'incsub_support_tickets_per_page' ) );

		// Check filtering
		if ( isset( $_POST['filter_action'] ) ) {

			$filters = array(
				'category' => false,
				'priority' => false
			);

			$url = false;

			if ( ! empty( $_POST['ticket-cat'] ) && $cat_id = absint( $_POST['ticket-cat'] ) )
				$filters['category'] = $cat_id;



			if ( isset( $_POST['ticket-priority'] ) && $_POST['ticket-priority'] !== '' )
				$filters['priority'] = $_POST['ticket-priority'];
			
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

		// Are we updating a ticket?
		if ( ! empty( $_POST['submit-ticket-details'] ) && 'details' === $this->get_current_edit_ticket_tab() ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'update-ticket-details-' . $ticket_id );

			if ( ! current_user_can( 'manage_network' ) )
				return;

			$ticket = incsub_support_get_ticket_b( $ticket_id );
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$plugin = incsub_support();
			$args = array();

			// Update Super Admin
			if ( isset( $_POST['super-admins'] ) ) {
				$possible_users = array_merge( $plugin::get_super_admins() );
				if ( in_array( $_POST['super-admins'], $possible_users ) ) {
					$user = get_user_by( 'login', $_POST['super-admins'] );
					if ( $user )
						$args['admin_id'] = $user->data->ID;
				}

				if ( empty( $_POST['super-admins'] ) )
					$args['admin_id'] = 0;
			}

			if ( isset( $_POST['ticket-priority'] ) ) {
				$possible_values = array_keys( $plugin::$ticket_priority );
				if ( in_array( absint( $_POST['ticket-priority'] ), $possible_values ) )
					$args['ticket_priority'] = absint( $_POST['ticket-priority'] );
			}

			// Close ticket?
			if ( isset( $_POST['close-ticket'] ) ) {
				incsub_support_close_ticket( $ticket_id );
			}
			else {
				incsub_support_open_ticket( $ticket_id );
			}

			incsub_support_update_ticket( $ticket_id, $args );

			$redirect = add_query_arg( 'updated', 'true' );
			wp_redirect( $redirect );
			exit();

		}

		// Are we adding a reply?
		if ( isset( $_POST['submit-ticket-reply'] ) ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'add-ticket-reply-' . $ticket_id );

			if ( ! current_user_can( 'manage_network' ) )
				return;

			$plugin = incsub_support();
			$ticket = incsub_support_get_ticket_b( $ticket_id );
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$args = array();
			if ( isset( $_POST['cat_id'] ) ) {
				$category = incsub_support_get_ticket_category( absint( $_POST['cat_id'] ) );
				if ( $category )
					$args['cat_id'] = $category->cat_id;
			}

			if ( isset( $_POST['ticket-priority'] ) ) {
				$possible_values = array_keys( $plugin::$ticket_priority );
				if ( in_array( absint( $_POST['ticket-priority'] ), $possible_values ) )
					$args['ticket_priority'] = absint( $_POST['ticket-priority'] );
			}

			$message = isset( $_POST['message-text'] ) ? wpautop( stripslashes_deep( $_POST['message-text'] ) ) : '';
			if ( empty( $message ) )
				add_settings_error( 'support_system_submit_reply', 'empty-message', __( 'Message cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$args['message'] = $message;

			$responsibility = isset( $_POST['responsibility'] ) ? $_POST['responsibility'] : 'accept';
			if ( in_array( $responsibility, $plugin::$responsibilities ) ) {
				switch ( $responsibility ) {
					case 'punt': { $args['admin_id'] = 0; break; }
					case 'accept': { $args['admin_id'] = get_current_user_id(); break; }
					default: { break; }
				}
			}

			$status = isset( $_POST['closeticket'] ) ? 5 : 2;

			// TODO: ATTACHMENTS

			$args['title'] = 'Re: ' . stripslashes_deep( $ticket->title );

			if ( ! get_settings_errors( 'support_system_submit_reply' ) ) {
				if ( isset( $_POST['closeticket'] ) )
					incsub_support_close_ticket( $ticket->ticket_id );

				wp_die();
			}

		}
	}

	public function render_inner_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		$current_tab = $this->get_current_edit_ticket_tab();

		if ( 'edit' == $action && isset( $_GET['tid'] ) && 'details' === $current_tab ) {
			$ticket = incsub_support_get_ticket_b( absint( $_GET['tid'] ) );
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );


			// Last reply user
			$last_reply_user_name = '';
			$last_reply_user = get_userdata( $ticket->last_reply_user_id );
			if ( $last_reply_user )
				$last_reply_user_name = $last_reply_user->data->user_nicename;

			// Submitted from
			$submitted_blog_link = __( 'Unknown', INCSUB_SUPPORT_LANG_DOMAIN );
			if ( is_multisite() ) {
	            $blog_details = get_blog_details( $ticket->blog_id );
	            if ( ! empty( $blog_details ) ) {
	                $blog_address = get_blogaddress_by_id( $ticket->blog_id );
					$submitted_blog_link = '<a href="' . $blog_address . '">' . $blog_details->blogname . '</a>';
				}
	        }
	        else {
	            $user = get_userdata( $ticket->user_id );
	            if ( ! empty( $user ) )
	                $submitted_blog_link = '<a href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->user_nicename . '</a>';
	        }

	        // Super admins dropdown
	        $super_admins_dropdown = incsub_support_super_admins_dropdown( 
				array( 
					'show_empty' => __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN ) ,
					'echo' => false,
					'selected' => $ticket->get_staff_login()
				) 
			);

			// Priorities dropdown
			$priorities_dropdown = incsub_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->ticket_priority
				) 
			);

			$this->render_edit_ticket_tabs( $ticket );
			
			include( 'views/edit-ticket-details.php' );
		}
		elseif ( 'edit' == $action && isset( $_GET['tid'] ) && 'history' === $current_tab ) {
			$ticket = incsub_support_get_ticket_b( absint( $_GET['tid'] ) );
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$this->render_edit_ticket_tabs( $ticket );

			include_once( 'inc/class-table-tickets-history.php' );
			$ticket_history_table = new Incsub_Support_Tickets_History_Table();
			$ticket_history_table->set_ticket( absint( $_GET['tid'] ) );
			$ticket_history_table->prepare_items();
			$ticket_history_table->display();

			// Categories dropdown
			$categories_dropdown = incsub_support_ticket_categories_dropdown(
				array(
					'echo' => false,
					'name' => 'category',
					'id' => 'category',
					'selected' => $ticket->cat_id,
					'show_empty' => false
				)
			);

			$priorities_dropdown = incsub_support_priority_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->ticket_priority
				) 
			);

			$errors = get_settings_errors( 'support_system_submit_reply' );


			include( 'views/edit-ticket-history.php' );
		}
		else {
			include_once( 'inc/class-table-tickets.php' );

			$status = $this->get_status_filter();
			$category = $this->get_filter( 'category' );
			$priority = $this->get_filter( 'priority' );

		    $tickets_table = new Incsub_Support_Tickets_Table( $status );
		    $tickets_table->set_status( $status );
		    $tickets_table->set_priority( $priority );
		    $tickets_table->set_category( $category );
		    $tickets_table->prepare_items();

		    $all_tickets_count = incsub_support_get_tickets_count();
		    $archived_tickets_count = incsub_support_get_tickets_count( array( 'status' => 'archive' ) );
		    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

		    include( 'views/network-tickets.php' );
		}
	}

	private function render_edit_ticket_tabs( $ticket ) {
		$updated = isset( $_GET['updated'] );
		$tabs = $this->get_edit_ticket_tabs();
		$menu_slug = $this->slug;
		$edit_menu_url = add_query_arg( 
			array(
				'action' => 'edit',
				'tid' => absint( $_GET['tid'] )
			),
			$this->get_menu_url()
		);
		$menu_url = $this->get_menu_url();
		$current_tab = $this->get_current_edit_ticket_tab();
		include( 'views/edit-ticket-tabs.php' );
	}

	private function get_status_filter() {
		if ( ! isset( $_GET['status'] ) )
			return 'all';

		return $_GET['status'];
	}

	private function get_filter( $slug ) {
		if ( ! isset( $_GET[ $slug ] ) )
			return false;

		return $_GET[ $slug ];
	}

	private function get_edit_ticket_tabs() {
		return array(
			'details' => __( 'Ticket details', INCSUB_SUPPORT_LANG_DOMAIN ),
			'history' => __( 'Update ticket', INCSUB_SUPPORT_LANG_DOMAIN )
		);
	}

	private function get_current_edit_ticket_tab() {
		$tabs = $this->get_edit_ticket_tabs();
		if ( empty( $_GET['tab'] ) )
			return key( $tabs );

		if ( ! in_array( $_GET['tab'], array_keys( $tabs ) ) )
			return key( $tabs );

		return $_GET['tab'];
	}
}