<?php
/**
 * @package zeen101's Leaky Paywall
 * @since 1.0.0
 */

function ice_dragon_bartag_func( $atts ) {
    $a = shortcode_atts( array(
        'foo' => 'something',
        'bar' => 'something else',
    ), $atts );

    ob_start(); ?>
    
    	<h2>html goes here</h2>
    
    <?php  $content = ob_get_contents();
	ob_end_clean();

	return $content; 
}
add_shortcode( 'ice_dragon_bartag', 'ice_dragon_bartag_func' );