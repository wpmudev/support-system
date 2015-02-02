<?php

function incsub_support_get_ticket_replies( $ticket_id ) {
	global $wpdb, $current_site;

	$_replies = array();

	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return $_replies;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;
	$tickets_replies_table = incsub_support()->model->tickets_messages_table;

	$query = $wpdb->prepare(
		"SELECT * FROM $tickets_replies_table
		WHERE ticket_id = %d
		ORDER BY message_id ASC",
		$ticket_id
	);
	
	$results = wp_cache_get( 'support-ticket-' . $ticket_id, 'support_system_ticket_replies' );

	if ( $results === false ){
		$results = $wpdb->get_results( $query );
		wp_cache_set( 'support-ticket-' . $ticket_id, $results, 'support_system_ticket_replies' );
	}

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

	$replies = apply_filters( 'support_system_get_ticket_replies', $replies, $ticket_id );
	return $replies;

}

function incsub_support_get_ticket_reply( $ticket_reply ) {
	$ticket_reply = Incsub_Support_Ticket_Reply::get_instance( $ticket_reply );
	$ticket_reply = apply_filters( 'support_system_get_ticket_reply', $ticket_reply );
	return $ticket_reply;
}

/**
 * Insert a new reply for a ticket
 * 
 * @param  int $ticket_id
 * @param  array  $args
 * @return int|boolean
 */
function incsub_support_insert_ticket_reply( $ticket_id, $args = array() ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$ticket = incsub_support_get_ticket( absint( $ticket_id ) );

	if ( ! $ticket )
		return false;

	wp_cache_delete( 'support-ticket-' . $ticket_id, 'support_system_ticket_replies' );
	wp_cache_delete( $ticket_id, 'support_system_tickets' );

	$defaults = array(
		'site_id' => $current_site_id,
		'poster_id' => 0,
		'subject' => 'Re: ' . wp_unslash( $ticket->title ),
		'message' => '',
		'message_date' => current_time( 'mysql', 1 ),
		'attachments' => array(),
		'send_emails' => true
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$plugin = incsub_support();
	$tickets_replies_table = $plugin->model->tickets_messages_table;

	$message = wp_kses_post( wp_unslash( $message ) );
	
	$result = $wpdb->insert(
		$tickets_replies_table,
		array(
			'site_id' => $site_id,
			'ticket_id' => absint( $ticket_id ),
			'admin_id' => is_super_admin( $poster_id ) ? absint( $poster_id ) : 0,
			'user_id' => is_super_admin( $poster_id ) ? 0 : absint( $poster_id ),
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

	$reply = incsub_support_get_ticket_reply( $reply_id );
	incsub_support_recount_ticket_replies( $reply->ticket_id );

	$users_tagged = incsub_support_get_ticket_meta( $ticket_id, 'tagged_users', array() );
	if ( ! in_array( $poster_id, $users_tagged ) ) {
		$users_tagged[] = $poster_id;
		incsub_support_update_ticket_meta( $ticket_id, 'tagged_users', $users_tagged );
	}

	do_action( 'support_system_insert_ticket_reply', $reply_id, $send_emails );

	return $reply_id;

}


function incsub_support_delete_ticket_reply( $reply_id ) {
	global $wpdb, $current_site;

	$ticket_reply = incsub_support_get_ticket_reply( $reply_id );
	if ( ! $ticket_reply )
		return false;

	$ticket = incsub_support_get_ticket( $ticket_reply->ticket_id );
	if ( ! $ticket )
		return false;

	wp_cache_delete( 'support-ticket-' . $ticket->ticket_id, 'support_system_ticket_replies' );
	wp_cache_delete( $ticket->ticket_id, 'support_system_tickets' );

	$replies = $ticket->get_replies();

	$main_reply = wp_list_filter( $replies, array( 'is_main_reply' => true ) );
	$main_reply = $main_reply[0];
	if ( $main_reply->message_id == $reply_id ) {
		// Do not allow to delete the main reply
		return false;
	}

	$tickets_replies_table = incsub_support()->model->tickets_messages_table;

	$wpdb->query( $wpdb->prepare( "DELETE FROM $tickets_replies_table WHERE message_id = %d", $reply_id ) );
	incsub_support_recount_ticket_replies( $ticket_reply->ticket_id );

	$old_ticket_reply = $ticket_reply;
	do_action( 'support_system_delete_ticket_reply', $reply_id, $old_ticket_reply );

	return true;
}