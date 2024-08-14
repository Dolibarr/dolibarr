<?php
/* Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/class/diasporahandler.class.php
 *      \ingroup    social
 *      \brief      Class to manage  socialNetwork (Diaspora)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/socialnetworkmanager.class.php';
 /**
 * Class for handling Diaspora API interactions
 */
class DiasporaHandler
{
	/**
	 * @var array Posts fetched from the API
	 */
	private $posts = [];

	/**
	 * @var string Error message
	 */
	public $error = '';

	 /**
	 * @var array Authentication parameters, including cookie name and value
	 */
	private $params = [];

	 /**
	 * Check if the provided cookie in params is valid.
	 * @return bool True if a valid cookie is found in params, false otherwise.
	 */
	private function isCookieValid()
	{
		return !empty($this->getCookieFromParams());
	}

	/**
	 * Get the cookie value from params, regardless of the exact key name.
	 * @return string|null The cookie string if found, null otherwise.
	 */
	private function getCookieFromParams()
	{
		foreach ($this->params as $key => $value) {
			if (stripos($key, 'cookie') !== false && !empty($value)) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * Fetch Social Network API to retrieve posts.
	 *
	 * @param string $urlAPI URL of the Diaspora API.
	 * @param int $maxNb Maximum number of posts to retrieve (default is 5).
	 * @param int $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string $cacheDir Directory to store cached data.
	 * @param array $authParams Authentication parameters including login URL, username, and password.
	 * @return bool Status code: False if error, true if success.
	 */
	public function fetch($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $authParams = [])
	{

		// Set authParams to the class attribute
		$this->params = $authParams;

		if (!$this->isCookieValid()) {
			$this->error = 'Invalid or missing authentication parameters';
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
				'Cookie: ' . $this->getCookieFromParams(),
				'Accept: application/json'
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

		$data = json_decode($data, true);
		if (!is_null($data)) {
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
				return true;
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
	 * Normalize data of retrieved posts.
	 *
	 * @param array $postData Data of a single post.
	 * @return array Normalized post data.
	 */
	public function normalizeData($postData)
	{
		if (!is_array($postData)) {
			return [];
		}

		return [
			'id' => $postData['guid'] ?? '',
			'content' => strip_tags($postData['text'] ?? $postData['title'] ?? ''),
			'created_at' => $this->formatDate($postData['created_at'] ?? ''),
			'url' => 'https://diaspora-fr.org/posts/' . ($postData['guid'] ?? ''),            'media_url' => !empty($postData['photos']) && isset($postData['photos'][0]['url']) ? $postData['photos'][0]['url'] : '',
			'author_name' => $postData['author']['name'] ?? '',
			'author_avatar' => $postData['author']['avatar']['small'] ?? ''
		];
	}

	/**
	 * Format date for normalize date.
	 * @param string $dateString Date in string format.
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
	 * @return array List of posts.
	 */
	public function getPosts()
	{
		return $this->posts;
	}
}
