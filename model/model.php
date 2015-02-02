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
		public $tickets_messages_table;
		public $ticketmeta;
		public $faq_table;
		public $faq_cats_table;
		public $tickets_table;
		public $tickets_cats_table;

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
			$this->ticketmeta 				= $wpdb->base_prefix . 'support_ticketmeta';
			$this->tickets_messages_table 	= $wpdb->base_prefix . 'support_tickets_messages';
			$this->tickets_cats_table 		= $wpdb->base_prefix . 'support_tickets_cats';

			$wpdb->support_ticketmeta = $this->ticketmeta;

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
			$this->create_ticketmeta_table();
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

		private function create_ticketmeta_table() {
			global $wpdb;

			$sql = "CREATE TABLE $this->ticketmeta (
			  meta_id bigint(20) unsigned NOT NULL auto_increment,
			  support_ticket_id bigint(20) unsigned NOT NULL default '0',
			  meta_key varchar(255) default NULL,
			  meta_value longtext,
			  PRIMARY KEY  (meta_id),
			  KEY support_ticket_id (support_ticket_id),
			  KEY meta_key (meta_key)
			) $this->db_charset_collate;";

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

		public function upgrade_1981() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_faq_cats_table();
		}

		public function upgrade_198() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_tickets_cats_table();
			$this->update_faq_counts();
		}

		public function upgrade_196() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_tickets_messages_table();
		}

		/**
		 * Upgrades the Database to 1.8 version
		 * 
		 * @since 1.8
		 */
		public function upgrade_18() {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_tickets_messages_table();
			$this->create_tickets_table();

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
			$wpdb->query( "UPDATE $this->tickets_table SET view_by_superadmin = 1" );

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
		 * Upgrades the Database to 1.8.1 version
		 * 
		 * @since 1.8.1
		 */
		public function upgrade_181() {
			global $wpdb;
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$this->create_tickets_table();				
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
		 * Fills the FAQ categories table with default values
		 * 
		 * @since 1.8
		 */
		public function fill_faq_cats_default() {

			global $wpdb, $current_site;

			$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

		/**
		 * Returns all FAQ categories
		 * 
		 * @since 1.8
		 * 
		 * @return Array
		 */
		public function get_faq_categories() {

			global $wpdb, $current_site;

			$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

			$this->update_faq_counts();
		}




		private function update_faq_counts() {
			global $wpdb;

			$categories = $this->get_faq_categories();

			foreach ( $categories as $category ) {
				$cat_id = $category['cat_id'];
				$query = $wpdb->prepare( "SELECT COUNT(faq_id) FROM $this->faq_table WHERE cat_id = %d", $cat_id );
				$faq_count = $wpdb->get_var( $query );
				$wpdb->update(
					$this->faq_cats_table,
					array(
						'qcount' => $faq_count
					),
					array(
						'cat_id' => $cat_id
					),
					array( '%d' ),
					array( '%d' )
				);
			}
		}


	}


}


?>