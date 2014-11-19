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
				</div>
			</div>
		</div>
	</div>
	

</div>