<?php
/* Copyright (C) 2018       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024      William Mead      <william.mead@manchenumerique.fr>
 * Copyright (C) 2024      MDW                      <mdeweerd@users.noreply.github.com>
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
	/** @var array Array to store detailed error messages. */
	private $errorMessages = array();

	/**
	 * Constructor
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db) {
		$this->db = $db;
	}

	/**
	 * Main cron task execution method.
	 * Orchestrates the data cleaning process by iterating through all defined policies.
	 * @return int Returns 0 for success, 1 for failure, as required for cron jobs.
	 */
	public function cleanDataForDataPolicy()
	{
		global $conf, $langs, $user;
		$langs->load('datapolicy@datapolicy');

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
		$dataPolicies = $this->_getDataPolicies();

		$this->db->begin();

		// Iterate through each defined policy to apply its rules.
		foreach ($dataPolicies as $policyKey => $policy) {
			// Instantiate object only once per class type for efficiency.
			if (!isset($objectInstances[$policy['class']])) {
				require_once $policy['file'];
				$objectInstances[$policy['class']] = new $policy['class']($this->db);
			}
			$object = $objectInstances[$policy['class']];

			// The order of operations is critical: deletion is always processed before anonymization.
			// This ensures that if a record meets criteria for both, it is deleted as the final action.
			$this->_processPolicyAction($policy, 'delete', $object, $processedIds, $conf, $user);
			$this->_processPolicyAction($policy, 'anonymize', $object, $processedIds, $conf, $user);
		}

		// Finalize the transaction based on the outcome of all operations.
		if (!$this->errorCount) {
			$this->db->commit();
			$this->output = $this->nbupdated . ' record(s) anonymized, ' . $this->nbdeleted . ' record(s) deleted.';
		} else {
			$this->db->rollback();
			$this->error = implode("\n", $this->errorMessages);
		}

		return $this->errorCount ? 1 : 0;
	}

	/**
	 * Private helper method to process a specific action (delete or anonymize) for a given policy.
	 * This encapsulates the core logic of querying and processing records.
	 *
	 * @param array    $policy         The policy definition array from _getDataPolicies().
	 * @param string   $action         The action to perform: 'delete' or 'anonymize'.
	 * @param object   $object         The instantiated Dolibarr object (e.g., Societe, Contact).
	 * @param array    &$processedIds  Reference to the array of IDs already processed in this run.
	 * @param object   $conf           The global conf object.
	 * @param User     $user           The user object for history tracking.
	 */
	private function _processPolicyAction($policy, $action, $object, &$processedIds, $conf, $user)
	{
		// Guard clause: Exit if the action (e.g., 'const_delete') is not defined for this policy.
		$constName = !empty($policy['const_' . $action]) ? $policy['const_' . $action] : '';
		if (empty($constName)) return;

		// Guard clause: Exit if the policy is disabled (delay is 0 or not set).
		$delay = getDolGlobalInt($constName);
		if ($delay <= 0) return;

		// Safely build the SQL query by replacing named tokens with sanitized, type-casted values.
		$sql = str_replace('__ENTITY__', (int) $conf->entity, $policy['sql_template']);
		$sql = str_replace('__DELAY__', (int) $delay, $sql);
		// Use Dolibarr's DB-agnostic function for the current timestamp for better database compatibility.
		$sql = str_replace('__NOW__', "'" . $this->db->idate(dol_now()) . "'", $sql);

		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->errorCount++;
			$this->errorMessages[] = 'Error executing ' . $action . ' query for policy ' . $constName . ': ' . $this->db->lasterror();
			return;
		}

		// Process the records found by the query.
		while ($obj = $this->db->fetch_object($resql)) {
			// Ensure each record is processed only once per cron run.
			if (in_array($obj->rowid, $processedIds)) continue;

			$object->fetch($obj->rowid);

			switch ($action) {
				case 'delete':
					if ($object->delete($user) < 0) {
						$this->errorCount++;
						$this->errorMessages[] = 'Failed to delete record ID ' . $obj->rowid . ' from class ' . $policy['class'] . '. Error: ' . $object->errorsToString();
					} else {
						$this->nbdeleted++;
						$processedIds[] = $obj->rowid;
					}
					break;

				case 'anonymize':
					// Business rule: Do not anonymize an object if it's still considered "in use" (e.g., linked to an unpaid invoice).
					if (method_exists($object, 'isObjectUsed') && $object->isObjectUsed() != 0) continue;

					// Apply anonymization to all specified fields.
					foreach ($policy['anonymize_fields'] as $field => $val) {
						$object->$field = ($val == 'MAKEANONYMOUS') ? $field . '-anonymous-' . $obj->rowid : $val;
					}

					if ($object->update($object->id, $user) < 0) {
						$this->errorCount++;
						$this->errorMessages[] = 'Failed to anonymize record ID ' . $obj->rowid . ' from class ' . $policy['class'] . '. Error: ' . $object->errorsToString();
					} else {
						$this->nbupdated++;
						$processedIds[] = $obj->rowid;
					}
					break;
			}
		}
	}

	/**
	 * Defines and returns the centralized data policy configuration.
	 * Separating this makes the main method cleaner.
	 * @return array The array of all data policies.
	 */
	private function _getDataPolicies() {
		$prefix = $this->db->prefix();

		return array(
			// --- Third Parties ---
			'tiers_client' => array(
				'const_delete' => 'DATAPOLICY_TIERS_CLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_TIERS_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM {$prefix}societe as s WHERE s.entity = __ENTITY__ AND s.client = 1 AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe', 'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php', 'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_bis' => '', 'name_alias' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'tiers_prospect' => array(
				'const_delete' => 'DATAPOLICY_TIERS_PROSPECT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_TIERS_PROSPECT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM {$prefix}societe as s WHERE s.entity = __ENTITY__ AND s.client = 2 AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe', 'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php', 'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_bis' => '', 'name_alias' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'tiers_prospect_client' => array(
				'const_delete' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_TIERS_PROSPECT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM {$prefix}societe as s WHERE s.entity = __ENTITY__ AND s.client = 3 AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe', 'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php', 'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_bis' => '', 'name_alias' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'tiers_niprosp_niclient' => array(
				'const_delete' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_TIERS_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM {$prefix}societe as s WHERE s.entity = __ENTITY__ AND s.client = 0 AND s.fournisseur = 0 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe', 'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php', 'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_bis' => '', 'name_alias' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'tiers_fournisseur' => array(
				'const_delete' => 'DATAPOLICY_TIERS_FOURNISSEUR_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_TIERS_FOURNISSEUR_ANONYMIZE_DELAY',
				'sql_template' => "SELECT s.rowid FROM {$prefix}societe as s WHERE s.entity = __ENTITY__ AND s.fournisseur = 1 AND s.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_soc = s.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Societe', 'file' => DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php', 'anonymize_fields' => array('name' => 'MAKEANONYMOUS', 'name_bis' => '', 'name_alias' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			// --- Contacts ---
			'contact_client' => array(
				'const_delete' => 'DATAPOLICY_CONTACT_CLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_CONTACT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM {$prefix}socpeople as c INNER JOIN {$prefix}societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = 1 AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact', 'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => '', 'civility_id' => '', 'poste' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone_pro' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'contact_prospect' => array(
				'const_delete' => 'DATAPOLICY_CONTACT_PROSPECT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM {$prefix}socpeople as c INNER JOIN {$prefix}societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = 2 AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact', 'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => '', 'civility_id' => '', 'poste' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone_pro' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'contact_prospect_client' => array(
				'const_delete' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_CONTACT_PROSPECT_CLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM {$prefix}socpeople as c INNER JOIN {$prefix}societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = 3 AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact', 'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => '', 'civility_id' => '', 'poste' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone_pro' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'contact_niprosp_niclient' => array(
				'const_delete' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM {$prefix}socpeople as c INNER JOIN {$prefix}societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.client = 0 AND s.fournisseur = 0 AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact', 'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => '', 'civility_id' => '', 'poste' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone_pro' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			'contact_fournisseur' => array(
				'const_delete' => 'DATAPOLICY_CONTACT_FOURNISSEUR_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_CONTACT_FOURNISSEUR_ANONYMIZE_DELAY',
				'sql_template' => "SELECT c.rowid FROM {$prefix}socpeople as c INNER JOIN {$prefix}societe as s ON s.rowid = c.fk_soc WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND s.fournisseur = 1 AND NOT EXISTS (SELECT a.id FROM {$prefix}actioncomm as a WHERE a.fk_contact = c.rowid AND a.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH)) AND NOT EXISTS (SELECT f.rowid FROM {$prefix}facture as f WHERE f.fk_soc = s.rowid)",
				'class' => 'Contact', 'file' => DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => '', 'civility_id' => '', 'poste' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone_pro' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			// --- Members ---
			'adherent' => array(
				'const_delete' => 'DATAPOLICY_ADHERENT_DELETE_DELAY', 'const_anonymize' => 'DATAPOLICY_ADHERENT_ANONYMIZE_DELAY',
				'sql_template' => "SELECT a.rowid FROM {$prefix}adherent as a WHERE a.entity = __ENTITY__ AND a.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT ac.id FROM {$prefix}actioncomm as ac WHERE ac.fk_element = a.rowid AND ac.elementtype = 'member' AND ac.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH))",
				'class' => 'Adherent', 'file' => DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php', 'anonymize_fields' => array('lastname' => 'MAKEANONYMOUS', 'firstname' => 'MAKEANONYMOUS', 'civility_id' => '', 'societe' => '', 'address' => '', 'town' => '', 'zip' => '', 'phone' => '', 'phone_perso' => '', 'phone_mobile' => '', 'email' => '', 'url' => '', 'fax' => '', 'state' => '', 'country' => '', 'state_id' => 1, 'socialnetworks' => [], 'country_id' => 0)
			),
			// --- Recruitment ---
			'recruitment_candidature' => array(
				'const_delete' => 'DATAPOLICY_RECRUITMENT_CANDIDATURE_DELETE_DELAY', 'const_anonymize' => '', // Anonymization not applicable
				'sql_template' => "SELECT c.rowid FROM {$prefix}recruitment_candidature as c WHERE c.entity = __ENTITY__ AND c.tms < DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH) AND NOT EXISTS (SELECT ac.id FROM {$prefix}actioncomm as ac WHERE ac.elementtype = 'recruitmentcandidature' AND ac.fk_element = c.rowid AND ac.tms > DATE_SUB(__NOW__, INTERVAL __DELAY__ MONTH))",
				'class' => 'RecruitmentCandidature', // Please verify this class name
				'file' => DOL_DOCUMENT_ROOT . '/recruitment/class/candidature.class.php', // Please verify this path
				'anonymize_fields' => array()
			)
		);
	}
}