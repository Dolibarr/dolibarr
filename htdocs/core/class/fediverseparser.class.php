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
 *      \file       htdocs/core/class/fediverseparser.class.php
 *      \ingroup    social
 *      \brief      Class to parse Fediverse (Mastodon, etc.) posts
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

/**
 * 	Class to parse Fediverse files
 */
class FediverseParser
{
	public $db;
	public $error = '';
	private $posts = array();
	private $lastFetchDate;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Parse Fediverse API to retrieve posts.
	 *
	 * @param string    $urlAPI     URL of the Fediverse API.
	 * @param int       $maxNb      Maximum number of posts to retrieve (default is 5).
	 * @param int       $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string    $cacheDir   Directory to store cached data.
	 * @param string    $platform   social network (Mastodan, Twiter,...)
	 * @return int      Status code: <0 if error, >0 if success.
	 */
	public function parser($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $platform = 'default')
	{
		if (!dol_is_url($urlAPI)) {
			$this->error = "Invalid URL";
			return -1;
		}

		$nowgmt = dol_now();
		$cacheFile = $cacheDir.'/'.dol_hash($urlAPI, 3);
		$foundInCache = false;

		// Check cache
		if ($cacheDelay > 0 && $cacheDir && dol_is_file($cacheFile)) {
			$fileDate = dol_filemtime($cacheFile);
			if ($fileDate >= ($nowgmt - $cacheDelay)) {
				$foundInCache = true;
				$this->lastFetchDate = $fileDate;
			}
		}

		// Load data
		if ($foundInCache) {
			$data = file_get_contents($cacheFile);
		} else {
			$data = $this->fetchFromAPI($urlAPI);
			if ($data && $cacheDir) {
				dol_mkdir($cacheDir);
				file_put_contents($cacheFile, $data);
				$this->lastFetchDate = $nowgmt;
			}
		}

		if ($data === false) {
			$this->error = 'Failed to retrieve data';
			return -1;
		}

		$this->posts = $this->processData($data, $maxNb, $platform);
		return 1;
	}

	/**
	 * Fetch data from the Fediverse API.
	 *
	 * @param string $urlAPI URL of the Fediverse API.
	 * @return string|false JSON data if OK,false if KO
	 */
	private function fetchFromAPI($urlAPI)
	{
		$result = getURLContent($urlAPI, 'GET', '', 1, array(), array('http', 'https'), 0);

		if (!empty($result['content'])) {
			return $result['content'];
		} elseif (!empty($result['error_msg'])) {
			$this->error = 'Error retrieving URL '.$urlAPI.' - '.$result['curl_error_msg'];
			return false;
		}

		return false;
	}

	/**
	 * Process the JSON data retrieved from the API.
	 *
	 * @param string $data JSON data.
	 * @param int    $maxNb Maximum number of posts to process.
	 * @param string    $platform   social network (Mastodan, Twiter,...)
	 * @return array Processed posts.
	 */
	private function processData($data, $maxNb, $platform)
	{
		$jsonData = json_decode($data, true);
		if (!is_array($jsonData)) {
			$this->error = 'Invalid JSON format';
			return array();
		}
		$posts = array();
		$count = 0;
		foreach ($jsonData as $post) {
			if ($count >= $maxNb) {
				break;
			}
			if (!is_numeric($post['account']['created_at'])) {
				$timestamp = strtotime($post['account']['created_at']);
				if ($timestamp > 0) {
					$date = $timestamp;
				}
			}
			if (is_numeric($date)) {
				$date = dol_print_date($date, "dayhour", 'tzuserrel');
			}

			$posts[] = $this->normalizeData($post, $platform);

			$count++;
		}
		return $posts;
	}

	/**
	 * Normalize data of retrieved posts
	 *
	 * @param  string   $postData   post retrieved
	 * @param string    $platform   social network (Mastodan, Twiter,...)
	 * @return array    return array if OK , empty if KO
	 */
	private function normalizeData($postData, $platform = 'default')
	{
		$normalizedData = array();

		$content = strip_tags($postData['content']);
		if ($platform == 'mastodon') {
			$normalizedData['id'] = $postData['id'] ?? '';
			$normalizedData['content'] = $content ?? '';
			$normalizedData['created_at'] = $this->formatDate($postData['account']['created_at']) ?? '';
			$normalizedData['url'] = $postData['url'] ?? '';
			$normalizedData['media_url'] = $postData['media_attachments'][0]['url'] ?? '';
		} elseif ($platform == 'twitter') {
			$normalizedData['id'] = $postData['id_str'] ?? '';
			$normalizedData['content'] = $content ?? '';
			$normalizedData['created_at'] = $this->formatDate($postData['created_at']) ?? '';
			$normalizedData['url'] = 'https://twitter.com/'.$postData['user']['screen_name'].'/status/'.$postData['id_str'];
			$normalizedData['media_url'] = $postData['entities']['media'][0]['media_url_https'] ?? '';
		} else {
			// Default format
			$normalizedData['id'] = $postData['id'] ?? '';
			$normalizedData['content'] = $content ?? $postData['text'] ?? 'No Content Available';
			$normalizedData['created_at'] = $this->formatDate($postData['created_at'] ?? $postData['date']) ?? 'Unknown Date';
			$normalizedData['url'] = $postData['url'] ?? 'No URL Available';
			$normalizedData['media_url'] = $postData['media_url'] ?? $postData['media'] ?? 'No Media Available';
		}

		return $normalizedData;
	}

	/**
	 * Format date for normelize date fediverse
	 * @param   string    $dateString   date with string format
	 * @return  string    return correct format
	 */
	private function formatDate($dateString)
	{
		if (is_numeric($dateString)) {
			$timestamp = (int) $dateString;
		} else {
			$timestamp = strtotime($dateString);
		}

		if ($timestamp > 0) {
			return dol_print_date($timestamp, "dayhour", 'tzuserrel');
		}
		return 'Invalid Date';
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

	/**
	 * Get the last fetch date.
	 *
	 * @return int Timestamp of the last successful fetch.
	 */
	public function getLastFetchDate()
	{
		return $this->lastFetchDate;
	}
}
