<?php

header('Content-type: application/json');

require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (is_user_logged_in() && !empty($_POST['serial']))
{
	$fullSerialNumber = trim(filter_var($_POST['serial'], FILTER_SANITIZE_STRING));
	$serialNumber = substr($fullSerialNumber, 0, -1);
	$checkDigit = substr($fullSerialNumber, -1);

	if ( (is_numeric($fullSerialNumber) && (int)$checkDigit == calculate_check_digit($serialNumber)) && array_key_exists($serialNumber, $baweicmu_options['codes']))
	{
		$current_user = wp_get_current_user();
		if (is_array($baweicmu_options['codes'][$serialNumber]['users'])){
			$serialAssigned = in_array($current_user->user_login, $baweicmu_options['codes'][$serialNumber]['users']);
		}else{
			$serialAssigned = $baweicmu_options['codes'][$serialNumber]['users'] == $current_user->user_login;
		}

		if ($serialAssigned) {
			echo json_encode([
				'success' => false,
				'message' => __('This serial is already assigned to this user','storefront'),
				'downloads' => '',
			]);
		} else {
			//$baweicmu_options['codes'][$serialNumber]['leftcount']--;
			$baweicmu_options['codes'][$serialNumber]['users'][] = $current_user->user_login;

			update_site_option('baweicmu_options', $baweicmu_options);
			saveNewDateSerialNumber($current_user->ID, $serialNumber);
			echo json_encode([
				'success' => true,
				'message' => '',
				'downloads' => \MVC\Models\Portal::instance()->studio_download_link(),
			]);
		}

	} else {

		echo json_encode([
			'success' => false,
			'message' => __('Wrong serial number', 'storefront'),
			'downloads' => '',
		]);
		
	}

} else {

	echo json_encode([
		'success' => false,
		'message' => 'Wrong data provided',
		'downloads' => '',
	]);
	
}

die;