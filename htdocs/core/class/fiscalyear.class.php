<?php
/* Copyright (C) 2014-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2020       OScss-Shop          <support@oscss-shop.fr>
 * Copyright (C) 2023-2024  Frédéric France     <frederic.france@free.fr>
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
 *      \file       htdocs/core/class/fiscalyear.class.php
 *		\ingroup    fiscal year
 *		\brief      File of class to manage fiscal years
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class to manage fiscal year
 */
class Fiscalyear extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'fiscalyear';

	/**
	 * @var string picto
	 */
	public $picto = 'calendar';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'accounting_fiscalyear';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = '';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string fiscal year label
	 */
	public $label;

	/**
	 * Date start (date_start)
	 *
	 * @var integer
	 */
	public $date_start;

	/**
	 * Date end (date_end)
	 *
	 * @var integer
	 */
	public $date_end;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * @var int status 0=open, 1=closed
	 * @deprecated
	 * @see $status
	 */
	public $statut;

	/**
	 * @var int status 0=open, 1=closed
	 */
	public $status;

	/**
	 * @var int Entity
	 */
	public $entity;


	const STATUS_OPEN = 0;
	const STATUS_CLOSED = 1;


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->labelStatusShort = array(self::STATUS_OPEN => 'Opened', self::STATUS_CLOSED => 'Closed');
		$this->labelStatus = array(self::STATUS_OPEN => 'Opened', self::STATUS_CLOSED => 'Closed');
	}

	/**
	 *	Create object in database
	 *
	 *	@param		User	$user   User making creation
	 *	@return 	int				Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf;

		$error = 0;

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".$this->db->prefix()."accounting_fiscalyear (";
		$sql .= "label";
		$sql .= ", date_start";
		$sql .= ", date_end";
		$sql .= ", statut";
		$sql .= ", entity";
		$sql .= ", datec";
		$sql .= ", fk_user_author";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->idate($this->date_start)."'";
		$sql .= ", ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", 0";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."accounting_fiscalyear");

			$result = $this->update($user);
			if ($result > 0) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return $result;
			}
		} else {
			$this->error = $this->db->lasterror()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update record
	 *
	 *	@param	User	$user		User making update
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function update($user)
	{
		// Check parameters
		if (empty($this->date_start) && empty($this->date_end)) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".$this->db->prefix()."accounting_fiscalyear";
		$sql .= " SET label = '".$this->db->escape($this->label)."'";
		$sql .= ", date_start = '".$this->db->idate($this->date_start)."'";
		$sql .= ", date_end = ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", statut = '".$this->db->escape($this->status ? $this->status : 0)."'";
		$sql .= ", fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog($this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load an object from database
	 *
	 * @param	int		$id		Id of record to load
	 * @return	int				Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT rowid, label, date_start, date_end, statut as status";
		$sql .= " FROM ".$this->db->prefix()."accounting_fiscalyear";
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);

			$this->id = $obj->rowid;
			$this->ref = $obj->rowid;
			$this->date_start	= $this->db->jdate($obj->date_start);
			$this->date_end = $this->db->jdate($obj->date_end);
			$this->label = $obj->label;
			$this->statut = $obj->status;
			$this->status = $obj->status;

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Delete record
	 *
	 *	@param	User	$user	User that delete
	 *	@return	int				Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."accounting_fiscalyear";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('compta');

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <b><u>'.$langs->trans("FiscalPeriod").'</u></b>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->date_start)) {
			$datas['date_start'] = '<br><b>'.$langs->trans('DateStart').':</b> '.dol_print_date($this->date_start, 'day');
		}
		if (isset($this->date_start)) {
			$datas['date_end'] = '<br><b>'.$langs->trans('DateEnd').':</b> '.dol_print_date($this->date_end, 'day');
		}

		return $datas;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      int			$withpicto                Add picto into link
	 *  @param	    int   	    $notooltip		          1=Disable tooltip
	 *  @param      int         $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return     string          			          String with URL
	 */
	public function getNomUrl($withpicto = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs, $user;

		if (empty($this->ref)) {
			$this->ref = (string) $this->id;
		}

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}
		$option = '';
		if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {
			$option = 'nolink';
		}
		$result = '';
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
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = 'ToComplete';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}
		$url = DOL_URL_ROOT.'/accountancy/admin/fiscalyear_card.php?id='.$this->id;

		if ($option !== 'nolink') {
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
		if (empty($notooltip) && $user->hasRight('accounting', 'fiscalyear', 'write')) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("FiscalPeriod");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($option === 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : $dataparams.' class="'.(($withpicto != 2) ? 'paddingright ' : '').$classfortooltip.'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}

	/**
	 * Give a label from a status
	 *
	 * @param	int		$mode   	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return  string   		   	Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Give a label from a status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule@mymodule");
			$this->labelStatus[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status4';
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT fy.rowid, fy.datec, fy.fk_user_author, fy.fk_user_modif,";
		$sql .= " fy.tms as datem";
		$sql .= " FROM ".$this->db->prefix()."accounting_fiscalyear as fy";
		$sql .= " WHERE fy.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch info", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return the number of entries by fiscal year
	 *
	 *	@param	int|string		$datestart	Date start to scan
	 *	@param	int|string		$dateend	Date end to scan
	 *	@return	string			Number of entries
	 */
	public function getAccountancyEntriesByFiscalYear($datestart = '', $dateend = '')
	{
		global $conf;

		if (empty($datestart)) {
			$datestart = $this->date_start;
		}
		if (empty($dateend)) {
			$dateend = $this->date_end;
		}

		$sql = "SELECT count(DISTINCT piece_num) as nb";
		$sql .= " FROM ".$this->db->prefix()."accounting_bookkeeping";
		$sql .= " WHERE entity IN (".getEntity('bookkeeping', 0).")";
		$sql .= " AND doc_date >= '".$this->db->idate($datestart)."' and doc_date <= '".$this->db->idate($dateend)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
		} else {
			dol_print_error($this->db);
		}

		return $nb;
	}

	/**
	 *  Return the number of movements by fiscal year
	 *
	 *  @param	int|string		$datestart	Date start to scan
	 *  @param	int|string		$dateend	Date end to scan
	 *  @return	string				Number of movements
	 */
	public function getAccountancyMovementsByFiscalYear($datestart = '', $dateend = '')
	{
		global $conf;

		if (empty($datestart)) {
			$datestart = $this->date_start;
		}
		if (empty($dateend)) {
			$dateend = $this->date_end;
		}

		$sql = "SELECT count(rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."accounting_bookkeeping ";
		$sql .= " WHERE entity IN (".getEntity('bookkeeping', 0).")";
		$sql .= " AND doc_date >= '".$this->db->idate($datestart)."' and doc_date <= '".$this->db->idate($dateend)."'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
		} else {
			dol_print_error($this->db);
		}

		return $nb;
	}
}
