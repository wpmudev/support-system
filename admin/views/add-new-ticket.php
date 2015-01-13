<?php settings_errors( 'support_system_submit_new_ticket' ); ?>
<form method="post" action="" enctype="multipart/form-data">
	<table class="form-table">
		
		<p><span class="description"><?php _e( '* All fields are required.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></p>
		<?php ob_start(); ?>
			<input type="text" name="subject" class="widefat" maxlength="100" value="<?php echo $subject; ?>"><br/>
			<span class="description"><?php _e( '(max: 100 characters)', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span>
		<?php $this->render_row( __( 'Subject', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php $this->render_row( __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ), $categories_dropdown ); ?>
		<?php $this->render_row( __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ), $priorities_dropdown ); ?>

		<?php ob_start(); ?>
			<?php wp_editor( $message, 'message-text', array( 'media_buttons' => true ) ); ?>
		<?php $this->render_row( __( 'Problem description', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php ob_start(); ?>
			<div class="support-attachments"></div>
		<?php $this->render_row( __( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ),  ob_get_clean() ); ?>

		<?php do_action( 'support_new_ticket_fields' ); ?>
		
	
	</table>
	<p class="submit">
		<?php wp_nonce_field( 'add-new-ticket' ); ?>
		<?php submit_button( __( 'Submit new ticket', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-ticket', false ); ?>

	</p>
</form>

<script>
	jQuery(document).ready(function($) {
		$('.wrap').support_system({
			attachments: {
				container_selector: '.support-attachments',
				button_text: " <?php _e( 'Add files...', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
				button_class: 'button-secondary',
				remove_file_title: "<?php esc_attr_e( 'Remove file', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
				remove_link_class: "button-secondary",
				remove_link_text: " <?php _e( 'Remove file', INCSUB_SUPPORT_LANG_DOMAIN ); ?>",
			}
		});
	});
</script>
