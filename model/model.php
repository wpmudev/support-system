<?php

/**
 * Support System Model
 */

if ( ! class_exists( 'MU_Support_System_Model' ) ) {

	class MU_Support_System_Model {

		/**
		 * Singleton
		 */
		public static $instance;

		/**
		 * Tables
		 */
		private $tickets_messages_table;
		private $faq_table;
		private $faq_cats_table;
		private $tickets_table;
		private $tickets_cats_table;

		/**
		 * Database charset and collate
		 */
		private $db_charset_collate = '';

		/**
		 * Singleton Pattern
		 * 
		 * Gets the instance of the class
		 * 
		 * @since 1.8
		 */
		public static function get_instance() {
			if ( empty( self::$instance ) )
				self::$instance = new MU_Support_System_Model();
			return self::$instance;
		}

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {
			global $wpdb;

			$this->faq_table 				= $wpdb->base_prefix . 'support_faq';
			$this->faq_cats_table 			= $wpdb->base_prefix . 'support_faq_cats';
			$this->tickets_table 			= $wpdb->base_prefix . 'support_tickets';
			$this->tickets_messages_table 	= $wpdb->base_prefix . 'support_tickets_messages';
			$this->tickets_cats_table 		= $wpdb->base_prefix . 'support_tickets_cats';

			 // Get the correct character collate
			if ( ! empty($wpdb->charset) )
				$this->db_charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( ! empty($wpdb->collate) )
				$this->db_charset_collate .= " COLLATE $wpdb->collate";
		}

		/**
		 * Creates all tables
		 * 
		 * @since 1.8
		 */
		public function create_tables() {

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			
			$this->create_faq_table();
			$this->create_faq_cats_table();
			$this->create_tickets_table();
			$this->create_tickets_messages_table();
			$this->create_tickets_cats_table();
		}

		

		/**
		 * Creates/upgrade FAQ table
		 * 
		 * @since 1.8
		 */
		private function create_faq_table() {

			global $wpdb;

			$sql = "CREATE TABLE $this->faq_table (
				faq_id bigint(20) unsigned NOT NULL auto_increment,
				site_id bigint(20) unsigned NOT NULL,
				cat_id bigint(20) unsigned NOT NULL,
				question varchar(255) NOT NULL,
				answer mediumtext NOT NULL,
				help_views bigint(20) unsigned NOT NULL default '0',
				help_count bigint(20) unsigned NOT NULL default '0',
				help_yes int(12) unsigned NOT NULL default '0',
				help_no int(12) unsigned NOT NULL default '0',
				PRIMARY KEY  (faq_id),
				KEY site_id (site_id,cat_id)
			      ) ENGINE=MyISAM $this->db_charset_collate;";

			dbDelta($sql);



		}

		/**
		 * Creates/upgrade FAQ categories table
		 * 
		 * @since 1.8
		 */
		private function create_faq_cats_table() {

			global $wpdb;

			$sql = "CREATE TABLE $this->faq_cats_table (
				cat_id bigint(20) unsigned NOT NULL auto_increment,
				site_id bigint(20) unsigned NOT NULL,
				cat_name varchar(255) NOT NULL,
				qcount smallint(3) unsigned NOT NULL,
				defcat enum('0','1') NOT NULL default '0',
				PRIMARY KEY  (cat_id),
				KEY site_id (site_id),
				UNIQUE KEY cat_name (cat_name)
			      ) ENGINE=MyISAM $this->db_charset_collate;";

			dbDelta($sql);

			$this->fill_faq_cats_default();

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
				PRIMARY KEY  (cat_id),
				KEY site_id (site_id),
				UNIQUE KEY cat_name (cat_name)
			      ) ENGINE=MyISAM $this->db_charset_collate;";

			dbDelta($sql);

			$this->fill_tickets_cats_default();

		}

		/**
		 * Upgrades the Database to 1.8 version
		 * 
		 * @since 1.8
		 */
		public function upgrade_18() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_tickets_messages_table();

			// Converting FAQs entities to text
			// So they can be properly displayed in the WP Editor
			global $wpdb;
			$faqs_ids = $wpdb->get_col( "SELECT faq_id FROM $this->faq_table" );

			if ( ! empty( $faqs_ids ) ) {
				foreach( $faqs_ids as $faq_id ) {
					$faq = $wpdb->get_row( 
						"SELECT question, answer, cat_id 
						FROM $this->faq_table
						WHERE faq_id = $faq_id",
						ARRAY_A );
					$answer = htmlspecialchars_decode( stripslashes_deep( html_entity_decode( str_replace( '&nbsp;', '<br/>', $faq['answer'] ), ENT_QUOTES ) ) );
					$question = stripslashes_deep( html_entity_decode( $faq['question'], ENT_QUOTES ) );
					$cat_id = $faq['cat_id'];
					$this->update_faq_question( $faq_id, $question, $answer, $cat_id );
				}
			}

			// Checking all tickets as viewed by a Super Admin
			$wpdb->query( "UPDATE $this->tickets_table SET view_by_admin = 1" );

			// Same for tickets messages
			$messages_ids = $wpdb->get_col( "SELECT message_id FROM $this->tickets_messages_table" );
			if ( ! empty( $messages_ids ) ) {
				foreach( $messages_ids as $message_id ) {
					$message = $wpdb->get_row( 
						"SELECT subject, message 
						FROM $this->tickets_messages_table
						WHERE message_id = $message_id",
						ARRAY_A );
					$subject = stripslashes_deep( $message['subject'] );
					$message_text = wpautop( stripslashes_deep( $message['message'] ) );
					$this->update_ticket_message( $message_id, $subject, $message_text );
				}
			}
		}

		/**
		 * Upgrades the Database to 1.7.2.2 version
		 * 
		 * @since 1.8
		 */
		public function upgrade_1722() {
			global $wpdb;

			$faq_cats = $wpdb->get_results("SELECT cat_id, site_id FROM $this->faq_cats_table;");

			foreach ($faq_cats as $faq_cat) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $this->faq_cats_table 
						SET qcount = (SELECT COUNT(*) FROM $this->faq_table 
							WHERE cat_id = '%d' 
							AND site_id = '%d') 
						WHERE cat_id = %d AND site_id = %d;", 
						$faq_cat->cat_id, 
						$faq_cat->site_id, 
						$faq_cat->cat_id, 
						$faq_cat->site_id
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


		/**
		 * Get the list of tickets and the total number of them
		 * 
		 * @since 1.8
		 * 
		 * @param String $type archive or other.
		 * @param Integer $offset First ticket to retrieve
		 * @param Integer $upper_limit Last ticket to retrieve
		 */
		public function get_tickets( $type, $offset, $upper_limit, $args = array() ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

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

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $this->tickets_table
					(site_id, blog_id, cat_id, user_id, ticket_priority, ticket_opened, title)
					VALUES ( '%d', '%d', '%d', '%d', '%d', NOW(), '%s' )",
					$current_site->id,
					get_current_blog_id(),
					$ticket_details['cat_id'],
					get_current_user_id(),
					$ticket_details['ticket_priority'],
					$ticket_details['subject']
				)
			);

			if ( ! $wpdb->insert_id )
				return false;

			$ticket_id = $wpdb->insert_id;
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO $this->tickets_messages_table
					(site_id, ticket_id, user_id, subject, message, message_date)
					VALUES (
						'%d', '%d', '%d', '%s', '%s', '%s'
					)", 
					$current_site->id, 
					$wpdb->insert_id, 
					get_current_user_id(), 
					$ticket_details['subject'], 
					$ticket_details['message'], 
					gmdate('Y-m-d H:i:s')
				)
			);

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

			$current_site_id = $current_site->id;
			$blog_id = get_current_blog_id();

			
			$q = $wpdb->prepare(
					"SELECT
						t.ticket_id, m.message_id, t.blog_id, t.cat_id, t.user_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_opened, t.ticket_updated, t.title,
						c.cat_name, u.display_name AS user_name, a.display_name AS admin_name, l.display_name AS last_user_reply, m.user_id AS user_avatar_id, 
						m.admin_id AS admin_avatar_id, m.message_date, m.subject, m.message, r.display_name AS reporting_name, s.display_name AS staff_member
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

			return $wpdb->get_results( $q, ARRAY_A );
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
		public function add_ticket_response( $ticket_id, $title, $message ) {
			global $wpdb;

			$current_site = get_current_site();
			$wpdb->insert(
				$this->tickets_messages_table,
				array(
					'site_id' => $current_site->id,
					'ticket_id' => $ticket_id,
					'admin_id' => get_current_user_id(),
					'subject' => $title,
					'message' => $message,
					'message_date' => gmdate('Y-m-d H:i:s')
				),
				array(
					'%d',
					'%d',
					'%d',
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
					num_replies = num_replies + 1
					{$adding_update_key}
				WHERE site_id = %d AND ticket_id = %d
				LIMIT 1", 
				$category_id, 
				get_current_user_id(), 
				$priority, 
				$status, 
				$current_site->id, 
				$ticket_id
			);

			$wpdb->query($q);

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

			$current_site_id = $current_site->id;

			$pq = $wpdb->prepare(
				"SELECT cat_name
				FROM $this->tickets_cats_table 
				WHERE site_id = %d
				AND cat_id = %d", 
				$current_site_id,
				$cat_id
			);

			return $wpdb->get_row( $pq, ARRAY_A );
		}
	
		
		public function get_faq_category( $cat_id ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$pq = $wpdb->prepare(
				"SELECT cat_name
				FROM $this->faq_cats_table 
				WHERE site_id = %d
				AND cat_id = %d", 
				$current_site_id,
				$cat_id
			);

			return $wpdb->get_row( $pq, ARRAY_A );
		}


		public function get_ticket_categories() {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$pq = $wpdb->prepare(
				"SELECT cat_id, cat_name, defcat
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
				$this->fill_ticket_cats_default();
				$cats = $wpdb->get_results(
					$pq,
					ARRAY_A
				);
			}

			return $cats;
		}

		public function update_ticket_category_name( $id, $name ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			return $wpdb->update(
				$this->tickets_cats_table,
				array( 'cat_name' => $name ),
				array( 'cat_id' => $id, 'site_id' => $current_site_id ),
				array( '%s' ),
				array( '%d' )
			);

		}

		public function update_faq_category_name( $id, $name ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			return $wpdb->update(
				$this->faq_cats_table,
				array( 'cat_name' => $name ),
				array( 'cat_id' => $id, 'site_id' => $current_site_id ),
				array( '%s' ),
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

			$current_site_id = $current_site->id;

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
		public function add_ticket_category( $name ) {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$res = $wpdb->insert(
				$this->tickets_cats_table,
				array( 
					'cat_name' 	=> $name,
					'site_id'	=> $current_site_id
				),
				array( '%s', '%d' )
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

			$current_site_id = $current_site->id;

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
		 * Get all the questions in a site
		 * 
		 * @since 1.8
		 * 
		 * @param String $type archive or other.
		 * @param Integer $offset First ticket to retrieve
		 * @param Integer $upper_limit Last ticket to retrieve
		 * 
		 * @return Array of questions
		 */
		public function get_questions( $offset, $upper_limit, $args = array() ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$where_clause = '';
			if ( isset( $args['category'] ) )
				$where_clause .= $wpdb->prepare( " AND q.cat_id = %d", $args['category'] );

			$results = $wpdb->get_results(
				"SELECT q.faq_id, q.cat_id, q.question, q.answer, c.cat_name, q.help_yes, q.help_no
				FROM $this->faq_table as q
				LEFT JOIN $this->faq_cats_table AS c 
				ON ( q.cat_id = c.cat_id )
				WHERE q.site_id = $current_site->id
				$where_clause
				ORDER BY c.cat_name, q.question ASC
				LIMIT $offset, $upper_limit",
				ARRAY_A
			);

			$counts = $wpdb->get_var(
				"SELECT COUNT(q.faq_id)
				FROM $this->faq_table as q
				LEFT JOIN $this->faq_cats_table AS c 
				ON ( q.cat_id = c.cat_id )
				WHERE q.site_id = $current_site->id"
			);

			return array(
				'total' 	=> $counts,
				'results' 	=> $results
			);
		}

		/**
		 * Gets a FAQ question details
		 * 
		 * @since 1.8
		 */
		public function get_faq_details( $faq_id ) {

			global $wpdb;

			return $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM $this->faq_table AS q
					LEFT JOIN $this->faq_cats_table AS c
					ON ( q.cat_id = c.cat_id )
					WHERE q.faq_id = %d",
					$faq_id
				),
				ARRAY_A
			);

		}

		/**
		 * Gets all FAQs details or just by a category
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $cat_id Category ID (optional)
		 * 
		 * @return Array of FAQs details
		 */
		public function get_faqs( $cat_id = false ) {
			global $wpdb, $current_site;

			$where_clause = '';
			if ( $cat_id )
				$where_clause = $wpdb->prepare( " AND q.cat_id = %d", $cat_id );

			
			$pq = $wpdb->prepare(
				"SELECT 
				q.faq_id, q.question, q.answer, q.help_count, q.help_yes, q.help_no, c.cat_name, c.cat_id, c.qcount
				FROM $this->faq_table AS q
				LEFT JOIN $this->faq_cats_table AS c 
				ON ( q.cat_id = c.cat_id )
				WHERE q.site_id = %d
				$where_clause
				ORDER BY c.cat_name ASC",
				$current_site->id
			);

			return $wpdb->get_results( $pq, ARRAY_A );

		}

		/**
		 * Fills the FAQ categories table with default values
		 * 
		 * @since 1.8
		 */
		public function fill_faq_cats_default() {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$default_cat = $wpdb->get_var(
				"SELECT * FROM $this->faq_cats_table
				WHERE defcat = 2"
			);
			if ( empty( $default_cat ) ) {
				$default_cat_name = __( 'General questions', INCSUB_SUPPORT_LANG_DOMAIN );
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $this->faq_cats_table (site_id, cat_name, defcat) 
						VALUES (%d, %s, 2)", 
						$current_site_id,
						$default_cat_name
					)
				);	
			}
			

		}

		/**
		 * Fills the FAQ categories table with default values
		 * 
		 * @since 1.8
		 */
		public function fill_tickets_cats_default() {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

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

		/**
		 * Returns all FAQ categories
		 * 
		 * @since 1.8
		 * 
		 * @return Array
		 */
		public function get_faq_categories() {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$pq = $wpdb->prepare(
				"SELECT cat_id, cat_name, defcat, qcount
				FROM $this->faq_cats_table 
				WHERE site_id = %d 
				ORDER BY cat_name ASC", 
				$current_site_id
			);

			$cats = $wpdb->get_results(
				$pq,
				ARRAY_A
			);
			
			if ( empty($cats) ) {
				$this->fill_faq_cats_default();
				
				$cats = $wpdb->get_results(
					$pq,
					ARRAY_A
				);
			}

			return $cats;
		}

		/**
		 * Updates a FAQ question
		 * 
		 * @param Integer $faq_id FAQ ID
		 * @param String $question 
		 * @param String $answer 
		 * @param Integer $cat_id Category ID
		 * 
		 */
		public function update_faq_question( $faq_id, $question, $answer, $cat_id ) {
			global $wpdb;

			$wpdb->update(
				$this->faq_table,
				array( 
					'question' 	=> $question,
					'answer'	=> $answer,
					'cat_id'	=> $cat_id
				),
				array(
					'faq_id'	=> $faq_id
				),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);
		}

		/**
		 * Votes a FAQ question (yes/no)
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $faq_id FAQ ID
		 * @param Boolean $vote False if the FAQ wasn't useful 
		 */
		public function vote_faq_question( $faq_id, $vote ) {
			global $wpdb;

			$set_field = $vote ? 'help_yes' : 'help_no';

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $this->faq_table
					SET $set_field = $set_field + 1
					WHERE faq_id = %d",
					$faq_id
				)
			);
		}


		/**
		 * Adds a new FAQ question
		 *
		 * @since 1.8
		 *  
		 * @param type $question 
		 * @param type $answer 
		 * @param type $cat_id Category ID
		 *
		 * @return Mixed ID for the new question / false if error
		 */
		public function add_new_faq_question( $question, $answer, $cat_id ) {
			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$result = $wpdb->insert( 
				$this->faq_table, 
				array( 
					'question' 	=> $question, 
					'answer' 	=> $answer,
					'cat_id' 	=> $cat_id,
					'site_id'	=> $current_site_id
				), 
				array( 
					'%s', 
					'%s', 
					'%d', 
					'%d' 
				) 
			);

			if ( $result ) {
				$faq_id = $wpdb->insert_id;
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE $this->faq_cats_table
						SET qcount = qcount + 1
						WHERE cat_id = %d",
						$cat_id
					)
				);

				if ( ! $result )
					return false;
				else
					return $faq_id;
			}
			
			return false;
			
		}

		/**
		 * Deletes a FAQ question
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $faq_id FAQ ID
		 * @return type
		 */
		public function delete_faq_question( $faq_id ) {
			global $wpdb;

			
			$cat_id = absint($wpdb->get_var(
				$wpdb->prepare(
					"SELECT cat_id 
					FROM $this->faq_table
					WHERE faq_id = %d",
					$faq_id
				)
			));

			if ( $cat_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM $this->faq_table
						WHERE faq_id = %d",
						$faq_id
					)
				);

				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $this->faq_cats_table
						SET qcount = qcount - 1
						WHERE cat_id = %d",
						$cat_id
					)
				);

				return true;
			}

			return false;

			
		}


		/**
		 * Deletes a FAQ category
		 * 
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $cat_id Category ID
		 * 
		 * @return Boolean
		 */
		public function delete_faq_category( $cat_id ) {

			global $wpdb;

			if ( $this->is_default_faq_category( $cat_id ) )
				return false;

			$pq = $wpdb->prepare(
				"DELETE FROM $this->faq_cats_table
				WHERE cat_id = %d",
				$cat_id
			);

			if ( ! $this -> get_faqs_from_cat( $cat_id ) ) {
				$result = $wpdb->query( $pq );
			}
			else {

				$default_cat_id = $this->get_default_faq_category_id();

				$wpdb->update(
					$this->faq_table,
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
		 * Checks if a FAQ category is the default one
		 * 
		 * @since 1.8
		 * @param type $cat_id 
		 * @return type
		 */
		public function is_default_faq_category( $cat_id ) {

			global $wpdb;

			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT defcat FROM $this->faq_cats_table
					WHERE cat_id = %d",
					$cat_id
				)
			);

			return ( ! empty( $result ) ) ? true : false;
		}


		/**
		 * Get FAQs numbers from a category
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $cat_id Category ID
		 *  
		 * @return Integer
		 */
		public function get_faqs_from_cat( $cat_id ) {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$results = $wpdb->get_var( 
				$wpdb->prepare(
					"SELECT COUNT(faq_id)
					FROM $this->faq_table
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
		 * Get the default FAQ category ID
		 * 
		 * @since 1.8
		 * 
		 * @param Integer $cat_id Category ID
		 *  
		 * @return Integer
		 */
		public function get_default_faq_category_id() {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$pq = $wpdb->prepare(
				"SELECT cat_id
				FROM $this->faq_cats_table
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


		/**
		 * Sets a FAQ category as the default one
		 * @param type $cat_id 
		 * @return type
		 */
		public function set_faq_category_as_default( $cat_id ) {
			global $wpdb;

			$default_cat = $this->get_default_faq_category_id();

			$wpdb->update(
				$this->faq_cats_table,
				array( 'defcat' => 1 ),
				array( 'cat_id' => $default_cat ),
				array( '%d' ),
				array( '%d' )
			);

			$wpdb->update(
				$this->faq_cats_table,
				array( 'defcat' => 2 ),
				array( 'cat_id' => $cat_id ),
				array( '%d' ),
				array( '%d' )
			);
		}


		/**
		 * Adds a FAQ category
		 * 
		 * @since 1.8
		 * 
		 * @param String $name Category name
		 * 
		 */
		public function add_faq_category( $name ) {

			global $wpdb, $current_site;

			$current_site_id = $current_site->id;

			$res = $wpdb->insert(
				$this->faq_cats_table,
				array( 
					'cat_name' 	=> $name,
					'site_id'	=> $current_site_id
				),
				array( '%s', '%d' )
			);

		}


	}



}


?>