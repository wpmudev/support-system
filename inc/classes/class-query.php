<?php

class Incsub_Support_Query {

	public $items = array();

	private $args = array();

	public $is_support_system = false;
	public $is_tickets_index = false;
	public $is_single_ticket = false;
	public $is_search = false;
	public $is_submit_ticket_page = false;
	public $is_faqs_page = false;

	public $found_items = 0;
	public $found_replies = 0;

	public $page = 1;
	public $current_item = -1;
	public $category_id = 0;
	public $remaining_items = 0; 
	public $search = false;

	public function __construct() {
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

		if ( $this->is_single_ticket ) {
			$ticket = incsub_support_get_ticket( $this->item_id );
			if ( $ticket ) {
				$this->found_items = 1;
				$this->items = array( $ticket );
				$this->item = $ticket;
			}
		}
		elseif ( $this->is_tickets_index ) {
			$args = array(
				'per_page' => $per_page,
				'page' => $this->page,
				'status' => 'open'
			);

			if ( $this->category_id )
				$args['category'] = $this->category_id;

			if ( stripslashes( $this->search ) )
				$args['s'] = stripslashes( $this->search );

			/**
			 * Filters the Tickets query Query arguments in the frontend
			 * 
			 * @param Array $args Query arguments that will be passed to incsub_support_get_tickets function
			 * @param Object $this Current Incsub_Support_Query Object
			 */
			$args = apply_filters( 'support_system_query_get_tickets_args', $args, $this );
			$this->items = incsub_support_get_tickets( $args );
			$this->found_items = incsub_support_get_tickets_count( $args );
			$this->total_pages = ceil( $this->found_items / $per_page );
		}
		elseif ( $this->is_faqs_page ) {
			$args = array(
				'per_page' => -1
			);

			if ( $this->category_id )
				$args['category'] = $this->category_id;

			if ( stripslashes( $this->search ) )
				$args['s'] = stripslashes( $this->search );

			/**
			 * Filters the FAQs query Query arguments in the frontend
			 * 
			 * @param Array $args Query arguments that will be passed to incsub_support_get_faqs function
			 * @param Object $this Current Incsub_Support_Query Object
			 */
			$args = apply_filters( 'support_system_query_get_faqs_args', $args, $this );
			$this->items = incsub_support_get_faqs( $args ); 
			$this->found_items = incsub_support_get_faqs_count( $args );
			$this->total_pages = ceil( $this->found_items / $per_page );
		}

		$this->remaining_items = count( $this->items );

	}


	public function parse() {
		$settings = incsub_support_get_settings();

		if ( is_multisite() && get_current_blog_id() != $settings['incsub_support_blog_id'] )
			return;

		$ticket_id = $this->get_query_var( 'tid' );
		if ( $ticket_id && is_page( $settings['incsub_support_support_page'] ) ) {
			$this->item_id = absint( $ticket_id );
			$this->is_single_ticket = true;
			$this->is_support_system = true;
		}
		elseif ( is_page( $settings['incsub_support_support_page'] ) ) {
			$this->is_tickets_index = true;
			if ( $cat_id =$this->get_query_var( 'cat-id' ) ) {
				$this->category_id = absint( $cat_id );
			}
			
			if ( $s = $this->get_query_var( 'support-system-s' ) ) {
				$this->is_search = true;
				$this->search = $s;
			}
			$this->is_support_system = true;
		}
		elseif( is_page( $settings['incsub_support_create_new_ticket_page'] ) ) {
			$this->is_submit_ticket_page = true;
			$this->is_support_system = true;
		}
		elseif( is_page( $settings['incsub_support_faqs_page'] ) ) {
			$this->is_faqs_page = true;
			$this->is_support_system = true;
			if ( $cat_id =$this->get_query_var( 'cat-id' ) ) {
				$this->category_id = absint( $cat_id );
			}

			if ( $s = $this->get_query_var( 'support-system-s' ) ) {
				$this->is_search = true;
				$this->search = $s;
			}
		}

		$page = $this->get_query_var( 'support-system-page' );
		if ( ! empty( $page ) )
			$this->page = absint( $page );

	}

	public function get_query_var( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			$value = $_REQUEST[ $name ];
			return $value;
		}

		return false;
	}


	public function the_ticket() {
		$ticket = $this->next_ticket();
		return $ticket;
	}

	public function next_ticket() {
		$this->current_item++;
		$this->item = $this->items[ $this->current_item ];
		$this->remaining_items--;
		return $this->item;
	}

	public function set_wp_title( $title, $sep = '' ) {
		if ( $this->is_single_ticket ) {
			$title .= ' ' . $sep . ' ' . $this->item->title ;
		}

		return $title;
	}

}

function is_support_system() {
	return incsub_support()->query->is_support_system;
}

function incsub_support_the_ticket() {
	incsub_support_the_item();
}

function incsub_support_is_ticket_closed( $ticket_id = false ) {
	return incsub_support()->query->item->is_closed();
}

function incsub_support_has_tickets() {
	return incsub_support_has_items();
}

function incsub_support_get_the_ticket_id() {
	return incsub_support()->query->item->ticket_id;
}

function incsub_support_get_the_ticket_class() {
	$ticket = incsub_support()->query->item;

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

function incsub_support_get_the_ticket_permalink() {
	$ticket = incsub_support()->query->item;
	$url = add_query_arg( 'tid', $ticket->ticket_id );
	return $url;
}

function incsub_support_get_the_ticket_title() {
	return incsub_support()->query->item->title;
}

function incsub_support_get_the_ticket_replies_number() {
	return absint( incsub_support()->query->item->num_replies );
}

function incsub_support_get_the_last_ticket_reply_url() {
	$ticket = incsub_support()->query->item;

	$url = incsub_support_get_the_ticket_permalink();
	$replies = $ticket->get_replies();
	$last_reply = end( $replies );
	$last_reply_id = $last_reply->message_id;
	reset( $replies );

	$url .= '#support-system-reply-' . $last_reply_id;

	return $url;
}

function incsub_support_get_the_ticket_updated_date() {
	$ticket = incsub_support()->query->item;
	return incsub_support_get_translated_date( $ticket->ticket_updated, true );
}

function incsub_support_get_the_ticket_date() {
	$ticket = incsub_support()->query->item;

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

function incsub_support_is_single_ticket() {
	return incsub_support()->query->is_single_ticket;
}

function incsub_support_is_tickets_index() {
	return incsub_support()->query->is_tickets_index;
}

function incsub_support_get_the_author_id() {
	return incsub_support()->query->item->user_id;
}

function incsub_support_get_the_author() {
	$user = get_userdata( incsub_support_get_the_author_id() );
	if ( ! $user )
		return __( 'Unknown user', INCSUB_SUPPORT_LANG_DOMAIN );

	return $user->data->user_nicename;
}

function incsub_support_get_the_ticket_message() {
	return incsub_support()->query->item->message;
}

function incsub_support_get_the_ticket_excerpt() {
	$message = incsub_support_get_the_ticket_message();
	return wpautop( wp_trim_words( $message, 40, ' [...]' ) );
	
}

function incsub_support_has_replies() {
	return ( count( incsub_support()->query->item->get_replies() ) > 1 );
}

function incsub_support_get_the_ticket_category() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->item->cat_id );
	return $cat->cat_name;
}

function incsub_support_get_the_ticket_category_id() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->item->cat_id );
	return $cat->cat_id;	
}

function incsub_support_get_the_ticket_category_link() {
	$cat = incsub_support_get_ticket_category( incsub_support()->query->item->cat_id );
	$url = add_query_arg( 'cat-id', $cat->cat_id );
	$url = remove_query_arg( 'support-system-s', $url );
	$url = remove_query_arg( 'support-sytem-page', $url );

	return '<a href="' . esc_url( $url ) . '">' . $cat->cat_name . '</a>';
}


function incsub_support_get_the_ticket_priority() {
	return incsub_support_get_ticket_priority_name( incsub_support()->query->item->ticket_priority );
}

function incsub_support_get_the_ticket_priority_id() {
	return incsub_support()->query->item->ticket_priority;
}

function incsub_support_get_the_ticket_status() {
	return incsub_support_get_ticket_status_name( incsub_support()->query->item->ticket_status );
}

function support_system_the_tickets_number() {
	return incsub_support_the_items_number();
}


function incsub_support_the_ticket_staff_name() {
	return incsub_support()->query->item->get_staff_name();
}

/** GENERIC FUNCTIONS */
function incsub_support_the_items_number() {
	return incsub_support()->query->found_items;
}

function incsub_support_has_items() {
	if ( incsub_support()->query->current_item === -1 ) {
		// The loop hasn't started yet
		return (bool)incsub_support()->query->found_items;
	}
	else {
		return (bool)incsub_support()->query->remaining_items;
	}
}

function incsub_support_the_item() {
	incsub_support()->query->the_ticket();
}


/** FAQS **/

function incsub_support_get_the_faq_class() {
	$ticket = incsub_support()->query->item;

	$class = array();
	$class[] = "support-system-faq-category-" . $ticket->cat_id;

	/**
	 * Filters the HTML ticket class in the frontend
	 * 
	 * @param String $classes Ticket HTML classes
	 */
	return apply_filters( 'support_system_the_faq_class', implode( ' ', $class ) );
}

function incsub_support_get_the_faq_id() {
	return incsub_support()->query->item->faq_id;
}

function incsub_support_get_the_faq_question() {
	return incsub_support()->query->item->question;
}

function incsub_support_get_the_faq_answer() {
	return incsub_support()->query->item->answer;
}

function incsub_support_get_the_faq_category_link() {
	$cat = incsub_support_get_faq_category( incsub_support()->query->item->cat_id );
	$url = add_query_arg( 'cat-id', $cat->cat_id );
	$url = remove_query_arg( 'support-system-s', $url );

	return '<a href="' . esc_url( $url ) . '">' . $cat->cat_name . '</a>';
}