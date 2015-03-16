<div class="wrap">
	<?php include( 'ticket-status-links.php' ); ?>
	<?php include( 'tickets-table-form.php' ); ?>
</div>

<script>
	jQuery(document).ready(function($) {
		$( 'span.delete > a' )
			.click( function(e) {
				return confirm( '<?php _e( "Are you sure you want to delete this ticket?", INCSUB_SUPPORT_LANG_DOMAIN ); ?>');
			})
	});
</script>