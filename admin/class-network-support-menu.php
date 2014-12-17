<?php

class Incsub_Support_Network_Support_Menu extends Incsub_Support_Parent_Support_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		
		// Tickets table filters
		add_filter( 'support_system_tickets_table_query_args', array( $this, 'set_tickets_table_query_args' ) );
	}



	public function add_menu() {
		$unviewed_tickets = incsub_support_get_tickets_count( array( 'view_by_superadmin' => 0 ) );
		
		$menu_title = __( 'Support', INCSUB_SUPPORT_LANG_DOMAIN );
		if ( $unviewed_tickets ) {
			$warning_title = __( '%d unviewed tickets', INCSUB_SUPPORT_LANG_DOMAIN );
			$menu_title .= " <span class='update-plugins count-$unviewed_tickets' title='$warning_title'><span class='update-count'>" . number_format_i18n( $unviewed_tickets ) . "</span></span>";	
		}
		parent::add_menu_page(
			__( 'Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN ),
			$menu_title, 
			is_multisite() ? 'manage_network' : 'manage_options',
			'dashicons-sos'
		);

		if ( isset( $_GET['action'] ) && isset( $_GET['tid'] ) && 'edit' === $_GET['action'] )
			add_filter( 'support_system_admin_page_title', '__return_empty_string' );

	}

	public function render_inner_page() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		$current_tab = $this->get_current_edit_ticket_tab();

		if ( 'edit' == $action && isset( $_GET['tid'] ) && 'details' === $current_tab ) {
			$this->render_inner_page_edit_details();
		}
		elseif ( 'edit' == $action && isset( $_GET['tid'] ) && 'history' === $current_tab ) {
			$this->render_inner_page_history();
		}
		else {
			$this->render_inner_page_tickets_table();
		}
	}


	public function set_tickets_table_query_args( $args ) {
		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );
		$s = $this->get_filter( 's' );

		$args['s'] = $s;
		$args['priority'] = $priority;
		$args['category'] = $category;

		return $args;
	}


}