<?php

/**
 * Sanitize the Incsub_Support_Ticket properties
 * @param  Object $ticket The ticket Object
 * @return Object the sanitized object
 */
function incsub_support_sanitize_ticket_fields( $ticket ) {
	$int_fields = array( 'ticket_id', 'site_id', 'blog_id', 'cat_id', 'user_id', 'admin_id', 
		'last_reply_id', 'ticket_type', 'ticket_priority', 'ticket_status', 'num_replies' );

	$array_fields = array( 'attachments' );

	foreach ( get_object_vars( $ticket ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		if ( in_array( $name, $array_fields ) ) {
			$value = maybe_unserialize( $value );
			if ( ! is_array( $value ) )
				$value = array();
		}

		$ticket->$name = $value;
	}

	$ticket = apply_filters( 'support_system_sanitize_ticket_fields', $ticket );

	return $ticket;
}

/**
 * Get the ticket Status string name
 * 
 * @param  int $status_id
 * @return string
 */
function incsub_support_get_ticket_status_name( $status_id ) {
	return MU_Support_System::$ticket_status[ $status_id ];
}

/**
 * Get the ticket Priority string name
 * 
 * @param  int $priority
 * @return string
 */
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

/**
 * Get a set of tickets
 * 
 * @param  array  $args
 * @return array
 */
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
		'order' => 'desc',
		's' => false
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

	if ( $priority !== false )
		$where[] = $wpdb->prepare( "t.ticket_priority = %d", $priority );

	if ( absint( $blog_id ) > 0 )
		$where[] = $wpdb->prepare( "t.blog_id = %d", $blog_id );

	if ( ! empty( $user_in ) && is_array( $user_in ) ) {
		$user_in = array_map( 'absint', $user_in );
		$where[] = "t.user_id IN (" . implode( ',', $user_in ) . ")";
	}

	if ( $view_by_superadmin !== null )
		$where[] = $wpdb->prepare( "t.view_by_superadmin = %d", $view_by_superadmin );

	if ( $s ) {
		$s = '%' . $s . '%';
		$where[] = $wpdb->prepare( "(t.title LIKE %s OR tm.message LIKE %s)", $s, $s );
	}

	$site_id = absint( $site_id );
	if ( $site_id )
		$where[] = $wpdb->prepare( "t.site_id = %d", $site_id );
	else
		$where[] = $wpdb->prepare( "t.site_id = %d", $current_site_id );

	$tickets_table = incsub_support()->model->tickets_table;

	$allowed_orderby = array( 'ticket_updated', 'title', 'cat_id', 'admin_id', 'blog_id', 'num_replies', 'ticket_priority', 'ticket_status' );
	$allowed_order = array( 'DESC', 'ASC' );
	$order_query = '';
	$order = strtoupper( $order );
	if ( in_array( $orderby, $allowed_orderby ) && in_array( $order, $allowed_order ) ) {
		$order_query = "ORDER BY $orderby $order";
	}

	$limit = '';
	if ( $per_page > -1 )
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );



	$where = "WHERE " . implode( ' AND ', $where );

	$join = '';
	if ( $s ) {
		$tickets_messages_table = incsub_support()->model->tickets_messages_table;
		$join = "LEFT JOIN $tickets_messages_table tm ON t.ticket_id = tm.ticket_id";
	}

	$group = '';
	if ( $s ) {
		$group = "GROUP BY t.ticket_id";
	}

	$join = apply_filters( 'support_system_get_tickets_join', $join, $count );
	$where = apply_filters( 'support_system_get_tickets_where', $where, $count );
	$group = apply_filters( 'support_system_get_tickets_where', $group, $count );

	$tickets = array();
	if ( $count ) {
		$query = "SELECT COUNT(tickets.ticket_id) FROM (SELECT t.ticket_id FROM $tickets_table t $join $where $group) tickets";

		$key = md5( $query );
		$cache_key = "incsub_support_get_tickets_count:$key";
		$results = wp_cache_get( $cache_key, 'support_system_tickets' );

		if ( $results === false ){
			$results = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $results, 'support_system_tickets' );
		}

		$tickets = $results;
	}
	else {
		$query = "SELECT t.* FROM $tickets_table t $join $where $group $order_query $limit";

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

		
	}

	$tickets = apply_filters( 'support_system_get_tickets', $tickets, $args );

	return $tickets;
	
}

/**
 * Get a single ticket
 * 
 * @param  int|Object $ticket The ticket ID or a Incsub_Support_Ticket class object
 * @return Object Incsub_Support_Ticket class object
 */
function incsub_support_get_ticket_b( $ticket ) {
	$ticket = Incsub_Support_Ticket::get_instance( $ticket );

	$ticket = apply_filters( 'support_system_get_ticket', $ticket );

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

/**
 * Close a ticket
 * 
 * Set the ticket status to 5
 * 
 * @param  int $ticket_id
 * @return boolean
 */
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

/**
 * Close a ticket
 * 
 * Set the ticket status to 0
 * 
 * @param  int $ticket_id
 * @return boolean
 */
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
	
	$old_status = $ticket->ticket_status;
	if ( $old_status == $status )
		return false;
	
	incsub_support_update_ticket( $ticket_id, array( 'ticket_status' => $status ) );

	do_action( 'support_system_ticket_transition_status', $status, $old_status, $ticket_id );

	return true;
}

/**
 * Delete a ticket
 * 
 * @param  int $ticket_id
 * @return Boolean
 */
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

	$old_ticket = $ticket;

	do_action( 'support_system_delete_ticket', $ticket_id, $old_ticket );

	wp_cache_delete( $ticket_id, 'support_system_tickets' );

	return true;
}

/**
 * Update a ticket
 * 
 * @param  int $ticket_id
 * @param  args $args
 * @return boolean
 */
function incsub_support_update_ticket( $ticket_id, $args ) {
	global $wpdb;

	$ticket = incsub_support_get_ticket_b( $ticket_id );
	if ( ! $ticket )
		return false;

	$fields = array( 'site_id' => '%d', 'blog_id' => '%d', 'cat_id' => '%d', 'user_id' => '%d', 'admin_id' => '%d', 'last_reply_id' => '%d', 
		'ticket_type' => '%d', 'ticket_priority' => '%d', 'title' => '%s', 'view_by_superadmin' => '%d', 'ticket_status' => '%d', 'num_replies' => '%d' );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( empty( $update ) )
		return false;
	
	$tickets_table = incsub_support()->model->tickets_table;

	$result = $wpdb->update(
		$tickets_table,
		$update,
		array( 'ticket_id' => $ticket_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( ! $result )
		return false;

	wp_cache_delete( $ticket_id, 'support_system_tickets' );

	$old_ticket = $ticket;
	do_action( 'support_system_update_ticket', $ticket_id, $args, $old_ticket );

	return true;

}

/**
 * Insert a new ticket
 * 
 * @param array $args {
 *     An array of elements that make up a ticket.
 * 
 *     @type int 'ticket_priority'    	The ticket priority.
 *     @type int 'cat_id'             	The ticket category ID
 *     @type int 'user_id'           	The creator (user) ID
 *     @type int 'admin_id'           	0 if there's not a staff assigned, staff (user) ID otherwise
 *     @type int 'site_id'           	Site ID, only for multinetwork sites otherwise = 1
 *     @type int 'blog_id'            	Blog ID, only for network sites, otherwise = 1
 *     @type int 'view_by_superadmin'   1 if the ticket has been viewed by a staff, 0 otherwise
 *     @type string 'title'             The ticket title
 *     @type string 'message          	The ticket content
 *     @type array 'attachments'        Array of attachments URLs
 * }
 * @return mixed the new ticket ID, WP_Error otherwise
 */
function incsub_support_insert_ticket( $args = array() ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$default_category = incsub_support_get_default_ticket_category();
	$defaults = array(
		'ticket_priority' => 0,
		'cat_id' => $default_category->cat_id,
		'user_id' => get_current_user_id(),
		'admin_id' => 0,
		'site_id' => $current_site_id,
		'blog_id' => get_current_blog_id(),
		'view_by_superadmin' => 0,
		'title' => '',
		'message' => '',
		'attachments' => array()
	);

	$args = wp_parse_args( $args, $defaults );

	$args['last_reply_id'] = 0;
	$args['ticket_type'] = 1;
	$args['ticket_opened'] = current_time( 'mysql', true );
	$args['ticket_status'] = 0;
	$args['num_replies'] = 0;

	$insert = array();

	// SITE ID
	$insert['site_id'] = absint( $args['site_id'] ); 

	// BLOG ID
	if ( ! is_multisite() )
		$insert['blog_id'] = absint( $args['blog_id'] );
	elseif ( is_multisite() && get_blog_details( $args['blog_id'] ) )
		$insert['blog_id'] = absint( $args['blog_id'] );
	else
		$insert['blog_id'] = get_current_blog_id();


	// USER ID
	$user = get_userdata( $args['user_id'] );
	if ( ! $user && ! is_user_logged_in() )
		$insert['user_id'] = 0;
	elseif ( ! $user && is_user_logged_in() )
		$insert['user_id'] = get_current_user_id();
	else
		$insert['user_id'] = absint( $args['user_id'] );

	// ADMIN ID
	$user = get_userdata( $args['admin_id'] );
	if ( ! $user )
		$insert['admin_id'] = 0;
	else
		$insert['admin_id'] = absint( $args['admin_id'] );

	// CATEGORY
	$category = incsub_support_get_ticket_category( absint( $args['cat_id'] ) );
	if ( ! $category ) {
		$insert['cat_id'] = $default_category->cat_id;
	}
	else {
		$insert['cat_id'] = $category->cat_id;
		$assigned_staff = $category->user_id;
		if ( get_userdata( $assigned_staff ) )
			$insert['admin_id'] = $category->user_id;
	}

	// LAST REPLY ID
	$insert['last_reply_id'] = absint( $args['last_reply_id'] ); 

	// TICKET TYPE
	$insert['ticket_type'] = absint( $args['ticket_type'] ); 
	$insert_wildcards[] = '%d'; 

	// TICKET PRIORITY
	$insert['ticket_priority'] = absint( $args['ticket_priority'] ); 
	$insert_wildcards[] = '%d'; 

	// TICKET STATUS
	$insert['ticket_status'] = absint( $args['ticket_status'] ); 
	$insert_wildcards[] = '%d'; 

	// TICKET OPENED
	$insert['ticket_opened'] = $args['ticket_opened']; 

	// NUM REPLIES
	$insert['num_replies'] = absint( $args['num_replies'] ); 

	// TITLE
	if ( empty( $args['title'] ) )
		return new WP_Error( 'empty_title', __( 'Ticket title must not be empty.', INCSUB_SUPPORT_LANG_DOMAIN ) );
	$insert['title'] = wp_unslash( strip_tags( $args['title'] ) ); 

	// VIEW BY SUPERADMIN
	$view_by_superadmin = absint( $args['view_by_superadmin'] ); 
	if ( $view_by_superadmin > 1 )
		$view_by_superadmin = 0;
	$insert['view_by_superadmin'] = $view_by_superadmin; 


	wp_unslash( $insert );
	
	// Insert the ticket	
	$table = incsub_support()->model->tickets_table;
	$wpdb->insert(
		$table,
		$insert
	);

	$ticket_id = $wpdb->insert_id;

	if ( ! $ticket_id )
		return new WP_Error( 'insert_error', __( 'Error inserting the ticket, please try again later.', INCSUB_SUPPORT_LANG_DOMAIN ) );

	// MESSAGE
	if ( empty( $args['message'] ) )
		return new WP_Error( 'empty_message', __( 'Message must not be empty.', INCSUB_SUPPORT_LANG_DOMAIN ) );
	$message = $args['message'];

	// ATTACHMENTS
	if ( ! is_array( $args['attachments'] ) )
		$args['attachments'] = array();

	// NOW ADD THE FIRST REPLY
	$reply_args = array(
		'site_id' => $args['site_id'],
		'subject' => stripslashes_deep( $args['title'] ),
		'message' => $message,
		'message_date' => current_time( 'mysql', 1 ),
		'attachments' => $args['attachments'],
		'send_emails' => false,
		'poster_id' => $args['user_id']
	);

	$result = incsub_support_insert_ticket_reply( $ticket_id, $reply_args );

	if ( ! $result ) {
		incsub_support_delete_ticket_b( $ticket_id );
		return new WP_Error( 'insert_error', __( 'Error inserting the ticket, please try again later.', INCSUB_SUPPORT_LANG_DOMAIN ) );
	}

	do_action( 'support_system_insert_ticket', $ticket_id, $args );

	// Current user data
	$user = get_userdata( get_current_user_id() );

	$ticket = incsub_support_get_ticket_b( $ticket_id );

	// First, a mail for the user that has just opened the ticket
	incsub_support_send_user_new_ticket_mail_b( $ticket );

	// Now, a mail for the main Administrator
	incsub_support_send_admin_new_ticket_mail_b( $ticket );

	return $ticket_id;

}

/**
 * Make a recount of all replies in a ticket
 * 
 * @param  int $ticket_id
 */
function incsub_support_recount_ticket_replies( $ticket_id ) {
	global $wpdb;
	
	$table = incsub_support()->model->tickets_table;
	
	$ticket = incsub_support_get_ticket_b( $ticket_id );

	if ( ! $ticket )
		return;
	
	$replies = $ticket->get_replies();

	$num_replies = count( $replies ) - 1;

	$last_reply = end( $replies );
	$last_reply_id = $last_reply->is_main_reply ? 0 : $last_reply->message_id;
	
	incsub_support_update_ticket( $ticket_id, array( 'num_replies' => $num_replies, 'last_reply_id' => $last_reply_id ) );

}

function incsub_support_upload_ticket_attachments( $attachments ) {
	global $current_user;

	$files_keys = array_keys( $attachments['name'] );

	$files_uploaded = array();

	$upload_cap = $current_user->allcaps['unfiltered_upload'];
	$current_user->allcaps['unfiltered_upload'] = true;

	$allowed_file_types = apply_filters( 'incsub_support_allowed_mime_types', array(
		'jpg' =>'image/jpg',
		'jpeg' =>'image/jpeg', 
		'gif' => 'image/gif', 
		'png' => 'image/png',
		'zip' => 'application/zip',
		'gz|gzip' => 'application/x-gzip',
		'rar' => 'application/rar',
		'pdf' => 'application/pdf'
	) );

	foreach ( $files_keys as $key ) {
		$file = array(
			'name'		=> $attachments['name'][ $key ],
			'type'		=> $attachments['type'][ $key ],
			'tmp_name'	=> $attachments['tmp_name'][ $key ],
			'error'		=> $attachments['error'][ $key ],
			'size'		=> $attachments['size'][ $key ]
		);
		$uploaded = wp_handle_upload( $file, $overrides = array('test_form' => false, 'mimes' => $allowed_file_types) );
		if ( ! isset( $uploaded['error'] ) )
			$files_uploaded[] = $uploaded;
	}
	$current_user->allcaps['unfiltered_upload'] = $upload_cap;

	return $files_uploaded;
}



