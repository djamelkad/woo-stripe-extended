<?php

defined( 'ABSPATH' ) || exit();

/**
 * @since 3.3.13
 */
class WC_Stripe_Connected_Settings extends WC_Stripe_Settings_API {

	public function __construct() {
		$this->id        = 'stripe_connected';
		$this->tab_title = __( 'Connected Settings', 'woo-stripe-payment' );
		parent::__construct();
	}

	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wc_stripe_settings_nav_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'woocommerce_stripe_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'                     => array(
				'type'  => 'title',
				'title' => __( 'Connected Settings', 'woo-stripe-payment' ),
			),
			'settings_description'      => array(
				'type'        => 'description',
				'description' => __( 'Manage connected stripe account settings', 'woo-stripe-payment' ),
			),
			'main_publishable_key'      => array(
				'title'       => __( 'Main account publishable key', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter main account stripe publishable key.', 'woo-stripe-payment' ),
			),
			'main_secret_key'           => array(
				'title'       => __( 'Main account stripe secret key', 'woo-stripe-payment' ),
				'type'        => 'password',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter main account stripe secret key.', 'woo-stripe-payment' ),
			),
			'connected_account_id'      => array(
				'title'       => __( 'Connected account id', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter connected account id.', 'woo-stripe-payment' ),
			),
			'connected_publishable_key' => array(
				'title'       => __( 'Connected account publishable key', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter connected account stripe publishable key.', 'woo-stripe-payment' ),
			),
			'connected_secret_key'      => array(
				'title'       => __( 'Connected account stripe secret key', 'woo-stripe-payment' ),
				'type'        => 'password',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter connected account stripe secret key.', 'woo-stripe-payment' ),
			),			
			'application_fee'           => array(
				'title'       => __( 'Application fee for main account (in %)', 'woo-stripe-payment' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter application fee for main account', 'woo-stripe-payment' ),
			),
		);
	}

	public function process_admin_options() {
		parent::process_admin_options();
	}
}
