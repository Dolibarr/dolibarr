<?php
/* Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/class/reddithandler.class.php
 *      \ingroup    social
 *      \brief      Class to manage each socialNetwork (Reddit, etc.)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/socialnetworkmanager.class.php';

/**
 * Class for handler Reddit
 */
class RedditHandler
{
	/**
	 * @var string $clientId Client ID for the Reddit application.
	 */
	private $clientId;

	/**
	 * @var string $clientSecret Client Secret for the Reddit application.
	 */
	private $clientSecret;

	/**
	 * @var string $username Reddit username for authentication.
	 */
	private $username;

	/**
	 * @var string $password Reddit password for authentication.
	 */
	private $password;

	/**
	 * @var string $accessToken The access token retrieved from Reddit.
	 */
	private $accessToken;

	/**
	 * @var string $userAgent The user agent to use for Reddit API requests.
	 */
	private $userAgent;

	/**
	 * @var  string  $authUrl  The url for authenticate with Reddit.
	 */
	private $authUrl = 'https://www.reddit.com/api/v1/access_token';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array<array{id:string,content:string,created_at:string,url:string,author_name:string,author_avatar?:string}|array{}>		Posts of the social network
	 */
	private $posts;

	/**
	 * Constructor to initialize RedditHandler.
	 *
	 * @param array{client_id?:string,client_secret?:string,username?:string,password?:string,name_app?:string} $authParams Array containing 'client_id', 'client_secret', 'username', and 'password'.
	 */
	public function __construct(array $authParams)
	{
		$this->clientId = $authParams['client_id'] ?? '';
		$this->clientSecret = $authParams['client_secret'] ?? '';
		$this->username = $authParams['username'] ?? '';
		$this->password = $authParams['password'] ?? '';
		$this->userAgent = ($authParams['name_app'] ?? '').'/0.1 by '.($authParams['username'] ?? '');
	}

	/**
	 * Authenticate with Reddit to get an access token.
	 *
	 * @return bool True if authentication was successful, false otherwise.
	 */
	private function authenticate()
	{

		$authData = [
			'grant_type' => 'password',
			'username' => $this->username,
			'password' => $this->password,
			'scope' => 'read identity'
		];

		$headers = [
			'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: ' . $this->userAgent
		];

		$result = getURLContent($this->getAuthUrl(), 'POST', http_build_query($authData), 1, $headers, ['http', 'https'], 0);

		if (!empty($result['content'])) {
			$data = json_decode($result['content'], true);
			if (isset($data['access_token'])) {
				$this->accessToken = $data['access_token'];
				return true;
			} else {
				$this->error = $data['error'] ?? 'Unknown error during authentication';
				return false;
			}
		} else {
			$this->error = 'Authentication failed. No content received.';
			return false;
		}
	}

	/**
	 * Fetch Reddit API to retrieve posts.
	 *
	 * @param string $urlAPI URL of the Reddit API to retrieve posts.
	 * @param int $maxNb Maximum number of posts to retrieve (default is 5).
	 * @param int $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string $cacheDir Directory to store cached data.
	 * @param array{client_id?:string,client_secret?:string,username?:string,password?:string,name_app?:string} $authParams Authentication parameters (not used in this context).
	 * @return array<array{id:string,content:string,created_at:string,url:string,author_name?:string,author_avatar?:string,media_url?:string}|array{}>|false Array of posts if successful, false otherwise.
	 */
	public function fetch($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $authParams = [])
	{
		if (empty($this->accessToken) && !$this->authenticate()) {
			return false;
		}

		$cacheFile = $cacheDir . '/' . dol_hash($urlAPI, '3');
		$foundInCache = false;
		$data = null;

		// Check cache
		if ($cacheDelay > 0 && $cacheDir && dol_is_file($cacheFile)) {
			$fileDate = dol_filemtime($cacheFile);
			if ($fileDate >= (dol_now() - $cacheDelay)) {
				$foundInCache = true;
				$data = file_get_contents($cacheFile);
			}
		}

		if (!$foundInCache) {
			$headers = [
				'Authorization: Bearer ' . $this->accessToken,
				'User-Agent: ' . $this->userAgent,
			];

			$result = getURLContent($urlAPI, 'GET', '', 1, $headers, ['http', 'https'], 0);

			if (!empty($result['content'])) {
				$data = $result['content'];

				if ($cacheDir) {
					dol_mkdir($cacheDir);
					file_put_contents($cacheFile, $data);
				}
			} else {
				$this->error = 'Error retrieving URL ' . $urlAPI;
				return false;
			}
		}
		if (!is_null($data)) {
			$data = json_decode($data, true);

			if (is_array($data)) {
				$this->posts = [];
				$count = 0;

				foreach ($data['data']['children'] as $postData) {
					if ($count >= $maxNb) {
						break;
					}
					$this->posts[$count] = $this->normalizeData($postData['data']);
					$count++;
				}

				return $this->posts;
			} else {
				$this->error = 'Invalid data format or empty response';
				return false;
			}
		} else {
			$this->error = 'Failed to retrieve or decode data';
			return false;
		}
	}

	/**
	 * Normalize the data fetched from the Reddit API.
	 *
	 * @param array{id?:string,title?:string,created?:string,permalink?:string,thumbnail?:string} $postData Data of a single post.
	 * @return array{}|array{id:string,content:string,created_at:string,url:string,media_url:string} Normalized post data.
	 */
	public function normalizeData($postData)
	{
		if (!is_array($postData)) {
			return [];
		}

		return [
			'id' => $postData['id'] ?? '',
			'content' => $postData['title'] ?? '',
			'created_at' => $this->formatDate($postData['created'] ?? ''),
			'url' => 'https://www.reddit.com' . ($postData['permalink'] ?? ''),
			'media_url' => $postData['thumbnail'] ?? '',
		];
	}

	/**
	 * Format date for normalize date.
	 * @param string|int $dateString Date in string format or timestamp.
	 * @return string Formatted date.
	 */
	private function formatDate($dateString)
	{
		$timestamp = is_numeric($dateString) ? (int) $dateString : strtotime($dateString);
		return $timestamp > 0 ? dol_print_date($timestamp, "dayhour", 'tzuserrel') : 'Invalid Date';
	}

	/**
	 * Get the list of retrieved posts.
	 *
	 * @return array<array{id:string,content:string,created_at:string,url:string,author_name:string,author_avatar?:string}|array{}>		Posts fetched from the API
	 */
	public function getPosts()
	{
		return $this->posts;
	}

	/** Get url for authenticate with Reddit
	 *
	 * @return  string  Url of Reddit to get access token
	*/
	public function getAuthUrl()
	{
		return $this->authUrl;
	}
}
