<?php
/*
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/includes/OAuth/Common/Storage/DoliStorage.php
 *      \ingroup    oauth
 *      \brief      Dolibarr token storage class
 */

namespace OAuth\Common\Storage;

use OAuth\Common\Token\TokenInterface;
use OAuth\Common\Storage\Exception\TokenNotFoundException;
use OAuth\Common\Storage\Exception\AuthorizationStateNotFoundException;
use DoliDB;



/**
 * Class to manage storage of OAUTH2 in Dolibarr
 */
class DoliStorage implements TokenStorageInterface
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * @var object|TokenInterface
	 */
	protected $tokens;

	/**
	 * @var string Error code (or message)
	 */
	public $error;
	/**
	 * @var string[] Several error codes (or messages)
	 */
	public $errors = array();

	private $key;
	//private $stateKey;
	private $keyforprovider;
	public $token;
	private $tenant;

	public $state;
	public $date_creation;
	public $date_modification;

	public $userid;		// ID of user for user specific OAuth entries


	/**
	 * @param 	DoliDB 	$db					Database handler
	 * @param 	\Conf 	$notused			Conf object (not used as parameter, used with global $conf)
	 * @param	string	$keyforprovider		Key to manage several providers of the same type. For example 'abc' will be added to 'Google' to defined storage key.
	 * @param	string	$tenant				Value of tenant if used
	 */
	public function __construct(DoliDB $db, \Conf $notused, $keyforprovider = '', $tenant = '')
	{
		$this->db = $db;
		$this->keyforprovider = $keyforprovider;
		$this->token = '';
		$this->tokens = array();
		$this->states = array();
		$this->tenant = $tenant;
		//$this->key = $key;
		//$this->stateKey = $stateKey;
	}

	/**
	 * {@inheritDoc}
	 */
	public function retrieveAccessToken($service)
	{
		dol_syslog("retrieveAccessToken service=".$service);

		if ($this->hasAccessToken($service)) {
			return $this->tokens[$service];
		}

		throw new TokenNotFoundException('Token not found in db, are you sure you stored it?');
	}

	/**
	 * {@inheritDoc}
	 */
	public function storeAccessToken($service, TokenInterface $tokenobj)
	{
		global $conf;

		//var_dump("storeAccessToken");
		//var_dump($token);
		dol_syslog(__METHOD__." storeAccessToken service=".$service);

		$servicepluskeyforprovider = $service;
		if (!empty($this->keyforprovider)) {
			// We clean the keyforprovider after the - to be sure it is not present
			$servicepluskeyforprovider = preg_replace('/\-'.preg_quote($this->keyforprovider, '/').'$/', '', $servicepluskeyforprovider);
			// Now we add the keyforprovider
			$servicepluskeyforprovider .= '-'.$this->keyforprovider;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		$serializedToken = serialize($tokenobj);

		if (!is_array($this->tokens)) {
			$this->tokens = array();
		}

		$this->tokens[$service] = $tokenobj;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($servicepluskeyforprovider)."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$obj = $this->db->fetch_array($resql);
		if ($obj) {
			// update
			$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
			$sql .= " SET token = '".$this->db->escape(dolEncrypt($serializedToken))."'";
			$sql .= " WHERE rowid = ".((int) $obj['rowid']);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
			}
		} else {
			// save
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, token, entity, datec)";
			$sql .= " VALUES ('".$this->db->escape($servicepluskeyforprovider)."', '".$this->db->escape(dolEncrypt($serializedToken))."', ".((int) $conf->entity).", ";
			$sql .= "'".$this->db->idate(dol_now())."'";
			$sql .= ")";
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
			}
		}
		//print $sql;

		// allow chaining
		return $this;
	}

	/**
	 * 	Load token and other data from a $service
	 *  Note: Token load are cumulated into array ->tokens when other properties are erased by last loaded token.
	 *
	 *  @return void
	 */
	public function hasAccessToken($service)
	{
		// get from db
		dol_syslog("hasAccessToken service=".$service);

		$servicepluskeyforprovider = $service;
		if (!empty($this->keyforprovider)) {
			// We clean the keyforprovider after the - to be sure it is not present
			$servicepluskeyforprovider = preg_replace('/\-'.preg_quote($this->keyforprovider, '/').'$/', '', $servicepluskeyforprovider);
			// Now we add the keyforprovider
			$servicepluskeyforprovider .= '-'.$this->keyforprovider;
		}

		$sql = "SELECT token, datec, tms, state FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($servicepluskeyforprovider)."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$result = $this->db->fetch_array($resql);
		if ($result) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
			$tokenobj = unserialize(dolDecrypt($result['token']));
			$this->token = dolDecrypt($result['token']);
			$this->date_creation = $this->db->jdate($result['datec']);
			$this->date_modification = $this->db->jdate($result['tms']);
			$this->state = $result['state'];
		} else {
			$tokenobj = '';
			$this->token = '';
			$this->date_creation = null;
			$this->date_modification = null;
			$this->state = '';
		}

		$this->tokens[$service] = $tokenobj;

		return is_array($this->tokens)
		&& isset($this->tokens[$service])
		&& $this->tokens[$service] instanceof TokenInterface;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clearToken($service)
	{
		dol_syslog("clearToken service=".$service);

		// TODO
		// get previously saved tokens
		//$tokens = $this->retrieveAccessToken($service);

		//if (is_array($tokens) && array_key_exists($service, $tokens)) {
		//    unset($tokens[$service]);

		$servicepluskeyforprovider = $service.($this->keyforprovider ? '-'.$this->keyforprovider : '');

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($servicepluskeyforprovider)."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		if (!empty($this->userid)) {
			$sql .= " AND fk_user = ".((int) $this->userid);
		}
		$resql = $this->db->query($sql);
		//}

		// allow chaining
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clearAllTokens()
	{
		// TODO Remove token using a loop on each $service
		/*
		$servicepluskeyforprovider = $service;
		if (!empty($this->keyforprovider)) {
			// We clean the keyforprovider after the - to be sure it is not present
			$servicepluskeyforprovider = preg_replace('/\-'.preg_quote($this->keyforprovider, '/').'$/', '', $servicepluskeyforprovider);
			// Now we add the keyforprovider
			$servicepluskeyforprovider .= '-'.$this->keyforprovider;
		}
		*/

		// allow chaining
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function retrieveAuthorizationState($service)
	{
		if ($this->hasAuthorizationState($service)) {
			return $this->states[$service];
		}

		dol_syslog('State not found in db, are you sure you stored it?', LOG_WARNING);
		throw new AuthorizationStateNotFoundException('State not found in db, are you sure you stored it?');
	}

	/**
	 * {@inheritDoc}
	 */
	public function storeAuthorizationState($service, $state)
	{
		global $conf;

		dol_syslog("storeAuthorizationState service=".$service." state=".$state);

		if (!isset($this->states) || !is_array($this->states)) {
			$this->states = array();
		}

		//$states[$service] = $state;
		$this->states[$service] = $state;

		//$newstate = preg_replace('/\-.*$/', '', $state);
		$newstate = $state;
		$servicepluskeyforprovider = $service.($this->keyforprovider ? '-'.$this->keyforprovider : '');

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($servicepluskeyforprovider)."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$obj = $this->db->fetch_array($resql);
		if ($obj) {
			// update
			$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
			$sql .= " SET state = '".$this->db->escape($newstate)."'";
			$sql .= " WHERE rowid = ".((int) $obj['rowid']);
			$resql = $this->db->query($sql);
		} else {
			// insert (should not happen)
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, state, entity, datec)";
			$sql .= " VALUES ('".$this->db->escape($servicepluskeyforprovider)."', '".$this->db->escape($newstate)."', ".((int) $conf->entity).", ";
			$sql .= "'".$this->db->idate(dol_now())."'";
			$sql .= ")";
			$resql = $this->db->query($sql);
		}

		// allow chaining
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasAuthorizationState($service)
	{
		// get state from db
		dol_syslog("hasAuthorizationState service=".$service);

		$servicepluskeyforprovider = $service.($this->keyforprovider ? '-'.$this->keyforprovider : '');

		$sql = "SELECT state FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($servicepluskeyforprovider)."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";

		$resql = $this->db->query($sql);

		$result = $this->db->fetch_array($resql);

		$states = array();
		$states[$service] = $result['state'];
		$this->states[$service] = $states[$service];

		return is_array($states)
		&& isset($states[$service])
		&& null !== $states[$service];
	}

	/**
	 * {@inheritDoc}
	 */
	public function clearAuthorizationState($service)
	{
		// TODO
		// get previously saved tokens

		if (is_array($this->states) && array_key_exists($service, $this->states)) {
			unset($this->states[$service]);
		}

		// allow chaining
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clearAllAuthorizationStates()
	{
		// TODO

		// allow chaining
		return $this;
	}

	/**
	 * Return the token
	 *
	 * @return string	String for the tenant used to create the token
	 */
	public function getTenant()
	{
		// Set/Reset tenant now so it will be defined for.
		// TODO We must store it into the table llx_oauth_token
		//$this->tenant = getDolGlobalString('OAUTH_MICROSOFT'.($this->keyforprovider ? '-'.$this->keyforprovider : '').'_TENANT');

		return $this->tenant;
	}

	/**
	 * Return the keyforprovider
	 *
	 * @return string	String for the accurate key provider identification
	 */
	public function getKeyForProvider()
	{
		return $this->keyforprovider;
	}
}
