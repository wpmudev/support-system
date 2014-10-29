<?php

class Incsub_Support_Ticket_Category {

	public $cat_id = 0;
	public $cat_name = '';
	public $defcat = false;
	public $user_id = 0;

	public static function get_instance( $cat_id ) {
		global $wpdb, $current_site;

		if ( is_object( $cat_id ) )
			return new self( $cat_id );

		$cat_id = absint( $cat_id );
		if ( ! $cat_id )
			return false;
		
		$table = incsub_support()->model->tickets_cats_table;
		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

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

		$_cat = new self( $_cat );

		return $_cat;

	}

	public function __construct( $cat ) {
		foreach ( get_object_vars( $cat ) as $key => $value )
			$this->$key = $value;
	}
}