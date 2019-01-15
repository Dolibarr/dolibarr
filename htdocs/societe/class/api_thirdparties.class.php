<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018   Pierre Chéné            <pierre.chene44@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;


/**
 * API class for thirdparties
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 *
 */
class Thirdparties extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'name'
	);

	/**
	 * @var Societe $company {@type Societe}
	 */
	public $company;

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $db, $conf;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';

		$this->company = new Societe($this->db);

		if (! empty($conf->global->SOCIETE_EMAIL_MANDATORY)) {
			static::$FIELDS[] = 'email';
		}
	}

	/**
	 * Get properties of a thirdparty object
	 *
	 * Return an array with thirdparty informations
	 *
	 * @param 	int 	$id ID of thirdparty
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	function get($id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		$absolute_discount = $this->company->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $this->company->getAvailableDiscounts('', $filtercreditnote);
		$this->company->absolute_discount = price2num($absolute_discount, 'MT');
		$this->company->absolute_creditnote = price2num($absolute_creditnote, 'MT');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * List thirdparties
	 *
	 * Get a list of thirdparties
	 *
	 * @param   string  $sortfield  Sort field
	 * @param   string  $sortorder  Sort order
	 * @param   int     $limit      Limit for list
	 * @param   int     $page       Page number
	 * @param   int     $mode       Set to 1 to show only customers
	 *                              Set to 2 to show only prospects
	 *                              Set to 3 to show only those are not customer neither prospect
	 * @param   string  $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.nom:like:'TheCompany%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of thirdparty objects
	 */
	function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $mode=0, $sqlfilters = '')
    {
		global $db, $conf;

		$obj_ret = array();

		// case of external user, we force socids
		$socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

		$sql = "SELECT t.rowid";
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as t";

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
		$sql.= " WHERE t.fk_stcomm = st.id";
		if ($mode == 1) $sql.= " AND t.client IN (1, 3)";
		if ($mode == 2) $sql.= " AND t.client IN (2, 3)";
		if ($mode == 3) $sql.= " AND t.client IN (0)";
		$sql.= ' AND t.entity IN ('.getEntity('societe').')';
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";
		//if ($email != NULL) $sql.= " AND s.email = \"".$email."\"";
		if ($socid) $sql.= " AND t.rowid IN (".$socids.")";
		if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
		// Insert sale filter
		if ($search_sale > 0)
		{
			$sql .= " AND sc.fk_user = ".$search_sale;
		}
		// Add sql filters
		if ($sqlfilters)
		{
			if (! DolibarrApi::_checkFilters($sqlfilters))
			{
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql.= $db->order($sortfield, $sortorder);

		if ($limit) {
			if ($page < 0)
			{
				$page = 0;
			}
			$offset = $limit * $page;

			$sql.= $db->plimit($limit + 1, $offset);
		}

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
				$obj = $db->fetch_object($result);
				$soc_static = new Societe($db);
				if($soc_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($soc_static);
				}
				$i++;
			}
		}
		else {
			throw new RestException(503, 'Error when retrieve thirdparties : '.$db->lasterror());
		}
		if( ! count($obj_ret)) {
			throw new RestException(404, 'Thirdparties not found');
		}
		return $obj_ret;
	}

	/**
	 * Create thirdparty object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of thirdparty
	 */
	function post($request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach($request_data as $field => $value) {
			$this->company->$field = $value;
		}
		if ($this->company->create(DolibarrApiAccess::$user) < 0)
			throw new RestException(500, 'Error creating thirdparty', array_merge(array($this->company->error), $this->company->errors));

		return $this->company->id;
	}

	/**
	 * Update thirdparty
	 *
	 * @param int   $id             Id of thirdparty to update
	 * @param array $request_data   Datas
	 * @return int
	 */
	function put($id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach($request_data as $field => $value) {
			if ($field == 'id') continue;
			$this->company->$field = $value;
		}

		if($this->company->update($id, DolibarrApiAccess::$user,1,'','','update'))
			return $this->get($id);

		return false;
	}

	/**
	 * Merge a thirdparty into another one.
	 *
	 * Merge content (properties, notes) and objects (like invoices, events, orders, proposals, ...) of a thirdparty into a target thirdparty,
	 * then delete the merged thirdparty.
	 * If a property has a defined value both in thirdparty to delete and thirdparty to keep, the value into the thirdparty to
	 * delete will be ignored, the value of target thirdparty will remain, except for notes (content is concatenated).
	 *
	 * @param int   $id             ID of thirdparty to keep (the target thirdparty)
	 * @param int   $idtodelete     ID of thirdparty to remove (the thirdparty to delete), once data has been merged into the target thirdparty.
	 * @return int
	 *
	 * @url PUT {id}/merge/{idtodelete}
	 */
	function merge($id, $idtodelete)
	{
		global $db, $hookmanager;

		$error = 0;

		if ($id == $idtodelete)
		{
			throw new RestException(400, 'Try to merge a thirdparty into itself');
		}

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);	// include the fetch of extra fields
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->companytoremove = new Societe($db);

		$result = $this->companytoremove->fetch($idtodelete);	// include the fetch of extra fields
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->companytoremove->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$soc_origin = $this->companytoremove;
		$object = $this->company;
		$user = DolibarrApiAccess::$user;


		// Call same code than into action 'confirm_merge'


		$db->begin();

		// Recopy some data
		$object->client = $object->client | $soc_origin->client;
		$object->fournisseur = $object->fournisseur | $soc_origin->fournisseur;
		$listofproperties=array(
			'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_pro', 'fax', 'email', 'skype', 'url', 'barcode',
			'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
			'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
			'stcomm_id', 'outstanding_limit', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
			'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
			'model_pdf', 'fk_projet'
		);
		foreach ($listofproperties as $property)
		{
			if (empty($object->$property)) $object->$property = $soc_origin->$property;
		}

		// Concat some data
		$listofproperties=array(
			'note_public', 'note_private'
		);
		foreach ($listofproperties as $property)
		{
			$object->$property = dol_concatdesc($object->$property, $soc_origin->$property);
		}

		// Merge extrafields
		if (is_array($soc_origin->array_options))
		{
			foreach ($soc_origin->array_options as $key => $val)
			{
				if (empty($object->array_options[$key])) $object->array_options[$key] = $val;
			}
		}

		// Merge categories
		$static_cat = new Categorie($db);
		$custcats = $static_cat->containing($soc_origin->id, 'customer', 'id');
		$object->setCategories($custcats, 'customer');
		$suppcats = $static_cat->containing($soc_origin->id, 'supplier', 'id');
		$object->setCategories($suppcats, 'supplier');

		// If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
		if ($soc_origin->code_client == $object->code_client
			|| $soc_origin->code_fournisseur == $object->code_fournisseur
			|| $soc_origin->barcode == $object->barcode)
		{
			dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
			$soc_origin->code_client = '';
			$soc_origin->code_fournisseur = '';
			$soc_origin->barcode = '';
			$soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
		}

		// Update
		$result = $object->update($object->id, $user, 0, 1, 1, 'merge');
		if ($result < 0)
		{
			$error++;
		}

		// Move links
		if (! $error)
		{
			$objects = array(
				'Adherent' => '/adherents/class/adherent.class.php',
				'Societe' => '/societe/class/societe.class.php',
				'Categorie' => '/categories/class/categorie.class.php',
				'ActionComm' => '/comm/action/class/actioncomm.class.php',
				'Propal' => '/comm/propal/class/propal.class.php',
				'Commande' => '/commande/class/commande.class.php',
				'Facture' => '/compta/facture/class/facture.class.php',
				'FactureRec' => '/compta/facture/class/facture-rec.class.php',
				'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
				'Contact' => '/contact/class/contact.class.php',
				'Contrat' => '/contrat/class/contrat.class.php',
				'Expedition' => '/expedition/class/expedition.class.php',
				'Fichinter' => '/fichinter/class/fichinter.class.php',
				'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
				'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
				'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
				'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
				'Livraison' => '/livraison/class/livraison.class.php',
				'Product' => '/product/class/product.class.php',
				'Project' => '/projet/class/project.class.php',
				'User' => '/user/class/user.class.php',
			);

			//First, all core objects must update their tables
			foreach ($objects as $object_name => $object_file)
			{
				require_once DOL_DOCUMENT_ROOT.$object_file;

				if (!$errors && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id))
				{
					$errors++;
					//setEventMessages($db->lasterror(), null, 'errors');
				}
			}
		}

		// External modules should update their ones too
		if (!$errors)
		{
			$reshook = $hookmanager->executeHooks('replaceThirdparty', array(
				'soc_origin' => $soc_origin->id,
				'soc_dest' => $object->id
			), $soc_dest, $action);

			if ($reshook < 0)
			{
				//setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				$errors++;
			}
		}


		if (! $error)
		{
			$object->context=array('merge'=>1, 'mergefromid'=>$soc_origin->id);

			// Call trigger
			$result=$object->call_trigger('COMPANY_MODIFY',$user);
			if ($result < 0)
			{
				//setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
			// End call triggers
		}

		if (! $error)
		{
			//We finally remove the old thirdparty
			if ($soc_origin->delete($soc_origin->id, $user) < 1)
			{
				$errors++;
			}
		}

		// End of merge

		if ($error)
		{
			$db->rollback();

			throw new RestException(500, 'Error failed to merged thirdparty '.$this->companytoremove->id.' into '.$id.'. Enable and read log file for more information.');
		}
		else
		{
			$db->commit();
		}

		return $this->get($id);
	}

	/**
	 * Delete thirdparty
	 *
	 * @param int $id   Thirparty ID
	 * @return integer
	 */
	function delete($id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->supprimer) {
			throw new RestException(401);
		}
		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}
		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		return $this->company->delete($id);
	}

	/**
	 * Get customer categories for a thirdparty
	 *
	 * @param int		$id         ID of thirdparty
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 *
	 * @return mixed
	 *
	 * @url GET {id}/categories
	 */
	function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result )
		{
			throw new RestException(404, 'Thirdparty not found');
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'customer', $sortfield, $sortorder, $limit, $page);

		if (is_numeric($result) && $result < 0)
		{
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		if (is_numeric($result) && $result == 0)	// To fix a return of 0 instead of empty array of method getListForItem
		{
			return array();
		}

		return $result;
	}

	/**
	 * Add a customer category to a thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int       $category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url POST {id}/categories/{category_id}
	 */
	function addCategory($id, $category_id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if( ! $result ) {
			throw new RestException(404, 'category not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if( ! DolibarrApi::_checkAccessToResource('category',$category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->company,'customer');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Remove the link between a customer category and the thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int		$category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url DELETE {id}/categories/{category_id}
	 */
	function deleteCategory($id, $category_id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if( ! $result ) {
			throw new RestException(404, 'category not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if( ! DolibarrApi::_checkAccessToResource('category',$category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->company,'customer');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Get supplier categories for a thirdparty
	 *
	 * @param int		$id         ID of thirdparty
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 *
	 * @return mixed
	 *
	 * @url GET {id}/supplier_categories
	 */
	function getSupplierCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result )
		{
			throw new RestException(404, 'Thirdparty not found');
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'supplier', $sortfield, $sortorder, $limit, $page);

		if (is_numeric($result) && $result < 0)
		{
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		if (is_numeric($result) && $result == 0)	// To fix a return of 0 instead of empty array of method getListForItem
		{
			return array();
		}

		return $result;
	}

	/**
	 * Add a supplier category to a thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int       $category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url POST {id}/supplier_categories/{category_id}
	 */
	function addSupplierCategory($id, $category_id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if( ! $result ) {
			throw new RestException(404, 'category not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if( ! DolibarrApi::_checkAccessToResource('category',$category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->company,'supplier');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Remove the link between a category and the thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int		$category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url DELETE {id}/supplier_categories/{category_id}
	 */
	function deleteSupplierCategory($id, $category_id)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if( ! $result ) {
			throw new RestException(404, 'category not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$this->company->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if( ! DolibarrApi::_checkAccessToResource('category',$category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->company,'supplier');

		return $this->_cleanObjectDatas($this->company);
	}


	/**
	 * Get outstanding proposals of thirdparty
	 *
	 * @param 	int 	$id			ID of the thirdparty
	 * @param 	string 	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandingproposals
	 *
	 * @return array  				List of outstandings proposals of thirdparty
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 */
	function getOutStandingProposals($id, $mode='customer')
	{
		$obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingProposals($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}


	/**
	 * Get outstanding orders of thirdparty
	 *
	 * @param 	int 	$id			ID of the thirdparty
	 * @param 	string 	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandingorders
	 *
	 * @return array  				List of outstandings orders of thirdparty
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 */
	function getOutStandingOrder($id, $mode='customer')
	{
		$obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingOrders($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}

	/**
	 * Get outstanding invoices of thirdparty
	 *
	 * @param 	int 	$id			ID of the thirdparty
	 * @param 	string 	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandinginvoices
	 *
	 * @return array  				List of outstandings invoices of thirdparty
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 */
	function getOutStandingInvoices($id, $mode='customer')
	{
		$obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingBills($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}

	/**
	 * Get fixed amount discount of a thirdparty (all sources: deposit, credit note, commercial offers...)
	 *
	 * @param 	int 	$id             ID of the thirdparty
	 * @param 	string 	$filter    	Filter exceptional discount. "none" will return every discount, "available" returns unapplied discounts, "used" returns applied discounts   {@choice none,available,used}
	 * @param   string  $sortfield  	Sort field
	 * @param   string  $sortorder  	Sort order
	 *
	 * @url     GET {id}/fixedamountdiscounts
	 *
	 * @return array  List of fixed discount of thirdparty
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 503
	 */
	function getFixedAmountDiscounts($id, $filter="none", $sortfield = "f.type", $sortorder = 'ASC')
	{
		$obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Thirdparty not found');
		}


		$sql = "SELECT f.ref, f.type as factype, re.fk_facture_source, re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc, re.description, re.fk_facture, re.fk_facture_line";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re, ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.rowid = re.fk_facture_source AND re.fk_soc = ".$id;
		if ($filter == "available")  $sql .= " AND re.fk_facture IS NULL AND re.fk_facture_line IS NULL";
		if ($filter == "used")  $sql .= " AND (re.fk_facture IS NOT NULL OR re.fk_facture_line IS NOT NULL)";

		$sql.= $this->db->order($sortfield, $sortorder);

		$result = $this->db->query($sql);
		if( ! $result ) {
			throw new RestException(503, $this->db->lasterror());
		} else {
			$num = $this->db->num_rows($result);
			while ( $obj = $this->db->fetch_object($result) ) {
				$obj_ret[] = $obj;
			}
		}

		return $obj_ret;
	}



	/**
	 * Return list of invoices qualified to be replaced by another invoice.
	 *
	 * @param int   $id             Id of thirdparty
	 *
	 * @url     GET {id}/getinvoicesqualifiedforreplacement
	 *
	 * @return array
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 405
	 */
	function getInvoicesQualifiedForReplacement($id)
    {
		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}
		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/*$result = $this->thirdparty->fetch($id);
		 if( ! $result ) {
		 throw new RestException(404, 'Thirdparty not found');
		 }*/

		$invoice = new Facture($this->db);
		$result = $invoice->list_replacable_invoices($id);
		if( $result < 0) {
			throw new RestException(405, $this->thirdparty->error);
		}

		return $result;
	}

	/**
	 * Return list of invoices qualified to be corrected by a credit note.
	 * Invoices matching the following rules are returned
	 * (validated + payment on process) or classified (payed completely or payed partialy) + not already replaced + not already a credit note
	 *
	 * @param int   $id             Id of thirdparty
	 *
	 * @url     GET {id}/getinvoicesqualifiedforcreditnote
	 *
	 * @return array
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 405
	 */
	function getInvoicesQualifiedForCreditNote($id)
    {
		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}
		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/*$result = $this->thirdparty->fetch($id);
		 if( ! $result ) {
		 throw new RestException(404, 'Thirdparty not found');
		 }*/

		$invoice = new Facture($this->db);
		$result = $invoice->list_qualified_avoir_invoices($id);
		if( $result < 0) {
			throw new RestException(405, $this->thirdparty->error);
		}

		return $result;
	}

	/**
	 * Get CompanyBankAccount objects for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 *
	 * @return array
	 *
	 * @url GET {id}/bankaccounts
	 */
	function getCompanyBankAccount($id)
    {
		global $db, $conf;

		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}
		if(empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if( ! DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/**
		 * We select all the records that match the socid
		 */

		$sql = "SELECT rowid, fk_soc, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio,";
		$sql.= " owner_address, default_rib, label, datec, tms as datem, rum, frstrecur";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id) $sql.= " WHERE fk_soc  = ".$id." ";


		$result = $db->query($sql);

		if($result->num_rows == 0 ){
			throw new RestException(404, 'Account not found');
		}

		$i=0;

		$accounts =[];

		if ($result)
		{
			$num = $db->num_rows($result);
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				$account = new CompanyBankAccount($db);
				if($account->fetch($obj->rowid)) {
					$accounts[] = $account;
				}
				$i++;
			}
		}
		else{
			throw new RestException(404, 'Account not found');
		}


		$fields = ['socid', 'default_rib', 'frstrecur', '1000110000001', 'datec', 'datem', 'label', 'bank', 'bic', 'iban', 'id', 'rum'];

		$returnAccounts = [];

		foreach($accounts as $account){
			$object= [];
			foreach($account as $key => $value)
				if(in_array($key, $fields)){
					$object[$key] = $value;
				}
			$returnAccounts[] = $object;
		}

		return $returnAccounts;
	}

	/**
	 * Create CompanyBankAccount object for thirdparty
	 * @param int  $id ID of thirdparty
	 * @param array $request_data Request data
	 *
	 * @return object  ID of thirdparty
	 *
	 * @url POST {id}/bankaccounts
	 */
	function createCompanyBankAccount($id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$account = new CompanyBankAccount($this->db);

		$account->socid = $id;

		foreach($request_data as $field => $value) {
			$account->$field = $value;
		}

		if ($account->create(DolibarrApiAccess::$user) < 0)
			throw new RestException(500, 'Error creating Company Bank account');


		if ($account->update(DolibarrApiAccess::$user) < 0)
			throw new RestException(500, 'Error updating values');

		return $account;
	}

	/**
	 * Update CompanyBankAccount object for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int  $bankaccount_id ID of CompanyBankAccount
	 * @param array $request_data Request data
	 *
	 * @return object  ID of thirdparty
	 *
	 * @url PUT {id}/bankaccounts/{bankaccount_id}
	 */
	function updateCompanyBankAccount($id, $bankaccount_id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$account = new CompanyBankAccount($this->db);

		$account->fetch($bankaccount_id, $id, -1, '');

		if($account->socid != $id){
			throw new RestException(401);
		}


		foreach($request_data as $field => $value) {
			$account->$field = $value;
		}

		if ($account->update(DolibarrApiAccess::$user) < 0)
			throw new RestException(500, 'Error updating values');

		return $account;
	}

	/**
	 * Delete a bank account attached to a thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int $bankaccount_id ID of CompanyBankAccount
	 *
	 * @return int -1 if error 1 if correct deletion
	 *
	 * @url DELETE {id}/bankaccounts/{bankaccount_id}
	 */
	function deleteCompanyBankAccount($id, $bankaccount_id)
    {
		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$account = new CompanyBankAccount($this->db);

		$account->fetch($bankaccount_id);

		if(!$account->socid == $id)
			throw new RestException(401);

		return $account->delete(DolibarrApiAccess::$user);
	}

	/**
	 * Generate a Document from a bank account record (like SEPA mandate)
	 *
	 * @param int 		$id 			Thirdparty id
	 * @param int 		$companybankid 	Companybank id
	 * @param string 	$model 			Model of document to generate
	 * @return void
	 *
	 * @url GET {id}/generateBankAccountDocument/{companybankid}/{model}
	 */
	public function generateBankAccountDocument($id, $companybankid = null, $model = 'sepamandate')
	{
		global $conf;

		$this->langs->loadLangs(array("main","dict","commercial","products","companies","banks","bills","withdrawals"));

		$this->company->fetch($id);

		$action = 'builddoc';
		if(! DolibarrApiAccess::$user->rights->societe->creer)
			throw new RestException(401);

		$this->company->setDocModel(DolibarrApiAccess::$user, $model);

		$this->company->fk_bank = $this->company->fk_account;

		$outputlangs = $this->langs;
		$newlang='';

		if ($this->conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
		if ($this->conf->global->MAIN_MULTILANGS && empty($newlang) && isset($this->company->thirdparty->default_lang)) $newlang=$this->company->thirdparty->default_lang;  // for proposal, order, invoice, ...
		if ($this->conf->global->MAIN_MULTILANGS && empty($newlang) && isset($this->company->default_lang)) $newlang=$this->company->default_lang;                  // for thirdparty
		if (! empty($newlang)) {
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}

		// To be sure vars is defined
		$hidedetails = $hidedesc = $hideref = 0;
		$moreparams=null;
		if (empty($hidedetails)) $hidedetails=0;
		if (empty($hidedesc)) $hidedesc=0;
		if (empty($hideref)) $hideref=0;
		if (empty($moreparams)) $moreparams=null;


		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id) $sql.= " WHERE fk_soc  = ".$id." ";
		if ($companybankid) $sql.= " AND id = ".$companybankid."";

		$i=0;
		$accounts=array();

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($result->num_rows == 0) {
				throw new RestException(404, 'Bank account not found');
			}

			$num = $this->db->num_rows($result);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);

				$account = new CompanyBankAccount($this->db);
				if ($account->fetch($obj->rowid)) {
					$accounts[] = $account;
				}
				$i++;
			}
		}
		else
		{
			throw new RestException(404, 'Bank account not found');
		}

		$moreparams = array(
			'use_companybankid'=>$accounts[0]->id,
			'force_dir_output'=>$this->conf->societe->multidir_output[$this->company->entity].'/'.dol_sanitizeFileName($this->company->id)
		);

		$result = 0;

		$result = $this->company->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

		if ($result > 0)
		{
			return array("success" => $result);
		}
		else
		{
			throw new RestException(500);
		}
    }

  /**
	 * Get a specific gateway attached to a thirdparty (by specifying the site key)
	 *
	 * @param int $id ID of thirdparty
	 * @param string $site Site key
	 *
	 * @return SocieteAccount[]
	 * @throws 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 *
	 * @url GET {id}/gateways/
	 */
	function getSocieteAccounts($id, $site=null)
    {
		global $db, $conf;

		if(!DolibarrApiAccess::$user->rights->societe->lire) {
			throw new RestException(401);
		}

		if(!DolibarrApi::_checkAccessToResource('societe',$id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/**
		 * We select all the records that match the socid
		 */
		$sql = "SELECT rowid, fk_soc, key_account, site, date_creation, tms FROM ".MAIN_DB_PREFIX."societe_account";
		$sql.= " WHERE fk_soc = $id";
		if($site) $sql .= " AND site ='$site'";

		$result = $db->query($sql);

		if($result->num_rows == 0){
			throw new RestException(404, 'This thirdparty does not have any gateway attached or does not exist.');
		}

		$i=0;

		$accounts =[];

		$num = $db->num_rows($result);
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$account = new SocieteAccount($db);

			if($account->fetch($obj->rowid)) {
				$accounts[] = $account;
			}
			$i++;
		}

		$fields = ['id', 'fk_soc', 'key_account', 'site', 'date_creation', 'tms'];

		$returnAccounts = [];

		foreach($accounts as $account){
			$object= [];
			foreach($account as $key => $value)
				if(in_array($key, $fields)){
					$object[$key] = $value;
				}
			$returnAccounts[] = $object;
		}

		return $returnAccounts;
	}

	/**
	 * Create and attach a new gateway to an existing thirdparty
	 *
	 * Possible fields for request_data (request body) are specified in <code>llx_societe_account</code> table.<br>
	 * See <a href="https://wiki.dolibarr.org/index.php/Table_llx_societe_account">Table llx_societe_account</a> wiki page for more information<br><br>
	 * <u>Example body payload :</u> <pre>{"key_account": "cus_DAVkLSs1LYyYI", "site": "stripe"}</pre>
	 *
	 * @param int $id ID of thirdparty
	 * @param array $request_data Request data
	 *
	 * @return SocieteAccount
	 * @throws 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws 409 Conflict: A SocieteAccount entity (gateway) already exists for this company and site.
	 * @throws 422 Unprocessable Entity: You must pass the site attribute in your request data !
	 * @throws 500 Internal Server Error: Error creating SocieteAccount account
	 * @status 201
	 *
	 * @url POST {id}/gateways
	 */
	function createSocieteAccount($id, $request_data = null)
	{
		global $db;

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		if(!isset($request_data['site'])) {
			throw new RestException(422, 'Unprocessable Entity: You must pass the site attribute in your request data !');
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".$id." AND site = '". $request_data['site']."' ";
		$result = $db->query($sql);

		if($result->num_rows == 0 ){
			$account = new SocieteAccount($this->db);
			if(!isset($request_data['login'])) {
				$account->login = "";
			}
			$account->fk_soc = $id;

			foreach($request_data as $field => $value) {
				$account->$field = $value;
			}

			if ($account->create(DolibarrApiAccess::$user) < 0)
				throw new RestException(500, 'Error creating SocieteAccount entity. Ensure that the ID of thirdparty provided does exist!');

			$this->_cleanObjectDatas($account);

			return $account;
		} else {
			throw new RestException(409, 'A SocieteAccount entity already exists for this company and site.');
		}
	}

	/**
	 * Create and attach a new (or replace an existing) specific site gateway to a thirdparty
	 *
	 * You <strong>MUST</strong> pass all values to keep (otherwise, they will be deleted) !<br>
	 * If you just need to update specific fields prefer <code>PATCH /thirdparties/{id}/gateways/{site}</code> endpoint.<br><br>
	 * When a <strong>SocieteAccount</strong> entity does not exist for the <code>id</code> and <code>site</code>
	 * supplied, a new one will be created. In that case <code>fk_soc</code> and <code>site</code> members form
	 * request body payload will be ignored and <code>id</code> and <code>site</code> query strings parameters
	 * will be used instead.
	 *
	 * @param int $id ID of thirdparty
	 * @param string $site Site key
	 * @param array $request_data Request data
	 *
	 * @return SocieteAccount
	 * @throws 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws 422 Unprocessable Entity: You must pass the site attribute in your request data !
	 * @throws 500 Internal Server Error: Error updating SocieteAccount entity
	 *
	 * @throws RestException
	 * @url PUT {id}/gateways/{site}
	 */
	function putSocieteAccount($id, $site, $request_data = null)
	{
		global $db;

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid, fk_user_creat, date_creation FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc = $id AND site = '$site' ";
		$result = $db->query($sql);

		// We do not found an existing SocieteAccount entity for this fk_soc and site ; we then create a new one.
		if($result->num_rows == 0 ){
			if(!isset($request_data['key_account'])) {
				throw new RestException(422, 'Unprocessable Entity: You must pass the key_account attribute in your request data !');
			}
			$account = new SocieteAccount($this->db);
			if(!isset($request_data['login'])) {
				$account->login = "";
			}

			foreach($request_data as $field => $value) {
				$account->$field = $value;
			}

			$account->fk_soc = $id;
			$account->site = $site;

			if ($account->create(DolibarrApiAccess::$user) < 0)
				throw new RestException(500, 'Error creating SocieteAccount entity.');
		// We found an existing SocieteAccount entity, we are replacing it
		} else {

			if(isset($request_data['site']) && $request_data['site'] !== $site) {
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".$id." AND site = '". $request_data['site']."' ";
				$result = $db->query($sql);

				if($result->num_rows !== 0)
					throw new RestException(409, "You are trying to update this thirdparty SocieteAccount (gateway record) from $site to ".$request_data['site'] . " but another SocieteAccount entity already exists with this site key.");
			}

			$obj = $db->fetch_object($result);

			$account = new SocieteAccount($this->db);
			$account->id = $obj->rowid;
			$account->fk_soc = $id;
			$account->site = $site;
			if(!isset($request_data['login'])) {
				$account->login = "";
			}
			$account->fk_user_creat = $obj->fk_user_creat;
			$account->date_creation = $obj->date_creation;

			foreach($request_data as $field => $value) {
				$account->$field = $value;
			}

			if ($account->update(DolibarrApiAccess::$user) < 0)
				throw new RestException(500, 'Error updating SocieteAccount entity.');
		}

		$this->_cleanObjectDatas($account);

		return $account;
	}

	/**
	 * Update specified values of a specific site gateway attached to a thirdparty
	 *
	 * @param int $id Id of thirdparty
	 * @param string  $site Site key
	 * @param array $request_data Request data
	 *
	 * @return SocieteAccount
	 * @throws 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws 409 Conflict: Another SocieteAccount entity already exists for this thirdparty with this site key.
	 * @throws 500 Internal Server Error: Error updating SocieteAccount entity
	 *
	 * @url PATCH {id}/gateways/{site}
	 */
	function patchSocieteAccount($id, $site, $request_data = null)
	{
		global $db;

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = $id AND site = '$site' ";
		$result = $db->query($sql);

		if($result->num_rows == 0 ){
			throw new RestException(404, "This thirdparty does not have $site gateway attached or does not exist.");
		} else {

			// If the user tries to edit the site member, we check first if
			if(isset($request_data['site']) && $request_data['site'] !== $site) {
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".$id." AND site = '". $request_data['site']."' ";
				$result = $db->query($sql);

				if($result->num_rows !== 0)
					throw new RestException(409, "You are trying to update this thirdparty SocieteAccount (gateway record) site member from $site to ".$request_data['site'] . " but another SocieteAccount entity already exists for this thirdparty with this site key.");
			}

			$obj = $db->fetch_object($result);
			$account = new SocieteAccount($this->db);
			$account->fetch($obj->rowid);

			foreach($request_data as $field => $value) {
				$account->$field = $value;
			}

			if ($account->update(DolibarrApiAccess::$user) < 0)
				throw new RestException(500, 'Error updating SocieteAccount account');

			$this->_cleanObjectDatas($account);

			return $account;
		}
	}

	/**
	 * Delete a specific site gateway attached to a thirdparty (by gateway id)
	 *
	 * @param int $id ID of thirdparty
	 * @param int $site Site key
	 *
	 * @return void
	 * @throws 401 Unauthorized: User does not have permission to delete thirdparties gateways
	 * @throws 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws 500 Internal Server Error: Error deleting SocieteAccount entity
	 *
	 * @url DELETE {id}/gateways/{site}
	 */
	function deleteSocieteAccount($id, $site)
    {
		global /** @var Database $db */
		$db;

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = $id AND site = '$site' ";
		$result = $db->query($sql);

		if($result->num_rows == 0 ){
			throw new RestException(404);
		} else {
			$obj = $db->fetch_object($result);
			$account = new SocieteAccount($this->db);
			$account->fetch($obj->rowid);

			if($account->delete(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, "Error while deleting $site gateway attached to this third party");
			}
		}
	}

	/**
	 * Delete all gateways attached to a thirdparty
	 *
	 * @param int $id ID of thirdparty
	 *
	 * @return void
	 * @throws 401 Unauthorized: User does not have permission to delete thirdparties gateways
	 * @throws 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws 500 Internal Server Error: Error deleting SocieteAccount entity
	 *
	 * @url DELETE {id}/gateways
	 */
	function deleteSocieteAccounts($id)
    {
		global /** @var Database $db */
		$db;

		if(! DolibarrApiAccess::$user->rights->societe->creer) {
			throw new RestException(401);
		}

		/**
		 * We select all the records that match the socid
		 */

		$sql = "SELECT rowid, fk_soc, key_account, site, date_creation, tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = $id ";

		$result = $db->query($sql);

		if($result->num_rows == 0 ){
			throw new RestException(404, 'This third party does not have any gateway attached or does not exist.');
		} else {
			$i=0;

			$num = $db->num_rows($result);
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				$account = new SocieteAccount($db);
				$account->fetch($obj->rowid);

				if($account->delete(DolibarrApiAccess::$user) < 0) {
					throw new RestException(500, 'Error while deleting gateways attached to this third party');
				}
				$i++;
			}
		}
	}

	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	function _cleanObjectDatas($object)
    {
		$object = parent::_cleanObjectDatas($object);

		unset($object->nom);	// ->name already defined and nom deprecated

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->lines);
		unset($object->thirdparty);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array $data   Datas to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	function _validate($data)
	{
		$thirdparty = array();
		foreach (Thirdparties::$FIELDS as $field) {
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$thirdparty[$field] = $data[$field];
		}
		return $thirdparty;
	}
}
