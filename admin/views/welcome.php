<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Support System %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></h1>

	<div class="about-text">
		<?php _e( 'Thanks for installing Support System!', INCSUB_SUPPORT_LANG_DOMAIN ); ?>					
	</div>

	<div class="wp-badge support-system-badge"><div class="badge-version"><?php printf( __( 'Version %s', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_version() ); ?></div></div>

	<p>
		<a href="<?php echo $settings_url; ?>" class="button button-primary"><?php _e( 'Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
		<a href="https://premium.wpmudev.org/project/support-system/" class="docs button button-secondary"><?php _e( 'More info', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
	</p>
	
	<div class="changelog">
		<div class="feature-section col one-col">
			<div class="col-1">
				<h2><?php _e( 'FAQs in Frontend are finally here!', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
			</div>
		</div>
	</div>

	<div class="changelog">
		<div class="feature-section col two-col">
			<div class="col-1">
				<p><?php printf( __( 'Support System comes now with options to display a FAQ in your site. As we did with tickets, FAQs are rendered using <a href="%s">Foundation 5</a>.', INCSUB_SUPPORT_LANG_DOMAIN ), 'http://foundation.zurb.com/' ); ?></p>
				<p><?php _e( 'You can manage your FAQs from admin, as usual. Users will still have access to FAQs through an admin menu unless you want to deactivate that menu in Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
				<p><?php _e( 'FAQs system comes with <strong>Pro Sites</strong> integration. Allow any kind of Pro User with any level assigned to see your FAQs!', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			</div>
			<div class="col-2 last-feature">
				<img src="<?php echo INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/images/support-welcome-1.png'; ?>">
			</div>
		</div>
	</div>

	<div class="changelog under-the-hood">

		<div class="feature-section col three-col">
			<div>
				<h4><?php esc_html_e( 'Changes in admin styles', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
				<img src="<?php echo INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/images/support-welcome-2.png'; ?>">
				<p><?php _e( 'We did it again. Support System 2.1 comes with many little (and bigger) improvements in admin. We have remade the Edit Ticket Screen thinking on usability, now you will be able to manage your tickets in a single page.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			</div>
			<div>
				<h4><?php esc_html_e( 'Widgets in Frontend Tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
				<img src="<?php echo INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/images/support-welcome-3.png'; ?>">
				<p><?php _e( 'We believe in making Support System extensible so you or your developer can add more and more features easily to the plugin.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			</div>
			<div class="last-feature">
				<h4><?php esc_html_e( 'More speed!', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
				<p><?php _e( 'Cache system has been reviewed, Support System now makes less queries and it behaves faster than ever!', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
			</div>
		</div>


		<hr>

		<div class="return-to-dashboard">
			<a href="<?php echo $settings_url; ?>"><?php esc_html_e( 'Go to Settings', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
		</div>

	</div>

</div>

