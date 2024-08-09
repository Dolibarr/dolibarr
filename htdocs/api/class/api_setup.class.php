<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin	        <regis.houssin@inodbox.com>
 * Copyright (C) 2017	Neil Orley	            <neil.orley@oeris.fr>
 * Copyright (C) 2018-2021   Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018-2022   Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2024        Jon Bendtsen            <jon.bendtsen.github@jonb.dk>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cstate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cregion.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';

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
	 * Get the list of Action Triggers.
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param string    $elementtype       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
	 * @param string    $lang       Code of the language the label of the type must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
	 * @return array				List of extra fields
	 *
	 * @url     GET actiontriggers
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of action triggers
	 */
	public function getListOfActionTriggers($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $elementtype = '', $lang='', $sqlfilters = '')
	{
		$list = array();

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$sql = "SELECT t.rowid as id, t.elementtype, t.code, t.contexts, t.label, t.description, t.rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as t";
		if (!empty($elementtype)) {
			$sql .= " WHERE t.elementtype = '".$this->db->escape($elementtype)."'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$type = $this->db->fetch_object($result);
				$this->translateLabel($type, $lang, 'Notify_', array('other'));
				$list[] = $type;
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of action triggers : '.$this->db->lasterror());
		}

		return $list;
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error retrieving list of ordering methods
	 */
	public function getOrderingMethods($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid, code, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_input_method as t";
		$sql .= " WHERE t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, $this->db->lasterror());
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
	 * @return array [List of ordering reasons]
	 *
	 * @url     GET dictionary/ordering_origins
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error retrieving list of ordering origins
	 */
	public function getOrderingOrigins($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid, code, label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_input_reason as t";
		$sql .= " WHERE t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, $this->db->lasterror());
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error retrieving list of payment types
	 */
	public function getPaymentTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire') && !DolibarrApiAccess::$user->hasRight('commande', 'lire') && !DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT id, code, type, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_paiement as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_paiement').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, $this->db->lasterror());
		}

		return $list;
	}
	/**
	 * Get the list of regions.
	 *
	 * The returned list is sorted by region ID.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $country    To filter on country
	 * @param string    $filter     To filter the regions by name
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array                List of regions
	 *
	 * @url     GET dictionary/regions
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error retrieving list of regions
	 */
	public function getListOfRegions($sortfield = "code_region", $sortorder = 'ASC', $limit = 100, $page = 0, $country = 0, $filter = '', $sqlfilters = '')
	{
		$list = array();

		// Note: The filter is not applied in the SQL request because it must
		// be applied to the translated names, not to the names in database.
		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."c_regions as t";
		$sql .= " WHERE 1 = 1";
		if ($country) {
			$sql .= " AND t.fk_pays = ".((int) $country);
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
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
				$region = new Cregion($this->db);
				if ($region->fetch($obj->rowid) > 0) {
					if (empty($filter) || stripos($region->name, $filter) !== false) {
						$list[] = $this->_cleanObjectDatas($region);
					}
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of regions');
		}

		return $list;
	}

	/**
	 * Get region by ID.
	 *
	 * @param 	int       $id       ID of region
	 * @return 	Object 				Object with cleaned properties
	 *
	 * @url     GET dictionary/regions/{id}
	 *
	 * @throws	RestException	404		Region not found
	 * @throws	RestException	503		Error retrieving region
	 */
	public function getRegionByID($id)
	{
		return $this->_fetchCregion($id, '');
	}

	/**
	 * Get region by Code.
	 *
	 * @param 	string    $code     Code of region
	 * @return 	Object 				Object with cleaned properties
	 *
	 * @url     GET dictionary/regions/byCode/{code}
	 *
	 * @throws	RestException	404		Region not found
	 * @throws	RestException	503		Error when retrieving region
	 */
	public function getRegionByCode($code)
	{
		return $this->_fetchCregion('', $code);
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
	 * @param int    	$country    To filter on country
	 * @param string    $filter     To filter the states by name
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array                List of states
	 *
	 * @url     GET dictionary/states
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error retrieving list of states
	 */
	public function getListOfStates($sortfield = "code_departement", $sortorder = 'ASC', $limit = 100, $page = 0, $country = 0, $filter = '', $sqlfilters = '')
	{
		$list = array();

		// Note: The filter is not applied in the SQL request because it must
		// be applied to the translated names, not to the names in database.
		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."c_departements as t";
		if ($country) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as d ON t.fk_region = d.code_region";
		}
		$sql .= " WHERE 1 = 1";
		if ($country) {
			$sql .= " AND d.fk_pays = ".((int) $country);
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @param 	int       $id        	ID of state
	 * @return 	Object 					Object with cleaned properties
	 *
	 * @url     GET dictionary/states/{id}
	 *
	 * @throws	RestException	404		State not found
	 * @throws	RestException	503		Error retrieving state
	 */
	public function getStateByID($id)
	{
		return $this->_fetchCstate($id, '');
	}

	/**
	 * Get state by Code.
	 *
	 * @param 	string    $code      	Code of state
	 * @return 	Object 					Object with cleaned properties
	 *
	 * @url     GET dictionary/states/byCode/{code}
	 *
	 * @throws	RestException	404		State not found
	 * @throws	RestException	503		Error retrieving state
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error retrieving list of countries
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
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @param 	int       $id        	ID of country
	 * @param 	string    $lang      	Code of the language the name of the country must be translated to
	 * @return 	Object 					Object with cleaned properties
	 *
	 * @url     GET dictionary/countries/{id}
	 *
	 * @throws	RestException	404		Country not found
	 * @throws	RestException	503		Error retrieving country
	 */
	public function getCountryByID($id, $lang = '')
	{
		return $this->_fetchCcountry($id, '', '', $lang);
	}

	/**
	 * Get country by Code.
	 *
	 * @param 	string    $code      	Code of country (2 characters)
	 * @param 	string    $lang      	Code of the language the name of the country must be translated to
	 * @return 	Object 					Object with cleaned properties
	 *
	 * @url     GET dictionary/countries/byCode/{code}
	 *
	 * @throws	RestException	404		Country not found
	 * @throws	RestException	503		Error retrieving country
	 */
	public function getCountryByCode($code, $lang = '')
	{
		return $this->_fetchCcountry('', $code, '', $lang);
	}

	/**
	 * Get country by Iso.
	 *
	 * @param 	string    $iso       	ISO of country (3 characters)
	 * @param 	string    $lang     	Code of the language the name of the country must be translated to
	 * @return 	Object 					Object with cleaned properties
	 *
	 * @url     GET dictionary/countries/byISO/{iso}
	 *
	 * @throws	RestException	404		Country not found
	 * @throws	RestException	503		Error retrieving country
	 */
	public function getCountryByISO($iso, $lang = '')
	{
		return $this->_fetchCcountry('', '', $iso, $lang);
	}

	/**
	 * Get region.
	 *
	 * @param 	int       $id       ID of region
	 * @param 	string    $code     Code of region
	 * @return 	Object 				Object with cleaned properties
	 *
	 * @throws RestException
	 */
	private function _fetchCregion($id, $code = '')
	{
		$region = new Cregion($this->db);

		$result = $region->fetch($id, $code);
		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving region : '.$region->error);
		} elseif ($result == 0) {
			throw new RestException(404, 'Region not found');
		}

		return $this->_cleanObjectDatas($region);
	}

	/**
	 * Get state.
	 *
	 * @param 	int       $id        	ID of state
	 * @param 	string    $code      	Code of state
	 * @return 	Object 					Object with cleaned properties
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
	 * @param 	int       $id        	ID of country
	 * @param 	string    $code      	Code of country (2 characters)
	 * @param 	string    $iso       	ISO of country (3 characters)
	 * @param 	string    $lang      	Code of the language the name of the country must be translated to
	 * @return 	Object 					Object with cleaned properties
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
	 * @param string    $sortfield  	Sort field
	 * @param string    $sortorder  	Sort order
	 * @param int       $limit      	Number of items per page
	 * @param int       $page       	Page number {@min 0}
	 * @param int       $active     	Delivery times is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters 	SQL criteria to filter with.
	 *
	 * @url     GET dictionary/availability
	 *
	 * @return array [List of availability]
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error when retrieving list of availabilities
	 */
	public function getAvailability($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid, code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_availability as t";
		$sql .= " WHERE t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, $this->db->lasterror());
		}

		return $list;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param 	Object    $object    	Object to clean
	 * @return 	Object 					Object with cleaned properties
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
	 * @param array    $dict      Array of dictionary for translation
	 * @return void
	 */
	private function translateLabel($object, $lang, $prefix = 'Country', $dict = array('dict'))
	{
		if (!empty($lang)) {
			// Load the translations if this is a new language.
			if ($this->translations == null || $this->translations->getDefaultLang() !== $lang) {
				global $conf;
				$this->translations = new Translate('', $conf);
				$this->translations->setDefaultLang($lang);
				$this->translations->loadLangs($dict);
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of events types
	 */
	public function getListOfEventTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT id, code, type, libelle as label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($type) {
			$sql .= " AND t.type LIKE '%".$this->db->escape($type)."%'";
		}
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of expense report types
	 */
	public function getListOfExpenseReportsTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $module = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT id, code, label, accountancy_code, active, module, position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_fees as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @param string    $lang       Code of the language the label of the civility must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array	  List of Contacts types
	 *
	 * @url     GET dictionary/contact_types
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of contacts types
	 */
	public function getListOfContactTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, element as type, libelle as label, source, module, position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($type) {
			$sql .= " AND type LIKE '%".$this->db->escape($type)."%'";
		}
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$contact_type = $this->db->fetch_object($result);
				$this->translateLabel($contact_type, $lang, 'TypeContact_'.$contact_type->type.'_'.$contact_type->source.'_', array("eventorganization", "resource", "projects", "contracts", "bills", "orders", "agenda", "propal", "stocks", "supplier_proposal", "interventions", "sendings", "ticket"));
				$list[] = $contact_type;
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
	 * @param string    $lang       Code of the language the label of the civility must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array		List of civility types
	 *
	 * @url     GET dictionary/civilities
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of civilities
	 */
	public function getListOfCivilities($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $module = '', $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, label, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_civility as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($module) {
			$sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$civility = $this->db->fetch_object($result);
				$this->translateLabel($civility, $lang, 'Civility', array('dict'));
				$list[] = $civility;
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of currencies
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
		$sql .= " WHERE t.active = ".((int) $active);
		if (!empty($multicurrency)) {
			$sql .= " AND m.entity IN (".getEntity('multicurrency').")";
			if (!empty($multicurrency) && $multicurrency != 2) {
				$sql .= " AND cr.date_sync = (SELECT MAX(cr2.date_sync) FROM ".MAIN_DB_PREFIX."multicurrency_rate AS cr2 WHERE cr2.fk_multicurrency = m.rowid)";
			}
		}

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @param string    $elementtype       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
	 * @return array				List of extra fields
	 *
	 * @url     GET extrafields
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of extra fields
	 */
	public function getListOfExtrafields($sortfield = "t.pos", $sortorder = 'ASC', $elementtype = '', $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(403, 'Only an admin user can get list of extrafields');
		}

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$sql = "SELECT t.rowid as id, t.name, t.entity, t.elementtype, t.label, t.type, t.size, t.fieldcomputed, t.fielddefault,";
		$sql .= " t.fieldunique, t.fieldrequired, t.perms, t.enabled, t.pos, t.alwayseditable, t.param, t.list, t.printable,";
		$sql .= " t.totalizable, t.langs, t.help, t.css, t.cssview, t.fk_user_author, t.fk_user_modif, t.datec, t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields as t";
		$sql .= " WHERE t.entity IN (".getEntity('extrafields').")";
		if (!empty($elementtype)) {
			$sql .= " AND t.elementtype = '".$this->db->escape($elementtype)."'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ($tab = $this->db->fetch_object($resql)) {
					// New usage
					$list[$tab->elementtype][$tab->name]['id'] = $tab->id;
					$list[$tab->elementtype][$tab->name]['type'] = $tab->type;
					$list[$tab->elementtype][$tab->name]['label'] = $tab->label;
					$list[$tab->elementtype][$tab->name]['size'] = $tab->size;
					$list[$tab->elementtype][$tab->name]['elementtype'] = $tab->elementtype;
					$list[$tab->elementtype][$tab->name]['default'] = $tab->fielddefault;
					$list[$tab->elementtype][$tab->name]['computed'] = $tab->fieldcomputed;
					$list[$tab->elementtype][$tab->name]['unique'] = $tab->fieldunique;
					$list[$tab->elementtype][$tab->name]['required'] = $tab->fieldrequired;
					$list[$tab->elementtype][$tab->name]['param'] = ($tab->param ? jsonOrUnserialize($tab->param) : '');	// This may be a string encoded with serialise() or json_encode()
					$list[$tab->elementtype][$tab->name]['pos'] = $tab->pos;
					$list[$tab->elementtype][$tab->name]['alwayseditable'] = $tab->alwayseditable;
					$list[$tab->elementtype][$tab->name]['perms'] = $tab->perms;
					$list[$tab->elementtype][$tab->name]['list'] = $tab->list;
					$list[$tab->elementtype][$tab->name]['printable'] = $tab->printable;
					$list[$tab->elementtype][$tab->name]['totalizable'] = $tab->totalizable;
					$list[$tab->elementtype][$tab->name]['langs'] = $tab->langs;
					$list[$tab->elementtype][$tab->name]['help'] = $tab->help;
					$list[$tab->elementtype][$tab->name]['css'] = $tab->css;
					$list[$tab->elementtype][$tab->name]['cssview'] = $tab->cssview;
					$list[$tab->elementtype][$tab->name]['csslist'] = $tab->csslist;
					$list[$tab->elementtype][$tab->name]['fk_user_author'] = $tab->fk_user_author;
					$list[$tab->elementtype][$tab->name]['fk_user_modif'] = $tab->fk_user_modif;
					$list[$tab->elementtype][$tab->name]['datec'] = $tab->datec;
					$list[$tab->elementtype][$tab->name]['tms'] = $tab->tms;
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of extra fields : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Delete extrafield
	 *
	 * @param   string     $attrname         extrafield attrname
	 * @param   string     $elementtype      extrafield elementtype
	 * @return  array
	 *
	 * @url     DELETE extrafields/{elementtype}/{attrname}
	 *
	 */
	public function deleteExtrafieldsFromNames($attrname, $elementtype)
	{
		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(403, 'Only an admin user can delete an extrafield by attrname and elementtype');
		}

		$extrafields = new ExtraFields($this->db);

		$result = $extrafields->fetch_name_optionals_label($elementtype, false, $attrname);
		if (!$result) {
			throw new RestException(404, 'Extrafield not found from attrname and elementtype');
		}

		if (!$extrafields->delete($attrname, $elementtype)) {
			throw new RestException(500, 'Error when delete extrafield : '.$extrafields->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Extrafield deleted from attrname and elementtype'
			)
		);
	}



	/** get Extrafield object
	 *
	 * @param	string	$attrname		extrafield attrname
	 * @param	string	$elementtype	extrafield elementtype
	 * @return	array					List of extra fields
	 *
	 * @url     GET		extrafields/{elementtype}/{attrname}
	 *
	 * @suppress PhanPluginUnknownArrayMethodParamType  Luracast limitation
	 *
	 */
	public function getExtrafields($attrname, $elementtype)
	{
		$answer = array();

		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(403, 'Only an admin user can get list of extrafields');
		}

		if ($elementtype == 'thirdparty') {
			$elementtype = 'societe';
		}
		if ($elementtype == 'contact') {
			$elementtype = 'socpeople';
		}

		$sql = "SELECT t.rowid as id, t.name, t.entity, t.elementtype, t.label, t.type, t.size, t.fieldcomputed, t.fielddefault,";
		$sql .= " t.fieldunique, t.fieldrequired, t.perms, t.enabled, t.pos, t.alwayseditable, t.param, t.list, t.printable,";
		$sql .= " t.totalizable, t.langs, t.help, t.css, t.cssview, t.fk_user_author, t.fk_user_modif, t.datec, t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields as t";
		$sql .= " WHERE t.entity IN (".getEntity('extrafields').")";
		$sql .= " AND t.elementtype = '".$this->db->escape($elementtype)."'";
		$sql .= " AND t.name = '".$this->db->escape($attrname)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ($tab = $this->db->fetch_object($resql)) {
					// New usage
					$answer[$tab->elementtype][$tab->name]['id'] = $tab->id;
					$answer[$tab->elementtype][$tab->name]['type'] = $tab->type;
					$answer[$tab->elementtype][$tab->name]['label'] = $tab->label;
					$answer[$tab->elementtype][$tab->name]['size'] = $tab->size;
					$answer[$tab->elementtype][$tab->name]['elementtype'] = $tab->elementtype;
					$answer[$tab->elementtype][$tab->name]['default'] = $tab->fielddefault;
					$answer[$tab->elementtype][$tab->name]['computed'] = $tab->fieldcomputed;
					$answer[$tab->elementtype][$tab->name]['unique'] = $tab->fieldunique;
					$answer[$tab->elementtype][$tab->name]['required'] = $tab->fieldrequired;
					$answer[$tab->elementtype][$tab->name]['param'] = ($tab->param ? jsonOrUnserialize($tab->param) : '');	// This may be a string encoded with serialise() or json_encode()
					$answer[$tab->elementtype][$tab->name]['pos'] = $tab->pos;
					$answer[$tab->elementtype][$tab->name]['alwayseditable'] = $tab->alwayseditable;
					$answer[$tab->elementtype][$tab->name]['perms'] = $tab->perms;
					$answer[$tab->elementtype][$tab->name]['list'] = $tab->list;
					$answer[$tab->elementtype][$tab->name]['printable'] = $tab->printable;
					$answer[$tab->elementtype][$tab->name]['totalizable'] = $tab->totalizable;
					$answer[$tab->elementtype][$tab->name]['langs'] = $tab->langs;
					$answer[$tab->elementtype][$tab->name]['help'] = $tab->help;
					$answer[$tab->elementtype][$tab->name]['css'] = $tab->css;
					$answer[$tab->elementtype][$tab->name]['cssview'] = $tab->cssview;
					$answer[$tab->elementtype][$tab->name]['csslist'] = $tab->csslist;
					$answer[$tab->elementtype][$tab->name]['fk_user_author'] = $tab->fk_user_author;
					$answer[$tab->elementtype][$tab->name]['fk_user_modif'] = $tab->fk_user_modif;
					$answer[$tab->elementtype][$tab->name]['datec'] = $tab->datec;
					$answer[$tab->elementtype][$tab->name]['tms'] = $tab->tms;
				}
			} else {
				throw new RestException(404, 'Extrafield not found from attrname and elementtype');
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of extra fields : '.$this->db->lasterror());
		}

		return $answer;
	}

	/**
	 * Create Extrafield object
	 *
	 * @param	string	$attrname		extrafield attrname
	 * @param	string	$elementtype	extrafield elementtype
	 * @param	array	$request_data	Request datas
	 * @return	int						ID of extrafield
	 *
	 * @url     POST	extrafields/{elementtype}/{attrname}
	 *
	 * @suppress PhanPluginUnknownArrayMethodParamType  Luracast limitation
	 *
	 */
	public function postExtrafields($attrname, $elementtype, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(403, 'Only an admin user can create an extrafield');
		}

		$extrafields = new ExtraFields($this->db);

		$result = $extrafields->fetch_name_optionals_label($elementtype, false, $attrname);
		if ($result) {
			throw new RestException(409, 'Duplicate extrafield already found from attrname and elementtype');
		}

		// Check mandatory fields is not working despise being a modified copy from api_thirdparties.class.php
		// $result = $this->_validateExtrafields($request_data, $extrafields);

		foreach ($request_data as $field => $value) {
			$extrafields->$field = $this->_checkValForAPI($field, $value, $extrafields);
		}

		$entity = DolibarrApiAccess::$user->entity;
		if (empty($entity)) {
			$entity = 1;
		}

		// built in validation
		$enabled = 1; // hardcoded because it seems to always be 1 in every row in the database

		if ($request_data['label']) {
			$label = $request_data['label'];
		} else {
			throw new RestException(400, "label field absent in json at root level");
		}

		$alwayseditable = $request_data['alwayseditable'];
		$default_value = $request_data['default_value'];
		$totalizable = $request_data['totalizable'];
		$printable = $request_data['printable'];
		$required = $request_data['required'];
		$langfile = $request_data['langfile'];
		$computed = $request_data['computed'];
		$unique = $request_data['unique'];
		$param = $request_data['param'];
		$perms = $request_data['perms'];
		$size = $request_data['size'];
		$type = $request_data['type'];
		$list = $request_data['list'];
		$help = $request_data['help'];
		$pos = $request_data['pos'];
		$moreparams = array();

		if (0 > $extrafields->addExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $default_value, $param, $alwayseditable, $perms, $list, $help, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams)) {
			throw new RestException(500, 'Error creating extrafield', array_merge(array($extrafields->errno), $extrafields->errors));
		}

		$sql = "SELECT t.rowid as id";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields as t";
		$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'";
		$sql .= " AND name = '".$this->db->escape($attrname)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$tab = $this->db->fetch_object($resql);
				$id = (int) $tab->id;
			} else {
				$id = (int) -1;
			}
		} else {
			$id = (int) -2;
		}

		return $id;
	}

	/**

	 * Update Extrafield object
	 *
	 * @param	string	$attrname		extrafield attrname
	 * @param	string	$elementtype	extrafield elementtype
	 * @param	array	$request_data	Request datas
	 * @return	int						ID of extrafield
	 *
	 * @url     PUT		extrafields/{elementtype}/{attrname}
	 *
	 * @suppress PhanPluginUnknownArrayMethodParamType  Luracast limitation
	 *
	 */
	public function updateExtrafields($attrname, $elementtype, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->admin) {
			throw new RestException(403, 'Only an admin user can create an extrafield');
		}

		$extrafields = new ExtraFields($this->db);

		$result = $extrafields->fetch_name_optionals_label($elementtype, false, $attrname);
		if (!$result) {
			throw new RestException(404, 'Extrafield not found from attrname and elementtype');
		}

		foreach ($request_data as $field => $value) {
			$extrafields->$field = $this->_checkValForAPI($field, $value, $extrafields);
		}

		$entity = DolibarrApiAccess::$user->entity;
		if (empty($entity)) {
			$entity = 1;
		}

		// built in validation
		$enabled = 1; // hardcoded because it seems to always be 1 in every row in the database
		if ($request_data['label']) {
			$label = $request_data['label'];
		} else {
			throw new RestException(400, "label field absent in json at root level");
		}

		$alwayseditable = $request_data['alwayseditable'];
		$default_value = $request_data['default_value'];
		$totalizable = $request_data['totalizable'];
		$printable = $request_data['printable'];
		$required = $request_data['required'];
		$langfile = $request_data['langfile'];
		$computed = $request_data['computed'];
		$unique = $request_data['unique'];
		$param = $request_data['param'];
		$perms = $request_data['perms'];
		$size = $request_data['size'];
		$type = $request_data['type'];
		$list = $request_data['list'];
		$help = $request_data['help'];
		$pos = $request_data['pos'];
		$moreparams = array();

		dol_syslog(get_class($this).'::updateExtraField', LOG_DEBUG);
		if (0 > $extrafields->updateExtraField($attrname, $label, $type, $pos, $size, $elementtype, $unique, $required, $default_value, $param, $alwayseditable, $perms, $list, $help, $computed, $entity, $langfile, $enabled, $totalizable, $printable, $moreparams)) {
			throw new RestException(500, 'Error updating extrafield', array_merge(array($extrafields->errno), $extrafields->errors));
		}

		$sql = "SELECT t.rowid as id";
		$sql .= " FROM ".MAIN_DB_PREFIX."extrafields as t";
		$sql .= " WHERE elementtype = '".$this->db->escape($elementtype)."'";
		$sql .= " AND name = '".$this->db->escape($attrname)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$tab = $this->db->fetch_object($resql);
				$id = (int) $tab->id;
			} else {
				$id = (int) -1;
			}
		} else {
			$id = (int) -2;
		}

		return $id;
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
	 * @param int       $active     Town is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of towns
	 *
	 * @url     GET dictionary/towns
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of towns
	 */
	public function getListOfTowns($sortfield = "zip,town", $sortorder = 'ASC', $limit = 100, $page = 0, $zipcode = '', $town = '', $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid AS id, zip, town, fk_county, fk_pays AS fk_country";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ziptown as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($zipcode) {
			$sql .= " AND t.zip LIKE '%".$this->db->escape($zipcode)."%'";
		}
		if ($town) {
			$sql .= " AND t.town LIKE '%".$this->db->escape($town)."%'";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	503		Error when retrieving list of payments terms
	 */
	public function getPaymentTerms($sortfield = "sortorder", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire') && !DolibarrApiAccess::$user->hasRight('commande', 'lire') && !DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid as id, code, sortorder, libelle as label, libelle_facture as descr, type_cdr, nbjour, decalage, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_payment_term').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of shipping methods.
	 *
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number {@min 0}
	 * @param int       $active     Shipping methodsm is active or not {@min 0} {@max 1}
	 * @param string    $lang       Code of the language the label of the method must be translated to
	 * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
	 *
	 * @url     GET dictionary/shipping_methods
	 *
	 * @return array List of shipping methods
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of shipping modes
	 */
	public function getShippingModes($limit = 100, $page = 0, $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid as id, code, libelle as label, description, tracking, module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_shipment_mode as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_shipment_mode').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$method = $this->db->fetch_object($result);
				$this->translateLabel($method, $lang, '', array('dict'));
				$list[] = $method;
			}
		} else {
			throw new RestException(503, $this->db->lasterror());
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of measuring units
	 */
	public function getListOfMeasuringUnits($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT t.rowid, t.code, t.label,t.short_label, t.active, t.scale, t.unit_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_units as t";
		$sql .= " WHERE t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * Get the list of legal form of business.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int   	$country    To filter on country
	 * @param int       $active     Lega form is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of legal form
	 *
	 * @url     GET dictionary/legal_form
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of legal form
	 */
	public function getListOfLegalForm($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $country = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT t.rowid, t.code, t.fk_pays, t.libelle, t.isvatexempted, t.active, t.module, t.position";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_forme_juridique as t";
		$sql .= " WHERE t.active = ".((int) $active);
		if ($country) {
			$sql .= " AND t.fk_pays = ".((int) $country);
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, 'Error when retrieving list of legal form: '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of staff.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Staff is active or not {@min 0} {@max 1}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of staff
	 *
	 * @url     GET dictionary/staff
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of staff
	 */
	public function getListOfStaff($sortfield = "id", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT t.id, t.code, t.libelle, t.active, t.module";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_effectif as t";
		$sql .= " WHERE t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
			throw new RestException(503, 'Error when retrieving list of staff: '.$this->db->lasterror());
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
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of social networks
	 */
	public function getListOfsocialNetworks($sortfield = "rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
	{
		global $conf;

		if (!isModEnabled('socialnetworks')) {
			throw new RestException(400, 'API not available: this dictionary is not enabled by setup');
		}

		$list = array();
		//TODO link with multicurrency module
		$sql = "SELECT t.rowid, t.entity, t.code, t.label, t.url, t.icon, t.active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_socialnetworks as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_socialnetworks').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
	 * @param string    $lang       Code of the language the label of the category must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of ticket categories
	 *
	 * @url     GET dictionary/ticket_categories
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of tickets categories
	 */
	public function getTicketsCategories($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_ticket_category').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$category = $this->db->fetch_object($result);
				$this->translateLabel($category, $lang, 'TicketCategoryShort', array('ticket'));
				$list[] = $category;
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
	 * @param string    $lang       Code of the language the label of the severity must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of ticket severities
	 *
	 * @url     GET dictionary/ticket_severities
	 *
	 * @throws	RestException	400		Bad value for sqlfilters
	 * @throws	RestException	503		Error when retrieving list of tickets severities
	 */
	public function getTicketsSeverities($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, color, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_ticket_severity').")";
		$sql .= " AND t.active = ".((int) $active);
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$severity = $this->db->fetch_object($result);
				$this->translateLabel($severity, $lang, 'TicketSeverityShort', array('ticket'));
				$list[] = $severity;
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
	 * @param string    $lang       Code of the language the label of the type must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of ticket types
	 *
	 * @url     GET dictionary/ticket_types
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 503 Error when retrieving list of tickets types
	 */
	public function getTicketsTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, pos,  label, use_default, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_type as t";
		$sql .= " WHERE t.entity IN (".getEntity('c_ticket_type').")";
		$sql .= " AND t.active = ".((int) $active);

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
				$type = $this->db->fetch_object($result);
				$this->translateLabel($type, $lang, 'TicketTypeShort', array('ticket'));
				$list[] = $type;
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of ticket types : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get the list of incoterms.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Number of items per page
	 * @param int       $page       Page number (starting from zero)
	 * @param int       $active     Payment term is active or not {@min 0} {@max 1}
	 * @param string    $lang       Code of the language the label of the type must be translated to
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
	 * @return array				List of incoterm types
	 *
	 * @url     GET dictionary/incoterms
	 *
	 * @throws RestException 503 Error when retrieving list of incoterms types
	 */
	public function getListOfIncoterms($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $lang = '', $sqlfilters = '')
	{
		$list = array();

		$sql = "SELECT rowid, code, active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_incoterms as t";
		$sql .= " WHERE 1=1";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
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
				$type = $this->db->fetch_object($result);
				$list[] = $type;
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of incoterm types : '.$this->db->lasterror());
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
			&& (!getDolGlobalString('API_LOGINS_ALLOWED_FOR_GET_COMPANY') || DolibarrApiAccess::$user->login != $conf->global->API_LOGINS_ALLOWED_FOR_GET_COMPANY)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_GET_COMPANY');
		}

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
	 * Get the list of establishments.
	 *
	 * @return array				List of establishments
	 *
	 * @url     GET /establishments
	 *
	 * @throws RestException 503 Error when retrieving list of establishments
	 */
	public function getEstablishments()
	{
		$list = array();

		$limit = 0;

		$sql = "SELECT e.rowid, e.rowid as ref, e.label, e.address, e.zip, e.town, e.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."establishment as e";
		$sql .= " WHERE e.entity IN (".getEntity('establishment').')';
		// if ($type) $sql .= " AND t.type LIKE '%".$this->db->escape($type)."%'";
		// if ($module)    $sql .= " AND t.module LIKE '%".$this->db->escape($module)."%'";
		// Add sql filters

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$list[] = $this->db->fetch_object($result);
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of establishments : '.$this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get establishment by ID.
	 *
	 * @param 	int       $id       	ID of establishment
	 * @return  Object    				Object with cleaned properties
	 *
	 * @url     GET establishments/{id}
	 *
	 * @throws RestException 404 Establishment not found
	 * @throws RestException 503 Error when retrieving establishment
	 */
	public function getEtablishmentByID($id)
	{
		$establishment = new Establishment($this->db);

		$result = $establishment->fetch($id);
		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving establishment : '.$establishment->error);
		} elseif ($result == 0) {
			throw new RestException(404, 'Establishment not found');
		}

		return $this->_cleanObjectDatas($establishment);
	}

	/**
	 * Get value of a setup variables
	 *
	 * Note that conf variables that stores security key or password hashes can't be loaded with API.
	 *
	 * @param	string			$constantname	Name of conf variable to get
	 * @return  string							Data without useless information
	 *
	 * @url     GET conf/{constantname}
	 *
	 * @throws RestException 400 Error Bad or unknown value for constantname
	 * @throws RestException 403 Forbidden
	 */
	public function getConf($constantname)
	{
		global $conf;

		if (!DolibarrApiAccess::$user->admin
			&& (!getDolGlobalString('API_LOGINS_ALLOWED_FOR_CONST_READ') || DolibarrApiAccess::$user->login != getDolGlobalString('API_LOGINS_ALLOWED_FOR_CONST_READ'))) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_CONST_READ');
		}

		if (!preg_match('/^[a-zA-Z0-9_]+$/', $constantname) || !isset($conf->global->$constantname)) {
			throw new RestException(400, 'Error Bad or unknown value for constantname');
		}
		if (isASecretKey($constantname)) {
			throw new RestException(403, 'Forbidden. This parameter can not be read with APIs');
		}

		return getDolGlobalString($constantname);
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
	 */
	public function getCheckIntegrity($target)
	{
		global $langs, $conf;

		if (!DolibarrApiAccess::$user->admin
			&& (!getDolGlobalString('API_LOGINS_ALLOWED_FOR_INTEGRITY_CHECK') || DolibarrApiAccess::$user->login != $conf->global->API_LOGINS_ALLOWED_FOR_INTEGRITY_CHECK)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_INTEGRITY_CHECK');
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$langs->load("admin");

		$outexpectedchecksum = '';
		$outcurrentchecksum = '';

		// Modified or missing files
		$file_list = array('missing' => array(), 'updated' => array());

		// Local file to compare to
		$xmlshortfile = dol_sanitizeFileName('filelist-'.DOL_VERSION.getDolGlobalString('MAIN_FILECHECK_LOCAL_SUFFIX').'.xml'.getDolGlobalString('MAIN_FILECHECK_LOCAL_EXT'));

		$xmlfile = DOL_DOCUMENT_ROOT.'/install/'.$xmlshortfile;
		if (!preg_match('/\.zip$/i', $xmlfile) && dol_is_file($xmlfile.'.zip')) {
			$xmlfile .= '.zip';
		}

		// Remote file to compare to
		$xmlremote = (($target == 'default' || $target == 'local') ? '' : $target);
		if (empty($xmlremote) && getDolGlobalString('MAIN_FILECHECK_URL')) {
			$xmlremote = getDolGlobalString('MAIN_FILECHECK_URL');
		}
		$param = 'MAIN_FILECHECK_URL_'.DOL_VERSION;
		if (empty($xmlremote) && getDolGlobalString($param)) {
			$xmlremote = getDolGlobalString($param);
		}
		if (empty($xmlremote)) {
			$xmlremote = 'https://www.dolibarr.org/files/stable/signatures/filelist-'.DOL_VERSION.'.xml';
		}
		if ($xmlremote && !preg_match('/^https?:\/\//i', $xmlremote)) {
			$langs->load("errors");
			throw new RestException(500, $langs->trans("ErrorURLMustStartWithHttp", $xmlremote));
		}
		if ($xmlremote && !preg_match('/\.xml$/', $xmlremote)) {
			$langs->load("errors");
			throw new RestException(500, $langs->trans("ErrorURLMustEndWith", $xmlremote, '.xml'));
		}

		if (LIBXML_VERSION < 20900) {
			// Avoid load of external entities (security problem).
			// Required only if LIBXML_VERSION < 20900
			// @phan-suppress-next-line PhanDeprecatedFunctionInternal
			libxml_disable_entity_loader(true);
		}

		if ($target == 'local') {
			if (dol_is_file($xmlfile)) {
				$xml = simplexml_load_file($xmlfile);
			} else {
				throw new RestException(500, $langs->trans('XmlNotFound').': /install/'.$xmlshortfile);
			}
		} else {
			$xmlarray = getURLContent($xmlremote, 'GET', '', 1, array(), array('http', 'https'), 0);	// Accept http or https links on external remote server only. Same is used into filecheck.php.

			// Return array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
			if (!$xmlarray['curl_error_no'] && $xmlarray['http_code'] != '400' && $xmlarray['http_code'] != '404') {
				$xmlfile = $xmlarray['content'];
				//print "xmlfilestart".$xmlfile."endxmlfile";
				$xml = simplexml_load_string($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
			} else {
				$errormsg = $langs->trans('XmlNotFound').': '.$xmlremote.' - '.$xmlarray['http_code'].(($xmlarray['http_code'] == 400 && $xmlarray['content']) ? ' '.$xmlarray['content'] : '').' '.$xmlarray['curl_error_no'].' '.$xmlarray['curl_error_msg'];
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
					if ($constname && getDolGlobalString($constname) != '') {
						$value = getDolGlobalString($constname);
					}
					$valueforchecksum = (empty($value) ? '0' : $value);

					$checksumconcat[] = $valueforchecksum;

					$i++;
					$out .= '<tr class="oddeven">';
					$out .= '<td>'.$i.'</td>'."\n";
					$out .= '<td>'.dol_escape_htmltag($constname).'</td>'."\n";
					$out .= '<td class="center">'.dol_escape_htmltag($constvalue).'</td>'."\n";
					$out .= '<td class="center">'.dol_escape_htmltag($valueforchecksum).'</td>'."\n";
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

				// Define qualified files (must be same than into generate_filelist_xml.php and in api_setup.class.php)
				$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
				$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|dejavu-fonts-ttf-.*|public\/test|sabre\/sabre\/.*\/tests|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$'; // Exclude dirs
				$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

				// Fill file_list with files in signature, new files, modified files
				$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', DOL_DOCUMENT_ROOT, $checksumconcat); // Fill array $file_list
				// Complete with list of new files
				foreach ($scanfiles as $keyfile => $valfile) {
					$tmprelativefilename = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $valfile['fullname']);
					if (!in_array($tmprelativefilename, $file_list['insignature'])) {
						$md5newfile = @md5_file($valfile['fullname']); // Can fails if we don't have permission to open/read file
						$file_list['added'][] = array('filename' => $tmprelativefilename, 'md5' => $md5newfile);
					}
				}

				// Files missing
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
						$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
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
						$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
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
						$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
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
			$checksumget = md5(implode(',', $checksumconcat));
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

		return array('resultcode' => $resultcode, 'resultcomment' => $resultcomment, 'expectedchecksum' => $outexpectedchecksum, 'currentchecksum' => $outcurrentchecksum, 'out' => $out);
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
			&& (!getDolGlobalString('API_LOGINS_ALLOWED_FOR_GET_MODULES') || DolibarrApiAccess::$user->login != $conf->global->API_LOGINS_ALLOWED_FOR_GET_MODULES)) {
			throw new RestException(403, 'Error API open to admin users only or to the users with logins defined into constant API_LOGINS_ALLOWED_FOR_GET_MODULES');
		}

		sort($conf->modules);

		return $this->_cleanObjectDatas($conf->modules);
	}
}
