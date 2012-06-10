<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/mailmanspip/class/mailmanspip.class.php
 *	\ingroup    member
 *	\brief      File of class to manage members of a foundation
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");


/**
 *	Class to manage mailman and spip
 */
class MailmanSpip
{
    var $db;
    var $error;


    /**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Fonction qui donne les droits redacteurs dans spip
     *
     *	@param	Object	$object		Object with data (->firstname, ->lastname, ->email and ->login)
     *  @return	int					=0 if KO, >0 if OK
     */
    function add_to_spip($object)
    {
        dol_syslog(get_class($this)."::add_to_spip");

        if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
        defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
        defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
        defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
        defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
        )
        {
            require_once(DOL_DOCUMENT_ROOT."/core/lib/security2.lib.php");
            $mdpass=dol_hash($object->pass);
            $htpass=crypt($object->pass,makesalt());
            $query = "INSERT INTO spip_auteurs (nom, email, login, pass, htpass, alea_futur, statut) VALUES(\"".$object->firstname." ".$object->lastname."\",\"".$object->email."\",\"".$object->login."\",\"$mdpass\",\"$htpass\",FLOOR(32000*RAND()),\"1comite\")";

            $mydb=getDoliDBInstance('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);

            if (! $mydb->ok)
            {
                $this->error=$mydb->lasterror();
                return 0;
            }

            $result = $mydb->query($query);
            if ($result)
            {
                $mydb->close();
                return 1;
            }
            else
            {
                $this->error=$mydb->lasterror();
                return 0;
            }
        }
    }

    /**
     *  Fonction qui enleve les droits redacteurs dans spip
     *
     *	@param	Object	$object		Object with data (->login)
     *  @return	int					=0 if KO, >0 if OK
     */
    function del_to_spip($object)
    {
        if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
        defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
        defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
        defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
        defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != ''
        )
        {
            $query = "DELETE FROM spip_auteurs WHERE login='".$object->login."'";

            $mydb=getDoliDBInstance('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);

            $result = $mydb->query($query);
            if ($result)
            {
                $mydb->close();
                return 1;
            }
            else
            {
                $this->error=$mydb->lasterror();
                $mydb->close();
                return 0;
            }
        }
    }

    /**
     *  Fonction qui dit si cet utilisateur est un redacteur existant dans spip
     *
     *	@param	Object	$object		Object with data (->login)
     *  @return int     			1=exists, 0=does not exists, -1=error
     */
    function is_in_spip($object)
    {
        if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1 &&
        defined('ADHERENT_SPIP_SERVEUR') && ADHERENT_SPIP_SERVEUR != '' &&
        defined('ADHERENT_SPIP_USER') && ADHERENT_SPIP_USER != '' &&
        defined('ADHERENT_SPIP_PASS') && ADHERENT_SPIP_PASS != '' &&
        defined('ADHERENT_SPIP_DB') && ADHERENT_SPIP_DB != '')
        {
            $query = "SELECT login FROM spip_auteurs WHERE login='".$object->login."'";

            $mydb=getDoliDBInstance('mysql',ADHERENT_SPIP_SERVEUR,ADHERENT_SPIP_USER,ADHERENT_SPIP_PASS,ADHERENT_SPIP_DB,ADHERENT_SPIP_PORT);

            if ($mydb->ok)
            {
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
                    $this->error=$mydb->lasterror();
                    $mydb->close();
                    return -1;
                }
            }
            else
            {
                $this->error="Failed to connect ".ADHERENT_SPIP_SERVEUR." ".ADHERENT_SPIP_USER." ".ADHERENT_SPIP_PASS." ".ADHERENT_SPIP_DB;
                return -1;
            }
        }
    }

    /**
     *  Subscribe an email to all mailing-lists
     *
     *	@param	Object	$object		Object with data (->email, ->pass, ->element, ->type)
     *  @param	array	$listes    	To force mailing-list (string separated with ,)
     *  @return	int		  			<0 if KO, >=0 if OK
     */
    function add_to_mailman($object,$listes='')
    {
        global $conf,$langs,$user;

        dol_syslog(get_class($this)."::add_to_mailman");

        if (! function_exists("curl_init"))
        {
            $langs->load("errors");
            $this->error=$langs->trans("ErrorFunctionNotAvailableInPHP","curl_init");
            return -1;
        }

        if (! empty($conf->global->ADHERENT_MAILMAN_URL))
        {
            if ($listes == '' && ! empty($conf->global->ADHERENT_MAILMAN_LISTS))
            {
                $lists=explode(',',$conf->global->ADHERENT_MAILMAN_LISTS);
            }
            else
            {
                $lists=explode(',',$listes);
            }
            foreach ($lists as $list)
            {
                // Filter on type something (ADHERENT_MAILMAN_LISTS = "filtervalue:mailinglist1,filtervalue2:mailinglist2,mailinglist3")
                $tmp=explode(':',$list);
                if (! empty($tmp[1]))
                {
                    $list=$tmp[1];
                    if ($object->element == 'member' && $object->type != $tmp[0])    // Filter on member type label
                    {
                        dol_syslog("We ignore list ".$list." because object member type ".$object->type." does not match ".$tmp[0], LOG_DEBUG);
                        continue;
                    }
                }

                // on remplace dans l'url le nom de la liste ainsi
                // que l'email et le mot de passe
                $patterns = array (
    				'/%LISTE%/',
    				'/%EMAIL%/',
    				'/%PASSWORD%/',
    				'/%MAILMAN_ADMINPW%/'
				);
				$replace = array (
    				$list,
    				$object->email,
    				$object->pass,
    				$conf->global->ADHERENT_MAILMAN_ADMINPW
				);
				$curl_url = preg_replace($patterns, $replace, $conf->global->ADHERENT_MAILMAN_URL);

                dol_syslog("Call URL to subscribe : ".$curl_url);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"$curl_url");
				//curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FAILONERROR, true);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				//curl_setopt($ch, CURLOPT_POST, 0);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
				//--- Start buffering
				$result=curl_exec($ch);
				dol_syslog('result curl_exec='.$result);
				//--- End buffering and clean output
				if ($result === false || curl_errno($ch) > 0)
				{
				    // error
				    $this->error=curl_errno($ch).' '.curl_error($ch);
				    dol_syslog('Error using curl '.$this->error, LOG_ERR);
				    return -2;
				}
				curl_close($ch);
            }
            return count($lists);
        }
        else
        {
            $this->error="ADHERENT_MAILMAN_URL not defined";
            return -1;
        }
    }

    /**
     *  Unsubscribe an email from all mailing-lists
     *  Used when a user is resiliated
     *
     *	@param	Object	$object		Object with data (->email, ->pass, ->element, ->type)
     *  @param	array	$listes     To force mailing-list (string separated with ,)
     *  @return int         		<0 if KO, >=0 if OK
     */
    function del_to_mailman($object,$listes='')
    {
        global $conf,$langs,$user;

        if (! empty($conf->global->ADHERENT_MAILMAN_UNSUB_URL))
        {
            if ($listes=='' && ! empty($conf->global->ADHERENT_MAILMAN_LISTS))
            {
                $lists=explode(',',$conf->global->ADHERENT_MAILMAN_LISTS);
            }
            else
            {
                $lists=explode(',',$listes);
            }
            foreach ($lists as $list)
            {
                // Filter on type something (ADHERENT_MAILMAN_LISTS = "filtervalue:mailinglist1,filtervalue2:mailinglist2,mailinglist3")
                $tmp=explode(':',$list);
                if (! empty($tmp[1]))
                {
                    if ($object->element == 'member' && $object->type != $tmp[1])    // Filter on member type label
                    {
                        continue;
                    }
                }

                // on remplace dans l'url le nom de la liste ainsi
                // que l'email et le mot de passe
                $patterns = array (
    				'/%LISTE%/',
    				'/%EMAIL%/',
    				'/%PASSWORD%/',
    				'/%MAILMAN_ADMINPW%/'
				);
				$replace = array (
    				trim($list),
    				$object->email,
    				$object->pass,
    				$conf->global->ADHERENT_MAILMAN_ADMINPW
				);
				$curl_url = preg_replace($patterns, $replace, $conf->global->ADHERENT_MAILMAN_UNSUB_URL);

                dol_syslog("Call URL to unsubscribe : ".$curl_url);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,"$curl_url");
				//curl_setopt($ch, CURLOPT_URL,"http://www.j1b.org/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
				curl_setopt($ch, CURLOPT_FAILONERROR, 1);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				//curl_setopt($ch, CURLOPT_POST, 0);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "a=3&b=5");
				//--- Start buffering
				$result=curl_exec($ch);
				dol_syslog($result);
				//--- End buffering and clean output
				if ($result === false || curl_errno($ch) > 0)
				{
				    $this->error=curl_errno($ch).' '.curl_error($ch);
				    dol_syslog('Error using curl '.$this->error, LOG_ERR);
				    // error
				    return -2;
				}
				curl_close($ch);
            }
            return count($lists);
        }
        else
        {
            $this->error="ADHERENT_MAILMAN_UNSUB_URL not defined";
            return -1;
        }
    }

}
?>
