<?php

function incsub_support_get_ticket( $ticket ) {

	if ( is_a( $ticket, 'Incsub_Support_Ticket' ) ) {
		$_ticket = $ticket;
	} 
	elseif ( is_object( $ticket ) ) {
		$_ticket = new Incsub_Support_Ticket( $ticket );
	} else {
		$_ticket = Incsub_Support_Ticket::get_instance( $ticket );
	}

	if ( ! $_ticket )
		return null;

	return $_ticket;
}

function incsub_support_get_tickets( $ticket_ids ) {
	$tickets = array();
	foreach ( $ticket_ids as $ticket_id ) {
		$tickets[] = incsub_support_get_ticket( absint( $ticket_id ) );
	}

	return $tickets;
}

function incsub_support_get_ticket_status_name( $status_id ) {
	return MU_Support_System::$ticket_status[ $status_id ];
}

function incsub_support_get_ticket_priority_name( $priority_id ) {
	return MU_Support_System::$ticket_priority[ $priority_id ];
}