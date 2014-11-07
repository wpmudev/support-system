<?php

/**
 * Functions that renders every mail involved in the system
 */

function incsub_support_get_email_headers() {
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'From: ' . MU_Support_System::$settings['incsub_support_from_name'] . ' <' . MU_Support_System::$settings['incsub_support_from_mail'] . '>';

	return $headers;
}

/**
 * Send a mail to the user that opened a new ticket
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function incsub_support_send_user_new_ticket_mail( $user, $ticket_id, $ticket ) {

	$headers = incsub_support_get_email_headers();

	$visit_link = add_query_arg(
		'tid',
		$ticket_id,
		MU_Support_System::$admin_single_ticket_menu->get_permalink()
	);

	$args = array(
		'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket['subject'],
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> MU_Support_System::$ticket_status[0],
		'ticket_priority'		=> MU_Support_System::$ticket_priority[ $ticket['ticket_priority'] ],
		'site_name'				=> get_bloginfo( 'name' )
	);
	$mail_content = incsub_support_user_get_new_ticket_mail_content( $args );

	wp_mail( $user->user_email, __( "Ticket submitted: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket['subject'], $mail_content, $headers );
}
function incsub_support_send_user_new_ticket_mail_b( $ticket ) {

	$ticket_creator = get_userdata( $ticket->user_id );
	if ( ! $ticket_creator )
		return;

	$headers = incsub_support_get_email_headers();

	$visit_link = get_admin_url( $ticket->blog_id, 'admin.php' );
	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'page' => 'ticket-manager-b',
			'action' => 'edit',
			'tab' => 'history'
		),
		$visit_link
	);

	$plugin = incsub_support();
	$args = array(
		'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->title,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> incsub_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> incsub_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'site_name'				=> get_bloginfo( 'name' )
	);
	$mail_content = incsub_support_user_get_new_ticket_mail_content( $args );

	wp_mail( $ticket_creator->user_email, __( "Ticket submitted: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket->title, $mail_content, $headers );
}

/**
 * Send a mail to the main Administrator when a new
 * ticket is submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function incsub_support_send_admin_new_ticket_mail( $user, $ticket_id, $ticket ) {
	
	$headers = incsub_support_get_email_headers();

	// Variables for the message
	if ( ! is_object( MU_Support_System::$network_single_ticket_menu ) )
		$network_admin = new MU_Support_Network_Single_Ticket_Menu( true );
	else
		$network_admin = MU_Support_System::$network_single_ticket_menu;


	$visit_link = add_query_arg(
		'tid',
		$ticket_id,
		$network_admin->get_permalink()
	);

	// Email arguments
	$args = array(
		'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket['subject'],
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> MU_Support_System::$ticket_status[0],
		'ticket_priority'		=> MU_Support_System::$ticket_priority[ $ticket['ticket_priority'] ],
		'ticket_message'		=> $ticket['message'],
		'user_nicename'			=> $user->display_name
	);

	$mail_content = incsub_support_admin_get_new_ticket_mail_content( $args );

	$admin_email = MU_Support_System::get_main_admin_email();
	if ( $ticket['admin_id'] ) {
		$admin_user = get_user_by( 'id', $ticket['admin_id'] );
		$admin_email = $admin_user->data->user_email;
	}

	wp_mail( $admin_email, __( "New Support Ticket: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket['subject'], $mail_content, $headers );
}
function incsub_support_send_admin_new_ticket_mail_b( $ticket ) {
	
	$headers = incsub_support_get_email_headers();

	if ( is_multisite() )
		$network_admin = network_admin_url( 'admin.php?page=ticket-manager-b' );
	else
		$network_admin = admin_url( 'admin.php?page=ticket-manager-b' );


	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'action' => 'edit',
			'tab' => 'history'
		),
		$network_admin
	);

	$admin_id = $ticket->admin_id;
	$user = get_userdata( $admin_id );
	if ( ! $user ) {
		$settings = incsub_support_get_settings();
		$main_admin = $settings['incsub_support_main_super_admin'];
		$user = get_user_by( 'login', $main_admin );
		if ( ! $user )
			return;
	}

	// Email arguments
	$args = array(
		'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->subject,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> incsub_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> incsub_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $ticket->message,
		'user_nicename'			=> $user->display_name
	);

	$mail_content = incsub_support_admin_get_new_ticket_mail_content( $args );

	wp_mail( $user->user_email, __( "New Support Ticket: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket->title, $mail_content, $headers );
}

/**
 * Send a mail to a user when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function incsub_support_send_user_reply_mail( $user, $response_user, $ticket_id, $ticket ) {
	
	$headers = incsub_support_get_email_headers();

	$visit_link = get_admin_url( $ticket['blog_id'], 'admin.php' );
	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket_id,
			'page' => 'single-ticket-manager'
		),
		$visit_link
	);

	if ( is_multisite() ) {
		switch_to_blog( $ticket['blog_id'] );
		$blogname = get_bloginfo( 'name' );
		restore_current_blog();	
	}
	else {
		$blogname = get_bloginfo( 'name' );
	}
	

	// Email arguments
	$args = array(
		'title' 				=> $ticket['subject'],
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> MU_Support_System::$ticket_status[0],
		'ticket_priority'		=> MU_Support_System::$ticket_priority[ $ticket['ticket_priority'] ],
		'ticket_message'		=> $ticket['message'],
		'user_nicename'			=> $response_user->display_name,
		'site_name'				=> $blogname
	);

	$mail_content = incsub_support_user_get_reply_ticket_mail_content( $args );

	wp_mail( $user->user_email, __( "Ticket response notification: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket['subject'], $mail_content, $headers );
}
function incsub_support_send_user_reply_mail_b( $ticket, $reply ) {
	
	$ticket_creator = get_userdata( $ticket->user_id );
	if ( ! $ticket_creator )
		return;

	$poster_id = $reply->get_poster_id();
	$poster = get_userdata( $poster_id );
	if ( ! $poster )
		return;

	$headers = incsub_support_get_email_headers();

	$visit_link = get_admin_url( $ticket->blog_id, 'admin.php' );
	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'page' => 'single-ticket-manager'
		),
		$visit_link
	);

	if ( is_multisite() ) {
		switch_to_blog( $ticket->blog_id );
		$blogname = get_bloginfo( 'name' );
		restore_current_blog();	
	}
	else {
		$blogname = get_bloginfo( 'name' );
	}
	

	// Email arguments
	$args = array(
		'title' 				=> $ticket->subject,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> incsub_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> incsub_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $reply->message,
		'user_nicename'			=> $poster->display_name,
		'site_name'				=> $blogname
	);

	$mail_content = incsub_support_user_get_reply_ticket_mail_content( $args );

	wp_mail( $ticket_creator->user_email, __( "Ticket response notification: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $reply->subject, $mail_content, $headers );
}

/**
 * Send a mail to an admin when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function incsub_support_send_admin_reply_mail( $admin_user, $response_user, $ticket_id, $ticket ) {
	
	$headers = incsub_support_get_email_headers();

	// Variables for the message
	if ( ! is_object( MU_Support_System::$network_single_ticket_menu ) )
		$network_admin = new MU_Support_Network_Single_Ticket_Menu( true );
	else
		$network_admin = MU_Support_System::$network_single_ticket_menu;


	$visit_link = add_query_arg(
		'tid',
		$ticket_id,
		$network_admin->get_permalink()
	);

	// Email arguments
	$args = array(
		'title' 				=> $ticket['subject'],
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> MU_Support_System::$ticket_status[0],
		'ticket_priority'		=> MU_Support_System::$ticket_priority[ $ticket['ticket_priority'] ],
		'ticket_message'		=> $ticket['message'],
		'user_nicename'			=> $response_user->display_name
	);

	$mail_content = incsub_support_admin_get_reply_ticket_mail_content( $args );

	wp_mail( $admin_user->user_email, __( "Ticket response notification: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket['subject'], $mail_content, $headers );
}
function incsub_support_send_admin_reply_mail_b( $admin_user, $ticket, $reply ) {
	
	$headers = incsub_support_get_email_headers();

	$poster_id = $reply->get_poster_id();
	$poster = get_userdata( $poster_id );
	if ( ! $poster )
		return;

	// Variables for the message
	if ( is_multisite() )
		$admin_url = network_admin_url( 'admin.php?page=ticket-manager-b' );
	else
		$admin_url = admin_url( 'admin.php?page=ticket-manager-b' );

	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'action' => 'edit',
		),
		$admin_url
	);

	// Email arguments
	$args = array(
		'title' 				=> $reply->subject,
		'visit_link' 			=> $visit_link,
		'ticket_status'			=> incsub_support_get_ticket_status_name( $ticket->ticket_status ),
		'ticket_priority'		=> incsub_support_get_ticket_priority_name( $ticket->ticket_priority ),
		'ticket_message'		=> $reply->message,
		'user_nicename'			=> $poster->display_name
	);

	$mail_content = incsub_support_admin_get_reply_ticket_mail_content( $args );

	wp_mail( $admin_user->user_email, __( "Ticket response notification: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $reply->subject, $mail_content, $headers );
}


/**
 * Send a mail to a user when a update in a ticket has been submitted
 * 
 * @param Object $user User Object
 * @param Integer $ticket_id Ticket ID
 * @param Array $ticket Ticket details
 * 
 * @since 1.9.5
 */
function incsub_support_send_user_closed_mail( $ticket_id ) {

	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;

	$creator = get_userdata( $ticket->user_id );
	if ( ! $creator )
		return false;
	
	$headers = incsub_support_get_email_headers();

	$visit_link = get_admin_url( $ticket->blog_id, 'admin.php' );
	$visit_link = add_query_arg(
		array( 
			'tid' => $ticket->ticket_id,
			'page' => 'single-ticket-manager'
		),
		$visit_link
	);

	// Email arguments
	$args = array(
		'support_fetch_imap' 	=> incsub_support_get_support_fetch_imap_message(),
		'title' 				=> $ticket->title,
		'ticket_url' 			=> $visit_link,
		'ticket_priority'		=> incsub_support_get_ticket_priority_name( $ticket->ticket_priority )
	);
	$mail_content = incsub_get_closed_ticket_mail_content( $args );

	wp_mail( $creator->user_email, __( "Ticket closed notification: ", INCSUB_SUPPORT_LANG_DOMAIN ) . $ticket->title, $mail_content, $headers );
}


function incsub_support_get_support_fetch_imap_message() {
	if ( get_site_option( 'incsub_support_fetch_imap', 'disabled' ) == 'enabled' )
		$support_fetch_imap = __( "***  DO NOT WRITE BELLOW THIS LINE  ***", INCSUB_SUPPORT_LANG_DOMAIN );
	else
		$support_fetch_imap = __("***  DO NOT REPLY TO THIS EMAIL  ***", INCSUB_SUPPORT_LANG_DOMAIN );

	return $support_fetch_imap;
}

function incsub_support_user_get_new_ticket_mail_content( $args ) {
	$content = __( '
SUPPORT_FETCH_IMAP

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Your ticket has been submitted

Visit: SUPPORT_LINK

to reply or view the new ticket.

Thanks,
SUPPORT_SITE_NAME', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_SITE_NAME', $args['site_name'], $content );

	return $content;
}

function incsub_support_admin_get_new_ticket_mail_content( $args ) {
	$content = __( '
SUPPORT_FETCH_IMAP

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

A new ticket has been submitted

Visit: SUPPORT_LINK

to reply or view the new ticket.

==============================================================
	Begin Ticket Message
==============================================================

SUPPORT_USER_NAME said:

SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_FETCH_IMAP', $args['support_fetch_imap'], $content );
	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );

	return $content;
}


function incsub_support_user_get_reply_ticket_mail_content( $args ) {
	$content = __( '

***  DO NOT REPLY TO THIS EMAIL  ***

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Please log into your site and visit the support page to reply to this ticket, if needed.

Visit: SUPPORT_LINK

==============================================================
     Begin Ticket Message
==============================================================

SUPPORT_RESPONSE_USER_NAME said:

SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================

Thanks,
SUPPORT_SITE_NAME', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_RESPONSE_USER_NAME', $args['user_nicename'], $content );
	$content = str_replace( 'SUPPORT_SITE_NAME', $args['site_name'], $content );

	return $content;
} 



function incsub_support_admin_get_reply_ticket_mail_content( $args ) {
	$content = __( '

***  DO NOT REPLY TO THIS EMAIL  ***

Subject: SUPPORT_SUBJECT
Status: SUPPORT_STATUS
Priority: SUPPORT_PRIORITY

Please log into network admin and visit the support page to reply to this ticket, if needed.

Visit: SUPPORT_LINK

==============================================================
     Begin Ticket Message
==============================================================

SUPPORT_RESPONSE_USER_NAME said:

SUPPORT_MESSAGE

==============================================================
      End Ticket Message
==============================================================

Thanks', INCSUB_SUPPORT_LANG_DOMAIN );

	$content = str_replace( 'SUPPORT_SUBJECT', $args['title'], $content );
	$content = str_replace( 'SUPPORT_STATUS', $args['ticket_status'], $content );
	$content = str_replace( 'SUPPORT_PRIORITY', $args['ticket_priority'], $content );
	$content = str_replace( 'SUPPORT_LINK', $args['visit_link'], $content );
	$content = str_replace( 'SUPPORT_MESSAGE', strip_tags( html_entity_decode( $args['ticket_message'] ) ), $content );
	$content = str_replace( 'SUPPORT_RESPONSE_USER_NAME', $args['user_nicename'], $content );

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