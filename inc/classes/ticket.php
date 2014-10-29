<?php


class Incsub_Support_Ticket {

	public $ticket_id;

	public $site_id;

	public $blog_id = 0;

	public $cat_id = 0;

	public $user_id = 0;

	public $admin_id = 0;

	public $last_reply_id = 0;

	public $ticket_type = 1;

	public $ticket_priority = 0;

	public $ticket_status = 3;

	public $ticket_opened = '0000-00-00 00:00:00';

	public $ticket_updated = '0000-00-00 00:00:00';

	public $num_replies = 0;

	public $title = '';

	public $view_by_superadmin = 0;

	private $replies = array();

	public $category = false;



	public static function get_instance( $ticket_id ) {
		global $wpdb, $current_site;

		if ( is_object( $ticket_id ) )
			return new self( $ticket_id );

		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id )
			return false;
		
		$tickets_table = incsub_support()->model->tickets_table;
		$current_site_id = ! empty ( $current_site ) ? $current_site->id : 1;

		$_ticket = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT * FROM $tickets_table
				WHERE site_id = %d
				AND ticket_id = %d
				LIMIT 1",
				$current_site_id,
				$ticket_id
			)
		);	

		if ( ! $_ticket )
			return false;

		$_ticket = new self( $_ticket );

		return $_ticket;

	}

	public function __construct( $ticket ) {
		foreach ( get_object_vars( $ticket ) as $key => $value )
			$this->$key = $value;

		if ( $this->cat_id )
			$this->category = incsub_support_get_ticket_category( $this->cat_id );
	}

	public function get_staff_name() {
		$user = get_userdata( $this->admin_id );

		if ( ! $user )
			return __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN );

		return $user->display_name;
	}

	public function get_category_name() {
		if ( ! is_object( $this->category ) )
			return false;

		return $this->category->cat_name;
	}


	public function delete() {
		$model = incsub_support_get_model();

		if ( $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->delete_ticket( $this->ticket_id );

    }

    public function open() {
    	$model = incsub_support_get_model();

    	if ( $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->open_ticket( $this->ticket_id );
    }

    public function close() {
    	$model = incsub_support_get_model();

    	if ( ! $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->close_ticket( $this->ticket_id );
    }

}