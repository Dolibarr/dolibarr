<?php
/* Copyright (C) 2026		Michael Wallis			<mh.wallis@gmail.com>
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

/**
 *      \file       htdocs/core/class/globalsearch.class.php
 *      \ingroup    core
 *      \brief      Class to find the records matching a string typed into the global search box
 */


/**
 *	Class to find the records matching a string typed into the global search box.
 *	The sections searched are the ones of the search box (core/ajax/selectsearchbox.php), so the
 *	same permissions, hidden sections and module hooks apply.
 */
class GlobalSearch
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message
	 */
	public $error = '';


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Return the definition of how to search records for each section of the search box.
	 *	Key is the key of the section into the array of core/ajax/selectsearchbox.php.
	 *	The fields searched are the main identifying fields of the list page of the section (line descriptions
	 *	and notes are not searched to keep the answer fast), the list page found with "See all" searches more.
	 *
	 *	@return	array<string,array{table:string,alias:string,element:string,class?:string,classfile?:string,ref:string,label:string,sub?:string,amount?:string,date:string,fields:string[],status?:array<string,string>,thirdparty?:string,nothirdpartyfilter?:int,firstname?:string,private?:int,producttype?:int,url:string,picto:string}>	Definitions
	 */
	public function getDefinitions()
	{
		global $user;

		$definitions = array();

		$definitions['searchintothirdparty'] = array(
			'table' => 'societe', 'alias' => 's', 'element' => 'societe',
			'ref' => 's.nom', 'label' => 's.name_alias', 'sub' => 's.town', 'date' => 's.tms',
			'fields' => array('s.nom', 's.name_alias', 's.code_client', 's.code_fournisseur', 's.email', 's.tva_intra', 's.siren', 's.siret'),
			'thirdparty' => 's.rowid',
			'url' => '/societe/card.php?socid=', 'picto' => 'company',
		);
		$definitions['searchintocontact'] = array(
			'table' => 'socpeople', 'alias' => 'c', 'element' => 'contact',
			'ref' => 'c.lastname', 'firstname' => 'c.firstname', 'label' => 's.nom', 'sub' => 'c.email', 'date' => 'c.tms',
			'fields' => array('c.lastname', 'c.firstname', 'c.email', 'c.phone', 'c.phone_mobile', 's.nom'),
			'thirdparty' => 'c.fk_soc', 'private' => 1,
			'url' => '/contact/card.php?id=', 'picto' => 'contact',
		);
		$definitions['searchintoproduct'] = array(
			'table' => 'product', 'alias' => 'p', 'element' => 'product',
			'ref' => 'p.ref', 'label' => 'p.label', 'date' => 'p.tms',
			'fields' => array('p.ref', 'p.label', 'p.barcode'),
			'url' => '/product/card.php?id=', 'picto' => 'product',
		);
		if (!(isModEnabled('product') && $user->hasRight('produit', 'lire'))) {
			$definitions['searchintoproduct']['producttype'] = 1;	// Services only
		} elseif (!(isModEnabled('service') && $user->hasRight('service', 'lire'))) {
			$definitions['searchintoproduct']['producttype'] = 0;	// Products only
		}
		$definitions['searchintomember'] = array(
			'table' => 'adherent', 'alias' => 'd', 'element' => 'adherent',
			'ref' => 'd.lastname', 'firstname' => 'd.firstname', 'label' => 'd.societe', 'sub' => 'd.ref', 'date' => 'd.tms',
			'fields' => array('d.ref', 'd.login', 'd.lastname', 'd.firstname', 'd.societe', 'd.email'),
			'class' => 'Adherent', 'classfile' => '/adherents/class/adherent.class.php', 'status' => array('d.statut' => 'status'),
			'url' => '/adherents/card.php?rowid=', 'picto' => 'member',
		);
		$definitions['searchintoprojects'] = array(
			'table' => 'projet', 'alias' => 'p', 'element' => 'project',
			'ref' => 'p.ref', 'label' => 'p.title', 'sub' => 's.nom', 'date' => 'p.tms',
			'fields' => array('p.ref', 'p.title', 's.nom', 's.name_alias'),
			'class' => 'Project', 'classfile' => '/projet/class/project.class.php', 'status' => array('p.fk_statut' => 'status'),
			'thirdparty' => 'p.fk_soc', 'nothirdpartyfilter' => 1,		// Visibility of projects is managed by getProjectsAuthorizedForUser()
			'url' => '/projet/card.php?id=', 'picto' => 'project',
		);
		$definitions['searchintopropal'] = array(
			'table' => 'propal', 'alias' => 'p', 'element' => 'propal',
			'ref' => 'p.ref', 'label' => 's.nom', 'sub' => 'p.ref_client', 'amount' => 'p.total_ttc', 'date' => 'p.datep',
			'fields' => array('p.ref', 'p.ref_client', 's.nom', 's.name_alias', 's.code_client'),
			'class' => 'Propal', 'classfile' => '/comm/propal/class/propal.class.php', 'status' => array('p.fk_statut' => 'status'),
			'thirdparty' => 'p.fk_soc',
			'url' => '/comm/propal/card.php?id=', 'picto' => 'propal',
		);
		$definitions['searchintoorder'] = array(
			'table' => 'commande', 'alias' => 'c', 'element' => 'commande',
			'ref' => 'c.ref', 'label' => 's.nom', 'sub' => 'c.ref_client', 'amount' => 'c.total_ttc', 'date' => 'c.date_commande',
			'fields' => array('c.ref', 'c.ref_client', 's.nom', 's.name_alias', 's.code_client'),
			'class' => 'Commande', 'classfile' => '/commande/class/commande.class.php', 'status' => array('c.fk_statut' => 'status', 'c.facture' => 'billed'),
			'thirdparty' => 'c.fk_soc',
			'url' => '/commande/card.php?id=', 'picto' => 'order',
		);
		$definitions['searchintoshipment'] = array(
			'table' => 'expedition', 'alias' => 'e', 'element' => 'expedition',
			'ref' => 'e.ref', 'label' => 's.nom', 'sub' => 'e.tracking_number', 'date' => 'e.date_creation',
			'fields' => array('e.ref', 'e.tracking_number', 's.nom', 's.name_alias'),
			'class' => 'Expedition', 'classfile' => '/expedition/class/expedition.class.php', 'status' => array('e.fk_statut' => 'status', 'e.billed' => 'billed'),
			'thirdparty' => 'e.fk_soc',
			'url' => '/expedition/card.php?id=', 'picto' => 'dolly',
		);
		$definitions['searchintoinvoice'] = array(
			'table' => 'facture', 'alias' => 'f', 'element' => 'invoice',
			'ref' => 'f.ref', 'label' => 's.nom', 'sub' => 'f.ref_client', 'amount' => 'f.total_ttc', 'date' => 'f.datef',
			'fields' => array('f.ref', 'f.ref_client', 's.nom', 's.name_alias', 's.code_client'),
			'class' => 'Facture', 'classfile' => '/compta/facture/class/facture.class.php', 'status' => array('f.fk_statut' => 'status', 'f.paye' => 'paye', 'f.type' => 'type'),
			'thirdparty' => 'f.fk_soc',
			'url' => '/compta/facture/card.php?id=', 'picto' => 'bill',
		);
		$definitions['searchintocontract'] = array(
			'table' => 'contrat', 'alias' => 'c', 'element' => 'contract',
			'ref' => 'c.ref', 'label' => 's.nom', 'sub' => 'c.ref_customer', 'date' => 'c.datec',
			'fields' => array('c.ref', 'c.ref_customer', 'c.ref_supplier', 's.nom', 's.name_alias'),
			'thirdparty' => 'c.fk_soc',
			'url' => '/contrat/card.php?id=', 'picto' => 'contract',
		);
		$definitions['searchintointervention'] = array(
			'table' => 'fichinter', 'alias' => 'f', 'element' => 'intervention',
			'ref' => 'f.ref', 'label' => 's.nom', 'sub' => 'f.ref_client', 'date' => 'f.datec',
			'fields' => array('f.ref', 'f.ref_client', 's.nom', 's.name_alias'),
			'class' => 'Fichinter', 'classfile' => '/fichinter/class/fichinter.class.php', 'status' => array('f.fk_statut' => 'status'),
			'thirdparty' => 'f.fk_soc',
			'url' => '/fichinter/card.php?id=', 'picto' => 'intervention',
		);
		$definitions['searchintosupplierpropal'] = array(
			'table' => 'supplier_proposal', 'alias' => 'p', 'element' => 'supplier_proposal',
			'ref' => 'p.ref', 'label' => 's.nom', 'amount' => 'p.total_ttc', 'date' => 'p.datec',
			'fields' => array('p.ref', 's.nom', 's.name_alias', 's.code_fournisseur'),
			'class' => 'SupplierProposal', 'classfile' => '/supplier_proposal/class/supplier_proposal.class.php', 'status' => array('p.fk_statut' => 'status'),
			'thirdparty' => 'p.fk_soc',
			'url' => '/supplier_proposal/card.php?id=', 'picto' => 'supplier_proposal',
		);
		$definitions['searchintosupplierorder'] = array(
			'table' => 'commande_fournisseur', 'alias' => 'c', 'element' => 'supplier_order',
			'ref' => 'c.ref', 'label' => 's.nom', 'sub' => 'c.ref_supplier', 'amount' => 'c.total_ttc', 'date' => 'c.date_creation',
			'fields' => array('c.ref', 'c.ref_supplier', 's.nom', 's.name_alias', 's.code_fournisseur'),
			'class' => 'CommandeFournisseur', 'classfile' => '/fourn/class/fournisseur.commande.class.php', 'status' => array('c.fk_statut' => 'status', 'c.billed' => 'billed'),
			'thirdparty' => 'c.fk_soc',
			'url' => '/fourn/commande/card.php?id=', 'picto' => 'supplier_order',
		);
		$definitions['searchintoreception'] = array(
			'table' => 'reception', 'alias' => 'e', 'element' => 'reception',
			'ref' => 'e.ref', 'label' => 's.nom', 'sub' => 'e.ref_supplier', 'date' => 'e.date_creation',
			'fields' => array('e.ref', 'e.ref_supplier', 's.nom', 's.name_alias'),
			'class' => 'Reception', 'classfile' => '/reception/class/reception.class.php', 'status' => array('e.fk_statut' => 'status', 'e.billed' => 'billed'),
			'thirdparty' => 'e.fk_soc',
			'url' => '/reception/card.php?id=', 'picto' => 'dolly',
		);
		$definitions['searchintosupplierinvoice'] = array(
			'table' => 'facture_fourn', 'alias' => 'f', 'element' => 'facture_fourn',
			'ref' => 'f.ref', 'label' => 's.nom', 'sub' => 'f.ref_supplier', 'amount' => 'f.total_ttc', 'date' => 'f.datef',
			'fields' => array('f.ref', 'f.ref_supplier', 's.nom', 's.name_alias', 's.code_fournisseur'),
			'class' => 'FactureFournisseur', 'classfile' => '/fourn/class/fournisseur.facture.class.php', 'status' => array('f.fk_statut' => 'status', 'f.paye' => 'paye', 'f.type' => 'type'),
			'thirdparty' => 'f.fk_soc',
			'url' => '/fourn/facture/card.php?facid=', 'picto' => 'supplier_invoice',
		);

		return $definitions;
	}

	/**
	 *	Search records matching a string into each section of the search box. All sections are returned, with 0 records if none match.
	 *
	 *	@param	string		$search			String to search
	 *	@param	array<string,array{position?:int,img?:string,label:string,text:string,url:string}>	$arraysections	Sections of the search box (as returned by core/ajax/selectsearchbox.php)
	 *	@param	int			$limit			Max number of records returned for each section
	 *	@return	array{sections:array<int,array{key:string,label:string,picto:string,url:string,total:int,records:array<int,array{url:string,picto:string,ref:string,label:string,sub:string,amount:string,status:string}>}>}
	 */
	public function search($search, $arraysections, $limit = 5)
	{
		global $hookmanager;

		$definitions = $this->getDefinitions();

		// Allow external modules to add definitions for the sections they add with the hook addSearchEntry
		$parameters = array('search' => $search, 'definitions' => $definitions);
		$reshook = $hookmanager->executeHooks('addSearchRecordsDefinitions', $parameters);
		if (empty($reshook)) {
			$definitions = array_merge($definitions, $hookmanager->resArray);
		} elseif ($reshook > 0) {
			$definitions = $hookmanager->resArray;
		}

		$result = array('sections' => array());

		foreach ($arraysections as $key => $section) {
			$found = array();
			$total = 0;
			if (!empty($definitions[$key])) {
				$total = $this->searchSection($definitions[$key], $search, $limit, $found);
				if ($total < 0) {
					// Do not break the whole search box because of one section
					dol_syslog(get_class($this)."::search error on section ".$key." ".$this->error, LOG_WARNING);
					$total = 0;
				}
			}
			// Sections without record are also returned, so the box shows them in the same order with a link to their list
			$result['sections'][] = array(
				'key' => $key,
				'label' => $section['label'],
				'picto' => img_picto('', empty($section['img']) ? 'generic' : $section['img'], 'class="pictofixedwidth"'),
				'url' => $section['url'],
				'total' => $total,
				'records' => $found,
			);
		}

		return $result;
	}

	/**
	 *	Search records of one section
	 *
	 *	@param	array{table:string,alias:string,element:string,class?:string,classfile?:string,ref:string,label:string,sub?:string,amount?:string,date:string,fields:string[],status?:array<string,string>,thirdparty?:string,nothirdpartyfilter?:int,firstname?:string,private?:int,producttype?:int,url:string,picto:string}	$def	Definition of section
	 *	@param	string		$search		String to search
	 *	@param	int			$limit		Max number of records to return
	 *	@param	array<int,array{url:string,picto:string,ref:string,label:string,sub:string,amount:string,status:string}>	$found	Records found
	 *	@return	int						Total number of records matching, <0 if error
	 */
	public function searchSection($def, $search, $limit, &$found)
	{
		global $conf, $langs, $user;

		// Names of table and fields of the definition (that may come from a module) are sanitized: only "alias.field" is allowed
		$sanitizedtable = $this->db->sanitize($def['table']);
		$sanitizedalias = $this->db->sanitize($def['alias']);
		$sanitizedref = $this->db->sanitize($def['ref']);
		$sanitizedlabel = $this->db->sanitize($def['label']);
		$sanitizedsub = (empty($def['sub']) ? '' : $this->db->sanitize($def['sub']));
		$sanitizedfirstname = (empty($def['firstname']) ? '' : $this->db->sanitize($def['firstname']));
		$sanitizedamount = (empty($def['amount']) ? '' : $this->db->sanitize($def['amount']));
		$sanitizeddate = $this->db->sanitize($def['date']);
		$sanitizedthirdparty = (empty($def['thirdparty']) ? '' : $this->db->sanitize($def['thirdparty']));

		$from = " FROM ".$this->db->prefix().$sanitizedtable." as ".$sanitizedalias;
		if ($sanitizedthirdparty && $sanitizedthirdparty != 's.rowid') {
			$from .= " LEFT JOIN ".$this->db->prefix()."societe as s ON ".$sanitizedthirdparty." = s.rowid";
		}

		$where = " WHERE ".$sanitizedalias.".entity IN (".getEntity($def['element']).")";
		$where .= natural_search($def['fields'], $search);
		// Same restrictions than on the list pages
		if (!empty($def['private'])) {
			$where .= " AND (".$sanitizedalias.".priv = 0 OR (".$sanitizedalias.".priv = 1 AND ".$sanitizedalias.".fk_user_creat = ".((int) $user->id)."))";
		}
		if (isset($def['producttype'])) {
			$where .= " AND ".$sanitizedalias.".fk_product_type = ".((int) $def['producttype']);
		}
		if ($sanitizedthirdparty && empty($def['nothirdpartyfilter'])) {
			if (!empty($user->socid)) {
				$where .= " AND ".$sanitizedthirdparty." = ".((int) $user->socid);
			} elseif (!$user->hasRight('societe', 'client', 'voir')) {
				$where .= " AND EXISTS (SELECT sc.rowid FROM ".$this->db->prefix()."societe_commerciaux as sc WHERE ".$sanitizedthirdparty." = sc.fk_soc AND sc.fk_user = ".((int) $user->id).")";
			}
		}
		if ($def['element'] == 'project' && !$user->hasRight('projet', 'all', 'lire')) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$projectstatic = new Project($this->db);
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, (int) $user->socid);
			$where .= " AND ".$sanitizedalias.".rowid IN (".$this->db->sanitize($projectsListId ? $projectsListId : '0').")";
		}

		$sql = "SELECT ".$sanitizedalias.".rowid as id, ".$sanitizedref." as ref, ".$sanitizedlabel." as label";
		$sql .= ", ".($sanitizedsub ? $sanitizedsub : "''")." as sub";
		if ($sanitizedfirstname) {
			$sql .= ", ".$sanitizedfirstname." as firstname";
		}
		if ($sanitizedamount) {
			$sql .= ", ".$sanitizedamount." as amount";
		}
		if (!empty($def['status'])) {
			foreach ($def['status'] as $field => $property) {
				$sql .= ", ".$this->db->sanitize($field)." as status_".$this->db->sanitize($property);
			}
		}
		$sql .= $from.$where;
		// Exact ref first, then refs starting with the string, then most recent
		$sql .= " ORDER BY CASE WHEN ".$sanitizedref." = '".$this->db->escape($search)."' THEN 0 WHEN ".$sanitizedref." LIKE '".$this->db->escape($this->db->escapeforlike($search))."%' THEN 1 ELSE 2 END, ".$sanitizeddate." DESC";
		$sql .= $this->db->plimit($limit + 1);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$staticobject = null;
		if (!empty($def['class']) && !empty($def['classfile'])) {
			include_once DOL_DOCUMENT_ROOT.$def['classfile'];
			if (class_exists($def['class'])) {
				$staticobject = new $def['class']($this->db);
			}
		}

		$num = $this->db->num_rows($resql);
		$i = 0;
		while ($i < min($num, $limit)) {
			$obj = $this->db->fetch_object($resql);

			$status = '';
			if (is_object($staticobject) && method_exists($staticobject, 'getLibStatut')) {
				foreach ($def['status'] as $field => $property) {
					$staticobject->$property = $obj->{'status_'.$this->db->sanitize($property)};
					if ($property == 'status') {
						$staticobject->statut = $obj->status_status;	// For classes still using the old property
					}
				}
				$status = (string) call_user_func(array($staticobject, 'getLibStatut'), 5);	// The class comes from the definition, method_exists() checked above
			}

			$found[] = array(
				'url' => DOL_URL_ROOT.$def['url'].((int) $obj->id),
				'picto' => img_picto('', $def['picto'], 'class="pictofixedwidth"'),
				'ref' => trim((isset($obj->firstname) ? $obj->firstname.' ' : '').$obj->ref),
				'label' => (string) $obj->label,
				'sub' => (string) $obj->sub,
				'amount' => (isset($obj->amount) ? price($obj->amount, 0, $langs, 1, -1, -1, $conf->currency) : ''),
				'status' => $status,
			);
			$i++;
		}
		$this->db->free($resql);

		$total = $num;
		if ($num > $limit) {
			$sql = "SELECT COUNT(".$sanitizedalias.".rowid) as nb".$from.$where;
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$total = (int) $obj->nb;
				$this->db->free($resql);
			}
		}

		return $total;
	}
}
