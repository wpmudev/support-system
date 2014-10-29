<?php

/**
 * Handles the Admin side of the plugin
 */
class Incsub_Support_Admin {

	public function __construct() {
		$this->includes();
		$this->add_menus();
	}

	/**
	 * Include needed files
	 */
	private function includes() {
		require_once( 'class-network-support-menu.php' );
	}


	/**
	 * Create the menu objects
	 */
	private function add_menus() {
		if ( is_multisite() ) {
			if ( is_network_admin() ) {
				new Incsub_Support_Network_Menu( 'ticket-manager-b', true );
			}
			else {

			}
		}
	}
}

