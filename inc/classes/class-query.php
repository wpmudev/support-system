<?php

class Incsub_Support_Query {

	/**
	 * Template tags
	 */
	public $is_support_system = false;
	public $is_tickets_index = false;
	public $is_single_ticket = false;
	public $is_search = false;
	public $is_tickets_search = false;
	public $is_faqs_search = false;
	public $is_submit_ticket_page = false;
	public $is_faqs_index = false;

	/**
	 * Ticket properties
	 */
	public $ticket_id = 0;
	public $ticket = false;
	public $found_tickets = 0;
	public $current_ticket = -1;
	public $remaining_tickets = 0; 
	public $ticket_category_id = 0;
	public $tickets_search = false;

	/**
	 * Pagination
	 * @var integer
	 */
	public $tickets_page = 1;
	
	/**
	 * FAQs properties
	 */
	public $faq_id = 0;
	public $faq = false;
	public $found_faqs = 0;
	public $current_faq = -1;
	public $remaining_faqs = 0; 
	public $faq_category_id = 0;
	public $faqs_search = false;


	public function __construct() {
		// Create an empty object in case there are no results
		$this->ticket = new Incsub_Support_Ticket( new stdClass() );

		$this->faq = new Incsub_Support_FAQ( new stdClass() );
		add_filter( 'wp_title', array( $this, 'set_wp_title' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'query' ) );
	}

	public function query() {
		$this->parse();

		if ( ! $this->is_support_system )
			return;

		/**
		 * Filters the number of tickets per page to be displayed in the front
		 * 
		 * @param Integer $tickets_per_page Tickets per page number. Default is posts_per_page option in wp_options table
		 * @param Object $this Current Incsub_Support_Query Object
		 */
		$per_page = apply_filters( 'support_system_query_per_page', get_option( 'posts_per_page' ), $this );

		if ( $this->is_single_ticket() ) {
			$ticket = incsub_support_get_ticket( $this->ticket_id );
			if ( $ticket ) {
				$this->found_tickets = 1;
				$this->tickets = array( $ticket );
				$this->ticket = $ticket;
			}

			$this->remaining_tickets = count( $this->tickets );

		}
		elseif ( $this->is_tickets_page() ) {
			$args = array(
				'per_page' => $per_page,
				'page' => $this->tickets_page
			);

			if ( $this->ticket_category_id )
				$args['category'] = $this->ticket_category_id;

			if ( stripslashes( $this->tickets_search ) )
				$args['s'] = stripslashes( $this->tickets_search );

			/**
			 * Filters the Tickets query Query arguments in the frontend
			 * 
			 * @param Array $args Query arguments that will be passed to incsub_support_get_tickets function
			 * @param Object $this Current Incsub_Support_Query Object
			 */
			$args = apply_filters( 'support_system_query_get_tickets_args', $args, $this );
			
			$this->tickets = incsub_support_get_tickets( $args );
			$this->found_tickets = incsub_support_get_tickets_count( $args );
			$this->total_pages = ceil( $this->found_tickets / $per_page );

			$this->remaining_tickets = count( $this->tickets );
		}
		
		if ( $this->is_faqs_page() ) {
			$args = array(
				'per_page' => -1
			);

			if ( $this->faq_category_id )
				$args['category'] = $this->faq_category_id;

			if ( stripslashes( $this->faqs_search ) )
				$args['s'] = stripslashes( $this->faqs_search );

			/**
			 * Filters the FAQs query Query arguments in the frontend
			 * 
			 * @param Array $args Query arguments that will be passed to incsub_support_get_faqs function
			 * @param Object $this Current Incsub_Support_Query Object
			 */
			$args = apply_filters( 'support_system_query_get_faqs_args', $args, $this );
			$this->faqs = incsub_support_get_faqs( $args ); 
			$this->found_faqs = incsub_support_get_faqs_count( $args );
			$this->total_pages = ceil( $this->found_faqs / $per_page );

			$this->remaining_faqs = count( $this->faqs );
		}

	}


	public function parse() {
		$settings = incsub_support_get_settings();

		if ( is_multisite() && get_current_blog_id() != $settings['incsub_support_blog_id'] )
			return;

		$post_id = get_the_ID();
		
		$ticket_id = $this->get_query_var( 'tid' );

		if ( $ticket_id && incsub_support_get_support_page_id() ) {
			// Single ticket page
			$this->ticket_id = absint( $ticket_id );
			$this->is_single_ticket = true;
			$this->is_support_system = true;
			
		}
		elseif ( $post_id == incsub_support_get_support_page_id() ) {
			// Tickets index page
			$this->is_tickets_index = true;
			$this->is_support_system = true;

			if ( $cat_id = $this->get_query_var( 'ticket-cat-id' ) ) {
				$this->ticket_category_id = absint( $cat_id );
			}
			
			if ( $s = $this->get_query_var( 'support-system-ticket-s' ) ) {
				$this->is_search = true;
				$this->search = $s;
				
				$this->is_tickets_search = true;
				$this->tickets_search = $s;
			}
			
		}
		
		if ( $post_id == incsub_support_get_faqs_page_id() && ! $this->is_single_ticket() ) {
			// FAQs Page
			$this->is_faqs_index = true;
			$this->is_support_system = true;

			if ( $cat_id = $this->get_query_var( 'faq-cat-id' ) ) {
				$this->faq_category_id = absint( $cat_id );
			}

			if ( $s = $this->get_query_var( 'support-system-faq-s' ) ) {
				$this->is_faqs_search = true;
				$this->faqs_search = $s;
			}	
			
		}

		if ( $post_id == incsub_support_get_new_ticket_page_id() ) {
			// Ticket form Page
			$this->is_submit_ticket_page = true;
			$this->is_support_system = true;				
		}

		$page = $this->get_query_var( 'support-system-page' );
		if ( ! empty( $page ) )
			$this->tickets_page = absint( $page );

		do_action_ref_array( 'support_system_parse_query', array( &$this ) );

	}

	public function get_query_var( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			$value = $_REQUEST[ $name ];
			return $value;
		}

		return false;
	}


	public function the_ticket() {
		return $this->next_ticket();
	}

	public function the_faq() {
		return $this->next_faq();
	}

	public function next_ticket() {
		$this->current_ticket++;
		$this->ticket = $this->tickets[ $this->current_ticket ];
		$this->remaining_tickets--;

		return $this->ticket;
	}

	public function next_faq() {
		$this->current_faq++;
		$this->faq = $this->faqs[ $this->current_faq ];
		$this->remaining_faqs--;

		return $this->faq;
	}

	public function set_wp_title( $title, $sep = '' ) {
		if ( $this->is_single_ticket() ) {
			$title .= ' ' . $sep . ' ' . $this->ticket->title ;
		}

		return $title;
	}

	public function is_support_system() {
		return $this->is_support_system;
	}

	public function is_faqs_page() {
		return $this->is_support_system && $this->is_faqs_index;
	}

	public function is_tickets_page() {
		return $this->is_support_system && $this->is_tickets_index;	
	}

	public function is_new_ticket_page() {
		return $this->is_support_system && $this->is_submit_ticket_page;	
	}

	public function is_single_ticket() {
		return $this->is_support_system && $this->is_single_ticket;	
	}

}

function is_support_system() {
	return incsub_support()->query->is_support_system();
}

function incsub_support_is_tickets_page() {
	return incsub_support()->query->is_tickets_page();
}

function incsub_support_is_faqs_page() {
	return incsub_support()->query->is_faqs_page();
}

function incsub_support_is_new_ticket_page() {
	return incsub_support()->query->is_new_ticket_page();
}

function incsub_support_is_single_ticket() {
	return incsub_support()->query->is_single_ticket();
}

function incsub_support_the_ticket() {
	incsub_support()->query->the_ticket();
}

function incsub_support_is_ticket_closed( $ticket_id = false ) {
	if ( $ticket_id ) {
		$ticket = incsub_support_get_ticket( $ticket_id );
		if ( $ticket )
			return $ticket->is_closed();

		return false;
	}

	return incsub_support()->query->ticket->is_closed();
}

function incsub_support_has_tickets() {
	if ( incsub_support()->query->current_ticket === -1 ) {
		// The loop hasn't started yet
		return (bool)incsub_support()->query->found_tickets;
	}
	else {
		return (bool)incsub_support()->query->remaining_tickets;
	}
}

function incsub_support_the_tickets_number() {
	return incsub_support()->query->found_tickets;
}

function incsub_support_get_the_ticket_id() {
	return incsub_support()->query->ticket->ticket_id;
}

function incsub_support_get_the_ticket_class() {
	$ticket = incsub_support()->query->ticket;

	$class = array();
	$class[] = "support-system-ticket-priority-" . $ticket->ticket_priority;
	$class[] = "support-system-ticket-category-" . $ticket->cat_id;
	$class[] = "support-system-ticket-staff-" . $ticket->admin_id;
	$class[] = "support-system-ticket-status-" . $ticket->ticket_status;

	/**
	 * Filters the HTML ticket class in the frontend
	 * 
	 * @param String $classes Ticket HTML classes
	 */
	return apply_filters( 'support_system_the_ticket_class', implode( ' ', $class ) );
}

function incsub_support_get_the_tickets_search_query() {
	return empty( incsub_support()->query->tickets_search ) ? '' : incsub_support()->query->tickets_search;
}


function incsub_support_get_queried_ticket_category_id() {
	return incsub_support()->query->ticket_category_id;
}


function incsub_support_get_the_ticket_title() {
	return incsub_support()->query->ticket->title;
}

function incsub_support_get_the_ticket_replies_number() {
	return absint( incsub_support()->query->ticket->num_replies );
}

function incsub_support_get_the_last_ticket_reply_url() {
	$ticket = incsub_support()->query->ticket;

	$url = incsub_support_get_the_ticket_permalink();
	$replies = $ticket->get_replies();
	$last_reply = end( $replies );
	$last_reply_id = $last_reply->message_id;
	reset( $replies );

	$url .= '#support-system-reply-' . $last_reply_id;

	return $url;
}

function incsub_support_get_the_ticket_updated_date() {
	$ticket = incsub_support()->query->ticket;
	return incsub_support_get_translated_date( $ticket->ticket_updated, true );
}

function incsub_support_get_the_ticket_date() {
	$ticket = incsub_support()->query->ticket;

	$human_read = false;
	$date = incsub_support_get_translated_date( $ticket->ticket_opened, $human_read );

	/**
	 * Filters the current ticket date in the front end
	 * 
	 * @param String $date Ticket date
	 * @param Object $ticket Current Incsub_Support_Ticket Object
	 * @param Object $human_read If the date is human readable
	 */
	return apply_filters( 'support_system_the_ticket_date', $date, $ticket, $human_read );
}

function incsub_support_get_the_author_id() {
	return incsub_support()->query->ticket->user_id;
}

function incsub_support_get_the_author() {
	$user = get_userdata( incsub_support_get_the_author_id() );
	if ( ! $user )
		return __( 'Unknown user', INCSUB_SUPPORT_LANG_DOMAIN );

	return $user->data->display_name;
}

function incsub_support_get_the_ticket_message() {
	return incsub_support()->query->ticket->message;
}

function incsub_support_get_the_ticket_excerpt() {
	$message = incsub_support_get_the_ticket_message();
	return wpautop( wp_trim_words( $message, 40, ' [...]' ) );
	
}

function incsub_support_has_replies() {
	return ( count( incsub_support()->query->ticket->get_replies() ) > 1 );
}

function incsub_support_get_the_ticket_category() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->ticket->cat_id );
	return $cat->cat_name;
}

function incsub_support_get_the_ticket_category_id() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->ticket->cat_id );
	return $cat->cat_id;	
}

function incsub_support_get_the_ticket_category_link() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->ticket->cat_id );
	$url = add_query_arg( 'ticket-cat-id', $cat->cat_id );
	$url = remove_query_arg( 'support-system-ticket-s', $url );
	$url = remove_query_arg( 'support-sytem-page', $url );

	return '<a href="' . esc_url( $url ) . '">' . $cat->cat_name . '</a>';
}


function incsub_support_get_the_ticket_priority() {
	return incsub_support_get_ticket_priority_name( incsub_support()->query->ticket->ticket_priority );
}

function incsub_support_get_the_ticket_priority_id() {
	return incsub_support()->query->ticket->ticket_priority;
}

function incsub_support_get_the_ticket_status() {
	return incsub_support_get_ticket_status_name( incsub_support()->query->ticket->ticket_status );
}

function support_system_the_tickets_number() {
	return incsub_support()->query->found_tickets;
}


function incsub_support_the_ticket_staff_name() {
	return incsub_support()->query->ticket->get_staff_name();
}

function incsub_support_the_ticket_staff_login() {
	return incsub_support()->query->ticket->get_staff_login();
}






/** FAQS **/

function incsub_support_get_the_faq_class() {
	$ticket = incsub_support()->query->faq;

	$class = array();
	$class[] = "support-system-faq-category-" . $ticket->cat_id;

	/**
	 * Filters the HTML ticket class in the frontend
	 * 
	 * @param String $classes Ticket HTML classes
	 */
	return apply_filters( 'support_system_the_faq_class', implode( ' ', $class ) );
}

function incsub_support_has_faqs() {
	if ( incsub_support()->query->current_faq === -1 ) {
		// The loop hasn't started yet
		return (bool)incsub_support()->query->found_faqs;
	}
	else {
		return (bool)incsub_support()->query->remaining_faqs;
	}
}

function incsub_support_the_faq() {
	incsub_support()->query->the_faq();
}

function incsub_support_the_faqs_number() {
	return incsub_support()->query->found_faqs;
}

function incsub_support_get_the_faq_id() {
	return incsub_support()->query->faq->faq_id;
}

function incsub_support_get_the_faq_question() {
	return incsub_support()->query->faq->question;
}

function incsub_support_get_the_faq_answer() {
	return do_shortcode( incsub_support()->query->faq->answer );
}

function incsub_support_get_the_faq_category_link() {
	$cat = incsub_support_get_faq_category( incsub_support()->query->faq->cat_id );
	$url = add_query_arg( 'cat-id', $cat->cat_id );
	$url = remove_query_arg( 'support-system-s', $url );

	return '<a href="' . esc_url( $url ) . '">' . $cat->cat_name . '</a>';
}

function incsub_support_get_queried_faq_category_id() {
	return incsub_support()->query->faq_category_id;
}

function incsub_support_get_the_faqs_search_query() {
	return empty( incsub_support()->query->faqs_search ) ? '' : incsub_support()->query->faqs_search;	
}