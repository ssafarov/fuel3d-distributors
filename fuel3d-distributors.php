<?php
/**
 * Plugin Name: Fuel3D Distributor Portal
 * Plugin URI: http://www.fuel-3d.com
 * Description: This plugin enabled specific functionality for Fuel3D Distributor Portal
 * Version: 1.0.0
 * Author: Itransition
 * Author URI: http://itransition.by/
 */

if ( ! defined( 'ABSPATH' ) )
{
	exit; // Exit if accessed directly
}

/**
 * Send email using a template
 *
 * @param string $to
 * @param string $subject
 * @param string $template
 * @param array $data
 *
 * @return void
 */
function f3d_send_email($to, $subject, $template, array $data)
{
	$template = dirname(__FILE__) . "/emails/{$template}.php";

	if (file_exists($template))
	{
		ob_start();

		foreach ($data as $key => $value)
		{
			$$key = $value;
		}

		include($template);

		$message = ob_get_contents();
		ob_end_clean();

		wp_mail($to, $subject, $message);
	}
}

/**
 * Apply to be a distributor AJAX call handler
 *
 * @return void
 */
function apply_to_be_a_distributor()
{
	$first_name = $_POST['first_name'];
	$last_name  = $_POST['last_name'];
	$email      = $_POST['email'];
	$password   = $_POST['password'];
	$country    = $_POST['country'];
	$why        = $_POST['why'];
	$errors     = [];

	if ( ! $first_name)
		$errors['first_name'] = 'Please enter your first name';

	if ( ! $last_name)
		$errors['last_name'] = 'Please enter your surname';

	if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors['email'] = 'Please enter a valid email address';
    // Validate email not duplicate
  } else if (email_exists($email)) {
    $errors['email'] = 'This email has been already used for registration.';
  }


	if (strlen($password) < 6)
		$errors['password'] = 'Please enter a password containing at least 6 characters';

	if ( ! $why)
		$errors['why'] = 'Please briefly explain why you want to be a distributor';

	$response = [
		'success' => false,
		'errors'  => $errors,
	];

	if ( ! count($errors))
	{
		$user_id = wp_insert_user([
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'user_email'   => $email,
			'user_pass'    => $password,
			'user_login'   => preg_replace('/__/', '_', preg_replace('/[^a-z0-9]/', '_', strtolower($email))),
			'display_name' => "{$first_name} {$last_name}",
			'role'         => 'inactive',
		]);

		if (is_wp_error($user_id))
		{
			$response = [
				'success' => false,
				'error'   => $user_id->get_error_message(),
			];
		}
		else
		{
			$countries = (new WC_Countries())->countries;

			if (isset($countries[$country]))
			{
				update_user_meta($user_id, 'shipping_country', $country);

				$country = $countries[$country];
			}

			$user = new WP_User($user_id);

			// foreach($user->roles as $role)
			// {
				// $user->remove_role($role);
			// }

			$response = [
				'success' => true
			];

			f3d_send_email(
				DISTRIBUTOR_APPLICATIONS,
				'Someone applied to be a distributor!',
				'admin-request-notification',
				compact('first_name', 'last_name', 'email', 'country', 'why', 'user_id')
			);

			f3d_send_email(
				$email,
				'Your application confirmation',
				'user-request-confirmation',
				[]
			);
		}
	}

	die(json_encode($response));
}

add_action('wp_ajax_nopriv_apply_to_be_a_distributor', 'apply_to_be_a_distributor');

/**
 * Filter payment methods for distributors and non-distributors on the Fuel3D website
 *
 * @return void
 */
function f3d_payment_method_filter($available_gateways)
{
	global $wp, $current_user;

	$isDistributor = ($current_user && $current_user->roles && in_array('distributor', $current_user->roles));
	$disallow = $isDistributor
		? ['paypal']
		: ['bacs'];

	foreach($available_gateways as $gateway_id => $gateway)
	{
		if (in_array($gateway_id, $disallow))
		{
			unset($available_gateways[$gateway_id]);
		}
	}

	return $available_gateways;
}

add_filter('woocommerce_available_payment_gateways', 'f3d_payment_method_filter', 21);

function f3d_box_auth_form() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . '_box-auth-form.php');
}

function f3d_box_auth_settings()
{
	add_options_page('BOX Authorization', 'BOX Authorization', 'manage_options', 'f3d-box-auth-settings', 'f3d_box_auth_form');
}
add_action( 'admin_menu', 'f3d_box_auth_settings' );

/**
 * Hack to catch the Box auth code because the TG
 * plugin, for some reason, catches ALL the requests
 * containing the ?code=... query parameter
 */
function f3d_catch_box_auth_code() {
	if (isset($_GET['page']) && $_GET['page'] == 'f3d-box-auth-settings' && ! empty($_GET['code']))
	{
		require_once(dirname(__FILE__) . '/BoxAPI.class.php');

		$box = new Box_API(
				apply_filters('get_custom_option', '', 'box_client_id'),
				apply_filters('get_custom_option', '', 'box_client_secret'),
			''
		);

		wp_redirect(admin_url('/admin.php?page=f3d-box-auth-settings'));
		exit;
	}
}
add_action( 'admin_init', 'f3d_catch_box_auth_code', 9 );