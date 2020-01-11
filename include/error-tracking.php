<?php 

/**
 * Stores error messages
 *
 * @access      public
 * @since       1.0
 */
function idra_errors() {
    static $wp_error; // Will hold global variable safely
    return isset( $wp_error ) ? $wp_error : ( $wp_error = new WP_Error( NULL, NULL, NULL ) );
}

/**
 * Retrieves the HTML for error messages
 *
 * @access      public
 * @since       4.0.0
 */
function idra_get_error_messages_html( $error_id = '' ) {

	$html   = '';
	$errors = idra_errors()->get_error_codes();

	if( $errors ) {
		
		$html .= '<div class="lpaywall_message error">';
		// Loop error codes and display errors
		foreach( $errors as $code ) {

			if( idra_errors()->get_error_data( $code ) == $error_id ) {

				$message = idra_errors()->get_error_message( $code );

				$html .= '<p class="lpaywall_error ' . esc_attr( $code ) . '"><span>' . $message . '</span></p>';

			}

		}

		$html .= '</div>';

	}

	return apply_filters( 'ice_dragon_paywall_error_messages_html', $html, $errors );

}

/**
 * Displays the HTML for error messages
 *
 * @access      public
 * @since       4.0.0
 */
function idra_show_error_messages( $error_id = '' ) {

	if( idra_errors()->get_error_codes() ) {

		do_action( 'idra_errors_before' );
		echo idra_get_error_messages_html( $error_id );
		do_action( 'idra_errors_after' );

	}
}