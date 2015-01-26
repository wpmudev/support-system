<div id="support-system-faqs">

	<div id="support-system-filter">
		<?php incsub_support_faqs_nav(); ?>
	</div>

	<hr/>

	<?php if ( incsub_support_has_items() ): ?>

		<ul class="accordion support-system-faqs-list" data-accordion>
			<?php while ( incsub_support_has_items() ): incsub_support_the_item(); ?>
				<li class="accordion-navigation support-system-faq <?php echo esc_attr( incsub_support_get_the_faq_class() ); ?>" id="support-faq-<?php echo incsub_support_get_the_faq_id(); ?>">
					<a href="#panel-<?php echo incsub_support_get_the_faq_id(); ?>"><h3><?php echo incsub_support_get_the_faq_question(); ?></h3></a>
					<div id="panel-<?php echo incsub_support_get_the_faq_id(); ?>" class="content">
				    	<?php echo incsub_support_get_the_faq_answer(); ?>
				    </div>
				</li>
			<?php endwhile; ?>
		</ul>

	<?php endif; ?>
</div>