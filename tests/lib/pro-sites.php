<?php

/**
 * Fake Class ProSites for integration tests
 */
class ProSites {

}

function is_pro_site( $blog_id, $level = '' ) {
	return true;
}

function psts_levels_select( $pro_sites_levels, $pro_sites_level ) {
	echo '';
}