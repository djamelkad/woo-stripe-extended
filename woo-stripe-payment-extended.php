<?php
/**
 * Plugin Name: Woo Stripe Extended
 * Plugin URI: https://rippleffect.tech
 * Description: Extension for Woo Stripe plugin
 * Version: 1.0.0
 * Author: Djamel Kadi
 * Author URI: https://rippleffect.tech
 * Text Domain: wps-woo-stripe-extended
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 4.0
 * WC tested up to: 5.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'WPS_WOO_STRIPE_EXTENDED_VERSION', '1.0.0' );
define( 'WPS_WOO_STRIPE_EXTENDED_FILE', __FILE__ );
define( 'WPS_WOO_STRIPE_EXTENDED_SLUG', 'wps-woo-stripe-extended' );
define( 'WPS_WOO_STRIPE_EXTENDED_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPS_WOO_STRIPE_EXTENDED_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WPS_WOO_STRIPE_EXTENDED_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require_once WPS_WOO_STRIPE_EXTENDED_DIR . '/functions.php';

class Woo_Stripe_Extended {

	protected static $instance;

	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'maybe_debug' ), 20 );
		add_filter( 'wc_stripe_setting_classes', array( $this, 'filter_wc_stripe_setting_classes' ), 20, 1 );
		add_filter( 'wc_stripe_get_account_id', array( $this, 'filter_wc_stripe_get_account_id' ), 20, 1 );
		add_filter( 'wc_stripe_api_options', array( $this, 'filter_wc_stripe_api_options' ), 20, 1 );
		add_filter( 'wc_stripe_get_secret_key', array( $this, 'filter_wc_stripe_get_secret_key' ), 20, 2 );
		add_filter( 'wc_stripe_get_publishable_key', array( $this, 'filter_wc_stripe_get_publishable_key' ), 20, 2 );
		add_filter( 'wc_stripe_payment_intent_args', array( $this, 'filter_wc_stripe_payment_intent_args' ), 20, 3 );

		add_action( 'wc_stripe_rest_process_checkout', array( $this, 'check_user_stripe_rest_process_checkout' ), 20, 2 );
		add_filter( 'woocommerce_checkout_customer_id', array( $this, 'filter_woocommerce_checkout_customer_id' ), 20, 1 );
	}

	public function check_user_stripe_rest_process_checkout( $request, $gateway = null ) {
		if ( is_user_logged_in() ) {
			return;
		}

		$email = $request->get_param( 'billing_email' );

		if ( email_exists( $email ) || username_exists( $email ) ) {
			// Just assign user id
		} else {
			$password = wp_generate_password();
			$username = $email = $request->get_param( 'billing_email' );
			$result   = wc_create_new_customer( $email, $username, $password );
			if ( $result instanceof WP_Error ) {
				// for email exists errors you want customer to either login or use a different email address.
				// throw new Exception( $result->get_error_message() );
				return;
			}

			// log the customer in
			wp_set_current_user( $result );
			wc_set_customer_auth_cookie( $result );

			// As we are now logged in, cart will need to refresh to receive updated nonces
			WC()->session->set( 'reload_checkout', true );
		}
	}

	public function filter_woocommerce_checkout_customer_id( $user_id = null ) {
		if ( $user_id ) {
			return $user_id;
		}

		$email = isset( $_REQUEST['billing_email'] ) ? $_REQUEST['billing_email'] : null;

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			return $user->ID;
		}

		$user = get_user_by( 'login', $email );
		if ( $user ) {
			return $user->ID;
		}

		return $user_id;
	}

	public function filter_wc_stripe_setting_classes( $setting_classes = null ) {
		require_once WPS_WOO_STRIPE_EXTENDED_DIR . '/class-wc-stripe-connected-settings.php';
		$new_setting_classes = array();
		foreach ( $setting_classes as $id => $class_name ) {
			$new_setting_classes[ $id ] = $class_name;
			if ( 'account_settings' === $id ) {
				$new_setting_classes['stripe_connected'] = 'WC_Stripe_Connected_Settings';
			}
		}

		return $new_setting_classes;
	}

	public function filter_wc_stripe_api_options( $args = null ) {
		$args['stripe_account'] = stripe_wc()->stripe_connected->get_option( 'connected_account_id' );
		return $args;
	}

	public function filter_wc_stripe_get_account_id( $account_id = null ) {
		return stripe_wc()->stripe_connected->get_option( 'connected_account_id' );
	}

	public function filter_wc_stripe_get_secret_key( $secret_key = null, $mode = null ) {
		return stripe_wc()->stripe_connected->get_option( 'main_secret_key' );
	}

	public function filter_wc_stripe_get_publishable_key( $publishable_key = null, $mode = null ) {
		return stripe_wc()->stripe_connected->get_option( 'connected_publishable_key' );
	}

	public function filter_wc_stripe_payment_intent_args( $args, $order = null, $stripe_intent = null ) {
		$args['application_fee_amount'] = intval( floatval( $args['amount'] ) * ( (float) stripe_wc()->stripe_connected->get_option( 'application_fee' ) / 100 ) );
		return $args;
	}

	public function maybe_debug() {
		if ( ! isset( $_GET['_yith_stripe'] ) ) {
			return;
		}

		die();
	}

	public function get_log_dir( string $handle ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/' . $handle . '-logs';
		wp_mkdir_p( $log_dir );
		return $log_dir;
	}

	public function get_log_file_name( string $handle ) {
		if ( function_exists( 'wp_hash' ) ) {
			$date_suffix = date( 'Y-m-d', time() );
			$hash_suffix = wp_hash( $handle );
			return $this->get_log_dir( $handle ) . '/' . sanitize_file_name( implode( '-', array( $handle, $date_suffix, $hash_suffix ) ) . '.log' );
		}

		return $this->get_log_dir( $handle ) . '/' . $handle . '-' . date( 'Y-m-d', time() ) . '.log';
	}

	public function log( $message ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->debug( print_r( $message, true ), array( 'source' => WPS_WOO_STRIPE_EXTENDED_SLUG ) );
		} else {
			error_log( date( '[Y-m-d H:i:s e] ' ) . print_r( $message, true ) . PHP_EOL, 3, $this->get_log_file_name( WPS_WOO_STRIPE_EXTENDED_SLUG ) );
		}
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

function Woo_Stripe_Extended() {
	return Woo_Stripe_Extended::get_instance();
}

$GLOBALS[ WPS_WOO_STRIPE_EXTENDED_SLUG ] = Woo_Stripe_Extended();
