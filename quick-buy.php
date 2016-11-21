<?php

/**
 * Custom Quick Buy functionality
 */

// fixme:
require_once(dirname(__FILE__) . '/../../../wp-config.php');

if ( ! empty($_POST['quick-buy']))
{
	WC()->cart->add_to_cart($_POST['quick-buy']);

	$get_checkout_url = apply_filters('woocommerce_get_checkout_url', WC()->cart->get_checkout_url());

	wp_redirect($get_checkout_url);
}