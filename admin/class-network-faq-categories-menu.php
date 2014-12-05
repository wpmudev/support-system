<?php

class Incsub_Support_Network_FAQ_Categories extends Incsub_Support_Admin_Menu {

	public function add_menu() {
		parent::add_submenu_page(
			'ticket-manager-b',
			__( 'FAQ Categories', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'FAQ Categories', INCSUB_SUPPORT_LANG_DOMAIN ), 
			'manage_network'
		);

		if ( isset( $_GET['action'] ) && isset( $_GET['category'] ) && 'edit' === $_GET['action'] ) {
			if ( $faq_category = incsub_support_get_faq_category( absint( $_GET['category'] ) ) )
				add_filter( 'support_system_admin_page_title', array( $this, 'set_edit_category_page_title' ) );
		}

	}

	public function set_edit_category_page_title( $title ) {
		$faq_category = incsub_support_get_faq_category( absint( $_GET['category'] ) );
		return '<h2>' . sprintf( _x( 'Edit %s', 'Edit faq category menu title', INCSUB_SUPPORT_LANG_DOMAIN ), $faq_category->cat_name ) . '</h2>';
	}

	public function on_load() {
		$edit = false;
		$add = false;

		if ( ( $edit = isset( $_POST['submit-edit-faq-category'] ) || $add = isset( $_POST['submit-new-faq-category'] ) ) && current_user_can( 'manage_network' ) ) {
			$edit = isset( $_POST['submit-edit-faq-category'] );
			$add = isset( $_POST['submit-new-faq-category'] );
			
			if ( $edit ) {
				if ( ! incsub_support_current_user_can( 'update_faq_category' ) )
					return;

				// Editing a category ?
				$faq_category_id = absint( $_POST['faq_cat_id'] );
				$faq_category = incsub_support_get_faq_category( $faq_category_id );
				if ( ! $faq_category )
					return;
				check_admin_referer( 'edit-faq-category-' . $faq_category->cat_id );
			}
			elseif ( $add ) {
				if ( ! incsub_support_current_user_can( 'insert_faq_category' ) )
					return;
				
				check_admin_referer( 'add-faq-category' );
			}
			else {
				return;
			}

			$cat_name = trim( $_POST['cat_name'] );
			if ( empty( $cat_name ) )
				add_settings_error( 'support_system_submit_category', 'empty-category-name', __( 'Category name must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			else
				$category_name = $_POST['cat_name'];

			if ( ! get_settings_errors( 'support_system_submit_category' ) ) {
				if ( $add ) {
					incsub_support_insert_faq_category( $category_name, $user_id );
					$redirect = add_query_arg( 'added', 'true', $this->get_menu_url() );
					wp_redirect( $redirect );
					exit();
				}
				elseif ( $edit ) {
					incsub_support_update_faq_category( $faq_category->cat_id, array( 'cat_name' => $category_name, 'user_id' => $user_id ) );
					$redirect = add_query_arg( 
						array( 
							'updated' => 'true',
							'action' => 'edit',
							'category' => $faq_category->cat_id 
						), $this->get_menu_url() 
					);
					wp_redirect( $redirect );
					exit();
				}
			}
		}
		
	}

	public function render_inner_page() {
		if ( isset( $_GET['category'] ) && $_GET['action'] == 'edit' ) {
			$faq_category = incsub_support_get_faq_category( absint( $_GET['category'] ) );
			if ( ! $faq_category )
				wp_die( __( 'The category does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$category_name = $faq_category->cat_name;
			if ( ! empty( $_POST['cat_name'] ) && trim( $_POST['cat_name'] ) )
				$category_name = stripslashes_deep( $_POST['cat_name'] );


			$updated = isset( $_GET['updated'] );

			settings_errors( 'support_system_submit_category' );
			include_once( 'views/edit-faq-category.php' );
		}
		else {
			include_once( 'inc/class-table-faq-categories.php' );
			$cats_table = new Incsub_Support_FAQ_Categories_Table();
			$cats_table->prepare_items();

			$category_name = '';
			if ( isset( $_POST['cat_name'] ) )
				$category_name = sanitize_text_field( stripslashes_deep( $_POST['cat_name'] ) );

			$added = isset( $_GET['added'] );

			settings_errors( 'support_system_submit_category' );
			include_once( 'views/network-faq-categories.php' );
		}
	}


}