<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    websites/websitepage.class.php
 * \ingroup websites
 * \brief   This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *          Put some comments here
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Websitepage
 */
class WebsitePage extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'websitepage';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'website_page';

	/**
	 */

	public $fk_website;
	public $pageurl;
	public $title;
	public $description;
	public $keywords;
	public $content;
	public $status;
	public $date_creation;
	public $date_modification;
	public $tms;

	/**
	 */


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
        $now=dol_now();

		// Clean parameters
		if (isset($this->fk_website)) {
			 $this->fk_website = trim($this->fk_website);
		}
		if (isset($this->pageurl)) {
			 $this->pageurl = trim($this->pageurl);
		}
		if (isset($this->title)) {
			 $this->title = trim($this->title);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->keywords)) {
			 $this->keywords = trim($this->keywords);
		}
		if (isset($this->content)) {
			 $this->content = trim($this->content);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}
		if (isset($this->date_creation)) {
			 $this->date_creation = $now;
		}
		if (isset($this->date_modification)) {
			 $this->date_modification = $now;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'fk_website,';
		$sql.= 'pageurl,';
		$sql.= 'title,';
		$sql.= 'description,';
		$sql.= 'keywords,';
		$sql.= 'content,';
		$sql.= 'status,';
		$sql.= 'date_creation,';
		$sql.= 'date_modification';
		$sql .= ') VALUES (';
		$sql .= ' '.(! isset($this->fk_website)?'NULL':$this->fk_website).',';
		$sql .= ' '.(! isset($this->pageurl)?'NULL':"'".$this->db->escape($this->pageurl)."'").',';
		$sql .= ' '.(! isset($this->title)?'NULL':"'".$this->db->escape($this->title)."'").',';
		$sql .= ' '.(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").',';
		$sql .= ' '.(! isset($this->keywords)?'NULL':"'".$this->db->escape($this->keywords)."'").',';
		$sql .= ' '.(! isset($this->content)?'NULL':"'".$this->db->escape($this->content)."'").',';
		$sql .= ' '.(! isset($this->status)?'NULL':$this->status).',';
		$sql .= ' '.(! isset($this->date_creation) || dol_strlen($this->date_creation)==0?'NULL':"'".$this->db->idate($this->date_creation)."'").',';
		$sql .= ' '.(! isset($this->date_modification) || dol_strlen($this->date_modification)==0?'NULL':"'".$this->db->idate($this->date_modification)."'");
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_CREATE',$user);
				//if ($result < 0) $error++;
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id           Id object
	 * @param string $website_id   Web site id
	 * @param string $page         Page name
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $website_id = null, $page = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';

		$sql .= " t.fk_website,";
		$sql .= " t.pageurl,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.content,";
		$sql .= " t.status,";
		$sql .= " t.date_creation,";
		$sql .= " t.date_modification,";
		$sql .= " t.tms";

		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (null !== $website_id) {
		    $sql .= ' WHERE t.fk_website = ' . '\'' . $website_id . '\'';
		    $sql .= ' AND t.pageurl = ' . '\'' . $page . '\'';
		} else {
			$sql .= ' WHERE t.rowid = ' . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->fk_website = $obj->fk_website;
				$this->pageurl = $obj->pageurl;
				$this->title = $obj->title;
				$this->description = $obj->description;
				$this->keywords = $obj->keywords;
				$this->content = $obj->content;
				$this->status = $obj->status;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->tms = $this->db->jdate($obj->tms);


			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param  string      $websiteid    Web site
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($websiteid, $sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records=array();

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.fk_website,";
		$sql .= " t.pageurl,";
		$sql .= " t.title,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.content,";
		$sql .= " t.status,";
		$sql .= " t.date_creation,";
		$sql .= " t.date_modification,";
		$sql .= " t.tms";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';
		$sql .= ' WHERE t.fk_website = '.$websiteid; 
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.rowid' || $key=='t.fk_website') {
					$sqlwhere[] = $key . '='. $value;
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
		 $sql .=  ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql))
			{
				$record = new WebsitePage($this->db);

				$record->id = $obj->rowid;
				$record->fk_website = $obj->fk_website;
				$record->pageurl = $obj->pageurl;
				$record->title = $obj->title;
				$record->description = $obj->description;
				$record->keywords = $obj->keywords;
				$record->content = $obj->content;
				$record->status = $obj->status;
				$record->date_creation = $this->db->jdate($obj->date_creation);
				$record->date_modification = $this->db->jdate($obj->date_modification);
				$record->tms = $this->db->jdate($obj->tms);
				//var_dump($record->id);
				$records[$record->id] = $record;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		if (isset($this->fk_website)) {
			 $this->fk_website = trim($this->fk_website);
		}
		if (isset($this->pageurl)) {
			 $this->pageurl = trim($this->pageurl);
		}
		if (isset($this->title)) {
			 $this->title = trim($this->title);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->keywords)) {
			 $this->keywords = trim($this->keywords);
		}
		if (isset($this->content)) {
			 $this->content = trim($this->content);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' fk_website = '.(isset($this->fk_website)?$this->fk_website:"null").',';
		$sql .= ' pageurl = '.(isset($this->pageurl)?"'".$this->db->escape($this->pageurl)."'":"null").',';
		$sql .= ' title = '.(isset($this->title)?"'".$this->db->escape($this->title)."'":"null").',';
		$sql .= ' description = '.(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").',';
		$sql .= ' keywords = '.(isset($this->keywords)?"'".$this->db->escape($this->keywords)."'":"null").',';
		$sql .= ' content = '.(isset($this->content)?"'".$this->db->escape($this->content)."'":"null").',';
		$sql .= ' status = '.(isset($this->status)?$this->status:"null").',';
		$sql .= ' date_creation = '.(! isset($this->date_creation) || dol_strlen($this->date_creation) != 0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').',';
		$sql .= ' date_modification = '.(! isset($this->date_modification) || dol_strlen($this->date_modification) != 0 ? "'".$this->db->idate($this->date_modification)."'" : 'null').',';
		$sql .= ' tms = '.(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : "'".$this->db->idate(dol_now())."'");
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if ($this->old_object->pageurl != $this->pageurl)
		{
		      dol_syslog("The alias was changed, we must rename/recreate the page file into document");
		      
		}
		
		if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			//// Call triggers
			//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 *
	 * @return int New id of clone
	 */
	public function createFromClone($fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $user;
		$error = 0;
		$object = new Websitepage($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return - 1;
		}
	}

	/**
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	integer	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $maxlen=24, $morecss='')
	{
		global $langs, $conf, $db;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("Page") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/websites/card.php?id='.$this->id.'"';
        $link.= ($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss?' '.$morecss:'').'"');
        $link.= '>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($link.img_object(($notooltip?'':$label), 'label', ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $link . $this->ref . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->fk_website = '';
		$this->pageurl = '';
		$this->title = '';
		$this->description = '';
		$this->keywords = '';
		$this->content = '';
		$this->status = '';
		$this->date_creation = '';
		$this->date_modification = '';
		$this->tms = '';


	}

}
