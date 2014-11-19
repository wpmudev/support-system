<form id="support-tickets" method="post">
	<?php $tickets_table->search_box( __( 'Search tickets', INCSUB_SUPPORT_LANG_DOMAIN ), 's' ); ?>
	<?php $tickets_table->display(); ?>
</form>