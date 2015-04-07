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

				/**
				 * Filters the Support System Menus
				 * 
				 * @param Array $menus Support Sytem Menus array
				 	array(
				 		[menu_key] => array(
							[class] => 'PHP Class name',
							[slug] => 'WordPress Admin Menu Slug'
				 		),
				 		...
				 	)
				 */
				$menus = apply_filters( 'incsub_support_menus', array(
					'network_support_menu' => array(
						'class' => 'Incsub_Support_Network_Support_Menu',
						'slug' => 'ticket-manager'
					),
					'network_ticket_categories_menu' => array(
						'class' => 'Incsub_Support_Network_Ticket_Categories',
						'slug' => 'ticket-categories'
					),
					'network_faqs_menu' => array(
						'class' => 'Incsub_Support_Network_FAQ_Menu',
						'slug' => 'support-faq-manager'
					),
					'network_faq_categories_menu' => array(
						'class' => 'Incsub_Support_Network_FAQ_Categories',
						'slug' => 'faq-categories'
					),
					'network_settings_menu' => array(
						'class' => 'Incsub_Support_Network_Settings_Menu',
						'slug' => 'mu-support-settings'
					),
					'network_welcome' => array(
						'class' => 'Incsub_Support_Welcome_Menu',
						'slug' => 'mu-support-welcome'
					)
				) );

				$network = true;
			}
			elseif ( ! is_network_admin() && is_admin() ) {
				/**
				 * Filters the Support System Menus
				 * 
				 * @param Array $menus Support Sytem Menus array
				 	array(
				 		[menu_key] => array(
							[class] => 'PHP Class name',
							[slug] => 'WordPress Admin Menu Slug'
				 		),
				 		...
				 	)
				 */
				$menus = apply_filters( 'incsub_support_menus', array(
					'admin_support_menu' => array(
						'class' => 'Incsub_Support_Admin_Support_Menu',
						'slug' => 'ticket-manager'
					),
					'admin_faq_menu' => array(
						'class' => 'Incsub_Support_Admin_FAQ_Menu',
						'slug' => 'support-faq'
					)
				) );

			}
		}
		elseif ( ! is_multisite() && is_admin() ) {
			/**
			 * Filters the Support System Menus
			 * 
			 * @param Array $menus Support Sytem Menus array
			 	array(
			 		[menu_key] => array(
						[class] => 'PHP Class name',
						[slug] => 'WordPress Admin Menu Slug'
			 		),
			 		...
			 	)
			 */
			$menus = apply_filters( 'incsub_support_menus', array(
				'admin_support_menu' => array(
					'class' => 'Incsub_Support_Admin_Support_Menu',
					'slug' => 'ticket-manager'
				),
				'network_ticket_categories_menu' => array(
					'class' => 'Incsub_Support_Network_Ticket_Categories',
					'slug' => 'ticket-categories'
				),
				'network_faqs_menu' => array(
					'class' => 'Incsub_Support_Network_FAQ_Menu',
					'slug' => 'support-faq-manager'
				),
				'network_faq_categories_menu' => array(
					'class' => 'Incsub_Support_Network_FAQ_Categories',
					'slug' => 'faq-categories'
				),
				'network_settings_menu' => array(
					'class' => 'Incsub_Support_Network_Settings_Menu',
					'slug' => 'mu-support-settings'
				),
				'network_welcome' => array(
					'class' => 'Incsub_Support_Welcome_Menu',
					'slug' => 'mu-support-welcome'
				),
				'admin_faq_menu' => array(
					'class' => 'Incsub_Support_Admin_FAQ_Menu',
					'slug' => 'support-faq'
				)
			) );
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

