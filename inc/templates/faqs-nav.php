<div class="row">
	<div class="large-12 columns">
		<form method="get" class="support-system-filter" action="#faq-cat-id">
			<div class="row">
				<div class="large-5 columns">
					<?php incsub_support_the_faq_category_filter( 'cat-id' ); ?>
				</div>
				<div class="large-4 columns">
					<?php incsub_support_the_search_input( array( 'placeholder' => __( 'Search FAQs', INCSUB_SUPPORT_LANG_DOMAIN ) ) ); ?>
				</div>
				<div class="large-3 columns">
					<input type="submit" class="button small" name="support-system-submit-filter" value="<?php esc_attr_e( 'Filter' , INCSUB_SUPPORT_LANG_DOMAIN ); ?>"/>
				</div>
				<div class="large-3 columns">
					<h5 class="support-system-items-count text-right"><?php printf( __( '%d FAQs', INCSUB_SUPPORT_LANG_DOMAIN ), incsub_support_the_faqs_number() ); ?></h5>
				</div>
			</div>
			
		</form>
		
	</div>
</div>