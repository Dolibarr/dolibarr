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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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

    /**
     * @param Conf   $conf
     * @param string $key
     * @param string $stateKey
     */
    public function __construct(DoliDB $db, $conf)
    {
        $this->db = $db;
        $this->conf = $conf;
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
        //var_dump("storeAccessToken");
        //var_dump($token);
        dol_syslog("storeAccessToken");
        
        $serializedToken = serialize($token);
        $this->tokens[$service] = $token;

        if (!is_array($this->tokens)) {
            $this->tokens = array();
        }
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_token";
        $sql.= " WHERE service='".$this->db->escape($service)."' AND entity=1";
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            dol_print_error($this->db);
        }
        $obj = $this->db->fetch_array($resql);
        if ($obj) {
            // update
            $sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
            $sql.= " SET token='".$this->db->escape($serializedToken)."'";
            $sql.= " WHERE rowid='".$obj['rowid']."'";
            $resql = $this->db->query($sql);
        } else {
            // save
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_token (service, token, entity)";
            $sql.= " VALUES ('".$this->db->escape($service)."', '".$this->db->escape($serializedToken)."', 1)";
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
        $sql.= " WHERE service='".$this->db->escape($service)."'";
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            dol_print_error($this->db);
        }
        $result = $this->db->fetch_array($resql);
        $token = unserialize($result['token']);
        
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
        // TODO
        // get previously saved tokens
        //$tokens = $this->retrieveAccessToken($service);

        //if (is_array($tokens) && array_key_exists($service, $tokens)) {
        //    unset($tokens[$service]);

            $sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
            $sql.= " WHERE service='".$this->db->escape($service)."'";
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

        throw new AuthorizationStateNotFoundException('State not found in db, are you sure you stored it?');
    }

    /**
     * {@inheritDoc}
     */
    public function storeAuthorizationState($service, $state)
    {
        // TODO save or update

        if (!is_array($states)) {
            $states = array();
        }

        $states[$service] = $state;
        $this->states[$service] = $state;

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."oauth_state";
        $sql.= " WHERE service='".$this->db->escape($service)."' AND entity=1";
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            dol_print_error($this->db);
        }
        $obj = $this->db->fetch_array($resql);
        if ($obj) {
            // update
            $sql = "UPDATE ".MAIN_DB_PREFIX."oauth_state";
            $sql.= " SET state='".$this->db->escape($state)."'";
            $sql.= " WHERE rowid='".$obj['rowid']."'";
            $resql = $this->db->query($sql);
        } else {
            // save
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."oauth_state (service, state, entity)";
            $sql.= " VALUES ('".$this->db->escape($service)."', '".$this->db->escape($state)."', 1)";
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
        dol_syslog("get state from db");
        $sql = "SELECT state FROM ".MAIN_DB_PREFIX."oauth_state";
        $sql.= " WHERE service='".$this->db->escape($service)."'";
        $resql = $this->db->query($sql);
        $result = $this->db->fetch_array($resql);
        $states[$service] = $result[state];
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

        if (is_array($states) && array_key_exists($service, $states)) {
            unset($states[$service]);

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
