<?php
/* Copyright (C) 2024 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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


/**
 * Class to manage Social network posts
 */
class SocialNetworkManager
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string  social network name
	 */
	private $platform;

	/**
	 * @var MastodonHandler	Instance of class handler
	 */
	private $handler;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int
	 */
	private $lastFetchDate; // @phpstan-ignore-line

	/**
	 *	Constructor
	 *
	 *  @param	string		$platform      name of social network
	 *  @param	array{username?:string,password?:string,name_app?:string,client_id?:string,client_secret?:string,redirect_uri?:string,access_token?:string}	$authParams    other parameters
	 */
	public function __construct($platform, $authParams = [])
	{
		$this->platform = $platform;
		$this->initializeHandler($authParams);
	}

	/**
	 * Initialize the social network needed
	 * @param	array{username?:string,password?:string,name_app?:string,client_id?:string,client_secret?:string,redirect_uri?:string,access_token?:string}	$authParams    other parameters
	 * @return void   new instance if founded
	 */
	private function initializeHandler($authParams)
	{
		$handlerClass = dol_ucfirst($this->platform).'Handler';
		if (class_exists($handlerClass)) {
			$this->handler = new $handlerClass($authParams);
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
	 * @param array{username?:string,password?:string,name_app?:string,client_id?:string,client_secret?:string,redirect_uri?:string,access_token?:string}	$authParams Authentication parameters
	 * @return bool      Status code: false if error,  array if success.
	 */
	public function fetchPosts($urlAPI, $maxNb = 5, $cacheDelay = 60, $cacheDir = '', $authParams = [])
	{
		if (!$this->handler) {
			return false;
		}

		// This fetch URL
		$result = $this->handler->fetch($urlAPI, $maxNb, $cacheDelay, $cacheDir, $authParams);

		if (!empty($this->handler->error)) {
			$this->error = $this->handler->error;
		}

		return $result;
	}

	/**
	 * Get the list of retrieved posts.
	 *
	 * @return array<array{id:string,content:string,created_at:string,url:string,author_name:string,author_avatar?:string}|array{}>		Posts fetched from the API
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
