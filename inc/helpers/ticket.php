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

function incsub_support_get_tickets( $args = array() ) {
	$defaults = array(
		'per_page' 		=> 30,
		'page'  		=> 1,
		'category_in' 	=> '',
		'status' 		=> 'all',
		'blog_id_in' 	=> '',
		'user_in' 		=> ''
	);

	$args = wp_parse_args( $args, $defaults );

	$model = incsub_support_get_ticket_model();
	$results = $model->get_tickets_beta( $args );

	$tickets = array();
	foreach ( $results as $result ) {
		$tickets[] = incsub_support_get_ticket( $result );
	}

	return $tickets;
}

function incsub_support_get_ticket_status_name( $status_id ) {
	return MU_Support_System::$ticket_status[ $status_id ];
}

function incsub_support_get_ticket_priority_name( $priority_id ) {
	return MU_Support_System::$ticket_priority[ $priority_id ];
}

function incsub_support_get_tickets_count() {
	$model = incsub_support_get_ticket_model();
	$settings = MU_Support_System::$settings;

	$args = array();
	if ( ! is_network_admin() && 'requestor' == $settings['incsub_ticket_privacy'] )
		$args['user_in'] = array( get_current_user_id() );

	$results = $model->get_tickets_count( $args );

	$counts = array();
	$total = 0;

	$counts[ 'closed' ] = 0;
	$counts[ 'opened' ] = 0;
	foreach ( $results as $result ) {
		$status = absint( $result['ticket_status'] ) == 5 ? 'closed' : 'opened';
		$counts[ $status ] += absint( $result['tickets_num'] );
		$total += absint( $result['tickets_num'] );
	}
	$counts['all'] = $total;

	if ( ! isset( $counts['opened'] ) )
		$counts['opened'] = 0;

	if ( ! isset( $counts['closed'] ) )
		$counts['closed'] = 0;

	return $counts;

}


function incsub_support_get_filtered_tickets_count( $status, $category, $args = array() ) {
	$model = incsub_support_get_ticket_model();

	return $model->get_filtered_tickets_count( $status, $category, $args );
}