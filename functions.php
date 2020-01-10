<?php

require_once ('ice-dragon-constants.php');

/**
 * @package zeen101's Leaky Paywall
 * @since 1.0.0
 */

if ( !function_exists( 'get_ice_dragon_paywall_settings' ) ) {

	/**
	 * Helper function to get zeen101's Leaky Paywall settings for current site
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Value set for the issuem options.
	 */
	function get_ice_dragon_paywall_settings() {
	
		global $ice_dragon_paywall;
		
		return $ice_dragon_paywall->get_settings();
		
	}
	
}

if ( !function_exists( 'update_lpaywall_settings' ) ) {

	/**
	 * Helper function to save zeen101's Leaky Paywall settings for current site
	 *
	 * @since 1.0.0
	 *
	 * @return mixed Value set for the issuem options.
	 */
	function update_lpaywall_settings( $settings ) {

		global $ice_dragon_paywall;

		return $ice_dragon_paywall->update_settings( $settings );
		
	}
	
}

if ( !function_exists('lpaywall_get_current_mode') ) {
	
	function lpaywall_get_current_mode() {

		$settings = get_ice_dragon_paywall_settings();
		$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';

		return apply_filters( 'ice_dragon_paywall_current_mode', $mode );
		
	}

}

if ( !function_exists('lpaywall_get_current_site') ) {
	
	function lpaywall_get_current_site() {

		global $blog_id;

		if ( !is_main_site( $blog_id ) ) {
			$site = '_' . $blog_id;
		} else {
			$site = '';
		}

		return apply_filters( 'ice_dragon_paywall_current_site', $site );
		
	}

}

if ( !function_exists('build_lpaywall_default_restriction_row') ) {

    /**
     * @since 1.0.0
     *
     * @return mixed Value set for the issuem options.
     */
    function build_lpaywall_default_restriction_row($restriction=array(), $row_key='' ) {

        $settings = get_ice_dragon_paywall_settings();

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

}

add_action( 'wp_ajax_leaky-paywall-get-restriction-row-post-type-taxonomies', 'ice_dragon_paywall_get_restriction_row_post_type_taxonomies' );

/**
 * Get the taxonomies for the selected post type in a restriction setting row
 *
 * @since 4.7.5
 */
function ice_dragon_paywall_get_restriction_row_post_type_taxonomies() {

	$post_type = $_REQUEST['post_type'];

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
	

 
if ( !function_exists( 'build_lpaywall_default_restriction_row_ajax' ) ) {

	/**
	 * AJAX Wrapper
	 *
	 * @since 1.0.0
	 */
	function build_lpaywall_default_restriction_row_ajax() {
		if ( isset( $_REQUEST['row-key'] ) )
			die( build_lpaywall_default_restriction_row( array(), $_REQUEST['row-key'] ) );
		else
			die();
	}
	add_action( 'wp_ajax_issuem-leaky-paywall-add-new-restriction-row', 'build_lpaywall_default_restriction_row_ajax' );
	
}

if ( !function_exists( 'wp_print_r' ) ) { 

	/**
	 * Helper function used for printing out debug information
	 *
	 * HT: Glenn Ansley @ iThemes.com
	 *
	 * @since 1.0.0
	 *
	 * @param int $args Arguments to pass to print_r
	 * @param bool $die TRUE to die else FALSE (default TRUE)
	 */
    function wp_print_r( $args, $die = true ) { 
	
        $echo = '<pre>' . print_r( $args, true ) . '</pre>';
		
        if ( $die ) die( $echo );
        	else echo $echo;
		
    }   
	
}

if ( !function_exists('lpaywall_supported_currencies') ) {
	
	function lpaywall_supported_currencies() {
		$currencies = array(
			'AED' => array( 'symbol' => '&#1583;.&#1573;', 'label' => __( 'UAE dirham', 'leaky-paywall' ), 'country' => __( 'UAE', 'leaky-paywall' ) ),
			'AFN' => array( 'symbol' => 'Afs', 'label' => __( 'Afghan afghani', 'leaky-paywall' ), 'country' => __( 'Afghanistan', 'leaky-paywall' ) ),
			'ALL' => array( 'symbol' => 'L', 'label' => __( 'Albanian lek', 'leaky-paywall' ), 'country' => __( 'Albania', 'leaky-paywall' ) ),
			'AMD' => array( 'symbol' => 'AMD', 'label' => __( 'Armenian dram', 'leaky-paywall' ), 'country' => __( 'Armenia', 'leaky-paywall' ) ),
			'ANG' => array( 'symbol' => 'NA&#402;', 'label' => __( 'Netherlands Antillean gulden', 'leaky-paywall' ), 'country' => __( 'Netherlands', 'leaky-paywall' ) ),
			'AOA' => array( 'symbol' => 'Kz', 'label' => __( 'Angolan kwanza', 'leaky-paywall' ), 'country' => __( 'Angolia', 'leaky-paywall' ) ),
			'ARS' => array( 'symbol' => '$', 'label' => __( 'Argentine peso', 'leaky-paywall' ), 'country' => __( 'Argentina', 'leaky-paywall' ) ),
			'AUD' => array( 'symbol' => '$', 'label' => __( 'Australian dollar', 'leaky-paywall' ), 'country' => __( 'Australia', 'leaky-paywall' ) ),
			'AWG' => array( 'symbol' => '&#402;', 'label' => __( 'Aruban florin', 'leaky-paywall' ), 'country' => __( 'Aruba', 'leaky-paywall' ) ),
			'AZN' => array( 'symbol' => 'AZN', 'label' => __( 'Azerbaijani manat', 'leaky-paywall' ), 'country' => __( 'Azerbaij', 'leaky-paywall' ) ),
			'BAM' => array( 'symbol' => 'KM', 'label' => __( 'Bosnia and Herzegovina konvertibilna marka', 'leaky-paywall' ), 'country' => __( 'Bosnia', 'leaky-paywall' ) ),
			'BBD' => array( 'symbol' => 'Bds$', 'label' => __( 'Barbadian dollar', 'leaky-paywall' ), 'country' => __( 'Barbadian', 'leaky-paywall' ) ),
			'BDT' => array( 'symbol' => '&#2547;', 'label' => __( 'Bangladeshi taka', 'leaky-paywall' ), 'country' => __( 'Bangladesh', 'leaky-paywall' ) ),
			'BGN' => array( 'symbol' => 'BGN', 'label' => __( 'Bulgarian lev', 'leaky-paywall' ), 'country' => __( 'Bulgaria', 'leaky-paywall' ) ),
			'BIF' => array( 'symbol' => 'FBu', 'label' => __( 'Burundi franc', 'leaky-paywall' ), 'country' => __( 'Burundi', 'leaky-paywall' ) ),
			'BMD' => array( 'symbol' => 'BD$', 'label' => __( 'Bermudian dollar', 'leaky-paywall' ), 'country' => __( 'Bermuda', 'leaky-paywall' ) ),
			'BND' => array( 'symbol' => 'B$', 'label' => __( 'Brunei dollar', 'leaky-paywall' ), 'country' => __( 'Brunei', 'leaky-paywall' ) ),
			'BOB' => array( 'symbol' => 'Bs.', 'label' => __( 'Bolivian boliviano', 'leaky-paywall' ), 'country' => __( 'Bolivia', 'leaky-paywall' ) ),
			'BRL' => array( 'symbol' => 'R$', 'label' => __( 'Brazilian real', 'leaky-paywall' ), 'country' => __( 'Brazil', 'leaky-paywall' ) ),
			'BSD' => array( 'symbol' => 'B$', 'label' => __( 'Bahamian dollar', 'leaky-paywall' ), 'country' => __( 'Bahamas', 'leaky-paywall' ) ),
			'BWP' => array( 'symbol' => 'P', 'label' => __( 'Botswana pula', 'leaky-paywall' ), 'country' => __( 'Botswana', 'leaky-paywall' ) ),
			'BZD' => array( 'symbol' => 'BZ$', 'label' => __( 'Belize dollar', 'leaky-paywall' ), 'country' => __( 'Belize', 'leaky-paywall' ) ),
			'CAD' => array( 'symbol' => '$', 'label' => __( 'Canadian dollar', 'leaky-paywall' ), 'country' => __( 'Canada', 'leaky-paywall' ) ),
			'CDF' => array( 'symbol' => 'F', 'label' => __( 'Congolese franc', 'leaky-paywall' ), 'country' => __( 'Congo', 'leaky-paywall' ) ),
			'CHF' => array( 'symbol' => 'CHF', 'label' => __( 'Swiss franc', 'leaky-paywall' ), 'country' => __( 'Switzerland', 'leaky-paywall' ) ),
			'CLP' => array( 'symbol' => '$', 'label' => __( 'Chilean peso', 'leaky-paywall' ), 'country' => __( 'Chili', 'leaky-paywall' ) ),
			'CNY' => array( 'symbol' => '&#165;', 'label' => __( 'Chinese Yuan Renminbi', 'leaky-paywall' ),'country' => __( 'Chinese Yuan', 'leaky-paywall' ) ),
			'COP' => array( 'symbol' => 'Col$', 'label' => __( 'Colombian peso', 'leaky-paywall' ),'country' => __( 'Colombia', 'leaky-paywall' ) ),
			'CRC' => array( 'symbol' => '&#8353;', 'label' => __( 'Costa Rican colon', 'leaky-paywall' ),'country' => __( 'Costa Rica', 'leaky-paywall' ) ),
			'CVE' => array( 'symbol' => 'Esc', 'label' => __( 'Cape Verdean escudo', 'leaky-paywall' ),'country' => __( 'Cape Verde', 'leaky-paywall' ) ),
			'CZK' => array( 'symbol' => 'K&#269;', 'label' => __( 'Czech koruna', 'leaky-paywall' ),'country' => __( 'Czech', 'leaky-paywall' ) ),
			'DJF' => array( 'symbol' => 'Fdj', 'label' => __( 'Djiboutian franc', 'leaky-paywall' ),'country' => __( 'Djibouti', 'leaky-paywall' ) ),
			'DKK' => array( 'symbol' => 'kr', 'label' => __( 'Danish krone', 'leaky-paywall' ),'country' => __( 'Danish', 'leaky-paywall' ) ),
			'DOP' => array( 'symbol' => 'RD$', 'label' => __( 'Dominican peso', 'leaky-paywall' ),'country' => __( 'Dominican Republic', 'leaky-paywall' ) ),
			'DZD' => array( 'symbol' => '&#1583;.&#1580;', 'label' => __( 'Algerian dinar', 'leaky-paywall' ),'country' => __( 'Algeria', 'leaky-paywall' ) ),
			'EEK' => array( 'symbol' => 'KR', 'label' => __( 'Estonian kroon', 'leaky-paywall' ),'country' => __( 'Estonia', 'leaky-paywall' ) ),
			'EGP' => array( 'symbol' => '&#163;', 'label' => __( 'Egyptian pound', 'leaky-paywall' ),'country' => __( 'Egypt', 'leaky-paywall' ) ),
			'ETB' => array( 'symbol' => 'Br', 'label' => __( 'Ethiopian birr', 'leaky-paywall' ),'country' => __( 'Ethiopia', 'leaky-paywall' ) ),
			'EUR' => array( 'symbol' => '&#8364;', 'label' => __( 'European Euro', 'leaky-paywall' ), 'country' => __( 'Euro', 'leaky-paywall' ) ),
			'FJD' => array( 'symbol' => 'FJ$', 'label' => __( 'Fijian dollar', 'leaky-paywall' ), 'country' => __( 'Fiji', 'leaky-paywall' ) ),
			'FKP' => array( 'symbol' => '&#163;', 'label' => __( 'Falkland Islands pound', 'leaky-paywall' ), 'country' => __( 'Falkland Islands', 'leaky-paywall' ) ),
			'GBP' => array( 'symbol' => '&#163;', 'label' => __( 'British pound', 'leaky-paywall' ), 'country' => __( 'Great Britian', 'leaky-paywall' ) ),
			'GEL' => array( 'symbol' => 'GEL', 'label' => __( 'Georgian lari', 'leaky-paywall' ), 'country' => __( 'Georgia', 'leaky-paywall' ) ),
			'GIP' => array( 'symbol' => '&#163;', 'label' => __( 'Gibraltar pound', 'leaky-paywall' ), 'country' => __( 'Gibraltar', 'leaky-paywall' ) ),
			'GMD' => array( 'symbol' => 'D', 'label' => __( 'Gambian dalasi', 'leaky-paywall' ), 'country' => __( 'Gambia', 'leaky-paywall' ) ),
			'GNF' => array( 'symbol' => 'FG', 'label' => __( 'Guinean franc', 'leaky-paywall' ), 'country' => __( 'Guinea', 'leaky-paywall' ) ),
			'GTQ' => array( 'symbol' => 'Q', 'label' => __( 'Guatemalan quetzal', 'leaky-paywall' ), 'country' => __( 'Guatemala', 'leaky-paywall' ) ),
			'GYD' => array( 'symbol' => 'GY$', 'label' => __( 'Guyanese dollar', 'leaky-paywall' ), 'country' => __( 'Guyanese', 'leaky-paywall' ) ),
			'HKD' => array( 'symbol' => 'HK$', 'label' => __( 'Hong Kong dollar', 'leaky-paywall' ), 'country' => __( 'Hong Kong', 'leaky-paywall' ) ),
			'HNL' => array( 'symbol' => 'L', 'label' => __( 'Honduran lempira', 'leaky-paywall' ), 'country' => __( 'Honduras', 'leaky-paywall' ) ),
			'HRK' => array( 'symbol' => 'kn', 'label' => __( 'Croatian kuna', 'leaky-paywall' ), 'country' => __( 'Croatia', 'leaky-paywall' ) ),
			'HTG' => array( 'symbol' => 'G', 'label' => __( 'Haitian gourde', 'leaky-paywall' ), 'country' => __( 'Haiti', 'leaky-paywall' ) ),
			'HUF' => array( 'symbol' => 'Ft', 'label' => __( 'Hungarian forint', 'leaky-paywall' ), 'country' => __( 'Hungary', 'leaky-paywall' ) ),
			'IDR' => array( 'symbol' => 'Rp', 'label' => __( 'Indonesian rupiah', 'leaky-paywall' ), 'country' => __( 'Idonesia', 'leaky-paywall' ) ),
			'ILS' => array( 'symbol' => '&#8362;', 'label' => __( 'Israeli new sheqel', 'leaky-paywall' ), 'country' => __( 'Israel', 'leaky-paywall' ) ),
			'INR' => array( 'symbol' => '&#8377;', 'label' => __( 'Indian rupee', 'leaky-paywall' ), 'country' => __( 'India', 'leaky-paywall' ) ),
			'ISK' => array( 'symbol' => 'kr', 'label' => __( 'Icelandic króna', 'leaky-paywall' ), 'country' => __( 'Iceland', 'leaky-paywall' ) ),
			'JMD' => array( 'symbol' => 'J$', 'label' => __( 'Jamaican dollar', 'leaky-paywall' ), 'country' => __( 'Jamaica', 'leaky-paywall' ) ),
			'JPY' => array( 'symbol' => '&#165;', 'label' => __( 'Japanese yen', 'leaky-paywall' ), 'country' => __( 'Japan', 'leaky-paywall' ) ),
			'KES' => array( 'symbol' => 'KSh', 'label' => __( 'Kenyan shilling', 'leaky-paywall' ), 'country' => __( 'Kenya', 'leaky-paywall' ) ),
			'KGS' => array( 'symbol' => '&#1089;&#1086;&#1084;', 'label' => __( 'Kyrgyzstani som', 'leaky-paywall' ), 'country' => __( 'Kyrgyzstan', 'leaky-paywall' ) ),
			'KHR' => array( 'symbol' => '&#6107;', 'label' => __( 'Cambodian riel', 'leaky-paywall' ), 'country' => __( 'Cambodia', 'leaky-paywall' ) ),
			'KMF' => array( 'symbol' => 'KMF', 'label' => __( 'Comorian franc', 'leaky-paywall' ), 'country' => __( 'Comorian', 'leaky-paywall' ) ),
			'KRW' => array( 'symbol' => 'W', 'label' => __( 'South Korean won', 'leaky-paywall' ), 'country' => __( 'South Korea', 'leaky-paywall' ) ),
			'KYD' => array( 'symbol' => 'KY$', 'label' => __( 'Cayman Islands dollar', 'leaky-paywall' ), 'country' => __( 'Cayman Islands', 'leaky-paywall' ) ),
			'KZT' => array( 'symbol' => 'T', 'label' => __( 'Kazakhstani tenge', 'leaky-paywall' ), 'country' => __( 'Kazakhstan', 'leaky-paywall' ) ),
			'LAK' => array( 'symbol' => 'KN', 'label' => __( 'Lao kip', 'leaky-paywall' ), 'country' => __( 'Loa', 'leaky-paywall' ) ),
			'LBP' => array( 'symbol' => '&#163;', 'label' => __( 'Lebanese lira', 'leaky-paywall' ), 'country' => __( 'Lebanese', 'leaky-paywall' ) ),
			'LKR' => array( 'symbol' => 'Rs', 'label' => __( 'Sri Lankan rupee', 'leaky-paywall' ), 'country' => __( 'Sri Lanka', 'leaky-paywall' ) ),
			'LRD' => array( 'symbol' => 'L$', 'label' => __( 'Liberian dollar', 'leaky-paywall' ), 'country' => __( 'Liberia', 'leaky-paywall' ) ),
			'LSL' => array( 'symbol' => 'M', 'label' => __( 'Lesotho loti', 'leaky-paywall' ), 'country' => __( 'Lesotho', 'leaky-paywall' ) ),
			'LTL' => array( 'symbol' => 'Lt', 'label' => __( 'Lithuanian litas', 'leaky-paywall' ), 'country' => __( 'Lithuania', 'leaky-paywall' ) ),
			'LVL' => array( 'symbol' => 'Ls', 'label' => __( 'Latvian lats', 'leaky-paywall' ), 'country' => __( 'Latvia', 'leaky-paywall' ) ),
			'MAD' => array( 'symbol' => 'MAD', 'label' => __( 'Moroccan dirham', 'leaky-paywall' ), 'country' => __( 'Morocco', 'leaky-paywall' ) ),
			'MDL' => array( 'symbol' => 'MDL', 'label' => __( 'Moldovan leu', 'leaky-paywall' ), 'country' => __( 'Moldova', 'leaky-paywall' ) ),
			'MGA' => array( 'symbol' => 'FMG', 'label' => __( 'Malagasy ariary', 'leaky-paywall' ), 'country' => __( 'Malagasy', 'leaky-paywall' ) ),
			'MKD' => array( 'symbol' => 'MKD', 'label' => __( 'Macedonian denar', 'leaky-paywall' ), 'country' => __( 'Macedonia', 'leaky-paywall' ) ),
			'MNT' => array( 'symbol' => '&#8366;', 'label' => __( 'Mongolian tugrik', 'leaky-paywall' ), 'country' => __( 'Mongolia', 'leaky-paywall' ) ),
			'MOP' => array( 'symbol' => 'P', 'label' => __( 'Macanese pataca', 'leaky-paywall' ), 'country' => __( 'Macanese', 'leaky-paywall' ) ),
			'MRO' => array( 'symbol' => 'UM', 'label' => __( 'Mauritanian ouguiya', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MUR' => array( 'symbol' => 'Rs', 'label' => __( 'Mauritian rupee', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MVR' => array( 'symbol' => 'Rf', 'label' => __( 'Maldivian rufiyaa', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MWK' => array( 'symbol' => 'MK', 'label' => __( 'Malawian kwacha', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MXN' => array( 'symbol' => '$', 'label' => __( 'Mexican peso', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MYR' => array( 'symbol' => 'RM', 'label' => __( 'Malaysian ringgit', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'MZN' => array( 'symbol' => 'MT', 'label' => __( 'Mozambique Metical', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NAD' => array( 'symbol' => 'N$', 'label' => __( 'Namibian dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NGN' => array( 'symbol' => '&#8358;', 'label' => __( 'Nigerian naira', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NIO' => array( 'symbol' => 'C$', 'label' => __( 'Nicaraguan Córdoba', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NOK' => array( 'symbol' => 'kr', 'label' => __( 'Norwegian krone', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NPR' => array( 'symbol' => 'NRs', 'label' => __( 'Nepalese rupee', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'NZD' => array( 'symbol' => 'NZ$', 'label' => __( 'New Zealand dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PAB' => array( 'symbol' => 'B./', 'label' => __( 'Panamanian balboa', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PEN' => array( 'symbol' => 'S/.', 'label' => __( 'Peruvian nuevo sol', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PGK' => array( 'symbol' => 'K', 'label' => __( 'Papua New Guinean kina', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PHP' => array( 'symbol' => '&#8369;', 'label' => __( 'Philippine peso', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PKR' => array( 'symbol' => 'Rs.', 'label' => __( 'Pakistani rupee', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PLN' => array( 'symbol' => 'z&#322;', 'label' => __( 'Polish zloty', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'PYG' => array( 'symbol' => '&#8370;', 'label' => __( 'Paraguayan guarani', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'QAR' => array( 'symbol' => 'QR', 'label' => __( 'Qatari riyal', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'RON' => array( 'symbol' => 'L', 'label' => __( 'Romanian leu', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'RSD' => array( 'symbol' => 'din.', 'label' => __( 'Serbian dinar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'RUB' => array( 'symbol' => 'R', 'label' => __( 'Russian ruble', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'RWF' => array( 'symbol' => 'R&#8355;', 'label' => __( 'Rwandan Franc' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SAR' => array( 'symbol' => 'SR', 'label' => __( 'Saudi riyal', 'leaky-paywall' ) ),
			'SBD' => array( 'symbol' => 'SI$', 'label' => __( 'Solomon Islands dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SCR' => array( 'symbol' => 'SR', 'label' => __( 'Seychellois rupee', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SEK' => array( 'symbol' => 'kr', 'label' => __( 'Swedish krona', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SGD' => array( 'symbol' => 'S$', 'label' => __( 'Singapore dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SHP' => array( 'symbol' => '&#163;', 'label' => __( 'Saint Helena pound', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SLL' => array( 'symbol' => 'Le', 'label' => __( 'Sierra Leonean leone', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SOS' => array( 'symbol' => 'Sh.', 'label' => __( 'Somali shilling', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SRD' => array( 'symbol' => '$', 'label' => __( 'Surinamese dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'STD' => array( 'symbol' => 'STD', 'label' => __( 'São Tomé and Príncipe Dobra', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SVC' => array( 'symbol' => '$', 'label' => __( 'El Salvador Colon', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'SZL' => array( 'symbol' => 'E', 'label' => __( 'Swazi lilangeni', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'THB' => array( 'symbol' => '&#3647;', 'label' => __( 'Thai baht', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TJS' => array( 'symbol' => 'TJS', 'label' => __( 'Tajikistani somoni', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TOP' => array( 'symbol' => 'T$', 'label' => __( "Tonga Pa'anga", 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TRY' => array( 'symbol' => 'TRY', 'label' => __( 'Turkish new lira', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TTD' => array( 'symbol' => 'TT$', 'label' => __( 'Trinidad and Tobago dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TWD' => array( 'symbol' => 'NT$', 'label' => __( 'New Taiwan dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'TZS' => array( 'symbol' => 'TZS', 'label' => __( 'Tanzanian shilling', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'UAH' => array( 'symbol' => 'UAH', 'label' => __( 'Ukrainian hryvnia', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'UGX' => array( 'symbol' => 'USh', 'label' => __( 'Ugandan shilling', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'USD' => array( 'symbol' => '$', 'label' => __( 'United States dollar', 'leaky-paywall' ), 'country' => __( 'United States', 'leaky-paywall' ) ),
			'UYU' => array( 'symbol' => '$U', 'label' => __( 'Uruguayan peso', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'UZS' => array( 'symbol' => 'UZS', 'label' => __( 'Uzbekistani som', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'VND' => array( 'symbol' => '&#8363;', 'label' => __( 'Vietnamese dong', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'VUV' => array( 'symbol' => 'VT', 'label' => __( 'Vanuatu vatu', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'WST' => array( 'symbol' => 'WS$', 'label' => __( 'Samoan tala', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'XAF' => array( 'symbol' => 'CFA', 'label' => __( 'Central African CFA franc', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'XCD' => array( 'symbol' => 'EC$', 'label' => __( 'East Caribbean dollar', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'XOF' => array( 'symbol' => 'CFA', 'label' => __( 'West African CFA franc', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'XPF' => array( 'symbol' => 'F', 'label' => __( 'CFP franc', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'YER' => array( 'symbol' => 'YER', 'label' => __( 'Yemeni rial', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'ZAR' => array( 'symbol' => 'R', 'label' => __( 'South African rand', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
			'ZMW' => array( 'symbol' => 'ZK', 'label' => __( 'Zambian kwacha', 'leaky-paywall' ), 'country' => __( '', 'leaky-paywall' ) ),
		);
	
		return apply_filters( 'ice_dragon_paywall_supported_currencies', $currencies );
	}
}

/**
 * Helper function to convert object to array
 *
 * @since 3.7.0
 * @return array
 */
if ( ! function_exists( 'object_to_array' ) ) {

	function object_to_array ($object) {

	    if(!is_object($object) && !is_array($object))
	        return $object;

	    return array_map('objectToArray', (array) $object);
	}

}

/**
 * Determine if the current subscriber can view the content
 *
 * @since 4.7.1
 * @return bool
 */
function ice_dragon_paywall_subscriber_can_view() {

	$restricted = new Ice_Dragon_Paywall_Restrictions();
	return $restricted->subscriber_can_view();

}

/**
 * Log Leaky Paywall events and data to a file
 *
 * @since 4.7.1
 * @return bool
 */
function ice_dragon_paywall_log( $data, $event ) {

	$str = '';

	if ( is_object( $data ) ) {
		$data = json_decode(json_encode($data), true);
	}

	if ( is_array( $data ) ) {
		foreach( $data as $key => $value ) {
			$str .= $key . ': ' . $value . ',';
		}
	} else {
		$str = $data;
	}

	$file = plugin_dir_path( __FILE__ ) . '/lp-log.txt'; 
	$open = fopen( $file, "a" ); 
	$write = fputs( $open, $event . " - " . current_time('mysql') . "\r\n" . $str . "\r\n" ); 
	fclose( $open );

}

/**
 * Add settings link to plugin table for Leaky Paywall
 *
 * @since 4.10.4
 * @param  $links default plugin links
 * @return  array $links
 */
function ice_dragon_paywall_plugin_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=' . IceDragonConstants::TOP_LEVEL_PAGE_NAME . '">' . __( 'Settings' ) . '</a>';
    array_unshift( $links, $settings_link );
  	return $links;
}
add_filter( 'plugin_action_links_' . ICE_DRAGON_PAYWALL_BASENAME, 'ice_dragon_paywall_plugin_add_settings_link' );

/**
 * Plugin row meta links for add ons
 *
 * @since 4.10.4
 * @param array $input already defined meta links
 * @param string $file plugin file path and name being processed
 * @return array $input
 */
function ice_dragon_paywall_plugin_row_meta( $input, $file ) {
	
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
add_filter( 'plugin_row_meta', 'ice_dragon_paywall_plugin_row_meta', 10, 2 );


/**
 * Replace language-specific characters by ASCII-equivalents.
 * @param string $s
 * @return string
 */
function ice_dragon_paywall_normalize_chars($s) {
    $replace = array(
        'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
        'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
        'Þ'=>'B',
        'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
        'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
        'Ğ'=>'G',
        'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
        'Ł'=>'L',
        'Ñ'=>'N', 'Ń'=>'N',
        'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
        'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
        'Ț'=>'T',
        'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
        'Ý'=>'Y',
        'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
        'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
        'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
        'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
        'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
        'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
        'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
        'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
        'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
        'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
        'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
        'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
        'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
        'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
        'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
        'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
        'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
        'ק'=>'q',
        'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
        'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
        'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
        'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
        'в'=>'v', 'ו'=>'v', 'В'=>'v',
        'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
        'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
        'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
    );
    return strtr($s, $replace);
}

add_action( 'admin_init', 'ice_dragon_paywall_update_admin_notice_viewed' );

function ice_dragon_paywall_update_admin_notice_viewed() {

	if ( !isset( $_GET['action'] ) ) {
		return;
	}

	if ( $_GET['action'] != 'ice_dragon_paywall_set_admin_notice_viewed' ) {
		return;
	}

	update_user_meta( get_current_user_id(), sanitize_text_field( $_GET['notice_id'] ), true );

}