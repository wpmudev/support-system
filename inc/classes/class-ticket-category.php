<?php

class Incsub_Support_Ticket_Category {

	public $cat_id = 0;
	public $cat_name = '';
	private $defcat = false;
	public $user_id = 0;

	public static function get_instance( $cat_id ) {
		global $wpdb, $current_site;

		if ( is_object( $cat_id ) ) {
			$cat = new self( $cat_id );
			$cat = incsub_support_sanitize_ticket_category_fields( $cat );
			return $cat;
		}

		$cat_id = absint( $cat_id );
		if ( ! $cat_id )
			return false;
		
		$table = incsub_support()->model->tickets_cats_table;
		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$_cat = wp_cache_get( $cat_id, 'support_system_ticket_categories' );

		if ( ! $_cat ) {
			$_cat = $wpdb->get_row( 
				$wpdb->prepare(
					"SELECT *
					FROM $table 
					WHERE site_id = %d
					AND cat_id = %d
					LIMIT 1", 
					$current_site_id,
					$cat_id
				) 
			);

			if ( ! $_cat )
				return false;

			wp_cache_add( $_cat->cat_id, $_cat, 'support_system_ticket_categories' );
		}

		$_cat = new self( $_cat );

		$_cat = incsub_support_sanitize_ticket_category_fields( $_cat );

		return $_cat;

	}

	public function __construct( $cat ) {
		foreach ( get_object_vars( $cat ) as $key => $value )
			$this->$key = $value;
	}

	public function __get( $name ) {
		if ( $name === 'defcat' ) {
			return ( ! empty( $this->defcat ) ) ? true : false;
		}

		return false;
	}

	public function get_tickets_count() {
		global $wpdb;

		$table = incsub_support()->model->tickets_table;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( ticket_id ) FROM $table WHERE cat_id = %d", $this->cat_id ) );
	}
}