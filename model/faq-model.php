<?php

class MU_Support_System_Faq_Model {

	static $instance;

	public $faq_table;
	public $faq_cats_table;

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;
		
		$this->faq_table 				= $wpdb->base_prefix . 'support_faq';
		$this->faq_cats_table 			= $wpdb->base_prefix . 'support_faq_cats';

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

	public function get_faq_category( $cat_id ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

	public function update_faq_category( $id, $name ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		return $wpdb->update(
			$this->faq_cats_table,
			array( 'cat_name' => $name ),
			array( 'cat_id' => $id, 'site_id' => $current_site_id ),
			array( '%s' ),
			array( '%d' )
		);

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

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$where_clause = '';
		if ( isset( $args['category'] ) )
			$where_clause .= $wpdb->prepare( " AND q.cat_id = %d", $args['category'] );

		$results = $wpdb->get_results(
			"SELECT q.faq_id, q.cat_id, q.question, q.answer, c.cat_name, q.help_yes, q.help_no
			FROM $this->faq_table as q
			LEFT JOIN $this->faq_cats_table AS c 
			ON ( q.cat_id = c.cat_id )
			WHERE q.site_id = $current_site_id
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
			WHERE q.site_id = $current_site_id"
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
	public function get_faqs( $cat_id = false, $search = false ) {
		global $wpdb, $current_site;

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$where_clause[] = $wpdb->prepare( "q.site_id = %d", $current_site_id );
		if ( $cat_id )
			$where_clause[] = $wpdb->prepare( "q.cat_id = %d", $cat_id );

		if ( $search ) {
			$s = '%' . $search . '%';
			$where_clause[] = $wpdb->prepare( "( q.question LIKE %s OR q.answer LIKE %s )", $s, $s );
		}

		$where_clause = implode( ' AND ', $where_clause );

		
		$pq = "SELECT 
			q.faq_id, q.question, q.answer, q.help_count, q.help_yes, q.help_no, c.cat_name, c.cat_id, c.qcount
			FROM $this->faq_table AS q
			LEFT JOIN $this->faq_cats_table AS c 
			ON ( q.cat_id = c.cat_id )
			WHERE $where_clause
			ORDER BY c.cat_name ASC";

		return $wpdb->get_results( $pq, ARRAY_A );

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

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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
			$this->update_faq_counts();
			return $wpdb->insert_id;
		}
		
		return false;
		
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

			$this->update_faq_counts();

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

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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