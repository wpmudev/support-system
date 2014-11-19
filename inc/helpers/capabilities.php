<?php

function incsub_support_current_user_can( $cap = '' ) {
	$user_id = get_current_user_id();

	$settings = incsub_support_get_settings();

	if ( is_super_admin() )
		return true;

	
	$user = get_userdata( $user_id );
	$user_role = isset( $user->roles[0] ) ? $user->roles[0] : '';

	if ( ! in_array( $cap, $settings['incsub_support_tickets_role'] ) )
		return false;

	if ( 'insert_ticket' === $cap || 'delete_ticket' === $cap || 'update_ticket' === $cap )
		return false;

	if ( 'update_reply' === $cap )
		return false;

	if ( 'insert_ticket_category' === $cap || 'update_ticket_category' === $cap || 'delete_ticket_category' === $cap )
		return false;

	return true;

}

function incsub_support_get_capabilities() {
	return array(
		'insert_ticket',
		'delete_ticket',
		'update_ticket',
		'read_ticket',

		'insert_reply',
		'update_reply',
		'delete_reply',

		'insert_ticket_category',
		'update_ticket_category',
		'delete_ticket_category',

		'manage_options'
	);
}