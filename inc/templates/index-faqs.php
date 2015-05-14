<div id="support-system-faqs">

	<div id="support-system-filter">
		<?php if ( incsub_support_is_faqs_page() ): ?>
			<?php incsub_support_faqs_nav(); ?>
		<?php endif; ?>
	</div>

	<hr/>

	<?php if ( incsub_support_has_faqs() ): ?>

		<ul class="accordion support-system-faqs-list" data-accordion>
			<?php while ( incsub_support_has_faqs() ): incsub_support_the_faq(); ?>
				<li class="accordion-navigation support-system-faq <?php echo esc_attr( incsub_support_get_the_faq_class() ); ?>" id="support-faq-<?php echo incsub_support_get_the_faq_id(); ?>">
					<a class="clearfix" href="#panel-<?php echo incsub_support_get_the_faq_id(); ?>">
						<h3 class="support-system-faq-title"><?php echo incsub_support_get_the_faq_question(); ?></h3>
						<div class="support-system-handldiv"></div>
					</a>
					<div id="panel-<?php echo incsub_support_get_the_faq_id(); ?>" class="content">
				    	<?php echo incsub_support_get_the_faq_answer(); ?>
				    	<?php 
				    		if ( is_user_logged_in() )
				    			echo incsub_support_the_faq_vote_box(); 
				    	?>
				    </div>
				</li>
			<?php endwhile; ?>
		</ul>

	<?php endif; ?>
</div>