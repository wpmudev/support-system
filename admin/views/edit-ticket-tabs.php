<h2 class="nav-tab-wrapper">
	<?php foreach ( $tabs as $key => $name ): ?>
		<?php $link = add_query_arg( 'tab', $key, $edit_menu_url ); ?>
		<a href="<?php echo $link; ?>" class="nav-tab <?php echo $current_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
	<?php endforeach; ?>
</h2>

<p><a class="button" href="<?php echo esc_url( $menu_url ); ?>"><?php echo '&larr; ' . __( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></p>

<?php if ( $updated ): ?>
	<div class="updated"><p><?php _e( 'Ticket updated', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php endif; ?>

<?php if ( $ticket->is_closed() ): ?>
	<div class="error"><p><?php _e( 'This ticket has been closed', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p></div>
<?php endif; ?>