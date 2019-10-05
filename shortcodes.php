<?php
/**
 * @package zeen101's Leaky Paywall
 * @since 1.0.0
 */

if ( !function_exists( 'do_leaky_paywall_login' ) ) { 

	/**
	 * Shortcode for zeen101's Leaky Paywall
	 * Prints out the zeen101's Leaky Paywall
	 *
	 * @since 1.0.0
	 */
	function do_leaky_paywall_login( $atts ) {
		
		$settings = get_leaky_paywall_settings();
		
		$defaults = array(
			'heading'			=> __( 'Email address:', 'leaky-paywall' ),
			'description' 		=> __( 'Check your email for a link to log in.', 'leaky-paywall' ),
			'email_sent' 		=> __( 'Email sent. Please check your email for the login link.', 'leaky-paywall' ),
			'error_msg' 		=> __( 'Error sending login email, please try again later.', 'leaky-paywall' ),
			'missing_email_msg' => __( 'Please supply a valid email address.', 'leaky-paywall' ),
			'login_redirect_url'	=> ''
		);
	
		// Merge defaults with passed atts
		// Extract (make each array element its own PHP var
		$args = shortcode_atts( $defaults, $atts );
		// extract( $args );
		
		$results = '';
		
		if ( 'passwordless' === $settings['login_method'] ) {
	
			if ( isset( $_REQUEST['submit-leaky-login'] ) ) {
				
				if ( isset( $_REQUEST['email'] ) && is_email( $_REQUEST['email'] ) ) {
				
					if ( send_leaky_paywall_email( $_REQUEST['email'] ) )
						return '<h3>' . $args['email_sent'] . '</h3>';
					else
						$results .= '<h1 class="error">' . $args['error_msg'] . '</h1>';
					
				} else {
				
					$results .= '<h1 class="error">' . $args['missing_email_msg'] . '</h1>';
					
				}
				
			}
			
			$results .= '<h2>' . $args['heading'] . '</h2>';
			$results .= '<form action="" method="post">';
			$results .= '<input type="text" id="leaky-paywall-login-email" name="email" placeholder="valid@email.com" value="" />';
			$results .= '<input type="submit" id="leaky-paywall-submit-buttom" name="submit-leaky-login" value="' . __( 'Send Login Email', 'leaky-paywall' ) . '" />';
			$results .= '</form>';
			$results .= '<h3>' . $args['description'] . '</h3>';
	
		} else { //traditional
			
			if ( $args['login_redirect_url'] ) {
				$page_link = $args['login_redirect_url'];
			} else if ( !empty( $settings['page_for_profile'] ) ) {
				$page_link = get_page_link( $settings['page_for_profile'] );
			} else if ( !empty( $settings['page_for_subscription'] ) ) {
				$page_link = get_page_link( $settings['page_for_subscription'] );
			}
			
            $results .= apply_filters( 'leaky_paywall_before_login_form', '' );

            $results .= '<div id="leaky-paywall-login-form">';
                        		
			add_action( 'login_form_bottom', 'leaky_paywall_add_lost_password_link' );
			$args = array(
				'echo' => false,
				'redirect' => $page_link,
			);
			$results .= wp_login_form( apply_filters( 'leaky_paywall_login_form_args', $args ) );
			
			$results .= '</div>';

		}
		
		return $results;
		
	}
	add_shortcode( 'leaky_paywall_login', 'do_leaky_paywall_login' );
	
}

if ( !function_exists( 'do_leaky_paywall_subscription' ) ) { 

	/**
	 * Shortcode for zeen101's Leaky Paywall
	 * Prints out the zeen101's Leaky Paywall
	 *
	 * @since 1.0.0
	 */
	function do_leaky_paywall_subscription( $atts ) {
		
		if ( isset( $_REQUEST['level_id'] ) ) {
			return do_leaky_paywall_register_form();
		}
		
		$settings = get_leaky_paywall_settings();
		$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';
		
		$defaults = array(
			'login_heading' 	=> __( 'Enter your email address to start your subscription:', 'leaky-paywall' ),
			'login_desc' 		=> __( 'Check your email for a link to start your subscription.', 'leaky-paywall' ),
		);

		// Merge defaults with passed atts
		// Extract (make each array element its own PHP var
		$args = shortcode_atts( $defaults, $atts );
		
		$results = '';
				
		if ( is_user_logged_in() ) {
			
			$sites = array( '' );
			if ( is_multisite_premium() ) {
				global $blog_id;			
				if ( !is_main_site( $blog_id ) ) {
					$sites = array( '_all', '_' . $blog_id );
				} else {
					$sites = array( '_all', '_' . $blog_id, '' );
				}
			}
			
			$user = wp_get_current_user();
				
			$results .= apply_filters( 'leaky_paywall_subscriber_info_start', '' );
			
			$results .= '<div class="issuem-leaky-paywall-subscriber-info">';

			foreach ( $sites as $site ) {
				
				if ( false !== $expires = leaky_paywall_has_user_paid( $user->user_email, $site ) ) {
						
					$results .= apply_filters( 'leaky_paywall_subscriber_info_paid_subscriber_start', '' );
					
					$payment_gateway = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_gateway' . $site, true );
					$subscriber_id = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_subscriber_id' . $site, true );
					$plan = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_plan' . $site, true );
					
					if ( empty( $payment_gateway ) && empty( $subscriber_id ) ) {
						continue;
					}
					
					switch( $expires ) {
					
						case 'subscription':

							if ( !$plan ) {
								// continue;
								break;
							}
							
							if ( 'free_registration' != $payment_gateway ) {
								$results .= sprintf( __( 'Your subscription will automatically renew until you <a href="%s">cancel</a>', 'leaky-paywall' ), '?cancel&payment_gateway=' . $payment_gateway . '&subscriber_id=' . $subscriber_id );
							}
							break;
							
						case 'unlimited':
							$results .= __( 'You are a lifetime subscriber!', 'leaky-paywall' );
							break;
					
						case 'canceled':
							$results .= sprintf( __( 'Your subscription has been canceled. You will continue to have access to %s until the end of your billing cycle. Thank you for the time you have spent subscribed to our site and we hope you will return soon!', 'leaky-paywall' ), $settings['site_name'] );
							break;
							
						default:
							$results .= sprintf( __( 'You are subscribed via %s until %s.', 'leaky-paywall' ), leaky_paywall_translate_payment_gateway_slug_to_name( $payment_gateway ), date_i18n( get_option('date_format'), strtotime( $expires ) ) );
							
					}
					
					$results .= apply_filters( 'leaky_paywall_subscriber_info_paid_subscriber_end', '' );
					
					$results .= '<p><a href="' . wp_logout_url( get_page_link( $settings['page_for_login'] ) ) . '">' . __( 'Log Out', 'leaky-paywall' ) . '</a></p>';
					
					break; //We only want one
									
				}
			
			}
			
			$results .= '</div>';
			
			$results .= apply_filters( 'leaky_paywall_subscriber_info_end', '' );
			
		}			
			
		$results .= leaky_paywall_subscription_options();
				
		return $results;
		
	}
	add_shortcode( 'leaky_paywall_subscription', 'do_leaky_paywall_subscription' );
	
}

if ( !function_exists( 'do_leaky_paywall_profile' ) ) { 

	/**
	 * Shortcode for zeen101's Leaky Paywall
	 * Prints out the zeen101's Leaky Paywall
	 *
	 * @since CHANGEME
	 */
	function do_leaky_paywall_profile( $atts ) {
		
		$settings = get_leaky_paywall_settings();
		$mode = 'off' === $settings['test_mode'] ? 'live' : 'test';

		$defaults = array(
		);
	
		// Merge defaults with passed atts
		// Extract (make each array element its own PHP var
		$args = shortcode_atts( $defaults, $atts );
		extract( $args );
		
		$results = '';
		
		if ( is_user_logged_in() ) {
			
			$sites = array( '' );
			global $blog_id;
			if ( is_multisite_premium() ) {
				if ( !is_main_site( $blog_id ) ) {
					$sites = array( '_all', '_' . $blog_id );
				} else {
					$sites = array( '_all', '_' . $blog_id, '' );
				}
			}			
			$user = wp_get_current_user();
			
			$results .= '<p class="leaky-paywall-logout-link">' . sprintf( __( 'Welcome %s, you are currently logged in. <a href="%s">Click here to log out.</a>', 'leaky-paywall' ) . '</p>', $user->user_login, wp_logout_url( get_page_link( $settings['page_for_login'] ) ) );
			
			//Your Subscription
			$results .= '<h2 class="leaky-paywall-profile-subscription-title">' . __( 'Your Subscription', 'leaky-paywall' ) . '</h2>';

			$results .= apply_filters( 'leaky_paywall_profile_your_subscription_start', '' );
			
			$profile_table = '<table class="leaky-paywall-profile-subscription-details">';
			$profile_table .= '<thead>';
			$profile_table .= '<tr>';
			$profile_table .= '	<th>' . __( 'Status', 'leaky-paywall' ) . '</th>';
			$profile_table .= '	<th>' . __( 'Type', 'leaky-paywall' ) . '</th>';
			$profile_table .= '	<th>' . __( 'Payment Method', 'leaky-paywall' ) . '</th>';
			$profile_table .= '	<th>' . __( 'Expiration', 'leaky-paywall' ) . '</th>';
			$profile_table .= '</tr>';
			$profile_table .= '</thead>';
			foreach( $sites as $site ) {
				$status = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_status' . $site, true );
				
				$level_id = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_level_id' . $site, true );
				$level_id = apply_filters( 'get_leaky_paywall_users_level_id', $level_id, $user, $mode, $site );
				$level_id = apply_filters( 'get_leaky_paywall_subscription_level_level_id', $level_id );
				if ( false === $level_id || empty( $settings['levels'][$level_id]['label'] ) ) {
					$level_name = __( 'Undefined', 'leaky-paywall' );
				} else {
					$level_name = stripcslashes( $settings['levels'][$level_id]['label'] );
				}
				
				$payment_gateway = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_gateway' . $site, true );
				
				$expires = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_expires' . $site, true );
				$expires = apply_filters( 'do_leaky_paywall_profile_shortcode_expiration_column', $expires, $user, $mode, $site, $level_id );
				if ( empty( $expires ) || '0000-00-00 00:00:00' === $expires ) {
					$expires = __( 'Never', 'leaky-paywall' );
				} else {
					$date_format = get_option( 'date_format' );
					$expires = mysql2date( $date_format, $expires );
				}
				
				$plan = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_plan' . $site, true );
				if ( !empty( $plan ) && 'Canceled' !== $plan && 'Never' !== $expires ) {
					
					if ( $status == 'canceled' ) {
						$expires = sprintf( __( 'Ends on %s', 'leaky-paywall' ), $expires );	
					} else {
						$expires = sprintf( __( 'Recurs on %s', 'leaky-paywall' ), $expires );	
					}
					
				}
							
				$paid = leaky_paywall_has_user_paid( $user->user_email, $site );
				$expiration = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_expires' . $site, true );
				$cancel = '';

				if ( empty( $expires) || '0000-00-00 00:00:00' === $expiration) {
					$cancel = '';
				} else if ( strcasecmp('active', $status) == 0 && $plan && 'Canceled' !== $plan ) {
					$subscriber_id = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_subscriber_id' . $site, true );
					$payment_gateway = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_gateway' . $site, true );

					if ( $payment_gateway == 'free_registration' ) {
						$cancel = '';
					} else {
						$cancel = sprintf( __( '<a href="%s">Cancel your subscription</a>', 'leaky-paywall' ), '?cancel&payment_gateway=' . $payment_gateway . '&subscriber_id=' . $subscriber_id );
					}
					
				} else if ( !empty( $plan ) && 'Canceled' == $plan ) {
					$cancel = __( 'You have canceled your subscription, but your account will remain active until your expiration date.', 'leaky-paywall' );
				} 

				if ( 'stripe' == $payment_gateway ) {
					$profile_payment = 'Credit Card';
				} else {
					$profile_payment = leaky_paywall_translate_payment_gateway_slug_to_name( $payment_gateway );
				}
				
				if ( !empty( $status ) && !empty( $level_name ) && !empty( $payment_gateway ) && !empty( $expires ) ) {
					$profile_table .= '<tbody>';
					$profile_table .= '	<td>' . ucfirst( $status ) . '</td>';
					$profile_table .= '	<td>' . $level_name . '</td>';
					$profile_table .= '	<td>' . $profile_payment . '</td>';
					$profile_table .= '	<td>' . $expires . '</td>';
					$profile_table .= '</tbody>';
				}
			}
			$profile_table .= '</table>';

			if ( $cancel ) {
				$profile_table .= '<p class="leaky-paywall-cancel-link">' . apply_filters( 'leaky_paywall_cancel_link', $cancel ) . '</p>';
			}


			$results .= apply_filters( 'leaky_paywall_profile_table', $profile_table, $user, $sites, $mode, $settings );
			$results .= apply_filters( 'leaky_paywall_profile_your_subscription_end', '' );
			
			$results .= '<div class="issuem-leaky-paywall-subscriber-info">';
						
			if ( false !== $expires = leaky_paywall_has_user_paid() ) {
				
				if (isset($_POST['stripeToken'])) {

					$secret_key = ( 'test' === $mode ) ? $settings['test_secret_key'] : $settings['live_secret_key'];

					\Stripe\Stripe::setApiKey( $secret_key );

					try {

						foreach ( $sites as $site ) {
							$subscriber_id = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_subscriber_id' . $site, true );
							if ( !empty( $subscriber_id ) ) {
								break;
							}
						}

					    $cu = \Stripe\Customer::retrieve($subscriber_id); // stored in your application
					    $cu->source = $_POST['stripeToken']; // obtained with Checkout
					    $cu->save();

					    $update_card_success = __( 'Your card details have been updated!', 'leaky-paywall' );

					  }
					  catch(\Stripe\Error\Card $e) {

					    $body = $e->getJsonBody();
					    $err  = $body['error'];
					    $update_card_error = $err['message'];

					  } catch(\Stripe\Error\InvalidRequest $e) {
					  	$body = $e->getJsonBody();
					  	$err  = $body['error'];
					  	$update_card_error = $err['message'];
					  }
				}

				$results .= apply_filters( 'leaky_paywall_profile_your_payment_info_start', '' );
				
				$results .= apply_filters( 'leaky_paywall_subscriber_info_paid_subscriber_start', '' );
				
				foreach( $sites as $site ) {	
					$payment_gateway = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_gateway' . $site, true );
					$subscriber_id = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_subscriber_id' . $site, true );
					$status = get_user_meta( $user->ID, '_issuem_leaky_paywall_' . $mode . '_payment_status' . $site, true );
					$expires = leaky_paywall_has_user_paid( $user->user_email, $site );

				if ( strcasecmp('active', $status) == 0 || strcasecmp('deactivated', $status) == 0 ) {
						$payment_form = '';
						switch( $payment_gateway ) {
							
							case 'stripe':
								
								if ( isset( $update_card_error ) ) {
								  $results .= '<div class="leaky_paywall_message error"><p>' . $update_card_error . '</p></div>';
								} elseif ( isset( $update_card_success ) ) {
								  $results .= '<div class="leaky_paywall_message success"><p>' . $update_card_success . '</p></div>';
								}

								
								$publishable_key = 'on' === $settings['test_mode'] ? $settings['test_publishable_key'] : $settings['live_publishable_key'];

								$payment_form .= '<form action="" method="POST">
									  <script
									  src="https://checkout.stripe.com/checkout.js" class="stripe-button"
									  data-key="' . $publishable_key . '"
									  data-name="' . get_bloginfo( 'name' ) . '"
									  data-panel-label="Update Card Details"
									  data-label="Update Credit Card Details"
									  data-allow-remember-me=false
									  data-email="' . $user->user_email . '"
									  data-locale="auto">	
									  </script>	
									</form>';

								
								break;
								
							case 'paypal-standard':
							case 'paypal_standard':
								$paypal_url   = 'test' === $mode ? 'https://www.sandbox.paypal.com/' : 'https://www.paypal.com/';
								$paypal_email = 'test' === $mode ? $settings['paypal_sand_email'] : $settings['paypal_live_email'];
								$payment_form .= '<p>' . __( "You can update your payment details through PayPal's website.", 'leaky-paywall' ) . '</p>';
								$payment_form .= '<p><a href="' . $paypal_url . '"><img src="https://www.paypalobjects.com/webstatic/en_US/btn/btn_pponly_142x27.png" border="0"></a></p>';
								break;
						}
						
						if ( $payment_form ) {
							$results .= '<hr>';
							$results .= '<h2 class="leaky-paywall-your-payment-information-header">' . __( 'Your Payment Information', 'leaky-paywall' ) . '</h2>';
							
							$results .= $payment_form;
						}
						
						break; //We only want the first match
					}
				}
			} else if ( !empty( $plan ) && 'Canceled' == $plan ) {
				$results .= '<h2 class="leaky-paywall-subscription-status-header">' . __( 'Your Subscription Has Been Canceled', 'leaky-paywall' ) . '</h2>';
				$results .= '<p>' . sprintf( __( 'You have canceled your subscription, but your account will remain active until your expiration date. To reactivate your subscription, please visit our <a href="%s">Subscription page</a>.', 'leaky-paywall' ), get_page_link( $settings['page_for_subscription'] ) ) . '</p>';
			} else {
				
				$results .= '<h2 class="leaky-paywall-subscription-status-header">' . __( 'Your Account is Not Currently Active', 'leaky-paywall' ) . '</h2>';
				$results .= '<p>' . sprintf( __( 'To reactivate your account, please visit our <a href="%s">Subscription page</a>.', 'leaky-paywall' ), get_page_link( $settings['page_for_subscription'] ) ) . '</p>';
				
			}
			$results .= '</div>';
			$results .= apply_filters( 'leaky_paywall_profile_your_payment_info_end', '' );


			$results .= '<hr>';

			$results .= apply_filters( 'leaky_paywall_profile_your_profile_start', '' );

			//Your Profile
			
			$results .= '<h2 class="leaky-paywall-your-profile-header">' . __( 'Your Profile', 'leaky-paywall' ) . '</h2>';

			

			if ( !empty( $_POST['leaky-paywall-profile-nonce'] ) ) {
				
				if ( wp_verify_nonce( $_POST['leaky-paywall-profile-nonce'], 'leaky-paywall-profile' ) ) {
					
					try {
						$userdata = get_userdata( $user->ID );
						$args = array(
							'ID' 			=> $user->ID,
							'user_login' 	=> $userdata->user_login,
							'display_name' 	=> $userdata->display_name,	
							'user_email' 	=> $userdata->user_email,	
						);
						
						if ( !empty( $_POST['username'] ) ) {
							$args['user_login'] = sanitize_text_field( $_POST['username'] );
						}
						
						if ( !empty( $_POST['displayname'] ) ) {
							$args['display_name'] = sanitize_text_field( $_POST['displayname'] );
						}
						
						if ( !empty( $_POST['email'] ) ) {
							if ( is_email( $_POST['email'] ) ) {
								$args['user_email'] = sanitize_text_field( $_POST['email'] );
							} else {
								throw new Exception( __( 'Invalid email address.', 'leaky-paywall' ) );
							}
						}
						
						if ( !empty( $_POST['password1'] ) && !empty( $_POST['password2'] ) ) {
							if ( $_POST['password1'] === $_POST['password2'] ) {
								wp_set_password( sanitize_text_field( $_POST['password1'] ), $user->ID );
							} else {
								throw new Exception( __( 'Passwords do not match.', 'leaky-paywall' ) );
							}
						}
						
						$user_id = wp_update_user( $args );
												
						if ( is_wp_error( $user_id ) ) {
							throw new Exception( $user_id->get_error_message() );
						} else {
							$user = get_userdata( $user_id ); //Refresh the user object				
							$results .= '<div class="leaky_paywall_message success"><p>' . __( 'Profile Changes Saved.', 'leaky-paywall' ) . '</p></div>';

							do_action( 'leaky_paywall_after_profile_changes_saved', $user_id, $args, $userdata );
							
						}		
						
					}
					catch ( Exception $e ) {
						$results .= '<div class="leaky_paywall_message error"><p class="error">' . $e->getMessage() . '</p></div>';
					}
					
				}
				
			}

			$results .= '<form id="leaky-paywall-profile" action="" method="post">';
			
			$results .= '<p>';
			$results .= '<label class="leaky-paywall-field-label" for="leaky-paywall-username">' . __( 'Username', 'leaky-paywall' ) . '</label>';
			$results .= '<input type="text" class="issuem-leaky-paywall-field-input" id="leaky-paywall-username" name="username" value="' . $user->user_login . '" disabled="disabled" readonly="readonly" />';
			$results .= '</p>';
			
			$results .= '<p>';
			$results .= '<label class="leaky-paywall-field-label" for="leaky-paywall-display-name">' . __( 'Display Name', 'leaky-paywall' ) . '</label>';
			$results .= '<input type="text" class="issuem-leaky-paywall-field-input" id="leaky-paywall-display-name" name="displayname" value="' . $user->display_name . '" />';
			$results .= '</p>';

			$results .= '<p>';
			$results .= '<label class="leaky-paywall-field-label" for="leaky-paywall-email">' . __( 'Email', 'leaky-paywall' ) . '</label>';
			$results .= '<input type="text" class="issuem-leaky-paywall-field-input" id="leaky-paywall-email" name="email" value="' . $user->user_email . '" />';
			$results .= '</p>';


			$results .= '<p>';
			$results .= '<label class="leaky-paywall-field-label" for="leaky-paywall-password1">' . __( 'New Password', 'leaky-paywall' ) . '</label>';
			$results .= '<input type="password" class="issuem-leaky-paywall-field-input" id="leaky-paywall-password1" name="password1" value="" />';
			$results .= '</p>';

			$results .= '<p>';
			$results .= '<label class="leaky-paywall-field-label" for="leaky-paywall-gift-subscription-password2">' . __( 'New Password (again)', 'leaky-paywall' ) . '</label>';
			$results .= '<input type="password" class="issuem-leaky-paywall-field-input" id="leaky-paywall-gift-subscription-password2" name="password2" value="" />';
			$results .= '</p>';

			$results .= apply_filters( 'leaky_paywall_profile_your_profile_before_submit', '' );
			
			$results .= wp_nonce_field( 'leaky-paywall-profile', 'leaky-paywall-profile-nonce', true, false );
			
			$results .= '<p class="submit"><input type="submit" id="submit" class="button button-primary" value="' . __( 'Save Profile Changes', 'leaky-paywall' ) . '"  /></p>'; 
			$results .= '</form>';

			if ( 'on' == $settings['enable_user_delete_account'] ) {
				$results .= '<form id="leaky-paywall-delete-account" action="" method="post">';
				$results .= '<p><button type="submit" onclick="return confirm(\'Deleting your account will delete your access and all your information on this site. If you have a recurring subscription, you must cancel that first to stop payments. Are you sure you want to continue?\')">' . __( 'Delete Account', 'leaky-paywall' ) . '</button></p>';
				$results .= wp_nonce_field( 'leaky-paywall-delete-account', 'leaky-paywall-delete-account-nonce', true, false );
				$results .= '</form>';

				
			}
			
			$results .= apply_filters( 'leaky_paywall_profile_your_profile_end', '' );
			
		} else {
			
			$results .= do_leaky_paywall_login( array() );
			
		}
		
		return $results;
		
	}
	add_shortcode( 'leaky_paywall_profile', 'do_leaky_paywall_profile' );
	
}

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