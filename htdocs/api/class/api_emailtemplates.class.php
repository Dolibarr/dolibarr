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
	);

	/**
	 * @var string[]       Mandatory fields which needs to be an integer, checked when create and update object
	 */
	public static $INTFIELDS = array(
	);

	/**
	 * @var cEmailTemplate {@type cEmailTemplate}
	 */
	public $email_template;

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->email_template = new cEmailTemplate($this->db);
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
		$allowaccess = $this->_CheckAccessRights();
		if (!$allowaccess) {
			throw new RestException(403, 'denied access to email templates');
		}

		$result = $this->email_template->apifetch($id, '');
		if (!$result) {
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
		$allowaccess = $this->_CheckAccessRights();
		if (!$allowaccess) {
			throw new RestException(403, 'denied access to email templates');
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
	 * @phan-return		cEmailTemplate
	 * @phpstan-return	cEmailTemplate
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
	 * Get properties of an email template by label
	 *
	 * Return an array with order information
	 *
	 * @param       string		$label		Label of object
	 * @return      Object				    Object with cleaned properties
	 * @phan-return		cEmailTemplate
	 * @phpstan-return	cEmailTemplate
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
	 * Get properties of an email template
	 *
	 * Return an array with email templates
	 *
	 * @param   int         $id             ID of email_template
	 * @param	string		$label			Label of email_template
	 * @return  Object						Object with cleaned properties
	 * @phan-return		cEmailTemplate
	 * @phpstan-return	cEmailTemplate
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 */
	private function _fetch($id, $label = '')
	{
		$allowaccess = $this->_CheckAccessRights();
		if (!$allowaccess) {
			throw new RestException(403, 'denied access to email templates');
		}

		$result = $this->email_template->apifetch($id, $label);
		if (!$result) {
			if ($id) {
				throw new RestException(404, 'Email template with id '.((string) $id).' not found');
			}
			if ($label) {
				throw new RestException(404, 'Email template with label '.$label.' not found');
			}
			throw new RestException(404, 'Email Template not found');
		}

		return $this->_cleanObjectDatas($this->email_template);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     	Object to clean
	 * @phan-param		cEmailTemplate	$object
	 * @phpstan-param	cEmailTemplate	$object
	 *
	 * @return  Object	Object with cleaned properties
	 * @phan-return		cEmailTemplate
	 * @phpstan-return	cEmailTemplate
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
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,null|int|string>	$data   Data to validate
	 * @return array<string,null|int|string>			Return array with validated mandatory fields and their value
	 * @phan-return array<string,?int|?string>			Return array with validated mandatory fields and their value
	 *
	 * @throws  RestException 403
	 */
	private function _CheckAccessRights()
	{
		// what kind of access management do we need?
		$allowaccess = false;
		if (isModEnabled("societe") && DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			$allowaccess = true;
		}
		if (isModEnabled('member') && DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			$allowaccess = true;
		}
		if (isModEnabled("propal") && DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
			$allowaccess = true;
		}
		if (isModEnabled('order') && DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			$allowaccess = true;
		}
		if (isModEnabled('invoice') && DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			$allowaccess = true;
		}
		if ($allowaccess) {
			return $allowaccess;
		} else {
			throw new RestException(403, 'denied access to email templates');
		}
	}
}
