<?php

function idra_general_metaboxes() {

    $hidden_post_types = apply_filters( 'ice_dragon_paywall_hidden_post_types_metaboxes', array( 'attachment', 'revision', 'nav_menu_item', 'lp_transaction', 'lp-coupons' ) );
    $post_types = get_post_types( array(), 'objects' );

    foreach ( $post_types as $post_type ) {

        if ( in_array( $post_type->name, $hidden_post_types ) )
            continue;

        add_meta_box( 'idra_content_visibility', __( 'Ice Dragon Paywall', 'issuem-leaky-paywall' ), 'idra_content_visibility', $post_type->name, 'side' );

    }

    do_action( 'idra_general_metaboxes' );

}
add_action( 'add_meta_boxes', 'idra_general_metaboxes' );


function idra_content_visibility( $post ) {

    $visibility = get_post_meta( $post->ID, '_puzzle_ice_dragon_paywall_visibility', true );

    $defaults = array(
        'visibility_type' 		=> 'default',
    );
    $visibility = wp_parse_args( $visibility, $defaults );

    echo '<label for="ice-dragon-visibility-type">' . sprintf( __( 'This %s should...', 'issuem-leaky-paywall' ), $post->post_type ) . '</label> ';
    echo '<select id="ice-dragon-visibility-type" name="ice_dragon_visibility_type">';
    echo '  <option value="default" ' . selected( $visibility['visibility_type'], 'default', true ) . '>' . __( "obey Ice Dragon defaults.", 'issuem-leaky-paywall' ) . '</option>';
    echo '  <option value="always" ' . selected( $visibility['visibility_type'], 'always', true ) . '>' . __( 'always show a paywall.', 'issuem-leaky-paywall' ) . '</option>';
    echo '  <option value="never" ' . selected( $visibility['visibility_type'], 'never', true ) . '>' . __( 'never show a paywall.', 'issuem-leaky-paywall' ) . '</option>';
    echo '</select>';

    wp_nonce_field( 'ice_dragon_content_visibility_meta_box', 'ice_dragon_content_visibility_meta_box_nonce' );
}


function idra_save_content_visibility( $post_id ) {

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
    if(isset($_POST['ice_dragon_visibility_type'])) {
        $visibility_type = trim(sanitize_text_field($_POST['ice_dragon_visibility_type']));
        update_post_meta($post_id, '_puzzle_ice_dragon_paywall_visibility', array('visibility_type' => $visibility_type));
    } else {
        delete_post_meta($post_id, '_puzzle_ice_dragon_paywall_visibility');
    }

}
add_action( 'save_post', 'idra_save_content_visibility' );
