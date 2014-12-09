<div class="support-system-reply row <?php echo esc_attr( incsub_support_the_reply_class() ); ?>" id="support-system-reply-<?php echo incsub_support_get_the_reply_id(); ?>">
	<div class="large-12 columns">
		<div class="support-system-ticket-reply-wrap">
			<div class="support-system-reply-header clearfix">
				<div class="support-system-reply-avatar"><?php echo get_avatar( incsub_support_get_the_poster_id(), 32 ); ?></div>
				<div class="support-system-reply-poster">
					<h3><?php echo incsub_support_get_the_poster_username(); ?></h3>
				</div>
			</div>
			<p class="support-system-reply-date"><strong><?php echo incsub_support_get_the_reply_date(); ?></strong></p>
			<hr/>
			<div class="row support-system-reply-message">
				<div class="large-12 columns">
					<?php echo incsub_support_get_the_reply_message(); ?>			

					<?php if ( incsub_support_reply_has_attachments() ): ?>
						<div class="support-system-reply-attachments">
							<h5><?php _e( 'Attachments', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h5>
							<ul>
							<?php foreach ( incsub_support_get_the_reply_attachments() as $attachment_url ): ?>
								<li><a href="<?php echo esc_url( $attachment_url ); ?>" title="<?php printf( __( 'Download %s file', INCSUB_SUPPORT_LANG_DOMAIN ), $attachment_url ); ?>" ><?php echo basename( $attachment_url ); ?></a></li>
							<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	

</div>