<?php

function incsub_support_get_settings() {
	return wp_parse_args( get_site_option( 'incsub_support_settings' ), incsub_support_get_default_settings() );
}

function incsub_support_get_default_settings() {

	$plugin = incsub_support();
	$super_admins = $plugin::get_super_admins();
	
	return array(
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
		'incsub_support_main_super_admin' => key( $super_admins ) //First of the Super Admins
	);
}