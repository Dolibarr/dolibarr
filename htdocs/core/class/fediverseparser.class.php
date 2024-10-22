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
require_once DOL_DOCUMENT_ROOT.'/core/class/socialnetworkmanager.class.php';
/**
 * 	Class to parse Fediverse files
 */
class FediverseParser
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var String Error code (or message)
	 */
	public $error = '';

	/**
	 * @var Object  Name of object manager
	 */
	private $manager;

	/**
	 *	Constructor
	 *
	 *  @param		string		$platform      name of social network
	 */
	public function __construct($platform)
	{
		$this->manager = new SocialNetworkManager($platform);
		if (!empty($this->manager->error)) {
			$this->error = $this->manager->error;
		}
	}

	/**
	 * Parse Fediverse API to retrieve posts.
	 *
	 * @param string $urlAPI URL of the Fediverse API.
	 * @param int    $maxNb Maximum number of posts to retrieve (default is 5).
	 * @param int    $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string $cacheDir Directory to store cached data.
	 * @return int Status code: <0 if error, >0 if success.
	 */
	public function parser($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '')
	{
		if (!$this->manager) {
			return -1;
		}

		$result = $this->manager->fetchPosts($urlAPI, $maxNb, $cacheDelay, $cacheDir);
		if ($result === false) {
			$this->error = $this->manager->error;
			return -1;
		}
		return 1;
	}


	/**
	 * Get the list of retrieved posts.
	 *
	 * @return array List of posts.
	 */
	public function getPosts()
	{
		return $this->manager ? $this->manager->getPosts() : [];
	}

	/**
	 * Get the last fetch date.
	 *
	 * @return int|String Timestamp of the last successful fetch.
	 */
	public function getLastFetchDate()
	{
		return $this->manager ? $this->manager->getLastFetchDate() : '';
	}
}
