<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Network_Support_settings' ) ) {

	class MU_Support_Network_Support_settings extends MU_Support_Menu {

		/**
		 * Status of the screen
		 */
		private $settings;
		private $imap_test = false;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct( $is_network = true, $capability = 'manage_network' ) {

			$this->page_title = __('Support System Settings', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Settings', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = $capability;
			$this->menu_slug = 'mu-support-settings';
			$this->parent = MU_Support_System::$network_main_menu->menu_slug;
			$this->submenu = true;

			parent::__construct( $is_network );

			$this->settings = MU_Support_System::$settings;

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );


		}

		public function enqueue_scripts( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_register_script( 'support-settings-js', INCSUB_SUPPORT_ASSETS_URL . 'js/settings.js', array(), '20130402' );
				wp_enqueue_script( 'support-settings-js' );
			}
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			if ( isset( $_POST['submit'] ) || isset( $_POST['test-imap-settings'] ) ) {
				$this->validate_form( $_POST );

				if ( $this->is_error() ) {
					$this->render_errors();
				}
				else {
					?>
						<div class="updated">
							<p><?php _e( 'Settings successfully saved', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
							<?php if( $this->imap_test ): ?>
								<p><?php _e( 'IMAP settings successfully tested.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
							<?php endif; ?>
						</div>
					<?php
				}

			}

			?>
				<form method="post" action="">
					<h3><?php _e( 'General Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
					<table class="form-table">
						<?php
							ob_start();
						    ?>
								<input type="text" class="regular-text" name="menu_name" value="<?php echo $this->settings['incsub_support_menu_name']; ?>">
								<span class="description"><?php _e("Change the text of the 'Support' menu item to anything you need.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
						    <?php
						    $this->render_row( __( 'Support menu name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

						    ob_start();
						    ?>
								<input type="text" class="regular-text" name="from_name" value="<?php echo $this->settings['incsub_support_from_name']; ?>">
								<span class="description"><?php _e("Support mail from name.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
						    <?php
						    $this->render_row( __( 'Support from name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

						    ob_start();
						    ?>
								<input type="text" class="regular-text" name="from_mail" value="<?php echo $this->settings['incsub_support_from_mail']; ?>">
								<span class="description"><?php _e("Support mail from address.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
						    <?php
						    $this->render_row( __( 'Support from e-mail', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

						    ob_start();
						    ?>
						    	<label for="faq_enabled">
									<input type="checkbox" name="faq_enabled" id="faq_enabled" <?php checked( $this->settings['incsub_support_faq_enabled'] ); ?>>
									<span class="description"><?php _e( "If unchecked, blogs users will not see the FAQ menu", INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
								</label>
						    <?php
						    $this->render_row( __( 'Enable FAQ menu', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

						    ob_start();
								$roles = MU_Support_System::get_roles();
							    ?>
							    	<p><label for="tickets_role">
							    		<select name="tickets_role" id="tickets_role">
							    			<?php foreach ( $roles as $key => $value ): ?>
							    				<option value="<?php echo $key; ?>" <?php selected( $this->settings['incsub_support_tickets_role'], $key ); ?>><?php echo $value; ?></option>
							    			<?php endforeach; ?>
							    		</select>
							    		<span class="description"><?php _e( 'Minimum User role that can open/see tickets.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
							    	</label></p>
							    	<p><label for="faqs_role">
							    		<select name="faqs_role" id="faqs_role">
							    			<?php foreach ( $roles as $key => $value ): ?>
							    				<option value="<?php echo $key; ?>" <?php selected( $this->settings['incsub_support_faqs_role'], $key ); ?>><?php echo $value; ?></option>
							    			<?php endforeach; ?>
							    		</select>
							    		<span class="description"><?php _e( 'Minimum User role that can see the FAQs.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
							    	</label></p>

							    <?php $this->render_row( __( 'Roles', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

							    ob_start();
							    ?>
							    	<select name="privacy" id="privacy">
							    		<?php foreach ( MU_Support_System::$privacy as $key => $value ): ?>
							    			<option value="<?php echo $key; ?>" <?php selected( $this->settings['incsub_ticket_privacy'], $key ); ?>><?php echo $value; ?></option>
							    		<?php endforeach; ?>
							    	</select>
							    <?php
							    $this->render_row( __( 'Privacy', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );

							    if ( is_plugin_active( 'pro-sites/pro-sites.php' ) ) {
								    ob_start();
								    ?>
								    	<p><label for="pro_sites">
								    		<input type="checkbox" id="pro_sites" name="pro_sites" <?php checked( $this->settings['incsub_allow_only_pro_sites'] ); ?>>
								    		<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support Tickets</strong> in a blog (if unchecked, Support will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
								    	</label></p>
								    	<p><label for="pro_sites_levels">
								    		<?php psts_levels_select( 'pro_sites_levels', $this->settings['incsub_pro_sites_level'] ); ?> 
								    		<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
								    	</label></p>

								    	<p><label for="pro_sites_faq">
								    		<input type="checkbox" id="pro_sites_faq" name="pro_sites_faq" <?php checked( $this->settings['incsub_allow_only_pro_sites_faq'] ); ?>>
								    		<span> <?php _e( 'Check and select a minimum Pro Site Level to allow <strong>Support FAQ</strong> in a blog (if unchecked, Support FAQ will be available for any blog)', INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
								    	</label></p>
								    	<p><label for="pro_sites_faq_levels">
								    		<?php psts_levels_select( 'pro_sites_faq_levels', $this->settings['incsub_pro_sites_faq_level'] ); ?> 
								    		<span class="description"><?php _e( 'Minimum Pro Site Level', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
								    	</label></p>
								    <?php
							    	$this->render_row( __( 'Pro Sites Integration', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );
								}
								?>
						</table>

						
					<p class="submit">
						<?php wp_nonce_field( 'do-support-settings' ); ?>
						<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
					</p>
				</form>
			<?php
		}

		private function validate_form( $input ) {

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'do-support-settings' ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			// MENU NAME
			if ( isset( $input['menu_name'] ) ) {
				$input['menu_name'] = sanitize_text_field( $input['menu_name'] );
				if ( empty( $input['menu_name'] ) )
					$this->add_error( 'menu-name', __( 'Menu name must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
				else
					$this->settings['incsub_support_menu_name'] = $input['menu_name'];
			}

			// FROM NAME
			if ( isset( $input['from_name'] ) ) {
				$input['from_name'] = sanitize_text_field( $input['from_name'] );
				if ( empty( $input['from_name'] ) )
					$this->add_error( 'site-name', __( 'Site name must not be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
				else
					$this->settings['incsub_support_from_name'] = $input['from_name'];
			}

			// FROM MAIL
			if ( isset( $input['from_mail'] ) ) {
				$input['from_mail'] = sanitize_email( $input['from_mail'] );
				if ( ! is_email( $input['from_mail'] ) ) {
					$this->add_error( 'site-mail', __( 'Email must be a valid email', INCSUB_SUPPORT_LANG_DOMAIN ) );
				}
				else
					$this->settings['incsub_support_from_mail'] = $input['from_mail'];
			}

			// PRIVACY
			if ( isset( $input['privacy'] ) && array_key_exists( $input['privacy'], MU_Support_System::$privacy ) ) {
				$this->settings['incsub_ticket_privacy'] = $input['privacy'];
			}

			
			// FETCH IMAP
			if ( isset( $input['fetch_imap'] ) && array_key_exists( $input['fetch_imap'], MU_Support_System::$fetch_imap ) ) {
				$this->settings['incsub_support_fetch_imap'] = $input['fetch_imap'];
			}
			
			// PRO SITES OPTION
			if ( isset( $input['pro_sites'] ) ) {
				$this->settings['incsub_allow_only_pro_sites'] = true;
				$this->settings['incsub_pro_sites_level'] = absint( $input['pro_sites_levels'] );
			}
			else {
				$this->settings['incsub_allow_only_pro_sites'] = false;
				$this->settings['incsub_pro_sites_level'] = '';
			}

			if ( isset( $input['pro_sites_faq'] ) ) {
				$this->settings['incsub_allow_only_pro_sites_faq'] = true;
				$this->settings['incsub_pro_sites_faq_level'] = absint( $input['pro_sites_faq_levels'] );
			}
			else {
				$this->settings['incsub_allow_only_pro_sites_faq'] = false;
				$this->settings['incsub_pro_sites_faq_level'] = '';
			}

			// FAQ MENU
			if ( isset( $input['faq_enabled'] ) )
				$this->settings['incsub_support_faq_enabled'] = true;
			else
				$this->settings['incsub_support_faq_enabled'] = false;

			// ROLES
			if ( isset( $input['tickets_role'] ) && array_key_exists( $input['tickets_role'], MU_Support_System::get_roles() ) )
				$this->settings['incsub_support_tickets_role'] = $input['tickets_role'];

			if ( isset( $input['faqs_role'] ) && array_key_exists( $input['faqs_role'], MU_Support_System::get_roles() ) )
				$this->settings['incsub_support_faqs_role'] = $input['faqs_role'];

			// Updating changes
			if ( ! $this->is_error() ) {
				update_site_option( 'incsub_support_settings', $this->settings );
			}

		}

	}

}