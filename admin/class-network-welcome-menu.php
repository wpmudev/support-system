<?php

class Incsub_Support_Welcome_Menu extends Incsub_Support_Admin_Menu {

	public function add_menu() {		
		$this->menu_title = sprintf( __( 'Welcome to Support System %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() );
		$this->page_id = add_dashboard_page( 
			$this->menu_title,
			$this->menu_title,
			'manage_network',
			$this->slug,
			array( $this, 'render_page' ) 
		);


		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_init', array( $this, 'redirect_to_here' ) );
	}

	public function enqueue_styles() {
		$file = 'about';
		if ( is_rtl() )
			$file .= '-rtl';

		$file .= '.css';

		wp_enqueue_style( 'support-system-welcome', admin_url( 'css/' . $file ) );
	}


	public function render_page() {

		if ( is_multisite() )
			$settings_url = network_admin_url( 'admin.php?page=mu-support-settings-b' );	
		else
			$settings_url = admin_url( 'admin.php?page=mu-support-settings-b' );	
		

		?>
			<div class="wrap about-wrap">
				<h1><?php printf( __( 'Welcome to Support System %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></h1>

				<div class="about-text">
					<?php _e( 'Thanks for installing! [MORE INFO HERE?]', INCSUB_SUPPORT_LANG_DOMAIN ); ?>					
				</div>

				<div class="wp-badge support-system-badge"><?php printf( __( 'Version %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></div>

				<p>
					<a href="<?php echo $settings_url; ?>" class="button button-primary"><?php _e( 'Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
					<a href="https://premium.wpmudev.org/project/support-system/" class="docs button button-secondary"><?php _e( 'More info', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
				</p>
				
				<div class="changelog">
					<div class="feature-section col one-col">
						<div class="col-1">
							<h3>HEY TESTERS! FOLLOW THIS STEPS!</h3>
							<ul>
								<li><strong>1. Test the admin suff. Go to settings, play with the settings (forget about the Front for the moment) and then open a few tickets in different sites.</strong></li>
								<li><strong>2. Test the front: </strong>
									<ul>
										<li>Go to settings and activate the frontend.</li>
										<li>Select a blog ID where you are going to display the front.</li>
										<li>Select the pages: One to show the tickets and another to show the submit ticket form (you can select both the same if you want)</li>
										<li>Insert the shortcodes. You'll need to go to both pages and you'll see a new button in the TinyMCE Editor to insert both shortcodes</li>
										<li>Yeah, we need an autoinstaller for this.</li>
										<li>Go to the front end. PLAY!</li>
									</ul>
								</li>
							</ul>
						</div>
					</div>
				</div>

				<div class="changelog">
					<div class="feature-section col two-col">
						<div class="col-1">
							<h3>Lorem ipsum dolor sit amet</h3>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Reprehenderit earum quaerat nisi magni. Animi debitis quisquam officia laborum rem corporis, magni dolores quis voluptates, consequuntur unde quas exercitationem, necessitatibus tempora.</p>
						</div>
						<div class="col-2 last-feature">
							<img src="//s.w.org/images/core/4.0/media.jpg">
						</div>
					</div>
				</div>

				<div class="changelog under-the-hood">

					<div class="feature-section col three-col">
						<div>
							<h4>Feature thing 1</h4>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit.</p>
						</div>
						<div>
							<h4>Feature thing 2</h4>
							<p>Deleniti nobis labore, dolores error voluptas.</p>
						</div>
						<div class="last-feature">
							<h4>Feature thing 3</h4>
							<p>Doloremque minus maxime, ea consequuntur praesentium voluptatibus.</p>
						</div>
					</div>

					<hr>

					<div class="return-to-dashboard">
						<a href="<?php echo $settings_url; ?>"><?php esc_html_e( 'Go to Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
					</div>

				</div>

			</div>
		<?php
				
	}

	public function render_inner_page() {}

	public function admin_head() {
		remove_submenu_page( 'index.php', $this->slug );
	}

	public function redirect_to_here() {
	    if ( ! get_transient( 'incsub_support_welcome' ) ) {
			return;
	    }

		delete_transient( 'incsub_support_welcome' );

		$url = is_multisite() ? network_admin_url( 'index.php?page=' . $this->slug ) : admin_url( 'index.php?page=' . $this->slug );
		wp_redirect( $url );
		exit;
	}

}