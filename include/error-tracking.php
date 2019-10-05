<?php 

/**
 * Stores error messages
 *
 * @access      public
 * @since       1.0
 */
if ( !function_exists( 'ice_dragon_paywall_errors' ) ) {
	function ice_dragon_paywall_errors() {
	    static $wp_error; // Will hold global variable safely
	    return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( NULL, NULL, NULL ) );
	}
}

/**
 * Retrieves the HTML for error messages
 *
 * @access      public
 * @since       4.0.0
 */
function ice_dragon_paywall_get_error_messages_html( $error_id = '' ) {

	$html   = '';
	$errors = leaky_paywall_errors()->get_error_codes();

	if( $errors ) {
		
		$html .= '<div class="leaky_paywall_message error">';
		// Loop error codes and display errors
		foreach( $errors as $code ) {

			if( leaky_paywall_errors()->get_error_data( $code ) == $error_id ) {

				$message = leaky_paywall_errors()->get_error_message( $code );

				$html .= '<p class="leaky_paywall_error ' . esc_attr( $code ) . '"><span>' . $message . '</span></p>';

			}

		}

		$html .= '</div>';

	}

	return apply_filters( 'leaky_paywall_error_messages_html', $html, $errors );

}

/**
 * Displays the HTML for error messages
 *
 * @access      public
 * @since       4.0.0
 */
function ice_dragon_paywall_show_error_messages( $error_id = '' ) {

	if( leaky_paywall_errors()->get_error_codes() ) {

		do_action( 'ice_dragon_paywall_errors_before' );
		echo ice_dragon_paywall_get_error_messages_html( $error_id );
		do_action( 'ice_dragon_paywall_errors_after' );

	}
}