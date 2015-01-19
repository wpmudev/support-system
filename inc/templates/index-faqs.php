<div id="support-system-tickets">

	<div id="support-system-filter">
		<?php incsub_support_faqs_nav(); ?>
	</div>

	<hr/>

	<?php if ( incsub_support_has_items() ): ?>

		<ul class="support-system-tickets-list">
			<?php while ( incsub_support_has_items() ): incsub_support_the_item(); ?>
				<ul class="support-system-faq row <?php echo esc_attr( incsub_support_get_the_faq_class() ); ?>" id="support-faq-<?php echo incsub_support_get_the_faq_id(); ?>">
					<li class="small-10 large-11 columns support-system-faq-content">
						<h2 class="support-system-faq-question"><?php echo incsub_support_get_the_faq_question(); ?></h2>
						<div class="support-system-faq-answer"><?php echo incsub_support_get_the_faq_answer(); ?></div>
						<ul class="inline-list support-system-ticket-meta">
							<li class="support-system-category"><span class="support-system-tag"><?php echo incsub_support_get_the_faq_category_link(); ?></span></li>
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