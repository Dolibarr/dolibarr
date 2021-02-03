<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin	        <regis.houssin@inodbox.com>
 * Copyright (C) 2017	Neil Orley	            <neil.orley@oeris.fr>
 * Copyright (C) 2018-2020   Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018-2020   Thibault FOUCART        <support@ptibogxiv.net>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cstate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';


/**
 * API class for dictionaries
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Setup extends DolibarrApi
{
	private $translations = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Get the list of ordering methods.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Payment type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter with. Syntax example "(t.code:=:'OrderByWWW')"
	 *
	 * @url     GET dictionary/ordering_methods
	 *
	 * @return array [List of ordering methods]
	 *
	 * @throws RestException 400
	 */
	public function getOrderingMethods($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->commande->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid, code, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_input_method as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of ordering origins.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Payment type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter with. Syntax example "(t.code:=:'OrderByWWW')"
	 *
	 * @url     GET dictionary/ordering_origins
	 *
	 * @return array [List of ordering reasons]
	 *
	 * @throws RestException 400
	 */
	public function getOrderingOrigins($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->commande->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid, code, label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_input_reason as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of payments types.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Payment type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter with. Syntax example "(t.code:=:'CHQ')"
	 *
	 * @url     GET dictionary/payment_types
	 *
	 * @return array [List of payment types]
	 *
	 * @throws RestException 400
	 */
	public function getPaymentTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->propal->lire && !DolibarrApiAccess::$user->rights->commande->lire && !DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT id, code, type, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_paiement as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_paiement').")";
		$sql .= " AND t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
			}
			  $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of states/provinces.
	 *
	 * The names of the states will be translated to the given language if
	 * the $lang parameter is provided. The value of $lang must be a language
	 * code supported by Dolibarr, for example 'en_US' or 'fr_FR'.
	 * The returned list is sorted by state ID.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $filter     To filter the countries by name
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array                List of countries
	 *
	 * @url     GET dictionary/states
	 *
	 * @throws RestException
	 */
	public function getListOfStates($sortfield = "code_departement", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = '', $sqlfilters = '')
	{
		$list = array();

		// Note: The filter is not applied in the SQL request because it must
		// be applied to the translated names, not to the names in database.
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_departements as t";
		$sql .= " WHERE 1 = 1";
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$obj = $this->db->fetch_object($result);
				$state = new Cstate($this->db);
				if ($state->fetch($obj->rowid) > 0) {
					if (empty($filter) || stripos($state->label, $filter) !== false) {
						$list[] = $this->_cleanObjectDatas($state);
					}
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of states');
		}

		return $list;
	}

	/**
	 * Get state by ID.
	 *
	 * @param int       $id        ID of state
	 * @return array 			   Array of cleaned object properties
	 *
	 * @url     GET dictionary/states/{id}
	 *
	 * @throws RestException
	 */
	public function getStateByID($id)
	{
		return $this->_fetchCstate($id, '');
	}

	/**
	 * Get state by Code.
	 *
	 * @param string    $code      Code of state
	 * @return array 			   Array of cleaned object properties
	 *
	 * @url     GET dictionary/states/byCode/{code}
	 *
	 * @throws RestException
	 */
	public function getStateByCode($code)
	{
		return $this->_fetchCstate('', $code);
	}

	/**
	 * Get the list of countries.
	 *
	 * The names of the countries will be translated to the given language if
	 * the $lang parameter is provided. The value of $lang must be a language
	 * code supported by Dolibarr, for example 'en_US' or 'fr_FR'.
	 * The returned list is sorted by country ID.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $filter     To filter the countries by name
	 * @param string    $lang       Code of the language the label of the countries must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array                List of countries
	 *
	 * @url     GET dictionary/countries
	 *
	 * @throws RestException
	 */
	public function getListOfCountries($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = '', $lang = '', $sqlfilters = '')
	{
		$list = array();

		// Note: The filter is not applied in the SQL request because it must
		// be applied to the translated names, not to the names in database.
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country as t";
		$sql .= " WHERE 1 = 1";
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$obj = $this->db->fetch_object($result);
				$country = new Ccountry($this->db);
				if ($country->fetch($obj->rowid) > 0) {
					// Translate the name of the country if needed
					// and then apply the filter if there is one.
					$this->translateLabel($country, $lang, 'Country');

					if (empty($filter) || stripos($country->label, $filter) !== false) {
						$list[] = $this->_cleanObjectDatas($country);
					}
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of countries');
		}

		return $list;
	}

	/**
	 * Get country by ID.
	 *
	 * @param int       $id        ID of country
	 * @param string    $lang      Code of the language the name of the
	 *                             country must be translated to
	 * @return array 			   Array of cleaned object properties
	 *
	 * @url     GET dictionary/countries/{id}
	 *
	 * @throws RestException
	 */
	public function getCountryByID($id, $lang = '')
	{
		return $this->_fetchCcountry($id, '', '', $lang);
	}

	/**
	 * Get country by Code.
	 *
	 * @param string    $code      Code of country (2 characters)
	 * @param string    $lang      Code of the language the name of the
	 *                             country must be translated to
	 * @return array 			   Array of cleaned object properties
	 *
	 * @url     GET dictionary/countries/byCode/{code}
	 *
	 * @throws RestException
	 */
	public function getCountryByCode($code, $lang = '')
	{
		return $this->_fetchCcountry('', $code, '', $lang);
	}

	/**
	 * Get country by Iso.
	 *
	 * @param string    $iso       ISO of country (3 characters)
	 * @param string    $lang      Code of the language the name of the
	 *                             country must be translated to
	 * @return array 			   Array of cleaned object properties
	 *
	 * @url     GET dictionary/countries/byISO/{iso}
	 *
	 * @throws RestException
	 */
	public function getCountryByISO($iso, $lang = '')
	{
		return $this->_fetchCcountry('', '', $iso, $lang);
	}

	/**
	 * Get state.
	 *
	 * @param int       $id        ID of state
	 * @param string    $code      Code of state
	 * @return array 			   Array of cleaned object properties
	 *
	 * @throws RestException
	 */
	private function _fetchCstate($id, $code = '')
	{
		$state = new Cstate($this->db);

		$result = $state->fetch($id, $code);
		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving state : '.$state->error);
		} elseif ($result == 0) {
			throw new RestException(404, 'State not found');
		}

		return $this->_cleanObjectDatas($state);
	}

	/**
	 * Get country.
	 *
	 * @param int       $id        ID of country
	 * @param string    $code      Code of country (2 characters)
	 * @param string    $iso       ISO of country (3 characters)
	 * @param string    $lang      Code of the language the name of the
	 *                             country must be translated to
	 * @return array 			   Array of cleaned object properties
	 *
	 * @throws RestException
	 */
	private function _fetchCcountry($id, $code = '', $iso = '', $lang = '')
	{
		$country = new Ccountry($this->db);

		$result = $country->fetch($id, $code, $iso);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving country : '.$country->error);
		} elseif ($result == 0) {
			throw new RestException(404, 'Country not found');
		}

		$this->translateLabel($country, $lang, 'Country');

		return $this->_cleanObjectDatas($country);
	}

	/**
	 * Get the list of delivery times.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Delivery times is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter with.
	 *
	 * @url     GET dictionary/availability
	 *
	 * @return array [List of availability]
	 *
	 * @throws RestException 400
	 */
	public function getAvailability($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->commande->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid, code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_availability as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
			}
				  $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param Object    $object    Object to clean
	 * @return Object 				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->error);
		unset($object->errors);

		return $object;
	}

	/**
	 * Translate the name of the object to the given language.
	 *
	 * @param object   $object    Object with label to translate
	 * @param string   $lang      Code of the language the name of the object must be translated to
	 * @param string   $prefix 	  Prefix for translation key
	 *
	 * @return void
	 */
	private function translateLabel($object, $lang, $prefix = 'Country')
	{
		if (!empty($lang)) {
			// Load the translations if this is a new language.
			if ($this->translations == null || $this->translations->getDefaultLang() !== $lang) {
				global $conf;
				$this->translations = new Translate('', $conf);
				$this->translations->setDefaultLang($lang);
				$this->translations->load('dict');
			}
			if ($object->code) {
				$key = $prefix.$object->code;

				$translation = $this->translations->trans($key);
				if ($translation != $key) {
					$object->label = html_entity_decode($translation);
				}
			}
		}
	}

	/**
	 * Get the list of shipment methods.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 *
	 * @return array List of shipment methods
	 *
	 * @url     GET dictionary/shipment_methods
	 *
	 * @throws RestException
	 */
	public function getListOfShipmentMethods($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();
		$sql = "SELECT t.rowid, t.code, t.libelle, t.description, t.tracking";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of shipment methods : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of events types.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $type       To filter on type of event
	 * @param string    $module     To filter on module events
	 * @param int       $active     Event's type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of events types
	 *
	 * @url     GET dictionary/event_types
	 *
	 * @throws RestException
	 */
	public function getListOfEventTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT id, code, type, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm as t";
		$sql .= " WHERE t.active = ".$active;
		if ($type) {
			$sql .= " AND t.type LIKE '%".$this->db->escape($type)."%'";
		}
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of events types : '.$this->db->lasterror());
		}

		return $list;
	}


	/**
	 * Get the list of Expense Report types.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $module     To filter on module
	 * @param int       $active     Event's type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of expense report types
	 *
	 * @url     GET dictionary/expensereport_types
	 *
	 * @throws RestException
	 */
	public function getListOfExpenseReportsTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT id, code, label, accountancy_code, active, module, position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_fees as t";
		$sql .= " WHERE t.active = ".$active;
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of expense report types : '.$this->db->lasterror());
		}

		return $list;
	}


	/**
	 * Get the list of contacts types.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $type       To filter on type of contact
	 * @param string    $module     To filter on module contacts
	 * @param int       $active     Contact's type is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array	  List of Contacts types
	 *
	 * @url     GET dictionary/contact_types
	 *
	 * @throws RestException
	 */
	public function getListOfContactTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, element as type, libelle as label, source, module, position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as t";
		$sql .= " WHERE t.active = ".$active;
		if ($type) {
			$sql .= " AND type LIKE '%".$this->db->escape($type)."%'";
		}
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of contacts types : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of civilities.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $module     To filter on module events
	 * @param int       $active     Civility is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array		List of civility types
	 *
	 * @url     GET dictionary/civilities
	 *
	 * @throws RestException
	 */
	public function getListOfCivilities($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_civility as t";
		$sql .= " WHERE t.active = ".$active;
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of civility : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of currencies.
	 *
	 * @param int       $multicurrency  Multicurrency rates (0: no multicurrency, 1: last rate, 2: all rates) {@min 0} {@max 2}
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of currencies
	 *
	 * @url     GET dictionary/currencies
	 *
	 * @throws RestException
	 */
	public function getListOfCurrencies($multicurrency = 0, $sortfield = "code_iso", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();
		$sql = "SELECT t.code_iso, t.label, t.unicode";
		if (!empty($multicurrency)) {
			$sql .= " , cr.date_sync, cr.rate ";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."c_currencies as t";
		if (!empty($multicurrency)) {
			$sql .= " JOIN ".MAIN_DB_PREFIX."multicurrency as m ON m.code=t.code_iso";
			$sql .= " JOIN ".MAIN_DB_PREFIX."multicurrency_rate as cr ON (m.rowid = cr.fk_multicurrency)";
		}
		$sql .= " WHERE t.active = ".$active;
		if (!empty($multicurrency)) {
			$sql .= " AND m.entity IN (".getEntity('multicurrency').")";
			if (!empty($multicurrency) && $multicurrency != 2) {
				$sql .= " AND cr.date_sync = (SELECT MAX(cr2.date_sync) FROM ".MAIN_DB_PREFIX."multicurrency_rate AS cr2 WHERE cr2.fk_multicurrency = m.rowid)";
			}
		}

		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of currency : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of extra fields.
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param string    $type       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
	 * @return array				List of extra fields
	 *
	 * @url     GET extrafields
	 *
	 * @throws RestException
	 */
	public function getListOfExtrafields($sortfield = "t.pos", $sortorder = 'ASC', $type = '', $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(401, 'Only an admin user can get list of extrafields');
		}

		if ($type == 'thirdparty') {
			$type = 'societe';
		}
		if ($type == 'contact') {
			$type = 'socpeople';
		}

		$sql = "SELECT t.rowid, t.name, t.label, t.type, t.size, t.elementtype, t.fieldunique, t.fieldrequired, t.param, t.pos, t.alwayseditable, t.perms, t.list, t.fielddefault, t.fieldcomputed";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields as t";
		$sql .= " WHERE t.entity IN (".getEntity('extrafields').")";
		if (!empty($type)) {
			$sql .= " AND t.elementtype = '".$this->db->escape($type)."'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql .= $this->db->order($sortfield, $sortorder);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ($tab = $this->db->fetch_object($resql)) {
					// New usage
					$list[$tab->elementtype][$tab->name]['type'] = $tab->type;
					$list[$tab->elementtype][$tab->name]['label'] = $tab->label;
					$list[$tab->elementtype][$tab->name]['size'] = $tab->size;
					$list[$tab->elementtype][$tab->name]['elementtype'] = $tab->elementtype;
					$list[$tab->elementtype][$tab->name]['default'] = $tab->fielddefault;
					$list[$tab->elementtype][$tab->name]['computed'] = $tab->fieldcomputed;
					$list[$tab->elementtype][$tab->name]['unique'] = $tab->fieldunique;
					$list[$tab->elementtype][$tab->name]['required'] = $tab->fieldrequired;
					$list[$tab->elementtype][$tab->name]['param'] = ($tab->param ? unserialize($tab->param) : '');
					$list[$tab->elementtype][$tab->name]['pos'] = $tab->pos;
					$list[$tab->elementtype][$tab->name]['alwayseditable'] = $tab->alwayseditable;
					$list[$tab->elementtype][$tab->name]['perms'] = $tab->perms;
					$list[$tab->elementtype][$tab->name]['list'] = $tab->list;
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of extra fields : '.$this->db->lasterror());
		}

		if (!count($list)) {
			throw new RestException(404, 'No extrafield found');
		}

		return $list;
	}


	/**
	 * Get the list of towns.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param string    $zipcode    To filter on zipcode
	 * @param string    $town       To filter on city name
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of towns
	 *
	 * @url     GET dictionary/towns
	 *
	 * @throws RestException
	 */
	public function getListOfTowns($sortfield = "zip,town", $sortorder = 'ASC', $limit = 100, $page = 0, $zipcode = '', $town = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid AS id, zip, town, fk_county, fk_pays AS fk_country";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ziptown as t";
		$sql .= " AND t.active = ".$active;
		if ($zipcode) {
			$sql .= " AND t.zip LIKE '%".$this->db->escape($zipcode)."%'";
		}
		if ($town) {
			$sql .= " AND t.town LIKE '%".$this->db->escape($town)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of towns : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of payments terms.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
	 *
	 * @url     GET dictionary/payment_terms
	 *
	 * @return array List of payment terms
	 *
	 * @throws RestException 400
	 */
	public function getPaymentTerms($sortfield = "sortorder", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->propal->lire && !DolibarrApiAccess::$user->rights->commande->lire && !DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid as id, code, sortorder, libelle as label, libelle_facture as descr, type_cdr, nbjour, decalage, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_payment_term').")";
		$sql .= " AND t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
				$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of shipping methods.
	 *
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Shipping methodsm is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
	 *
	 * @url     GET dictionary/shipping_methods
	 *
	 * @return array List of shipping methods
	 *
	 * @throws RestException 400
	 */
	public function getShippingModes($limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid as id, code, libelle as label, description, tracking, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_shipment_mode').")";
		$sql .= " AND t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(400, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
				$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		//$sql.= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(400, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of measuring units.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Measuring unit is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of measuring unit
	 *
	 * @url     GET dictionary/units
	 *
	 * @throws RestException
	 */
	public function getListOfMeasuringUnits($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		//TODO link with multicurrency module
		$sql = "SELECT t.rowid, t.code, t.label,t.short_label, t.active, t.scale, t.unit_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_units as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of measuring units: '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of social networks.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Social network is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of social networks
	 *
	 * @url     GET dictionary/socialnetworks
	 *
	 * @throws RestException
	 */
	public function getListOfsocialNetworks($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		global $conf;

		if (empty($conf->socialnetworks->enabled)) {
			throw new RestException(400, 'API not available: this dictionary is not enabled by setup');
		}

		$list = array();
		//TODO link with multicurrency module
		$sql = "SELECT t.rowid, t.entity, t.code, t.label, t.url, t.icon, t.active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_socialnetworks as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_socialnetworks').")";
		$sql .= " AND t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of social networks: '.$this->db->lasterror());
		}

		return $list;
	}

	 /**
	  * Get the list of tickets categories.
	  *
	  * @param string    $sortfield  Sort field
	  * @param string    $sortorder  Sort order
	  * @param int       $limit      Number of items per page
	  * @param int       $page       Page number (starting from zero)
	  * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	  * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	  * @return array				List of ticket categories
	  *
	  * @url     GET dictionary/ticket_categories
	  *
	  * @throws RestException
	  */
	public function getTicketsCategories($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of ticket categories : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of tickets severity.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of ticket severities
	 *
	 * @url     GET dictionary/ticket_severities
	 *
	 * @throws RestException
	 */
	public function getTicketsSeverities($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, color, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity as t";
		$sql .= " WHERE t.active = ".$active;
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of ticket severities : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of tickets types.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of ticket types
	 *
	 * @url     GET dictionary/ticket_types
	 *
	 * @throws RestException
	 */
	public function getTicketsTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_type as t";
		$sql .= " WHERE t.active = ".(int) $active;
		// if ($type) $sql .= " AND t.type LIKE '%".$this->db->escape($type)."%'";
		// if ($module)    $sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}


		$sql .= $this->db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of ticket types : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get properties of company
	 *
	 * @url	GET /company
	 *
	 * @return  array|mixed Mysoc object
	 *
	 * @throws RestException 403 Forbidden
	 */
	public function getCompany()
	{
		global $conf, $mysoc;

		if (!DolibarrApiAccess::$user->admin
			&& (empty($conf->global->API_LOGINS_ALLOWED_FOR_GET_COMPANY) || DolibarrApiAccess::$user->login != $conf->global->API_LOGINS_ALLOWED_FOR_GET_COMPANY)) {
				throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_GET_COMPANY');
		}

		unset($mysoc->skype);
		unset($mysoc->twitter);
		unset($mysoc->facebook);
		unset($mysoc->linkedin);

		unset($mysoc->pays);
		unset($mysoc->note);
		unset($mysoc->nom);

		unset($mysoc->lines);

		unset($mysoc->effectif);
		unset($mysoc->effectif_id);
		unset($mysoc->forme_juridique_code);
		unset($mysoc->forme_juridique);
		unset($mysoc->mode_reglement_supplier_id);
		unset($mysoc->cond_reglement_supplier_id);
		unset($mysoc->transport_mode_supplier_id);
		unset($mysoc->fk_prospectlevel);

		unset($mysoc->total_ht);
		unset($mysoc->total_tva);
		unset($mysoc->total_localtax1);
		unset($mysoc->total_localtax2);
		unset($mysoc->total_ttc);

		unset($mysoc->lastname);
		unset($mysoc->firstname);
		unset($mysoc->civility_id);

		unset($mysoc->client);
		unset($mysoc->prospect);
		unset($mysoc->fournisseur);
		unset($mysoc->contact_id);

		unset($mysoc->fk_incoterms);
		unset($mysoc->label_incoterms);
		unset($mysoc->location_incoterms);

		return $this->_cleanObjectDatas($mysoc);
	}


	/**
	 * Get value of a setup variables
	 *
	 * Note that conf variables that stores security key or password hashes can't be loaded with API.
	 *
	 * @param	string			$constantname	Name of conf variable to get
	 * @return  array|mixed 				Data without useless information
	 *
	 * @url     GET conf/{constantname}
	 *
	 * @throws RestException 403 Forbidden
	 * @throws RestException 404 Error Bad or unknown value for constantname
	 */
	public function getConf($constantname)
	{
		global $conf;

		if (!DolibarrApiAccess::$user->admin
			&& (empty($conf->global->API_LOGINS_ALLOWED_FOR_CONST_READ) || DolibarrApiAccess::$user->login != $conf->global->API_LOGINS_ALLOWED_FOR_CONST_READ)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_CONST_READ');
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $constantname) || !isset($conf->global->$constantname)) {
			throw new RestException(404, 'Error Bad or unknown value for constantname');
		}
		if (isASecretKey($constantname)) {
			throw new RestException(403, 'Forbidden. This parameter cant be read with APIs');
		}

		return $conf->global->$constantname;
	}

	/**
	 * Do a test of integrity for files and setup.
	 *
	 * @param string	$target			Can be 'local' or 'default' or Url of the signatures file to use for the test. Must be reachable by the tested Dolibarr.
	 * @return array					Result of file and setup integrity check
	 *
	 * @url     GET checkintegrity
	 *
	 * @throws RestException 403 Forbidden
	 * @throws RestException 404 Signature file not found
	 * @throws RestException 500 Technical error
	 * @throws RestException 503 Forbidden
	 */
	public function getCheckIntegrity($target)
	{
		global $langs, $conf;

		if (!DolibarrApiAccess::$user->admin
			&& (empty($conf->global->API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK) || DolibarrApiAccess::$user->login != $conf->global->API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGIN_ALLOWED_FOR_INTEGRITY_CHECK');
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$langs->load("admin");

		$outexpectedchecksum = '';
		$outcurrentchecksum = '';

		// Modified or missing files
		$file_list = array('missing' => array(), 'updated' => array());

		// Local file to compare to
		$xmlshortfile = GETPOST('xmlshortfile') ?GETPOST('xmlshortfile') : '/install/filelist-'.DOL_VERSION.'.xml';
		$xmlfile = DOL_DOCUMENT_ROOT.$xmlshortfile;
		// Remote file to compare to
		$xmlremote = ($target == 'default' ? '' : $target);
		if (empty($xmlremote) && !empty($conf->global->MAIN_FILECHECK_URL)) {
			$xmlremote = $conf->global->MAIN_FILECHECK_URL;
		}
		$param = 'MAIN_FILECHECK_URL_'.DOL_VERSION;
		if (empty($xmlremote) && !empty($conf->global->$param)) {
			$xmlremote = $conf->global->$param;
		}
		if (empty($xmlremote)) {
			$xmlremote = 'https://www.dolibarr.org/files/stable/signatures/filelist-'.DOL_VERSION.'.xml';
		}

		if ($target == 'local') {
			if (dol_is_file($xmlfile)) {
				$xml = simplexml_load_file($xmlfile);
			} else {
				throw new RestException(500, $langs->trans('XmlNotFound').': '.$xmlfile);
			}
		} else {
			$xmlarray = getURLContent($xmlremote);

			// Return array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
			if (!$xmlarray['curl_error_no'] && $xmlarray['http_code'] != '400' && $xmlarray['http_code'] != '404') {
				$xmlfile = $xmlarray['content'];
				//print "xmlfilestart".$xmlfile."endxmlfile";
				$xml = simplexml_load_string($xmlfile);
			} else {
				$errormsg = $langs->trans('XmlNotFound').': '.$xmlremote.' - '.$xmlarray['http_code'].' '.$xmlarray['curl_error_no'].' '.$xmlarray['curl_error_msg'];
				throw new RestException(500, $errormsg);
			}
		}

		if ($xml) {
			$checksumconcat = array();
			$file_list = array();
			$out = '';

			// Forced constants
			if (is_object($xml->dolibarr_constants[0])) {
				$out .= load_fiche_titre($langs->trans("ForcedConstants"));

				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder">';
				$out .= '<tr class="liste_titre">';
				$out .= '<td>#</td>';
				$out .= '<td>'.$langs->trans("Constant").'</td>';
				$out .= '<td class="center">'.$langs->trans("ExpectedValue").'</td>';
				$out .= '<td class="center">'.$langs->trans("Value").'</td>';
				$out .= '</tr>'."\n";

				$i = 0;
				foreach ($xml->dolibarr_constants[0]->constant as $constant) {    // $constant is a simpleXMLElement
					$constname = $constant['name'];
					$constvalue = (string) $constant;
					$constvalue = (empty($constvalue) ? '0' : $constvalue);
					// Value found
					$value = '';
					if ($constname && $conf->global->$constname != '') {
						$value = $conf->global->$constname;
					}
					$valueforchecksum = (empty($value) ? '0' : $value);

					$checksumconcat[] = $valueforchecksum;

					$i++;
					$out .= '<tr class="oddeven">';
					$out .= '<td>'.$i.'</td>'."\n";
					$out .= '<td>'.$constname.'</td>'."\n";
					$out .= '<td class="center">'.$constvalue.'</td>'."\n";
					$out .= '<td class="center">'.$valueforchecksum.'</td>'."\n";
					$out .= "</tr>\n";
				}

				if ($i == 0) {
					$out .= '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
				}
				$out .= '</table>';
				$out .= '</div>';

				$out .= '<br>';
			}

			// Scan htdocs
			if (is_object($xml->dolibarr_htdocs_dir[0])) {
				$includecustom = (empty($xml->dolibarr_htdocs_dir[0]['includecustom']) ? 0 : $xml->dolibarr_htdocs_dir[0]['includecustom']);

				// Defined qualified files (must be same than into generate_filelist_xml.php)
				$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
				$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|public\/test|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$'; // Exclude dirs
				$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

				// Fill file_list with files in signature, new files, modified files
				$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', DOL_DOCUMENT_ROOT, $checksumconcat); // Fill array $file_list
				// Complete with list of new files
				foreach ($scanfiles as $keyfile => $valfile) {
					$tmprelativefilename = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $valfile['fullname']);
					if (!in_array($tmprelativefilename, $file_list['insignature'])) {
						$md5newfile = @md5_file($valfile['fullname']); // Can fails if we don't have permission to open/read file
						$file_list['added'][] = array('filename'=>$tmprelativefilename, 'md5'=>$md5newfile);
					}
				}

				// Files missings
				$out .= load_fiche_titre($langs->trans("FilesMissing"));

				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder">';
				$out .= '<tr class="liste_titre">';
				$out .= '<td>#</td>';
				$out .= '<td>'.$langs->trans("Filename").'</td>';
				$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
				$out .= '</tr>'."\n";
				$tmpfilelist = dol_sort_array($file_list['missing'], 'filename');
				if (is_array($tmpfilelist) && count($tmpfilelist)) {
					$i = 0;
					foreach ($tmpfilelist as $file) {
						$i++;
						$out .= '<tr class="oddeven">';
						$out .= '<td>'.$i.'</td>'."\n";
						$out .= '<td>'.$file['filename'].'</td>'."\n";
						$out .= '<td class="center">'.$file['expectedmd5'].'</td>'."\n";
						$out .= "</tr>\n";
					}
				} else {
					$out .= '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
				}
				$out .= '</table>';
				$out .= '</div>';

				$out .= '<br>';

				// Files modified
				$out .= load_fiche_titre($langs->trans("FilesModified"));

				$totalsize = 0;
				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder">';
				$out .= '<tr class="liste_titre">';
				$out .= '<td>#</td>';
				$out .= '<td>'.$langs->trans("Filename").'</td>';
				$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
				$out .= '<td class="center">'.$langs->trans("CurrentChecksum").'</td>';
				$out .= '<td class="right">'.$langs->trans("Size").'</td>';
				$out .= '<td class="right">'.$langs->trans("DateModification").'</td>';
				$out .= '</tr>'."\n";
				$tmpfilelist2 = dol_sort_array($file_list['updated'], 'filename');
				if (is_array($tmpfilelist2) && count($tmpfilelist2)) {
					$i = 0;
					foreach ($tmpfilelist2 as $file) {
						$i++;
						$out .= '<tr class="oddeven">';
						$out .= '<td>'.$i.'</td>'."\n";
						$out .= '<td>'.$file['filename'].'</td>'."\n";
						$out .= '<td class="center">'.$file['expectedmd5'].'</td>'."\n";
						$out .= '<td class="center">'.$file['md5'].'</td>'."\n";
						$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
						$totalsize += $size;
						$out .= '<td class="right">'.dol_print_size($size).'</td>'."\n";
						$out .= '<td class="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']), 'dayhour').'</td>'."\n";
						$out .= "</tr>\n";
					}
					$out .= '<tr class="liste_total">';
					$out .= '<td></td>'."\n";
					$out .= '<td>'.$langs->trans("Total").'</td>'."\n";
					$out .= '<td align="center"></td>'."\n";
					$out .= '<td align="center"></td>'."\n";
					$out .= '<td class="right">'.dol_print_size($totalsize).'</td>'."\n";
					$out .= '<td class="right"></td>'."\n";
					$out .= "</tr>\n";
				} else {
					$out .= '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
				}
				$out .= '</table>';
				$out .= '</div>';

				$out .= '<br>';

				// Files added
				$out .= load_fiche_titre($langs->trans("FilesAdded"));

				$totalsize = 0;
				$out .= '<div class="div-table-responsive-no-min">';
				$out .= '<table class="noborder">';
				$out .= '<tr class="liste_titre">';
				$out .= '<td>#</td>';
				$out .= '<td>'.$langs->trans("Filename").'</td>';
				$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
				$out .= '<td class="center">'.$langs->trans("CurrentChecksum").'</td>';
				$out .= '<td class="right">'.$langs->trans("Size").'</td>';
				$out .= '<td class="right">'.$langs->trans("DateModification").'</td>';
				$out .= '</tr>'."\n";
				$tmpfilelist3 = dol_sort_array($file_list['added'], 'filename');
				if (is_array($tmpfilelist3) && count($tmpfilelist3)) {
					$i = 0;
					foreach ($tmpfilelist3 as $file) {
						$i++;
						$out .= '<tr class="oddeven">';
						$out .= '<td>'.$i.'</td>'."\n";
						$out .= '<td>'.$file['filename'].'</td>'."\n";
						$out .= '<td class="center">'.$file['expectedmd5'].'</td>'."\n";
						$out .= '<td class="center">'.$file['md5'].'</td>'."\n";
						$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
						$totalsize += $size;
						$out .= '<td class="right">'.dol_print_size($size).'</td>'."\n";
						$out .= '<td class="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']), 'dayhour').'</td>'."\n";
						$out .= "</tr>\n";
					}
					$out .= '<tr class="liste_total">';
					$out .= '<td></td>'."\n";
					$out .= '<td>'.$langs->trans("Total").'</td>'."\n";
					$out .= '<td align="center"></td>'."\n";
					$out .= '<td align="center"></td>'."\n";
					$out .= '<td class="right">'.dol_print_size($totalsize).'</td>'."\n";
					$out .= '<td class="right"></td>'."\n";
					$out .= "</tr>\n";
				} else {
					$out .= '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
				}
				$out .= '</table>';
				$out .= '</div>';


				// Show warning
				if (empty($tmpfilelist) && empty($tmpfilelist2) && empty($tmpfilelist3)) {
					//setEventMessages($langs->trans("FileIntegrityIsStrictlyConformedWithReference"), null, 'mesgs');
				} else {
					//setEventMessages($langs->trans("FileIntegritySomeFilesWereRemovedOrModified"), null, 'warnings');
				}
			} else {
				throw new RestException(500, 'Error: Failed to found dolibarr_htdocs_dir into XML file '.$xmlfile);
			}


			// Scan scripts
			asort($checksumconcat); // Sort list of checksum
			$checksumget = md5(join(',', $checksumconcat));
			$checksumtoget = trim((string) $xml->dolibarr_htdocs_dir_checksum);

			$outexpectedchecksum = ($checksumtoget ? $checksumtoget : $langs->trans("Unknown"));
			if ($checksumget == $checksumtoget) {
				if (count($file_list['added'])) {
					$resultcode = 'warning';
					$resultcomment = 'FileIntegrityIsOkButFilesWereAdded';
					//$outcurrentchecksum =  $checksumget.' - <span class="'.$resultcode.'">'.$langs->trans("FileIntegrityIsOkButFilesWereAdded").'</span>';
					$outcurrentchecksum = $checksumget;
				} else {
					$resultcode = 'ok';
					$resultcomment = 'Success';
					//$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
					$outcurrentchecksum = $checksumget;
				}
			} else {
				$resultcode = 'error';
				$resultcomment = 'Error';
				//$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
				$outcurrentchecksum = $checksumget;
			}
		} else {
			throw new RestException(404, 'No signature file known');
		}

		return array('resultcode'=>$resultcode, 'resultcomment'=>$resultcomment, 'expectedchecksum'=> $outexpectedchecksum, 'currentchecksum'=> $outcurrentchecksum, 'out'=>$out);
	}


	/**
	 * Get list of enabled modules
	 *
	 * @url	GET /modules
	 *
	 * @return  array|mixed Data without useless information
	 *
	 * @throws RestException 403 Forbidden
	 */
	public function getModules()
	{
		global $conf;

		if (!DolibarrApiAccess::$user->admin
			&& (empty($conf->global->API_LOGIN_ALLOWED_FOR_GET_MODULES) || DolibarrApiAccess::$user->login != $conf->global->API_LOGIN_ALLOWED_FOR_GET_MODULES)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGIN_ALLOWED_FOR_GET_MODULES');
		}

		sort($conf->modules);

		return $this->_cleanObjectDatas($conf->modules);
	}
}
