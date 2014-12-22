<?php if ( incsub_support_has_replies() ): ?>
	<?php incsub_support_list_replies(
		array(
			'reply_class' => 'row',
			'author_class' => '',
			'message_class' => '',
			'date_class' => ''
		)
	); ?>
<?php else: ?>
	<h2 class="alert-box info"><?php _e( 'There are no replies yet', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
<?php endif; ?>

<?php if ( ! incsub_support_is_ticket_closed() && incsub_support_current_user_can( 'insert_reply' ) ): ?>
	<div id="support-system-reply-form-wrap">
		<h2><?php _e( 'Add a Reply', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
		<?php incsub_support_reply_form(); ?>
	</div>
<?php endif; ?>