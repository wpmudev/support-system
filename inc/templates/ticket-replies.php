<?php if ( incsub_support_has_replies() ): ?>
	<?php incsub_support_list_replies(); ?>
<?php else: ?>
	<h2 class="text-center"><?php _e( 'There are no replies yet', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
<?php endif; ?>