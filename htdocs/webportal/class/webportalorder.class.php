<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file        htdocs/webportal/class/webportalorder.class.php
 * \ingroup     webportal
 * \brief       This file is a CRUD class file for WebPortalOrder (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

/**
 * Class for WebPortalOrder
 */
class WebPortalOrder extends Commande
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'webportal';

	/**
	 * Status list (short label)
	 */
	const ARRAY_STATUS_LABEL = array(
		Commande::STATUS_DRAFT => 'StatusOrderDraftShort',
		Commande::STATUS_VALIDATED => 'StatusOrderValidated',
		Commande::STATUS_SHIPMENTONPROCESS => 'StatusOrderSentShort',
		Commande::STATUS_CLOSED => 'StatusOrderDelivered',
		Commande::STATUS_CANCELED => 'StatusOrderCanceledShort',
	);

	/**
	 * @var Commande Order for static methods
	 */
	protected $order_static = null;

	/**
	 *  'type' field format:
	 *    'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *    'select' (list of values are in 'options'),
	 *    'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *    'chkbxlst:...',
	 *    'varchar(x)',
	 *    'text', 'text:none', 'html',
	 *    'double(24,8)', 'real', 'price',
	 *    'date', 'datetime', 'timestamp', 'duration',
	 *    'boolean', 'checkbox', 'radio', 'array',
	 *    'mail', 'phone', 'url', 'password', 'ip'
	 *        Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt('MY_SETUP_PARAM') or 'isModEnabled("multicurrency")' ...)
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
	 *    'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 10,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 20, 'index' => 1,),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 2, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 25,),
		'date_commande' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => 2, 'position' => 60,),
		'date_livraison' => array('type' => 'date', 'label' => 'DateDeliveryPlanned', 'enabled' => 1, 'visible' => 2, 'position' => 70,),
		'total_ht' => array('type' => 'price', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => 2, 'position' => 125, 'isameasure' => 1,),
		'total_tva' => array('type' => 'price', 'label' => 'VAT', 'enabled' => 1, 'visible' => 2, 'position' => 130, 'isameasure' => 1,),
		'total_ttc' => array('type' => 'price', 'label' => 'TotalTTC', 'enabled' => 1, 'visible' => 2, 'position' => 145, 'isameasure' => 1,),
		'multicurrency_total_ht' => array('type' => 'price', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 255, 'isameasure' => 1,),
		'multicurrency_total_tva' => array('type' => 'price', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 260, 'isameasure' => 1,),
		'multicurrency_total_ttc' => array('type' => 'price', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 265, 'isameasure' => 1,),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 2, 'position' => 500, 'notnull' => -5, 'arrayofkeyval' => self::ARRAY_STATUS_LABEL,),
	);
	//public $rowid;
	//public $ref;
	//public $date_commande;
	//public $date_livraison;
	//public $total_ht;
	//public $total_tva;
	//public $total_ttc;
	//public $multicurrency_total_ht;
	//public $multicurrency_total_tva;
	//public $multicurrency_total_ttc;

	/**
	 * @var int status
	 */
	public $fk_statut;
	// END MODULEBUILDER PROPERTIES


	/**
	 * Get order for static method
	 *
	 * @return	Commande
	 */
	protected function getOrderStatic()
	{
		if (!$this->order_static) {
			$this->order_static = new Commande($this->db);
		}

		return $this->order_static;
	}

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		$this->isextrafieldmanaged = 0;

		$this->getOrderStatic();
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("WebPortalOrder")];
		}
		$datas['picto'] = img_picto('', $this->picto) . ' <u>' . $langs->trans("WebPortalOrder") . '</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' ' . $this->getLibStatut(5);
		}
		$datas['ref'] .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		return $datas;
	}

	/**
	 * Return clickable link of object (with eventually picto)
	 *
	 * @param	int		$withpicto				Add picto into link
	 * @param	string	$option					Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 * @param	int		$max					Max length to show
	 * @param	int		$short					Short
	 * @param	int		$notooltip				1=Disable tooltip
	 * @param	int		$save_lastsearch_value	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @param	int		$addlinktonotes			Add link to notes
	 * @param	string	$target					Attribute target for link
	 * @return	string	String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0, $target = '')
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$url = '';

		$option = 'nolink';

		if ($short) {
			return $url;
		}
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="' . dol_escape_htmltag(json_encode($params)) . '"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$linkclose = '';

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		if ($option === 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
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
	 * Return clickable link of object (with eventually picto)
	 *
	 * @param	string					$option				Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 * @param	array{string,mixed}		$arraydata			Array of data
	 * @return	string										HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs, $conf;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<div class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', 'order');
		$return .= '</div>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . (method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref) . '</span>';
		$return .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';

		if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
			$return .= '<br><div class="info-box-ref tdoverflowmax150">' . $this->thirdparty->getNomUrl(1) . '</div>';
		}
		if (property_exists($this, 'total_ht')) {
			$return .= '<div class="info-box-ref amount">' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency) . ' ' . $langs->trans('HT') . '</div>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<div class="info-box-status margintoponly">' . $this->getLibStatut(5) . '</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 * @param	int		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string	Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->fk_statut, $this->billed, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 * @param	int			$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->fk_statut, $this->billed, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of status
	 *
	 * @param	int		$status				Id status
	 * @param	int		$billed				If invoiced
	 * @param	int 	$mode				0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 * @param	int 	$donotshowbilled	Do not show billed status after order status
	 * @return 	string	Label of status
	 */
	public function LibStatut($status, $billed, $mode, $donotshowbilled = 0)
	{
		// phpcs:enable
		return $this->getOrderStatic()->LibStatut($status, $billed, $mode, $donotshowbilled);
	}
}
