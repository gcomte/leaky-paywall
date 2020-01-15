<?php
/**
* Load the Restrictions Class
*/
class IDRA_Content_Restrictions {

	/** @var string Name of the restriction cookie */
	public $cookie_name = 'issuem_lp';
	private $post_id;
	public $is_ajax;

	public function __construct( $post_id = '' ) 
	{
		$this->post_id = $post_id ? $post_id : get_the_ID();
		$this->is_ajax = false;

		add_action( 'wp_footer', array( $this, 'display_viewed_content_debug' ) );
	}

	public function display_viewed_content_debug() 
	{

		if ( !isset( $_GET['lpaywall_debug'] ) ) {
			return;
		}
		?>
			<div style="position:absolute; top: 0; right: 0; padding: 10px; background: #fff; z-index: 9999;">
				<?php 
				echo '<pre>Viewed Content: ';
				print_r( $this->get_content_viewed_by_user() );
				echo '</pre>';
				?>
			</div>
		<?php 
	}

	public function process_content_restrictions() 
	{

		do_action( 'ice_dragon_paywall_before_process_requests', idra_get_ice_dragon_paywall_settings() );

		if ( !$this->is_content_restricted() ) {
			return;
		}

		// content is restricted, so see if the current user can access it
		if ( apply_filters( 'ice_dragon_paywall_current_user_can_access', $this->current_user_can_access() ) ) {
			return;
		}



		$this->display_subscribe_nag();

		do_action( 'ice_dragon_paywall_is_restricted_content' );

	}

	// Helper method when restrictions need to be checked manually (like custom fields)
	public function subscriber_can_view() 
	{
		if ( !$this->is_content_restricted() ) {
			return true;
		}

		// content is restricted, so see if the current user can access it
		if ( $this->current_user_can_access() ) {
			return true;
		}

		return false;
	}

	public function is_content_restricted() 
	{
		$is_restricted = false;
		
		if ( $this->content_matches_restriction_rules() ) {
			$is_restricted = true;
		}

		return apply_filters( 'ice_dragon_paywall_filter_is_restricted', $is_restricted, $this->get_restriction_settings(), $this->post_id );
	}

	public function content_matches_restriction_rules() 
	{

		if ( !$this->is_single() ) {
			return false;
		}

		// allow admins to view all content
		if ( current_user_can( apply_filters( 'ice_dragon_paywall_current_user_can_view_all_content', 'manage_options' ) ) ) {
			return false;
		}

		// We don't ever want to block the login page, subscription page, etc.
		if ( $this->is_unblockable_content() ) {
			return false;
		}

		// check if content is set to be open to everyone
        if ($this->visibility_restricts_access() !== null) {
		    return $this->visibility_restricts_access();
        }
        // check if content is restricted based on main restriction settings
        if ( $this->content_restricted_by_settings() ) {
            return true;
        }

        return false;
	}

	public function is_single() 
	{

		$is_single = false;

		if ( is_single( $this->post_id ) ) {
			$is_single = true;
		}

		if ( is_page( $this->post_id ) ) {
			$is_single = true;
		}

		// for ajax
		if ( $this->is_ajax ) {
			$is_single = true;
		}

		return $is_single;

	}

	public function current_user_can_access()
	{
        $settings = idra_get_ice_dragon_paywall_settings();
        $paymentConfirmationSecret = $settings[IDRA_Constants::SETTINGS_KEY_HMAC_SECRET];

        define('IDRA_PLUGIN_ABSOLUTE_PATH', dirname(__FILE__, 2) . '/');
        define('IDRA_DRAGONS_NEST_CLASS_RELATIVE_PATH', 'include/class-dragons-nest.php');
        define('IDRA_DRAGONS_NEST_CLASS_ABSOLUTE_PATH', IDRA_PLUGIN_ABSOLUTE_PATH . IDRA_DRAGONS_NEST_CLASS_RELATIVE_PATH);

        require_once(IDRA_DRAGONS_NEST_CLASS_ABSOLUTE_PATH);

        $dragonsNest = new IDRA_DragonsNest();
        return $dragonsNest->receivedValidIceDragonCookie($paymentConfirmationSecret);
	}

	 public function display_subscribe_nag() 
	 {
	 	add_filter( 'the_content', array( $this, 'get_subscribe_nag' ), 999 );
	 }

	 public function get_subscribe_nag( $content = '' ) 
	 {

	 	if ( !$content ) {
	 		$content = get_the_content( $this->post_id );
	 	}

	 	$message = $this->the_content_paywall_message();
	 	$new_content = $this->get_nag_excerpt( $content ) . $message;

		return apply_filters( 'ice_dragon_paywall_subscribe_or_login_message', $new_content, $message, $content );
	 }

	 public function get_nag_excerpt( $content ) 
	 {
	 	$settings = idra_get_ice_dragon_paywall_settings();

	 	if ( isset( $settings['custom_excerpt_length'] ) && strlen( $settings['custom_excerpt_length'] ) > 0 ) {
			$excerpt = substr( strip_tags( get_the_content( get_the_ID() ) ), 0, intval( $settings['custom_excerpt_length'] ) );
		} else {
			$excerpt = substr( strip_tags( $content ), 0, 100 );
		}

	 	return apply_filters( 'ice_dragon_paywall_nag_excerpt', strip_shortcodes( $excerpt ), $this->post_id );
	 }

	public function the_content_paywall_message() 
	{

		$settings = idra_get_ice_dragon_paywall_settings();
		
		$message  = '<div class="lpaywall_message_wrap"><div id="lpaywall_message">';
		if ( !is_user_logged_in() ) {
            $message .= '<a class="link-on-paywall" href="https://ice-dragon.ch" target="_blank">';
            $message .= '<div id="paywall-login-container">';
            if ( $settings['use_css'] === true ) {
                $message .= '<img src="' . IDRA_PLUGIN_URL . '/images/iceDragonLogo.png" alt="Ice Dragon Logo" id="ice-dragon-logo-on-paywall">';
            }
            $message .=  '<div id="paywall-login-text"><div>' . $this->replace_variables( stripslashes( $settings['subscribe_login_message'] ) . '</div></div>' );
            $message .= '</div></a>';
        } else {
			$message .= $this->replace_variables( stripslashes( $settings['pay_direct_message'] ) );
		}
		$message .= '</div></div>';

		return do_shortcode( $message );

	}

	/**
	 * Replace any variables in the content paywall barrier message with dyanmic values
	 *
	 * @since 4.10.3
	 *
	 * @param string $message
	 *
	 * @return string $message Message with dynamic values inserted
	 */
	public function replace_variables( $message ) {

		$settings = idra_get_ice_dragon_paywall_settings();

		if ( 0 === $settings['page_for_subscription'] )
			$subscription_url = get_bloginfo( 'wpurl' ) . '/?subscription'; //CHANGEME -- I don't really know what this is suppose to do...
		else
			$subscription_url = get_page_link( $settings['page_for_subscription'] );

		if ( 0 === $settings['page_for_profile'] )
			$my_account_url = get_bloginfo( 'wpurl' ) . '/?my-account'; //CHANGEME -- I don't really know what this is suppose to do...
		else
			$my_account_url = get_page_link( $settings['page_for_profile'] );

		$message = str_ireplace( '{{SUBSCRIBE_LOGIN_URL}}', $subscription_url, $message );
		$message = str_ireplace( '{{SUBSCRIBE_URL}}', $subscription_url, $message );
		$message = str_ireplace( '{{MY_ACCOUNT_URL}}', $my_account_url, $message );

		if ( 0 === $settings['page_for_login'] )
			$login_url = get_bloginfo( 'wpurl' ) . '/?login'; //CHANGEME -- I don't really know what this is suppose to do...
		else
			$login_url = get_page_link( $settings['page_for_login'] );

		$message = str_ireplace( '{{LOGIN_URL}}', $login_url, $message );

		//Deprecated
		if ( !empty( $settings['price'] ) ) {
			$message = str_ireplace( '{{PRICE}}', $settings['price'], $message );
		}

		return $message;

	}

	 /**
	 * Determine if the current content is unblockable by the content paywall barrier
	 *
	 * @since 4.10.3
	 *
	 * @return boolean
	 */
	public function is_unblockable_content()
	{

		$settings = idra_get_ice_dragon_paywall_settings();

		$unblockable_content = array(
			$settings['page_for_login'],
			$settings['page_for_subscription'],
			$settings['page_for_profile'],
			$settings['page_for_register']
		);

		if ( in_array( $this->post_id, apply_filters( 'ice_dragon_paywall_unblockable_content', $unblockable_content ) ) ) {
			return true;
		}

		return false;

	}

    /**
	 * Determine if the current content can be viewed based on individual settings
	 *
	 * @since 4.10.3
	 *
	 * @return boolean
	 */
	public function visibility_restricts_access()
	{
        $paywallVisibility = get_post_meta( $this->post_id, '_puzzle_ice_dragon_paywall_visibility' );

        if ($paywallVisibility[0]['visibility_type'] === 'always') {
            return true;
        }

        if($paywallVisibility[0]['visibility_type'] === 'never') {
            return false;
        }
	}

    /**
     * Check if the Leaky Paywall visibility settings for this post restrict its access to the current user
     *
     * @since 4.10.3
     *
     * @param object $post The post object
     *
     * @return bool $is_restricted
     */
    public function content_restricted_by_settings() {
        $restrictions = $this->get_restriction_settings();
        if ( empty( $restrictions ) ) {
            return false;
        }
        $content_post_type = get_post_type( $this->post_id );
        foreach( $restrictions['post_types'] as $key => $restriction ) {
            if ( !is_numeric( $key ) ) {
                continue;
            }

            // post_type, taxonomy, allowed_value

            $restriction_taxomony = isset( $restriction['taxonomy'] ) ? $restriction['taxonomy'] : 'all';

            if ( $restriction['post_type'] == $content_post_type && $restriction_taxomony == 'all'  ) {
                return true;
            }
            if ( $restriction['post_type'] == $content_post_type && $this->content_taxonomy_matches( $restriction_taxomony ) ) {
                return true;
            }
        }
    }

	public function get_restriction_settings()
	{
		$settings = idra_get_ice_dragon_paywall_settings();
		return $settings['restrictions'];
	}

	public function content_taxonomy_matches( $restricted_term_id, $post_id = '' ) 
	{

		if ( !$post_id ) {
			$post_id = $this->post_id;
		}

		// get current post taxonomies
		$taxonomies = get_post_taxonomies( $post_id );

		foreach( $taxonomies as $taxonomy ) {
			// get all terms for current post
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( $terms ) {
				foreach( $terms as $term ) {
					// see if one of the term_ids matches the restricted_term_id
					if ( $term->term_id == $restricted_term_id ) {

						return true;
					}

				}
			}	
		}

		return false;
	}

	/**
	 * Calculate expiration time for cookie
	 *
	 * @since 4.10.3
	 *
	 * @return string $expiration
	 */
	public function get_expiration_time()
	{

		$settings = idra_get_ice_dragon_paywall_settings();

		switch ( $settings['cookie_expiration_interval'] ) {
			case 'hour':
				$multiplier = 60 * 60; //seconds in an hour
				break;
			case 'day':
				$multiplier = 60 * 60 * 24; //seconds in a day
				break;
			case 'week':
				$multiplier = 60 * 60 * 24 * 7; //seconds in a week
				break;
			case 'month':
				$multiplier = 60 * 60 * 24 * 7 * 4; //seconds in a month (4 weeks)
				break;
			case 'year':
				$multiplier = 60 * 60 * 24 * 7 * 52; //seconds in a year (52 weeks)
				break;
		}

		$expiration = time() + ( $settings['cookie_expiration'] * $multiplier );

		return apply_filters( 'ice_dragon_paywall_expiration_time', $expiration );

	}

}