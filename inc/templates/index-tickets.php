<div id="support-system-tickets">

	<div class="support-system-filter">
		<?php incsub_support_tickets_list_filter(); ?>
	</div>

	<hr/>

	<?php if ( incsub_support_has_tickets() ): ?>

		<ul class="support-system-tickets-list">
			<?php while ( incsub_support_has_tickets() ): incsub_support_the_ticket(); ?>
				<ul class="support-system-ticket row <?php echo esc_attr( incsub_support_get_the_ticket_class() ); ?>" id="support-ticket-<?php echo incsub_support_get_the_ticket_id(); ?>">
					<li class="small-2 large-1 columns support-system-ticket-author-data">
						<?php echo get_avatar( incsub_support_get_the_author_id(), 48 ); ?>
					</li>
					<li class="small-10 large-11 columns support-system-ticket-content">
						<h2 class="support-system-ticket-title">
							<a href="<?php echo esc_url( incsub_support_get_the_ticket_permalink() ); ?>" title="<?php echo esc_attr( incsub_support_get_the_ticket_title() ); ?>">
								<?php echo incsub_support_get_the_ticket_title(); ?> 
							</a>
								<?php 
									incsub_support_the_ticket_badges(
										array(
											'badge_base_class' => 'label',
											'replies_badge_class' => 'secondary',
											'status_badge_class' => 'success'
										)
									); 
								?>
						</h2>
						<div class="support-system-ticket-message">
							<?php echo incsub_support_get_the_ticket_excerpt(); ?>
						</div>
						<ul class="inline-list support-system-ticket-meta">
							<li class="support-system-byauthor"><span><?php echo sprintf( _x( 'by <strong>%s</strong>', 'ticket author', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_get_the_author() ); ?></span></li>	
							<li class="support-system-lastreply">
								<span><?php echo sprintf( _x( 'Last reply: <a href="%s">%s ago</a>', 'ticket update date', INCSUB_SUPPORT_LANG_DOMAIN ), esc_url( incsub_support_get_the_last_ticket_reply_url() ), incsub_support_get_the_ticket_updated_date() ); ?></span>
							</li>
							<li class="support-system-category"><span class="support-system-tag"><?php echo incsub_support_get_the_ticket_category_link(); ?></span></li>
						</ul>
						
					</li>
				</ul>
			<?php endwhile; ?>
		</ul>

		<div class="row">
			<div class="large-12 columns">
				<?php 
					incsub_support_paginate_links( 
						array( 
							'ul_class' => 'pagination right',
							'current_class' => 'current',
							'disabled_class' => 'unavailable',
							'arrow_class' => 'arrow'
						) 
					); 
				?>
			</div>
		</div>
	<?php endif; ?>
</div>