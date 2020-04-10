<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/mailmanspip/class/mailmanspip.class.php
 *	\ingroup    member
 *	\brief      File of class to manage mailman and spip actions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to manage mailman and spip
 */
class MailmanSpip
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
	 * @var string Error code (or message)
	 */
	public $error = '';

    /**
     * @var string[]	Array of error strings
     */
    public $errors = array();

    public $mladded_ok;
    public $mladded_ko;
    public $mlremoved_ok;
    public $mlremoved_ko;


    /**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Function used to check if SPIP is enabled on the system
     *
     * @return boolean
     */
    public function isSpipEnabled()
    {
        if (defined("ADHERENT_USE_SPIP") && (ADHERENT_USE_SPIP == 1))
        {
            return true;
        }

        return false;
    }

    /**
     * Function used to check if the SPIP config is correct
     *
     * @return boolean
     */
    public function checkSpipConfig()
    {
        if (defined('ADHERENT_SPIP_SERVEUR') && defined('ADHERENT_SPIP_USER') && defined('ADHERENT_SPIP_PASS') && defined('ADHERENT_SPIP_DB'))
        {
            if (ADHERENT_SPIP_SERVEUR != '' && ADHERENT_SPIP_USER != '' && ADHERENT_SPIP_PASS != '' && ADHERENT_SPIP_DB != '')
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Function used to connect to SPIP
     *
     * @return boolean|DoliDB		Boolean of DoliDB
     */
    public function connectSpip()
    {
        $resource = getDoliDBInstance('mysql', ADHERENT_SPIP_SERVEUR, ADHERENT_SPIP_USER, ADHERENT_SPIP_PASS, ADHERENT_SPIP_DB, ADHERENT_SPIP_PORT);

        if ($resource->ok)
        {
            return $resource;
        }

        dol_syslog('Error when connecting to SPIP '.ADHERENT_SPIP_SERVEUR.' '.ADHERENT_SPIP_USER.' '.ADHERENT_SPIP_PASS.' '.ADHERENT_SPIP_DB, LOG_ERR);

        return false;
    }

    /**
     * Function used to connect to Mailman
     *
     * @param	Adherent 	$object 	Object with the data
     * @param	string 	$url    	Mailman URL to be called with patterns
     * @param	string	$list		Name of mailing-list
     * @return 	mixed				Boolean or string
     */
    private function callMailman($object, $url, $list)
    {
        global $conf;

        //Patterns that are going to be replaced with their original value
        $patterns = array(
            '%LISTE%',
            '%EMAIL%',
            '%PASSWORD%',
            '%MAILMAN_ADMINPW%'
        );
        $replace = array(
            $list,
            $object->email,
            $object->pass,
            $conf->global->ADHERENT_MAILMAN_ADMINPW
        );

        $curl_url = str_replace($patterns, $replace, $url);
        dol_syslog('Calling Mailman: '.$curl_url);

        $ch = curl_init($curl_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT) ? 5 : $conf->global->MAIN_USE_CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT) ? 30 : $conf->global->MAIN_USE_RESPONSE_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        dol_syslog('result curl_exec='.$result);

        //An error was found, we store it in $this->error for later
        if ($result === false || curl_errno($ch) > 0)
        {
            $this->error = curl_errno($ch).' '.curl_error($ch);
            dol_syslog('Error using curl '.$this->error, LOG_ERR);
        }

        curl_close($ch);

        return $result;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Fonction qui donne les droits redacteurs dans spip
     *
     *	@param	Adherent	$object		Object with data (->firstname, ->lastname, ->email and ->login)
     *  @return	int					=0 if KO, >0 if OK
     */
    public function add_to_spip($object)
    {
        // phpcs:enable
        dol_syslog(get_class($this)."::add_to_spip");

        if ($this->isSpipEnabled())
        {
            if ($this->checkSpipConfig())
            {
                $mydb = $this->connectSpip();

                if ($mydb)
                {
                    require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
                    $mdpass = dol_hash($object->pass);
                    $htpass = crypt($object->pass, makesalt());
                    $query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"".dolGetFirstLastname($object->firstname, $object->lastname)."\",\"".$object->email."\",\"".$object->login."\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"1comite\")";

                    $result = $mydb->query($query);

                    $mydb->close();

                    if ($result)
                    {
                        return 1;
                    }
                    else $this->error = $mydb->lasterror();
                }
                else $this->error = 'Failed to connect to SPIP';
            }
            else $this->error = 'BadSPIPConfiguration';
        }
        else $this->error = 'SPIPNotEnabled';

        return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Fonction qui enleve les droits redacteurs dans spip
     *
     *	@param	Adherent	$object		Object with data (->login)
     *  @return	int					=0 if KO, >0 if OK
     */
    public function del_to_spip($object)
    {
        // phpcs:enable
        dol_syslog(get_class($this)."::del_to_spip");

        if ($this->isSpipEnabled())
        {
            if ($this->checkSpipConfig())
            {
                $mydb = $this->connectSpip();

                if ($mydb)
                {
                    $query = "DELETE FROM spip_auteurs WHERE login='".$object->login."'";

                    $result = $mydb->query($query);

                    $mydb->close();

                    if ($result)
                    {
                        return 1;
                    }
                    else $this->error = $mydb->lasterror();
                }
                else $this->error = 'Failed to connect to SPIP';
            }
            else $this->error = 'BadSPIPConfiguration';
        }
        else $this->error = 'SPIPNotEnabled';

        return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Fonction qui dit si cet utilisateur est un redacteur existant dans spip
     *
     *	@param	object	$object		Object with data (->login)
     *  @return int     			1=exists, 0=does not exists, -1=error
     */
    public function is_in_spip($object)
    {
        // phpcs:enable
        if ($this->isSpipEnabled())
        {
            if ($this->checkSpipConfig())
            {
                $mydb = $this->connectSpip();

                if ($mydb)
                {
                    $query = "SELECT login FROM spip_auteurs WHERE login='".$object->login."'";

                    $result = $mydb->query($query);

                    if ($result)
                    {
                        if ($mydb->num_rows($result))
                        {
                            // nous avons au moins une reponse
                            $mydb->close($result);
                            return 1;
                        }
                        else
                        {
                            // nous n'avons pas de reponse => n'existe pas
                            $mydb->close($result);
                            return 0;
                        }
                    }
                    else
                    {
                        $this->error = $mydb->lasterror();
                        $mydb->close();
                    }
                }
                else $this->error = 'Failed to connect to SPIP';
            }
            else $this->error = 'BadSPIPConfiguration';
        }
        else $this->error = 'SPIPNotEnabled';

        return -1;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Subscribe an email to all mailing-lists
     *
     *	@param	Adherent	$object		Object with data (->email, ->pass, ->element, ->type)
     *  @param	array	$listes    	To force mailing-list (string separated with ,)
     *  @return	int		  			<0 if KO, >=0 if OK
     */
    public function add_to_mailman($object, $listes = '')
    {
        // phpcs:enable
        global $conf, $langs, $user;

        dol_syslog(get_class($this)."::add_to_mailman");

        $this->mladded_ok = array();
        $this->mladded_ko = array();

        if (!function_exists("curl_init"))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorFunctionNotAvailableInPHP", "curl_init");
            return -1;
        }

        if ($conf->adherent->enabled)	// Synchro for members
        {
	        if (!empty($conf->global->ADHERENT_MAILMAN_URL))
	        {
	            if ($listes == '' && !empty($conf->global->ADHERENT_MAILMAN_LISTS)) $lists = explode(',', $conf->global->ADHERENT_MAILMAN_LISTS);
	            else $lists = explode(',', $listes);

	            $categstatic = new Categorie($this->db);

	            foreach ($lists as $list)
	            {
	                // Filter on type something (ADHERENT_MAILMAN_LISTS = "mailinglist0,TYPE:typevalue:mailinglist1,CATEG:categvalue:mailinglist2")
	                $tmp = explode(':', $list);
	                if (!empty($tmp[2]))
	                {
	                    $list = $tmp[2];
	                    if ($object->element == 'member' && $tmp[0] == 'TYPE' && $object->type != $tmp[1])    // Filter on member type label
	                    {
	                        dol_syslog("We ignore list ".$list." because object member type ".$object->type." does not match ".$tmp[1], LOG_DEBUG);
	                        continue;
	                    }
	                    if ($object->element == 'member' && $tmp[0] == 'CATEG' && !in_array($tmp[1], $categstatic->containing($object->id, 'member', 'label')))    // Filter on member category
	                    {
	                        dol_syslog("We ignore list ".$list." because object member is not into category ".$tmp[1], LOG_DEBUG);
	                        continue;
	                    }
	                }

	                //We call Mailman to subscribe the user
	                $result = $this->callMailman($object, $conf->global->ADHERENT_MAILMAN_URL, $list);

					if ($result === false)
					{
						$this->mladded_ko[$list] = $object->email;
					    return -2;
					}
					else $this->mladded_ok[$list] = $object->email;
	            }
	            return count($lists);
	        }
	        else
	        {
	            $this->error = "ADHERENT_MAILMAN_URL not defined";
	            return -1;
	        }
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Unsubscribe an email from all mailing-lists
     *  Used when a user is resiliated
     *
     *	@param	Adherent	$object		Object with data (->email, ->pass, ->element, ->type)
     *  @param	array	$listes     To force mailing-list (string separated with ,)
     *  @return int         		<0 if KO, >=0 if OK
     */
    public function del_to_mailman($object, $listes = '')
    {
        // phpcs:enable
        global $conf, $langs, $user;

        dol_syslog(get_class($this)."::del_to_mailman");

        $this->mlremoved_ok = array();
        $this->mlremoved_ko = array();

        if (!function_exists("curl_init"))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorFunctionNotAvailableInPHP", "curl_init");
            return -1;
        }

        if ($conf->adherent->enabled)	// Synchro for members
        {
	        if (!empty($conf->global->ADHERENT_MAILMAN_UNSUB_URL))
	        {
	            if ($listes == '' && !empty($conf->global->ADHERENT_MAILMAN_LISTS)) $lists = explode(',', $conf->global->ADHERENT_MAILMAN_LISTS);
	            else $lists = explode(',', $listes);

	            $categstatic = new Categorie($this->db);

	            foreach ($lists as $list)
	            {
	            	// Filter on type something (ADHERENT_MAILMAN_LISTS = "mailinglist0,TYPE:typevalue:mailinglist1,CATEG:categvalue:mailinglist2")
	            	$tmp = explode(':', $list);
	            	if (!empty($tmp[2]))
	            	{
	            		$list = $tmp[2];
	            		if ($object->element == 'member' && $tmp[0] == 'TYPE' && $object->type != $tmp[1])    // Filter on member type label
	            		{
	            			dol_syslog("We ignore list ".$list." because object member type ".$object->type." does not match ".$tmp[1], LOG_DEBUG);
	            			continue;
	            		}
	            		if ($object->element == 'member' && $tmp[0] == 'CATEG' && !in_array($tmp[1], $categstatic->containing($object->id, 'member', 'label')))    // Filter on member category
	            		{
	            			dol_syslog("We ignore list ".$list." because object member is not into category ".$tmp[1], LOG_DEBUG);
	            			continue;
	            		}
	            	}

	                //We call Mailman to unsubscribe the user
	                $result = $this->callMailman($object, $conf->global->ADHERENT_MAILMAN_UNSUB_URL, $list);

					if ($result === false)
					{
						$this->mlremoved_ko[$list] = $object->email;
					    return -2;
					}
					else $this->mlremoved_ok[$list] = $object->email;
	            }
	            return count($lists);
	        }
	        else
			{
	            $this->error = "ADHERENT_MAILMAN_UNSUB_URL not defined";
	            return -1;
	        }
        }
    }
}
