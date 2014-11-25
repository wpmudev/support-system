<?php

class Incsub_Support_Settings {

	public $options_name = 'incsub_support_settings';

	public function __construct() {
		add_filter( 'incsub_support_menus', array( $this, 'filter_menus' ) );
	}

	public function get( $name ) {
		$settings = $this->get_all();
		if ( isset( $settings[ $name ] ) )
			return $settings[ $name ];

		return false;
	}

	public function get_all() {
		$settings = get_site_option( $this->options_name, array() );
		return wp_parse_args( $settings, $this->get_default_settings() );
	}

	public function update( $new_settings ) {
		$settings = update_site_option( $this->options_name, $new_settings );
	}

	public function set( $name, $value ) {
		$settings = $this->get_all();
		$settings[ $name ] = $value;
		$this->update( $settings );
	}

	public function get_default_settings() {
		$plugin = incsub_support();
		$super_admins = $plugin::get_super_admins();
		return apply_filters( 'support_system_default_settings', array(
			'incsub_support_menu_name' => __( 'Support', INCSUB_SUPPORT_LANG_DOMAIN ),
			'incsub_support_from_name' => get_bloginfo( 'blogname' ),
			'incsub_support_from_mail' => get_bloginfo( 'admin_email' ),
			'incsub_support_fetch_imap' => 'disabled',
			'incsub_support_imap_frequency' => '',
			'incsub_allow_only_pro_sites' => false,
			'incsub_pro_sites_level' => '',
			'incsub_allow_only_pro_sites_faq' => false,
			'incsub_pro_sites_faq_level' => '',
			'incsub_ticket_privacy' => 'all',
			'incsub_support_tickets_role' => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),
			'incsub_support_faqs_role' => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),
			'incsub_support_main_super_admin' => key( $super_admins ), //First of the Super Admins
			'incsub_support_support_page' => 0,
			'incsub_support_blog_id' => is_multisite() ? BLOG_ID_CURRENT_SITE : 1
		) );
	}

	public function filter_menus( $menus ) {
		if ( is_multisite() && ! is_network_admin() ) {
			$settings = $this->get_all();

			if ( isset( $menus['admin_faq_menu'] ) && ! incsub_support_current_user_can( 'efwefw' ) ) {
				unset( $menus['admin_faq_menu'] );
			}
		}

		return $menus;
	}

}