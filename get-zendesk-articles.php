<?php

use Zendesk\API\Client as ZendeskAPI;
use Zendesk\Api\Http as Http;

$list = [];

if ( ! empty($_GET['category']))
{
	$requestedCategory = $_GET['category'];

	/**
	 * Authenticate Zendesk
	 */
	require(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'zendesk-api' . DIRECTORY_SEPARATOR . 'Client.php');

	$subdomain = "fuel3d";
	$username  = "Stephen.atkinson@fuel-3d.com";
	$token     = "IkWh75fHV4GEWFtVqMpIlgMBOgxwI0Wzfd3DiAty";

	$client = new ZendeskAPI($subdomain, $username);
	$client->setAuth('token', $token);

	/**
	 * Get all categories
	 */
	$categories = Http::send($client, 'help_center/categories.json');

	if ($categories && ! empty($categories->categories))
	{
		/**
		 * Search for the requested category ID
		 */
		foreach ($categories->categories as $category)
		{
			if ($requestedCategory == $category->name || $requestedCategory == $category->id)
			{
				/**
				 * If found, pull all articles
				 */
				$articles = Http::send($client, "help_center/categories/{$category->id}/articles.json");

				if ($articles && ! empty($articles->articles))
				{
					foreach ($articles->articles as $article)
					{
						$list[] = [
							'id'    => $article->id,
							'title' => $article->title,
						];
					}
				}
			}
		}
	}
}

header('Content-type: application/json');
echo json_encode($list);