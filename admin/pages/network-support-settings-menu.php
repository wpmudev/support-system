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
		public function __construct() {

			$this->page_title = __('Support System Options', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Support Options', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = 'manage_network';
			$this->menu_slug = 'mu-support-settings';
			$this->parent = 'settings.php';
			$this->submenu = true;

			parent::__construct();

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

						<h3><?php _e( 'IMAP Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>		
						<table class="form-table">
							<?php
							ob_start();
							?>
								<select name="fetch_imap" id="fetch_imap" <?php disabled( ! function_exists( 'imap_open' ) ); ?>>
									<?php foreach ( MU_Support_System::$fetch_imap as $key => $value ): ?>
										<option value="<?php echo $key; ?>" <?php selected( $this->settings['incsub_support_fetch_imap'], $key ); ?>><?php echo $value; ?></option>
									<?php endforeach; ?>
								</select>
								<span class="description"><?php _e("Enable or disable fetching responses to tickets via IMAP", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
							<?php 
							$this->render_row( __( 'Fetch responses via IMAP', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							$disabled = ! function_exists( 'imap_open' ) || $this->settings['incsub_support_fetch_imap'] == 'disabled';
							ob_start();
							?>
								<select class="imap-settings" <?php disabled( $disabled ); ?> name="imap_frequency" id="imap_frequency">
									<option value="" <?php selected( $this->settings['incsub_support_imap_frequency'], '' ); ?>></option>
									<?php foreach ( wp_get_schedules() as $recurrence => $schedule ): ?>
										<option value="<?php echo $recurrence; ?>" <?php selected( $this->settings['incsub_support_imap_frequency'], $recurrence ); ?>><?php echo $schedule['display']; ?></option>
									<?php endforeach; ?>
								</select><br/>
								<span class="description"><?php _e( 'Enable or disable fetching responses to tickets via IMAP', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
							<?php $this->render_row( __( 'Fetch responses via IMAP', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<input class="imap-settings" <?php disabled( $disabled ); ?> type="text" id="incsub_support_imap_host" name="incsub_support_imap[host]" value="<?php echo $this->settings['incsub_support_imap']['host']; ?>" size="40" />
							<?php $this->render_row( __( 'IMAP server host', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<input class="imap-settings" <?php disabled( $disabled ); ?> type="text" id="incsub_support_imap_port" name="incsub_support_imap[port]" value="<?php echo $this->settings['incsub_support_imap']['port']; ?>" size="4" />
							<?php $this->render_row( __( 'IMAP server port', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<select class="imap-settings" <?php disabled( $disabled ); ?> name="incsub_support_imap[ssl]" id="incsub_support_imap_ssl">
									<?php foreach ( MU_Support_System::$incsub_support_imap_ssl as $key => $value ): ?>
										<option value="<?php echo $key; ?>" <?php selected( $this->settings['incsub_support_imap']['ssl'], $key ); ?>><?php echo $value; ?></option>
									<?php endforeach; ?>
								</select>
							<?php $this->render_row( __( 'SSL', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<input class="imap-settings" <?php disabled( $disabled ); ?> type="text" id="incsub_support_imap_mailbox" name="incsub_support_imap[mailbox]" value="<?php echo $this->settings['incsub_support_imap']['mailbox']; ?>" size="40" />
							<?php $this->render_row( __( 'IMAP mailbox', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<input class="imap-settings" <?php disabled( $disabled ); ?> type="text" id="incsub_support_imap_username" name="incsub_support_imap[username]" value="<?php echo $this->settings['incsub_support_imap']['username']; ?>" size="40" />
							<?php $this->render_row( __( 'IMAP username', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); 

							ob_start();
							?>
								<input class="imap-settings" <?php disabled( $disabled ); ?> type="password" id="incsub_support_imap_password" name="incsub_support_imap[password]" value="<?php echo $this->settings['incsub_support_imap']['password']; ?>" size="40" />
							<?php $this->render_row( __( 'IMAP password', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
							</div>
					</table>
					<p class="submit">
						<?php wp_nonce_field( 'do-support-settings' ); ?>
						<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit', false ); ?>
						<input type="submit" class="button-secondary" <?php echo (function_exists('imap_open'))?'':'disabled="disabled"'; ?> value="<?php _e( 'Test IMAP settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"></input>
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

			// IMAP SETTINGS
			if ( 'enabled' == $this->settings['incsub_support_fetch_imap'] ) {

				if ( isset( $input['imap_frequency'] ) && $input['imap_frequency'] != $this->settings['incsub_support_imap_frequency'] ) {
					if ( wp_reschedule_event( 0, $input['imap_frequency'], 'incsub_support_fetch_imap' ) === false ) {
						wp_schedule_event( 0, $input['imap_frequency'], 'incsub_support_fetch_imap' );
					}
					$this->settings['incsub_support_imap_frequency'] = $input['imap_frequency'];
				}

				$this->settings['incsub_support_imap'] = $input['incsub_support_imap'];
			
				if ( isset( $input['test-imap-settings'] ) ) {
					if ( $this->incsub_support_fetch_imap() )
						$this->imap_test = true;
					else
						$this->add_error( 'imap-test', __( 'The IMAP test failed', INCSUB_SUPPORT_LANG_DOMAIN ) );
				}

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

			// Updating changes
			if ( ! $this->is_error() ) {
				foreach ( $this->settings as $key => $setting ) {
					update_site_option( $key, $setting );
				}
			}

		}

		/**
		 * Fetch mails via IMAP
		 *
		 * @since 1.6
		 * 
		 * @return	boolean		Successfully connected to IMAP
		 */
		private function incsub_support_fetch_imap() {
			$imap_settings = $this->settings['incsub_support_imap'];
			
			/* connect to IMAP server */
			$hostname = "{{$imap_settings['host']}:{$imap_settings['port']}/imap{$imap_settings['ssl']}}{$imap_settings['mailbox']}";
			$username = $imap_settings['username'];
			$password = $imap_settings['password'];
			
			/* try to connect */
			$inbox = imap_open($hostname,$username,$password);
			
			if ($inbox == false) {
				return false;
			}
			
			/* grab emails */
			$emails = imap_search($inbox,'UNSEEN');
			
			/* if emails are returned, cycle through each... */
			if($emails) {
				foreach($emails as $email_number) {
					$overview = imap_fetch_overview($inbox, $email_number, 0);
					
					$from = preg_replace('/.*<([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})>$/i', '$1', trim($overview[0]->from));
					
					$user = get_user_by('email', $from);
					
					if (!$user) {
						continue;
					}
					
					$message = quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1));
					
					$tlines = preg_split("/(\r\n|\n|\r)>/", $message);
					
					$lines = preg_split("/\r\n|\n\r|\n|\r/", trim($tlines[0]));
					
					array_pop($lines);
					
					$_POST['message'] = trim(join("\r\n", $lines));
					$_POST['category'] = 1;
					$_POST['priority'] = 1;
					$_POST['status'] = 3;
					
					if (preg_match('/R.+\[#[0-9]+\]/i', $overview[0]->subject) >= 1) {
						$_POST['modifyticket'] = 1;	
						$_POST['subject'] = preg_replace('/R.+\[#([0-9]+)\] (.*)/i', '$2', $overview[0]->subject);
						$_POST['ticket_id'] = preg_replace('/R.+\[#([0-9]+)\] .*/i', '$1', $overview[0]->subject);
					} else {
						$_POST['addticket'] = 1;
						$_POST['subject'] = $overview[0]->subject;
					}
					
					incsub_support_process_reply($user);
				}
				imap_setflag_full($inbox, join(',', $emails), "\\Seen");
			}
			
			imap_close($inbox);
			return true;
		}

	}

}