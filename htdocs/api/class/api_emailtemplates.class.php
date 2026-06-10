<?php
/*
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cemailtemplate.class.php';

/**
 * API for handling Object of table llx_c_email_templates
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class EmailTemplates extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'label',
		'topic',
		'type_template'
	);

	/**
	 * @var string[]       Mandatory fields which needs to be an integer, checked when create and update object
	 */
	public static $INTFIELDS = array(
		'active',
		'private',
		'fk_user',
		'joinfiles',
		'position'
	);

	/**
	 * @var CEmailTemplate {@type CEmailTemplate}
	 */
	public $email_template;

	/**
	 * @var string 	Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'c_email_templates';

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->email_template = new CEmailTemplate($this->db);
	}

	/**
	 * Delete an email template
	 *
	 * @param   int     $id         email template ID
	 * @return  array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url	DELETE {id}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function deleteById($id)
	{
		$allowaccess = $this->_checkAccessRights('lire');
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to email templates');
		}

		$result = $this->email_template->apifetch($id, '');
		if (!$result || $id == 0) {
			throw new RestException(404, 'Email Template with id '.$id.' not found');
		}

		if (!$this->email_template->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete email template : '.$this->email_template->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'email template deleted'
			)
		);
	}

	/**
	 * Delete an email template
	 *
	 * @param   string     $label         email template label
	 * @return  array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url	DELETE label/{label}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function deleteByLAbel($label)
	{
		$allowaccess = $this->_checkAccessRights('lire');
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to email templates');
		}

		$result = $this->email_template->apifetch(0, $label);
		if (!$result) {
			throw new RestException(404, "Email Template with label ".$label." not found");
		}

		if (!$this->email_template->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete email template : '.$this->email_template->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'email template deleted'
			)
		);
	}

	/**
	 * Get properties of a email template by id
	 *
	 * Return an array with email template information
	 *
	 * @param   int         $id		ID of email template
	 * @return  Object				Object with cleaned properties
	 * @phan-return		CEmailTemplate
	 * @phpstan-return	CEmailTemplate
	 *
	 * @url	GET {id}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getById($id)
	{
		return $this->_fetch($id, '');
	}

	/**
	 * Get properties of an email template by label
	 *
	 * Return an array with order information
	 *
	 * @param       string		$label		Label of object
	 * @return      Object				    Object with cleaned properties
	 * @phan-return		CEmailTemplate
	 * @phpstan-return	CEmailTemplate
	 *
	 * @url GET    label/{label}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getByLabel($label)
	{
		return $this->_fetch(0, $label);
	}

	/**
	 * List email templates
	 *
	 * Get a list of email templates
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$fk_user			User ids to filter email templates of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(e.active:=:1) and (e.module:=:'adherent')"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array						Array of order objects
	 * @phan-return CEmailTemplate[]|array{data:CEmailTemplate[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return CEmailTemplate[]|array{data:CEmailTemplate[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @url GET
	 *
	 * @throws RestException 404 Not found
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "e.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $fk_user = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$allowaccess = $this->_checkAccessRights('lire');
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to email templates');
		}

		$obj_ret = array();

		$sql = "SELECT e.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." AS e";
		$sql .= " WHERE e.entity IN (".getEntity($this->table_element).")";
		if (!$fk_user == '') {
			$sql .= " AND e.fk_user = ".((int) $fk_user);
		}

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
		$sqlTotals = str_replace('SELECT e.rowid', 'SELECT count(e.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::index", LOG_DEBUG);
		$result = $this->db->query($sql);
		dol_syslog(get_class($this)."::pindex", LOG_DEBUG);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$email_template_static = new CEmailTemplate($this->db);
				if ($email_template_static->apifetch($obj->rowid, '') > 0) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($email_template_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve email template list : '.$this->db->lasterror());
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create an email template
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"label":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"label":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url POST
	 *
	 * @return  int     ID of email template
	 *
	 * @throws	RestException 400
	 * @throws	RestException 403
	 * @throws	RestException 500
	 */
	public function post($request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('creer');
		if (!$allowaccess) {
			throw new RestException(403, 'denied create access to email templates');
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->email_template->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'id') {
				throw new RestException(400, 'Creating with id field is forbidden');
			}
			if ($field == 'tms') {
				throw new RestException(400, 'Creating with tms field is forbidden');
			}

			$this->email_template->$field = $this->_checkValForAPI($field, $value, $this->email_template);
		}

		if ($this->email_template->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating email template", array_merge(array($this->email_template->error), $this->email_template->errors));
		}

		return ((int) $this->email_template->id);
	}

	/**
	 * Update an email template
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"label":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"label":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param	int		$id             Id of order to update
	 * @param	array	$request_data   Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url PUT {id}
	 *
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException 400
	 * @throws	RestException 403
	 * @throws	RestException 404
	 * @throws	RestException 500
	 */
	public function putById($id, $request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('creer');
		if (!$allowaccess) {
			throw new RestException(403, 'denied update access to email templates');
		}

		$result = $this->email_template->apifetch($id, '');
		if (!$result || $id == 0) {
			throw new RestException(404, 'email template with id='.$id.' not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				throw new RestException(400, 'Updating with id field is forbidden');
			}
			if ($field == 'datec') {
				throw new RestException(400, 'Updating with datec field is forbidden');
			}

			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->email_template->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->email_template->$field = $this->_checkValForAPI($field, $value, $this->email_template);
		}

		if ($this->email_template->update(DolibarrApiAccess::$user) > 0) {
			return $this->_fetch($id, '');
		} else {
			throw new RestException(500, $this->email_template->error);
		}
	}

	/**
	 * Update an email template
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"label":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"label":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param	string	$label			Label of order to update
	 * @param	array	$request_data	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url PUT label/{label}
	 *
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException 400
	 * @throws	RestException 404
	 * @throws	RestException 500
	 */
	public function putbyLabel($label, $request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('creer');
		if (!$allowaccess) {
			throw new RestException(403, 'denied update access to email templates');
		}

		$result = $this->email_template->apifetch(0, $label);
		if (!$result) {
			throw new RestException(404, 'email template not found');
		}

		$newlabel = $label;
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				throw new RestException(400, 'Updating with id field is forbidden');
			}
			if ($field == 'datec') {
				throw new RestException(400, 'Updating with datec field is forbidden');
			}

			if ($field == 'label') {
				$newlabel = $this->_checkValForAPI($field, $value, $this->email_template);
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->email_template->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->email_template->$field = $this->_checkValForAPI($field, $value, $this->email_template);
		}

		if ($this->email_template->update(DolibarrApiAccess::$user) > 0) {
			return $this->_fetch(0, $newlabel);
		} else {
			throw new RestException(500, $this->email_template->error);
		}
	}

	/**
	 * Get properties of an email template
	 *
	 * Return an array with email templates
	 *
	 * @param   int         $id             ID of email_template
	 * @param	string		$label			Label of email_template
	 * @return  Object						Object with cleaned properties
	 * @phan-return		CEmailTemplate
	 * @phpstan-return	CEmailTemplate
	 *
	 * @throws	RestException 400
	 * @throws	RestException 403
	 * @throws	RestException 404
	 */
	private function _fetch($id, $label = '')
	{
		global $conf;

		$allowaccess = $this->_checkAccessRights('lire');
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to email templates');
		}

		$result = $this->email_template->apifetch($id, $label);
		if ($result > 0) {
			return $this->_cleanObjectDatas($this->email_template);
		}
		if ($result == 0) {
			if ($id) {
				throw new RestException(404, 'Email template with id='.((string) $id).' not found in entity='.(int) $conf->entity);
			}
			if ($label) {
				throw new RestException(404, 'Email template with label '.$label.' not found in entity='.(int) $conf->entity);
			}
			throw new RestException(404, 'Email Template not found');
		} else {
			if (empty($this->email_template->error)) {
				throw new RestException(400, 'Unknown error in your request');
			} else {
				throw new RestException(400, 'Error: '.$this->email_template->error);
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Object  $object     	Object to clean
	 * @phan-param		CEmailTemplate	$object
	 * @phpstan-param	T	$object
	 *
	 * @return  Object	Object with cleaned properties
	 * @phan-return		CEmailTemplate
	 * @phpstan-return	T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);
		dol_syslog(get_class($this)."::_cleanObjectDatas", LOG_DEBUG);


		unset($object->import_key);
		unset($object->array_languages);
		unset($object->contacts_ids);
		unset($object->linkedObjectsIds);
		unset($object->canvas);
		unset($object->fk_project);
		unset($object->contact_id);
		unset($object->user);
		unset($object->origin_type);
		unset($object->origin_id);
		unset($object->ref);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->status);
		unset($object->civility_code);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->state_id);
		unset($object->region_id);
		unset($object->barcode_type);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->shipping_method_id);
		unset($object->shipping_method);
		unset($object->fk_multicurrency);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->last_main_doc);
		unset($object->fk_account);
		unset($object->note_public);
		unset($object->note_private);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->lines);
		unset($object->actiontypecode);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->user_author);
		unset($object->user_creation);
		unset($object->user_creation_id);
		unset($object->user_valid);
		unset($object->user_validation);
		unset($object->user_validation_id);
		unset($object->user_closing_id);
		unset($object->user_modification);
		unset($object->user_modification_id);
		unset($object->fk_user_creat);
		unset($object->fk_user_modif);
		unset($object->totalpaid);
		unset($object->product);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->warehouse_id);
		unset($object->target);
		unset($object->array_options);
		unset($object->extraparams);
		unset($object->specimen);
		unset($object->date_validation);
		unset($object->date_modification);
		unset($object->date_cloture);
		unset($object->rowid);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,null|int|string>	$data   Data to validate
	 * @return array<string,null|int|string>			Return array with validated mandatory fields and their value
	 * @phan-return array<string,?int|?string>			Return array with validated mandatory fields and their value
	 *
	 * @throws  RestException 400
	 */
	private function _validate($data)
	{
		$email_template = array();
		foreach (EmailTemplates::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$email_template[$field] = $data[$field];
		}
		return $email_template;
	}

	/**
	 * function to check for access rights - should probably have 1. parameter which is read/write/delete/...
	 * Why a separate function? because we probably needs to check so many many different kinds of objects
	 *
	 * @param	string		$accesstype		accesstype: read, write, delete, ...
	 * @return 	bool     					Return true if access is granted else false
	 *
	 * @throws  RestException 403
	 */
	private function _checkAccessRights($accesstype)
	{
		// what kind of access management do we need?
		$allowaccess = false;
		if (isModEnabled("societe") && DolibarrApiAccess::$user->hasRight('societe', $accesstype)) {
			$allowaccess = true;
		}
		if (isModEnabled('member') && DolibarrApiAccess::$user->hasRight('adherent', $accesstype)) {
			$allowaccess = true;
		}
		if (isModEnabled("propal") && DolibarrApiAccess::$user->hasRight('propal', $accesstype)) {
			$allowaccess = true;
		}
		if (isModEnabled('order') && DolibarrApiAccess::$user->hasRight('commande', $accesstype)) {
			$allowaccess = true;
		}
		if (isModEnabled('invoice') && DolibarrApiAccess::$user->hasRight('facture', $accesstype)) {
			$allowaccess = true;
		}
		if ($allowaccess) {
			return $allowaccess;
		} else {
			throw new RestException(403, 'denied access to email templates');
		}
	}
}
