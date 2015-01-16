<div class="row">
	<div class="large-12 columns">
		<form method="get" class="support-system-filter">
			<div class="row">
				<div class="large-5 columns">
					<?php incsub_support_the_category_filter(); ?>
				</div>
				<div class="large-4 columns">
					<?php incsub_support_the_search_input(); ?>
				</div>
				<div class="large-3 columns">
					<input type="submit" class="button small" name="support-system-submit-filter" value="<?php esc_attr_e( 'Filter' , INCSUB_SUPPORT_LANG_DOMAIN ); ?>"/>
				</div>
				<div class="large-3 columns">
					<h5 class="support-system-tickets-count text-right"><?php printf( __( '%d tickets', INCSUB_SUPPORT_LANG_DOMAIN ), support_system_the_tickets_number() ); ?></h5>
				</div>
			</div>
		</form>
		
	</div>
</div>