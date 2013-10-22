<?php

class MU_Support_System_Ticket_Model {

	static $instance;

	public $tickets_messages_table;
	public $tickets_table;
	public $tickets_cats_table;

	private $tickets_count_cache_slug = 'tickets_count';

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$this->tickets_table 			= $wpdb->base_prefix . 'support_tickets';
		$this->tickets_messages_table 	= $wpdb->base_prefix . 'support_tickets_messages';
		$this->tickets_cats_table 		= $wpdb->base_prefix . 'support_tickets_cats';

		 // Get the correct character collate
		if ( ! empty($wpdb->charset) )
			$this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$this->db_charset_collate .= " COLLATE $wpdb->collate";
	}

	public function create_tables() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$this->create_tickets_table();
		$this->create_tickets_messages_table();
		$this->create_tickets_cats_table();
	}

	/**
	 * Creates/upgrade tickets table
	 * 
	 * @since 1.8
	 */
	private function create_tickets_table() {

		global $wpdb;

		$sql = "CREATE TABLE $this->tickets_table (
			ticket_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			blog_id bigint(20) unsigned NOT NULL,
			cat_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			admin_id bigint(20) unsigned NOT NULL default '0',
			last_reply_id bigint(20) unsigned NOT NULL default '0',
			ticket_type tinyint(1) unsigned NOT NULL default '1',
			ticket_priority tinyint(1) unsigned NOT NULL default '1',
			ticket_status tinyint(1) unsigned NOT NULL default '0',
			ticket_opened timestamp NOT NULL default '0000-00-00 00:00:00',
			ticket_updated timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			num_replies smallint(3) unsigned NOT NULL default '0',
			title varchar(120) character set utf8 NOT NULL,
			view_by_superadmin tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY  (ticket_id),
			KEY site_id (site_id),
			KEY blog_id (blog_id),
			KEY user_id (user_id),
			KEY admin_id (admin_id),
			KEY ticket_status (ticket_status),
			KEY ticket_updated (ticket_updated),
			KEY view_by_superadmin (view_by_superadmin)
		      ) ENGINE=MyISAM $this->db_charset_collate;";

		dbDelta($sql);

	}

	/**
	 * Creates/upgrade tickets messages table
	 * 
	 * @since 1.8
	 */
	private function create_tickets_messages_table() {

		global $wpdb;

		$sql = "CREATE TABLE $this->tickets_messages_table (
			message_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			admin_id bigint(20) unsigned NOT NULL,
			message_date timestamp NOT NULL default CURRENT_TIMESTAMP,
			subject varchar(255) character set utf8 NOT NULL,
			message mediumtext character set utf8 NOT NULL,
			attachments text DEFAULT '',
			PRIMARY KEY  (message_id),
			KEY ticket_id (ticket_id)
		      ) ENGINE=MyISAM $this->db_charset_collate;";

		dbDelta($sql);

	}

	/**
	 * Creates/upgrade tickets categories table
	 * 
	 * @since 1.8
	 */
	private function create_tickets_cats_table() {

		global $wpdb;

		$sql = "CREATE TABLE $this->tickets_cats_table (
			cat_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			cat_name varchar(100) NOT NULL,
			defcat enum('0','1') NOT NULL default '0',
			user_id bigint(20) DEFAULT 0,
			PRIMARY KEY  (cat_id),				
			KEY site_id (site_id),
			UNIQUE KEY cat_name (cat_name)
		      ) ENGINE=MyISAM $this->db_charset_collate;";

		dbDelta($sql);

		$this->fill_tickets_cats_default();

	}

	/**
	 * Fills the FAQ categories table with default values
	 * 
	 * @since 1.8
	 */
	public function fill_tickets_cats_default() {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$default_cat = $wpdb->get_var(
			"SELECT * FROM $this->tickets_cats_table
			WHERE defcat = 2"
		);

		if ( empty( $default_cat ) ) {
			$default_cat_name = __( 'General', INCSUB_SUPPORT_LANG_DOMAIN );
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $this->tickets_cats_table (site_id, cat_name, defcat) 
					VALUES (%d, %s, 2)", 
					$current_site_id,
					$default_cat_name
				)
			);
		}
	}

	public function get_unchecked_tickets() {
		global $wpdb;

		return $wpdb->get_var( "SELECT COUNT(ticket_id) FROM $this->tickets_table WHERE view_by_superadmin = 0" );
	}

	/**
	 * Chcks a ticket as viewed already by a super admin
	 * 
	 * @since 1.8
	 */
	public function check_ticket_as_viewed( $ticket_id ) {
		global $wpdb;

		// We don't want to update the date
		$ticket_updated = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ticket_updated FROM $this->tickets_table WHERE ticket_id = %d",
				$ticket_id
			)
		);

		$wpdb->update(
			$this->tickets_table,
			array( 
				'view_by_superadmin' => 1,
				 'ticket_updated' => $ticket_updated 
			),
			array( 'ticket_id' => $ticket_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	public function get_ticket( $ticket_id ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		return $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM $this->tickets_table
				WHERE site_id = %d
				AND ticket_id = %d
				LIMIT 1",
				$current_site_id,
				$ticket_id
			)
		);
	}


	/**
	 * Get the list of tickets and the total number of them
	 * 
	 * @since 1.8
	 * 
	 * @param String $type archive or other.
	 * @param Integer $offset First ticket to retrieve
	 * @param Integer $upper_limit Last ticket to retrieve
	 */
	public function get_tickets( $type, $offset = 0, $upper_limit = 0, $args = array() ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$where_clause = '';
		if ( 'archive' == $type )
			$where_clause = "AND t.ticket_status = 5";
		elseif ( 'all' == $type )
			$where_clause = '';
		else
			$where_clause = "AND t.ticket_status != 5";

		if ( isset( $args['category'] ) )
			$where_clause .= $wpdb->prepare( " AND t.cat_id = %d", $args['category'] );

		if ( isset( $args['ticket_status'] ) )
			$where_clause .= $wpdb->prepare( " AND t.ticket_status = %d", $args['ticket_status'] );

		if ( isset( $args['blog_id'] ) )
			$where_clause .= $wpdb->prepare( " AND t.blog_id = %d", $args['blog_id'] );

		if ( isset( $args['user_in'] ) && is_array( $args['user_in'] ) ) {
			$where_clause .= " AND t.user_id IN (" . implode( ',', $args['user_in'] ) . ")";
		}


		// Total number of tickets
		$counts = $wpdb->get_var(
			$wpdb->prepare("
					SELECT COUNT(t.ticket_id)
					FROM $this->tickets_table AS t
					LEFT JOIN $this->tickets_cats_table AS c ON (t.cat_id = c.cat_id)
					LEFT JOIN $wpdb->users AS u ON (t.admin_id = u.ID)
					WHERE t.site_id = %d 
					$where_clause",
				$current_site_id
			)
		);

		// Results. It gets a segment based on offset and upper_limit
		
		$pq = $wpdb->prepare("
				SELECT t.ticket_id, t.blog_id, t.user_id, t.cat_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_updated, t.title, t.view_by_superadmin, c.cat_name, u.display_name
				FROM $this->tickets_table AS t
				LEFT JOIN $this->tickets_cats_table AS c ON (t.cat_id = c.cat_id)
				LEFT JOIN $wpdb->users AS u ON (t.admin_id = u.ID)
				WHERE t.site_id = %d 
				$where_clause
				ORDER BY t.ticket_updated DESC
				LIMIT $offset, $upper_limit", 
			$current_site_id
		);
		
		$results = $wpdb->get_results( $pq, ARRAY_A );

		return array(
			'total' 	=> $counts,
			'results' 	=> $results
		);
	}





	/**
	 * Checks if a ticket has been archived
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id Ticket ID
	 * 
	 * @return Boolean
	 */
	public function is_ticket_archived( $ticket_id ) {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ticket_status FROM $this->tickets_table WHERE ticket_id = %d",
				$ticket_id
			),
			ARRAY_A
		);

		if ( 5 == $result['ticket_status'] )
			return true;
		else
			return false;
	}

	/**
	 * Deletes a ticket
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id 
	 * @return 
	 */
	public function delete_ticket( $ticket_id ) {
		global $wpdb;

		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM $this->tickets_table
				 WHERE ticket_id = %d",
			     $ticket_id
		     )
		);

		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM $this->tickets_messages_table
				 WHERE ticket_id = %d",
			     $ticket_id
		     )
		);

		delete_transient( $this->tickets_count_cache_slug . 0 );
		delete_transient( $this->tickets_count_cache_slug . get_current_user_id() );
	}

	/**
	 * Reopen a ticket
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id 
	 * @return 
	 */
	public function open_ticket( $ticket_id ) {
		global $wpdb;

		$wpdb->update(
			$this->tickets_table,
			array( 'ticket_status' => 0 ),
			array( 'ticket_id' => $ticket_id ),
			array( '%d' ),
			array( '%d' )
		);

		delete_transient( $this->tickets_count_cache_slug . 0 );
		delete_transient( $this->tickets_count_cache_slug . get_current_user_id() );
	}

	/**
	 * Close a ticket
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id 
	 * @return 
	 */
	public function close_ticket( $ticket_id ) {
		global $wpdb;

		$wpdb->update(
			$this->tickets_table,
			array( 'ticket_status' => 5 ),
			array( 'ticket_id' => $ticket_id ),
			array( '%d' ),
			array( '%d' )
		);

		delete_transient( $this->tickets_count_cache_slug . 0 );
		delete_transient( $this->tickets_count_cache_slug . get_current_user_id() );
	}

	/**
	 * Cheks if a ticket belongs to the current blog
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id The ticket ID
	 * 
	 * @return Boolean
	 */
	public function is_current_blog_ticket( $ticket_id ) {

		global $wpdb;

		$current_blog_id = get_current_blog_id();

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT blog_id 
				FROM $this->tickets_table 
				WHERE ticket_id = %d
				AND blog_id = %d",
				$ticket_id,
				$current_blog_id
			)
		);

		if ( ! empty( $result ) )
			return true;

		return false;
	}

	/**
	 * Updates a Ticket message
	 * 
	 * @param Integer $faq_id FAQ ID
	 * @param String $question 
	 * @param String $answer 
	 * @param Integer $cat_id Category ID
	 * 
	 */
	public function update_ticket_message( $message_id, $subject, $message ) {
		global $wpdb;

		$wpdb->update(
			$this->tickets_messages_table,
			array( 
				'subject' 	=> $subject,
				'message'	=> $message
			),
			array(
				'message_id'	=> $message_id
			),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Adds a new ticket
	 * 
	 * @since 1.8
	 * 
	 * @param Array $ticket_details Array of ticket fields
	 */
	public function add_new_ticket( $ticket_details ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$attachments = !empty( $ticket_details['attachments'] ) ? $ticket_details['attachments'] : array();
		$time = current_time( 'mysql', 1 );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $this->tickets_table
				(site_id, blog_id, cat_id, user_id, ticket_priority, ticket_opened, ticket_updated, title, admin_id)
				VALUES ( '%d', '%d', '%d', '%d', '%d', %s, %s, '%s', %d )",
				$current_site_id,
				get_current_blog_id(),
				$ticket_details['cat_id'],
				get_current_user_id(),
				$ticket_details['ticket_priority'],
				$time,
				$time,
				$ticket_details['subject'],
				$ticket_details['admin_id']
			)
		);

		if ( ! $wpdb->insert_id )
			return false;



		$ticket_id = $wpdb->insert_id;
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $this->tickets_messages_table
				(site_id, ticket_id, user_id, subject, message, message_date, attachments)
				VALUES (
					'%d', '%d', '%d', '%s', '%s', '%s', '%s'
				)", 
				$current_site_id, 
				$wpdb->insert_id, 
				get_current_user_id(), 
				$ticket_details['subject'], 
				$ticket_details['message'], 
				$time,
				maybe_serialize( $attachments )
			)
		);

		delete_transient( $this->tickets_count_cache_slug . 0 );
		delete_transient( $this->tickets_count_cache_slug . get_current_user_id() );

		if ( ! $wpdb->insert_id )
			return false;
		else
			return $ticket_id;

	}

	/**
	 * Get a ticket details
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $tid Ticket ID
	 */
	public function get_ticket_details( $tid ) {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;
		$blog_id = get_current_blog_id();

		
		$q = $wpdb->prepare(
				"SELECT
					t.ticket_id, m.message_id, t.blog_id, t.cat_id, t.user_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_opened, t.ticket_updated, t.title,
					c.cat_name, u.display_name AS user_name, a.display_name AS admin_name, a.user_login AS admin_login, l.display_name AS last_user_reply, m.user_id AS user_avatar_id, 
					m.admin_id AS admin_avatar_id, m.message_date, m.subject, m.message, r.display_name AS reporting_name, s.display_name AS staff_member, m.attachments AS attachments
				FROM $this->tickets_messages_table AS m
				LEFT JOIN $this->tickets_table AS t ON (m.ticket_id = t.ticket_id)
				LEFT JOIN $wpdb->users AS u ON (t.user_id = u.ID)
				LEFT JOIN $wpdb->users AS a ON (t.admin_id = a.ID)
				LEFT JOIN $wpdb->users AS l ON (t.last_reply_id = l.ID)
				LEFT JOIN $wpdb->users AS r ON (m.user_id = r.ID)
				LEFT JOIN $wpdb->users AS s ON (m.admin_id = s.ID)
				LEFT JOIN $this->tickets_cats_table AS c ON (t.cat_id = c.cat_id)
				WHERE (m.ticket_id = %d AND t.site_id = %d)
				ORDER BY m.message_id ASC",
			$tid,
			$current_site_id,
			$blog_id
		);
		
		$results = $wpdb->get_results( $q, ARRAY_A );

		if ( ! empty( $results ) ) {
			for ( $i = 0; $i < count( $results ); $i++ ) {
				$results[$i]['attachments'] = maybe_unserialize( $results[$i]['attachments'] );		
			} 
		}

		return $results;
	}

	/**
	 * Gets a message details
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $mid Message ID
	 * 
	 * @return Mixed Details Array or false in case of error
	 */
	public function get_ticket_message_details( $mid ) {
		global $wpdb;

		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT subject, message
				FROM $this->tickets_messages_table
				WHERE message_id = %d",
				$mid
			),
			ARRAY_A
		);

		if ( empty( $results ) )
			return false;
		else
			return $results;

	}

	/**
	 * Adds a ticket response.
	 * 
	 * Returns the last inserted ID or false if an error happened.
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id Ticket ID
	 * @param String $title Ticket subject
	 * @param String $message Ticket Message
	 * 
	 * @return Mixed Last inserted ID or false 
	 */
	public function add_ticket_response( $ticket_id, $title, $message, $attachments = array() ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$wpdb->insert(
			$this->tickets_messages_table,
			array(
				'site_id' => $current_site_id,
				'ticket_id' => $ticket_id,
				'admin_id' => get_current_user_id(),
				'subject' => $title,
				'message' => $message,
				'message_date' => current_time( 'mysql', 1 ),
				'attachments' => maybe_serialize( $attachments )
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

		if ( !empty($wpdb->insert_id) )
			return $wpdb->insert_id;
		else
			return false;

	}

	/**
	 * Updates a ticket status
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id Ticket ID
	 * @param Integer $category_id New Category ID. The category must exist.
	 * @param Integer $priority New ticket priority
	 * @param Integer $status New ticket status
	 * @param Mixed $responsibility keep, punt, accept or help or Bool if nothing need to change
	 * 
	 * @return Boolean True if everything went well
	 */
	public function update_ticket_status( $ticket_id, $category_id, $priority, $status, $responsibility = 'keep' ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;
		$time = current_time( 'mysql', 1 );
		
		switch ( $responsibility ) {
			case 'keep': $adding_update_key = ''; break;
			case 'punt': $adding_update_key = ',admin_id = 0'; break;
			case 'help': $adding_update_key = ''; break;
			default: $adding_update_key = ',admin_id = ' . get_current_user_id(); break;
		}

		$q = $wpdb->prepare(
			"UPDATE $this->tickets_table
			SET
				cat_id = %d, 
				last_reply_id = %d, 
				ticket_priority = '%s', 
				ticket_status = %d, 
				ticket_updated = %s,
				num_replies = num_replies + 1
				{$adding_update_key}
			WHERE site_id = %d AND ticket_id = %d
			LIMIT 1", 
			$category_id, 
			get_current_user_id(), 
			$priority, 
			$status,
			$time, 
			$current_site_id, 
			$ticket_id
		);

		$wpdb->query($q);

		delete_transient( $this->tickets_count_cache_slug . 0 );
		delete_transient( $this->tickets_count_cache_slug . get_current_user_id() );

		if ( ! empty( $wpdb->rows_affected ) )
			return true;
		else
			return false;

	}

	/**
	 * Updates a ticket setting
	 * 
	 * 
	 * @return type
	 */
	public function update_ticket_field( $ticket_id, $field, $value ) {
		global $wpdb;

		if ( ! in_array( $field, array( 'admin_id', 'ticket_priority' ) ) )
			return false;

		$wpdb->update(
			$this->tickets_table,
			array( $field => $value ),
			array( 'ticket_id' => $ticket_id ),
			array( '%d' ),
			array( '%d' )
		);
	}


	/**
	 * Get the user ID that posted last in a ticket
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $ticket_id Ticket ID
	 * 
	 * @return Mixed The user ID or false in other case
	 */
	public function get_ticket_user_id( $ticket_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $this->tickets_messages_table 
				WHERE ticket_id = %d 
				AND admin_id = 0 
				ORDER BY message_date 
				DESC LIMIT 1",
				$ticket_id
			)
		);
	}

	
	public function get_ticket_category( $cat_id ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$pq = $wpdb->prepare(
			"SELECT cat_name, user_id
			FROM $this->tickets_cats_table 
			WHERE site_id = %d
			AND cat_id = %d", 
			$current_site_id,
			$cat_id
		);

		return $wpdb->get_row( $pq, ARRAY_A );
	}

	public function get_ticket_categories() {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$pq = $wpdb->prepare(
			"SELECT cat_id, cat_name, defcat, user_id
			FROM $this->tickets_cats_table 
			WHERE site_id = %d 
			ORDER BY cat_name ASC", 
			$current_site_id
		);

		$cats = $wpdb->get_results(
			$pq,
			ARRAY_A
		);
		
		if ( empty($cats) ) {
			$this->fill_tickets_cats_default();
			$cats = $wpdb->get_results(
				$pq,
				ARRAY_A
			);
		}

		return $cats;
	}

	public function update_ticket_category( $id, $name, $user_id = 0 ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		return $wpdb->update(
			$this->tickets_cats_table,
			array( 'cat_name' => $name, 'user_id' => $user_id ),
			array( 'cat_id' => $id, 'site_id' => $current_site_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);

	}

	/**
	 * Get tickets numbers from a category
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $cat_id Category ID
	 *  
	 * @return Integer
	 */
	public function get_tickets_from_cat( $cat_id ) {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$results = $wpdb->get_var( 
			$wpdb->prepare(
				"SELECT COUNT(ticket_id)
				FROM $this->tickets_table 
				WHERE site_id = %d 
				AND cat_id = %d", 
				$current_site_id,
				absint( $cat_id )
			)
		);

		if ( empty( $results ) )
			return 0;
		else
			return $results;
	}


	/**
	 * Adds a category
	 * 
	 * @since 1.8
	 * 
	 * @param String $name Category name
	 * 
	 */
	public function add_ticket_category( $name, $user_id = 0 ) {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$res = $wpdb->insert(
			$this->tickets_cats_table,
			array( 
				'cat_name' 	=> $name,
				'site_id'	=> $current_site_id,
				'user_id'	=> $user_id
			),
			array( '%s', '%d', '%d' )
		);

	}

	/**
	 * Deletes a category
	 * 
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $cat_id Category ID
	 * 
	 * @return Boolean
	 */
	public function delete_ticket_category( $cat_id ) {

		global $wpdb;

		if ( $this->is_default_ticket_category( $cat_id ) )
			return false;

		$pq = $wpdb->prepare(
			"DELETE FROM $this->tickets_cats_table
			WHERE cat_id = %d",
			$cat_id
		);

		if ( ! $this -> get_tickets_from_cat( $cat_id ) ) {
			$result = $wpdb->query( $pq );
		}
		else {

			$default_cat_id = $this->get_default_ticket_category_id();

			$wpdb->update(
				$this->tickets_table,
				array( 'cat_id' => $default_cat_id ),
				array( 'cat_id' => $cat_id ),
				array( '%d' ),
				array( '%d' )
			);

			$result = $wpdb->query( $pq );
		}

		return $result;
	}

	/**
	 * Checks if a category is the default one
	 * 
	 * @since 1.8
	 * @param type $cat_id 
	 * @return type
	 */
	public function is_default_ticket_category( $cat_id ) {

		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT defcat FROM $this->tickets_cats_table
				WHERE cat_id = %d",
				$cat_id
			)
		);

		return ( ! empty( $result ) ) ? true : false;
	}

	/**
	 * Get the default category ID
	 * 
	 * @since 1.8
	 * 
	 * @param Integer $cat_id Category ID
	 *  
	 * @return Integer
	 */
	public function get_default_ticket_category_id() {

		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$pq = $wpdb->prepare(
			"SELECT cat_id
			FROM $this->tickets_cats_table 
			WHERE site_id = %d 
			AND defcat = 2",  // This is an enum field!!
			$current_site_id
		);

		$results = $wpdb->get_var( $pq );

		if ( empty( $results ) )
			return false;
		else
			return $results;
	}

	/**
	 * Sets a ticket category as the default one
	 * @param type $cat_id 
	 * @return type
	 */
	public function set_ticket_category_as_default( $cat_id ) {
		global $wpdb;

		$default_cat = $this->get_default_ticket_category_id();

		$wpdb->update(
			$this->tickets_cats_table,
			array( 'defcat' => 1 ),
			array( 'cat_id' => $default_cat ),
			array( '%d' ),
			array( '%d' )
		);

		$wpdb->update(
			$this->tickets_cats_table,
			array( 'defcat' => 2 ),
			array( 'cat_id' => $cat_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	// BETA FUNCTIONS
	public function get_tickets_beta( $args = array() ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$upper_limit = absint( $args['per_page'] );
		$page = absint( $args['page'] );
		$offset = ( $page - 1 ) * $upper_limit;

		$where = array();

		// Site
		$where[] = $wpdb->prepare( "t.site_id = %d", $current_site_id );

		// Status
		if ( is_integer( $args['status'] ) ) {
			$where[] = $wpdb->prepare( "t.ticket_status = %d", $args['status'] );
		}
		else {
			if ( 'opened' == $args['status'] ) {
				$where[] = "t.ticket_status != 5";
			}
			elseif ( 'closed' == $args['status'] ) {
				$where[] = "t.ticket_status = 5";
			}
		}
		
		// Category
		if ( ! empty( $args['category_in'] ) ) {
			if ( is_array( $args['category_in'] ) ) {
				$category_in = implode( ',', $args['category_in'] );
				$where[] = "t.cat_id IN ( $category_in )";
			}
			else {
				$category_in = absint( $args['category_in'] );
				$where[] = $wpdb->prepare( "t.cat_id = %d", $category_in );
			}
		}

		// Blog
		if ( ! empty( $args['blog_id'] ) )
			$where[] = $wpdb->prepare( "t.blog_id = %d", absint( $args['blog_id'] ) );

		// User
		if ( ! empty( $args['user_in'] ) ) {
			if ( is_array( $args['user_in'] ) ) {
				$user_in = implode( ',', $args['user_in'] );
				$where[] = "t.user_id IN ( $user_in )";
			}
			else {
				$user_in = absint( $args['user_in'] );
				$where[] = $wpdb->prepare( "t.user_id = %d", $user_in );
			}
		}

		$where = implode( " AND ", $where );

		// Results. It gets a segment based on offset and upper_limit
		
		$pq = "SELECT *
			FROM $this->tickets_table AS t
			WHERE $where
			ORDER BY t.ticket_updated DESC
			LIMIT $offset, $upper_limit";
		
		$results = $wpdb->get_results( $pq );

		return $results;
	}

	public function get_tickets_count( $args = array() ) {
		global $wpdb;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$query = "SELECT t.ticket_status, COUNT( t.ticket_id ) AS tickets_num FROM $this->tickets_table t";

		$where_clause = array();

		$where_clause[] = $wpdb->prepare( "t.site_id = %d", $current_site_id );

		$user_id = 0;
		if ( isset( $args['user_in'] ) ) {
			$where_clause[] = $wpdb->prepare( "t.user_id = %d", $args['user_id'] );
			$user_id = $args['user_in'];
		}

		$where_clause = implode( " AND ", $where_clause );

		$query = "$query WHERE $where_clause GROUP BY ticket_status";

		$count = get_transient( $this->tickets_count_cache_slug . $user_id );
		if ( false !== $count )
			return $count;
		
		$count = $wpdb->get_results( $query, ARRAY_A );

		set_transient( $this->tickets_count_cache_slug . $user_id, $count, 43200 ); // We'll save it 12 hours

		return $count;

	}

	public function get_ticket_category_name_beta( $cat_id ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$pq = $wpdb->prepare(
			"SELECT cat_name
			FROM $this->tickets_cats_table 
			WHERE site_id = %d
			AND cat_id = %d
			LIMIT 1", 
			$current_site_id,
			$cat_id
		);

		return $wpdb->get_var( $pq );
	}



}