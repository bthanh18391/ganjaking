<?php
/**
 * Plugin Name: reCaptcha for WooCommerce
 * Plugin URI: http://wordpress.org/plugins/woo-recpatcha
 * Description: Protect your eCommerce site with google recptcha.
 * Version: 1.0.15
 * Author: I Thirteen Web Solution 
 * Author URI: http://www.i13websolution.com
 * WC requires at least: 3.2
 * WC tested up to: 4.4.0
 * Text Domain:recaptcha-for-woocommerce
 * Domain Path: languages/
 * Woo: 5347485:aeae74683dd892d43ed390cc28533524
 */


defined('ABSPATH') || exit();

class I13_Woo_Recpatcha {


	public function __construct() {



		// Add Javascript and CSS for admin screens
		add_action('plugins_loaded', array($this, 'i13_woo_load_lang_for_woo_recaptcha'));
		add_action('wp_enqueue_scripts', array($this, 'i13_woo_recaptcha_load_styles_and_js'), 9999);
		add_action('login_enqueue_scripts', array($this, 'i13_woo_recaptcha_load_styles_and_js'), 9999);
		

		add_action('login_form', array($this, 'i13woo_extra_wp_login_form'));
		add_action('register_form', array($this, 'i13woo_extra_wp_register_form'));
		add_action('lostpassword_form', array($this, 'i13woo_extra_wp_lostpassword_form'));
		add_action('woocommerce_register_form', array($this, 'i13woo_extra_register_fields'));
		add_action('woocommerce_login_form', array($this, 'i13woo_extra_login_fields'));
		add_action('woocommerce_lostpassword_form', array($this, 'i13woo_extra_lostpassword_fields'));
		add_action('woocommerce_review_order_before_submit', array($this, 'i13woo_extra_checkout_fields'));
		add_action('woocommerce_register_post', array($this, 'i13_woocomm_validate_signup_captcha'), 10, 3);
		add_action('lostpassword_post', array($this, 'i13_woocomm_validate_lostpassword_captcha'), 10, 1);
		add_action('woocommerce_process_login_errors', array($this, 'i13_woocomm_validate_login_captcha'), 10, 3);
		add_action('woocommerce_after_checkout_validation', array($this, 'i13_woocomm_validate_checkout_captcha'), 10, 2);
		add_filter('woocommerce_get_settings_pages', array($this, 'i13_woocomm_load_custom_settings_tab'));
		add_filter('wp_authenticate_user', array($this, 'i13_woo_wp_verify_login_captcha'), 10, 2);
		add_filter('register_post', array($this, 'i13_woo_verify_wp_register_captcha'), 10, 3);
		add_filter('lostpassword_post', array($this, 'i13_woo_verify_wp_lostpassword_captcha'), 10, 1);
		add_filter('wpforms_frontend_recaptcha_noconflict', array($this, 'i13_woo_remove_no_conflict'));
				add_action( 'woocommerce_payment_complete', array($this, 'i13_woo_payment_complete') );
				add_action( 'wp_footer', array($this, 'i13_woo_add_payment_method') );
							  
		 
					// add the action 
					add_action( 'woocommerce_init', array($this, 'i13_woocommerce_init') );
	}
		
		
	public function i13_woo_payment_complete( $order_id ) {

		$nonece=isset( $_POST['woocommerce-process-checkout-nonce'] ) ? wc_clean( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if (wp_verify_nonce($nonece, 'woocommerce-process_checkout')) {
			if (!empty($nonece)) {

					delete_transient($nonece);
			}
		}

	}


	public function i13_woo_remove_no_conflict() {
			
		return false;
	}
		
	public function i13_woocomm_load_custom_settings_tab( $settings) {

		$settings[] = include plugin_dir_path(__FILE__) . 'includes/Settings.php';
		return $settings;
	}

	public function i13_woocomm_validate_signup_captcha( $username, $email, $validation_errors) {
				
		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_signup');
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
				$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');
		$captcha_lable = get_option('i13_recapcha_signup_title');
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		$nonce_value = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.NoNonceVerification
		$nonce_value = isset($_POST['woocommerce-register-nonce']) ? sanitize_text_field(wp_unslash($_POST['woocommerce-register-nonce'])) : $nonce_value; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.NoNonceVerification

		if ('yes' == $is_enabled && isset($_POST['woocommerce-register-nonce']) && !empty($_POST['woocommerce-register-nonce'])) {

			if (wp_verify_nonce($nonce_value, 'woocommerce-register')) {

				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							if (''==trim($recapcha_error_msg_captcha_invalid)) {

								 $validation_errors->add('g-recaptcha_error', __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));

							} else {
															
									$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_invalid);
							}
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
							$validation_errors->add('g-recaptcha_error', __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
								$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_no_response);
						}
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {
											
						$validation_errors->add('g-recaptcha_error', __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
											
					} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_blank);
					}
				}
			} else {

				$validation_errors->add('g-recaptcha_error', __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}

		return $validation_errors;
	}

	public function i13_woo_verify_wp_register_captcha( $username, $email, $validation_errors) {


		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_wpregister');

		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');

		$captcha_lable = trim(get_option('i13_recapcha_wpregister_title'));
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='captcha';
		}
				
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		$nonce_value = isset($_POST['wp-register-nonce']) ? sanitize_text_field(wp_unslash($_POST['wp-register-nonce'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.NoNonceVerification

		if ('yes' == $is_enabled && isset($_POST['wp-submit']) && !empty($_POST['wp-submit'])) {

			if (wp_verify_nonce($nonce_value, 'wp-register-nonce')) {

				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {
							if (''==trim($recapcha_error_msg_captcha_invalid)) {
															
									$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_invalid);
							}
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_no_response);
						}
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {
											
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_blank);
					}
				}
			} else {

				$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}

		return $validation_errors;
	}

	public function i13_woo_verify_wp_lostpassword_captcha( $validation_errors) {

		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_wplostpassword');

		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');
		$nonce_value = isset($_POST['wp-lostpassword-nonce']) ? sanitize_text_field(wp_unslash($_POST['wp-lostpassword-nonce'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.NoNonceVerification

		$captcha_lable = get_option('i13_recapcha_wplostpassword_title');
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='captcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		if ('yes' == $is_enabled && isset($_POST['wp-submit']) && !empty($_POST['wp-submit'])) {

			if (wp_verify_nonce($nonce_value, 'wp-lostpassword-nonce')) {
				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							if (''==trim($recapcha_error_msg_captcha_invalid)) {
															
									$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_invalid);
							}
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
												   $validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_no_response);
						}
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {
											
											   $validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_blank);
					}
				}
			} else {

				$validation_errors->add('g-recaptcha_error', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}

		return $validation_errors;
	}

	public function i13_woocomm_validate_checkout_captcha( $fields, $validation_errors) {
				
		$captcha_lable = get_option('i13_recapcha_guestcheckout_title');
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='recaptcha';
		}
				
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');
		$i13_recapcha_checkout_timeout = get_option('i13_recapcha_checkout_timeout');
		if (null==$i13_recapcha_checkout_timeout || ''==$i13_recapcha_checkout_timeout) {
					
			$i13_recapcha_checkout_timeout=3;
		}
		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_guestcheckout');
		$is_enabled_logincheckout = get_option('i13_recapcha_enable_on_logincheckout');

		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', '<strong>' . ucfirst($captcha_lable) . '</strong>', $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', '<strong>' . $captcha_lable . '</strong>', $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', '<strong>' . $captcha_lable . '</strong>', $recapcha_error_msg_captcha_invalid);

		if ('yes' == $is_enabled && ( ( isset($_POST['woocommerce-process-checkout-nonce']) && !empty($_POST['woocommerce-process-checkout-nonce']) ) || ( isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce']) ) ) && !is_user_logged_in()) {

			$nonce_value = '';
			if (isset($_REQUEST['woocommerce-process-checkout-nonce']) || isset($_REQUEST['_wpnonce'])) {

				if (isset($_REQUEST['woocommerce-process-checkout-nonce']) && !empty($_REQUEST['woocommerce-process-checkout-nonce'])) {
									
					$nonce_value=sanitize_text_field($_REQUEST['woocommerce-process-checkout-nonce']);
				} else if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {
									
					$nonce_value=sanitize_text_field($_REQUEST['_wpnonce']);
				}
				
			}

			if (wp_verify_nonce($nonce_value, 'woocommerce-process_checkout')) {

				if ('yes'==get_transient($nonce_value)) {

					return $validation_errors;
				}

				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
									
									   
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							if (''==trim($recapcha_error_msg_captcha_invalid)) {
	
																$validation_errors->add('g-recaptcha_error', __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_invalid);
							}
														
						} else {
													
							if (0!=$i13_recapcha_checkout_timeout) {
								
								set_transient($nonce_value, 'yes', ( $i13_recapcha_checkout_timeout*60 ));
							}
						}
												
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
													$validation_errors->add('g-recaptcha_error', __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_no_response);
						}  
					}
				} else {

					
					if (''==trim($recapcha_error_msg_captcha_blank)) {
		
							  $validation_errors->add('g-recaptcha_error', __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_blank);
					}  
				}
			} else {
				$validation_errors->add('g-recaptcha_error', __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		} else if ('yes' == $is_enabled_logincheckout && ( ( isset($_POST['woocommerce-process-checkout-nonce']) && !empty($_POST['woocommerce-process-checkout-nonce']) ) || ( isset($_POST['_wpnonce']) && !empty($_POST['_wpnonce']) ) ) && is_user_logged_in()) {
						
			$nonce_value = '';
			if (isset($_REQUEST['woocommerce-process-checkout-nonce']) || isset($_REQUEST['_wpnonce'])) {

				if (isset($_REQUEST['woocommerce-process-checkout-nonce']) && !empty($_REQUEST['woocommerce-process-checkout-nonce'])) {
									
					$nonce_value=sanitize_text_field($_REQUEST['woocommerce-process-checkout-nonce']);
				} else if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {
									
					$nonce_value=sanitize_text_field($_REQUEST['_wpnonce']);
				}
				
			}
						
			if (wp_verify_nonce($nonce_value, 'woocommerce-process_checkout')) {

								
				if ('yes'==get_transient($nonce_value)) {
									
					 return $validation_errors;
				}
				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
									
									  
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							if (''==trim($recapcha_error_msg_captcha_invalid)) {

									 $validation_errors->add('g-recaptcha_error', __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_invalid);
							}
							
						} else {
													
							if (0!=$i13_recapcha_checkout_timeout) {
								
								set_transient($nonce_value, 'yes', ( $i13_recapcha_checkout_timeout*60 ));
							}
						}
					} else {

						
						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
							$validation_errors->add('g-recaptcha_error', __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_no_response);
						}  
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {

							 $validation_errors->add('g-recaptcha_error', __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_blank);
					}  
				}
			} else {
							
				$validation_errors->add('g-recaptcha_error', __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}
				
		return $validation_errors;
	}

	public function i13_woocomm_validate_lostpassword_captcha( $validation_errors) {

		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_lostpassword');
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');

		$captcha_lable = get_option('i13_recapcha_lostpassword_title');
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		$nonce_value = '';
		if (isset($_REQUEST['woocommerce-lost-password-nonce']) || isset($_REQUEST['_wpnonce'])) {
					
			if (isset($_REQUEST['woocommerce-lost-password-nonce']) && !empty($_REQUEST['woocommerce-lost-password-nonce'])) {

				  $nonce_value=sanitize_text_field($_REQUEST['woocommerce-lost-password-nonce']);
			} else if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {

				$nonce_value=sanitize_text_field($_REQUEST['_wpnonce']);
			}
			
		}
		if ('yes' == $is_enabled && isset($_POST['wc_reset_password'])) {

			if (wp_verify_nonce($nonce_value, 'lost_password')) {

				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							if (''==trim($recapcha_error_msg_captcha_invalid)) {
	
																 $validation_errors->add('g-recaptcha_error', __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_invalid);
							}
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {

													$validation_errors->add('g-recaptcha_error', __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
													
													
						} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_no_response);
						}   
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {

												$validation_errors->add('g-recaptcha_error', __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_blank);
					} 
				}
			} else {

				$validation_errors->add('g-recaptcha_error', __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}

		return $validation_errors;
	}

	public function i13_woocomm_validate_login_captcha( $validation_errors, $username, $password) {

				
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');

		$captcha_lable = get_option('i13_recapcha_login_title');
		if (''==trim($captcha_lable)) {
					
			$captcha_lable='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_login');

		$nonce_value = '';
		if (isset($_REQUEST['woocommerce-login-nonce']) || isset($_REQUEST['_wpnonce'])) {
					
					
			if (isset($_REQUEST['woocommerce-login-nonce']) && !empty($_REQUEST['woocommerce-login-nonce'])) {

				$nonce_value=sanitize_text_field($_REQUEST['woocommerce-login-nonce']);
			} else if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {

				$nonce_value=sanitize_text_field($_REQUEST['_wpnonce']);
			}
			
		}
		if ('yes' == $is_enabled && isset($_POST['login'])) {

			if (wp_verify_nonce($nonce_value, 'woocommerce-login')) {
				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {
													
							if (''==trim($recapcha_error_msg_captcha_invalid)) {

									$validation_errors->add('g-recaptcha_error', __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_invalid);
							}
	
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
							$validation_errors->add('g-recaptcha_error', __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_no_response);
						}
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {

												  $validation_errors->add('g-recaptcha_error', __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							$validation_errors->add('g-recaptcha_error', $recapcha_error_msg_captcha_blank);
					}  
				}
			} else {

				$validation_errors->add('g-recaptcha_error', __('Could not verify request.', 'recaptcha-for-woocommerce'));
			}
		}

		return $validation_errors;
	}

	public function i13_woo_wp_verify_login_captcha( $user, $password) {

				
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
		$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');
		$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
		$is_enabled = get_option('i13_recapcha_enable_on_wplogin');

		$captcha_lable = get_option('i13_recapcha_wplogin_title');
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
		$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
		$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);

		$nonce_value = isset($_POST['wp-login-nonce']) ? sanitize_text_field(wp_unslash($_POST['wp-login-nonce'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.NoNonceVerification
		if ('yes' == $is_enabled && isset($_POST['wp-submit'])) {

			if (wp_verify_nonce($nonce_value, 'wp-login-nonce')) {
						
					
				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					// Google reCAPTCHA API secret key 
					$response = sanitize_text_field($_POST['g-recaptcha-response']);

					// Verify the reCAPTCHA response 
					$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

					if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

						// Decode json data 
						$responseData = json_decode($verifyResponse['body']);

						// If reCAPTCHA response is valid 
						if (!$responseData->success) {

							
							if (''==trim($recapcha_error_msg_captcha_invalid)) {
		
									return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Invalid recaptcha.', 'recaptcha-for-woocommerce'));
							} else {
									return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_invalid);
							}
						}
					} else {

						if (''==trim($recapcha_error_msg_captcha_no_response)) {
													
							  return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));
						} else {
							return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_no_response);
						}
						
					}
				} else {

					if (''==trim($recapcha_error_msg_captcha_blank)) {
											
						   return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'));
					} else {
							return new WP_Error('Captcha Invalid', '<strong>' . __('ERROR:', 'recaptcha-for-woocommerce') . '</strong> ' . $recapcha_error_msg_captcha_blank);
					}

					
				}
			} else {
						
				return new WP_Error('Captcha Invalid', '<strong>' . __('Error:', 'recaptcha-for-woocommerce') . '</strong> ' . __('Could not verify request.', 'recaptcha-for-woocommerce'));
						
						
			}
		}

		return $user;
	}

	public function i13_woo_load_lang_for_woo_recaptcha() {

		load_plugin_textdomain('recaptcha-for-woocommerce', false, basename(dirname(__FILE__)) . '/languages/');
		//add_filter('map_meta_cap', array($this, 'map_i13_woo_map_woo_product_slider_meta_caps'), 10, 4);
	}
		
	public function i13_woocommerce_init( $array) {
				
		if (isset($_REQUEST['woocommerce-add-payment-method-nonce']) || isset($_REQUEST['_wpnonce']) ) {
				
			$secret_key = get_option('wc_settings_tab_recapcha_secret_key');
			$is_enabled = get_option('i13_recapcha_enable_on_addpaymentmethod');
			$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
			$recapcha_error_msg_captcha_no_response = get_option('wc_settings_tab_recapcha_error_msg_captcha_no_response');
			$recapcha_error_msg_captcha_invalid = get_option('wc_settings_tab_recapcha_error_msg_captcha_invalid');
			$captcha_lable = trim(get_option('i13_recapcha_addpaymentmethod_title'));
			if (''==$captcha_lable) {
					
				$captcha_lable='recaptcha';
			}
			$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);
			$recapcha_error_msg_captcha_no_response = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_no_response);
			$recapcha_error_msg_captcha_invalid = str_replace('[recaptcha]', $captcha_lable, $recapcha_error_msg_captcha_invalid);
				
			if ('yes' == $is_enabled && isset($_POST['woocommerce_add_payment_method']) && isset($_POST['payment_method'])) {
				
				if (isset($_REQUEST['woocommerce-add-payment-method-nonce']) && !empty($_REQUEST['woocommerce-add-payment-method-nonce'])) {
									
					$nonce_value = wc_get_var( sanitize_text_field($_REQUEST['woocommerce-add-payment-method-nonce']), '' ); // @codingStandardsIgnoreLine.
				} else if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {
									
					$nonce_value = wc_get_var( sanitize_text_field($_REQUEST['_wpnonce']), '' ); // @codingStandardsIgnoreLine.
				}
				if (!empty($nonce_value)) {
						
					if (wp_verify_nonce($nonce_value, 'woocommerce-add-payment-method')) {

							
						if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
								
								// Google reCAPTCHA API secret key 
								$response = sanitize_text_field($_POST['g-recaptcha-response']);

								// Verify the reCAPTCHA response 
								$verifyResponse = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $response);

							if (is_array($verifyResponse) && !is_wp_error($verifyResponse) && isset($verifyResponse['body'])) {

									// Decode json data 
									$responseData = json_decode($verifyResponse['body']);

									// If reCAPTCHA response is valid 
								if (!$responseData->success) {

									if (''==trim($recapcha_error_msg_captcha_invalid)) {

											return wc_add_notice(__('Invalid recaptcha.', 'recaptcha-for-woocommerce'));

									} else {
												   return wc_add_notice($recapcha_error_msg_captcha_invalid);
									}

													
								}
							} else {

								if (''==trim($recapcha_error_msg_captcha_no_response)) {
								
									return  wc_add_notice( __('Could not get response from recaptcha server.', 'recaptcha-for-woocommerce'));

								} else {
										   return wc_add_notice( $recapcha_error_msg_captcha_no_response, 'error' );
														
													   
								}
											
							}
						} else {
									
							if (''==trim($recapcha_error_msg_captcha_blank)) {
								
								return wc_add_notice( __('Recaptcha is a required field.', 'recaptcha-for-woocommerce'), 'error' );
										 
								
							} else {
									return wc_add_notice( $recapcha_error_msg_captcha_blank, 'error' );
											
							}
									
						}
							
					} else {
							
						return wc_add_notice( __('Could not verify request.', 'recaptcha-for-woocommerce'), 'error' );
							
							
								
					}
						
				}
				  
			}
				
		}
		  
	}
	public function i13_woo_add_payment_method() {
			
		$is_enabled = get_option('i13_recapcha_enable_on_addpaymentmethod');
		$disable_submit_btn = get_option('i13_recapcha_disable_submitbtn_paymentmethod');
		$captcha_lable = get_option('i13_recapcha_addpaymentmethod_title');
		$i13_recapcha_hide_label_addpayment = get_option('i13_recapcha_hide_label_addpayment');
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_addpaymentmethod_theme');
		$size = trim(get_option('i13_recapcha_addpaymentmethod_size'));
		if (''==$captcha_lable) {
				
			$captcha_lable='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable), $recapcha_error_msg_captcha_blank);

		if ('yes' == $is_enabled && is_user_logged_in() && is_wc_endpoint_url( get_option( 'woocommerce_myaccount_add_payment_method_endpoint', 'add-payment-method' )) ) {
			?> 

			  <script type="text/javascript">
			   
			 <?php $intval= uniqid('interval_'); ?>
			   
					var <?php echo esc_html($intval); ?> = setInterval(function() {

					if(document.readyState === 'complete') {

					   clearInterval(<?php echo esc_html($intval); ?>);
						var myCaptcha_payment_method = null;
					<?php if ('yes'==trim($disable_submit_btn)) : ?>
							jQuery('#add_payment_method').submit(function() {
								if(grecaptcha.getResponse()==""){


										<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
										   alert("<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
										<?php else : ?>
											alert("<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
										<?php endif; ?> 

										location.reload();
										return false; // return false to cancel form action

								}
								else{


									return true;
								}
							});
						<?php endif; ?>    
					
														
							var verifyCallback_add_payment_method = function(response) {
															 if(response.length!==0){
																<?php if ('yes'==trim($disable_submit_btn)) : ?>
																	 jQuery("#place_order").removeAttr("title");
																	 jQuery("#place_order").attr("disabled", false);
																 <?php endif; ?>    

																  if (typeof add_payment_method_recaptcha_verified === "function") { 

																	   add_payment_method_recaptcha_verified(response);
																   }
															 }   

							  };
						
														 

					  var waitForEl = function(selector, callback) {

							if (jQuery("#"+selector).length) {
							  callback_recpacha();
							} else {
							  setTimeout(function() {
								waitForEl(jQuery("#"+selector), callback);
							  }, 100);
							}
						  };

						<?php if (''==trim($captcha_lable)) : ?>
								 jQuery(".woocommerce-PaymentMethods").append(`<li id="payment_method" class="woocommerce-PaymentMethod woocommerce-PaymentMethod--stripe payment_method_stripe"><p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								 <?php 
									if ('yes'!=$i13_recapcha_hide_label_addpayment) :
										?>
 <label for="payment_method_captcha"><?php echo esc_html(__('Captcha', 'recaptcha-for-woocommerce')); ?>&nbsp;<span class="required">*</span></label><?php endif; ?><div id="g-recaptcha-payment-method" name="g-recaptcha-payment-method"  data-callback="verifyCallback_add_payment_method" data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div></li>`).ready(function () {
							<?php else : ?>
								jQuery(".woocommerce-PaymentMethods").append(`<li id="payment_method" class="woocommerce-PaymentMethod woocommerce-PaymentMethod--stripe payment_method_stripe"><p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<?php 
								if ('yes'!=$i13_recapcha_hide_label_addpayment) :
									?>
 <label for="payment_method_captcha"><?php echo esc_html($captcha_lable); ?>&nbsp;<span class="required">*</span></label><?php endif; ?><div id="g-recaptcha-payment-method" name="g-recaptcha-payment-method"  data-callback="verifyCallback_add_payment_method" data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div></li>`).ready(function () {
							<?php endif; ?>    

							 
								waitForEl('g-recaptcha-payment-method', function() {
								<?php if ('yes'==trim($disable_submit_btn)) : ?>
										jQuery("#place_order").attr("disabled", true);
									 <?php if (''==esc_html($recapcha_error_msg_captcha_blank)) : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
									 <?php else : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
									 <?php endif; ?>    
								   <?php endif; ?>
									   
									if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha_payment_method === null) {

												   try{ 
													   myCaptcha_payment_method=grecaptcha.render('g-recaptcha-payment-method', {
															'sitekey': '<?php echo esc_html($site_key); ?>',
															'callback' : verifyCallback_add_payment_method
														});
													}catch(error){}
											}
									})

							  })
						

							function callback_recpacha(){

								 if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha_payment_method === null) {

									  <?php if ('yes'==trim($disable_submit_btn)) : ?>  
												jQuery("#place_order").attr("disabled", true);
												jQuery("#place_order").attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
										   <?php endif; ?>     
											   
												try{
													myCaptcha_payment_method=grecaptcha.render('g-recaptcha-payment-method', {
														'sitekey': '<?php echo esc_html($site_key); ?>',
														'callback' : verifyCallback_add_payment_method
													});
												}catch(error){}
										
										 }

							}


					}    
				 }, 100); 
			
			  </script> 
			  
			<?php    
		}
	}

	public function i13_woo_recaptcha_load_styles_and_js() {


		//wp_register_style('i13-woo-styles', plugins_url('/public/css/styles.css', __FILE__), array(), '1.0');
		wp_register_script('i13-woo-captcha', 'https://www.google.com/recaptcha/api.js', array(), '1.0');
		$is_enabled = get_option('i13_recapcha_enable_on_guestcheckout');
				$is_enabled_on_payment_page = get_option('i13_recapcha_enable_on_addpaymentmethod');
		   
				$is_enabled_logincheckout = get_option('i13_recapcha_enable_on_logincheckout');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');

		
		if ('yes' == $is_enabled_on_payment_page && is_user_logged_in() && is_wc_endpoint_url( get_option( 'woocommerce_myaccount_add_payment_method_endpoint', 'add-payment-method' )) ) {
					
			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

					$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
												wp_dequeue_script( $handle );
												//wp_deregister_script( $handle );
												break;
						}
					}
				}
			}
			wp_enqueue_script('i13-woo-captcha');
		}
				
		if ('yes' == $is_enabled && !is_user_logged_in() && is_checkout() ) {
					
			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

					$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
												wp_dequeue_script( $handle );
												//wp_deregister_script( $handle );
												break;
						}
					}
				}
			}
			wp_enqueue_script('i13-woo-captcha');
		} else if ('yes' == $is_enabled_logincheckout && is_user_logged_in() && is_checkout() ) {
					
			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

					$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
												wp_dequeue_script( $handle );
												//wp_deregister_script( $handle );
												break;
						}
					}
				}
			}
			wp_enqueue_script('i13-woo-captcha');
		}
	}

	public function i13_woo_product_slider_admin_init() {


		wp_register_script('i13-woo-captcha', 'https://www.google.com/recaptcha/api.js', array(), '1.0');
	}

	public function i13woo_extra_register_fields() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_woo_signup');
				$i13_recapcha_hide_label_signup=get_option('i13_recapcha_hide_label_signup');
		$captcha_lable = trim(get_option('i13_recapcha_signup_title'));
		$captcha_lable_ = $captcha_lable;
				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {
					
			$captcha_lable_='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);
			  
				
				$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_signup_theme');
		$size = get_option('i13_recapcha_signup_size');
		$is_enabled = get_option('i13_recapcha_enable_on_signup');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');
				
		if ('yes' == $is_enabled) {

			if ('yes'==$i13_recapcha_no_conflict) {
				global $wp_scripts;

				$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
										wp_dequeue_script( $handle );
										//wp_deregister_script( $handle );
										break;
						}
					}
				}
			}
						
						wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
						<p id="woo_reg_recaptcha" class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_signup) :
					?>
 <label for="reg_captcha"><?php echo esc_html( ( ''==trim($captcha_lable) )? esc_html(__('Captcha', 'recaptcha-for-woocommerce')) : esc_html($captcha_lable)); ?>&nbsp;<span class="required">*</span></label><?php endif; ?>
			<div name="g-recaptcha" class="g-recaptcha" data-callback="verifyCallback_woo_signup" data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
			</p>
										<script type="text/javascript">
						<?php if ('yes'==trim($disable_submit_btn)) : ?>

							

							   var myCaptcha = null;    
							  <?php $intval_signup= uniqid('interval_'); ?>

							  var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

							  if(document.readyState === 'complete') {

									  clearInterval(<?php echo esc_html($intval_signup); ?>);
									
										  jQuery('button[name$="register"]').attr("disabled", true);
									   <?php if (''==$recapcha_error_msg_captcha_blank) : ?>
										   jQuery('button[name$="register"]').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
									   <?php else : ?>
										   jQuery('button[name$="register"]').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
									   <?php endif; ?>    
							   

										}    
							   }, 100);    

															<?php endif; ?>
														
															var verifyCallback_woo_signup = function(response) {

															   if(response.length!==0){ 
																   
																	<?php if ('yes'==trim($disable_submit_btn)) : ?>
																		 jQuery('button[name$="register"]').removeAttr("title");
																		 jQuery('button[name$="register"]').attr("disabled", false);
																	 <?php endif; ?>  

																		if (typeof woo_register_recaptcha_verified === "function") { 

																			woo_register_recaptcha_verified(response);
																		}
															   }

															 };  
																	

						  </script>
					  
			<?php
			
		}
	}

	public function i13woo_extra_checkout_fields() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_guestcheckout');
				$disable_submit_btn_login_checkout=get_option('i13_recapcha_disable_submitbtn_logincheckout');
								$i13_recapcha_hide_label_checkout=get_option('i13_recapcha_hide_label_checkout');
		$captcha_lable = get_option('i13_recapcha_guestcheckout_title');
		$captcha_lable_ = get_option('i13_recapcha_guestcheckout_title');
		$refresh_lable = get_option('i13_recapcha_guestcheckout_refresh');
		if ('' == esc_html($refresh_lable)) {
					
			$refresh_lable=__('Refresh Captcha', 'recaptcha-for-woocommerce');
		}
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_guestcheckout_theme');
		$size = get_option('i13_recapcha_guestcheckout_size');
		$is_enabled = get_option('i13_recapcha_enable_on_guestcheckout');
				$is_enabled_logincheckout = get_option('i13_recapcha_enable_on_logincheckout');

				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {
					
			$captcha_lable_='recaptcha';
		}
		$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);
				
		if ('yes' == $is_enabled && !is_user_logged_in()) {

			wp_enqueue_script('jquery');
			
			?>
			<p class="guest-checkout-recaptcha woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_checkout) :
					?>
 <label for="reg_captcha"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;<span class="required">*</span></label><?php endif; ?>
			<div id="g-recaptcha-checkout-i13" name="g-recaptcha" class="g-recaptcha" data-callback="verifyCallback_add_guestcheckout"  data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
						<div id='refresh_captcha' style="width:100%;padding-top:5px"> 
							<a href="javascript:myCaptcha=grecaptcha.reset(myCaptcha);" style="clear:both"><?php echo esc_html($refresh_lable); ?></a>
						</div>    
			
			</p>
			<script type="text/javascript">
							 var myCaptcha = null;    
							<?php $intval_guest_checkout= uniqid('interval_'); ?>

							var <?php echo esc_html($intval_guest_checkout); ?> = setInterval(function() {
								
							if(document.readyState === 'complete') {

									clearInterval(<?php echo esc_html($intval_guest_checkout); ?>);
										   
									<?php if ('yes'==trim($disable_submit_btn)) : ?>
										jQuery("#place_order").attr("disabled", true);
										<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
									 <?php else : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
									 <?php endif; ?>    
								   <?php endif; ?>
									   
									
										
									   if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha === null) {

											<?php if ('yes'==trim($disable_submit_btn)) : ?>
												try{
													myCaptcha=grecaptcha.render('g-recaptcha-checkout-i13', {
														'sitekey': '<?php echo esc_html($site_key); ?>',
														'callback' : verifyCallback_add_guestcheckout
													});
												}catch(error){}
											<?php else : ?>
											
												  try{
													  myCaptcha=grecaptcha.render('g-recaptcha-checkout-i13', {
														'sitekey': '<?php echo esc_html($site_key); ?>'
													});
												}catch(error){}
											<?php endif; ?>

										}       

										jQuery(document).on('updated_checkout', function () {

												if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha === null) {

															try{
																myCaptcha=grecaptcha.render('g-recaptcha-checkout-i13', {
																	'sitekey': '<?php echo esc_html($site_key); ?>',
																	'callback' : verifyCallback_add_guestcheckout
															});
															}catch(error){}
													
												}
										});
									
								  
								  
								}    
							 }, 100); 


							
										
														var verifyCallback_add_guestcheckout = function(response) {

															if(response.length!==0){ 
																
																	<?php if ('yes'==trim($disable_submit_btn)) : ?>
																		jQuery("#place_order").removeAttr("title");
																		jQuery("#place_order").attr("disabled", false);
																	<?php endif; ?>   

																	 if (typeof woo_guest_checkout_recaptcha_verified === "function") { 

																		   woo_guest_checkout_recaptcha_verified(response);
																	   }
															  }

														  };
														
							
			</script>
					  <?php
			
		} else if ('yes' == $is_enabled_logincheckout && is_user_logged_in()) {

			wp_enqueue_script('jquery');
			
			?>
			<p class="login-checkout-captcha woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_checkout) :
					?>
 <label for="reg_captcha"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;<span class="required">*</span></label><?php endif; ?>
			<div id="g-recaptcha-checkout-i13" name="g-recaptcha" class="g-recaptcha" data-callback="verifyCallback_add_logincheckout"   data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
						<div id='refresh_captcha' style="width:100%;padding-top:5px"> <a href="javascript:myCaptcha=grecaptcha.reset(myCaptcha);"><?php echo esc_html($refresh_lable); ?></a></div>
			
			</p>
			<script type="text/javascript">
							 var myCaptcha = null;    
							<?php $intval_login_checkout= uniqid('interval_'); ?>

							var <?php echo esc_html($intval_login_checkout); ?> = setInterval(function() {
								
							if(document.readyState === 'complete') {

									 clearInterval(<?php echo esc_html($intval_login_checkout); ?>);
									
										   
									<?php if ('yes'==trim($disable_submit_btn_login_checkout)) : ?>
										jQuery("#place_order").attr("disabled", true);
										
										<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
									 <?php else : ?>
										 jQuery("#place_order").attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
									 <?php endif; ?>    
								   <?php endif; ?>
									   
								   
										
									   if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha === null) {

												try{
													myCaptcha=grecaptcha.render('g-recaptcha-checkout-i13', {
														'sitekey': '<?php echo esc_html($site_key); ?>',
														'callback' : verifyCallback_add_logincheckout
													});
												}catch(error){}
										
										}       

										jQuery(document).on('updated_checkout', function () {

												if (typeof (grecaptcha.render) !== 'undefined' && myCaptcha === null) {

																										try{ 
																												myCaptcha=grecaptcha.render('g-recaptcha-checkout-i13', {
																															 'sitekey': '<?php echo esc_html($site_key); ?>',
																															 'callback' : verifyCallback_add_logincheckout
																													 });
																											 }catch(error){}
													
												}
										});
								
								}    
							 }, 100); 


														var verifyCallback_add_logincheckout = function(response) {

															 if(response.length!==0){ 

																	<?php if ('yes'==trim($disable_submit_btn_login_checkout)) : ?>

																	 jQuery("#place_order").removeAttr("title");
																	 jQuery("#place_order").attr("disabled", false);

																   <?php endif; ?>

																	if (typeof woo_login_checkout_recaptcha_verified === "function") { 

																		  woo_login_checkout_recaptcha_verified(response);
																	  }
															   }

															 

														  };
														
																	   

							

			</script>
			  <?php
			
		}
	}

	public function i13woo_extra_login_fields() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_woo_login');
				$i13_recapcha_hide_label_login=get_option('i13_recapcha_hide_label_login');
		$captcha_lable = get_option('i13_recapcha_login_title');
				$captcha_lable_ = $captcha_lable;
		  
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_login_theme');
		$size = get_option('i13_recapcha_login_size');
		$is_enabled = get_option('i13_recapcha_enable_on_login');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');
								
				
				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {

			$captcha_lable_='recaptcha';
		}
				$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);
				
		if ('yes' == $is_enabled) {

			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

				   $urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
											 wp_dequeue_script( $handle );
											 //wp_deregister_script( $handle );
											 break;
						}
					}
				}
			}
						wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
			<p class="woo-login-captcha woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_login) :
					?>
 <label for="login_captcha"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;<span class="required">*</span></label><?php endif; ?>
			<div name="g-recaptcha-login-i13" class="g-recaptcha" data-callback="verifyCallback_woo_login" data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>


			</p>
						
						  

								<script type="text/javascript">

									<?php $intval_signup= uniqid('interval_'); ?>

									var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

									if(document.readyState === 'complete') {

											clearInterval(<?php echo esc_html($intval_signup); ?>);
										
																							 <?php if ('yes'==trim($disable_submit_btn)) : ?>
												jQuery('button[name$="login"]').attr("disabled", true);
																									<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
													jQuery('button[name$="login"]').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
												<?php else : ?>
													jQuery('button[name$="login"]').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
												<?php endif; ?>    
									
																							 <?php endif; ?>        
											}    
									}, 100);    


																		var verifyCallback_woo_login = function(response) {

																			if(response.length!==0){ 
																				<?php if ('yes'==trim($disable_submit_btn)) : ?>
																					jQuery('button[name$="login"]').removeAttr("title");
																					jQuery('button[name$="login"]').attr("disabled", false);
																				<?php endif; ?>    


																					if (typeof woo_login_captcha_verified === "function") { 

																						 woo_login_captcha_verified(response);
																					 }

																				}

																		};  
																		
																		
																	  
								</script>
						
								
			<?php
		
		}
	}

	public function i13woo_extra_lostpassword_fields() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_woo_lostpassword');
				$i13_recapcha_hide_label_lostpassword=get_option('i13_recapcha_hide_label_lostpassword');
		$captcha_lable = get_option('i13_recapcha_lostpassword_title');
				$captcha_lable_ = $captcha_lable;
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_lostpassword_theme');
		$size = get_option('i13_recapcha_lostpassword_size');
		$is_enabled = get_option('i13_recapcha_enable_on_lostpassword');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');

				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {

			$captcha_lable_='recaptcha';
		}
				$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);

		if ('yes' == $is_enabled) {

			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

					$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
												wp_dequeue_script( $handle );
												//wp_deregister_script( $handle );
												break;
						}
					}
				}
			}
						wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
			<p class="woo-lost-password-captcha woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_lostpassword) :
					?>
 <label for="lostpassword_captcha"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;<span class="required">*</span></label><?php endif; ?>
			<div name="g-recaptcha-lostpassword-i13" class="g-recaptcha" data-callback="verifyCallback_woo_lostpassword"  data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>


			</p>
						
						

							<script type="text/javascript">

								var myCaptcha = null;    
								<?php $intval_signup= uniqid('interval_'); ?>

								var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

								if(document.readyState === 'complete') {

										clearInterval(<?php echo esc_html($intval_signup); ?>);
																				<?php if ('yes'==trim($disable_submit_btn)) : ?>
											jQuery('.woocommerce-Button').attr("disabled", true);
																						<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
																								jQuery('.woocommerce-Button').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
																						<?php else : ?>
																								jQuery('.woocommerce-Button').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
																						<?php endif; ?>    
										
																					<?php endif; ?> 
										}    
								}, 100);    


																	var verifyCallback_woo_lostpassword = function(response) {
																	
																		if(response.length!==0){
																			
																				<?php if ('yes'==trim($disable_submit_btn)) : ?>
																					jQuery('.woocommerce-Button').removeAttr("title");
																					jQuery('.woocommerce-Button').attr("disabled", false);
																				<?php endif; ?>  
																					
																				   if (typeof woo_lostpassword_captcha_verified === "function") { 

																						 woo_lostpassword_captcha_verified(response);
																					 }    
																				
																		  }

																	};  
																	
																   
																   
							</script>
							 
			<?php
		
		}
	}

	public function i13woo_extra_wp_login_form() {
				
				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_wp_login');
								$i13_recapcha_hide_label_wplogin=get_option('i13_recapcha_hide_label_wplogin');
				$captcha_lable = get_option('i13_recapcha_wplogin_title');
				$captcha_lable_ = $captcha_lable;
				
				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {

			$captcha_lable_='recaptcha';
		}
				$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);

		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_wplogin_theme');
		$size = get_option('i13_recapcha_wplogin_size');
		$is_enabled = get_option('i13_recapcha_enable_on_wplogin');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');
		if ('yes' == $is_enabled) {

			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

					$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
									wp_dequeue_script( $handle );
									//wp_deregister_script( $handle );
									break;
						}
					}
				}
			}
						wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
						<input type="hidden" autocomplete="off" name="wp-login-nonce" value="<?php echo esc_html(wp_create_nonce('wp-login-nonce')); ?>" />
					<p class="i13_woo_wp_login_captcha">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_wplogin) :
					?>
 <label for="g-recaptcha-wp-login-i13"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;</label><?php endif; ?>
			<div name="g-recaptcha-wp-login-i13" class="g-recaptcha" data-callback="verifyCallback_wp_login"  data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
			<br/>


			</p>
						
						

								<script type="text/javascript">

										<?php $intval_signup= uniqid('interval_'); ?>

										var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

										if(document.readyState === 'complete') {

														clearInterval(<?php echo esc_html($intval_signup); ?>);

															 <?php if ('yes'==trim($disable_submit_btn)) : ?>
																	jQuery('#wp-submit').attr("disabled", true);
																	<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
																		   jQuery('#wp-submit').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
																   <?php else : ?>
																		   jQuery('#wp-submit').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
																   <?php endif; ?>    
															 <?php endif; ?>        


														}    
										}, 100);    


										var verifyCallback_wp_login = function(response) {

											if(response.length!==0){


												<?php if ('yes'==trim($disable_submit_btn)) : ?>
													jQuery('#wp-submit').removeAttr("title");
													jQuery('#wp-submit').attr("disabled", false);
												<?php endif; ?>    


												if (typeof woo_wp_login_captcha_verified === "function") { 

													woo_wp_login_captcha_verified(response);
												}  
											}

										};  
										
									  

								</script>
						
			<?php
			
		}
	}

	public function i13woo_extra_wp_lostpassword_form() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_wp_lost_password');
				$i13_recapcha_hide_label_wplostpassword=get_option('i13_recapcha_hide_label_wplostpassword');
		$captcha_lable = get_option('i13_recapcha_wplostpassword_title');
				$captcha_lable_ = $captcha_lable;
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_wplostpassword_theme');
		$size = get_option('i13_recapcha_wplostpassword_size');
		$is_enabled = get_option('i13_recapcha_enable_on_wplostpassword');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');

				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {

			$captcha_lable_='recaptcha';
		}
				$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);

				
		if ('yes' == $is_enabled) {

			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

				$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
							wp_dequeue_script( $handle );
							//wp_deregister_script( $handle );
							break;
						}
					}
				}
			}
			wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
			<input type="hidden" autocomplete="off" name="wp-lostpassword-nonce" value="<?php echo esc_html(wp_create_nonce('wp-lostpassword-nonce')); ?>" />
						<p class="i13_woo_wp_forgopt_password_captcha" >
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_wplostpassword) :
					?>
 <label for="g-recaptcha-wp-lostpassword-i13"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;</label><?php endif; ?>
			<div name="g-recaptcha-wp-lostpassword-i13" data-callback="verifyCallback_woo_lost_password"  class="g-recaptcha" data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
			<br/>

			</p>
						

								<script type="text/javascript">

										<?php $intval_signup= uniqid('interval_'); ?>

										var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

										if(document.readyState === 'complete') {

														clearInterval(<?php echo esc_html($intval_signup); ?>);
														 <?php if ('yes'==trim($disable_submit_btn)) : ?>
																jQuery('#wp-submit').attr("disabled", true);
																<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
																	jQuery('#wp-submit').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
																 <?php else : ?>
																	jQuery('#wp-submit').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
																<?php endif; ?>    
														<?php endif; ?>


														}    
										}, 100);    


										var verifyCallback_woo_lost_password = function(response) {

												if(response.length!==0){

												   <?php if ('yes'==trim($disable_submit_btn)) : ?>
													   jQuery('#wp-submit').removeAttr("title");
													   jQuery('#wp-submit').attr("disabled", false);
												   <?php endif; ?>   

												   if (typeof woo_wp_lost_password_captcha_verified === "function") { 

													   woo_wp_lost_password_captcha_verified(response);
												   }  
											   }


										};  
										
									  
								</script>
						

			<?php
			
		}
	}

	public function i13woo_extra_wp_register_form() {

				$disable_submit_btn=get_option('i13_recapcha_disable_submitbtn_wp_register');
				$i13_recapcha_hide_label_wpregister=get_option('i13_recapcha_hide_label_wpregister');
		$captcha_lable = get_option('i13_recapcha_wpregister_title');
				$captcha_lable_ = $captcha_lable;
		$site_key = get_option('wc_settings_tab_recapcha_site_key');
		$theme = get_option('i13_recapcha_wpregister_theme');
		$size = get_option('i13_recapcha_wpregister_size');
		$is_enabled = get_option('i13_recapcha_enable_on_wpregister');
				$i13_recapcha_no_conflict = get_option('i13_recapcha_no_conflict');
				$recapcha_error_msg_captcha_blank = get_option('wc_settings_tab_recapcha_error_msg_captcha_blank');
		if (''==trim($captcha_lable_)) {

			$captcha_lable_='recaptcha';
		}
				$recapcha_error_msg_captcha_blank = str_replace('[recaptcha]', ucfirst($captcha_lable_), $recapcha_error_msg_captcha_blank);

				
		if ('yes' == $is_enabled) {

			if ('yes'== $i13_recapcha_no_conflict) {
						   
				global $wp_scripts;

				$urls = array( 'google.com/recaptcha', 'gstatic.com/recaptcha' );

				foreach ( $wp_scripts->queue as $handle ) {

					foreach ( $urls as $url ) {
						if ( false !== strpos( $wp_scripts->registered[ $handle ]->src, $url ) ) {
							wp_dequeue_script( $handle );
							//wp_deregister_script( $handle );
							break;
						}
					}
				}
			}
						wp_enqueue_script('jquery');
			wp_enqueue_script('i13-woo-captcha');
			?>
			<input type="hidden" autocomplete="off" name="wp-register-nonce" value="<?php echo esc_html(wp_create_nonce('wp-register-nonce')); ?>" />
						<p class="wp_register_captcha">
				<?php 
				if ('yes'!=$i13_recapcha_hide_label_wpregister) :
					?>
 <label for="g-recaptcha-wp-register-i13"><?php echo esc_html(( ''==trim($captcha_lable) )? __('Captcha', 'recaptcha-for-woocommerce') :esc_html($captcha_lable)); ?>&nbsp;</label><?php endif; ?>
			<div name="g-recaptcha-wp-register-i13" class="g-recaptcha" data-callback="verifyCallback_wp_register"  data-sitekey="<?php echo esc_html($site_key); ?>" data-theme="<?php echo esc_html($theme); ?>" data-size="<?php echo esc_html($size); ?>"></div>
						<br/>


			</p>
			
							<script type="text/javascript">

									var myCaptcha = null;    
									<?php $intval_signup= uniqid('interval_'); ?>

									var <?php echo esc_html($intval_signup); ?> = setInterval(function() {

									if(document.readyState === 'complete') {

													clearInterval(<?php echo esc_html($intval_signup); ?>);
														<?php if ('yes'==trim($disable_submit_btn)) : ?>

																jQuery('#wp-submit').attr("disabled", true);
																<?php if (''==$recapcha_error_msg_captcha_blank) : ?>
																	  jQuery('#wp-submit').attr("title", "<?php echo esc_html(__('Recaptcha is a required field.', 'recaptcha-for-woocommerce')); ?>");
																<?php else : ?>
																		jQuery('#wp-submit').attr("title", "<?php echo esc_html($recapcha_error_msg_captcha_blank); ?>");
																<?php endif; ?>    
													<?php endif; ?>


													}    
									}, 100);    


									var verifyCallback_wp_register = function(response) {

										 if(response.length!==0){

												 <?php if ('yes'==trim($disable_submit_btn)) : ?> 
													jQuery('#wp-submit').removeAttr("title");
													jQuery('#wp-submit').attr("disabled", false);
												<?php endif; ?> 

												if (typeof woo_wp_register_captcha_verified === "function") { 

													woo_wp_register_captcha_verified(response);
												}  
											}


									};  
									
								  

							</script>
					
			<?php
			
		}
	}

}

if (!defined('ABSPATH')) {
	exit;
}
if (function_exists('is_multisite') && is_multisite()) {
	
	
	if (array_key_exists('woocommerce/woocommerce.php', apply_filters('active_plugins', get_site_option('active_sitewide_plugins')))) {

		global $i13_woo_recpatcha;
		$i13_woo_recpatcha = new I13_Woo_Recpatcha();
	} else {
			
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

			global $i13_woo_recpatcha;
			$i13_woo_recpatcha = new I13_Woo_Recpatcha();
		}
	}

} else {
	
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

		global $i13_woo_recpatcha;
		$i13_woo_recpatcha = new I13_Woo_Recpatcha();
	}
}
