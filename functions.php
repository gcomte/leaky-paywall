<?php

require_once ('ice-dragon-constants.php');

/**
 * Helper function to get Ice Dragon settings for current site
 *
 * @return mixed Value set for the plugin options.
 */
function idra_get_ice_dragon_paywall_settings() {
    global $ice_dragon_paywall;
    return $ice_dragon_paywall->get_settings();
}

/**
 * @since 1.0.0
 *
 * @return mixed Value set for the plugin options.
 */
function idra_build_lpaywall_default_restriction_row($restriction=array(), $row_key='' ) {

    $settings = idra_get_ice_dragon_paywall_settings();

    if ( empty( $restriction ) ) {
        $restriction = array(
            'post_type' 	=> '',
            'taxonomy'	=> '',
            'allowed_value' => '0',
        );
    }

    if ( !isset( $restriction['taxonomy'] ) ) {
        $restriction['taxonomy'] = 'all';
    }

    // $return  = '<div class="issuem-leaky-paywall-restriction-row">';
    echo '<tr class="issuem-leaky-paywall-restriction-row">';
    $hidden_post_types = array( 'attachment', 'revision', 'nav_menu_item', 'lp_transaction', 'custom_css' );
    $post_types = get_post_types( array(), 'objects' );
    // $return .= '<label for="restriction-post-type-' . $row_key . '">' . __( 'Number of', 'leaky-paywall' ) . '</label> ';
    echo '<td><select class="leaky-paywall-restriction-post-type" id="restriction-post-type-' . $row_key . '" name="restrictions[post_types][' . $row_key . '][post_type]">';
    foreach ( $post_types as $post_type ) {

        if ( in_array( $post_type->name, $hidden_post_types ) ) {
            continue;
        }

        echo '<option value="' . $post_type->name . '" ' . selected( $post_type->name, $restriction['post_type'], false ) . '>' . $post_type->labels->name . '</option>';

    }

    echo '</select></td>';

    // get taxonomies for this post type
    echo '<td><select style="width: 100%;" name="restrictions[post_types][' . $row_key . '][taxonomy]">';
    $tax_post_type = $restriction['post_type'] ? $restriction['post_type'] : 'post';
    $taxes = get_object_taxonomies( $tax_post_type, 'objects' );
    $hidden_taxes = array( 'post_format' );

    echo '<option value="all" ' . selected( 'all', $restriction['taxonomy'], false ) . '>All</option>';

    foreach( $taxes as $tax ) {

        if ( in_array( $tax->name, $hidden_taxes ) ) {
            continue;
        }

        // create option group for this taxonomy
        echo '<optgroup label="' . $tax->label . '">';

        // create options for this taxonomy
        $terms = get_terms( array(
            'taxonomy' => $tax->name,
            'hide_empty'	=> false
        ));

        foreach( $terms as $term ) {
            echo '<option value="' . $term->term_id . '" ' . selected( $term->term_id, $restriction['taxonomy'], false ) . '>' . $term->name . '</option>';
        }

        echo '</optgroup>';

    }
    echo '</select></td>';

    echo '<td><span class="delete-x delete-restriction-row">&times;</span></td>';

    echo '</tr>';

}


add_action( 'wp_ajax_leaky-paywall-get-restriction-row-post-type-taxonomies', 'idra_get_restriction_row_post_type_taxonomies' );

/**
 * Get the taxonomies for the selected post type in a restriction setting row
 *
 * @since 4.7.5
 */
function idra_get_restriction_row_post_type_taxonomies() {

    $post_type = trim(sanitize_text_field($_REQUEST['post_type']));

	$taxes = get_object_taxonomies( $post_type, 'objects' );
	$hidden_taxes = array( 'post_format' );

	 ob_start(); ?>
    
    	<select style="width: 100%;">
    		<option value="all">All</option>
    		
    	<?php 
    		foreach( $taxes as $tax ) {

				if ( in_array( $tax->name, $hidden_taxes ) ) {
					continue;
				}

				// create option group for this taxonomy
				echo '<optgroup label="' . $tax->label . '">';

				// create options for this taxonomy
				$terms = get_terms( array(
					'taxonomy' => $tax->name,
					'hide_empty'	=> false
				));

				foreach( $terms as $term ) {
					echo '<option value="' . $term->term_id . '">' . $term->name . '</option>';
				}

				echo '</optgroup>';

			}

    	?>

    	</select>
    
    <?php  $content = ob_get_contents();
	ob_end_clean();

	wp_send_json($content);
}

/**
 * AJAX Wrapper
 *
 * @since 1.0.0
 */
function idra_build_lpaywall_default_restriction_row_ajax() {
    if (isset($_REQUEST['row-key'])) {
        die(idra_build_lpaywall_default_restriction_row(array(), intval($_REQUEST['row-key'])));
    } else {
        die();
    }
}
add_action( 'wp_ajax_issuem-leaky-paywall-add-new-restriction-row', 'idra_build_lpaywall_default_restriction_row_ajax' );


/**
 * Add settings link to plugin table for Leaky Paywall
 *
 * @since 4.10.4
 * @param  $links default plugin links
 * @return  array $links
 */
function idra_plugin_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=' . IDRA_Constants::TOP_LEVEL_PAGE_NAME . '">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
  	return $links;
}
add_filter( 'plugin_action_links_' . IDRA_PLUGIN_BASENAME, 'idra_plugin_add_settings_link' );

/**
 * Plugin row meta links for add ons
 *
 * @since 4.10.4
 * @param array $input already defined meta links
 * @param string $file plugin file path and name being processed
 * @return array $input
 */
function idra_plugin_row_meta( $input, $file ) {
	
	if ( $file != 'ice-dragon-wp-plugin/ice-dragon-wp-plugin.php' ) {
		return $input;
	}

	$lp_link = esc_url( add_query_arg( array(
			'utm_source'   => 'plugins-page',
			'utm_medium'   => 'plugin-row',
			'utm_campaign' => 'admin',
		), 'https://zeen101.com/for-developers/leakypaywall/leaky-paywall-add-ons/' )
	);

	$links = array(
		'<a href="' . $lp_link . '">' . esc_html__( 'Add-Ons', 'leaky-paywall' ) . '</a>',
	);

	$input = array_merge( $input, $links );

	return $input;
}
add_filter( 'plugin_row_meta', 'idra_plugin_row_meta', 10, 2 );

add_action( 'admin_init', 'idra_update_admin_notice_viewed' );

function idra_update_admin_notice_viewed() {

	if ( !isset( $_GET['action'] ) ) {
		return;
	}

	if ( $_GET['action'] != 'ice_dragon_paywall_set_admin_notice_viewed' ) {
		return;
	}

	update_user_meta( get_current_user_id(), sanitize_text_field( $_GET['notice_id'] ), true );

}