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
		require_once( 'class-network-settings-menu.php' );
		require_once( 'class-network-welcome-menu.php' );

		// Admin
		require_once( 'class-admin-support-menu.php' );
		require_once( 'class-admin-faqs-menu.php' );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			require_once( 'inc/ajax.php' );
		}
	}


	/**
	 * Create the menu objects
	 */
	private function add_menus() {
		$menus = array();
		$network = false;

		if ( is_multisite() ) {
			if ( is_network_admin() && incsub_support_current_user_can( 'manage_options' ) ) {
				$menus = apply_filters( 'incsub_support_menus', array(
					'network_support_menu' => array(
						'class' => 'Incsub_Support_Network_Support_Menu',
						'slug' => 'ticket-manager-b'
					),
					'network_ticket_categories_menu' => array(
						'class' => 'Incsub_Support_Network_Ticket_Categories',
						'slug' => 'ticket-categories-b'
					),
					'network_faqs_menu' => array(
						'class' => 'Incsub_Support_Network_FAQ_Menu',
						'slug' => 'support-faq-manager-b'
					),
					'network_faq_categories_menu' => array(
						'class' => 'Incsub_Support_Network_FAQ_Categories',
						'slug' => 'faq-categories-b'
					),
					'network_settings_menu' => array(
						'class' => 'Incsub_Support_Network_Settings_Menu',
						'slug' => 'mu-support-settings-b'
					),
					'network_welcome' => array(
						'class' => 'Incsub_Support_Welcome_Menu',
						'slug' => 'mu-support-welcome'
					)
				) );

				$network = true;
			}
			elseif ( ! is_network_admin() && is_admin() ) {

				$menus = apply_filters( 'incsub_support_menus', array(
					'admin_support_menu' => array(
						'class' => 'Incsub_Support_Admin_Support_Menu',
						'slug' => 'ticket-manager-b'
					),
					'admin_faq_menu' => array(
						'class' => 'Incsub_Support_Admin_FAQ_Menu',
						'slug' => 'support-faq-b'
					)
				) );

				$user = get_userdata( get_current_user_id() );
				$user_role = isset( $user->roles[0] ) ? $user->roles[0] : ( is_super_admin() ? 'administrator' : '' );

				$tickets_role = incsub_support_get_setting( 'incsub_support_tickets_role' );
				$admin_ticket_menu_allowed = false;

				// Tickets allowed?
				foreach ( $tickets_role as $ticket_role ) {
					if ( $user_role == $ticket_role ) {
						$admin_ticket_menu_allowed = true;
						break;
					}
				}

				$settings = incsub_support_get_settings();
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
		else {

		}

		foreach ( $menus as $key => $menu ) {
			if ( class_exists( $menu['class'] ) ) {
				$args = array( 'slug' => $menu['slug'], 'is_network' => $network );
	            $r = new ReflectionClass( $menu['class'] );
	            $this->menus[ $key ] = $r->newInstanceArgs( $args );
			} 
		}
	}
}

