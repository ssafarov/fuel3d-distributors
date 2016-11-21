<?php

header('Content-type: application/json');

if ( ! empty($_FILES['file']['tmp_name']))
{
	// $_GET['code'] = 'gVIyzWzsM0QopXPqJaYIcfk4uhPsZev2';

	$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

	/**
	 * File extension validation - only allow CSV files for now
	 */
	if ($ext != 'csv')
	{
		echo json_encode(['success' => false]);
		exit;
	}

	require_once(dirname(__FILE__) . '/../../../wp-config.php');

	if (is_user_logged_in() && ($current_user = wp_get_current_user()) && in_array('distributor', $current_user->roles))
	{
		require_once(dirname(__FILE__) . '/BoxAPI.class.php');

		$box = new Box_API(
				apply_filters('get_custom_option', '', 'box_client_id'),
				apply_filters('get_custom_option', '', 'box_client_secret'),
			'http://' . DOMAIN_CURRENT_SITE . '/distributor-portal'
		);

		$result = $box->put_file(
			$_FILES['file']['tmp_name'],
			date('Ymd_His') . "_{$current_user->ID}_sales.csv",
			apply_filters('get_custom_option', '', 'box_upload_folder_id')
		);

		if (isset($result['total_count']) && is_numeric($result['total_count']) && intval($result['total_count']) > 0)
		{
			echo json_encode(['success' => true]);
			exit;
		}
	}
}

echo json_encode(['success' => false]);