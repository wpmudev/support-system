<?php

function incsub_support_ticket_categories_dropdown( $selected = false ) {
	$model = incsub_support_get_ticket_model();
	$categories = $model->get_ticket_categories();

	?>
		<option value="" <?php selected( $selected, false ); ?>><?php _e( 'Show all categories', INCSUB_SUPPORT_LANG_DOMAIN ); ?></option>		
	<?php
	foreach( $categories as $category ) {
		?>
			<option value="<?php echo esc_attr( absint( $category['cat_id'] ) ); ?>" <?php selected( $selected, absint( $category['cat_id'] ) ); ?>><?php echo $category['cat_name']; ?></option>			
		<?php
	}
}

function incsub_support_ticket_type_dropdown( $selected = false ) {
	?>
		<option value="" <?php selected( $selected, false ); ?>><?php _e( 'Show all status', INCSUB_SUPPORT_LANG_DOMAIN ); ?></option>		
	<?php
	foreach( MU_Support_system::$ticket_status as $key => $status ) {
		?>
			<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $status; ?></option>			
		<?php
	}
}