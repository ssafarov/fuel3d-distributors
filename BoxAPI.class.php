<?php
/**
 * BoxPHPAPI v1.0.5
 *
 * @url https://github.com/golchha21/BoxPHPAPI
 *
 * @fixed and improved by Sergei Safarov @Itransition
 */

require_once(dirname(__FILE__) . '/../../../wp-load.php');

class Box_API {

	public $client_id     = '';
	public $client_secret = '';
	public $redirect_uri  = '';
	public $access_token  = '';
	public $refresh_token = '';
	public $authorize_url = 'https://www.box.com/api/oauth2/authorize';
	public $token_url     = 'https://www.box.com/api/oauth2/token';
	public $api_url       = 'https://api.box.com/2.0';
	public $upload_url    = 'https://upload.box.com/api/2.0';

	public function __construct($client_id = '', $client_secret = '', $redirect_uri = '')
	{
		if (empty($client_id) || empty($client_secret))
		{
			throw ('Invalid CLIENT_ID or CLIENT_SECRET or REDIRECT_URL. Please provide CLIENT_ID, CLIENT_SECRET and REDIRECT_URL when creating an instance of the class.');
		}
		else
		{
			$this->client_id 		= $client_id;
			$this->client_secret	= $client_secret;
			$this->redirect_uri		= $redirect_uri;
		}

		if ( ! $this->load_token())
		{
			if (isset($_GET['code']))
			{
				$token = $this->get_token($_GET['code'], true);

				if ($this->write_token($token))
				{
					$this->load_token();
				}
			}
			else
			{
				$this->get_code();
			}
		}
	}

	private function not_authorized()
	{
		$url = $this->authorize_url . '?' . http_build_query([
			'response_type' => 'code',
			'client_id' => $this->client_id,
			'redirect_uri' => $this->redirect_uri
		]);

		echo json_encode([
			'error' => 'BOX not authorized',
			'authorize_url' => $url,
		]);

		exit;
	}

	/* First step for authentication [Gets the code] */
	public function get_code()
	{
		if (array_key_exists('refresh_token', $_REQUEST))
		{
			$this->refresh_token = $_REQUEST['refresh_token'];
		}
		else
		{
			$this->not_authorized();
		}
	}

	/* Second step for authentication [Gets the access_token and the refresh_token] */
	public function get_token($code = '', $json = false)
	{
		$url = $this->token_url;
		if ( ! empty($this->refresh_token))
		{
			$params = [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refresh_token,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret];
		}
		else
		{
			$params = [
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret];
		}

		if ($json)
		{
			return $this->post($url, $params);
		}
		else
		{
			return json_decode($this->post($url, $params), true);
		}
	}

	/* Gets the current user details */
	public function get_user()
	{
		$url = $this->build_url('/users/me');

		return json_decode($this->get($url),true);
	}

	/* Get the details of the mentioned folder */
	public function get_folder_details($folder, $json = false)
	{
		$url = $this->build_url("/folders/$folder");

		if ($json)
		{
			return $this->get($url);
		}
		else
		{
			return json_decode($this->get($url),true);
		}
	}

	/* Get the list of items in the mentioned folder */
	public function get_folder_items($folder, array $fields = [])
	{
		$opts = [
			'limit' => 1000
		];

		if (count($fields))
		{
			$opts['fields'] = implode(',', $fields);
		}

		$url = $this->build_url("/folders/{$folder}/items", $opts);

		return json_decode($this->get($url),true);
	}

	/* Get the list of collaborators in the mentioned folder */
	public function get_folder_collaborators($folder, $json = false)
	{
		$url = $this->build_url("/folders/{$folder}/collaborations");

		return $json
			? $this->get($url)
			: json_decode($this->get($url),true);
	}

	/* Lists the folders in the mentioned folder */
	public function get_folders($folder, array $fields = [])
	{
		$data = $this->get_folder_items($folder, $fields);

		if ( ! is_array($data) || ! isset($data['entries']) || ! is_array($data['entries']))
		{
			$this->not_authorized();
		}

		foreach ($data['entries'] as $item)
		{
			if($item['type'] == 'folder')
			{
				$return[] = $item;
			}
		}

		return $return;
	}

	/* Lists the files in the mentioned folder */
	public function get_files($folder, array $fields = [])
	{
		$return = [];
		$data   = $this->get_folder_items($folder, $fields);

		if ( ! is_array($data) || ! isset($data['entries']) || ! is_array($data['entries']))
		{
			$this->not_authorized();
		}

		foreach($data['entries'] as $item)
		{
			if($item['type'] == 'file')
			{
				$return[] = $item;
			}
		}

		return $return;
	}

	/* Lists the files in the mentioned folder */
	public function get_links($folder, array $fields = [])
	{
		$return = [];
		$data   = $this->get_folder_items($folder, $fields);

		if ( ! is_array($data) || ! isset($data['entries']) || ! is_array($data['entries']))
		{
			$this->not_authorized();
		}

		foreach($data['entries'] as $item)
		{
			if($item['type'] == 'web_link')
			{
				$return[] = $item;
			}

		}

		return $return;
	}

	public function create_folder($name, $parent_id)
	{
		$url    = $this->build_url("/folders");
		$params = [
			'name' => $name,
			'parent' => [
				'id' => $parent_id
			]
		];

		return json_decode($this->post($url, json_encode($params)), true);
	}

	/* Modifies the folder details as per the api */
	public function update_folder($folder, array $params)
	{
		$url = $this->build_url("/folders/$folder");

		return json_decode($this->put($url, $params), true);
	}

	/* Deletes a folder */
	public function delete_folder($folder, array $opts)
	{
		echo $url = $this->build_url("/folders/$folder", $opts);

		$return = json_decode($this->delete($url), true);

		if(empty($return))
		{
			return 'The folder has been deleted.';
		}
		else
		{
			return $return;
		}
	}

	/* Shares a folder */
	public function share_folder($folder, array $params)
	{
		$url = $this->build_url("/folders/$folder");

		return json_decode($this->put($url, $params), true);
	}

	/* Shares a file */
	public function share_file($file, array $params)
	{
		$url = $this->build_url("/files/$file");

		return json_decode($this->put($url, $params), true);
	}

	/* Get the details of the mentioned file */
	public function get_file_details($file, $json = false)
	{
		$url = $this->build_url("/files/$file");

		if($json)
		{
			return $this->get($url);
		}
		else
		{
			return json_decode($this->get($url),true);
		}
	}

	/* Uploads a file */
	public function put_file($filepath, $filename, $parent_id)
	{
		$url    = $this->upload_url . '/files/content';
		$params = [
			'file'         => "@{$filepath}",
			'attributes'   => json_encode([
				'name'     => $filename,
				'parent'   => [
					'id' => $parent_id
				],
			]),
		];

		return json_decode($this->post($url, $params), true);
	}

	/* Modifies the file details as per the api */
	public function update_file($file, array $params)
	{
		$url = $this->build_url("/files/$file");

		return json_decode($this->put($url, $params), true);
	}

	/* Deletes a file */
	public function delete_file($file)
	{
		$url    = $this->build_url("/files/$file");
		$return = json_decode($this->delete($url),true);

		if(empty($return))
		{
			return 'The file has been deleted.';
		}
		else
		{
			return $return;
		}
	}

	/* Saves the token */
	public function write_token($token)
	{
		$array = json_decode($token, true);

		if(isset($array['error']))
		{
			$this->error = $array['error_description'];

			return false;
		}
		else
		{
			$array['timestamp'] = time();
			update_option('f3d_box_token', json_encode($array));

			return true;
		}
	}

	/* Reads the token */
	public function read_token()
	{
		return json_decode(get_option('f3d_box_token'), true);
	}

	/* Loads the token */
	public function load_token()
	{
		$array = $this->read_token();

		if(!$array || count($array) < 2)
		{
			return false;
		}
		else
		{
			if (isset($array['error']))
			{
				$this->error = $array['error_description'];
				return false;
			}
			elseif ($this->expired($array['expires_in'], $array['timestamp']))
			{
				$this->refresh_token = $array['refresh_token'];
				$token = $this->get_token(NULL, true);

				if ($this->write_token($token))
				{
					$array = json_decode($token, true);
					$this->refresh_token = $array['refresh_token'];
					$this->access_token = $array['access_token'];
					return true;
				}
			}
			else
			{
				$this->refresh_token = $array['refresh_token'];
				$this->access_token = $array['access_token'];
				return true;
			}
		}
	}

	/* Builds the URL for the call */
	private function build_url($api_func, array $opts = [])
	{
		$opts         = $this->set_opts($opts);
		$base         = $this->api_url . $api_func;
		$query_string = http_build_query($opts);

		if ($query_string)
		{
			$base = $base . '?' . $query_string;
		}

		return $base;
	}

	/* Sets the required before biulding the query */
	private function set_opts(array $opts)
	{
		// if(!array_key_exists('access_token', $opts))
		// {
			// $opts['access_token'] = $this->access_token;
		// }

		return $opts;
	}

	private function parse_result($res)
	{
		$xml   = simplexml_load_string($res);
		$json  = json_encode($xml);
		$array = json_decode($json,TRUE);

		return $array;
	}

	private static function expired($expires_in, $timestamp)
	{
		$ctimestamp = time();

		return (($ctimestamp - $timestamp) >= $expires_in);
	}

	private function get($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if ($this->access_token) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->access_token]);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	private function post($url, $params)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		if ($this->access_token) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->access_token]);
		}
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}

	private function put($url, array $params = [])
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		if ($this->access_token) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->access_token]);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	private function delete($url, $params = '')
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		if ($this->access_token) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->access_token]);
		}
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}