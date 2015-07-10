<?php

/**
 * Pro Sites integration
 */

class Support_System_Pro_Sites_Integration {

	public function __construct() {

		if ( ! class_exists( 'ProSites' ) )
			return;

		add_filter( 'support_system_default_settings', array( $this, 'add_pro_sites_default_settings' ) );
		add_action( 'support_sytem_general_settings', array( $this, 'add_pro_sites_general_settings_fields' ) );
		add_action( 'support_sytem_front_settings', array( $this, 'add_pro_sites_front_settings_fields' ) );
		add_filter( 'support_system_validate_general_settings', array( $this, 'validate_general_settings' ) );
		add_filter( 'support_system_validate_front_settings', array( $this, 'validate_front_settings' ) );
		add_filter( 'support_system_user_can', array( $this, 'set_capabilities' ), 10, 3 );
		
		add_filter( 'support_system_not_allowed_tickets_list_message', array( $this, 'maybe_add_link_to_admin' ), 10, 2 );
		add_filter( 'support_system_not_allowed_submit_ticket_form_message', array( $this, 'maybe_add_link_to_admin' ), 10, 2 );
		add_filter( 'support_system_not_allowed_faqs_list_message', array( $this, 'maybe_add_link_to_admin' ), 10, 2 );
	}

	/**
	 * Add a link to the frontend message when the user cannot use support in frontend
	 * 
	 * Sometimes, a user cannot use support in the front but in the admin side. This function
	 * will add a link to the support system
	 * 
	 * @param  String $message Current message
	 * @return String          New message
	 */
	public function maybe_add_link_to_admin( $message, $type ) {
		if ( ! is_user_logged_in() )
			return $message;

		$user_id = get_current_user_id();

		$settings = incsub_support_get_settings();
		if ( is_multisite() && $settings['incsub_allow_only_pro_sites'] ) {
			$pro_blog_id = false;

			// Let's check if its main blog is a pro site
			$main_blog = get_active_blog_for_user( $user_id );
			if ( $main_blog && is_pro_site( $main_blog->blog_id, $settings['incsub_pro_sites_level'] ) ) {
				$pro_blog_id = $main_blog->blog_id;
			}
			else {
				$user_blogs = get_blogs_of_user( $user_id );
				foreach ( $user_blogs as $blog ) {

					if ( is_pro_site( $blog->userblog_id, $settings['incsub_pro_sites_level']  ) ) {
						$pro_blog_id = $blog->userblog_id;
						break;					
					}
				}
			}

			if ( $pro_blog_id ) {
				$admin_url = add_query_arg( 'page', 'ticket-manager', get_admin_url( $pro_blog_id, 'admin.php' ) );
				$message = sprintf( __( 'Need support? <a href="%s" title="%s">Click here to go to your dashboard</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $admin_url, esc_attr( __( 'Go to support in your site dashboard', INCSUB_SUPPORT_LANG_DOMAIN ) ) );
			}

			
		}
		elseif ( is_multisite() && ! $settings['incsub_allow_only_pro_sites'] ) {

			$blog_id = false;

			$main_blog = get_active_blog_for_user( $user_id );
			if ( $main_blog ) {
				$blog_id = $main_blog->blog_id;
			}
			else {
				$user_blogs = get_blogs_of_user( $user_id );
				if ( $user_blogs ) {
					$blog = key( $user_blogs );
					$blog_id = $blog->userblog_id;
				}
			}

			if ( $blog_id ) {
				if ( $type == 'faq-list' )
					$admin_url = add_query_arg( 'page', 'support-faq', get_admin_url( $blog_id, 'admin.php' ) );
				else	
					$admin_url = add_query_arg( 'page', 'ticket-manager', get_admin_url( $blog_id, 'admin.php' ) );

				$message = sprintf( __( 'Need support? <a href="%s" title="%s">Click here to go to your dashboard</a>', INCSUB_SUPPORT_LANG_DOMAIN ), $admin_url, esc_attr( __( 'Go to support in your site dashboard', INCSUB_SUPPORT_LANG_DOMAIN ) ) );
			}
			
		}

		return $message;
	}

	public function add_pro_sites_general_settings_fields() {
		$settings = incsub_support_get_settings();

		$allow_only_pro_sites = $settings['incsub_allow_only_pro_sites'];
		$pro_sites_level = $settings['incsub_pro_sites_level'];
		$allow_only_pro_sites_faq = $settings['incsub_allow_only_pro_sites_faq'];
		$pro_sites_faq_level = $settings['incsub_pro_sites_faq_level'];
		$allow_only_pro_users_tickets = $settings['incsub_allow_only_pro_users_tickets'];
		$allow_only_pro_users_faq = $settings['incsub_allow_only_pro_users_faq'];

		?>

			<h3><?php _e( 'Pro Sites integration', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
			<p><?php _e( 'Set a Pro Site level for Admin Menus. This options will not affect the Frontend.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			<table class="form-table">

				<tr valign="top">
					<th scope="row"><?php _e( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></th>
					<td>
				    	<p>
				    		<label for="pro_sites">
					    		<input type="checkbox" id="pro_sites" name="pro_sites" <?php checked( $allow_only_pro_sites ); ?>>
					    		<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support Tickets</strong> in a blog (if unchecked, Support will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
				    		</label>
				    	</p>
				    	<p>
				    		<label for="pro_sites_levels">
				    			<?php psts_levels_select( 'pro_sites_levels', $pro_sites_level ); ?> 
				    			<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
				    		</label>
				    	</p>

				    </td>
				</tr>
				<tr valign="top">
				    <th scope="row"><?php _e( 'FAQs', INCSUB_SUPPORT_LANG_DOMAIN ); ?></th>
				    <td>
				    	<p>
				    		<label for="pro_sites_faq">
				    			<input type="checkbox" id="pro_sites_faq" name="pro_sites_faq" <?php checked( $allow_only_pro_sites_faq ); ?>>
				    			<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support FAQ</strong> in a blog (if unchecked, Support FAQ will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
				    		</label>
				    	</p>
				    	<p>
				    		<label for="pro_sites_faq_levels">
				    			<?php psts_levels_select( 'pro_sites_faq_levels', $pro_sites_faq_level ); ?> 
				    			<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
				    		</label>
				    	</p>
				    </td>
		    	</tr>
		    </table>
	    <?php
	}

	public function add_pro_sites_front_settings_fields() {
		$settings = incsub_support_get_settings();

		$pro_users_level = $settings['incsub_pro_users_level'];
		$pro_users_faqs_level = $settings['incsub_pro_users_faqs_level'];
		$allow_only_pro_users_tickets = $settings['incsub_allow_only_pro_users_tickets'];
		$allow_only_pro_users_faqs = $settings['incsub_allow_only_pro_users_faqs'];

		?>

			<h3><?php _e( 'Pro Users integration', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
			<p><?php _e( 'Set a Pro Level for users. A user is pro if it has at least one Pro Site with the specified level.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			<table class="form-table">

		    	<tr valign="top">
					<th scope="row"><?php _e( 'Tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></th>
				    <td>
				    	<p>
				    		<label for="pro_users_tickets">
				    			<input type="checkbox" id="pro_users_tickets" name="pro_users_tickets" <?php checked( $allow_only_pro_users_tickets ); ?>>
				    			<span> <?php _e( 'Allow <strong>Support Tickets</strong> only for Pro Users', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
				    		</label>
				    	</p>
				    	<p>
				    		<label for="pro_users_levels">
				    			<?php psts_levels_select( 'pro_users_levels', $pro_users_level ); ?> 
				    			<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
				    		</label>
				    	</p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'FAQs', INCSUB_SUPPORT_LANG_DOMAIN ); ?></th>
				    <td>
				    	<p>
				    		<label for="pro_users_faqs">
				    			<input type="checkbox" id="pro_users_faqs" name="pro_users_faqs" <?php checked( $allow_only_pro_users_faqs ); ?>>
				    			<span> <?php _e( 'Allow <strong>Support FAQs</strong> only for Pro Users', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
				    		</label>
				    	</p>
				    	<p>
				    		<label for="pro_users_faq_levels">
				    			<?php psts_levels_select( 'pro_users_faqs_levels', $pro_users_faqs_level ); ?> 
				    			<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
				    		</label>
				    	</p>
					</td>
				</tr>
				
		    </table>
	    <?php
	}

	public function add_pro_sites_default_settings( $defaults ) {
		$pro_sites_defaults = array(
			'incsub_allow_only_pro_sites' => false,
			'incsub_pro_sites_level' => '',
			'incsub_allow_only_pro_sites_faq' => false,
			'incsub_pro_sites_faq_level' => '',
			'incsub_allow_only_pro_users_tickets' => false,
			'incsub_allow_only_pro_users_faqs' => false,
			'incsub_pro_users_level' => '',
			'incsub_pro_users_faqs_level' => ''
		);

		return array_merge( $defaults, $pro_sites_defaults );
	}

	public function validate_general_settings( $settings ) {
		$input = $_POST;

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

		return $settings;
	}

	public function validate_front_settings( $settings ) {
		$input = $_POST;

		if ( isset( $input['pro_users_tickets'] ) ) {
			$settings['incsub_allow_only_pro_users_tickets'] = true;
			$settings['incsub_pro_users_level'] = absint( $input['pro_users_levels'] );
		}
		else {
			$settings['incsub_allow_only_pro_users_tickets'] = false;
			$settings['incsub_pro_users_level'] = '';
		}

		if ( isset( $input['pro_users_faqs'] ) ) {
			$settings['incsub_allow_only_pro_users_faqs'] = true;
			$settings['incsub_pro_users_faqs_level'] = absint( $input['pro_users_faqs_levels'] );
		}
		else {
			$settings['incsub_allow_only_pro_users_faqs'] = false;
			$settings['incsub_pro_users_faqs_level'] = '';
		}


		return $settings;
	}

	public function set_capabilities( $user_can, $user_id, $cap ) {

		$tickets_caps = array( 'read_ticket', 'insert_ticket', 'insert_reply' );
		$faqs_caps = array( 'read_faq' );

		$settings = incsub_support_get_settings();
		if ( 
			is_multisite()
			&& is_admin()
			&& ! is_network_admin()
			&& $settings['incsub_allow_only_pro_sites'] 
			&& in_array( $cap, $tickets_caps )
			&& ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_level'] ) ) 
		) {
			return false;
		}
		
		if ( 
			is_multisite()
			&& is_admin()
			&& ! is_network_admin()
			&& $settings['incsub_allow_only_pro_sites_faq'] 
			&& in_array( $cap, $faqs_caps )
			&& ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_faq_level'] ) ) 
		) {
			return false;
		}

		if ( 
			is_multisite() 
			&& ! is_admin()
			&& $settings['incsub_allow_only_pro_users_tickets'] 
			&& in_array( $cap, $tickets_caps )
		) {
			return $this->_is_pro_user( $user_id, $settings['incsub_pro_users_level'] );
		}

		if ( 
			is_multisite() 
			&& ! is_admin()
			&& $settings['incsub_allow_only_pro_users_faqs'] 
			&& in_array( $cap, $faqs_caps )
		) {
			return $this->_is_pro_user( $user_id, $settings['incsub_pro_users_faqs_level'] );
		}

		return $user_can;
	}

	private function _is_pro_user( $user_id, $level ) {
		if ( is_super_admin( $user_id ) )
			return true;

		$user_blogs = get_blogs_of_user( $user_id );
		
		$cache = wp_cache_get( 'pro_user_' . $user_id, 'support_system_pro_user' );

		if ( is_array( $cache ) )
			return $cache['is_pro_user'];

		$is_pro_user = false;
		foreach ( $user_blogs as $blog ) {

			if ( is_pro_site( $blog->userblog_id, $level ) ) {
				switch_to_blog( $blog->userblog_id );
				if ( user_can( $user_id, 'manage_options' ) )
					$is_pro_user = true;
				restore_current_blog();

				if ( $is_pro_user )
					break;
			}
		}

		wp_cache_set( 'pro_user_' . $user_id, array( 'user_id' => $user_id, 'is_pro_user' => $is_pro_user ), 'support_system_pro_user' );
		return $is_pro_user;

	}
}

add_action( 'plugins_loaded', 'support_system_init_pro_sites_integration', 100 );
function support_system_init_pro_sites_integration() {
	if ( class_exists( 'ProSites' ) ) {
		incsub_support_add_integrator( 'Support_System_Pro_Sites_Integration' );
	}
}