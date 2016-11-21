<?php
set_time_limit(180);

/**
 * Rebuild cache using Box API
 */
function rebuild_media_downloads_cache()
{
	require_once(dirname(__FILE__) . '/BoxAPI.class.php');

	$sections = [];

	$box = new Box_API(
			apply_filters('get_custom_option', '', 'box_client_id'),
			apply_filters('get_custom_option', '', 'box_client_secret'),
		'http://' . DOMAIN_CURRENT_SITE . '/distributor-portal'
	);

	$folders = $box->get_folders(apply_filters('get_custom_option', '', 'box_shared_folder_id'));

	foreach ($folders as $folder)
	{
		$files = $box->get_files($folder['id'], ['name', 'shared_link']);
		$items = [];

		foreach ($files as $file)
		{
			if (empty($file['shared_link']['download_url'])) continue;

			$items[] = [
				'label' => $file['name'],
				'url' =>   $file['shared_link']['download_url'],
			];
		}

		if (count($items))
		{
			$sections[] = [
				'label' => $folder['name'],
				'items' => $items,
			];
		}
	}

	return json_encode([
		'expires'  => time() + (60 * 60),
		'sections' => $sections,
	]);
}

/**
 * Check if cache is valid and not expired
 */
function media_cache_valid($cache)
{
	return (
		is_array($cache) &&
		isset($cache['expires']) &&
		is_numeric($cache['expires']) &&
		$cache['expires'] >= time()
	);
}

header('Content-type: application/json');

require_once(dirname(__FILE__) . '/../../../wp-config.php');

if (is_user_logged_in() && ($current_user = wp_get_current_user()) && (current_user_can('manage_options') || in_array('distributor', $current_user->roles)))
{
	$cache = get_option('f3d_media_downloads');
	$array = @json_decode($cache, true);

	if ( ! $array || ! media_cache_valid($array))
	{
		$cache = rebuild_media_downloads_cache();

		update_option('f3d_media_downloads', $cache);
	}
	$cache = json_decode($cache, true);

	echo json_encode(isset($cache['sections']) ? $cache['sections'] : []);
	exit;
}

echo json_encode([]);