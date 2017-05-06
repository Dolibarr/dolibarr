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

	var $code;
	var $label;
	var $nature;		// 0:various operations, 1:sale, 2:purchase, 3:bank, 9: has-new
	var $active;

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
	* @param	int		$id		Id of record to load
	* @return	int				<0 if KO, >0 if OK
	*/
	function fetch($id)
	{
		$sql = "SELECT rowid, code, label, nature, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_journal";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;

			$this->code			= $obj->code;
			$this->ref			= $obj->code;
			$this->label		= $obj->label;
			$this->nature	    = $obj->nature;
			$this->active		= $obj->active;

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
	
	/**
	 * Return clicable name (with picto eventually)
	 *
	 * @param	int		$withpicto	0=No picto, 1=Include picto into link, 2=Only picto
	 * @return	string				Chaine avec URL
	 */
	function getNomUrl($withpicto = 0) {
		global $langs;

		$result = '';

		$link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/journals_card.php?id=' . $this->id . '">';
		$linkend = '</a>';

		$picto = 'billr';

		$label = $langs->trans("Show") . ': ' . $this->code . ' - ' . $this->label;

		if ($withpicto)
			$result .= ($link . img_object($label, $picto) . $linkend);
		if ($withpicto && $withpicto != 2)
			$result .= ' ';
		if ($withpicto != 2)
			$result .= $link . $this->code . ' - ' . $this->label . $linkend;
		return $result;
	}
	
	/**
	 *  Retourne le libelle du statut d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court
	 *  @return	string 			       Label of type
	 */
	function getLibType($mode=0)
	{
	    return $this->LibType($this->nature,$mode);
	}
	
	/**
	 *  Return type of an accounting journal
	 *
	 *  @param	int		$nature        	Id type
	 *  @param  int		$mode          	0=libelle long, 1=libelle court
	 *  @return string 			       	Label of type
	 */
	function LibType($nature,$mode=0)
	{
	    global $langs;

		$langs->load("accountancy");
	
	    if ($mode == 0)
	    {
	        $prefix='';
			if ($nature == 9) return $langs->trans('AccountingJournalTypeHasNew');
			if ($nature == 3) return $langs->trans('AccountingJournalTypeBank');
			if ($nature == 2) return $langs->trans('AccountingJournalTypePurchase');
	        if ($nature == 1) return $langs->trans('AccountingJournalTypeSale');
	        if ($nature == 0) return $langs->trans('AccountingJournalTypeVariousOperation');
	    }
	    if ($mode == 1)
	    {
			if ($nature == 9) return $langs->trans('AccountingJournalTypeHasNew');
			if ($nature == 3) return $langs->trans('AccountingJournalTypeBank');
			if ($nature == 2) return $langs->trans('AccountingJournalTypePurchase');
	        if ($nature == 1) return $langs->trans('AccountingJournalTypeSale');
	        if ($nature == 0) return $langs->trans('AccountingJournalTypeVariousOperation');
	    }
	}
}
