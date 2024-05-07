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
 * \file        htdocs/webportal/class/webportalinvoice.class.php
 * \ingroup     webportal
 * \brief       This file is a CRUD class file for WebPortalInvoice (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

/**
 * Class for WebPortalInvoice
 */
class WebPortalInvoice extends Facture
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'webportal';

	/**
	 * Status list (short label)
	 */
	const ARRAY_STATUS_LABEL = array(
		Facture::STATUS_DRAFT => 'BillShortStatusDraft',
		Facture::STATUS_VALIDATED => 'BillShortStatusNotPaid',
		Facture::STATUS_CLOSED => 'BillShortStatusPaid',
		Facture::STATUS_ABANDONED => 'BillShortStatusCanceled',
	);

	/**
	 * @var Facture Invoice for static methods
	 */
	protected $invoice_static = null;

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
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 1,),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 20, 'index' => 1,),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 2, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 5,),
		'type' => array('type' => 'smallint(6)', 'label' => 'Type', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 15,),
		'datef' => array('type' => 'date', 'label' => 'DateInvoice', 'enabled' => 1, 'visible' => 2, 'position' => 20,),
		'date_lim_reglement' => array('type' => 'date', 'label' => 'DateDue', 'enabled' => 1, 'visible' => 2, 'position' => 25,),
		'paye' => array('type' => 'smallint(6)', 'label' => 'InvoicePaidCompletely', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 80,),
		'total_ht' => array('type' => 'price', 'label' => 'AmountHT', 'enabled' => 1, 'visible' => 2, 'position' => 95, 'isameasure' => 1,),
		'total_tva' => array('type' => 'price', 'label' => 'AmountVAT', 'enabled' => 1, 'visible' => 2, 'position' => 100, 'isameasure' => 1,),
		'total_ttc' => array('type' => 'price', 'label' => 'AmountTTC', 'enabled' => 1, 'visible' => 2, 'position' => 130, 'isameasure' => 1,),
		'multicurrency_total_ht' => array('type' => 'price', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 290, 'isameasure' => 1,),
		'multicurrency_total_tva' => array('type' => 'price', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 291, 'isameasure' => 1,),
		'multicurrency_total_ttc' => array('type' => 'price', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -2, 'position' => 292, 'isameasure' => 1,),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 2, 'notnull' => 1, 'position' => 1000, 'arrayofkeyval' => self::ARRAY_STATUS_LABEL,),
	);
	//public $rowid;
	//public $ref;
	public $datef;
	//public $date_lim_reglement;
	//public $total_ht;
	//public $total_tva;
	//public $total_ttc;
	//public $multicurrency_total_ht;
	//public $multicurrency_total_tva;
	//public $multicurrency_total_ttc;
	public $fk_statut;
	// END MODULEBUILDER PROPERTIES


	/**
	 * Get invoice for static methods
	 *
	 * @return	Facture
	 */
	protected function getInvoiceStatic()
	{
		if (!$this->invoice_static) {
			$this->invoice_static = new Facture($this->db);
		}

		return $this->invoice_static;
	}

	/**
	 * Constructor
	 *
	 * @param	DoliDb	 $db	Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->isextrafieldmanaged = 0;

		$this->getInvoiceStatic();
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param 	array	$params		ex option, infologin
	 * @return 	array
	 * @since v18
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('bills');

		$datas = [];

		return $datas;
	}

	/**
	 *  Return clicable link of object (with eventually picto)
	 *
	 * @param	int		$withpicto				Add picto into link
	 * @param	string	$option					Where point the link
	 * @param	int		$max					Maxlength of ref
	 * @param	int		$short					1=Return just URL
	 * @param	string	$moretitle				Add more text to title tooltip
	 * @param	int		$notooltip				1=Disable tooltip
	 * @param	int		$addlinktonotes			1=Add link to notes
	 * @param	int		$save_lastsearch_value	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @param	string	$target					Target of link ('', '_self', '_blank', '_parent', '_backoffice', ...)
	 * @return	string	String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $addlinktonotes = 0, $save_lastsearch_value = -1, $target = '')
	{
		global $langs, $conf;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$url = '';
		$option = 'nolink';

		if ($short) {
			return $url;
		}

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'moretitle' => $moretitle,
			'option' => $option,
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

		$linkclose = ($target ? ' target="' . $target . '"' : '');

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		if ($option == 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : $dataparams . ' class="' . (($withpicto != 2) ? 'paddingright ' : '') . $classfortooltip . '"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($max ? dol_trunc($this->ref, $max) : $this->ref);
		}
		$result .= $linkend;

		global $action, $hookmanager;
		$hookmanager->initHooks(array('invoicedao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result, 'notooltip' => $notooltip, 'addlinktonotes' => $addlinktonotes, 'save_lastsearch_value' => $save_lastsearch_value, 'target' => $target);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 * Return clicable link of object (with eventually picto)
	 *
	 * @param	string	$option		Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 * @param	array 	$arraydata	Array of data
	 * @return	string	HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . (method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref) . '</span>';
		$return .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
		if (property_exists($this, 'socid')) {
			$return .= '<br><span class="info-box-label">' . $this->socid . '</span>';
		}
		if (property_exists($this, 'fk_user_author')) {
			$return .= '<br><span class="info-box-label">' . $this->fk_user_author . '</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status margintoponly">' . $this->getLibStatut(3) . '</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 * @param	int			$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string		Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->paye, $this->fk_statut, $mode, -1, $this->type);
	}

	/**
	 *  Return label of object status
	 *
	 * @param	int			$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 * @param	int			$alreadypaid	0=No payment already done, >0=Some payments were already done
	 * @return 	string		Label of status
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $this->fk_statut, $mode, $alreadypaid, $this->type);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Return label of a status
	 *
	 * @param	int		$paye			Status field paye
	 * @param	int		$status			Id status
	 * @param	int 	$mode 			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=long label + picto
	 * @param	int 	$alreadypaid	0=No payment already done, >0=Some payments were already done
	 * @param	int 	$type 			Type invoice. If -1, we use $this->type
	 * @return  string	Label of status
	 */
	public function LibStatut($paye, $status, $mode = 0, $alreadypaid = -1, $type = -1)
	{
		// phpcs:enable
		return $this->getInvoiceStatic()->LibStatut($paye, $status, $mode, $alreadypaid, $type);
	}
}
