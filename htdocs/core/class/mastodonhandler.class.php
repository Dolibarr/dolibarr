<?php
/* Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024 MDW                 <mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/class/mastodonhandler.class.php
 *      \ingroup    social
 *      \brief      Class to manage social network Mastodon
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/socialnetworkmanager.class.php';


/**
 * Class for handler Mastodon
 */
class MastodonHandler
{
	/**
	 * @var array<array{id:string,content:string,created_at:string,url:string,media_url:string}|array{}>    Posts of social network (Mastodon)
	 */
	private $posts;

	/**
	 * @var String Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string Access token for authenticated requests
	 */
	private $accessToken;

	/**
	 * @var string The client ID for the Mastodon app
	 */
	private $clientId;

	/**
	 * @var string The client secret for the Mastodon app
	 */
	private $clientSecret;

	/**
	 * @var string The redirect URI for the Mastodon app
	 */
	private $redirectUri;


	/**
	 * Constructor to set the necessary credentials.
	 *
	 * @param array   $authParams  parameters for authentication
	 */
	public function __construct($authParams)
	{

		$this->clientId = $authParams['client_id'] ?? '';
		$this->clientSecret = $authParams['client_secret'] ?? '';
		$this->redirectUri = $authParams['redirect_uri'] ?? '';
		$this->accessToken = $authParams['access_token'] ?? '';
	}


	/**
	 * Fetch posts from Mastodon API using the access token.
	 *
	 * @param string $urlAPI The URL of the API endpoint
	 * @param int $maxNb Maximum number of posts to retrieve
	 * @param int $cacheDelay Cache delay in seconds
	 * @param string $cacheDir Directory for caching
	 * @param array $authParams Authentication parameters
	 * @return array|false Array of posts if successful, False otherwise
	 */
	public function fetch($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $authParams = [])
	{
		if (empty($this->accessToken) && isset($authParams['access_token'])) {
			return false;
		}
		$cacheFile = $cacheDir.'/'.dol_hash($urlAPI, 3);
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
				'Content-Type: application/json'
			];

			$result = getURLContent($urlAPI, 'GET', '', 1, $headers, array('http', 'https'), 0);
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

				foreach ($data as $postData) {
					if ($count >= $maxNb) {
						break;
					}
					$this->posts[$count] = $this->normalizeData($postData);
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
	 * Normalize data of retrieved posts
	 *
	 * @param  array{content?:string,created_at?:string,url?:string,media_attachments?:array<array{url:string}>}	$postData   post retrieved
	 * @return array{id:string,content:string,created_at:string,url:string,media_url:string}|array{}    return array with normalized postData
	 */
	public function normalizeData($postData)
	{
		if (!is_array($postData)) {
			return [];
		}
		return [
			'id' => $postData['id'] ?? '',
			'content' => strip_tags($postData['content'] ?? ''),
			'created_at' => $this->formatDate($postData['created_at'] ?? ''),
			'url' => $postData['url'] ?? '',
			'media_url' => $postData['media_attachments'][0]['url'] ?? ''
		];
	}

	/**
	 * Format date for normalize date
	 * @param   string    $dateString   date with string format
	 * @return  string    return correct format
	 */
	private function formatDate($dateString)
	{
		$timestamp = is_numeric($dateString) ? (int) $dateString : strtotime($dateString);
		return $timestamp > 0 ? dol_print_date($timestamp, "dayhour", 'tzuserrel') : 'Invalid Date';
	}

	/**
	 * Get the list of retrieved posts.
	 *
	 * @return array<array{id:string,content:string,created_at:string,url:string,media_url:string}|array{}>    List of posts
	 */
	public function getPosts()
	{
		return $this->posts;
	}

	/**
	 * Getter for url to redirect
	 * @return   string    url
	 */
	public function getRedirectUri()
	{
		return $this->redirectUri;
	}

	/**
	 * Getter for access token
	 * @return string  token
	 */
	public function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * Getter for client Id
	 * @return  string  client Id
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * Getter for secret client
	 * @return string  secret client
	 */
	public function getClientSecret()
	{
		return $this->clientSecret;
	}
}
