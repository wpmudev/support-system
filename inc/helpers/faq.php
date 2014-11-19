<?php 

function incsub_support_sanitize_faq_fields( $faq ) {
	$int_fields = array( 'faq_id', 'site_id', 'cat_id', 'help_views', 'help_count', 'help_yes', 
		'help_no' );

	foreach ( get_object_vars( $faq ) as $name => $value ) {
		if ( in_array( $name, $int_fields ) )
			$value = intval( $value );

		$faq->$name = $value;
	}

	$faq = apply_filters( 'support_system_sanitize_faq_fields', $faq );

	return $faq;
}

/**
 * Get a single FAQ element
 * 
 * @param  int|Object $faq The FAQ ID or a Incsub_Support_FAQ class object
 * @return Object Incsub_Support_FAQ class object
 */
function incsub_support_get_faq( $faq ) {
	$faq = Incsub_Support_FAQ::get_instance( $faq );

	$faq = apply_filters( 'support_system_get_faq', $faq );

	return $faq;
}

/**
 * Insert a new FAQ
 * 
 * @param array $args {
 *     An array of elements that make up a FAQ.
 * 
 *     @type int 'site_id'    			Site ID, only for multinetwork sites otherwise = 1
 *     @type int 'cat_id'             	The FAQ category ID
 *     @type string 'question'          The question
 *     @type string 'answer'           	The answer
 * }
 * @return mixed the new FAQ ID, WP_Error otherwise
 */
function incsub_support_insert_faq( $args = array() ) {
	global $wpdb, $current_site;

	$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

	$defaults = array(
		'site_id' => $current_site_id,
		'cat_id' => 0,
		'question' => '',
		'answer' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	$args['help_views'] = 0;
	$args['help_count'] = 0;
	$args['help_yes'] = 0;
	$args['help_no'] = 0;

	$insert = array();
	$insert_wildcards = array();

	// SITE ID
	$insert['site_id'] = $args['site_id']; 
	$insert_wildcards[] = '%d'; 

	// CATEGORY
	

	// QUESTION
	$question = stripslashes_deep( $args['question'] );
	$question = strip_tags( $question );
	if ( empty( $question ) )
		return new WP_Error( 'empty_question', __( 'FAQ title must not be empty.', INCSUB_SUPPORT_LANG_DOMAIN ) );

	$insert['question'] = $question; 
	$insert_wildcards[] = '%s'; 

	// ANSWER
	$answer = stripslashes_deep( $args['answer'] );
	if ( empty( $answer ) )
		return new WP_Error( 'empty_answer', __( 'FAQ answer must not be empty.', INCSUB_SUPPORT_LANG_DOMAIN ) );

	$insert['answer'] = $answer; 
	$insert_wildcards[] = '%s'; 

	$table = incsub_support()->model->faq_table;
	$wpdb->insert(
		$table,
		$insert,
		$insert_wildcards
	);

	$faq_id = $wpdb->insert_id;

	if ( ! $faq_id )
		return new WP_Error( 'insert_error', __( 'Error inserting the FAQ element, please try again later.', INCSUB_SUPPORT_LANG_DOMAIN ) );


	do_action( 'support_system_insert_faq', $faq_id, $args );


	return $faq_id;

}

function incsub_support_update_faq( $faq_id, $args ) {
	global $wpdb;

	$faq = incsub_support_get_faq( $faq_id );
	if ( ! $faq )
		return false;

	$fields = array( 'site_id' => '%d', 'cat_id' => '%d', 'question' => '%s', 'answer' => '%s', 'help_views' => '%d', 'help_count' => '%d', 
		'help_yes' => '%d', 'help_no' => '%d' );

	$update = array();
	$update_wildcards = array();
	foreach ( $fields as $field => $wildcard ) {
		if ( isset( $args[ $field ] ) ) {
			$update[ $field ] = $args[ $field ];
			$update_wildcards[] = $wildcard;
		}
	}

	if ( empty( $update ) )
		return false;
	
	$faqs_table = incsub_support()->model->faq_table;

	$result = $wpdb->update(
		$faqs_table,
		$update,
		array( 'faq_id' => $faq_id ),
		$update_wildcards,
		array( '%d' )
	);

	if ( ! $result )
		return false;

	wp_cache_delete( $faq_id, 'support_system_faqs' );

	$old_faq = $faq;
	do_action( 'support_system_update_faq', $faq_id, $args, $old_faq );

	return true;
}

function incsub_support_vote_faq( $faq_id, $vote ) {
	if ( ! $faq = incsub_support_get_faq( $faq_id ) )
		return false;

	$set_field = $vote ? 'help_yes' : 'help_no';

	$field_value = $faq->$set_field + 1;

	return incsub_support_update_faq( $faq->faq_id, array( $set_field => $field_value ) );
}

/**
 * Delete a FAQ
 * 
 * @param  int $faq_id
 * @return Boolean
 */
function incsub_support_delete_faq( $faq_id ) {
    global $wpdb;

    $faq = incsub_support_get_faq( $faq_id );

    if ( ! $faq )
        return false;

    $faqs_table = incsub_support()->model->faq_table;

    $wpdb->query( 
        $wpdb->prepare( 
            "DELETE FROM $faqs_table
             WHERE faq_id = %d",
             $faq_id
         )
    );


    $old_faq = $faq;
    do_action( 'support_system_delete_faq', $faq_id, $old_faq );

    wp_cache_delete( $faq_id, 'support_system_faqs' );

    return true;
}