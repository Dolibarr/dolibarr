<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2021 Kuba admin <js@hands-on-technology.org>
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

use Luracast\Restler\RestException;

dol_include_once('/handson/class/contaokategorie.class.php');


/**
 * \file    handson/class/api_handson.class.php
 * \ingroup handson
 * \brief   File for API management of contaokategorie.
 */

/**
 * API class for handson contaokategorie
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class HandsOn extends DolibarrApi
{
	/**
	 * @var Contaokategorie $contaokategorie {@type Contaokategorie}
	 */
	public $contaokategorie;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->contaokategorie = new Contaokategorie($this->db);
	}

	/**
	 * Clean sensible object datas
	 *
	 * @param Object $object Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		/*unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		*/

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array $data Array of data to validate
	 * @return    array
	 *
	 * @throws    RestException
	 */
	private function _validate($data)
	{
		$contaokategorie = array();
		foreach ($this->contaokategorie->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) continue; // Not a mandatory field
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$contaokategorie[$field] = $data[$field];
		}
		return $contaokategorie;
	}

	/**
	 * Contaokategorien auflisten
	 *
	 * @return mixed
	 *
	 * @url GET kategorien
	 */
	public function getContaocategories()
	{
		global $db, $conf;

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$sql = "SELECT t.ref, t.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "handson_contaokategorie as t";

		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$ergebnisse = [];
		if ($result) {
			return $result;
		} else {
			throw new RestException(503, 'Error when retrieve product list : ' . $this->db->lasterror());
		}
	}

	/**
	 * Förderungen auflisten
	 *
	 * @return mixed
	 *
	 * @url GET foerderungen
	 */
	public function getFoerderungen()
	{
		global $db, $conf;

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$sql = "SELECT *";
		$sql .= " FROM " . MAIN_DB_PREFIX . "handson_foerderung as t";

		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$ergebnisse = [];
		if ($result) {
			return $result;
		} else {
			throw new RestException(503, 'Error when retrieve product list : ' . $this->db->lasterror());
		}
	}

	/**
	 * Saisons auflisten
	 *
	 * @return mixed
	 *
	 * @url GET saisons
	 *
	 **/
	public function getSaisons()
	{
		global $db, $conf;

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$sql = "SELECT t.rowid, t.ref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "handson_saison as t";

		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		$ergebnisse = [];
		if ($result) {
			return $result;
		} else {
			throw new RestException(503, 'Error when retrieve product list : ' . $this->db->lasterror());
		}
	}

	/**
	 * Email-Arten anzeigen
	 *
	 * @return mixed
	 *
	 * @url GET mails
	 *
	public function getMails()
	{
		return "Mails";
	}*/

	/**
	 * Email verschicken
	 *
	 * @param array $request_data Request data
	 *
	 * @return mixed
	 *
	 * @url POST sendmail
	 */
	public function sendMail($request_data)
	{
		global $conf;

		if ($request_data['type'] == "confirmOrder") {
			include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
			include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

			$order = new Commande($this->db);
			$order->fetch('', $request_data['order_ref']);
			$contactId = $order->getIdContact('external', "CUSTOMER");
			$foerd = $order->array_options['options_foerderung'];


			if ($foerd != "4" && $foerd != "5") {
				if (!is_dir(DOL_DATA_ROOT . '/commande/' . $order->ref . '/' . $order->ref . '.pdf')) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang('de_DE');
					$order->generateDocument('', $outputlangs);
				}

				// All contact data is now in $contact
				$contact = new Contact($this->db);
				$contact->fetch($contactId[0]);

				//$template = ($foerd == "1") ? 'confirmOrder_KLA_FOERD_CHA_HESSEN' : 'confirmOrder_KLA_2020-21';
				$template = 'confirmOrder_KLA_2020-21';

				$sql = "SELECT topic, content, joinfiles FROM llx_c_email_templates WHERE label='" . $template . "'";
				$result = $this->db->query($sql)->fetch_array(MYSQLI_ASSOC);

				$to = $contact->email;
				$cc = '';
				//$bcc = ($foerd == "1") ? '' : 'eq@hands-on-technology.org';
				$bcc = 'eq@hands-on-technology.org';
				$from = 'HANDS on TECHNOLOGY e.V.<info@hands-on-technology.org>';
				if ($result['joinfiles'] == '1') {
					$file = ($foerd == "1") ? array() : array(DOL_DATA_ROOT . '/commande/' . $order->ref . '/' . $order->ref . '.pdf');
					$mime = ($foerd == "1") ? array() : array('application/pdf');
					$filenames = ($foerd == "1") ? array() : array($order->ref . '.pdf');
				} else {
					$file = '';
					$mime = '';
					$filenames = '';
				}

				$content = $result['content'];
				$content = str_replace('__BESTELLNUMMER__', $order->ref, $content);
				$content = str_replace('__VORNAME__', $contact->firstname, $content);
				$content = str_replace('__NACHNAME__', $contact->lastname, $content);

				$mailfile = new CMailFile($result['topic'], $to, $from, $content, $file, $mime, $filenames, $cc, $bcc, 0, 1);

				if ($mailfile->sendfile()) {
					return 1;
				} else {
					$this->error = $langs->trans("ErrorFailedToSendMail", $from, $this->email) . '. ' . $mailfile->error;
					return -1;
				}
			}
		}

	}

	/**
	 * Team anlegen
	 *
	 * @param array $team_data Request data
	 *
	 * @return mixed
	 *
	 * @url POST teams
	 */
	public function createTeam($team_data)
	{
		if (!DolibarrApiAccess::$user->rights->handson->team->write) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($team_data);

		dol_include_once('custom/handson/class/team.class.php');
		$this->team = new Team($this->db);
		foreach ($team_data as $field => $value) {
			$this->team->$field = $value;
		}

		if ($this->team->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating team", array_merge(array($this->team->error), $this->team->errors));
		}
		return $this->team->id;
	}

	/**
	 * Team aufrufen
	 *
	 * @param int $team_id Team ID
	 *
	 * @return array|mixed
	 *
	 * @url GET teams/{team_id}
	 */
	public function getTeam($team_id)
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/team.class.php';
		$team = new Team($db);
		$team->fetch($team_id);

		/*$team->fe
		$team_data = [];
		array_push($team_data, $team->ref, $team->label);*/
		unset($team->fields);
		unset($team->db);
		unset($team->queries);

		return $team;
	}

	/**
	 * Teammitglied hinzufügen
	 *
	 * @param array $data
	 *
	 * @return int
	 *
	 * @url POST teams/players
	 */
	public function addPlayer($data) {
		if (!DolibarrApiAccess::$user->rights->handson->team->write) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($data);

		dol_include_once('custom/handson/class/player.class.php');
		$this->player = new Player($this->db);
		foreach ($data as $field => $value) {
			$this->player->$field = $value;
		}

		if ($this->player->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating team", array_merge(array($this->player->error), $this->player->errors));
		}
		return $this->player->id;
	}

	/**
	 * Teammitglieder auslesen
	 *
	 * @param int $team_id
	 *
	 * @return array
	 *
	 * @url GET teams/{team_id}/players
	 */

	public function getPlayers($team_id) {
		if (!DolibarrApiAccess::$user->rights->handson->team->read) {
			throw new RestException(401);
		}
		$result = $this->db->query("SELECT label, gender, essen FROM ".MAIN_DB_PREFIX."handson_player WHERE team=".$team_id);
		$players = [];
		for ($i = 0; $i < mysqli_num_rows($result); $i++) {
			array_push($players, mysqli_fetch_assoc($result));
		}
		return $players;
	}
}
