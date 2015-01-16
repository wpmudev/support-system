<?php

class Incsub_Support_Parent_Support_Menu extends Incsub_Support_Admin_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		if ( $this->get_current_edit_ticket_tab() === 'history' || $action === 'add' )
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Tickets table filters
		add_filter( 'support_system_tickets_table_menu_url', array( $this, 'get_menu_url' ) );
	}

	public function enqueue_styles( $page_id ) {
		if ( $page_id === $this->page_id )
			wp_enqueue_style( 'support-menu-styles', INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/css/support-menu.css' );
	}

	public function enqueue_scripts( $page_id ) {
		if ( $page_id === $this->page_id ) {
			incsub_support_enqueue_main_script();
		}
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'incsub_support_tickets_per_page' == $option ) 
			return $value;

		return $status;
	}

	public function add_menu() {}


	public function on_load() {

		// Add screen options
		add_screen_option( 'per_page', array( 'label' => __( 'Tickets per page', INCSUB_SUPPORT_PLUGIN_URL ), 'default' => 20, 'option' => 'incsub_support_tickets_per_page' ) );

		// Check filtering
		if ( isset( $_POST['filter_action'] ) || ! empty( $_POST['s'] ) ) {

			$filters = array(
				'category' => false,
				'priority' => false,
				's' => false
			);

			$url = false;

			if ( ! empty( $_REQUEST['ticket-cat'] ) && $cat_id = absint( $_REQUEST['ticket-cat'] ) )
				$filters['category'] = $cat_id;


			if ( isset( $_REQUEST['ticket-priority'] ) && $_REQUEST['ticket-priority'] !== '' )
				$filters['priority'] = $_REQUEST['ticket-priority'];

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

		// Are we updating a ticket?
		if ( ! empty( $_POST['submit-ticket-details'] ) && 'details' === $this->get_current_edit_ticket_tab() ) {
			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'update-ticket-details-' . $ticket_id );

			$ticket = incsub_support_get_ticket( $ticket_id );
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

			if ( isset( $_POST['ticket-cat'] ) ) {
				$cat_id = absint( $_POST['ticket-cat'] );
				if ( incsub_support_get_ticket_category( $cat_id ) )
					$args['cat_id'] = $cat_id;
			}

			// Close ticket?
			if ( isset( $_POST['close-ticket'] ) && incsub_support_current_user_can( 'close_ticket', $ticket_id ) ) {
				incsub_support_close_ticket( $ticket_id );
			}
			elseif ( incsub_support_current_user_can( 'open_ticket', $ticket_id ) && $ticket->is_closed() ) {
				incsub_support_restore_ticket_previous_status( $ticket_id );
			}

			if ( incsub_support_current_user_can( 'update_ticket' ) ) {
				incsub_support_update_ticket( $ticket_id, $args );
			}
			

			$redirect = add_query_arg( 'updated', 'true' );
			wp_redirect( $redirect );
			exit();

		}

		// Are we adding a reply?
		if ( isset( $_POST['submit-ticket-reply'] ) ) {

			$ticket_id = absint( $_POST['ticket_id'] );
			check_admin_referer( 'add-ticket-reply-' . $ticket_id );

			$plugin = incsub_support();
			$ticket = incsub_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$reply_args = array();

			$message = isset( $_POST['message-text'] ) ? wpautop( stripslashes_deep( $_POST['message-text'] ) ) : '';
			if ( empty( $message ) )
				add_settings_error( 'support_system_submit_reply', 'empty-message', __( 'Message cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$reply_args['message'] = $message;

			$reply_args['poster_id'] = get_current_user_id();


			if ( ! get_settings_errors( 'support_system_submit_reply' ) ) {
				$ticket_args = array();
				if ( isset( $_POST['category'] ) ) {
					$category = incsub_support_get_ticket_category( absint( $_POST['category'] ) );
					if ( $category )
						$ticket_args['cat_id'] = $category->cat_id;
				}

				if ( isset( $_POST['ticket-priority'] ) ) {
					$possible_values = array_keys( $plugin::$ticket_priority );
					if ( in_array( absint( $_POST['ticket-priority'] ), $possible_values ) )
						$ticket_args['ticket_priority'] = absint( $_POST['ticket-priority'] );
				}


				$responsibility = isset( $_POST['responsibility'] ) ? $_POST['responsibility'] : 'accept';
				if ( in_array( $responsibility, $plugin::$responsibilities ) ) {
					switch ( $responsibility ) {
						case 'punt': { $ticket_args['admin_id'] = 0; break; }
						case 'accept': { $ticket_args['admin_id'] = get_current_user_id(); break; }
						default: { break; }
					}
				}

				$status = isset( $_POST['closeticket'] ) ? 5 : 2;

				if ( $ticket->admin_id && $ticket->user_id === get_current_user_id() ) {
					$status = 3;
				}
				if ( isset( $_POST['closeticket'] ) && incsub_support_current_user_can( 'close_ticket', $ticket->ticket_id ) )
					incsub_support_close_ticket( $ticket->ticket_id );
				elseif ( incsub_support_current_user_can( 'open_ticket', $ticket->ticket_id ) )
					incsub_support_ticket_transition_status( $ticket->ticket_id, $status );

				// Attachments
				if ( ! empty( $_FILES['support-attachment'] ) ) {
					$files_uploaded = incsub_support_upload_ticket_attachments( $_FILES['support-attachment'] );					

					if ( ! empty( $files_uploaded ) ) {
						$reply_args['attachments'] = wp_list_pluck( $files_uploaded, 'url' );
					}
				}

				// Order is important on this
				if ( incsub_support_current_user_can( 'update_ticket' ) )
					incsub_support_update_ticket( $ticket->ticket_id, $ticket_args );

				if ( incsub_support_current_user_can( 'insert_reply' ) )
					incsub_support_insert_ticket_reply( $ticket->ticket_id, $reply_args );

				// Redirecting to ticket history
				$link = add_query_arg( 'updated', 'true' );
				wp_redirect( $link );
				exit();

			}
		

		}

		// Are we creating a FAQ based on a response?
		if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'create-faq-from-ticket' && isset( $_REQUEST['tid'] ) && isset( $_REQUEST['rid'] ) ) {
			$ticket_id = absint( $_REQUEST['tid'] );
			$reply_id = absint( $_REQUEST['rid'] );

			check_admin_referer( 'create-faq-from-ticket-' . $ticket_id . '-' . $reply_id );

			$ticket = incsub_support_get_ticket( $ticket_id );
			if ( ! $ticket )
				return;

			$reply = incsub_support_get_ticket_reply( $reply_id );
			if ( ! $reply )
				return;

			
			$redirect_to = incsub_support()->admin->menus['network_faqs_menu']->get_menu_url();
			$redirect_to = add_query_arg(
				array(
					'action' => 'add',
					'tid' => $ticket_id,
					'rid' => $reply_id
				),
				$redirect_to
			);

			wp_redirect( $redirect_to );
			exit;
			
		}
	}

	protected function render_inner_page_edit_details() {
		$ticket = incsub_support_get_ticket( absint( $_GET['tid'] ) );
		if ( ! $ticket )
			wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

		if ( ! $ticket->view_by_super_admin && incsub_support_current_user_can( 'update_ticket' ) )
			incsub_support_update_ticket( $ticket->ticket_id, array( 'view_by_superadmin' => 1 ) );

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

        if ( incsub_support_current_user_can( 'update_ticket' ) ) {
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

			// Categories dropdown
			$categories_dropdown = incsub_support_ticket_categories_dropdown(
				array( 
					'show_empty' => false,
					'echo' => false,
					'selected' => $ticket->cat_id
				) 
			);
		}
		else {
			$super_admins_dropdown = ! $ticket->get_staff_login() ? __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN ) : $ticket->get_staff_login();
			$priorities_dropdown = incsub_support_get_ticket_priority_name( $ticket->ticket_priority );
			$ticket_category = incsub_support_get_ticket_category( $ticket->cat_id );
			$categories_dropdown = $ticket_category->cat_name;
		}

		$this->render_edit_ticket_tabs( $ticket );
		
		include( 'views/edit-ticket-details.php' );
	}

	protected function render_inner_page_history() {
		$ticket = incsub_support_get_ticket( absint( $_GET['tid'] ) );
		if ( ! $ticket )
			wp_die( __( 'The ticket does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

		if ( ! $ticket->view_by_super_admin && incsub_support_current_user_can( 'update_ticket' ) )
			incsub_support_update_ticket( $ticket->ticket_id, array( 'view_by_superadmin' => 1 ) );

		$this->render_edit_ticket_tabs( $ticket );

		include_once( 'inc/class-table-tickets-history.php' );
		$ticket_history_table = new Incsub_Support_Tickets_History_Table();
		$ticket_history_table->set_ticket( absint( $_GET['tid'] ) );
		$ticket_history_table->prepare_items();
		$ticket_history_table->display();

		if ( isset( $_POST['category'] ) )
			$ticket->cat_id = absint( $_POST['category'] );

		if ( isset( $_POST['ticket-priority'] ) )
			$ticket->ticket_priority = absint( $_POST['ticket-priority'] );

		if ( isset( $_POST['responsibility'] ) ) {
			$responsibility = $_POST['responsibility'];
		}
		else {
			if ( $ticket->admin_id == get_current_user_id() )
				$responsibility = 'keep';
			else
				$responsibility = 'accept';
		}

		if ( isset( $_POST['closeticket'] ) )
			$ticket->ticket_status = 5;


		if ( incsub_support_current_user_can( 'update_ticket' ) ) {
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
		}
		else {
			$priorities_dropdown = incsub_support_get_ticket_priority_name( $ticket->ticket_priority );
			$ticket_category = incsub_support_get_ticket_category( $ticket->cat_id );
			$categories_dropdown = $ticket_category->cat_name;
		}

		$errors = get_settings_errors( 'support_system_submit_reply' );

		if ( incsub_support_current_user_can( 'insert_reply' ) && ! $ticket->is_closed() )
			include( 'views/edit-ticket-history.php' );
	}

	protected function render_inner_page_tickets_table() {
		include_once( 'inc/class-table-tickets.php' );

		$status = $this->get_status_filter();
		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );

	    $tickets_table = new Incsub_Support_Tickets_Table( array( 'status' => $status ) );

	    $tickets_table->prepare_items();

	    $counts_args = array();
	    if ( false !== $priority )
	    	$counts_args['priority'] = absint( $priority );

	    if ( false !== $category ) 
	    	$counts_args['category'] = absint( $category );

	    /**
	     * Filters the arguments that will be passed to the function that counts the tickets
	     * in the admin page
	     * 
	     * @param Array $counts_args Count arguments
	     */
	    $counts_args = apply_filters( 'support_system_support_menu_counts_args', $counts_args );

	    $all_tickets_count = incsub_support_get_tickets_count( $counts_args );

	    $counts_args['status'] = 'archive';
	    $archived_tickets_count = incsub_support_get_tickets_count( $counts_args );
	    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

	    include( 'views/network-tickets.php' );
	}

	public function render_inner_page() {}
	


	protected function render_edit_ticket_tabs( $ticket ) {
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

	protected function get_status_filter() {
		if ( ! isset( $_GET['status'] ) )
			return 'all';

		return $_GET['status'];
	}

	protected function get_filter( $slug ) {
		if ( ! isset( $_REQUEST[ $slug ] ) )
			return false;

		return $_REQUEST[ $slug ];
	}

	protected function get_edit_ticket_tabs() {
		return array(
			'details' => __( 'Ticket details', INCSUB_SUPPORT_LANG_DOMAIN ),
			'history' => __( 'Update ticket', INCSUB_SUPPORT_LANG_DOMAIN )
		);
	}

	protected function get_current_edit_ticket_tab() {
		$tabs = $this->get_edit_ticket_tabs();
		if ( empty( $_GET['tab'] ) )
			return key( $tabs );

		if ( ! in_array( $_GET['tab'], array_keys( $tabs ) ) )
			return key( $tabs );

		return $_GET['tab'];
	}
}