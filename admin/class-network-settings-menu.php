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

		$this->render_tabs();

		$current_tab = $this->get_current_tab();

		$method = apply_filters( 'support_system_render_tab_function', array( $this, 'render_' . $current_tab . '_settings' ) );
		call_user_func( $method );
				
	}

	public function render_general_settings() {
		$settings = incsub_support_get_settings();

		$args = array(
			'name' => 'super_admin',
			'id' => 'super_admin',
			'show_empty' => false,
			'selected' => $settings['incsub_support_main_super_admin'],
			'echo' => false
		);
		$staff_dropdown = incsub_support_super_admins_dropdown( $args );
		
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
		include_once( 'views/network-settings-general.php' );
	}

	public function render_front_settings() {

		$settings = incsub_support_get_settings();

		$front_active = $settings['incsub_support_activate_front'];

		$blog_id = $settings['incsub_support_blog_id'];

		$support_pages_dropdown_args = array(
			'selected' => $settings['incsub_support_support_page'], 
			'show_option_none' => __( '-- Select a page --', INCSUB_SUPPORT_LANG_DOMAIN ),
			'name' => 'support_page_id',
			'echo' => false
		);



		$submit_ticket_pages_dropdown_args = array(
			'selected' => $settings['incsub_support_create_new_ticket_page'], 
			'show_option_none' => __( '-- Select a page --', INCSUB_SUPPORT_LANG_DOMAIN ),
			'name' => 'create_new_ticket_page_id',
			'echo' => false
		);


		$pages_dropdowns = false;
		if ( ! is_multisite() ) {
			$support_pages_dropdown = wp_dropdown_pages( $support_pages_dropdown_args );
			$submit_ticket_pages_dropdown = wp_dropdown_pages( $submit_ticket_pages_dropdown_args );

			$create_page_url = admin_url( 'post-new.php?post_type=page' );
			$view_page_url = get_permalink( $support_pages_dropdown_args['selected'] );

			$pages_dropdowns = true;
		}
		elseif ( is_multisite() ) {
			$blog_details = get_blog_details( $blog_id );

			if ( $blog_details && $blog_details->blog_id == $blog_id ) {
				switch_to_blog( $blog_id );
				$support_pages_dropdown = wp_dropdown_pages( $support_pages_dropdown_args );
				$submit_ticket_pages_dropdown = wp_dropdown_pages( $submit_ticket_pages_dropdown_args );

				$create_page_url = admin_url( 'post-new.php?post_type=page' );
				$view_page_url = get_permalink( $support_pages_dropdown_args['selected'] );
				restore_current_blog();	

				$pages_dropdowns = true;
			}
			else {
				$blog_id = '';
			}

		}

		if ( $pages_dropdowns ) {
			$support_pages_dropdown .= '<a href="' . esc_url( $create_page_url ) . '" target="_blank" class="button-primary support-create-page">' . esc_html__( 'Create new page', INCSUB_SUPPORT_LANG_DOMAIN ) . '</a>';
			$support_pages_dropdown .= '<a href="' . esc_url( $view_page_url ) . '" target="_blank" class="button-secondary support-view-page">' . esc_html__( 'View page', INCSUB_SUPPORT_LANG_DOMAIN ) . '</a>';
			$support_pages_dropdown .= '<br/><span class="description">' . __( 'Remember to insert <code>[support-system-tickets-index]</code> shortcode in this page', INCSUB_SUPPORT_LANG_DOMAIN ) . '</span>';
			$submit_ticket_pages_dropdown .= '<br/><span class="description">' . __( 'Remember to insert <code>[support-system-submit-ticket-form]</code> shortcode in this page', INCSUB_SUPPORT_LANG_DOMAIN ) . '</span>';
		}

		$errors = get_settings_errors( 'incsub-support-settings' );
		include_once( 'views/network-settings-front.php' );
	}

	public function render_submit_block() {
		$tab = $this->get_current_tab();
		?>
			<p class="submit">
				<?php wp_nonce_field( 'do-support-settings-' . $tab ); ?>
				<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-' . $tab, false ); ?>
			</p>
		<?php
	}

	public function on_load() {
		
		$current_tab = $this->get_current_tab();
		$tabs = $this->get_tabs();

		$validate_method = false;
		foreach ( $tabs as $tab => $name ) {
			if ( isset( $_POST[ 'submit-' . $tab ] ) ) {
				check_admin_referer( 'do-support-settings-' . $current_tab );
				$validate_method = apply_filters( 'support_system_settings_validate_function', array( $this, 'validate_' . $tab . '_settings' ) );
				$settings = call_user_func( $validate_method );

				if ( $settings && is_array( $settings ) ) {
					incsub_support_update_settings( $settings );
					if ( ! get_settings_errors( 'incsub-support-settings' ) ) {
						$redirect_to = add_query_arg( 'updated', 'true' );
						wp_redirect( $redirect_to );
						exit;
					}
				}
			}
					
		}
	}

	function validate_general_settings() {
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
		if ( isset( $input['tickets_role'] ) && is_array( $input['tickets_role'] ) ) {
			foreach ( $input['tickets_role'] as $ticket_role ) {
				if ( array_key_exists( $ticket_role, MU_Support_System::get_roles() ) )
					$settings['incsub_support_tickets_role'][] = $ticket_role;	
			}
		}


		if ( isset( $input['faqs_role'] ) && is_array( $input['faqs_role'] ) ) {
			foreach ( $input['faqs_role'] as $faq_role ) {
				if ( array_key_exists( $faq_role, MU_Support_System::get_roles() ) )
					$settings['incsub_support_faqs_role'][] = $faq_role;	
			}
		}

		return stripslashes_deep( $settings );
	}

	function validate_front_settings() {
		$input = $_POST;
		$settings = incsub_support_get_settings();

		// FRONT ACTIVE
		$is_active = $settings['incsub_support_activate_front'];
		if ( isset( $input['activate_front'] ) ) {
			$settings['incsub_support_activate_front'] = true;			
		}
		else {
			$settings['incsub_support_activate_front'] = false;	
			$settings['incsub_support_blog_id'] = false;
			$settings['incsub_support_support_page'] = 0;
			$settings['incsub_support_create_new_ticket_page'] = 0;
		}
		
		// BLOG ID
		$current_blog_id = $settings['incsub_support_blog_id'];
		if ( is_multisite() && isset( $input['support_blog_id'] ) && $settings['incsub_support_activate_front'] ) {
			if ( absint( $input['support_blog_id'] ) && get_blog_details( absint( $input['support_blog_id'] ) ) ) {
				$settings['incsub_support_blog_id'] = absint( $input['support_blog_id'] );
				if ( $current_blog_id != $settings['incsub_support_blog_id'] ) {
					// The blog ID has changed, let's reset the pages
					$settings['incsub_support_support_page'] = 0;
					$settings['incsub_support_create_new_ticket_page'] = 0;
				}
			}
			else {
				add_settings_error( 'incsub-support-settings', 'wrong_blog_id', __( 'The blog ID does not exist', INCSUB_SUPPORT_LANG_DOMAIN ) );
			}
		}

		// SUPPORT PAGES
		if ( ! empty( $input['support_page_id'] ) )
			$settings['incsub_support_support_page'] = absint( $input['support_page_id'] );
		else
			$settings['incsub_support_support_page'] = false;

		if ( ! empty( $input['create_new_ticket_page_id'] ) )
			$settings['incsub_support_create_new_ticket_page'] = absint( $input['create_new_ticket_page_id'] );
		else
			$settings['incsub_support_create_new_ticket_page'] = false;

		

		

		return $settings;
	}
		

	protected function render_tabs() {
		$updated = isset( $_GET['updated'] );
		$tabs = $this->get_tabs();
		$menu_slug = $this->slug;

		$menu_url = $this->get_menu_url();
		$current_tab = $this->get_current_tab();
		include( 'views/network-settings-tabs.php' );
	}

	protected function get_tabs() {
		return apply_filters( 'support_system_settings_tabs', array(
			'general' => __( 'General', INCSUB_SUPPORT_LANG_DOMAIN ),
			'front' => __( 'Front End', INCSUB_SUPPORT_LANG_DOMAIN )
		) );
	}

	protected function get_current_tab() {
		$tabs = $this->get_tabs();
		if ( empty( $_GET['tab'] ) )
			return key( $tabs );

		if ( ! in_array( $_GET['tab'], array_keys( $tabs ) ) )
			return key( $tabs );

		return $_GET['tab'];
	}

}