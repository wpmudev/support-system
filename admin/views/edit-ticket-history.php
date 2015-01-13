<?php if ( $errors ): ?>
	<div style="padding-top:30px;" id="edit-ticket-form-errors">
		<?php foreach ( $errors as $error ): ?>
			<div class="support-system-error"><p><?php echo esc_html( $error['message'] ); ?></p></div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<form id="edit-ticket-form" action="#edit-ticket-form-errors" method="post" enctype="multipart/form-data">
	<h2><?php esc_html_e( 'Insert new Reply', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
	<table class="form-table">
		<?php $this->render_row(__( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ), $categories_dropdown ); ?>
		<?php $this->render_row(__( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ), $priorities_dropdown ); ?>

		<?php if ( incsub_support_current_user_can( 'update_ticket' ) ): ?>

			<?php ob_start(); ?>
				<select name="responsibility" id="responsibility">
					<?php if ( $ticket->admin_id == get_current_user_id() ): ?>
						<option <?php selected( $responsibility, 'keep' ); ?> value="keep"><?php _e("Keep Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<option <?php selected( $responsibility, 'punt' ); ?> value="punt"><?php _e("Give Up Responsibility To Allow Another Admin To Accept", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
					<?php else: ?>
						<option <?php selected( $responsibility, 'accept' ); ?> value="accept"><?php _e("Accept Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<option <?php selected( $responsibility, 'keep' ); ?> value="keep"><?php _e("Keep ticket unassigned", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<?php if ( ! empty( $ticket->admin_id ) ): ?>
							<option <?php selected( $responsibility, 'help' ); ?> value="help"><?php _e("Keep Current Admin, And Just Help Out With A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						<?php endif; ?>
					<?php endif; ?>
				</select>
			<?php $this->render_row(__( 'Ticket Responsibility', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
		<?php endif; ?>
		
		<?php ob_start(); ?>
			<h4 class="support-system-add-reply-subtitle"><?php _e("Please provide as much information as possible, so that the user can understand the solution/request.", INCSUB_SUPPORT_LANG_DOMAIN); ?></h4>
			<?php wp_editor( '', 'message-text', array( 'media_buttons' => true ) ); ?>
		<?php $this->render_row(__( 'Add a reply', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>

		<?php if ( incsub_support_current_user_can( 'close_ticket', $ticket->ticket_id ) ): ?>
			<?php ob_start(); ?>
				<label for="closeticket">
					<input type="checkbox" name="closeticket" id="closeticket" value="1" <?php checked( $ticket->is_closed() ); ?>/> <strong><?php _e( 'Yes, close this ticket.', INCSUB_SUPPORT_LANG_DOMAIN ); ?></strong><br />
				</label>
				<span class="description"><?php _e("Once a ticket is closed, users can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
			<?php $this->render_row(__( "Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() ); ?>
		<?php endif; ?>

		<?php ob_start(); ?>
			<div class="support-attachments"></div>
		<?php $this->render_row( __( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ),  ob_get_clean() ); ?>
		
		<input type="hidden" name="ticket_id" value="<?php echo $ticket->ticket_id; ?>" />
		<?php wp_nonce_field( 'add-ticket-reply-' . $ticket->ticket_id ); ?>
	</table>

	<?php submit_button( __( 'Update ticket', INCSUB_SUPPORT_LANG_DOMAIN ), 'primary', 'submit-ticket-reply' ); ?>

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
