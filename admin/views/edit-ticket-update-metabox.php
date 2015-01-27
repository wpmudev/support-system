<div class="submitbox" id="submitbox">
	<form method="post" action="">
		<ul class="ticket-update-fields">
			<?php foreach ( $fields as $field_id => $field ): ?>
				<li id="<?php echo esc_attr( $field_id ); ?>">
					<p class="ticket-field-label"><?php echo $field['label']; ?></p> 
					<p class="ticket-field-value"><?php echo $field['content']; ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
		

		<?php wp_nonce_field( 'update-ticket-details-' . $ticket->ticket_id ); ?>
		<input type="hidden" name="ticket_id" value="<?php echo $ticket->ticket_id; ?>">
		<div id="major-publishing-actions">
			<?php submit_button( __( 'Save changes', INCSUB_SUPPORT_LANG_DOMAIN ),  'primary', 'submit-ticket-details', false ); ?>
			<div class="clear"></div>
		</div>
	</form>
</div>
