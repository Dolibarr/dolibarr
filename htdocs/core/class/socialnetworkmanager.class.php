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
 *      \file       htdocs/core/class/socialnetworkmanager.class.php
 *      \ingroup    social
 *      \brief      Class to manage each socialNetwork (Mastodon, etc.)
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/mastodonhandler.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/diasporahandler.class.php';


/**
 * Class to manage Social network posts
 */
class SocialNetworkManager
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string  social network name
	 */
	private $platform;

	/**
	 * @var Object  Name of class handler
	 */
	private $handler;

	/**
	 * @var String Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int
	 */
	private $lastFetchDate;

	/**
	 *	Constructor
	 *
	 *  @param		string		$platform      name of social network
	 */
	public function __construct($platform)
	{
		$this->platform = $platform;
		$this->initializeHandler();
	}

	/**
	 * Initialize the social network needed
	 * @return void   new instance if founded
	 */
	private function initializeHandler()
	{
		$handlerClass = dol_ucfirst($this->platform).'Handler';
		if (class_exists($handlerClass)) {
			$this->handler = new $handlerClass();
		} else {
			$this->error = "Handler for $this->platform not found.";
		}
	}

	/**
	 * Fetch Social Network API to retrieve posts.
	 *
	 * @param string    $urlAPI     URL of the Fediverse API.
	 * @param int       $maxNb      Maximum number of posts to retrieve (default is 5).
	 * @param int       $cacheDelay Number of seconds to use cached data (0 to disable caching).
	 * @param string    $cacheDir   Directory to store cached data.
	 * @param array     $authParams (Optional) Parameters for authentication, if needed.
	 * @return bool      Status code: false if error,  array if success.
	 */
	public function fetchPosts($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $authParams = [])
	{
		if (!$this->handler) {
			return false;
		}
		return $this->handler->fetch($urlAPI, $maxNb, $cacheDelay, $cacheDir, $authParams);
	}

	/**
	 * Get the list of retrieved posts.
	 *
	 * @return array List of posts.
	 */
	public function getPosts()
	{
		return $this->handler ? $this->handler->getPosts() : [];
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
