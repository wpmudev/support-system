<?php

function incsub_support_get_model() {
	return MU_Support_System_Model::get_instance();
}

function incsub_support_get_ticket_status_name( $status_id ) {
	return MU_Support_System::$ticket_status[ $status_id ];
}

function incsub_support_get_ticket_priority_name( $priority_id ) {
	return MU_Support_System::$ticket_priority[ $priority_id ];
}

/**
 * Translate dates
 * 
 * @param string $date The date
 * @return string Date
 */
function incsub_support_get_translated_date($date){
	// get the date from gmt date in Y-m-d H:i:s
	$date_in_gmt = get_date_from_gmt($date);
	//get it localised
	$transl_date = mysql2date( get_option("date_format") ." ". get_option("time_format"), $date_in_gmt, true );
	return $transl_date;
}