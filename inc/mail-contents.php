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
	$content = __( '
SUPPORT_FETCH_IMAP

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Visit:

	SUPPORT_LINK

to reply to view the new ticket.

==============================================================
	Begin Ticket Message
==============================================================

SUPPORT_USER_NAME said:

SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================

Ticket URL:

SUPPORT_TICKET_URL', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_TICKET_URL', $args['ticket_url'], $content );

	return $content;
}


function incsub_support_get_ticketadmin_mail_content( $args ) {
	$content = __( '

***  DO NOT REPLY TO THIS EMAIL  ***

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Please log into your site and visit the support page to reply to this ticket, if needed.

Visit:

	SUPPORT_LINK

to reply to this ticket, if needed.

==============================================================
     Begin Ticket Message
==============================================================

SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================

Thanks,
SUPPORT_USER_NAME,
SUPPORT_SITE_NAME', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_SITE_NAME', $args['site_name'], $content );

	return $content;

}


function incsub_get_support_tickets_mail_content( $args ) {

	$content = __('

SUPPORT_FETCH_IMAP

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Visit:

	SUPPORT_LINK

to respond to this ticket update.


==============================================================
     Begin Ticket Message
==============================================================

SUPPORT_USER_NAME said:


SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================


Ticket URL:
	SUPPORT_TICKET_URL', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_TICKET_URL', $args['ticket_url'], $content );

	return $content;
} 

function incsub_get_closed_ticket_mail_content( $args ) {

	$content = __('

SUPPORT_FETCH_IMAP

Subject: SUPPORT_SUBJECT
Priority: SUPPORT_PRIORITY

The ticket has been closed.

Ticket URL:
	SUPPORT_TICKET_URL', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_TICKET_URL', $args['ticket_url'], $content );

	return $content;
} 