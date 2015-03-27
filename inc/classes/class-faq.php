<?php


class Incsub_Support_FAQ {

	public $faq_id = 0;

	public $site_id = 0;

	public $cat_id = 0;

	public $question = '';

	public $answer = '';

	public $help_views = 0;

	public $help_count = 0;

	public $help_yes = 0;

	public $help_no = 0;

	public $category = null;

	public static function get_instance( $faq_id ) {
		global $wpdb, $current_site;

		if ( is_object( $faq_id ) ) {
			$_faq = new self( $faq_id );
			$_faq = incsub_support_sanitize_faq_fields( $_faq );
			return $_faq;
		}

		$faq_id = absint( $faq_id );
		if ( ! $faq_id )
			return false;
		
		$faq_table = incsub_support()->model->faq_table;

		$_faq = wp_cache_get( $faq_id, 'support_system_faqs' );
		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		if ( ! $_faq ) {
			$_faq = $wpdb->get_row( 
				$wpdb->prepare( 
					"SELECT * FROM $faq_table
					WHERE faq_id = %d
					AND site_id = %d
					LIMIT 1",
					$faq_id,
					$current_site_id
				)
			);	

			if ( ! $_faq )
				return false;

			wp_cache_add( $_faq->faq_id, $_faq, 'support_system_faqs' );
		}

		if ( ! $_faq )
			return false;

		$_faq = new self( $_faq );

		$_faq = incsub_support_sanitize_faq_fields( $_faq );

		return $_faq;

	}



	public function __construct( $faq ) {
		foreach ( get_object_vars( $faq ) as $key => $value ) {			
			$this->$key = $value;
		}

		if ( $this->cat_id )
			$this->category = incsub_support_get_faq_category( $this->cat_id );
	}

	public function get_category_name() {
		if ( ! is_object( $this->category ) )
			return false;

		return $this->category->cat_name;
	}

}