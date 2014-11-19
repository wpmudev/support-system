<form method="post" id="support-system-reply-form" action="#support-system-reply-form-wrap">
	<?php incsub_support_reply_form_errors(); ?>
	<?php incsub_support_editor(); ?>
	<?php incsub_support_reply_form_fields(); ?>
	<br/>
	<input type="submit" name="support-system-submit-reply" class="button small" value="<?php esc_attr_e( 'Submit Reply', INCSUB_SUPPORT_LANG_DOMAIN ); ?>" />
	
</form>