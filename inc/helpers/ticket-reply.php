<?php

function incsub_support_get_ticket_replies( $ticket_id ) {
	global $wpdb, $current_site;

	$_replies = array();

	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return $_replies;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;
	$tickets_replies_table = incsub_support()->model->tickets_messages_table;

	$results = $wpdb->get_results( 
		$wpdb->prepare( 
			"SELECT * FROM $tickets_replies_table
			WHERE site_id = %d
			AND ticket_id = %d
			ORDER BY message_id ASC",
			$current_site_id,
			$ticket_id
		)
	);	

	if ( $results )
		$_replies = $results;

	$replies = array();
	$i = 0;
	foreach ( $_replies as $_reply ) {
		$reply = incsub_support_get_ticket_reply( $_reply );

		if ( $i === 0 )
			$reply->is_main_reply = true;

		$replies[] = $reply;
		$i++;
	}

	return $replies;

}

function incsub_support_get_ticket_reply( $ticket_reply ) {
	$ticket_reply = Incsub_Support_Ticket_Reply::get_instance( $ticket_reply );
	return $ticket_reply;
}

function incsub_support_insert_ticket_reply( $ticket_id, $args = array() ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$ticket = incsub_support_get_ticket_b( absint( $ticket_id ) );
	if ( ! $ticket )
		return false;

	$defaults = array(
		'site_id' => $current_site_id,
		'user_id' => 0,
		'admin_id' => 0,
		'subject' => 'Re: ' . stripslashes_deep( $ticket->title ),
		'message' => '',
		'message_date' => current_time( 'mysql', 1 ),
		'attachments' => array(),
		'send_emails' => true
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$plugin = incsub_support();
	$tickets_replies_table = $plugin->model->tickets_messages_table;

	$result = $wpdb->insert(
		$tickets_replies_table,
		array(
			'site_id' => $site_id,
			'ticket_id' => absint( $ticket_id ),
			'admin_id' => $admin_id,
			'user_id' => $user_id,
			'subject' => $subject,
			'message' => $message,
			'message_date' => $message_date,
			'attachments' => maybe_serialize( $attachments )
		),
		array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
	);

	if ( ! $result )
		return false;

	$reply_id = $wpdb->insert_id;

	if ( ! $reply_id )
		return false;

	if ( ! $send_emails )
		return true;

	$reply = incsub_support_get_ticket_reply( $reply_id );

	if ( empty( $ticket->admin_id ) ) {
		$super_admin = $plugin::get_main_admin_details();
		incsub_support_send_user_reply_mail_b( $ticket, $reply );
		incsub_support_send_admin_reply_mail_b( $super_admin, $ticket, $reply );

	}
	else {
		if ( get_current_user_id() == absint( $ticket->admin_id ) ) {
			// Response by assigned staff
			// Send to creator
			incsub_support_send_user_reply_mail_b( $ticket, $reply );
		}
		elseif ( get_current_user_id() == absint( $ticket->user_id ) ) {
			// Response by creator
			// Send to Staff
			$staff = get_userdata( $ticket->admin_id );
			incsub_support_send_admin_reply_mail_b( $staff, $ticket, $reply );
		}
		else {
			// Response by none of them
			// Send to Creator & Staff
			$staff = get_userdata( $ticket->admin_id );
			$creator = get_userdata( $ticket->user_id );

			incsub_support_send_user_reply_mail_b( $ticket, $reply );
			incsub_support_send_admin_reply_mail_b( $staff, $ticket, $reply );

		}
	}

	return true;
}