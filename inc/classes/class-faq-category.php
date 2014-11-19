<?php

class Incsub_Support_faq_Category {

	public $faq_id = 0;
	
	public $cat_name = '';
	
	private $defcat = false;
	
	public $user_id = 0;
	

	public static function get_instance( $faq_id ) {
		global $wpdb, $current_site;

		if ( is_object( $faq_id ) ) {
			$cat = new self( $faq_id );
			$cat = incsub_support_sanitize_faq_category_fields( $cat );
			return $cat;
		}

		$faq_id = absint( $faq_id );
		if ( ! $faq_id )
			return false;
		
		$table = incsub_support()->model->faq_cats_table;
		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$_cat = wp_cache_get( $faq_id, 'support_system_faq_categories' );

		if ( ! $_cat ) {
			$_cat = $wpdb->get_row( 
				$wpdb->prepare(
					"SELECT *
					FROM $table 
					WHERE faq_id = %d
					LIMIT 1", 
					$faq_id
				) 
			);

			if ( ! $_cat )
				return false;

			wp_cache_add( $_cat->faq_id, $_cat, 'support_system_faq_categories' );
		}

		$_cat = new self( $_cat );

		$_cat = incsub_support_sanitize_faq_category_fields( $_cat );

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

	public function get_faqs_count() {
		global $wpdb;

		$table = incsub_support()->model->faqs_table;

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( faq_id ) FROM $table WHERE faq_id = %d", $this->faq_id ) );
	}
}