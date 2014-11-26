<?php

class Incsub_Support_Network_Settings_Menu extends Incsub_Support_Admin_Menu {


	public function add_menu() {		
		parent::add_submenu_page(
			'ticket-manager-b',
			__( 'Settings', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'Support System Settings', INCSUB_SUPPORT_LANG_DOMAIN ), 
			'manage_network'
		);

	}


	public function render_inner_page() {
		$settings = incsub_support_get_settings();

		$args = array(
			'name' => 'super_admin',
			'id' => 'super_admin',
			'show_empty' => false,
			'selected' => $settings['incsub_support_main_super_admin'],
			'echo' => false
		);
		$staff_dropdown = incsub_support_super_admins_dropdown( $args );
		
		$pages_dropdown = wp_dropdown_pages( 
			array( 
				'selected' => $settings['incsub_support_support_page'], 
				'show_option_none' => __( '-- Select a page --', INCSUB_SUPPORT_LANG_DOMAIN ),
				'name' => 'support_page_id',
				'echo' => false
			) 
		);

		$blog_id = $settings['incsub_support_blog_id'];
		$menu_name = $settings['incsub_support_menu_name'];
		$from_name = $settings['incsub_support_from_name'];
		$from_email = $settings['incsub_support_from_mail'];
		$tickets_role = $settings['incsub_support_tickets_role'];
		$faqs_role = $settings['incsub_support_faqs_role'];
		$allow_only_pro_sites = $settings['incsub_allow_only_pro_sites'];
		$pro_sites_level = $settings['incsub_pro_sites_level'];
		$allow_only_pro_sites_faq = $settings['incsub_allow_only_pro_sites_faq'];
		$pro_sites_faq_level = $settings['incsub_pro_sites_faq_level'];
		$ticket_privacy = $settings['incsub_ticket_privacy'];
		$roles = MU_Support_System::get_roles();

		$errors = get_settings_errors( 'incsub-support-settings' );
		$updated = isset( $_GET['updated'] ) && empty( $errors ) ? true : false;
		include_once( 'views/network-settings.php' );
	}

	public function on_load() {
		// Are we submitting the form?
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'do-support-settings' );

			$input = $_POST;

			$settings = incsub_support_get_settings();

			// MENU NAME
			if ( isset( $input['menu_name'] ) ) {
				$input['menu_name'] = sanitize_text_field( $input['menu_name'] );
				if ( empty( $input['menu_name'] ) )
					add_settings_error( 'incsub-support-settings', 'menu-name', __( 'Menu name must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
				else
					$settings['incsub_support_menu_name'] = $input['menu_name'];
			}

			// FROM NAME
			if ( isset( $input['from_name'] ) ) {
				$input['from_name'] = sanitize_text_field( $input['from_name'] );
				if ( empty( $input['from_name'] ) )
					add_settings_error( 'incsub-support-settings', 'site-name', __( 'Site name must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
				else
					$settings['incsub_support_from_name'] = $input['from_name'];
			}

			// FROM MAIL
			if ( isset( $input['from_mail'] ) ) {
				$input['from_mail'] = sanitize_email( $input['from_mail'] );
				if ( ! is_email( $input['from_mail'] ) ) {
					add_settings_error( 'incsub-support-settings', 'site-mail', __( 'Email must be a valid email', INCSUB_SUPPORT_LANG_DOMAIN ) );
				}
				else
					$settings['incsub_support_from_mail'] = $input['from_mail'];
			}

			// MAIN SUPER ADMIN
			if ( isset( $input['super_admin'] ) )
				$settings['incsub_support_main_super_admin'] = absint( $input['super_admin'] );

			// PRIVACY
			if ( isset( $input['privacy'] ) && array_key_exists( $input['privacy'], MU_Support_System::$privacy ) ) {
				$settings['incsub_ticket_privacy'] = $input['privacy'];
			}

			// SUPPORT PAGE
			if ( ! empty( $input['support_page_id'] ) && 'page' === get_post_type( $input['support_page_id'] ) )
				$settings['incsub_support_support_page'] = absint( $input['support_page_id'] );

			
			// FETCH IMAP
			if ( isset( $input['fetch_imap'] ) && array_key_exists( $input['fetch_imap'], MU_Support_System::$fetch_imap ) ) {
				$settings['incsub_support_fetch_imap'] = $input['fetch_imap'];
			}
			
			// PRO SITES OPTION
			if ( isset( $input['pro_sites'] ) ) {
				$settings['incsub_allow_only_pro_sites'] = true;
				$settings['incsub_pro_sites_level'] = absint( $input['pro_sites_levels'] );
			}
			else {
				$settings['incsub_allow_only_pro_sites'] = false;
				$settings['incsub_pro_sites_level'] = '';
			}

			if ( isset( $input['pro_sites_faq'] ) ) {
				$settings['incsub_allow_only_pro_sites_faq'] = true;
				$settings['incsub_pro_sites_faq_level'] = absint( $input['pro_sites_faq_levels'] );
			}
			else {
				$settings['incsub_allow_only_pro_sites_faq'] = false;
				$settings['incsub_pro_sites_faq_level'] = '';
			}

			// ROLES
			$settings['incsub_support_tickets_role'] = array();
			if ( isset( $input['tickets_role'] ) && is_array( $input['tickets_role'] ) ) {
				foreach ( $input['tickets_role'] as $ticket_role ) {
					if ( array_key_exists( $ticket_role, MU_Support_System::get_roles() ) )
						$settings['incsub_support_tickets_role'][] = $ticket_role;	
				}
			}


			$settings['incsub_support_faqs_role'] = array();
			if ( isset( $input['faqs_role'] ) && is_array( $input['faqs_role'] ) ) {
				foreach ( $input['faqs_role'] as $faq_role ) {
					if ( array_key_exists( $faq_role, MU_Support_System::get_roles() ) )
						$settings['incsub_support_faqs_role'][] = $faq_role;	
				}
			}

			// BLOG ID
			if ( is_multisite() ) {
				if ( isset( $input['support_blog_id'] ) && absint( $input['support_blog_id'] ) && get_blog_details( absint( $input['support_blog_id'] ) ) ) {
					$settings['incsub_support_blog_id'] = absint( $input['support_blog_id'] );
				}
				else {
					add_settings_error( 'incsub-support-settings', 'wrong_blog_id', __( 'The blog ID does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );
				}
			}

			incsub_support_update_settings( $settings );

			if ( ! get_settings_errors( 'incsub-support-settings' ) ) {
				$redirect_to = add_query_arg( 'updated', 'true' );
				wp_redirect( $redirect_to );
				exit;
			}

		}
	}

}