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



