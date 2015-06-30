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


/**
 * Get a set of tickets
 * 
 * @param  array  $args
 * @return array
 */
function incsub_support_get_tickets( $args = array() ) {
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

	$join = apply_filters( 'support_system_get_tickets_join', $join, $count, $args );
	$where = apply_filters( 'support_system_get_tickets_where', $where, $count, $args );
	$group = apply_filters( 'support_system_get_tickets_group', $group, $count, $args );

	$tickets = array();
	if ( $count ) {
		$query = "SELECT COUNT(tickets.ticket_id) FROM (SELECT t.ticket_id FROM $tickets_table t $join $where $group) tickets";
		$results = $wpdb->get_var( $query );
		$tickets = $results;
	}
	else {
		$query = "SELECT t.* FROM $tickets_table t $join $where $group $order_query $limit";
		$results = $wpdb->get_results( $query );
		$tickets = array_map( 'incsub_support_get_ticket', $results );
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
function incsub_support_get_ticket( $ticket ) {
	$ticket = Incsub_Support_Ticket::get_instance( $ticket );

	$ticket = apply_filters( 'support_system_get_ticket', $ticket );

	return $ticket;
}

function incsub_support_get_tickets_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;

	$count = incsub_support_get_tickets( $args );

	return $count;
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
	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return false;

	// Is already closed?
	if ( 5 == $ticket->ticket_status )
		return true;

	$result = incsub_support_ticket_transition_status( $ticket_id, 5 );

	do_action( 'support_system_close_ticket', $ticket_id );

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
	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return false;

	// Is already opened?
	if ( 5 != $ticket->ticket_status )
		return true;

	$previous_status = $ticket->ticket_status;

	$result = incsub_support_ticket_transition_status( $ticket_id, 0 );
	incsub_support_update_ticket_meta( $ticket_id, 'previous_status', $previous_status );

	return $result;
}

function incsub_support_ticket_transition_status( $ticket_id, $status ) {
	$plugin = incsub_support();
	$all_status = array_keys( $plugin::$ticket_status );

	if ( ! in_array( $status, $all_status ) )
		return false;

	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return false;
	
	$previous_status = $ticket->ticket_status;
	if ( $previous_status == $status )
		return false;
	
	incsub_support_update_ticket( $ticket_id, array( 'ticket_status' => $status ) );
	incsub_support_update_ticket_meta( $ticket_id, 'previous_status', $previous_status );

	do_action( 'support_system_ticket_transition_status', $status, $previous_status, $ticket_id );

	return true;
}

function incsub_support_restore_ticket_previous_status( $ticket_id ) {
	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return;

	$previous_status = incsub_support_get_ticket_meta( $ticket_id, 'previous_status', true );
	if ( $previous_status === false ) {
		incsub_support_open_ticket( $ticket_id );
	}
	else {
		incsub_support_ticket_transition_status( $ticket_id, $previous_status );	
	}

	
}

/**
 * Delete a ticket
 * 
 * @param  int $ticket_id
 * @return Boolean
 */
function incsub_support_delete_ticket( $ticket_id ) {
	global $wpdb;

	$ticket = incsub_support_get_ticket( $ticket_id );

	if ( ! $ticket )
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

	incsub_support_clean_ticket_cache( $ticket_id );
	incsub_support_clean_ticket_category_cache( $ticket->cat_id );

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

	$ticket = incsub_support_get_ticket( $ticket_id );
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

	$update['ticket_updated'] = current_time( 'mysql', true );
	$update_wildcards[] = '%s';

	$result = $wpdb->update(
		$tickets_table,
		$update,
		array( 'ticket_id' => $ticket_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( ! $result )
		return false;

	incsub_support_clean_ticket_cache( $ticket_id );
	if ( array_key_exists( 'cat_id', $update ) ) {
		// Clean the old and new ctaegories cache
		incsub_support_clean_ticket_category_cache( $update['cat_id'] );
		incsub_support_clean_ticket_category_cache( $ticket->cat_id );
	}

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

	// TICKET OPENED/UPDATED
	$insert['ticket_opened'] = $args['ticket_opened']; 
	$insert['ticket_updated'] = $args['ticket_opened']; 

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

	do_action( 'support_system_before_insert_ticket', $insert );
	
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
		incsub_support_delete_ticket( $ticket_id );
		return new WP_Error( 'insert_error', __( 'Error inserting the ticket, please try again later.', INCSUB_SUPPORT_LANG_DOMAIN ) );
	}

	incsub_support_clean_ticket_category_cache( $category );

	do_action( 'support_system_insert_ticket', $ticket_id, $args );

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
	
	$ticket = incsub_support_get_ticket( $ticket_id );

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

	$allowed_file_types = incsub_support_get_allowed_mime_types();

	$errors = array();

	foreach ( $files_keys as $key ) {
		$file = array(
			'name'		=> $attachments['name'][ $key ],
			'type'		=> $attachments['type'][ $key ],
			'tmp_name'	=> $attachments['tmp_name'][ $key ],
			'error'		=> $attachments['error'][ $key ],
			'size'		=> $attachments['size'][ $key ]
		);

		if ( ! function_exists( 'wp_handle_upload' ) ) 
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$uploaded = wp_handle_upload( $file, $overrides = array('test_form' => false, 'mimes' => $allowed_file_types) );
		if ( ! isset( $uploaded['error'] ) )
			$files_uploaded[] = $uploaded;
		else
			$errors[] = sprintf( __( 'Error uploading <strong>%s</strong> file: %s', INCSUB_SUPPORT_LANG_DOMAIN ), $attachments['name'][ $key ], $uploaded['error'] );
	}

	$current_user->allcaps['unfiltered_upload'] = $upload_cap;

	if ( ! empty( $errors ) ) {
		// There has been errors uploading one or more file
		if ( ! empty( $files_uploaded ) ) {
			// There have been files uploaded, let's delete them
			foreach ( $files_uploaded as $file ) {
				@unlink( $file['file'] );
			}
		}

		return array( 'error' => true, 'result' => $errors );
	}

	return array( 'error' => false, 'result' => $files_uploaded );
}

function incsub_support_get_allowed_mime_types() {
	return apply_filters( 'incsub_support_allowed_mime_types', array(
		'jpg' =>'image/jpg',
		'jpeg' =>'image/jpeg', 
		'gif' => 'image/gif', 
		'png' => 'image/png',
		'zip' => 'application/zip',
		'gz|gzip' => 'application/x-gzip',
		'rar' => 'application/rar',
		'pdf' => 'application/pdf',
		'txt' => 'text/plain',
	) );
}


function incsub_support_get_edit_ticket_admin_url( $ticket_id ) {

	if ( ! incsub_support_get_ticket( $ticket_id ) )
		return '';

	if ( is_multisite() )
		$network_admin = network_admin_url( 'admin.php?page=ticket-manager' );
	else
		$network_admin = admin_url( 'admin.php?page=ticket-manager' );


	return add_query_arg(
		array( 
			'tid' => $ticket_id,
			'action' => 'edit',
		),
		$network_admin
	);
}


function incsub_support_get_ticket_meta( $ticket_id, $key = '', $single = false) {
	return get_metadata( 'support_ticket', $ticket_id, $key, $single );
}

function incsub_support_add_ticket_meta( $ticket_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'support_ticket', $ticket_id, $meta_key, $meta_value, $unique );
}

function incsub_support_update_ticket_meta( $ticket_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'support_ticket', $ticket_id, $meta_key, $meta_value, $prev_value );
}

function incsub_support_delete_ticket_meta( $ticket_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'support_ticket', $ticket_id, $meta_key, $meta_value );
}

function incsub_support_clean_ticket_cache( $ticket ) {
	$ticket = incsub_support_get_ticket( $ticket );
	if ( ! $ticket )
		return;

	wp_cache_delete( $ticket->ticket_id, 'support_system_tickets' );

	do_action( 'support_system_clean_ticket_cache', $ticket );
}

/**
 * Return a ticket URL based on a user ID
 * 
 * Tickets can be displayed on frontend or not and user may have permissions or not.
 * This function tries to find a ticket URL depending on the user capabilities.
 * 
 * @param  Integer  $ticket_id Ticket ID
 * @param  Mixed $user_id   User ID/default false
 * @return String             Ticket URL
 */
function incsub_support_get_user_ticket_url( $ticket_id, $user_id = false ) {
	if ( ! $user_id )
		$user_id = get_current_user_id();

	$ticket = incsub_support_get_ticket( $ticket_id );
	if ( ! $ticket )
		return false;

	$settings = incsub_support_get_settings();

	$support_blog_id = get_current_blog_id();
	if ( is_multisite() ) {
		$support_blog_id = $settings['incsub_support_blog_id'];
		switch_to_blog( $support_blog_id );
		$support_page = get_post( incsub_support_get_support_page_id() );
		restore_current_blog();
	}
	else {
		$support_page = get_post( incsub_support_get_support_page_id() );
	}

	// Check the user role
	$user_can = incsub_support_user_can( $user_id, 'read_ticket' );
	if ( ! $user_can )
		return false;

	$url  = false;

	if ( incsub_support_get_support_page_id() && $support_page ) {
		// The tickets are in the frontend
		$url = incsub_support_get_the_ticket_permalink( $ticket_id );
	}
	else {
		// The tickets are in the admin side

		$url = get_admin_url( $ticket->blog_id, 'admin.php' );
		$url = add_query_arg(
			array( 
				'tid' => $ticket->ticket_id,
				'page' => 'ticket-manager',
				'action' => 'edit'
			),
			$url
		);
	}

	return $url;
}

function incsub_support_get_the_ticket_permalink( $ticket_id = false ) {
	if ( $ticket_id ) {
		$ticket = incsub_support_get_ticket( $ticket_id );
		if ( ! $ticket )
			return '';

		$blog_id = incsub_support_get_setting( 'incsub_support_blog_id' );
		if ( is_multisite() )
			switch_to_blog( $blog_id );

		$support_page_id = incsub_support_get_support_page_id();
		$url = get_permalink( $support_page_id );

		if ( is_multisite() )
			restore_current_blog();

		if ( ! $url )
			return '';

		return add_query_arg( 'tid', $ticket->ticket_id, $url );
	}
	
	$ticket = incsub_support()->query->ticket;	
	$url = add_query_arg( 'tid', $ticket->ticket_id );
	return $url;
}