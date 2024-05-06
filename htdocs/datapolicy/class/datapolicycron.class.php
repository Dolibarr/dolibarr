<?php
/* Copyright (C) 2018       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $error;

	public $output;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Function exec
	 * CAN BE A CRON TASK
	 *
	 * @return		int					if OK: 0 (this function is used also by cron so only 0 is OK)
	 */
	public function cleanDataForDataPolicy()
	{
		global $conf, $langs, $user;

		$langs->load('datapolicy@datapolicy');

		$error = 0;
		$errormsg = '';
		$nbupdated = $nbdeleted = 0;

		// FIXME Exclude data from the selection if there is at least 1 invoice.
		$arrayofparameters = array(
			'DATAPOLICY_TIERS_CLIENT' => array(
				'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE s.entity = %d
                    AND s.client = 1
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Societe",
				"file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
				'fields_anonym' => array(
					'name' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_TIERS_PROSPECT' => array(
				'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE s.entity = %d
                    AND s.client = 2
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Societe",
				"file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
				'fields_anonym' => array(
					'name' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_TIERS_PROSPECT_CLIENT' => array(
				'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE s.entity = %d
                    AND s.client = 3
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Societe",
				"file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
				'fields_anonym' => array(
					'name' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT' => array(
				'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE s.entity = %d
                    AND s.client = 0
                    AND s.fournisseur = 0
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Societe",
				"file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
				'fields_anonym' => array(
					'name' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_TIERS_FOURNISSEUR' => array(
				'sql' => "
                    SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s
                    WHERE s.entity = %d
                    AND s.fournisseur = 1
                    AND s.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Societe",
				"file" => DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php',
				'fields_anonym' => array(
					'name' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_CONTACT_CLIENT' => array(
				'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 1
                    AND s.fournisseur = 0
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Contact",
				"file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_CONTACT_PROSPECT' => array(
				'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 2
                    AND s.fournisseur = 0
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Contact",
				"file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_CONTACT_PROSPECT_CLIENT' => array(
				'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 3
                    AND s.fournisseur = 0
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Contact",
				"file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT' => array(
				'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.client = 0
                    AND s.fournisseur = 0
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Contact",
				"file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_CONTACT_FOURNISSEUR' => array(
				'sql' => "
                    SELECT c.rowid FROM ".MAIN_DB_PREFIX."socpeople as c
                    INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc
                    WHERE c.entity = %d
                    AND c.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
                    AND s.fournisseur = 1
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH)
                    )
					AND NOT EXISTS (
                        SELECT rowid FROM ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.rowid
                    )
                ",
				"class" => "Contact",
				"file" => DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
			'DATAPOLICY_ADHERENT' => array(
				'sql' => "
                    SELECT a.rowid FROM ".MAIN_DB_PREFIX."adherent as a
                    WHERE a.entity = %d
                    AND a.tms < DATE_SUB(NOW(), INTERVAL %d MONTH)
					AND NOT EXISTS (
                        SELECT id FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_element = a.rowid AND a.tms > DATE_SUB(NOW(), INTERVAL %d MONTH) AND a.elementtype LIKE 'member'
                    )
                ",
				"class" => "Adherent",
				"file" => DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php',
				'fields_anonym' => array(
					'lastname' => 'MAKEANONYMOUS',
					'firstname' => 'MAKEANONYMOUS',
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
					'state_id' => 1,
					'socialnetworks' => [],
					'country_id' => 0,
				)
			),
		);

		$this->db->begin();

		foreach ($arrayofparameters as $key => $params) {
			if (getDolGlobalInt($key) > 0) {
				// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
				$sql = sprintf($params['sql'], (int) $conf->entity, (int) getDolGlobalInt($key), (int) getDolGlobalInt($key));

				$resql = $this->db->query($sql);

				if ($resql && $this->db->num_rows($resql) > 0) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					require_once $params['file'];
					$object = new $params['class']($this->db);

					while ($i < $num && !$error) {
						$obj = $this->db->fetch_object($resql);

						$object->fetch($obj->rowid);
						$object->id = $obj->rowid;

						$action = 'anonymize';	// TODO Offer also action "delete" in setup of module

						if ($action == 'anonymize') {
							if ($object->isObjectUsed($obj->rowid) == 0) {			// If object to clean is used
								foreach ($params['fields_anonym'] as $field => $val) {
									if ($val == 'MAKEANONYMOUS') {
										$object->$field = $field.'-anonymous-'.$obj->rowid; // @phpstan-ignore-line
									} else {
										$object->$field = $val;
									}
								}
								$result = $object->update($obj->rowid, $user);
								if ($result > 0) {
									$errormsg = $object->error;
									$error++;
								}
								$nbupdated++;
							}
						}

						if ($action == 'delete') {									// If object to clean is not used
							$result = $object->delete($user);
							if ($result < 0) {
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

		if (!$error) {
			$this->output = $nbupdated.' record updated, '.$nbdeleted.' record deleted';
		} else {
			$this->error = $errormsg;
		}

		return 0;
	}
}
