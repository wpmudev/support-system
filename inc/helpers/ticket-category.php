<?php

function incsub_support_get_ticket_category( $cat ) {
	$cat = Incsub_Support_Ticket_Category::get_instance( $cat );
	return $cat;
}

function incsub_support_get_ticket_categories() {
	global $wpdb, $current_site;

	$table = incsub_support()->model->tickets_cats_table;
	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$pq = $wpdb->prepare(
		"SELECT cat_id, cat_name, defcat, user_id
		FROM $table
		WHERE site_id = %d 
		ORDER BY cat_name ASC", 
		$current_site_id
	);

	$_cats = $wpdb->get_results( $pq );

	$cats = array();
	foreach ( $_cats as $cat ) {
		$cats[] = incsub_support_get_ticket_category( $cat );
	}
	
	if ( empty( $cats ) )
		return array();

	return $cats;
}

function incsub_support_ticket_categories_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'ticket-cat',
		'id' => 'ticket-cat',
		'show_empty' => __( 'Select a category', INCSUB_SUPPORT_LANG_DOMAIN ),
		'selected' => '',
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( ! $echo )
		ob_start();

	$cats = incsub_support_get_ticket_categories();

	?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( empty( $selected ) ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>

			<?php foreach ( $cats as $cat ): ?>
				<option value="<?php echo esc_attr( $cat->cat_id ); ?>" <?php selected( $selected == $cat->cat_id ); ?>><?php echo esc_html( $cat->cat_name ); ?></option>
			<?php endforeach; ?>

		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}