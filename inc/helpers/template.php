<?php

function incsub_support_the_ticket_form( $ticket_id, $args  ) {
	$ticket = incsub_support_get_ticket_b( $ticket_id );
	var_dump($ticket);

	$defaults = array(
		'form_id' => 'support-system-ticket-form',
		'action' => '',
		'edit_fields' => array( 'category', 'priority', 'responsibility', 'closeticket', 'attachments' ),
		'remove_fields' => array(),
		'errors' => array()
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );
	?>
	
	<?php
}