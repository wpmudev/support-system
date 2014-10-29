<?php

function incsub_support_get_ticket_status_name( $status_id ) {
	return MU_Support_System::$ticket_status[ $status_id ];
}

function incsub_support_get_ticket_priority_name( $priority_id ) {
	return MU_Support_System::$ticket_priority[ $priority_id ];
}

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

function incsub_support_get_tickets_b( $args = array() ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$defaults = array(
		'per_page' => get_option( 'posts_per_page' ),
		'page' => 1,
		'status' => 'all',
		'blog_id' => false,
		'user_in' => false,
		'category' => false,
		'priority' => false,
		'site_id' => $current_site_id,
		'count' => false,
		'orderby' => 'ticket_updated',
		'order' => 'desc'
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	$where = array();
	if ( 'archive' == $status )
		$where[] = "t.ticket_status = 5";
	elseif ( 'all' == $status )
		$where[] = '1 = 1';
	else
		$where[] = "t.ticket_status != 5";

	if ( $category )
		$where[] = $wpdb->prepare( "t.cat_id = %d", $category );
	
	if ( $priority )
		$where[] = $wpdb->prepare( "t.cat_id = %d", $priority );

	if ( absint( $blog_id ) > 0 )
		$where[] = $wpdb->prepare( "t.blog_id = %d", $blog_id );

	if ( ! empty( $user_in ) && is_array( $user_in ) ) {
		$user_in = array_map( 'absint', $user_in );
		$where[] = "AND t.user_id IN (" . implode( ',', $user_in ) . ")";
	}

	$tickets_table = incsub_support()->model->tickets_table;

	$order = strtoupper( $order );
	$order = "ORDER BY $orderby $order";

	if ( $per_page > -1 )
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

	$where = "WHERE " . implode( ' AND ', $where );

	if ( $count ) {
		$results = $wpdb->get_var( "SELECT COUNT(ticket_id) FROM $tickets_table t $where" );
		return $results;
	}
	else {
		$results = $wpdb->get_results( "SELECT * FROM $tickets_table t $where $order $limit" );
		if ( empty( $results ) )
			return array();

		$tickets = array();
		foreach ( $results as $result ) {
			$tickets[] = incsub_support_get_ticket_b( $result );
		}

		return $tickets;
	}
	
}

function incsub_support_get_tickets_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;
	return incsub_support_get_tickets_b( $args );
}


function incsub_support_get_ticket_b( $ticket ) {
	$ticket = Incsub_Support_Ticket::get_instance( $ticket );
	return $ticket;
}