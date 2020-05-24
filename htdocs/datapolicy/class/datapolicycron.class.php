<?php
/* Copyright (C) 2018       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2018-2020  Frédéric France     <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/class/datapolicycron.class.php
 * \ingroup datapolicy
 * \brief   File for cron task of module DataPolicy
 */

/**
 * Class DataPolicyCron
 */
class DataPolicyCron
{
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
     * Function exec
	 * CAN BE A CRON TASK
     *
	 * @return	int									0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
	public function cleanDataForDataPolicy()
    {
        global $conf, $langs, $user;

        $langs->load('datapolicy@datapolicy');

        $error = 0;
        $errormsg = '';
        $nbupdated = $nbdeleted = 0;

        // FIXME Removed hardcoded values of id
        $arrayofparameters = array(
            'DATAPOLICIES_TIERS_CLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique IN (11, 12, 13, 15, 17, 18, 19, 35, 60, 312, 316, 401, 600, 700, 1005) OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 1
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_TIERS_PROSPECT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique IN (11, 12, 13, 15, 17, 18, 19, 35, 60, 312, 316, 401, 600, 700, 1005) OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 2
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_TIERS_PROSPECT_CLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique  IN (11, 12, 13, 15, 17, 18, 19, 35, 60, 312, 316, 401, 600, 700, 1005) OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 3
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_TIERS_NIPROSPECT_NICLIENT' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique  IN (11, 12, 13, 15, 17, 18, 19, 35, 60, 312, 316, 401, 600, 700, 1005) OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.client = 0
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_soc IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_TIERS_FOURNISSEUR' => array(
                'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE (s.fk_forme_juridique  IN (11, 12, 13, 15, 17, 18, 19, 35, 60, 312, 316, 401, 600, 700, 1005) OR s.fk_typent = 8)
                    AND s.entity = %d
                    AND s.fournisseur = 1
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.rowid NOT IN (
                        SELECT DISTINCT a.fk_soc
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Societe",
                "file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
                'fields_anonym' => array(
                    'name' => $langs->trans('ANONYME'),
                    'name_bis' => '',
                    'name_alias' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_CONTACT_CLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 1
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_CONTACT_PROSPECT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 2
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_CONTACT_PROSPECT_CLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 3
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_CONTACT_NIPROSPECT_NICLIENT' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 0
                    AND s.fournisseur = 0
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_CONTACT_FOURNISSEUR' => array(
                'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.fournisseur = 1
                    AND c.rowid NOT IN (
                        SELECT DISTINCT a.fk_contact
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.fk_contact IS NOT NULL
                    )
                ",
                "class" => "Contact",
                "file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => '',
                    'civility_id' => '',
                    'poste' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone_pro' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'jabberid' => '',
                    'country_id' => '',
                )
            ),
            'DATAPOLICIES_ADHERENT' => array(
                'sql' => "
                    SELECT a.rowid FROM ".MAIN_DB_PREFIX."adherent as a
                    WHERE a.entity = %d
                    AND a.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND a.rowid NOT IN (
                        SELECT DISTINCT a.fk_element
                        FROM ".MAIN_DB_PREFIX."actioncomm as a
                        WHERE a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                        AND a.elementtype LIKE 'member'
                        AND a.fk_element IS NOT NULL
                    )
                ",
                "class" => "Adherent",
                "file" => DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php',
                'fields_anonym' => array(
                    'lastname' => $langs->trans('ANONYME'),
                    'firstname' => $langs->trans('ANONYME'),
                    'civility_id' => '',
                    'societe' => '',
                    'address' => '',
                    'town' => '',
                    'zip' => '',
                    'phone' => '',
                    'phone_perso' => '',
                    'phone_mobile' => '',
                    'email' => '',
                    'url' => '',
                    'fax' => '',
                    'state' => '',
                    'country' => '',
                    'state_id' => '',
                    'skype' => '',
                    'country_id' => '',
                )
            ),
        );

        $this->db->begin();

        foreach ($arrayofparameters as $key => $params)
        {
            if ($conf->global->$key != '' && is_numeric($conf->global->$key) && (int) $conf->global->$key > 0)
            {
                $sql = sprintf($params['sql'], (int) $conf->entity, (int) $conf->global->$key, (int) $conf->global->$key);

                $resql = $this->db->query($sql);

                if ($resql && $this->db->num_rows($resql) > 0)
                {
                    $num = $this->db->num_rows($resql);
                    $i = 0;

                    require_once $params['file'];
                    $object = new $params['class']($this->db);

                    while ($i < $num && !$error)
                    {
                        $obj = $this->db->fetch_object($resql);

                        $object->fetch($obj->rowid);
                        $object->id = $obj->rowid;

                        if ($object->isObjectUsed($obj->rowid) > 0)			// If object to clean is used
                        {
                            foreach ($params['fields_anonym'] as $fields => $val) {
                                $object->$fields = $val;
                            }
                            $result = $object->update($obj->rowid, $user);
                            if ($result > 0)
                            {
	                            if ($params['class'] == 'Societe') {
	                                // We delete contacts of thirdparty
	                                $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$obj->rowid;
	                                $result = $this->db->query($sql);
	                                if ($result < 0)
	                                {
	                                	$errormsg = $this->db->lasterror();
	                                	$error++;
	                                }
	                            }
                            }
                            else
                            {
                            	$errormsg = $object->error;
                            	$error++;
                            }
                            $nbupdated++;
                        } else {											// If object to clean is not used
                            if ($object->element == 'adherent') {
                            	$result = $object->delete($obj->rowid, $user);
                            } else {
                            	$result = $object->delete($user);
                            }
                            if ($result < 0)
                            {
                            	$errormsg = $object->error;
                            	$error++;
                            }

                            $nbdeleted++;
                        }

                        $i++;
                    }
                }
            }
        }

        $this->db->commit();

        if (!$error)
        {
        	$this->output = $nbupdated.' record updated, '.$nbdeleted.' record deleted';
        }
        else
        {
        	$this->error = $errormsg;
        }

        return 0;
    }
}
