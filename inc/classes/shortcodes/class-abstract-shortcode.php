<?php

abstract class Incsub_Support_Shortcode {
	public abstract function render( $atts );

	public function end() {
		echo '</div><div style="clear:both"></div>';
		return ob_get_clean();
	}

	public function start() {
		echo '<div id="support-system">';
		ob_start();
	}

}