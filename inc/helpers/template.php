<?php

function incsub_support_get_template( $slug, $name = false ) {
	$template = $slug . '.php';

	$template = apply_filters( 'support_system_get_template', $template, $slug );

	$templates = array();
	if ( $name )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	$locations = support_system_get_template_locations();

	$located = false;

	foreach ( $templates as $template ) {
		if ( empty( $template ) )
			return;

		$template = ltrim( $template, '/' );

		
		foreach ( $locations as $location ) {
			if ( empty( $location ) )
				continue;

			if ( file_exists( trailingslashit( $location ) . $template ) ) {
				$located = trailingslashit( $location ) . $template;
				break;
			}

		}

		if ( ! empty( $located ) ) {
			load_template( $located );
			break;
		}
	}

	return $located;
}

function support_system_get_template_locations() {
	return apply_filters( 'support_system_templates_locations', array(
		get_stylesheet_directory() . '/incsub-support',
		INCSUB_SUPPORT_PLUGIN_DIR . 'inc/templates'
	) );
}

function incsub_support_ticket_replies() {
	$ticket_id = incsub_support()->query->ticket->ticket_id;
	incsub_support_get_template( 'ticket-replies', $ticket_id );
}

function incsub_support_tickets_list_filter() {
	incsub_support_get_template( 'tickets-filter' );
}

function incsub_support_list_replies( $args = array() ) {
	$defaults = array(
		'avatar_size' => 32,
		'echo' => true,
		'reply_class' => 'support-system-ticket-reply'
	);

	$args = wp_parse_args( $args, $defaults );
	extract( $args );

	$replies = incsub_support()->query->ticket->get_replies();
	// Remove the main reply
	unset( $replies[0] );

	if ( ! $echo )
		ob_start();
	foreach ( $replies as $reply ) {
		$user = get_userdata( $reply->get_poster_id() );
		if ( ! $user ) {
			$username = __( 'Unknown user', INCSUB_SUPPORT_LANG_DOMAIN );
		}
		else {
			$username = $user->data->user_nicename;
		}

		$class = array();
		$class[] = $reply_class;
		if ( is_super_admin( $reply->admin_id ) )
			$class[] = $reply_class . '-staff-reply';

		$class = implode( ' ', $class );

		?>
			<div class="<?php echo esc_attr( $class ); ?>" id="support-system-reply-<?php echo $reply->message_id; ?>">
				<div class="<?php echo esc_attr( $reply_class ) . '-author'; ?>">
					<?php echo get_avatar( $reply->get_poster_id(), $avatar_size ); ?>
					<?php echo $username; ?>			
				</div>
				<div class="<?php echo esc_attr( $reply_class ) . '-message'; ?>">
					<?php echo $reply->message; ?>			
				</div>
				<div class="<?php echo esc_attr( $reply_class ) . '-date'; ?>">
					<?php echo incsub_support_get_translated_date( $reply->message_date ); ?>			
				</div>
			</div>
		<?php
	}

	if ( ! $echo )
		return ob_get_clean();
}

function incsub_support_the_category_filter( $class = '' ) {
	$selected = '';
	if ( ! empty( $_REQUEST['ticket-cat'] ) && incsub_support_get_ticket_category( absint(  $_REQUEST['ticket-cat'] ) ) ) 
		$selected = absint( $_REQUEST['ticket-cat'] );

	$args = array(
		'class' => $class,
		'selected' => $selected
	);

	incsub_support_ticket_categories_dropdown( $args );
}

function incsub_support_the_search_input( $class = '' ) {
	$search = ! empty( $_REQUEST['support-system-s'] ) ? stripslashes( $_REQUEST['support-system-s'] ) : '';
	?>
		<input type="text" placeholder="<?php esc_attr_e( 'Search tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?>" name="support-system-s" class="<?php echo esc_attr( $class ); ?>" value="<?php echo esc_attr( $search ); ?>"/>
	<?php
}

function incsub_support_the_tickets_nav() {
	$total_items = incsub_support()->query->found_tickets;
	$total_pages = incsub_support()->query->total_pages;

	if ( $total_pages == 1 )
		return;

	$current = incsub_support()->query->page;

	$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

	$output = '<span class="support-system-tickets-count">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

	$page_links = array();

	$disable_first = $disable_last = '';
	if ( $current == 1 )
		$disable_first = ' disabled';

	if ( $current == $total_pages )
		$disable_last = ' disabled';

	$page_links[] = sprintf( "<li><a class='%s' title='%s' href='%s'>%s</a></li>",
		'first-page' . $disable_first,
		esc_attr__( 'Go to the first page' ),
		esc_url( remove_query_arg( 'support-sytem-page', $current_url ) ),
		'&laquo;'
	);

	$page_links[] = sprintf( "<li><a class='%s' title='%s' href='%s'>%s</a></li>",
		'prev-page' . $disable_first,
		esc_attr__( 'Go to the previous page' ),
		esc_url( add_query_arg( 'support-sytem-page', max( 1, $current-1 ), $current_url ) ),
		'&lsaquo;'
	);

	$html_current_page = sprintf( "<span class='current-page' id='current-page-selector' type='text' name='support-sytem-page'>%d</span>", $current );

	$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );

	$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

	$page_links[] = sprintf( "<li><a class='%s' title='%s' href='%s'>%s</a></li>",
		'next-page' . $disable_last,
		esc_attr__( 'Go to the next page' ),
		esc_url( add_query_arg( 'support-sytem-page', min( $total_pages, $current+1 ), $current_url ) ),
		'&rsaquo;'
	);

	$page_links[] = sprintf( "<li><a class='%s' title='%s' href='%s'>%s</a></li>",
		'last-page' . $disable_last,
		esc_attr__( 'Go to the last page' ),
		esc_url( add_query_arg( 'support-sytem-page', $total_pages, $current_url ) ),
		'&raquo;'
	);

	$pagination_links_class = 'pagination';

	$output .= "\n<ul class='$pagination_links_class'>" . join( "\n", $page_links ) . '</ul>';

	if ( $total_pages ) {
		$page_class = $total_pages < 2 ? ' one-page' : '';
	} else {
		$page_class = ' no-pages';
	}
	$output = "<div class='support-system-pagination'>$output</div>";

	echo $output;
}

