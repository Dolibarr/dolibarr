<?php
/*
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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
	 * @var ObjectLink $objectlink {@type ObjectLink}
	 */
	public $objectlink;

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
	 * @param   int         $id				ID of objectlink
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		return $this->_fetch($id);
	}

	/**
	 * Delete an object link
	 *
	 * @param   int     $id         object link ID
	 * @return  array
	 */
	public function delete($id)
	{
		// Reverse permission check. First we find out which kind of objects are linked, and if the user has rights to that then we present it.
		$result = $this->objectlink->fetch($id);

		$result = $this->db->query($sql);
		if ($result) {
			if (!DolibarrApiAccess::$user->hasRight(((string) $result->sourcetype), 'lire')) {
				throw new RestException(403);
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $result->targettype), 'lire')) {
				throw new RestException(403);
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
	 * Get properties of an object link
	 *
	 * Return an array with object links
	 *
	 * @param   int         $id             ID of objectlink
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id)
	{
		$result = $this->objectlink->fetch($id);
		if ($result) {
			if (!DolibarrApiAccess::$user->hasRight(((string) $this->objectlink->sourcetype), 'lire')) {
				throw new RestException(403, 'denied access to the objectlinks sourcetype');
			}
			if (!DolibarrApiAccess::$user->hasRight(((string) $this->objectlink->targettype), 'lire')) {
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
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
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
}
