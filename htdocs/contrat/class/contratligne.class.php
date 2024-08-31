<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Destailleur Laurent		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2008		Raphael Bertrand		<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2016	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018   	Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2015-2018	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/contrat/class/contratligne.class.php
 *	\ingroup    contrat
 *	\brief      File of class to manage contract lines
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

/**
 *	Class to manage lines of contracts
 */
class ContratLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contratdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'contratdet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'contrat';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_contrat';

	/**
	 * @var string 	Name to use for 'features' parameter to check module permissions user->rights->feature with restrictedArea().
	 * 				Undefined means same value than $element. Can be use to force a check on another element for example for class of line, we mention here the parent element.
	 */
	public $element_for_permission = 'contrat';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var int ID
	 */
	public $fk_contrat;

	/**
	 * @var int ID
	 */
	public $fk_product;

	/**
	 * @var int 0 inactive, 4 active, 5 closed
	 */
	public $statut;

	/**
	 * @var int 0 for product, 1 for service
	 */
	public $type;

	/**
	 * @var string
	 * @deprecated
	 */
	public $label;

	/**
	 * @var string
	 * @deprecated
	 */
	public $libelle;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int 0 for product, 1 for service
	 */
	public $product_type;

	/**
	 * @var string
	 */
	public $product_ref;

	/**
	 * @var string
	 */
	public $product_label;

	/**
	 * @var int|string
	 */
	public $date_commande;

	/**
	 * @var int|string date start planned
	 */
	public $date_start;

	/**
	 * @var int|string date start real
	 */
	public $date_start_real;

	/**
	 * @var int|string date end planned
	 */
	public $date_end;

	/**
	 * @var null|int|string date end real
	 */
	public $date_end_real;

	/**
	 * @var float|string
	 */
	public $tva_tx;

	/**
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * @var string|float
	 */
	public $localtax1_tx;

	/**
	 * @var string|float
	 */
	public $localtax2_tx;

	/**
	 * @var string Local tax 1 type
	 */
	public $localtax1_type;

	/**
	 * @var string Local tax 2 type
	 */
	public $localtax2_type;

	/**
	 * @var float
	 */
	public $qty;

	/**
	 * @var int|string
	 */
	public $remise_percent;

	/**
	 * @var float|string
	 * @deprecated
	 */
	public $remise;

	/**
	 * @var int ID
	 */
	public $fk_remise_except;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * @var float
	 * @deprecated Use $price_ht instead
	 * @see $price_ht
	 */
	public $price;

	/**
	 * @var float price without tax
	 */
	public $price_ht;

	/**
	 * @var float
	 */
	public $total_ht;

	/**
	 * @var float
	 */
	public $total_tva;

	/**
	 * @var float
	 */
	public $total_localtax1;

	/**
	 * @var float
	 */
	public $total_localtax2;

	/**
	 * @var float
	 */
	public $total_ttc;

	/**
	 * @var int 	ID
	 */
	public $fk_fournprice;

	/**
	 * @var float
	 */
	public $pa_ht;

	/**
	 * @var int		Info bits
	 */
	public $info_bits;

	/**
	 * @var int 	ID of user that insert the service
	 */
	public $fk_user_author;

	/**
	 * @var int 	ID of user opening the service
	 */
	public $fk_user_ouverture;

	/**
	 * @var int 	ID of user closing the service
	 */
	public $fk_user_cloture;

	/**
	 * @var string	Comment
	 */
	public $commentaire;


	/**
	 * @var int line rank
	 */
	public $rang = 0;


	const STATUS_INITIAL = 0;
	const STATUS_OPEN = 4;
	const STATUS_CLOSED = 5;


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 30, 'index' => 1),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
		'qty' => array('type' => 'integer', 'label' => 'Quantity', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 35, 'isameasure' => 1),
		'total_ht' => array('type' => 'integer', 'label' => 'AmountHT', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 36, 'isameasure' => 1),
		'total_tva' => array('type' => 'integer', 'label' => 'AmountVAT', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 37, 'isameasure' => 1),
		'total_ttc' => array('type' => 'integer', 'label' => 'AmountTTC', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 38, 'isameasure' => 1),
		//'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		//'fk_soc' =>array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>70),
		'fk_contrat' => array('type' => 'integer:Contrat:contrat/class/contrat.class.php', 'label' => 'Contract', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 70),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1', 'label' => 'Product', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		//'fk_user_author' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>90),
		'note_private' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 105),
		'note_public' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 110),
		//'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>115),
		//'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>120),
		//'extraparams' =>array('type'=>'varchar(255)', 'label'=>'Extraparams', 'enabled'=>1, 'visible'=>-1, 'position'=>125),
		'fk_user_ouverture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserStartingService', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 135),
		'fk_user_cloture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserClosingService', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 135),
		'statut' => array('type' => 'smallint(6)', 'label' => 'Statut', 'enabled' => 1, 'visible' => -1, 'position' => 500, 'arrayofkeyval' => array(0 => 'Draft', 4 => 'Open', 5 => 'Closed')),
		'rang' => array('type' => 'integer', 'label' => 'Rank', 'enabled' => 1, 'visible' => 0, 'position' => 500, 'default' => '0')
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Return label of this contract line status
	 *
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string      		Label of status
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut, $mode, ((!empty($this->date_end)) ? ($this->date_end < dol_now() ? 1 : 0) : -1));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a contract line status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@param	int		$expired	0=Not expired, 1=Expired, -1=Both or unknown
	 *  @param	string	$moreatt	More attribute
	 *  @param	string	$morelabel	More label
	 *  @return string      		Label of status
	 */
	public static function LibStatut($status, $mode, $expired = -1, $moreatt = '', $morelabel = '')
	{
		// phpcs:enable
		global $langs;
		$langs->load("contracts");

		if ($status == self::STATUS_INITIAL) {
			$labelStatus = $langs->transnoentities("ServiceStatusInitial");
			$labelStatusShort = $langs->transnoentities("ServiceStatusInitial");
		} elseif ($status == self::STATUS_OPEN && $expired == -1) {
			$labelStatus = $langs->transnoentities("ServiceStatusRunning");
			$labelStatusShort = $langs->transnoentities("ServiceStatusRunning");
		} elseif ($status == self::STATUS_OPEN && $expired == 0) {
			$labelStatus = $langs->transnoentities("ServiceStatusNotLate");
			$labelStatusShort = $langs->transnoentities("ServiceStatusNotLateShort");
		} elseif ($status == self::STATUS_OPEN && $expired == 1) {
			$labelStatus = $langs->transnoentities("ServiceStatusLate");
			$labelStatusShort = $langs->transnoentities("ServiceStatusLateShort");
		} elseif ($status == self::STATUS_CLOSED) {
			$labelStatus = $langs->transnoentities("ServiceStatusClosed");
			$labelStatusShort = $langs->transnoentities("ServiceStatusClosed");
		} else {
			$labelStatus = '';
			$labelStatusShort = '';
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_OPEN && $expired == 1) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}

		$params = array();
		$reg = array();
		if (preg_match('/class="(.*)"/', $moreatt, $reg)) {
			$params = array('badgeParams' => array('css' => $reg[1]));
		}
		return dolGetStatus($labelStatus.($morelabel ? ' '.$morelabel : ''), $labelStatusShort.($morelabel ? ' '.$morelabel : ''), '', $statusType, $mode, '', $params);
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array<string,string>
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$datas = [];
		$datas['label'] = $langs->trans("ShowContractOfService").': '.$this->label;
		if (empty($datas['label'])) {
			$datas['label'] = $this->description;
		}

		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually) for ContratLigne
	 *
	 *  @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int		$maxlength		Max length
	 *  @return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlength = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("ShowContractOfService").': '.$this->label;
		if (empty($label)) {
			$label = $this->description;
		}
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$params = [
				'id' => $this->fk_contrat,
				'objecttype' => $this->element,
			];
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		}

		$link = '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$this->fk_contrat.'"';
		$link .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
		$link .= $dataparams.' class="'.$classfortooltip.'">';
		$linkend = '</a>';

		$picto = 'service';
		if ($this->type == 0) {
			$picto = 'product';
		}

		if ($withpicto) {
			$result .= ($link.img_object($label, $picto, $dataparams.' class="'.$classfortooltip.'"').$linkend);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ' ';
		}
		if ($withpicto != 2) {
			$result .= $link.($this->product_ref ? $this->product_ref.' ' : '').($this->label ? $this->label : $this->description).$linkend;
		}
		return $result;
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param	string	$ref		Ref of contract line
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.fk_contrat,";
		$sql .= " t.fk_product,";
		$sql .= " t.statut,";
		$sql .= " t.label,"; // This field is not used. Only label of product
		$sql .= " p.ref as product_ref,";
		$sql .= " p.label as product_label,";
		$sql .= " p.description as product_desc,";
		$sql .= " p.fk_product_type as product_type,";
		$sql .= " t.description,";
		$sql .= " t.date_commande,";
		$sql .= " t.date_ouverture_prevue as date_start,";
		$sql .= " t.date_ouverture as date_start_real,";
		$sql .= " t.date_fin_validite as date_end,";
		$sql .= " t.date_cloture as date_end_real,";
		$sql .= " t.tva_tx,";
		$sql .= " t.vat_src_code,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.localtax1_type,";
		$sql .= " t.localtax2_type,";
		$sql .= " t.qty,";
		$sql .= " t.remise_percent,";
		$sql .= " t.remise,";
		$sql .= " t.fk_remise_except,";
		$sql .= " t.subprice,";
		$sql .= " t.price_ht,";
		$sql .= " t.total_ht,";
		$sql .= " t.total_tva,";
		$sql .= " t.total_localtax1,";
		$sql .= " t.total_localtax2,";
		$sql .= " t.total_ttc,";
		$sql .= " t.fk_product_fournisseur_price as fk_fournprice,";
		$sql .= " t.buy_price_ht as pa_ht,";
		$sql .= " t.info_bits,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_ouverture,";
		$sql .= " t.fk_user_cloture,";
		$sql .= " t.commentaire,";
		$sql .= " t.fk_unit,";
		$sql .= " t.rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as t LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		}
		if ($ref) {
			$sql .= " WHERE t.rowid = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_contrat = $obj->fk_contrat;
				$this->fk_product = $obj->fk_product;
				$this->statut = $obj->statut;
				$this->product_ref = $obj->product_ref;
				$this->product_label = $obj->product_label;
				$this->product_type = $obj->product_type;
				$this->label = $obj->label; // deprecated. We do not use this field. Only ref and label of product, and description of contract line
				$this->description = $obj->description;
				$this->date_commande = $this->db->jdate($obj->date_commande);

				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_start_real = $this->db->jdate($obj->date_start_real);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->date_end_real = $this->db->jdate($obj->date_end_real);
				// For backward compatibility
				//$this->date_ouverture_prevue = $this->db->jdate($obj->date_ouverture_prevue);
				//$this->date_ouverture = $this->db->jdate($obj->date_ouverture);
				//$this->date_fin_validite = $this->db->jdate($obj->date_fin_validite);
				//$this->date_cloture = $this->db->jdate($obj->date_cloture);

				$this->tva_tx = $obj->tva_tx;
				$this->vat_src_code = $obj->vat_src_code;
				$this->localtax1_tx = $obj->localtax1_tx;
				$this->localtax2_tx = $obj->localtax2_tx;
				$this->localtax1_type = $obj->localtax1_type;
				$this->localtax2_type = $obj->localtax2_type;
				$this->qty = $obj->qty;
				$this->remise_percent = $obj->remise_percent;
				$this->fk_remise_except = $obj->fk_remise_except;
				$this->subprice = $obj->subprice;
				$this->price_ht = $obj->price_ht;
				$this->total_ht = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_localtax1 = $obj->total_localtax1;
				$this->total_localtax2 = $obj->total_localtax2;
				$this->total_ttc = $obj->total_ttc;
				$this->info_bits = $obj->info_bits;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_ouverture = $obj->fk_user_ouverture;
				$this->fk_user_cloture = $obj->fk_user_cloture;
				$this->commentaire = $obj->commentaire;
				$this->fk_fournprice = $obj->fk_fournprice;

				$marginInfos = getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->fk_fournprice, $obj->pa_ht);
				$this->pa_ht = $marginInfos[0];
				$this->fk_unit = $obj->fk_unit;

				$this->rang = $obj->rang;

				$this->fetch_optionals();
			}

			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *      Update database for contract line
	 *
	 *      @param	User	$user        	User that modify
	 *      @param  int		$notrigger	    0=no, 1=yes (no update trigger)
	 *      @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $mysoc;

		$error = 0;

		// Clean parameters
		$this->fk_contrat = (int) $this->fk_contrat;
		$this->fk_product = (int) $this->fk_product;
		$this->statut = (int) $this->statut;
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		$this->vat_src_code = trim($this->vat_src_code);
		$this->tva_tx = trim((string) $this->tva_tx);
		$this->localtax1_tx = trim($this->localtax1_tx);
		$this->localtax2_tx = trim($this->localtax2_tx);
		$this->qty = (float) $this->qty;
		$this->remise_percent = trim((string) $this->remise_percent);
		$this->fk_remise_except = (int) $this->fk_remise_except;
		$this->subprice = (float) price2num($this->subprice);
		$this->price_ht = (float) price2num($this->price_ht);
		$this->info_bits = (int) $this->info_bits;
		$this->fk_user_author = (int) $this->fk_user_author;
		$this->fk_user_ouverture = (int) $this->fk_user_ouverture;
		$this->fk_user_cloture = (int) $this->fk_user_cloture;
		$this->commentaire = trim($this->commentaire);
		$this->rang = (int) $this->rang;
		//if (empty($this->subprice)) $this->subprice = 0;
		if (empty($this->price_ht)) {
			$this->price_ht = 0;
		}
		if (empty($this->total_ht)) {
			$this->total_ht = 0;
		}
		if (empty($this->total_tva)) {
			$this->total_tva = 0;
		}
		if (empty($this->total_ttc)) {
			$this->total_ttc = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
		$localtaxes_type = getLocalTaxesFromRate($this->tva_tx, 0, $this->thirdparty, $mysoc);

		$tabprice = calcul_price_total($this->qty, $this->price_ht, $this->remise_percent, $this->tva_tx, $this->localtax1_tx, $this->localtax2_tx, 0, 'HT', 0, 1, $mysoc, $localtaxes_type);
		$this->total_ht  = $tabprice[0];
		$this->total_tva = $tabprice[1];
		$this->total_ttc = $tabprice[2];
		$this->total_localtax1 = $tabprice[9];
		$this->total_localtax2 = $tabprice[10];

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return -1;
			} else {
				$this->pa_ht = $result;
			}
		}

		// $this->oldcopy should have been set by the caller of update (here properties were already modified)
		if (empty($this->oldcopy)) {
			dol_syslog("this->oldcopy should have been set by the caller of update (here properties were already modified)", LOG_WARNING);
			$this->oldcopy = dol_clone($this, 2);
		}

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql .= " fk_contrat = ".((int) $this->fk_contrat).",";
		$sql .= " fk_product = ".($this->fk_product ? ((int) $this->fk_product) : 'null').",";
		$sql .= " statut = ".((int) $this->statut).",";
		$sql .= " label = '".$this->db->escape($this->label)."',";
		$sql .= " description = '".$this->db->escape($this->description)."',";
		$sql .= " date_commande = ".($this->date_commande != '' ? "'".$this->db->idate($this->date_commande)."'" : "null").",";
		$sql .= " date_ouverture_prevue = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " date_ouverture = ".($this->date_start_real != '' ? "'".$this->db->idate($this->date_start_real)."'" : "null").",";
		$sql .= " date_fin_validite = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		$sql .= " date_cloture = ".($this->date_end_real != '' ? "'".$this->db->idate($this->date_end_real)."'" : "null").",";
		$sql .= " vat_src_code = '".$this->db->escape($this->vat_src_code)."',";
		$sql .= " tva_tx = ".price2num($this->tva_tx).",";
		$sql .= " localtax1_tx = ".price2num($this->localtax1_tx).",";
		$sql .= " localtax2_tx = ".price2num($this->localtax2_tx).",";
		$sql .= " qty = ".price2num($this->qty).",";
		$sql .= " remise_percent = ".price2num($this->remise_percent).",";
		$sql .= " remise = ".($this->remise ? price2num($this->remise) : "null").",";
		$sql .= " fk_remise_except = ".($this->fk_remise_except > 0 ? $this->fk_remise_except : "null").",";
		$sql .= " subprice = ".($this->subprice != '' ? $this->subprice : "null").",";
		$sql .= " price_ht = ".($this->price_ht != '' ? $this->price_ht : "null").",";
		$sql .= " total_ht = ".$this->total_ht.",";
		$sql .= " total_tva = ".$this->total_tva.",";
		$sql .= " total_localtax1 = ".$this->total_localtax1.",";
		$sql .= " total_localtax2 = ".$this->total_localtax2.",";
		$sql .= " total_ttc = ".$this->total_ttc.",";
		$sql .= " fk_product_fournisseur_price = ".(!empty($this->fk_fournprice) ? $this->fk_fournprice : "NULL").",";
		$sql .= " buy_price_ht = '".price2num($this->pa_ht)."',";
		$sql .= " info_bits = '".$this->db->escape($this->info_bits)."',";
		$sql .= " fk_user_author = ".($this->fk_user_author >= 0 ? $this->fk_user_author : "NULL").",";
		$sql .= " fk_user_ouverture = ".($this->fk_user_ouverture > 0 ? $this->fk_user_ouverture : "NULL").",";
		$sql .= " fk_user_cloture = ".($this->fk_user_cloture > 0 ? $this->fk_user_cloture : "NULL").",";
		$sql .= " commentaire = '".$this->db->escape($this->commentaire)."',";
		$sql .= " fk_unit = ".(!$this->fk_unit ? 'NULL' : $this->fk_unit).",";
		$sql .= " rang = ".(empty($this->rang) ? '0' : (int) $this->rang);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$error++;
		}

		if (!$error) { // For avoid conflicts if trigger used
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		// If we change a planned date (start or end) of one contract line, sync dates for all other services too
		if (!$error && getDolGlobalString('CONTRACT_SYNC_PLANNED_DATE_OF_SERVICES')) {
			dol_syslog(get_class($this)."::update CONTRACT_SYNC_PLANNED_DATE_OF_SERVICES is on so we update date for all lines", LOG_DEBUG);

			if ($this->date_start != $this->oldcopy->date_start) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'contratdet SET';
				$sql .= " date_ouverture_prevue = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : "null");
				$sql .= " WHERE fk_contrat = ".((int) $this->fk_contrat);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = "Error ".$this->db->lasterror();
				}
			}
			if ($this->date_end != $this->oldcopy->date_end) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'contratdet SET';
				$sql .= " date_fin_validite = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : "null");
				$sql .= " WHERE fk_contrat = ".((int) $this->fk_contrat);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = "Error ".$this->db->lasterror();
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINECONTRACT_MODIFY', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->errors[] = $this->error;
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update in database the fields total_xxx of lines
	 *	Used by migration process
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql .= " total_ht=".price2num($this->total_ht, 'MT');
		$sql .= ",total_tva=".price2num($this->total_tva, 'MT');
		$sql .= ",total_localtax1=".price2num($this->total_localtax1, 'MT');
		$sql .= ",total_localtax2=".price2num($this->total_localtax2, 'MT');
		$sql .= ",total_ttc=".price2num($this->total_ttc, 'MT');
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}


	/**
	 * Inserts a contrat line into database
	 *
	 * @param int $notrigger Set to 1 if you don't want triggers to be fired
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $user;

		$error = 0;

		// Insertion dans la base
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
		$sql .= " (fk_contrat, label, description, fk_product, qty, vat_src_code, tva_tx,";
		$sql .= " localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice,";
		$sql .= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
		$sql .= " info_bits,";
		$sql .= " rang,";
		$sql .= " price_ht, remise, fk_product_fournisseur_price, buy_price_ht";
		if ($this->date_start > 0) {
			$sql .= ",date_ouverture_prevue";
		}
		if ($this->date_end > 0) {
			$sql .= ",date_fin_validite";
		}
		$sql .= ") VALUES ($this->fk_contrat, '', '".$this->db->escape($this->description)."',";
		$sql .= ($this->fk_product > 0 ? $this->fk_product : "null").",";
		$sql .= " '".$this->db->escape($this->qty)."',";
		$sql .= " '".$this->db->escape($this->vat_src_code)."',";
		$sql .= " '".$this->db->escape($this->tva_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax2_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".price2num($this->remise_percent).",".price2num($this->subprice).",";
		$sql .= " ".price2num($this->total_ht).",".price2num($this->total_tva).",".price2num($this->total_localtax1).",".price2num($this->total_localtax2).",".price2num($this->total_ttc).",";
		$sql .= " '".$this->db->escape($this->info_bits)."',";
		$sql .= " ".(empty($this->rang) ? '0' : (int) $this->rang).",";
		$sql .= " ".price2num($this->price_ht).",".price2num($this->remise).",";
		if ($this->fk_fournprice > 0) {
			$sql .= ' '.((int) $this->fk_fournprice).',';
		} else {
			$sql .= ' null,';
		}
		if ($this->pa_ht > 0) {
			$sql .= ' '.((float) price2num($this->pa_ht));
		} else {
			$sql .= ' null';
		}
		if ($this->date_start > 0) {
			$sql .= ",'".$this->db->idate($this->date_start)."'";
		}
		if ($this->date_end > 0) {
			$sql .= ",'".$this->db->idate($this->date_end)."'";
		}
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'contratdet');

			// Insert of extrafields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINECONTRACT_INSERT', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Activate a contract line
	 *
	 * @param   User 		$user 		Object User who activate contract
	 * @param  	int 		$date 		Date real activation
	 * @param  	int|string 	$date_end 	Date planned end. Use '-1' to keep it unchanged.
	 * @param   string 		$comment 	A comment typed by user
	 * @return 	int                    	Return integer <0 if KO, >0 if OK
	 */
	public function active_line($user, $date, $date_end = '', $comment = '')
	{
		// phpcs:enable
		$error = 0;

		$this->db->begin();

		$this->statut = ContratLigne::STATUS_OPEN;
		$this->date_start_real = $date;
		$this->date_end = $date_end;
		$this->fk_user_ouverture = $user->id;
		$this->date_end_real = null;
		$this->commentaire = $comment;

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = ".((int) $this->statut).",";
		$sql .= " date_ouverture = ".(dol_strlen($this->date_start_real) != 0 ? "'".$this->db->idate($this->date_start_real)."'" : "null").",";
		if ($date_end >= 0) {
			$sql .= " date_fin_validite = ".(dol_strlen($this->date_end) != 0 ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		}
		$sql .= " fk_user_ouverture = ".((int) $this->fk_user_ouverture).",";
		$sql .= " date_cloture = null,";
		$sql .= " commentaire = '".$this->db->escape($comment)."'";
		$sql .= " WHERE rowid = ".((int) $this->id)." AND (statut = ".ContratLigne::STATUS_INITIAL." OR statut = ".ContratLigne::STATUS_CLOSED.")";

		dol_syslog(get_class($this)."::active_line", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('LINECONTRACT_ACTIVATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Close a contract line
	 *
	 * @param    User 	$user 			Object User who close contract
	 * @param  	 int 	$date_end_real 	Date end
	 * @param    string $comment 		A comment typed by user
	 * @param    int	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @return int                    	Return integer <0 if KO, >0 if OK
	 */
	public function close_line($user, $date_end_real, $comment = '', $notrigger = 0)
	{
		// phpcs:enable
		$this->date_cloture = $date_end_real;
		$this->date_end_real = $date_end_real;
		$this->user_closing_id = $user->id;
		$this->commentaire = $comment;

		$error = 0;

		// statut actif : 4

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = ".((int) ContratLigne::STATUS_CLOSED).",";
		$sql .= " date_cloture = '".$this->db->idate($date_end_real)."',";
		$sql .= " fk_user_cloture = ".((int) $user->id).",";
		$sql .= " commentaire = '".$this->db->escape($comment)."'";
		$sql .= " WHERE rowid = ".((int) $this->id)." AND statut = ".((int) ContratLigne::STATUS_OPEN);

		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINECONTRACT_CLOSE', $user);
				if ($result < 0) {
					$error++;
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}
}
