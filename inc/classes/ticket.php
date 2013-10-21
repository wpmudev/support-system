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

	public $replies = array();



	public static function get_instance( $ticket_id, $add_replies = false ) {

		$ticket_id = absint( $ticket_id );
		if ( ! $ticket_id )
			return false;
		
		$model = incsub_support_get_ticket_model();

		$_ticket = $model->get_ticket( $ticket_id );

		if ( ! $_ticket )
			return false;

		$_ticket = new self( $_ticket );

		return $_ticket;

	}

	public function __construct( $ticket ) {
		foreach ( get_object_vars( $ticket ) as $key => $value )
			$this->$key = $value;
	}

	public function get_staff_name() {
		$user = get_userdata( $this->admin_id );

		if ( ! $user )
			return __( 'Not yet assigned', INCSUB_SUPPORT_LANG_DOMAIN );

		return $user->display_name;
	}

	public function get_category_name() {
		$model = incsub_support_get_ticket_model();

		return $model->get_ticket_category_name_beta( $this->cat_id );
	}

	public function delete() {
		$model = incsub_support_get_ticket_model();

		if ( $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->delete_ticket( $this->ticket_id );

    }

    public function open() {
    	$model = incsub_support_get_ticket_model();

    	if ( $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->open_ticket( $this->ticket_id );
    }

    public function close() {
    	$model = incsub_support_get_ticket_model();

    	if ( ! $model->is_ticket_archived( absint( $this->ticket_id ) ) )
            $model->close_ticket( $this->ticket_id );
    }

}