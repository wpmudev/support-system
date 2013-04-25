<?php

/**
 * Functions that renders every mail involved in the system
 */

function incsub_support_get_support_fetch_imap_message() {
	if ( get_site_option( 'incsub_support_fetch_imap', 'disabled' ) == 'enabled' )
		$support_fetch_imap = __( "***  DO NOT WRITE BELLOW THIS LINE  ***", INCSUB_SUPPORT_LANG_DOMAIN );
	else
		$support_fetch_imap = __("***  DO NOT REPLY TO THIS EMAIL  ***", INCSUB_SUPPORT_LANG_DOMAIN );

	return $support_fetch_imap;
}

function incsub_support_get_support_process_reply_mail_content( $args ) {
	return sprintf( __( "
%s

Subject: %s
Status: %s
Priority: %s

Visit:

	%s

to reply to view the new ticket.

==============================================================
	Begin Ticket Message
==============================================================

%s said:

%s

==============================================================
      End Ticket Message
==============================================================

Ticket URL:

%s", 
			INCSUB_SUPPORT_LANG_DOMAIN ),
		$args['support_fetch_imap'],
		$args['title'],
		$args['ticket_status'],
		$args['ticket_priority'],
		$args['visit_link'],
		$args['user_nicename'],
		strip_tags( html_entity_decode( $args['ticket_message'] ) ),
		$args['ticket_url']
	);
}


function incsub_support_get_ticketadmin_mail_content( $args ) {
	return sprintf( __( "

***  DO NOT REPLY TO THIS EMAIL  ***

Subject: %s
Status: %s
Priority: %s

Please log into your site and visit the support page to reply to this ticket, if needed.

Visit:

	%s

to reply to this ticket, if needed.

==============================================================
     Begin Ticket Message
==============================================================

%s

==============================================================
      End Ticket Message
==============================================================

Thanks,
%s,
%s

			", INCSUB_SUPPORT_LANG_DOMAIN ),
		$args['title'],
		$args['ticket_status'],
		$args['ticket_priority'],
		$args['visit_link'],
		strip_tags( html_entity_decode( $args['ticket_message'] ) ),
		$args['user_nicename'],
		$args['site_name']
	);
}


function incsub_get_support_tickets_mail_content( $args ) {

	return sprintf( __("

%s

Subject: %s
Status: %s
Priority: %s

Visit:

	%s

to respond to this ticket update.


==============================================================
     Begin Ticket Message
==============================================================

%s said:


%s

==============================================================
      End Ticket Message
==============================================================


Ticket URL:
	%s
			", INCSUB_SUPPORT_LANG_DOMAIN ),
		$args['support_fetch_imap'],
		$args['title'],
		$args['ticket_status'],
		$args['ticket_priority'],
		$args['visit_link'],
		$args['user_nicename'],
		strip_tags( html_entity_decode( $args['ticket_message'] ) ),
		$args['ticket_url']
	);
} 

function incsub_get_closed_ticket_mail_content( $args ) {

	return sprintf( __("

%s

Subject: %s
Priority: %s

The ticket has been closed.

Ticket URL:
	%s
			", INCSUB_SUPPORT_LANG_DOMAIN ),
		$args['support_fetch_imap'],
		$args['title'],
		$args['ticket_priority'],
		$args['ticket_url']
	);
} 