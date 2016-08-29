<?php

class Incsub_Support_Admin_FAQ_Menu extends Incsub_Support_Admin_Menu {
	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
	}

	public function add_menu() {

		$menu_title = __( 'FAQ', INCSUB_SUPPORT_LANG_DOMAIN );
		$page_title = __( 'Frequently Asked Questions', INCSUB_SUPPORT_LANG_DOMAIN );
		
		/**
		 * Filters the FAQ menu position
		 * 
		 * In some cases, tickets menu is not displayed so FAQ menu needs
		 * to be a parent menu instead of a submenu
		 * 
		 * @param Boolean $value If set to true, FAQ menu will be a sunmenu. Otherwise, it will be a parent menu
		 */
		if ( apply_filters( 'support_system_add_faq_menu_as_submenu', true ) ) {
			parent::add_submenu_page(
				'ticket-manager',
				$menu_title,
				$page_title, 
				'read'
			);
		}
		else {
			parent::add_menu_page(
				$page_title, 
				$menu_title,
				'read',
				'dashicons-sos'
			);
		}

		add_action( 'load-' . $this->page_id, array( $this, 'set_filters' ) );

	}

	public function set_filters() {
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );
	}

	public function enqueue_scripts( $hook ) {
		incsub_support_enqueue_main_script();
	}

	public function enqueue_styles( $hook ) {
		wp_enqueue_style( 'mu-support-faq-css', INCSUB_SUPPORT_PLUGIN_URL . 'admin/assets/css/support-admin-faqs-menu.css', array( ), '20130402' );
	}


	public function render_inner_page() {
		$faq_categories = incsub_support_get_faq_categories();

		if ( isset( $_POST['submit-faq-search'] ) && check_admin_referer( 'faq_search' ) ) {
			$new_faq_categories = array();
			foreach ( $faq_categories as $key => $item ) {
				$answers = incsub_support_get_faqs( array( 's' => $_POST['faq-s'], 'per_page' => -1, 'category' => $item->cat_id ) );
				if ( count( $answers ) > 0 ) {
					$the_faq = $item;
	            	$the_faq->answers = $answers;
	            	$the_faq->faqs = count( $answers );
	            	$new_faq_categories[] = $the_faq;
	            }
	        }

	        $index = 0;
	        $faq_categories = $new_faq_categories;
		}
		else {
	    	foreach ( $faq_categories as $key => $item ) {
	            $faq_categories[ $key ]->faqs = incsub_support_count_faqs_on_category( $item->cat_id );
	            $faq_categories[ $key ]->answers = incsub_support_get_faqs( array( 'category' => $item->cat_id ) );
	        }
	    }		    

        $half_of_array = ceil( count( $faq_categories ) / 2 );

        include_once( 'views/admin-faq.php' );

	}

	public function embed_media( $match ) {
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$wp_oembed = _wp_oembed_get_object();

		$embed_code = $wp_oembed->get_html( $match[1] );
		return $embed_code;
	}
}