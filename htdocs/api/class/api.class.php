<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2020		Frédéric France		<frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

use Luracast\Restler\Restler;
use Luracast\Restler\Defaults;

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Class for API REST v1
 */
class DolibarrApi
{
	/**
	 * @var DoliDB        $db Database object
	 */
	protected $db;

	/**
	 * @var Restler     $r	Restler object
	 */
	public $r;

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		        Database handler
	 * @param   string  $cachedir       Cache dir
	 * @param   boolean $refreshCache   Update cache
	 */
	public function __construct($db, $cachedir = '', $refreshCache = false)
	{
		global $conf, $dolibarr_main_url_root;

		if (empty($cachedir)) {
			$cachedir = $conf->api->dir_temp;
		}
		Defaults::$cacheDirectory = $cachedir;

		$this->db = $db;
		$production_mode = !(!getDolGlobalString('API_PRODUCTION_MODE'));
		$this->r = new Restler($production_mode, $refreshCache);

		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file

		$urlwithouturlrootautodetect = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim(DOL_MAIN_URL_ROOT));
		$urlwithrootautodetect = $urlwithouturlroot.DOL_URL_ROOT; // This is to use local domain autodetected by dolibarr from url

		$this->r->setBaseUrls($urlwithouturlroot, $urlwithouturlrootautodetect);
		$this->r->setAPIVersion(1);
		//$this->r->setSupportedFormats('json');
		//$this->r->setSupportedFormats('jsonFormat');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Check and convert a string depending on its type/name.
	 *
	 * @param	string			$field		Field name
	 * @param	string|array	$value		Value to check/clean
	 * @param	Object			$object		Object
	 * @return 	string|array				Value cleaned
	 */
	protected function _checkValForAPI($field, $value, $object)
	{
		// phpcs:enable
		if (!is_array($value)) {
			// Sanitize the value using its type declared into ->fields of $object
			if (!empty($object->fields) && !empty($object->fields[$field]) && !empty($object->fields[$field]['type'])) {
				if (strpos($object->fields[$field]['type'], 'int') || strpos($object->fields[$field]['type'], 'double') || in_array($object->fields[$field]['type'], array('real', 'price', 'stock'))) {
					return sanitizeVal($value, 'int');
				}
				if ($object->fields[$field]['type'] == 'html') {
					return sanitizeVal($value, 'restricthtml');
				}
				if ($object->fields[$field]['type'] == 'select') {
					// Check values are in the list of possible 'options'
					// TODO
				}
				if ($object->fields[$field]['type'] == 'sellist' || $object->fields[$field]['type'] == 'checkbox') {
					// TODO
				}
				if ($object->fields[$field]['type'] == 'boolean' || $object->fields[$field]['type'] == 'radio') {
					// TODO
				}
				if ($object->fields[$field]['type'] == 'email') {
					return sanitizeVal($value, 'email');
				}
				if ($object->fields[$field]['type'] == 'password') {
					return sanitizeVal($value, 'none');
				}
				// Others will use 'alphanohtml'
			}

			if (in_array($field, array('note', 'note_private', 'note_public', 'desc', 'description'))) {
				return sanitizeVal($value, 'restricthtml');
			} else {
				return sanitizeVal($value, 'alphanohtml');
			}
		} else {	// Example when $field = 'extrafields' and $value = content of $object->array_options
			$newarrayvalue = array();
			foreach ($value as $tmpkey => $tmpvalue) {
				$newarrayvalue[$tmpkey] = $this->_checkValForAPI($tmpkey, $tmpvalue, $object);
			}

			return $newarrayvalue;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Filter properties that will be returned on object
	 *
	 * @param   Object  $object			Object to clean
	 * @param   String  $properties		Comma separated list of properties names
	 * @return	Object					Object with cleaned properties
	 */
	protected function _filterObjectProperties($object, $properties)
	{
		// phpcs:enable
		// If properties is empty, we return all properties
		if (empty($properties)) {
			return $object;
		}

		// Copy of exploded array for efficiency
		$arr_properties = explode(',', $properties);
		$magic_properties = array();
		$real_properties = get_object_vars($object);

		// Unsetting real properties may unset magic properties.
		// We keep a copy of the requested magic properties
		foreach ($arr_properties as $key) {
			if (!array_key_exists($key, $real_properties)) {
				// Not a real property,
				// check if $key is a magic property (we want to keep '$obj->$key')
				if (property_exists($object, $key) && isset($object->$key)) {
					$magic_properties[$key] = $object->$key;
				}
			}
		}

		// Filter real properties (may indirectly unset magic properties)
		foreach (get_object_vars($object) as $key => $value) {
			if (!in_array($key, $arr_properties)) {
				unset($object->$key);
			}
		}

		// Restore the magic properties
		foreach ($magic_properties as $key => $value) {
			$object->$key = $value;
		}

		return $object;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		// Remove $db object property for object
		unset($object->db);
		unset($object->isextrafieldmanaged);
		unset($object->ismultientitymanaged);
		unset($object->restrictiononfksoc);
		unset($object->table_rowid);
		unset($object->pass);
		unset($object->pass_indatabase);

		// Remove linkedObjects. We should already have and keep only linkedObjectsIds that avoid huge responses
		unset($object->linkedObjects);
		//unset($object->lines[$i]->linked_objects);		// This is the array to create linked object during create

		unset($object->fields);
		unset($object->oldline);

		unset($object->error);
		unset($object->errors);
		unset($object->errorhidden);

		unset($object->ref_previous);
		unset($object->ref_next);
		unset($object->imgWidth);
		unset($object->imgHeight);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);

		unset($object->mode_reglement);		// We use mode_reglement_id now
		unset($object->cond_reglement);		// We use cond_reglement_id now
		unset($object->note);				// We use note_public or note_private now
		unset($object->contact);			// We use contact_id now
		unset($object->thirdparty);			// We use thirdparty_id or fk_soc or socid now

		unset($object->projet); // Should be fk_project
		unset($object->project); // Should be fk_project
		unset($object->fk_projet); // Should be fk_project
		unset($object->author); // Should be fk_user_author
		unset($object->timespent_old_duration);
		unset($object->timespent_id);
		unset($object->timespent_duration);
		unset($object->timespent_date);
		unset($object->timespent_datehour);
		unset($object->timespent_withhour);
		unset($object->timespent_fk_user);
		unset($object->timespent_note);
		unset($object->fk_delivery_address);
		unset($object->model_pdf);
		unset($object->sendtoid);
		unset($object->name_bis);
		unset($object->newref);
		unset($object->oldref);
		unset($object->alreadypaid);
		unset($object->openid);
		unset($object->fk_bank);
		unset($object->showphoto_on_popup);
		unset($object->nb);
		unset($object->nbphoto);
		unset($object->output);
		unset($object->tpl);
		//unset($object->libelle);

		unset($object->stats_propale);
		unset($object->stats_commande);
		unset($object->stats_contrat);
		unset($object->stats_facture);
		unset($object->stats_commande_fournisseur);
		unset($object->stats_reception);
		unset($object->stats_mrptoconsume);
		unset($object->stats_mrptoproduce);

		unset($object->origin_object);
		unset($object->origin);
		unset($object->element);
		unset($object->element_for_permission);
		unset($object->fk_element);
		unset($object->table_element);
		unset($object->table_element_line);
		unset($object->class_element_line);
		unset($object->picto);
		unset($object->linked_objects);

		unset($object->fieldsforcombobox);
		unset($object->regeximgext);

		unset($object->skip_update_total);
		unset($object->context);
		unset($object->next_prev_filter);

		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->state);
		unset($object->state_code);
		unset($object->fk_departement);
		unset($object->departement);
		unset($object->departement_code);

		unset($object->libelle_statut);
		unset($object->libelle_paiement);
		unset($object->labelStatus);
		unset($object->labelStatusShort);

		unset($object->actionmsg);
		unset($object->actionmsg2);

		unset($object->prefix_comm);

		if (!isset($object->table_element) || $object->table_element != 'ticket') {
			unset($object->comments);
		}

		// Remove the $oldcopy property because it is not supported by the JSON
		// encoder. The following error is generated when trying to serialize
		// it: "Error encoding/decoding JSON: Type is not supported"
		// Note: Event if this property was correctly handled by the JSON
		// encoder, it should be ignored because keeping it would let the API
		// have a very strange behavior: calling PUT and then GET on the same
		// resource would give different results:
		// PUT /objects/{id} -> returns object with oldcopy = previous version of the object
		// GET /objects/{id} -> returns object with oldcopy empty
		unset($object->oldcopy);

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->contact);
				unset($object->lines[$i]->contact_id);
				unset($object->lines[$i]->country);
				unset($object->lines[$i]->country_id);
				unset($object->lines[$i]->country_code);
				unset($object->lines[$i]->mode_reglement_id);
				unset($object->lines[$i]->mode_reglement_code);
				unset($object->lines[$i]->mode_reglement);
				unset($object->lines[$i]->cond_reglement_id);
				unset($object->lines[$i]->cond_reglement_code);
				unset($object->lines[$i]->cond_reglement);
				unset($object->lines[$i]->fk_delivery_address);
				unset($object->lines[$i]->fk_projet);
				unset($object->lines[$i]->fk_project);
				unset($object->lines[$i]->thirdparty);
				unset($object->lines[$i]->user);
				unset($object->lines[$i]->model_pdf);
				unset($object->lines[$i]->note_public);
				unset($object->lines[$i]->note_private);
				unset($object->lines[$i]->fk_incoterms);
				unset($object->lines[$i]->label_incoterms);
				unset($object->lines[$i]->location_incoterms);
				unset($object->lines[$i]->name);
				unset($object->lines[$i]->lastname);
				unset($object->lines[$i]->firstname);
				unset($object->lines[$i]->civility_id);
				unset($object->lines[$i]->fk_multicurrency);
				unset($object->lines[$i]->multicurrency_code);
				unset($object->lines[$i]->shipping_method_id);
			}
		}

		if (!empty($object->thirdparty) && is_object($object->thirdparty)) {
			$this->_cleanObjectDatas($object->thirdparty);
		}

		if (!empty($object->product) && is_object($object->product)) {
			$this->_cleanObjectDatas($object->product);
		}

		return $object;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Check access by user to a given resource
	 *
	 * @param string	$resource		element to check
	 * @param int		$resource_id	Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
	 * @param string	$dbtablename	'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity. Not used if objectid is null (optional)
	 * @param string	$feature2		Feature to check, second level of permission (optional). Can be or check with 'level1|level2'.
	 * @param string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
	 * @param string	$dbt_select     Field name for select if not rowid. Not used if objectid is null (optional)
	 * @return bool
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid')
	{
		// phpcs:enable
		// Features/modules to check
		$featuresarray = array($resource);
		if (preg_match('/&/', $resource)) {
			$featuresarray = explode("&", $resource);
		} elseif (preg_match('/\|/', $resource)) {
			$featuresarray = explode("|", $resource);
		}

		// More subfeatures to check
		if (!empty($feature2)) {
			$feature2 = explode("|", $feature2);
		}

		return checkUserAccessToObject(DolibarrApiAccess::$user, $featuresarray, $resource_id, $dbtablename, $feature2, $dbt_keyfield, $dbt_select);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Return if a $sqlfilters parameter is valid
	 * Function no more used. Kept for backward compatibility with old APIs of modules
	 *
	 * @param  	string   		$sqlfilters     sqlfilter string
	 * @param	string			$error			Error message
	 * @return 	boolean|string   				True if valid, False if not valid
	 */
	protected function _checkFilters($sqlfilters, &$error = '')
	{
		// phpcs:enable
		$firstandlastparenthesis = 0;
		return dolCheckFilters($sqlfilters, $error, $firstandlastparenthesis);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Function to forge a SQL criteria from a Generic filter string.
	 * Function no more used. Kept for backward compatibility with old APIs of modules
	 *
	 * @param  array    $matches    Array of found string by regex search.
	 * 								Each entry is 1 and only 1 criteria.
	 * 								Example: "t.ref:like:'SO-%'", "t.date_creation:<:'20160101'", "t.date_creation:<:'2016-01-01 12:30:00'", "t.nature:is:NULL", "t.field2:isnot:NULL"
	 * @return string               Forged criteria. Example: "t.field like 'abc%'"
	 */
	protected static function _forge_criteria_callback($matches)
	{
		return dolForgeCriteriaCallback($matches);
	}
}
