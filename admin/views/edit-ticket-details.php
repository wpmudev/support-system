<form method="post" action="">
	<table class="form-table">
		<h3><?php echo __( 'Ticket Subject', INCSUB_SUPPORT_LANG_DOMAIN ) . ': ' .  stripslashes_deep( $ticket->title ); ?></h3>
		<?php $this->render_row( __( 'Current Status', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_ticket_status_name( $ticket->ticket_status ) ); ?>
		<?php $this->render_row( __( 'Created On (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_translated_date( $ticket->ticket_opened ) ); ?>


		<?php $this->render_row( __( 'Reporting User', INCSUB_SUPPORT_LANG_DOMAIN ), $ticket->get_user_name() ); ?>
		<?php $this->render_row( __( 'Last Reply From', INCSUB_SUPPORT_LANG_DOMAIN ), $last_reply_user_name ); ?>
		<?php $this->render_row( __( 'Last Updated (GMT)', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_translated_date( $ticket->ticket_updated ) ); ?>

		<?php do_action( 'support_network_ticket_details_fields', $ticket ); ?>
		
		<?php $this->render_row( __( 'Submitted from', INCSUB_SUPPORT_LANG_DOMAIN ), $submitted_blog_link ); ?>

		<?php $this->render_row( __( 'Staff Representative', INCSUB_SUPPORT_LANG_DOMAIN ), $super_admins_dropdown ); ?>

		<?php $this->render_row( __( 'Priority', INCSUB_SUPPORT_LANG_DOMAIN ),  $priorities_dropdown ); ?>
		<?php $this->render_row( __( 'Category', INCSUB_SUPPORT_LANG_DOMAIN ),  $categories_dropdown ); ?>

		<?php if ( incsub_support_current_user_can( 'update_ticket' ) ): ?>
			<?php ob_start(); ?>
				<input name="close-ticket" type="checkbox" <?php checked( $ticket->is_closed() ); ?> />
			<?php $this->render_row( __( 'Ticket closed', INCSUB_SUPPORT_LANG_DOMAIN ),  ob_get_clean() ); ?>
		<?php endif; ?>

	</table>

	<?php wp_nonce_field( 'update-ticket-details-' . $ticket->ticket_id ); ?>
	<input type="hidden" name="ticket_id" value="<?php echo $ticket->ticket_id; ?>">
	<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ),  'primary', 'submit-ticket-details' ); ?>
</form>