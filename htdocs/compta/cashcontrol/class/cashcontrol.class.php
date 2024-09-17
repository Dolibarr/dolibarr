<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018 Andreu Bisquerra     <jove@bisquerra.com>
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
 * \file       htdocs/compta/cashcontrol/class/cashcontrol.class.php
 * \ingroup    cashdesk|takepos
 * \brief      This file is CRUD class file (Create/Read/Update/Delete) for cash fence table
 */

/**
 *    Class to manage cash fence
 */
class CashControl extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'cashcontrol';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'pos_cash_fence';

	/**
	 * @var string String with name of icon for pos_cash_fence. Must be the part after the 'object_' into object_pos_cash_fence.png
	 */
	public $picto = 'cash-register';

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */
	public $fields = array(
	'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 10),
	'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 15),
	'ref' => array('type' => 'varchar(64)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 18),
	'posmodule' => array('type' => 'varchar(30)', 'label' => 'Module', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 19),
	'posnumber' => array('type' => 'varchar(30)', 'label' => 'Terminal', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 20, 'css' => 'center'),
	'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 0, 'position' => 24),
	'opening' => array('type' => 'price', 'label' => 'Opening', 'enabled' => 1, 'visible' => 1, 'position' => 25, 'csslist' => 'amount'),
	'cash' => array('type' => 'price', 'label' => 'Cash', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'csslist' => 'amount'),
	'cheque' => array('type' => 'price', 'label' => 'Cheque', 'enabled' => 1, 'visible' => 1, 'position' => 33, 'csslist' => 'amount'),
	'card' => array('type' => 'price', 'label' => 'CreditCard', 'enabled' => 1, 'visible' => 1, 'position' => 36, 'csslist' => 'amount'),
	'year_close' => array('type' => 'integer', 'label' => 'Year close', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 50, 'css' => 'center'),
	'month_close' => array('type' => 'integer', 'label' => 'Month close', 'enabled' => 1, 'visible' => 1, 'position' => 55, 'css' => 'center'),
	'day_close' => array('type' => 'integer', 'label' => 'Day close', 'enabled' => 1, 'visible' => 1, 'position' => 60, 'css' => 'center'),
	'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 500),
	'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 502),
	'tms' => array('type' => 'timestamp', 'label' => 'Tms', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 505),
	'fk_user_creat' => array('type' => 'integer:User', 'label' => 'UserCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 600),
	'fk_user_valid' => array('type' => 'integer:User', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 602),
	'import_key' => array('type' => 'varchar(14)', 'label' => 'Import key', 'enabled' => 1, 'visible' => 0, 'position' => 700),
	'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'position' => 1000, 'notnull' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Draft', '1' => 'Validated')),
	);

	/**
	 * @var int Object Id
	 */
	public $id;
	public $label;
	public $opening;
	public $status;
	public $year_close;
	public $month_close;
	public $day_close;
	public $posmodule;
	public $posnumber;
	public $cash;
	public $cheque;
	public $card;

	public $fk_user_creat;

	/**
	 * @var int|string $date_valid
	 */
	public $date_valid;
	public $fk_user_valid;


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CLOSED = 1; // For the moment CLOSED = VALIDATED


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 0;
	}


	/**
	 *  Create in database
	 *
	 * @param  User $user User that create
	 * @param  int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean data
		if (empty($this->cash)) {
			$this->cash = 0;
		}
		if (empty($this->cheque)) {
			$this->cheque = 0;
		}
		if (empty($this->card)) {
			$this->card = 0;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."pos_cash_fence (";
		$sql .= "entity";
		//$sql .= ", ref";
		$sql .= ", opening";
		$sql .= ", status";
		$sql .= ", date_creation";
		$sql .= ", posmodule";
		$sql .= ", posnumber";
		$sql .= ", day_close";
		$sql .= ", month_close";
		$sql .= ", year_close";
		$sql .= ", cash";
		$sql .= ", cheque";
		$sql .= ", card";
		$sql .= ") VALUES (";
		//$sql .= "'(PROV)', ";
		$sql .= ((int) $conf->entity);
		$sql .= ", ".(is_numeric($this->opening) ? price2num($this->opening, 'MT') : 0);
		$sql .= ", 0"; // Draft by default
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ", '".$this->db->escape($this->posmodule)."'";
		$sql .= ", '".$this->db->escape($this->posnumber)."'";
		$sql .= ", ".($this->day_close > 0 ? $this->day_close : "null");
		$sql .= ", ".($this->month_close > 0 ? $this->month_close : "null");
		$sql .= ", ".((int) $this->year_close);
		$sql .= ", ".price2num($this->cash, 'MT');
		$sql .= ", ".price2num($this->cheque, 'MT');
		$sql .= ", ".price2num($this->card, 'MT');
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."pos_cash_fence");

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'pos_cash_fence SET ref = rowid where rowid = '.((int) $this->id);
			$this->db->query($sql);
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Validate cash fence
	 *
	 * @param 	User 		$user		User
	 * @param 	int 		$notrigger	No trigger
	 * @return 	int						Return integer <0 if KO, >0 if OK
	 */
	public function valid(User $user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::valid action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."pos_cash_fence";
		$sql .= " SET status = ".self::STATUS_VALIDATED.",";
		$sql .= " date_valid='".$this->db->idate($now)."',";
		$sql .= " fk_user_valid = ".$user->id;
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::close", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->status = self::STATUS_VALIDATED;
			$this->date_valid = $now;
			$this->fk_user_valid = $user->id;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CASHCONTROL_VALIDATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		//if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user       User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[0] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[1] = $langs->transnoentitiesnoconv('Closed');
			$this->labelStatusShort[0] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[1] = $langs->transnoentitiesnoconv('Closed');
		}

		$statusType = 'status0';
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *  Return clickable link of object (with eventually picto)
	 *
	 *  @param  int     $withpicto                  Add picto into link
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$newref = ($this->ref ? $this->ref : $this->id);

		$label = '<u>'.$langs->trans("CashControl").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.($this->ref ? $this->ref : $this->id);

		$url = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action;
		$hookmanager->initHooks(array('cashfencedao'));
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
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array{string,mixed}		$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//var_dump($this->fields['rowid']);exit;
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1, '', 1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'posmodule')) {
			$return .= '<br><span class="opacitymedium">'.substr($langs->trans("Module/Application"), 0, 12).'</span> : <span class="info-box-label">'.$this->posmodule.'</span>';
		}
		if (property_exists($this, 'year_close')) {
			$return .= '<br><span class="info-box-label opacitymedium" >'.$langs->trans("Year").'</span> : <span>'.$this->year_close.'</span>';
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
