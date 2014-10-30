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
	foreach ( $_replies as $_reply ) {
		$replies[] = incsub_support_get_ticket_reply( $_reply );
	}

	return $replies;

}

function incsub_support_get_ticket_reply( $ticket_reply ) {
	$ticket_reply = Incsub_Support_Ticket_Reply::get_instance( $ticket_reply );
	return $ticket_reply;
}