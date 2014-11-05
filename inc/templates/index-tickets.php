<div id="support-system-tickets">
	<div class="support-system-search">
		<!-- get template search -->
	</div>

	<?php if ( incsub_support_has_tickets() ): ?>
		<ul class="support-system-tickets-list">
			<?php while ( incsub_support_has_tickets() ): incsub_support_the_ticket(); ?>
				<div class="row">
					<ul class="large-12 small-block-grid-2 large-block-grid-2 columns support-system-ticket" id="support-ticket-<?php echo incsub_support_get_the_ticket_id(); ?>">
						<li class="support-system-ticket-author-data">
							<?php echo get_avatar( incsub_support_get_the_author_id(), 48 ); ?>
						</li>
						<li>
							<h2 class="support-system-ticket-title">
								<a href="<?php echo esc_url( incsub_support_get_the_ticket_permalink() ); ?>" title="<?php echo esc_attr( incsub_support_get_the_ticket_title() ); ?>">
									<?php echo incsub_support_get_the_ticket_title(); ?>
								</a>
							</h2>
							<div class="support-system-ticket-message">
								<?php echo incsub_support_get_the_ticket_excerpt(); ?>
							</div>
							<ul class="inline-list support-system-ticket-meta">
								<li class="support-system-byauthor"><?php echo sprintf( _x( 'by %s', 'ticket author', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_the_author() ); ?></li>	
								<li class="support-system-lastreply">
									<a href="<?php echo esc_url( incsub_support_get_the_last_ticket_reply_url() ); ?>">
										<?php echo sprintf( _x( '%s ago', 'ticket update date', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_the_ticket_updated_date() ); ?>
									</a>
								</li>
							</ul>
							<div class="support-system-ticket-category">
								<span class="support-system-tag"><?php echo incsub_support_get_the_ticket_category(); ?></span>
							</div>
							
						</li>
					</ul>
				</div>
			<?php endwhile; ?>
			</div>
		</ul>
	<?php endif; ?>
</div>