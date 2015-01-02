<?php

function incsub_support_current_user_can( $cap = '' ) {
	$user_id = get_current_user_id();

	return incsub_support_user_can( $user_id, $cap );

}

function incsub_support_user_can( $user_id, $cap = '' ) {

	$settings = incsub_support_get_settings();

	$user_can = false;
	if ( ( is_multisite() && is_super_admin( $user_id ) ) || ( ! is_multisite() && user_can( $user_id, 'manage_options' ) ) ) {
		$user_can = true;
	}
	else {
		$user = get_userdata( $user_id );
		if ( ! $user )
			$user_role = 'support-guest';
		else 
			$user_role = isset( $user->roles[0] ) ? $user->roles[0] : '';

		switch ( $cap ) {
			case 'insert_ticket':
			case 'read_ticket':
			case 'insert_reply': { 
				if ( in_array( $user_role, $settings['incsub_support_tickets_role'] ) )
					$user_can = true;
				break; 
			}
			
			case 'update_reply': { $user_can = false; break; }

			case 'insert_ticket_category': 
			case 'update_ticket_category': 
			case 'delete_ticket_category': { 
				$user_can = false;
				break;
			}

			case 'read_faq': { 
				if ( in_array( $user_role, $settings['incsub_support_faqs_role'] ) )
					$user_can = true;
				break; 
			}

			case 'open_ticket': 
			case 'close_ticket': 
			case 'delete_ticket': 
			case 'update_ticket': { 
				$user_can = false;
				break;
			}

			case 'manage_options': { $user_can = false; break;}

			default: { $user_can = false; break; }
		}
	}

	return apply_filters( 'support_system_user_can', $user_can, $user_id, $cap );

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