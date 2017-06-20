<?php
/* Copyright (C) 2017		Alexandre Spangaro   <aspangaro@zendsi.com>
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

/**
 * \file		htdocs/accountancy/class/accountingjournal.class.php
 * \ingroup		Advanced accountancy
 * \brief		File of class to manage accounting journals
 */

/**
 * Class to manage accounting accounts
 */
class AccountingJournal extends CommonObject
{
	public $element='accounting_journal';
	public $table_element='accounting_journal';
	public $fk_element = '';
	protected $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $rowid;

	public $code;
	public $label;
	public $nature;		// 0:various operations, 1:sale, 2:purchase, 3:bank, 4:expense-report, 9: has-new
	public $active;

	public $lines;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handle
	 */
	function __construct($db) {
		$this->db = $db;
	}

	/**
	 * Load an object from database
	 *
	 * @param	int		$rowid				Id of record to load
	 * @param 	string 	$journal_code		Journal code
	 * @return	int							<0 if KO, Id of record if OK and found
	 */
	function fetch($rowid = null, $journal_code = null)
	{
		if ($rowid || $journal_code)
		{
			$sql = "SELECT rowid, code, label, nature, active";
			$sql.= " FROM ".MAIN_DB_PREFIX."accounting_journal";
			$sql .= " WHERE";
			if ($rowid) {
				$sql .= " rowid = " . (int) $rowid;
			} elseif ($journal_code) {
				$sql .= " code = '" . $this->db->escape($journal_code) . "'";
			}

			dol_syslog(get_class($this)."::fetch sql=" . $sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$obj = $this->db->fetch_object($result);

				if ($obj) {
					$this->id			= $obj->rowid;
					$this->rowid		= $obj->rowid;

					$this->code			= $obj->code;
					$this->ref			= $obj->code;
					$this->label		= $obj->label;
					$this->nature		= $obj->nature;
					$this->active		= $obj->active;

					return $this->id;
				} else {
					return 0;
				}
			}
			else
			{
				$this->error = "Error " . $this->db->lasterror();
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		return -1;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND') {
		$sql = "SELECT rowid, code, label, nature, active";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 't.code' || $key == 't.label' || $key == 't.nature') {
					$sqlwhere[] = $key . '\'' . $this->db->escape($value) . '\'';
				} elseif ($key == 't.rowid' || $key == 't.active') {
					$sqlwhere[] = $key . '=' . $value;
				}
			}
		}
		$sql .= ' WHERE 1 = 1';
		$sql .= " AND entity IN (" . getEntity('accountancy') . ")";
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' ' . $filtermode . ' ', $sqlwhere);
		}

		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new self($this->db);

				$line->id = $obj->rowid;
				$line->code = $obj->code;
				$line->label = $obj->label;
				$line->nature = $obj->nature;
				$line->active = $obj->active;

				$this->lines[] = $line;
			}

			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Return clicable name (with picto eventually)
	 *
	 * @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 * @param	int		$withlabel		0=No label, 1=Include label of journal
	 * @param	int  	$nourl			1=Disable url
	 * @param	string  $moretitle		Add more text to title tooltip
	 * @param	int  	$notooltip		1=Disable tooltip
	 * @return	string	String with URL
	 */
	function getNomUrl($withpicto = 0, $withlabel = 0, $nourl = 0, $moretitle='',$notooltip=0)
	{
		global $langs, $conf, $user;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result = '';

		$url = DOL_URL_ROOT . '/accountancy/admin/journals_list.php?id=35';

		$picto = 'billr';
		$label='';

		$label = '<u>' . $langs->trans("ShowAccountingJournal") . '</u>';
		if (! empty($this->code))
			$label .= '<br><b>'.$langs->trans('Code') . ':</b> ' . $this->code;
		if (! empty($this->label))
			$label .= '<br><b>'.$langs->trans('Label') . ':</b> ' . $this->label;
		if ($moretitle) $label.=' - '.$moretitle;

		$linkclose='';
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowAccoutingJournal");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip"';
		}

		$linkstart='<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		if ($nourl)
		{
			$linkstart = '';
			$linkclose = '';
			$linkend = '';
		}

		$label_link = $this->code;
		if ($withlabel) $label_link .= ' - ' . $this->label;

		if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';
		if ($withpicto != 2) $result.=$linkstart . $label_link . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du statut d'un user (actif, inactif)
	 *
	 *  @param	int		$mode		  0=libelle long, 1=libelle court
	 *  @return	string 				   Label of type
	 */
	function getLibType($mode=0)
	{
		return $this->LibType($this->nature,$mode);
	}

	/**
	 *  Return type of an accounting journal
	 *
	 *  @param	int		$nature			Id type
	 *  @param  int		$mode		  	0=libelle long, 1=libelle court
	 *  @return string 				   	Label of type
	 */
	function LibType($nature,$mode=0)
	{
		global $langs;

		$langs->load("accountancy");

		if ($mode == 0)
		{
			$prefix='';
			if ($nature == 9) return $langs->trans('AccountingJournalType9');
			if ($nature == 5) return $langs->trans('AccountingJournalType5');
			if ($nature == 4) return $langs->trans('AccountingJournalType4');
			if ($nature == 3) return $langs->trans('AccountingJournalType3');
			if ($nature == 2) return $langs->trans('AccountingJournalType2');
			if ($nature == 1) return $langs->trans('AccountingJournalType1');
		}
		if ($mode == 1)
		{
			if ($nature == 9) return $langs->trans('AccountingJournalType9');
			if ($nature == 5) return $langs->trans('AccountingJournalType5');
			if ($nature == 4) return $langs->trans('AccountingJournalType4');
			if ($nature == 3) return $langs->trans('AccountingJournalType3');
			if ($nature == 2) return $langs->trans('AccountingJournalType2');
			if ($nature == 1) return $langs->trans('AccountingJournalType1');
		}
	}
}
