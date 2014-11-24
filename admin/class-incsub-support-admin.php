<?php

/**
 * Handles the Admin side of the plugin
 */
class Incsub_Support_Admin {

	public $menus = array();

	public function __construct() {
		$this->includes();
		$this->add_menus();
	}

	/**
	 * Include needed files
	 */
	private function includes() {
		require_once( 'class-abstract-menu.php' );

		// Network
		require_once( 'class-parent-support-menu.php' );
		require_once( 'class-network-support-menu.php' );
		require_once( 'class-network-ticket-categories-menu.php' );
		require_once( 'class-network-faqs-menu.php' );
		require_once( 'class-network-faq-categories-menu.php' );

		// Admin
		require_once( 'class-admin-support-menu.php' );
	}


	/**
	 * Create the menu objects
	 */
	private function add_menus() {
		if ( is_multisite() ) {
			if ( is_network_admin() && current_user_can( 'manage_network' ) ) {
				$this->menus['network_support_menu'] = new Incsub_Support_Network_Support_Menu( 'ticket-manager-b', true );
				$this->menus['network_ticket_categories_menu'] = new Incsub_Support_Network_Ticket_Categories( 'ticket-categories-b', true );
				$this->menus['network_faqs_menu'] = new Incsub_Support_Network_FAQ_Menu( 'support-faq-manager-b', true );
				$this->menus['network_faq_categories'] = new Incsub_Support_Network_FAQ_Categories( 'faq-categories-b', true );
			}
			elseif ( ! is_network_admin() && is_admin() ) {

				$user = get_userdata( get_current_user_id() );
				$user_role = isset( $user->roles[0] ) ? $user->roles[0] : ( is_super_admin() ? 'administrator' : '' );

				$tickets_role = incsub_support_get_setting( 'incsub_support_tickets_role' );
				var_dump(incsub_support_get_settings());
				$admin_ticket_menu_allowed = false;

				// Tickets allowed?
				foreach ( $tickets_role as $ticket_role ) {
					if ( $user_role == $ticket_role ) {
						$admin_ticket_menu_allowed = true;
						break;
					}
				}

				if ( (boolean)$settings['incsub_allow_only_pro_sites'] && $admin_ticket_menu_allowed )
					$admin_ticket_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_level'] ) );

				// FAQs allowed?
				$admin_faq_menu_allowed = false;
				foreach ( $settings['incsub_support_faqs_role'] as $faq_role ) {
					if ( $user_role == $faq_role ) {
						$admin_faq_menu_allowed = true;
						break;
					}
				}

				if ( $settings['incsub_allow_only_pro_sites_faq'] && $admin_faq_menu_allowed )
					$admin_faq_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_faq_level'] ) );

				$this->menus['admin_support_menu'] = new Incsub_Support_Admin_Support_Menu( 'ticket-manager-b' );
				/**
				// If is not a Pro site we will not create the menu
				if ( $admin_ticket_menu_allowed ) {
					$admin_single_ticket_menu = new MU_Support_Admin_Single_Ticket_Menu();
					$admin_new_ticket_menu = new MU_Support_Admin_New_Ticket_Menu();
					$admin_main_menu = new MU_Support_Admin_Main_Menu();
				}
				
				if ( ! $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
					$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( true );
				elseif ( $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
					$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( false );
				**/
			}
		}
	}
}

