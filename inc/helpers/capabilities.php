<?php

function incsub_support_current_user_can( $cap = '' ) {
	$user_id = get_current_user_id();

	if ( ( is_multisite() && is_super_admin() ) || ( ! is_multisite() && current_user_can( 'manage_options' ) ) )
		return true;

	$settings = incsub_support_get_settings();

	if ( is_user_logged_in() ) {
		$user = get_userdata( $user_id );
		$user_role = isset( $user->roles[0] ) ? $user->roles[0] : '';
	}
	else {
		$user_role = 'support-guest';
	}

	if ( 'insert_ticket' === $cap || 'read_ticket' === $cap  ) {
		if ( in_array( $user_role, $settings['incsub_support_tickets_role'] ) )
			return true;

	}

	if ( 'update_reply' === $cap )
		return false;

	if ( 'insert_reply' === $cap && in_array( $user_role, $settings['incsub_support_tickets_role'] ) )
		return true;

	if ( 'insert_ticket_category' === $cap || 'update_ticket_category' === $cap || 'delete_ticket_category' === $cap )
		return false;

	if ( 'read_faq' === $cap && in_array( $user_role, $settings['incsub_support_faqs_role'] ) ) {
		return true;
	}

	if ( 'open_ticket' === $cap || 'close_ticket' === $cap || 'delete_ticket' === $cap || 'update_ticket' === $cap )
		return false;

	if ( 'manage_options' === $cap )
		return false;

	return false;

}

function incsub_support_get_capabilities() {
	return array(
		'insert_ticket',
		'delete_ticket',
		'update_ticket',
		'open_ticket',
		'close_ticket',
		'read_ticket',

		'insert_reply',
		'update_reply',
		'delete_reply',

		'insert_ticket_category',
		'update_ticket_category',
		'delete_ticket_category',

		'manage_options',

		'insert_faq',
		'delete_faq',
		'update_faq',
		'read_faq'
	);
}