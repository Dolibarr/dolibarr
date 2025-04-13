<?php
/*
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/emailtemplate.class.php';


/**
 * API that gives shows links between objects in an Dolibarr instance.
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
	);

	/**
	 * @var string[]       Mandatory fields which needs to be an integer, checked when create and update object
	 */
	public static $INTFIELDS = array(
    );

	/**
	 * @var EmailTemplate {@type EmailTemplate}
	 */
	public $emailtemplate;

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->emailtemplate = new EmailTemplate($this->db);
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
		return $this->_fetch($id, '');
	}

	/**
	 * Get properties of an order object by ref
	 *
	 * Return an array with order information
	 *
	 * @param       string		$label			Label of object
	 * @return	array|mixed data without useless information
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
	 * Get properties of an object link
	 *
	 * Return an array with object links
	 *
	 * @param   int         $id             ID of emailtemplate
	 * @param	string		$label			Label of emailtemplate
	 * @return  Object						Object with cleaned properties
	 * @phan-return		EmailTemplate
	 * @phpstan-return	EmailTemplate
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 */
	private function _fetch($id, $label = '')
	{

        $allowaccess = FALSE;
        if (isModEnabled("societe") && DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
            $allowaccess = TRUE;
        }
        if (isModEnabled('member') && DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
            $allowaccess = TRUE;
        }
        if (isModEnabled("propal") && DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
            $allowaccess = TRUE;
        }
        if (isModEnabled('order') && DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
            $allowaccess = TRUE;
        }
        if (isModEnabled('invoice') && DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
            $allowaccess = TRUE;
        }

        if (!$allowaccess) {
            throw new RestException(403, 'denied access to email templates');
        }
		$result = $this->emailtemplate->fetch($id, $label);
		if (!$result) {
			throw new RestException(404, 'Object Link not found');
		}

		return $this->_cleanObjectDatas($this->emailtemplate);
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
