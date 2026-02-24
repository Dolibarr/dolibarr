<?php
/* Copyright (C) 2018       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2018-2025  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024      William Mead      <william.mead@manchenumerique.fr>
 * Copyright (C) 2024-2025	MDW                      <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025      Quentin VIAL--GOUTEYRON   <quentin.vial-gouteyron@atm-consulting.fr>
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
	/** @var DoliDB Database handler. */
	public $db;
	/** @var string Final error message if any. */
	public $error;
	/** @var string Final output message on success. */
	public $output;
	/** @var int Counter for updated records. */
	private $nbupdated = 0;
	/** @var int Counter for deleted records. */
	private $nbdeleted = 0;
	/** @var int Counter for errors. */
	private $errorCount = 0;
	/** @var string[] Array to store detailed error messages. */
	private $errorMessages = array();

	/**
	 * Constructor
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Defines and returns the centralized data policy configuration.
	 * Separating this makes the main method cleaner.
	 *
	 * @return 	array<string, array<string, mixed>> 	The array of all data policies.
	 */
	public function getDataPolicies()
	{
		$prefix = $this->db->prefix();

		$arrayofpolicies = array(
			// --- Third Parties ---
			'tiers_client' => array(
				'group' => 'ThirdParty',
				'label_key' => 'DATAPOLICY_TIERS_CLIENT',
				'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_TIERS_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.client = ".Societe::CUSTOMER." AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe',
				'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
				'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_alias' => 'MAKEANONYMOUS', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'email' => 'anonymous+__ID__@example.com', 'url' => '---', 'fax' => '---', 'siret' => '---', 'siren' => '---', 'ape' => '---', 'idprof4' => '---', 'idprof5' => '---', 'idprof6' => '---', 'tva_intra' => '---', 'capital' => 0, 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('id', 'user'), // $object->delete($id, $user)
					'update' => array('id', 'user')  // $object->update($id, $user)
				)
			),
			'tiers_prospect' => array(
				'group' => 'ThirdParty',
				'label_key' => 'DATAPOLICY_TIERS_PROSPECT',
				'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_TIERS_PROSPECT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.client = ".Societe::PROSPECT." AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe',
				'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
				'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_alias' => 'MAKEANONYMOUS', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'email' => 'anonymous+__ID__@example.com', 'url' => '---', 'fax' => '---', 'siret' => '---', 'siren' => '---', 'ape' => '---', 'idprof4' => '---', 'idprof5' => '---', 'idprof6' => '---', 'tva_intra' => '---', 'capital' => 0, 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('id', 'user'), // $object->delete($id, $user)
					'update' => array('id', 'user')  // $object->update($id, $user)
				)
			),
			'tiers_prospect_client' => array(
				'group' => 'ThirdParty',
				'label_key' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT',
				'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.client = ".Societe::CUSTOMER_AND_PROSPECT." AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe',
				'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
				'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_alias' => 'MAKEANONYMOUS', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'email' => 'anonymous+__ID__@example.com', 'url' => '---', 'fax' => '---', 'siret' => '---', 'siren' => '---', 'ape' => '---', 'idprof4' => '---', 'idprof5' => '---', 'idprof6' => '---', 'tva_intra' => '---', 'capital' => 0, 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('id', 'user'), // $object->delete($id, $user)
					'update' => array('id', 'user')  // $object->update($id, $user)
				)
			),
			'tiers_niprosp_niclient' => array(
				'group' => 'ThirdParty',
				'label_key' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT',
				'picto' => img_picto('', 'company', 'class="pictofixedwidth"'),
				'const_delete' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_DELETE_DELAY',
				'const_anonymize' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.client = ".Societe::NO_CUSTOMER." AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'sql_template_delete' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.client = ".Societe::NO_CUSTOMER." AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe',
				'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
				'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_alias' => 'MAKEANONYMOUS', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'email' => 'anonymous+__ID__@example.com', 'url' => '---', 'fax' => '---', 'siret' => '---', 'siren' => '---', 'ape' => '---', 'idprof4' => '---', 'idprof5' => '---', 'idprof6' => '---', 'tva_intra' => '---', 'capital' => 0, 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('id', 'user'), // $object->delete($id, $user)
					'update' => array('id', 'user')  // $object->update($id, $user)
				)
			),
			'tiers_fournisseur' => array(
				'group' => 'ThirdParty',
				'label_key' => 'DATAPOLICY_TIERS_FOURNISSEUR',
				'picto' => img_picto('', 'supplier', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_TIERS_FOURNISSEUR_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM ".$prefix."societe as s WHERE s.entity = __ENTITY__ AND s.fournisseur = 1 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe',
				'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php',
				'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_alias' => 'MAKEANONYMOUS', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'email' => 'anonymous+__ID__@example.com', 'url' => '---', 'fax' => '---', 'siret' => '---', 'siren' => '---', 'ape' => '---', 'idprof4' => '---', 'idprof5' => '---', 'idprof6' => '---', 'tva_intra' => '---', 'capital' => 0, 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('id', 'user'), // $object->delete($id, $user)
					'update' => array('id', 'user')  // $object->update($id, $user)
				)
			),
			// --- Contacts ---
			'contact_client' => array(
				'group' => 'Contact',
				'label_key' => 'DATAPOLICY_CONTACT_CLIENT',
				'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_CONTACT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM ".$prefix."socpeople as c INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = ".Societe::CUSTOMER." AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact',
				'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'poste' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone_pro' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'), // $object->delete($user)
					'update' => array('id', 'user') // $object->update($id, $user)
				)
			),
			'contact_prospect' => array(
				'group' => 'Contact',
				'label_key' => 'DATAPOLICY_CONTACT_PROSPECT',
				'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM ".$prefix."socpeople as c INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = ".Societe::PROSPECT." AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact',
				'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'poste' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone_pro' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'), // $object->delete($user)
					'update' => array('id', 'user') // $object->update($id, $user)
				)
			),
			'contact_prospect_client' => array(
				'group' => 'Contact',
				'label_key' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT',
				'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM ".$prefix."socpeople as c INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = ".Societe::CUSTOMER_AND_PROSPECT." AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact',
				'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'poste' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone_pro' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'), // $object->delete($user)
					'update' => array('id', 'user') // $object->update($id, $user)
				)
			),
			'contact_niprosp_niclient' => array(
				'group' => 'Contact',
				'label_key' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT',
				'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM ".$prefix."socpeople as c INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = ".Societe::NO_CUSTOMER." AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact',
				'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'poste' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone_pro' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'), // $object->delete($user)
					'update' => array('id', 'user') // $object->update($id, $user)
				)
			),
			'contact_fournisseur' => array(
				'group' => 'Contact',
				'label_key' => 'DATAPOLICY_CONTACT_FOURNISSEUR',
				'picto' => img_picto('', 'contact', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_CONTACT_FOURNISSEUR_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM ".$prefix."socpeople as c INNER JOIN ".$prefix."societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.fournisseur = 1 AND NOT EXISTS (SELECT a.id FROM ".$prefix."actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM ".$prefix."facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact',
				'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'poste' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone_pro' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'geolat' => 0, 'geolong' => 0, 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'), // $object->delete($user)
					'update' => array('id', 'user') // $object->update($id, $user)
				)
			)
		);
		if (isModEnabled('member')) {
			// --- Members ---
			$sqltemplate = "SELECT a.rowid FROM ".$prefix."adherent as a WHERE a.entity = __ENTITY__ AND a.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)";
			$sqltemplate .= " AND NOT EXISTS (SELECT ac.id FROM ".$prefix."actioncomm as ac WHERE ac.fk_element = a.rowid AND ac.elementtype = 'member' AND ac.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH))";
			$sqltemplate .= " AND NOT EXISTS (SELECT s.rowid FROM ".$prefix."subscription as s WHERE s.fk_adherent = a.rowid AND s.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH))";

			$arrayofpolicies['adherent'] = array(
				'group' => 'Member',
				'label_key' => 'DATAPOLICY_ADHERENT',
				'picto' => img_picto('', 'member', 'class="pictofixedwidth"'),
				'const_delete' => '',
				'const_anonymize' => 'DATAPOLICY_ADHERENT_ANONYMIZE_DELAY',
				'sql_template' => $sqltemplate,
				'class' => 'Adherent',
				'file' => DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php',
				'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'societe' => '---', 'address' => '---', 'town' => '---', 'zip' => '---', 'phone' => '---', 'phone_perso' => '---', 'phone_mobile' => '---', 'email' => 'anonymous+__ID__@example.com', 'birth' => '1900-01-01', 'photo' => '', 'url' => '---', 'fax' => '---', 'socialnetworks' => [], 'ip' => '0.0.0.0'),
				'call_params' => array(
					'delete' => array('user'),   // $object->delete($user)
					'update' => array('user')    // $object->update($user)
				)
			);
		}

		if (isModEnabled('recruitment')) {
			// --- Recruitment ---
			$arrayofpolicies['recruitment_candidature'] = array(
				'group' => 'Recruitment',
				'label_key' => 'DATAPOLICY_RECRUITMENT_CANDIDATURE',
				'picto' => img_picto('', 'recruitmentcandidature', 'class="pictofixedwidth"'),
				'const_delete' => 'DATAPOLICY_RECRUITMENT_CANDIDATURE_DELETE_DELAY',
				'const_anonymize' => '', // Anonymization not applicable
				'sql_template_delete' => "SELECT c.rowid FROM ".$prefix."recruitment_recruitmentcandidature as c WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT ac.id FROM ".$prefix."actioncomm as ac WHERE ac.elementtype = 'recruitmentcandidature@recruitment' AND ac.fk_element = c.rowid AND ac.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH))",
				'class' => 'RecruitmentCandidature',
				'file' => DOL_DOCUMENT_ROOT . '/recruitment/class/recruitmentcandidature.class.php',
				'anonymize_fields' => array(),
				'call_params' => array(
					'delete' => array('user'),   // $object->delete($user)
					'update' => array('user')    // $object->update($user)
				)
			);
		}

		// TODO Allow an external module to add an entry into the array


		return $arrayofpolicies;
	}

	/**
	 * Main cron task execution method.
	 * Orchestrates the data cleaning process by iterating through all defined policies.
	 *
	 * @return 	int 	Returns 0 for success, 1 for failure, as required for cron jobs.
	 */
	public function cleanDataForDataPolicy(): int
	{
		global $conf, $user;

		// Reset state properties for this specific execution run.
		$this->nbupdated = 0;
		$this->nbdeleted = 0;
		$this->errorCount = 0;
		$this->errorMessages = array();

		// Tracks record IDs that have been processed in this run to prevent duplicate actions (e.g., anonymizing a just-deleted record).
		$processedIds = array();
		// Caches object instances to avoid redundant 'new Class()' calls, improving performance.
		$objectInstances = array();

		// Retrieve the master list of all data policies. This separates configuration from execution.
		$dataPolicies = $this->getDataPolicies();

		$this->db->begin();

		// Iterate through each defined policy to apply its rules.
		foreach ($dataPolicies as $policy) {
			// Instantiate object only once per class type for efficiency.
			if (! isset($objectInstances[$policy['class']])) {
				require_once $policy['file'];
				$classtoinit = $policy['class'];
				$objectInstances[$policy['class']] = new $classtoinit($this->db);
			}
			$object = $objectInstances[$policy['class']];

			// Process actions.
			// ->errorCount, ->nbupdated and ->nbdelete will be set after that.

			// The order of operations is critical: deletion is always processed before anonymization.
			// This ensures that if a record meets criteria for both, it is deleted as the first and final action.
			$this->_processPolicyAction($policy, 'delete', $object, $processedIds, $conf, $user);
			// Now process anonymization
			$this->_processPolicyAction($policy, 'anonymize', $object, $processedIds, $conf, $user);
		}

		// Finalize the transaction based on the outcome of all operations.
		if (! $this->errorCount) {
			$this->db->commit();
			$this->output = $this->nbupdated . ' record(s) anonymized, ' . $this->nbdeleted . ' record(s) deleted.';
		} else {
			$this->db->rollback();
			$this->error = implode("\n", $this->errorMessages);
		}

		return $this->errorCount ? 1 : 0;
	}

	/**
	 * Processes a specific action (delete or anonymize) for a given policy.
	 * This method orchestrates the process by delegating to specialized handlers.
	 *
	 * @param array<string, mixed> 	$policy 		The policy definition array.
	 * @param string 				$action 		The action to perform: 'delete' or 'anonymize'.
	 * @param CommonObject 			$object 		The instantiated Dolibarr object.
	 * @param int[] 				$processedIds 	Reference to the array of processed IDs.
	 * @param object 				$conf 			The global conf object.
	 * @param User 					$user 			The user object for history tracking.
	 * @return void
	 */
	private function _processPolicyAction($policy, $action, $object, &$processedIds, $conf, $user)
	{
		$constName = $policy['const_' . $action] ?? null;
		$delay = $constName ? getDolGlobalInt($constName) : 0;

		if ($delay <= 0) {
			return;
		}

		// Prepare SQL query
		$sqlPlaceholders = array(
			'__ENTITY__' => (string) $conf->entity,
			'__DELAY__' => (string) $delay,
			'__NOW__' => "'" . $this->db->idate(dol_now()) . "'"
		);
		$sql = str_replace(array_keys($sqlPlaceholders), array_values($sqlPlaceholders), $policy['sql_template']);

		$resql = $this->db->query($sql);

		if (! $resql) {
			$this->errorCount++;
			$this->errorMessages[] = 'Error executing ' . $action . ' query for policy ' . $constName . ': ' . $this->db->lasterror();

			return;
		}

		// Define the handler method for the action
		$handlerMethod = '_handle' . ucfirst($action);

		// Process the records found by the query
		while ($obj = $this->db->fetch_object($resql)) {
			if (in_array($obj->rowid, $processedIds) || ! method_exists($this, $handlerMethod)) {
				continue;
			}
			/** @var CommonObject $object */
			$object = clone $object;
			$object->fetch($obj->rowid);

			if (!empty($object->childtables) && method_exists($object, 'isObjectUsed') && $object->isObjectUsed() != 0) {
				continue; // Not an error, just skipping.
			}

			// Dynamically call the appropriate handler (_handleDelete or _handleAnonymize)
			$result = $this->$handlerMethod($object, $user, $policy);

			// Record the outcome and add to processed list on success
			$this->_recordActionResult($result, $object, $action);
			$processedIds[] = $obj->rowid;
		}
	}

	/**
	 * Handles the specific logic for deleting an object.
	 *
	 * @param 	CommonObject 			$object 	The object to delete.
	 * @param 	User 					$user 		The user performing the action.
	 * @param 	array<string, mixed> 	$policy 	The policy configuration.
	 * @return 	int   								The result of the delete operation.
	 */
	private function _handleDelete($object, $user, $policy): int
	{
		$callArgs = $this->_buildCallArguments($object, $user, $policy, 'delete');

		return $object->delete(...$callArgs);
	}

	/**
	 * Handles the specific logic for anonymizing an object.
	 *
	 * @param 	CommonObject 	$object 		The object to anonymize.
	 * @param 	User 			$user 			The user performing the action.
	 * @param 	array<string, mixed> $policy 	The policy configuration.
	 * @return 	int   							The result of the update operation, or 0 if skipped.
	 */
	private function _handleAnonymize($object, $user, $policy): int
	{
		foreach ($policy['anonymize_fields'] as $field => $val) {
			if ($val == 'MAKEANONYMOUS') {
				// For each field with rule "MAKEANONYMOUS, set the new value, keeping the ID.
				$object->$field = $field . '-anon-' . $object->id;
			} else {
				// For others, force the value, but only if not already empty.
				if (!empty($object->$field)) {
					$newval = str_replace('__ID__', $object->id ? (string) $object->id : '0', $val);
					$object->$field = $newval;
				}
			}
		}

		$callArgs = $this->_buildCallArguments($object, $user, $policy, 'update');

		return $object->update(...$callArgs);
	}

	/**
	 * Builds the dynamic argument list for method calls based on policy configuration.
	 *
	 * @param CommonObject $object The target object.
	 * @param User $user The user object.
	 * @param array<string, mixed> $policy The policy configuration.
	 * @param 'delete'|'update' $method The method key ('delete' or 'update').
	 * @return mixed[] The list of arguments for the call.
	 */
	private function _buildCallArguments($object, $user, $policy, $method)
	{
		$availableArgs = array(
			'id' => $object->id,
			'user' => $user
		);

		$paramConfig = $policy['call_params'][$method] ?? [];

		return array_map(
			/**
			 * @param	string$paramName	Name of parameter to get
			 * @return	mixed				Parameter value
			 */
			static function (string $paramName) use ($availableArgs) {
				return $availableArgs[$paramName];
			},
			$paramConfig
		);
	}

	/**
	 * Records the result of an action, updating counters and error messages.
	 *
	 * @param int $result The result code from the action (<0 for error).
	 * @param CommonObject $object The processed object.
	 * @param string $action The action that was performed ('delete' or 'anonymize').
	 * @return void
	 */
	private function _recordActionResult($result, $object, $action)
	{
		if ($result <= 0) {
			$this->errorCount++;
			$this->errorMessages[] = 'Failed to ' . $action . ' record ID ' . $object->id . ' from class ' . get_class($object) . '. Error: ' . $object->errorsToString();
		} else {
			if ($action === 'delete') {
				$this->nbdeleted++;
			} elseif ($action === 'anonymize') {
				// Only count as updated if the update method returns a positive result
				$this->nbupdated++;
			}
		}
	}
}
