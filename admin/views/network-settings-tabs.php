<h2 class="nav-tab-wrapper">
	<?php foreach ( $tabs as $key => $name ): ?>
		<?php $link = add_query_arg( 'tab', $key, $menu_url ); ?>
		<a href="<?php echo $link; ?>" class="nav-tab <?php echo $current_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
	<?php endforeach; ?>
</h2>

<?php if ( $updated ): ?>
	<div class="updated"><p><?php _e( 'Settings updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php endif; ?>