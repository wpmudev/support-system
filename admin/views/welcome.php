<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Support System %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></h1>

	<div class="about-text">
		<?php _e( 'Thanks for installing Support System!', INCSUB_SUPPORT_LANG_DOMAIN ); ?>					
	</div>

	<div class="wp-badge support-system-badge"><?php printf( __( 'Version %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></div>

	<p>
		<a href="<?php echo $settings_url; ?>" class="button button-primary"><?php _e( 'Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
		<a href="https://premium.wpmudev.org/project/support-system/" class="docs button button-secondary"><?php _e( 'More info', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
	</p>
	
	<div class="changelog">
		<div class="feature-section col one-col">
			<div class="col-1">
				<h2><?php _e( 'Support System has been revamped', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
			</div>
		</div>
	</div>

	<div class="changelog">
		<div class="feature-section col two-col">
			<div class="col-1">
				<h3><?php _e( 'New frontend!', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
				<p><?php printf( __( 'Support System comes with <a href="%s">Foundation 5</a> integrated. Foundation 5 is a consistent and bullet-proof CSS Framework that allows to display the front in the best way possible in most of the themes.', INCSUB_SUPPORT_LANG_DOMAIN ), 'http://foundation.zurb.com/' ); ?></p>
			</div>
			<div class="col-2 last-feature">
				<img src="//s.w.org/images/core/4.0/media.jpg">
			</div>
		</div>
	</div>

	<div class="changelog under-the-hood">

		<div class="feature-section col three-col">
		<h3>This section is still on development. Stay alert :)</h3>
			<div>
				<h4>Totally recoded</h4>
				<p>Yes. We have completely refurbished home. More hooks, a better code, faster and more readable is now populating the plugin core.</p>
			</div>
			<div>
				<h4>Extensibility is the key</h4>
				<p>We believe in making Support System extensible so you or your developer can add more and more features easily to the plugin.</p>
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