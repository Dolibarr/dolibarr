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

	private $conf;
	private $key;
	private $stateKey;
	private $keyforprovider;


	/**
	 * @param 	DoliDB 	$db					Database handler
	 * @param 	Conf 	$conf				Conf object
	 * @param	string	$keyforprovider		Key to manage several providers of the same type. For example 'abc' will be added to 'Google' to defined storage key.
	 */
	public function __construct(DoliDB $db, $conf, $keyforprovider = '')
	{
		$this->db = $db;
		$this->conf = $conf;
		$this->keyforprovider = $keyforprovider;
		$this->tokens = array();
		$this->states = array();
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
	public function storeAccessToken($service, TokenInterface $token)
	{
		global $conf;

		//var_dump("storeAccessToken");
		//var_dump($token);
		dol_syslog("storeAccessToken service=".$service);

		$serializedToken = serialize($token);
		$this->tokens[$service] = $token;

		if (!is_array($this->tokens)) {
			$this->tokens = array();
		}
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$obj = $this->db->fetch_array($resql);
		if ($obj) {
			// update
			$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
			$sql.= " SET token = '".$this->db->escape($serializedToken)."'";
			$sql.= " WHERE rowid = ".((int) $obj['rowid']);
			$resql = $this->db->query($sql);
		} else {
			// save
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, token, entity)";
			$sql.= " VALUES ('".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."', '".$this->db->escape($serializedToken)."', ".((int) $conf->entity).")";
			$resql = $this->db->query($sql);
		}
		//print $sql;

		// allow chaining
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasAccessToken($service)
	{
		// get from db
		dol_syslog("hasAccessToken service=".$service);

		$sql = "SELECT token FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($service.(empty($this->keyforprovider) ? '' : '-'.$this->keyforprovider))."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$result = $this->db->fetch_array($resql);
		if ($result) {
			$token = unserialize($result['token']);
		} else {
			$token = '';
		}

		$this->tokens[$service] = $token;

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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
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
		// TODO
		$this->conf->remove($this->key);

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

		// TODO save or update

		dol_syslog("storeAuthorizationState service=".$service);

		if (!isset($this->states) || !is_array($this->states)) {
			$this->states = array();
		}

		//$states[$service] = $state;
		$this->states[$service] = $state;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_state";
		$sql .= " WHERE service = '".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."'";
		$sql .= " AND entity IN (".getEntity('oauth_token').")";
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_print_error($this->db);
		}
		$obj = $this->db->fetch_array($resql);
		if ($obj) {
			// update
			$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_state";
			$sql.= " SET state = '".$this->db->escape($state)."'";
			$sql.= " WHERE rowid = ".((int) $obj['rowid']);
			$resql = $this->db->query($sql);
		} else {
			// save
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_state (service, state, entity)";
			$sql.= " VALUES ('".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."', '".$this->db->escape($state)."', ".((int) $conf->entity).")";
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

		$sql = "SELECT state FROM ".MAIN_DB_PREFIX."oauth_state";
		$sql .= " WHERE service = '".$this->db->escape($service.($this->keyforprovider?'-'.$this->keyforprovider:''))."'";
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
		//$states = $this->conf->get($this->stateKey);

		if (is_array($this->states) && array_key_exists($service, $this->states)) {
			unset($this->states[$service]);

			// Replace the stored tokens array
			//$this->conf->set($this->stateKey, $states);
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
		//$this->conf->remove($this->stateKey);

		// allow chaining
		return $this;
	}
}
