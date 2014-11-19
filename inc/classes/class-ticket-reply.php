<?php

class Incsub_Support_Ticket_Reply {

	public $message_id = 0;

	public $site_id = 1;

	public $ticket_id = 0;

	public $user_id = 0;

	public $admin_id = 0;

	public $message_date = '0000-00-00 00:00:00';

	public $subject = '';

	private $message = '';

	public $attachments = array();

	public $is_main_reply = false;

	public static function get_instance( $ticket_reply_id ) {
		global $wpdb, $current_site;

		if ( is_object( $ticket_reply_id ) )
			return new self( $ticket_reply_id );

		$ticket_reply_id = absint( $ticket_reply_id );
		if ( ! $ticket_reply_id )
			return false;
		
		$tickets_replies_table = incsub_support()->model->tickets_messages_table;

		$_reply = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM $tickets_replies_table
				WHERE message_id = %d
				LIMIT 1",
				$ticket_reply_id
			)
		);	

		if ( ! $_reply )
			return false;

		$_reply = new self( $_reply );

		return $_reply;

	}

	public function __construct( $ticket ) {
		foreach ( get_object_vars( $ticket ) as $key => $value ) {
			if ( $key === 'attachments' ) {
				$value = maybe_unserialize( $value );
			}

			$this->$key = $value;
		}
	}

	public function __get( $name ) {
		if ( $name == 'message' ) {
			return apply_filters( 'the_content', $this->message, 0 );
		}
	}

	public function get_poster_id() {
		if ( $this->user_id )
			return absint( $this->user_id );

		if ( $this->admin_id )
			return absint( $this->admin_id );
		
		return 0;
	}

}