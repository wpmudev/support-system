<?php

/**
 * Pro Sites integration
 */

class Support_System_Pro_Sites_Integration {

	public function __construct() {
		add_filter( 'support_system_default_settings', array( $this, 'add_pro_sites_default_settings' ) );
		add_action( 'support_sytem_general_settings', array( $this, 'add_pro_sites_settings_fields' ) );
		add_filter( 'support_system_validate_general_settings', array( $this, 'validate_settings' ) );
		add_filter( 'incsub_support_menus', array( $this, 'set_admin_menus' ) );
		add_filter( 'support_system_user_can', array( $this, 'set_capabilities' ), 10, 2 );
	}

	public function add_pro_sites_settings_fields() {
		$settings = incsub_support_get_settings();

		$allow_only_pro_sites = $settings['incsub_allow_only_pro_sites'];
		$pro_sites_level = $settings['incsub_pro_sites_level'];
		$allow_only_pro_sites_faq = $settings['incsub_allow_only_pro_sites_faq'];
		$pro_sites_faq_level = $settings['incsub_pro_sites_faq_level'];
		$allow_only_pro_users_tickets = $settings['incsub_allow_only_pro_users_tickets'];
		$allow_only_pro_users_faq = $settings['incsub_allow_only_pro_users_faq'];

		?>

			<h3><?php _e( 'Pro Sites integration', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
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
		    	<tr valign="top">
				    <th scope="row"><?php _e( 'Pro Users', INCSUB_SUPPORT_LANG_DOMAIN ); ?></th>
				    <td>
				    	<p>
				    		<label for="pro_users_tickets">
				    			<input type="checkbox" id="pro_users_tickets" name="pro_users_tickets" <?php checked( $allow_only_pro_users_tickets ); ?>>
				    			<span> <?php _e( 'Allow <strong>Support Tickets</strong> only for Pro Users', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
				    		</label>
				    	</p>
				    	<p>
				    		<label for="pro_users_faq">
				    			<input type="checkbox" id="pro_users_faq" name="pro_users_faq" <?php checked( $allow_only_pro_users_faq ); ?>>
				    			<span> <?php _e( 'Allow <strong>Support FAQ</strong> only for Pro Users', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
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
			'incsub_allow_only_pro_users_faq' => false,
			'incsub_allow_only_pro_users_tickets' => false,
		);

		return array_merge( $defaults, $pro_sites_defaults );
	}

	public function validate_settings( $settings ) {
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

		if ( isset( $input['pro_users_faq'] ) ) {
			$settings['incsub_allow_only_pro_users_faq'] = true;
		}
		else {
			$settings['incsub_allow_only_pro_users_faq'] = false;
		}

		if ( isset( $input['pro_users_tickets'] ) ) {
			$settings['incsub_allow_only_pro_users_tickets'] = true;
		}
		else {
			$settings['incsub_allow_only_pro_users_tickets'] = false;
		}

		return $settings;
	}

	public function set_admin_menus( $menus ) {
		if ( is_multisite() && ! is_network_admin() && is_admin() ) {
			$settings = incsub_support_get_settings();
			if ( $settings['incsub_allow_only_pro_sites'] ) {
				if ( ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_level'] ) ) ) {
					unset( $menus['admin_support_menu'] );
				}
			}

			if ( $settings['incsub_allow_only_pro_sites_faq'] ) {
				if ( ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_faq_level'] ) ) ) {
					unset( $menus['admin_faq_menu'] );
				}
			}

			if ( $settings['incsub_allow_only_pro_users_tickets'] && ! is_pro_user( get_current_user_id() ) )
				unset( $menus['admin_support_menu'] );

			if ( $settings['incsub_allow_only_pro_users_faq'] && ! is_pro_user( get_current_user_id() ) )
				unset( $menus['admin_faq_menu'] );		
		}

		return $menus;
	}

	public function set_capabilities( $user_can, $user_id ) {
		$settings = incsub_support_get_settings();
		if ( $settings['incsub_allow_only_pro_sites'] && ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_level'] ) ) ) {
			return false;
		}
		
		if ( $settings['incsub_allow_only_pro_sites_faq'] && ! is_pro_site( get_current_blog_id(), absint( $settings['incsub_pro_sites_faq_level'] ) ) ) {
			return false;
		}

		if ( $settings['incsub_allow_only_pro_users_tickets'] && ! is_pro_user( get_current_user_id() ) )
			return false;

		if ( $settings['incsub_allow_only_pro_users_faq'] && ! is_pro_user( get_current_user_id() ) )
			return false;		

		return $user_can;
	}
}

add_action( 'plugins_loaded', 'support_system_init_pro_sites_integration' );
function support_system_init_pro_sites_integration() {
	if ( class_exists( 'ProSites' ) ) {
		new Support_System_Pro_Sites_Integration();
	}
}