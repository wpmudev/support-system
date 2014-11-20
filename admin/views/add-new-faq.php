<?php settings_errors( 'support_system_submit_new_faq' ); ?>
<form method="post" action="">
		
	<table class="form-table">
		<?php ob_start(); ?>
			<input type="text" value="<?php echo esc_attr( $question ); ?>" class="widefat" name="question" id="question">
		<?php $this->render_row( 'Question', ob_get_clean() ); ?>

		<?php $this->render_row( __( 'FAQ Category', INCSUB_SUPPORT_LANG_DOMAIN ), $categories_dropdown ); ?>

		<?php ob_start(); ?>
			<?php wp_editor( $answer, 'answer', array( 'media_buttons' => true ) ); ?> 
		<?php $this->render_row( 'Answer', ob_get_clean() ); ?>

	</table>

	<p class="submit">
		<?php wp_nonce_field( 'add-new-faq' ); ?>
		<?php submit_button( __( 'Submit new FAQ', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-faq', false ); ?>
		<a href="<?php echo esc_attr( $list_menu_url ); ?>" class="button-secondary"><?php _e( 'Back to FAQs list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>
	</p>
</form>
