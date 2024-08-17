<?php
/* Copyright (C) 2015       ATM Consulting          <support@atm-consulting.fr>
 * Copyright (C) 2019-2020  Open-DSI                <support@open-dsi.fr>
 * Copyright (C) 2020-2024  Frédéric France         <frederic.france@free.fr>
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

/**
 *    \file       htdocs/intracommreport/class/intracommreport.class.php
 *    \ingroup    Intracomm report
 *    \brief      File of class to manage intracomm report
 */


require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class to manage intracomm report
 */
class IntracommReport extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'intracommreport';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'intracommreport';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_intracommreport';

	/**
	 * @var string 	String with name of icon for intracommreport. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'intracommreport@monmodule' if picto is file 'img/object_intracommreport.png'.
	 */
	public $picto = 'intracommreport';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'length' the length of field. Example: 255, '24,8'
	 *  'label' the translation key.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>"1", 'position'=>10, 'notnull'=>1, "visible"=>"0",),
		"ref" => array("type"=>"varchar(30)", "label"=>"Ref", "enabled"=>"1", 'position'=>15, 'notnull'=>1, "visible"=>"1", "csslist"=>"tdoverflowmax150", "showoncombobox"=>"1",),
		"type_declaration" => array("type"=>"varchar(32)", "label"=>"TypeOfDeclaration", "enabled"=>"1", 'position'=>25, 'notnull'=>0, "visible"=>"1", 'arrayofkeyval' => array("deb" => "DEB", "des" => "DES")),
		"periods" => array("type"=>"varchar(32)", "label"=>"Periods", "enabled"=>"1", 'position'=>30, 'notnull'=>0, "visible"=>"-1",),
		"mode" => array("type"=>"varchar(32)", "label"=>"Mode", "enabled"=>"1", 'position'=>35, 'notnull'=>0, "visible"=>"-1",),
		"content_xml" => array("type"=>"text", "label"=>"Contentxml", "enabled"=>"1", 'position'=>40, 'notnull'=>0, "visible"=>"-1",),
		"type_export" => array("type"=>"varchar(10)", "label"=>"TypeOfExport", "enabled"=>"1", 'position'=>45, 'notnull'=>0, "visible"=>"-1", 'arrayofkeyval' => array("in" => "Input", "out" => "Output")),
		"datec" => array("type"=>"datetime", "label"=>"DateCreation", "enabled"=>"1", 'position'=>50, 'notnull'=>0, "visible"=>"-1",),
		"tms" => array("type"=>"timestamp", "label"=>"DateModification", "enabled"=>"1", 'position'=>55, 'notnull'=>1, "visible"=>"-1",),
	);
	public $rowid;
	public $ref;
	public $type_declaration;
	public $periods;
	public $mode;
	public $content_xml;
	public $type_export;
	public $datec;
	public $tms;
	// END MODULEBUILDER PROPERTIES


	/**
	 * @var string ref ???
	 */
	public $label;

	public $period;

	public $declaration;

	/**
	 * @var string declaration number
	 */
	public $declaration_number;

	/**
	 * @var string
	 */
	public $numero_declaration;


	/**
	 * DEB - Product
	 */
	const TYPE_DEB = 0;

	/**
	 * DES - Service
	 */
	const TYPE_DES = 1;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('intracommreport', 'myobject', 'read')) {
		 $this->fields['myfield']['visible'] = 1;
		 $this->fields['myfield']['noteditable'] = 0;
		 }*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}

		$this->type_export = 'deb';
	}

	/**
	 * Function create
	 *
	 * @param 	User 	$user 		User
	 * @param 	int 	$notrigger 	notrigger
	 * @return 	int
	 */
	public function create($user, $notrigger = 0)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Function fetch
	 *
	 * @param 	int 	$id 			object ID
	 * @param 	string 	$ref  			Ref
	 * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int		$nolines		0=Default to load extrafields, 1=No extrafields
	 * @return 	int     				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, '', $noextrafields);
		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		return $result;
	}

	/**
	 * Function delete
	 *
	 * @param 	User 	$user 		User
	 * @param 	int 	$notrigger 	notrigger
	 * @return 	int
	 */
	public function delete($user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 * Generate XML file
	 *
	 * @param string		$mode 				'O' for create, R for regenerate (Look always 0 meant toujours 0 within the framework of XML exchanges according to documentation)
	 * @param string		$type 				Declaration type by default - introduction or expedition (always 'expedition' for Des)
	 * @param string		$period_reference	Period of reference
	 * @return string|false						Return a well-formed XML string based on SimpleXML element, false or 0 if error
	 */
	public function getXML($mode = 'O', $type = 'introduction', $period_reference = '')
	{
		global $conf, $mysoc;

		/**************Construction de quelques variables********************/
		$party_id = substr(strtr($mysoc->tva_intra, array(' ' => '')), 0, 4).$mysoc->idprof2;
		$declarant = substr($mysoc->managers, 0, 14);
		$id_declaration = self::getDeclarationNumber($this->numero_declaration);
		/********************************************************************/

		/**************Construction du fichier XML***************************/
		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><INSTAT></INSTAT>');

		$envelope = $e->addChild('Envelope');
		$envelope->addChild('envelopeId', $conf->global->INTRACOMMREPORT_NUM_AGREMENT);
		$date_time = $envelope->addChild('DateTime');
		$date_time->addChild('date', date('Y-m-d'));
		$date_time->addChild('time', date('H:i:s'));
		$party = $envelope->addChild('Party');
		$party->addAttribute('partyType', $conf->global->INTRACOMMREPORT_TYPE_ACTEUR);
		$party->addAttribute('partyRole', $conf->global->INTRACOMMREPORT_ROLE_ACTEUR);
		$party->addChild('partyId', $party_id);
		$party->addChild('partyName', $declarant);
		$envelope->addChild('softwareUsed', 'Dolibarr');
		$declaration = $envelope->addChild('Declaration');
		$declaration->addChild('declarationId', $id_declaration);
		$declaration->addChild('referencePeriod', $period_reference);
		if (getDolGlobalString('INTRACOMMREPORT_TYPE_ACTEUR') === 'PSI') {
			$psiId = $party_id;
		} else {
			$psiId = 'NA';
		}
		$declaration->addChild('PSIId', $psiId);
		$function = $declaration->addChild('Function');
		$functionCode = $function->addChild('functionCode', $mode);
		$declaration->addChild('declarationTypeCode', getDolGlobalString('INTRACOMMREPORT_NIV_OBLIGATION_'.strtoupper($type)));
		$declaration->addChild('flowCode', ($type == 'introduction' ? 'A' : 'D'));
		$declaration->addChild('currencyCode', $conf->global->MAIN_MONNAIE);
		/********************************************************************/

		/**************Ajout des lignes de factures**************************/
		$res = $this->addItemsFact($declaration, $type, $period_reference);
		/********************************************************************/

		$this->errors = array_unique($this->errors);

		if (!empty($res)) {
			return $e->asXML();
		} else {
			return false;
		}
	}

	/**
	 * Generate XMLDes file
	 *
	 * @param int		$period_year		Year of declaration
	 * @param int		$period_month		Month of declaration
	 * @param string	$type_declaration	Declaration type by default - 'introduction' or 'expedition' (always 'expedition' for Des)
	 * @return string|false					Return a well-formed XML string based on SimpleXML element, false or 0 if error
	 */
	public function getXMLDes($period_year, $period_month, $type_declaration = 'expedition')
	{
		global $mysoc;

		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><fichier_des></fichier_des>');

		$declaration_des = $e->addChild('declaration_des');
		$declaration_des->addChild('num_des', self::getDeclarationNumber($this->numero_declaration));
		$declaration_des->addChild('num_tvaFr', $mysoc->tva_intra); // /^FR[a-Z0-9]{2}[0-9]{9}$/  // Doit faire 13 caractères
		$declaration_des->addChild('mois_des', (string) $period_month);
		$declaration_des->addChild('an_des', (string) $period_year);

		// Add invoice lines
		$res = $this->addItemsFact($declaration_des, $type_declaration, $period_year.'-'.$period_month, 'des');

		$this->errors = array_unique($this->errors);

		if (!empty($res)) {
			return $e->asXML();
		} else {
			return false;
		}
	}

	/**
	 *  Add line from invoice
	 *
	 *  @param	SimpleXMLElement	$declaration		Reference declaration
	 *  @param	string				$type				Declaration type by default - 'introduction' or 'expedition' (always 'expedition' for Des)
	 *  @param	int					$period_reference	Reference period
	 *  @param	string				$exporttype	    	'deb' for DEB, 'des' for DES
	 *  @return	int       			  					Return integer <0 if KO, >0 if OK
	 */
	public function addItemsFact(&$declaration, $type, $period_reference, $exporttype = 'deb')
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$sql = $this->getSQLFactLines($type, $period_reference, $exporttype);

		$resql = $this->db->query($sql);

		if ($resql) {
			$i = 1;

			if ($this->db->num_rows($resql) <= 0) {
				$this->errors[] = 'No data for this period';
				return 0;
			}

			if ($exporttype == 'deb' && getDolGlobalInt('INTRACOMMREPORT_CATEG_FRAISDEPORT') > 0) {
				$categ_fraisdeport = new Categorie($this->db);
				$categ_fraisdeport->fetch(getDolGlobalString('INTRACOMMREPORT_CATEG_FRAISDEPORT'));
				$TLinesFraisDePort = array();
			}

			while ($res = $this->db->fetch_object($resql)) {
				if ($exporttype == 'des') {
					$this->addItemXMlDes($declaration, $res, $i);
				} else {
					if (empty($res->fk_pays)) {
						// We don't stop the loop because we want to know all the third parties who don't have an informed country
						$this->errors[] = 'Country not filled in for the third party <a href="'.dol_buildpath('/societe/soc.php', 1).'?socid='.$res->id_client.'">'.$res->nom.'</a>';
					} else {
						if (getDolGlobalInt('INTRACOMMREPORT_CATEG_FRAISDEPORT') > 0 && $categ_fraisdeport->containsObject('product', $res->id_prod)) {
							$TLinesFraisDePort[] = $res;
						} else {
							$this->addItemXMl($declaration, $res, $i, '');
						}
					}
				}

				$i++;
			}

			if (!empty($TLinesFraisDePort)) {
				$this->addItemFraisDePort($declaration, $TLinesFraisDePort, $type, $categ_fraisdeport, $i);
			}

			if (count($this->errors) > 0) {
				return 0;
			}
		}

		return 1;
	}

	/**
	 *  Add invoice line
	 *
	 *  @param      string	$type				Declaration type by default - introduction or expedition (always 'expedition' for Des)
	 *  @param      int		$period_reference	Reference declaration
	 *  @param      string	$exporttype	    	deb=DEB, des=DES
	 *  @return     string       			  	Return integer <0 if KO, >0 if OK
	 */
	public function getSQLFactLines($type, $period_reference, $exporttype = 'deb')
	{
		global $mysoc, $conf;

		if ($type == 'expedition' || $exporttype == 'des') {
			$sql = "SELECT f.ref as refinvoice, f.total_ht";
			$table = 'facture';
			$table_extraf = 'facture_extrafields';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
		} else { // Introduction
			$sql = "SELECT f.ref_supplier as refinvoice, f.total_ht";
			$table = 'facture_fourn';
			$table_extraf = 'facture_fourn_extrafields';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
		}
		$sql .= ", l.fk_product, l.qty
				, p.weight, p.rowid as id_prod, p.customcode
				, s.rowid as id_client, s.nom, s.zip, s.fk_pays, s.tva_intra
				, c.code
				, ext.mode_transport
				FROM ".MAIN_DB_PREFIX.$tabledet." l
				INNER JOIN ".MAIN_DB_PREFIX.$table." f ON (f.rowid = l.".$this->db->escape($field_link).")
				LEFT JOIN ".MAIN_DB_PREFIX.$table_extraf." ext ON (ext.fk_object = f.rowid)
				INNER JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = l.fk_product)
				INNER JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = f.fk_soc)
				LEFT JOIN ".MAIN_DB_PREFIX."c_country c ON (c.rowid = s.fk_pays)
				WHERE f.fk_statut > 0
				AND l.product_type = ".($exporttype == "des" ? 1 : 0)."
				AND f.entity = ".((int) $conf->entity)."
				AND (s.fk_pays <> ".((int) $mysoc->country_id)." OR s.fk_pays IS NULL)
				AND f.datef BETWEEN '".$this->db->escape($period_reference)."-01' AND '".$this->db->escape($period_reference)."-".date('t')."'";

		return $sql;
	}

	/**
	 *	Add item for DEB
	 *
	 * 	@param	SimpleXMLElement	$declaration		Reference declaration
	 * 	@param	stdClass			$res				Result of request SQL
	 *  @param	int					$i					Line Id
	 * 	@param	string				$code_douane_spe	Specific customs authorities code
	 *  @return	void
	 */
	public function addItemXMl(&$declaration, &$res, $i, $code_douane_spe = '')
	{
		$item = $declaration->addChild('Item');
		$item->addChild('itemNumber', (string) $i);
		$cn8 = $item->addChild('CN8');
		if (empty($code_douane_spe)) {
			$code_douane = $res->customcode;
		} else {
			$code_douane = $code_douane_spe;
		}
		$cn8->addChild('CN8Code', $code_douane);
		$item->addChild('MSConsDestCode', $res->code); // code iso pays client
		$item->addChild('countryOfOriginCode', substr($res->zip, 0, 2)); // code iso pays d'origine
		$item->addChild('netMass', (string) round($res->weight * $res->qty)); // Poids du produit
		$item->addChild('quantityInSU', (string) $res->qty); // Quantité de produit dans la ligne
		$item->addChild('invoicedAmount', (string) round($res->total_ht)); // Montant total ht de la facture (entier attendu)
		// $item->addChild('invoicedNumber', $res->refinvoice); // Numéro facture
		if (!empty($res->tva_intra)) {
			$item->addChild('partnerId', $res->tva_intra);
		}
		$item->addChild('statisticalProcedureCode', '11');
		$nature_of_transaction = $item->addChild('NatureOfTransaction');
		$nature_of_transaction->addChild('natureOfTransactionACode', '1');
		$nature_of_transaction->addChild('natureOfTransactionBCode', '1');
		$item->addChild('modeOfTransportCode', $res->mode_transport);
		$item->addChild('regionCode', substr($res->zip, 0, 2));
	}

	/**
	 *	Add item for DES
	 *
	 * 	@param	SimpleXMLElement	$declaration		Reference declaration
	 * 	@param	stdClass			$res				Result of request SQL
	 *  @param	int					$i					Line Id
	 *  @return	void
	 */
	public function addItemXMlDes($declaration, &$res, $i)
	{
		$item = $declaration->addChild('ligne_des');
		$item->addChild('numlin_des', (string) $i);
		$item->addChild('valeur', (string) round($res->total_ht)); // Total amount excl. tax of the invoice (whole amount expected)
		$item->addChild('partner_des', $res->tva_intra); // Represents the foreign customer's VAT number
	}

	/**
	 *	This function adds an item by retrieving the customs code of the product with the highest amount in the invoice
	 *
	 * 	@param	SimpleXMLElement	$declaration		Reference declaration
	 * 	@param	array				$TLinesFraisDePort	Data of shipping costs line
	 *  @param	string	    		$type				Declaration type by default - introduction or expedition (always 'expedition' for Des)
	 *  @param	Categorie			$categ_fraisdeport	category of shipping costs
	 *  @param	int		    		$i					Line Id
	 *  @return	void
	 */
	public function addItemFraisDePort(&$declaration, &$TLinesFraisDePort, $type, &$categ_fraisdeport, $i)
	{
		global $conf;

		if ($type == 'expedition') {
			$table = 'facture';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
			$more_sql = 'f.ref';
		} else { // Introduction
			$table = 'facture_fourn';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
			$more_sql = 'f.ref_supplier';
		}

		foreach ($TLinesFraisDePort as $res) {
			$sql = "SELECT p.customcode
					FROM ".MAIN_DB_PREFIX.$tabledet." d
					INNER JOIN ".MAIN_DB_PREFIX.$table." f ON (f.rowid = d.".$this->db->escape($field_link).")
					INNER JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = d.fk_product)
					WHERE d.fk_product IS NOT NULL
					AND f.entity = ".((int) $conf->entity)."
					AND ".$more_sql." = '".$this->db->escape($res->refinvoice)."'
					AND d.total_ht =
					(
						SELECT MAX(d.total_ht)
						FROM ".MAIN_DB_PREFIX.$tabledet." d
						INNER JOIN ".MAIN_DB_PREFIX.$table." f ON (f.rowid = d.".$this->db->escape($field_link).")
						WHERE d.fk_product IS NOT NULL
						AND ".$more_sql." = '".$this->db->escape($res->refinvoice)."'
						AND d.fk_product NOT IN
						(
							SELECT fk_product
							FROM ".MAIN_DB_PREFIX."categorie_product
							WHERE fk_categorie = ".((int) $categ_fraisdeport->id)."
						)
					)";

			$resql = $this->db->query($sql);
			$ress = $this->db->fetch_object($resql);

			$this->addItemXMl($declaration, $res, $i, $ress->customcode);

			$i++;
		}
	}

	/**
	 *	Return next reference of declaration not already used (or last reference)
	 *
	 *	@return    string					free ref or last ref
	 */
	public function getNextDeclarationNumber()
	{
		$sql = "SELECT MAX(numero_declaration) as max_declaration_number";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE exporttype = '".$this->db->escape($this->type_export)."'";
		$resql = $this->db->query($sql);
		if ($resql) {
			$res = $this->db->fetch_object($resql);
		}

		return (string) ($res->max_declaration_number + 1);
	}

	/**
	 *	Verify declaration number. Positive integer of a maximum of 6 characters recommended by the documentation
	 *
	 *	@param     	string		$number		Number to verify / convert
	 *	@return		string 				Number
	 */
	public static function getDeclarationNumber($number)
	{
		return str_pad($number, 6, '0', STR_PAD_LEFT);
	}

	/**
	 *	Generate XML file
	 *
	 *  @param		string		$content_xml	Content
	 *	@return		void
	 */
	public function generateXMLFile($content_xml)
	{
		$name = $this->period.'.xml';

		// TODO Must be stored into a dolibarr temp directory
		$fname = sys_get_temp_dir().'/'.$name;

		$f = fopen($fname, 'w+');
		fwrite($f, $content_xml);
		fclose($f);

		header('Content-Description: File Transfer');
		header('Content-Type: application/xml');
		header('Content-Disposition: attachment; filename="'.$name.'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize($fname));

		readfile($fname);
		exit;
	}


	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = dol_buildpath('/intracommreport/card.php', 1).'?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array($this->element.'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Return a thumb for kanban views
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">'.$this->label.'</div>';
		}
		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br>';
			$return .= '<span class="info-box-label amount">'.price($this->amount, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}
}
