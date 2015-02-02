<?php


class Incsub_Support_Ticket {

	public $ticket_id;

	public $site_id;

	public $blog_id = 0;

	public $cat_id = 0;

	public $user_id = 0;

	public $admin_id = 0;

	private $last_reply_id = 0;

	public $ticket_type = 1;

	public $ticket_priority = 0;

	public $ticket_status = 3;

	public $ticket_opened = '0000-00-00 00:00:00';

	public $ticket_updated = '0000-00-00 00:00:00';

	public $num_replies = 0;

	public $title = '';

	public $view_by_superadmin = 0;

	private $replies = null;

	private $message = '';

	public $category = false;



	public static function get_instance( $ticket_id ) {
		global $wpdb, $current_site;

		if ( is_object( $ticket_id ) ) {
			$_ticket = new self( $ticket_id );
			$_ticket = incsub_support_sanitize_ticket_fields( $_ticket );
			return $_ticket;
		}

		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id )
			return false;
		
		$tickets_table = incsub_support()->model->tickets_table;

		$_ticket = wp_cache_get( $ticket_id, 'support_system_tickets' );

		if ( ! $_ticket ) {

			$_ticket = $wpdb->get_row( 
				$wpdb->prepare( 
					"SELECT * FROM $tickets_table
					WHERE ticket_id = %d
					LIMIT 1",
					$ticket_id
				)
			);	

			if ( ! $_ticket )
				return false;

			wp_cache_add( $_ticket->ticket_id, $_ticket, 'support_system_tickets' );
		}

		if ( ! $_ticket )
			return false;

		$_ticket = new self( $_ticket );

		$_ticket = incsub_support_sanitize_ticket_fields( $_ticket );

		return $_ticket;

	}

	public function __construct( $ticket ) {
		foreach ( get_object_vars( $ticket ) as $key => $value ) {			
			$this->$key = $value;
		}

		if ( $this->cat_id )
			$this->category = incsub_support_get_ticket_category( $this->cat_id );
	}

	public function __get( $name ) {
		if ( 'last_reply_user_id' === $name ) {
			$replies = $this->get_replies();
			end( $replies );
			$last_reply = current( $replies );
			if ( ! $last_reply->user_id && ! $last_reply->admin_id ) {
				return false;
			}
			elseif( $last_reply->user_id ) {
				return $last_reply->user_id;
			}
			elseif( $last_reply->admin_id ) {
				return $last_reply->admin_id;
			}
		}

		if ( 'message' === $name ) {
			if ( ! empty( $this->message ) )
				return $this->message;

			$replies = $this->get_replies();
			if ( ! empty( $replies ) )
				$this->message = $this->replies[0]->message;

			return $this->message;

		}

		if ( 'last_reply_id' === $name ) {
			$replies = $this->get_replies();
			unset( $replies[0] );
			if ( ! empty( $replies ) ) {
				$this->last_reply_id = end( $replies )->message_id;
				reset( $this->replies );
			}

			return $this->last_reply_id;
		}

		return false;
	}

	public function get_replies() {
		$this->replies = incsub_support_get_ticket_replies( $this->ticket_id );

		return $this->replies;
	}

	public function get_staff_name() {
		$user = get_userdata( $this->admin_id );

		if ( ! $user )
			return __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN );

		return $user->display_name;
	}

	public function get_staff_login() {
		$user = get_userdata( $this->admin_id );

		if ( ! $user )
			return false;

		return $user->user_login;
	}

	public function get_user_name() {
		$user = get_userdata( $this->user_id );

		if ( ! $user )
			return __( 'User not found', INCSUB_SUPPORT_LANG_DOMAIN );

		return $user->display_name;
	}

	public function get_category_name() {
		if ( ! is_object( $this->category ) )
			return false;

		return $this->category->cat_name;
	}


    public function is_closed() {
    	return $this->ticket_status == 5;
    }

}