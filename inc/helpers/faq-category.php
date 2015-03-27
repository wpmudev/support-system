<?php

function incsub_support_sanitize_faq_category_fields( $cat ) {
	$int_fields = array( 'cat_id', 'user_id', 'site_id', 'qcount' );

	foreach ( get_object_vars( $cat ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		$cat->$name = $value;
	}

	$cat = apply_filters( 'support_system_sanitize_faq_category_fields', $cat );

	return $cat;
}

function incsub_support_get_faq_category( $cat ) {
	$cat = Incsub_Support_faq_Category::get_instance( $cat );
	$cat = apply_filters( 'support_system_get_faq_category', $cat );
	return $cat;
}



function incsub_support_get_faq_categories( $args = array() ) {
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

	$table = incsub_support()->model->faq_cats_table;
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
		$results = $wpdb->get_var( $query );
		return $results;
	}
	else {
		$query = "SELECT * FROM $table $where $order $limit";
		$_cats = $wpdb->get_results( $query );

		$cats = array();
		foreach ( $_cats as $cat ) {
			$cats[] = incsub_support_get_faq_category( $cat );
		}
		
		if ( empty( $cats ) )
			$cats = array();


		return $cats;
	}

	$cats = apply_filters( 'support_system_get_faq_categories', $cats, $args );

	return $cats;
}

function incsub_support_get_faq_categories_count( $args = array() ) {
	$args['count'] = true;
	$args['per_page'] = -1;
	return incsub_support_get_faq_categories( $args );
}

function incsub_support_faq_categories_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'faq-cat',
		'id' => false,
		'show_empty' => __( 'Select a category', INCSUB_SUPPORT_LANG_DOMAIN ),
		'selected' => '',
		'class' => '',
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( ! $id )
		$id = $name;
	
	if ( ! $echo )
		ob_start();

	$cats = incsub_support_get_faq_categories();

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

function incsub_support_insert_faq_category( $name ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$faq_cats_table = incsub_support()->model->faq_cats_table;

	$name = trim( wp_unslash( $name ) );
	if ( empty( $name ) )
		return false;

	$faq_category = incsub_support_get_faq_category( $name );
	if ( $faq_category )
		return false;

	$res = $wpdb->insert(
		$faq_cats_table,
		array( 
			'cat_name' 	=> $name,
			'site_id'	=> $current_site_id,
			'qcount' 	=> 0
		),
		array( '%s', '%d', '%d' )
	);

	if ( ! $res )
		return false;

	$cat_id = $wpdb->insert_id;

	do_action( 'support_system_insert_faq_category', $cat_id );

	return $cat_id;
}

function incsub_support_update_faq_category( $faq_category_id, $args = array() ) {
	global $wpdb, $current_site;

	$faq_cats_table = incsub_support()->model->faq_cats_table;

	$faq_category = incsub_support_get_faq_category( $faq_category_id );
	if ( ! $faq_category )
		return false;

	$defaults = array(
		'cat_name' => $faq_category->cat_name,
		'defcat' => $faq_category->defcat,
		'qcount' => $faq_category->qcount
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

	if ( $defcat )
		incsub_support_set_default_faq_category( $faq_category_id );

	$result = $wpdb->update(
		$faq_cats_table,
		$update,
		array( 'cat_id' => $faq_category_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( ! $result )
		return false;

	incsub_support_clean_faq_category_cache( $faq_category_id );

	$old_faq_category = $faq_category;
	do_action( 'support_system_update_faq_category', $faq_category_id, $args, $old_faq_category );

	return true;
}

function incsub_support_get_default_faq_category() {
	$default_category = wp_cache_get( 'support_system_default_faq_category', 'support_system_faq_categories' );

	if ( $default_category )
		return $default_category;

	$results = incsub_support_get_faq_categories( array( 'per_page' => 1, 'defcat' => 1 ) );

	if ( isset( $results[0] ) ) {
		wp_cache_set( 'support_system_default_faq_category', $results[0], 'support_system_faq_categories' );
		return $results[0];
	}



	return false;
}

function incsub_support_set_default_faq_category( $faq_category_id ) {
	global $wpdb;

	$faq_cats_table = incsub_support()->model->faq_cats_table;

	$faq_category = incsub_support_get_faq_category( $faq_category_id );
	if ( ! $faq_category )
		return false;

	$default_category = incsub_support_get_default_faq_category();
	if ( $default_category )
		$wpdb->query( "UPDATE $faq_cats_table SET defcat = 1" ); // enum type field!!

	$result = $wpdb->update(
		$faq_cats_table,
		array( 'defcat' => 2 ), // enum type field!!
		array( 'cat_id' => $faq_category_id ),
		array( '%d' ),
		array( '%d' )
	);

	wp_cache_delete( 'support_system_default_faq_category', 'support_system_faq_categories' );
	incsub_support_clean_faq_category_cache( $faq_category_id );
	incsub_support_clean_faq_category_cache( $default_category->cat_id );

	if ( ! $result )
		return false;

	return true;

}

function incsub_support_delete_faq_category( $faq_category_id ) {
	global $wpdb;

	$faq_cats_table = incsub_support()->model->faq_cats_table;

	$faq_category = incsub_support_get_faq_category( $faq_category_id );
	if ( ! $faq_category )
		return false;

	// Don't allow to remove the default category
	if ( $faq_category->defcat )
		return false;

	$default_category = incsub_support_get_default_faq_category();

	$category_faqs = incsub_support_get_faqs(
		array(
			'per_page' => -1,
			'category' => $faq_category_id
		)
	);
	if ( $category_faqs && $default_category ) {
		foreach ( $category_faqs as $faq )
			incsub_support_update_faq( $faq->faq_id, array( 'cat_id' => $default_category->cat_id ) );
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM $faq_cats_table WHERE cat_id = %d", $faq_category_id ) );

	incsub_support_clean_faq_category_cache( $faq_category_id );

	$old_faq_category = $faq_category;
	do_action( 'support_system_delete_faq_category', $faq_category_id, $old_faq_category );

	return true;
}

function incsub_support_count_faqs_on_category( $faq_category_id ) {
	global $wpdb;

	$faqs_table = incsub_support()->model->faq_table;

	$faq_category = incsub_support_get_faq_category( $faq_category_id );
	if ( ! $faq_category )
		return false;

	return $faq_category->get_faqs_count();	
}

function incsub_support_clean_faq_category_cache( $faq_category ) {

	$faq_category = incsub_support_get_faq_category( $faq_category );

	if ( empty( $faq_category ) )
		return;

	wp_cache_delete( $faq_category->cat_id, 'support_system_faq_categories' );
	wp_cache_delete( $faq_category->cat_id, 'support_system_faq_categories_counts' );

	do_action( 'support_system_clean_faq_category_cache', $faq_category );
}