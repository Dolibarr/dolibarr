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
	 * fetch Social Network API to retrieve posts.
	 *
	 * @param string    $urlAPI     URL of the Fediverse API.
	 * @param int       $maxNb      Maximum number of posts to retrieve (default is 5).
	 * @param int       $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string    $cacheDir   Directory to store cached data.
	 * @return bool      Status code: False if error, true if success.
	 */
	public function fetch($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '')
	{
		$result = getURLContent($urlAPI, 'GET', '', 1, array(), array('http', 'https'), 0);

		if (empty($result['content'])) {
			$this->error = 'Error retrieving URL '.$urlAPI;
			return false;
		}

		$data = json_decode($result['content'], true);
		if (!is_array($data)) {
			$this->error = 'Invalid JSON format';
			return false;
		}

		$this->posts = array_slice(array_map([$this, 'normalizeData'], $data), 0, $maxNb);
		return true;
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
}
