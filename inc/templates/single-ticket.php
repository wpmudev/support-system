<div id="support-system-single-ticket">
	<?php if ( incsub_support_has_tickets() ): incsub_support_the_ticket(); ?>
		<div class="row support-system-single-ticket-primary">
			<div class="large-12 columns <?php echo incsub_support_get_the_ticket_class(); ?>" id="support-system-ticket-<?php echo incsub_support_get_the_ticket_id(); ?>">
				<ul class="small-block-grid-2 large-block-grid-2">
					<li class="support-system-ticket-author-data">
						<?php echo get_avatar( incsub_support_get_the_author_id(), 96 ); ?><br/>
						<span class="support-system-ticket-author-name"><?php echo incsub_support_get_the_author(); ?></span>
					</li>
					<li class="support-system-ticket-title">
						<h1><?php echo incsub_support_get_the_ticket_title(); ?></h1>
					</li>
				</ul>
				<div class="support-system-ticket-message">
					<?php echo incsub_support_get_the_ticket_message(); ?>
				</div>
			</div>
		</div>
		<hr>
		<div class="row support-system-single-ticket-secondary">
			<div class="support-system-ticket-replies large-8 columns">
				<?php incsub_support_ticket_replies(); ?>
			</div>
				
			<div class="panel support-system-ticket-details large-4 columns">
				<h2><?php _e( 'Ticket details', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h2>
				<ul>
					<li><?php echo '<strong>' . __( 'Category:', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong> ' . incsub_support_get_the_ticket_category(); ?></li>
					<li><?php echo '<strong>' . __( 'Priority:', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong> ' . incsub_support_get_the_ticket_priority(); ?></li>
					<li><?php echo '<strong>' . __( 'Status:', INCSUB_SUPPORT_LANG_DOMAIN ) . '</strong> ' . incsub_support_get_the_ticket_status(); ?></li>
				</ul>
			</div>
		</div>
	<?php endif; ?>
</div>