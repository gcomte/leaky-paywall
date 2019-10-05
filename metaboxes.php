<?php
/**
 * Random metaboxes
 *
 * @package zeen101's Leaky Paywall
 * @since 2.0.0
 */

if ( !function_exists( 'leaky_paywall_general_metaboxes' ) ) {

	function leaky_paywall_general_metaboxes() {
	
		$hidden_post_types = apply_filters( 'leaky_paywall_hidden_post_types_metaboxes', array( 'attachment', 'revision', 'nav_menu_item', 'lp_transaction', 'lp-coupons' ) );
		$post_types = get_post_types( array(), 'objects' );
	
		foreach ( $post_types as $post_type ) {
		
			if ( in_array( $post_type->name, $hidden_post_types ) ) 
				continue;
				
			add_meta_box( 'leaky_paywall_content_visibility', __( 'Use Ice Dragon Paywall', 'issuem-leaky-paywall' ), 'leaky_paywall_content_visibility', $post_type->name, 'side' );
		
		}
		
		do_action( 'leaky_paywall_general_metaboxes' );
		
	}
	add_action( 'add_meta_boxes', 'leaky_paywall_general_metaboxes' );

}

if ( !function_exists( 'leaky_paywall_content_visibility' ) ) {

	function leaky_paywall_content_visibility( $post ) {
	
		$visibility = get_post_meta( $post->ID, '_issuem_leaky_paywall_visibility' );
        $showPaywall = ((!$visibility[0] || $visibility[0] === '0') ? false : true );

        $checked = ($showPaywall ? ' checked="checked"' : '');

        echo '<input id="use-ice-dragon-checkbox" type="checkbox" name="ice_dragon_post_visibility"' . $checked . '>';
        echo '<label for="use-ice-dragon-checkbox">' . sprintf( __( 'Show a paywall for this %s', 'issuem-leaky-paywall' ), $post->post_type ) . '</label>';

        wp_nonce_field( 'ice_dragon_content_visibility_meta_box', 'ice_dragon_content_visibility_meta_box_nonce' );
	}

}

if ( !function_exists( 'save_leaky_paywall_content_visibility' ) ) {

	function save_leaky_paywall_content_visibility( $post_id ) {
	
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['ice_dragon_content_visibility_meta_box_nonce'] ) ) {
			return;
		}
	
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['ice_dragon_content_visibility_meta_box_nonce'], 'ice_dragon_content_visibility_meta_box' ) ) {
			return;
		}
	
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
			
		// Check the user's permissions.
		if ( ! ( current_user_can( 'edit_pages', $post_id ) || current_user_can( 'edit_posts', $post_id ) ) ) {
			return;
		}
	
		/* OK, it's safe for us to save the data now. */
        $visibility = isset($_POST['ice_dragon_post_visibility']);
			
        update_post_meta( $post_id, '_issuem_leaky_paywall_visibility', $visibility );
	}
	add_action( 'save_post', 'save_leaky_paywall_content_visibility' );
	
}