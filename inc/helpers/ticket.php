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
		'view_by_superadmin' => null,
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
		$where[] = "t.user_id IN (" . implode( ',', $user_in ) . ")";
	}

	if ( $view_by_superadmin !== null )
		$where[] = $wpdb->prepare( "t.view_by_superadmin = %d", $view_by_superadmin );

	$tickets_table = incsub_support()->model->tickets_table;

	$order = strtoupper( $order );
	$order = "ORDER BY $orderby $order";

	$limit = '';
	if ( $per_page > -1 )
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

	$where = "WHERE " . implode( ' AND ', $where );

	if ( $count ) {
		$query = "SELECT COUNT(ticket_id) FROM $tickets_table t $where";

		$key = md5( $query );
		$cache_key = "incsub_support_get_tickets_count:$key";
		$results = wp_cache_get( $cache_key, 'support_system_tickets' );

		if ( $results === false ){
			$results = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $results, 'support_system_tickets' );
		}

		return $results;
	}
	else {
		$query = "SELECT * FROM $tickets_table t $where $order $limit";

		$key = md5( $query );
		$cache_key = "incsub_support_get_tickets:$key";
		$results = wp_cache_get( $cache_key, 'support_system_tickets' );

		if ( $results === false ) {
			$results = $wpdb->get_results( $query );
			if ( empty( $results ) )
				return array();
			wp_cache_set( $cache_key, $results, 'support_system_tickets' );
		}

		$tickets = array();
		foreach ( $results as $result ) {
			$tickets[] = incsub_support_get_ticket_b( $result );
		}

		return $tickets;
	}
	
}

function incsub_support_get_ticket_b( $ticket ) {
	$ticket = Incsub_Support_Ticket::get_instance( $ticket );
	return $ticket;
}

function incsub_support_get_tickets_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;

	$count = incsub_support_get_tickets_b( $args );

	return $count;
}

function incsub_support_delete_ticket( $ticket_id ) {
	global $wpdb;


	$wpdb->update(
		$this->tickets_table,
		array( 'ticket_status' => 5 ),
		array( 'ticket_id' => $ticket_id ),
		array( '%d' ),
		array( '%d' )
	);
	wp_die();
}

function incsub_support_close_ticket( $ticket_id ) {
	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;

	// Is already closed?
	if ( 5 == $ticket->ticket_status )
		return true;

	$result = incsub_support_ticket_transition_status( $ticket_id, 5 );

	if ( $result )
		incsub_support_send_user_closed_mail( $ticket_id );

	return $result;
}

function incsub_support_open_ticket( $ticket_id ) {
	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;

	// Is already opened?
	if ( 5 != $ticket->ticket_status )
		return true;

	$result = incsub_support_ticket_transition_status( $ticket_id, 0 );

	return $result;
}

function incsub_support_ticket_transition_status( $ticket_id, $status ) {
	$plugin = incsub_support();
	$all_status = array_keys( $plugin::$ticket_status );

	if ( ! in_array( $status, $all_status ) )
		return false;

	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;
	
	$current_status = $ticket->ticket_status;
	if ( $current_status == $status )
		return false;
	
	incsub_support_update_ticket( $ticket_id, array( 'ticket_status' => $status ) );

	return true;
}

function incsub_support_delete_ticket_b( $ticket_id ) {
	global $wpdb;

	$ticket = incsub_support_get_ticket_b( $ticket_id );

	if ( ! $ticket )
		return false;

	if ( ! $ticket->is_closed() )
		return false;

	$plugin = incsub_support();
	$tickets_table = $plugin->model->tickets_table;
	$tickets_messages_table = $plugin->model->tickets_messages_table;

	$wpdb->query( 
		$wpdb->prepare( 
			"DELETE FROM $tickets_table
			 WHERE ticket_id = %d",
		     $ticket_id
	     )
	);

	$wpdb->query( 
		$wpdb->prepare( 
			"DELETE FROM $tickets_messages_table
			 WHERE ticket_id = %d",
		     $ticket_id
	     )
	);

	return true;
}

function incsub_support_update_ticket( $ticket_id, $args ) {
	global $wpdb;

	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;

	$fields = array( 'site_id' => '%d', 'blog_id' => '%d', 'cat_id' => '%d', 'user_id' => '%d', 'admin_id' => '%d', 'last_reply_id' => '%d', 
		'ticket_type' => '%d', 'ticket_priority' => '%d', 'title' => '%s', 'view_by_superadmin' => '%d', 'ticket_status' => '%d' );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	$tickets_table = incsub_support()->model->tickets_table;

	$wpdb->update(
		$tickets_table,
		$update,
		array( 'ticket_id' => $ticket_id ),
		$update_wildcards,
		array( '%d' )
	);

}


