<?php

class Incsub_Support_Ticket_Message {

	public $ID;

	public $ticket_id = 0;

	public $user_id = 0;

	public $staff_id = 0;

	public $message_date = '0000-00-00 00:00:00';

	public $subject = '';

	public $content = '';

	public $attachments = array();

	public function __construct() {

	}


}