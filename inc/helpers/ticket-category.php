<?php

function incsub_support_sanitize_ticket_category_fields( $cat ) {
	$int_fields = array( 'cat_id', 'user_id', 'site_id' );

	foreach ( get_object_vars( $cat ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		$cat->$name = $value;
	}

	return $cat;
}

function incsub_support_get_ticket_category( $cat ) {
	$cat = Incsub_Support_Ticket_Category::get_instance( $cat );
	return $cat;
}

function incsub_support_get_ticket_categories( $args = array() ) {
	global $wpdb, $current_site;

	$defaults = array(
		'orderby' => 'cat_name',
		'order' => 'asc',
		'per_page' => -1,
		'count' => false,
		'page' => 1,
		'defcat' => null
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$table = incsub_support()->model->tickets_cats_table;
	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	// WHERE
	$where = array();
	$where[] = $wpdb->prepare( "site_id = %d", $current_site_id );

	if ( $defcat !== null ) {
		// This is an enum field type!!
		if ( $defcat )
			$where[] = "defcat = 2";
		else
			$where[] = "defcat = 1";
	}	

	$where = "WHERE " . implode( " AND ", $where );

	// ORDER
	$order = strtoupper( $order );
	$order = "ORDER BY $orderby $order";

	$limit = '';
	if ( $per_page > -1 )
		$limit = $wpdb->prepare( "LIMIT %d, %d", intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );

	if ( $count ) {
		$query = "SELECT COUNT(cat_id) FROM $table $where";

		$key = md5( $query );
		$cache_key = "incsub_support_get_ticket_categories_count:$key";
		$results = wp_cache_get( $cache_key, 'support_system_ticket_categories' );

		if ( $results === false ) {
			$results = $wpdb->get_var( $query );
			wp_cache_set( $cache_key, $results, 'support_system_ticket_categories' );
		}

		return $results;
	}
	else {
		$query = "SELECT cat_id, cat_name, defcat, user_id FROM $table $where $order $limit";

		$key = md5( $query );
		$cache_key = "incsub_support_get_ticket_categories:$key";
		$_cats = wp_cache_get( $cache_key, 'support_system_ticket_categories' );

		if ( $_cats === false ) {
			$_cats = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $_cats, 'support_system_ticket_categories' );
		}

		$cats = array();
		foreach ( $_cats as $cat ) {
			$cats[] = incsub_support_get_ticket_category( $cat );
		}
		
		if ( empty( $cats ) )
			return array();

		return $cats;
	}
}

function incsub_support_get_ticket_categories_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;
	return incsub_support_get_ticket_categories( $args );
}

function incsub_support_ticket_categories_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'ticket-cat',
		'id' => 'ticket-cat',
		'show_empty' => __( 'Select a category', INCSUB_SUPPORT_LANG_DOMAIN ),
		'selected' => '',
		'class' => '',
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( ! $echo )
		ob_start();

	$cats = incsub_support_get_ticket_categories();

	?>
		<select class="<?php echo esc_attr( $class ); ?>" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
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

function incsub_support_insert_ticket_category( $name, $user_id = 0 ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$tickets_cats_table = incsub_support()->model->tickets_cats_table;

	$name = trim( $name );
	if ( empty( $name ) )
		return false;

	$name = wp_unslash( $name );

	$res = $wpdb->insert(
		$tickets_cats_table,
		array( 
			'cat_name' 	=> $name,
			'site_id'	=> $current_site_id,
			'user_id'	=> $user_id,
		),
		array( '%s', '%d', '%d' )
	);

	if ( ! $res )
		return false;

	return $wpdb->insert_id;
}

function incsub_support_update_ticket_category( $ticket_category_id, $args = array() ) {
	global $wpdb, $current_site;

	$tickets_cats_table = incsub_support()->model->tickets_cats_table;

	$ticket_category = incsub_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return false;

	$defaults = array(
		'cat_name' => $ticket_category->cat_name,
		'user_id' => 0,
		'defcat' => 0
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$cat_name = trim( $cat_name );
	if ( empty( $cat_name ) )
		return false;

	$update = array();
	$update_wildcards = array();

	$update['cat_name'] = wp_unslash( $cat_name );
	$update_wildcards[] = '%s';

	$update['user_id'] = $user_id;
	$update_wildcards[] = '%d';

	if ( $defcat )
		incsub_support_set_default_ticket_category( $ticket_category_id );

	wp_cache_delete( $ticket_category_id, 'support_system_ticket_categories' );

	return $wpdb->update(
		$tickets_cats_table,
		$update,
		array( 'cat_id' => $ticket_category_id ),
		$update_wildcards,
		array( '%d' )
	);
}

function incsub_support_get_default_ticket_category() {
	$default_category = wp_cache_get( 'support_system_default_ticket_category', 'support_system_ticket_categories' );

	if ( $default_category )
		return $default_category;

	$results = incsub_support_get_ticket_categories( array( 'per_page' => 1, 'defcat' => 1 ) );
	if ( isset( $results[0] ) ) {
		wp_cache_set( 'support_system_default_ticket_category', $results[0], 'support_system_ticket_categories' );
		return $results[0];
	}

	return false;
}

function incsub_support_set_default_ticket_category( $ticket_category_id ) {
	global $wpdb;

	$tickets_cats_table = incsub_support()->model->tickets_cats_table;

	$ticket_category = incsub_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return false;

	$default_category = incsub_support_get_default_ticket_category();
	if ( $default_category )
		$wpdb->query( "UPDATE $tickets_cats_table SET defcat = 1" ); // enum type field!!

	$result = $wpdb->update(
		$tickets_cats_table,
		array( 'defcat' => 2 ), // enum type field!!
		array( 'cat_id' => $ticket_category_id ),
		array( '%d' ),
		array( '%d' )
	);

	wp_cache_delete( 'support_system_default_ticket_category', 'support_system_ticket_categories' );
	wp_cache_delete( $ticket_category_id, 'support_system_ticket_categories' );

}

function incsub_support_delete_ticket_category( $ticket_category_id ) {
	global $wpdb;

	$tickets_cats_table = incsub_support()->model->tickets_cats_table;

	$ticket_category = incsub_support_get_ticket_category( $ticket_category_id );
	if ( ! $ticket_category )
		return;

	// Don't allow to remove the default category
	if ( $ticket_category->defcat )
		return;

	$default_category = incsub_support_get_default_ticket_category();

	$category_tickets = incsub_support_get_tickets_b(
		array(
			'per_page' => -1,
			'category' => $ticket_category_id
		)
	);
	if ( $category_tickets && $default_category ) {
		foreach ( $category_tickets as $ticket )
			incsub_support_update_ticket( $ticket->ticket_id, array( 'cat_id' => $default_category->cat_id ) );
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM $tickets_cats_table WHERE cat_id = %d", $ticket_category_id ) );

	wp_cache_delete( $ticket_category_id, 'support_system_ticket_categories' );
}