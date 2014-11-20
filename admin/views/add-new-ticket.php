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

		<?php
		// ATACHMENTS
			ob_start();
		?>				
		<ul id="attachments-list">
		
		</ul>			
			<button id="submit-new-attachment" class="button-secondary"><?php _e( 'Upload a new file', INCSUB_SUPPORT_LANG_DOMAIN ); ?></button>
			<?php
			$markup = ob_get_clean();
			$this->render_row( __( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ),  $markup ); 
		?>

		<?php do_action( 'support_new_ticket_fields' ); ?>
		
	
	</table>
	<p class="submit">
		<?php wp_nonce_field( 'add-new-ticket' ); ?>
		<?php submit_button( __( 'Submit new ticket', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-new-ticket', false ); ?>
		<a href="<?php echo esc_attr( $list_menu_url ); ?>" class="button-secondary"><?php _e( 'Back to tickets list', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a>

	</p>
</form>