<?php
/* Copyright (C) 2025		Jon Bendtsen<jon.bendtsen.github@jonb.dk>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/objectlink.class.php';


/**
 * API that gives shows links between objects in an Dolibarr instance.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ObjectLinks extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_source',
		'sourcetype',
		'fk_target',
		'targettype'
	);

	/**
	 * @var ObjectLink {@type ObjectLink}
	 */
	public $objectlink;

	/**
	 * @var int		notrigger is default 0, which means to trigger, else set notrigger: 1
	 */
	private $notrigger;

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->objectlink = new ObjectLink($this->db);
	}

	/**
	 * Get properties of a ObjectLink object
	 *
	 * Return an array with object link information
	 *
	 * @param   int         $id		ID of objectlink
	 * @return  Object				Object with cleaned properties
	 * @phan-return		ObjectLink
	 * @phpstan-return	ObjectLink
	 *
	 *
	 * @url	GET {id}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getById($id)
	{
		return $this->_fetch($id);
	}



	/**
	 * Set a field of $this->objectlink, with proper type
	 *
	 * @param string		$field	The field to set
	 * @param string|float	$value	The "unclean" value
	 * @return void					No return value, but field is set in $this->objectlink
	 */
	private function _setObjectLinkField($field, $value)
	{

		$clean_field = $this->_checkValForAPI($field, $value, $this->objectlink);

		/**
		 * Fields that are of integer type, used for casting during object creation and update
		 */
		$intFields = array(
			'fk_source',
			'fk_target'
		);

		if (in_array($field, $intFields)) {
			$this->objectlink->$field = (int) $clean_field;
		} else {
			$this->objectlink->$field = (string) $clean_field;
		}
	}


	/**
	 *	Create object link
	 *
	 * 	Examples: Only set "notrigger": 1 because 0 is the default value.
	 *  Linking subscriptions for when you sell membership as part of another sale
	 *  {"fk_source":"1679","sourcetype":"propal","fk_target":"1233","targettype":"commande"}
	 *  {"fk_source":"167","sourcetype":"facture","fk_target":"123","targettype":"subscription"}
	 *
	 *  @param 		array   $request_data   Request data, see Example above for required parameters. Currently unused is relationtype. notrigger is default 0, which means to trigger, else set notrigger: 1
	 * @phan-param ?array<string,string>	$request_data
	 * @phpstan-param ?array<string,string>	$request_data
	 *  @return		array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url POST
	 *
	 * @throws RestException 304
	 * @throws RestException 403
	 * @throws RestException 500
	 */
	public function create($request_data = null)
	{
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field == 'notrigger') {
				$this->notrigger = (int) $value;
			} else {
				$this->_setObjectLinkField($field, $value);
			}
		}

		// Permission check
		$srctype = $this->objectlink->sourcetype;
		if ($this->objectlink->sourcetype == 'subscription') {
			$srctype = 'adherent';
		}
		$tgttype = $this->objectlink->targettype;
		if ($this->objectlink->targettype == 'subscription') {
			$tgttype = 'adherent';
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $srctype, 'creer')) {
			throw new RestException(403, 'denied access to create the objectlinks sourcetype='.$this->objectlink->sourcetype);
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $tgttype, 'creer')) {
			throw new RestException(403, 'denied access to create the objectlinks targettype='.$this->objectlink->targettype);
		}

		$result = $this->objectlink->create(DolibarrApiAccess::$user, $this->objectlink->fk_source, $this->objectlink->sourcetype, $this->objectlink->fk_target, $this->objectlink->targettype, $this->objectlink->relationtype, $this->notrigger);

		if ($result < 0) {
			throw new RestException(500, 'when create objectlink : '.$this->objectlink->error);
		}

		if ($result == 0) {
			throw new RestException(304, 'Object link already exists');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'object link created'
			)
		);
	}

	/**
	 * Delete an object link
	 *
	 * @param   int     $id         object link ID
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
		// Reverse permission check. First we find out which kind of objects are linked, and if the user has rights to that then we delete it.
		$result = $this->objectlink->fetch($id);
		if ($result) {
			$srctype = $this->objectlink->sourcetype;
			if ($this->objectlink->sourcetype == 'subscription') {
				$srctype = 'adherent';
			}
			$tgttype = $this->objectlink->targettype;
			if ($this->objectlink->targettype == 'subscription') {
				$tgttype = 'adherent';
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $srctype), 'lire')) {
				throw new RestException(403, 'denied access to the objectlinks sourcetype');
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $tgttype), 'lire')) {
				throw new RestException(403, 'denied access to the objectlinks targettype');
			}
		} else {
			throw new RestException(404, 'Object Link not found');
		}

		if (!$this->objectlink->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete objectlink : '.$this->objectlink->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'object link deleted'
			)
		);
	}

	/**
	 *	GET object link(s) By Values, not id
	 *
	 *  @param		int		$fk_source		source id of object we link from
	 *  @param		string	$sourcetype		type of the source object
	 *  @param		int		$fk_target		target id of object we link to
	 *  @param		string	$targettype 	type of the target object
	 *  @param		string	$relationtype 	type of the relation, usually null
	 *  @return		Object
	 * @phan-return		ObjectLink
	 * @phpstan-return	ObjectLink
	 *
	 * @url GET
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function getByValues($fk_source, $sourcetype, $fk_target, $targettype, $relationtype = null)
	{
		$request_data = array(
			'fk_source' => ((int) $fk_source),
			'sourcetype' => (string) $sourcetype,
			'fk_target' => ((int) $fk_target),
			'targettype' => (string) $targettype,
			'relationtype' => $relationtype,
		);

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->_setObjectLinkField($field, $value);
		}

		// Permission check
		$srctype = $this->objectlink->sourcetype;
		if ($this->objectlink->sourcetype == 'subscription') {
			$srctype = 'adherent';
		}
		$tgttype = $this->objectlink->targettype;
		if ($this->objectlink->targettype == 'subscription') {
			$tgttype = 'adherent';
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $srctype, 'creer')) {
			throw new RestException(403, 'denied access to get the objectlinks sourcetype='.$this->objectlink->sourcetype);
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $tgttype, 'creer')) {
			throw new RestException(403, 'denied access to get the objectlinks targettype='.$this->objectlink->targettype);
		}

		$findresult = $this->objectlink->fetchByValues($this->objectlink->fk_source, $this->objectlink->sourcetype, $this->objectlink->fk_target, $this->objectlink->targettype, $this->objectlink->relationtype);

		if ($findresult < 0) {
			throw new RestException(500, 'Error when finding objectlink : '.$this->objectlink->error);
		} elseif ($findresult > 0) {
			return $this->_cleanObjectDatas($this->objectlink);
		} else {
			throw new RestException(404, 'Object Link not found');
		}
	}


	/**
	 *	Delete object link By Values, not id
	 *
	 *  @param		int		$fk_source		source id of object we link from
	 *  @param		string	$sourcetype		type of the source object
	 *  @param		int		$fk_target		target id of object we link to
	 *  @param		string	$targettype 	type of the target object
	 *  @param		string	$relationtype 	type of the relation, usually null
	 *	@param		int     $notrigger	    1=Does not execute triggers, 0=execute triggers {@choice 0,1}
	 *  @return		array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url DELETE
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function deleteByValues($fk_source, $sourcetype, $fk_target, $targettype, $relationtype = null, $notrigger = 0)
	{
		$request_data = array(
			'fk_source' => ((int) $fk_source),
			'sourcetype' => (string) $sourcetype,
			'fk_target' => ((int) $fk_target),
			'targettype' => (string) $targettype,
			'relationtype' => $relationtype,
		);

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->_setObjectLinkField($field, $value);
		}

		// Permission check
		$srctype = $this->objectlink->sourcetype;
		if ($this->objectlink->sourcetype == 'subscription') {
			$srctype = 'adherent';
		}
		$tgttype = $this->objectlink->targettype;
		if ($this->objectlink->targettype == 'subscription') {
			$tgttype = 'adherent';
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $srctype, 'creer')) {
			throw new RestException(403, 'denied access to delete the objectlinks sourcetype='.$this->objectlink->sourcetype);
		}
		if (!DolibarrApiAccess::$user->hasRight((string) $tgttype, 'creer')) {
			throw new RestException(403, 'denied access to delete the objectlinks targettype='.$this->objectlink->targettype);
		}

		$findresult = $this->objectlink->fetchByValues($this->objectlink->fk_source, $this->objectlink->sourcetype, $this->objectlink->fk_target, $this->objectlink->targettype, $this->objectlink->relationtype);

		if ($findresult < 0) {
			throw new RestException(500, 'Error when finding objectlink : '.$this->objectlink->error);
		} elseif ($findresult > 0) {
			$result = $this->objectlink->delete(DolibarrApiAccess::$user, $notrigger);

			if ($result < 0) {
				throw new RestException(500, 'Error when delete objectlink : '.$this->objectlink->error);
			}

			return array(
				'success' => array(
					'code' => 200,
					'message' => 'object link deleted'
				)
			);
		} else {
			throw new RestException(404, 'Object Link not found');
		}
	}

	/**
	 * Get properties of an object link
	 *
	 * Return an array with object links
	 *
	 * @param   int         $id             ID of objectlink
	 * @return  Object						Object with cleaned properties
	 * @phan-return		ObjectLink
	 * @phpstan-return	ObjectLink
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 */
	private function _fetch($id)
	{
		$result = $this->objectlink->fetch($id);
		if ($result) {
			$srctype = $this->objectlink->sourcetype;
			if ($this->objectlink->sourcetype == 'subscription') {
				$srctype = 'adherent';
			}
			$tgttype = $this->objectlink->targettype;
			if ($this->objectlink->targettype == 'subscription') {
				$tgttype = 'adherent';
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $srctype), 'lire')) {
				throw new RestException(403, 'denied access to the objectlinks sourcetype');
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $tgttype), 'lire')) {
				throw new RestException(403, 'denied access to the objectlinks targettype');
			}
		} else {
			throw new RestException(404, 'Object Link not found');
		}

		return $this->_cleanObjectDatas($this->objectlink);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     	Object to clean
	 * @phan-param		ObjectLink	$object
	 * @phpstan-param	ObjectLink	$object
	 *
	 * @return  Object	Object with cleaned properties
	 * @phan-return		ObjectLink
	 * @phpstan-return	ObjectLink
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->module);
		unset($object->entity);
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
		unset($object->date_creation);
		unset($object->date_validation);
		unset($object->date_modification);
		unset($object->tms);
		unset($object->date_cloture);
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

		return $object;
	}

	// source before modifications was api_orders.class.php
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
		$objectlink = array();
		foreach (ObjectLinks::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$objectlink[$field] = $data[$field];
		}
		return $objectlink;
	}
}
