<?php

header('Content-type: application/json');

require_once(dirname(__FILE__) . '/../../../wp-config.php');

$fullSerialNumber = trim(filter_var($_POST['serial'], FILTER_SANITIZE_STRING));
$serialNumber = substr($fullSerialNumber, 0, -1);
$checkDigit = substr($fullSerialNumber, -1); //not used here

if (is_user_logged_in() &&	($current_user = wp_get_current_user()) && isset($baweicmu_options['codes'][$serialNumber]))
{
	$current_user_name = $current_user->user_login;
	$registered_users  = $baweicmu_options['codes'][$serialNumber]['users'];

	if (in_array($current_user_name, $registered_users))
	{
		foreach ($baweicmu_options['codes'][$serialNumber]['users'] as $key => $user)
		{
			if ($user == $current_user_name)
			{
				unset($baweicmu_options['codes'][$serialNumber]['users'][$key]);

				//$baweicmu_options['codes'][$serialNumber]['leftcount']++;
			}
		}

		/**
		 * Reset array keys
		 */
		$baweicmu_options['codes'][$serialNumber]['users'] = array_merge($baweicmu_options['codes'][$serialNumber]['users'], []);

		if ( !is_multisite() )
		{
			update_option( 'baweicmu_options', $baweicmu_options );
		}
		else
		{
			update_site_option( 'baweicmu_options', $baweicmu_options );
		}
		removeNewDateSerialNumber($current_user->ID, $serialNumber);
		echo json_encode(['success' => true, 'message'=>__('Serial unassigned', 'storefront'), 'downloads' => '']);
		die;
	}

}

echo json_encode(['success' => false, 'message'=>'General error', 'downloads' => '']);
die;