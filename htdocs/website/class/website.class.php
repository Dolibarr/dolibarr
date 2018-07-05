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
 * \file    website/website.class.php
 * \ingroup website
 * \brief   File for the CRUD class of website (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Website
 */
class Website extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'website';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'website';
	/**
	 * @var array  Does website support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;
	/**
	 * @var string String with name of icon for website. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'globe';

	/**
	 * @var int
	 */
	public $entity;
	/**
	 * @var string
	 */
	public $ref;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var int
	 */
	public $status;
	/**
	 * @var mixed
	 */
	public $date_creation;
	/**
	 * @var mixed
	 */
	public $tms = '';
	/**
	 * @var integer
	 */
	public $fk_default_home;
	/**
	 * @var string
	 */
	public $virtualhost;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
		return 1;
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
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$now=dol_now();

		// Clean parameters
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->ref)) {
			 $this->ref = trim($this->ref);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}
		if (empty($this->date_creation)) $this->date_creation = $now;
		if (empty($this->date_modification)) $this->date_modification = $now;

		// Check parameters
		if (empty($this->entity)) { $this->entity = $conf->entity; }

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'entity,';
		$sql.= 'ref,';
		$sql.= 'description,';
		$sql.= 'status,';
		$sql.= 'fk_default_home,';
		$sql.= 'virtualhost,';
		$sql.= 'fk_user_create,';
		$sql.= 'date_creation,';
		$sql.= 'tms';
		$sql .= ') VALUES (';
		$sql .= ' '.((empty($this->entity) && $this->entity != '0')?'NULL':$this->entity).',';
		$sql .= ' '.(! isset($this->ref)?'NULL':"'".$this->db->escape($this->ref)."'").',';
		$sql .= ' '.(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").',';
		$sql .= ' '.(! isset($this->status)?'NULL':$this->status).',';
		$sql .= ' '.(! isset($this->fk_default_home)?'NULL':$this->fk_default_home).',';
		$sql .= ' '.(! isset($this->virtualhost)?'NULL':"'".$this->db->escape($this->virtualhost)."'").",";
		$sql .= ' '.(! isset($this->fk_user_create)?$user->id:$this->fk_user_create).',';
		$sql .= ' '.(! isset($this->date_creation) || dol_strlen($this->date_creation)==0?'NULL':"'".$this->db->idate($this->date_creation)."'").",";
		$sql .= ' '.(! isset($this->date_modification) || dol_strlen($this->date_modification)==0?'NULL':"'".$this->db->idate($this->date_creation)."'");
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
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
		if ($error) {
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
	 * @param 	int    $id  	Id object
	 * @param 	string $ref 	Ref
	 * @return 	int 			<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.description,";
		$sql .= " t.status,";
		$sql .= " t.fk_default_home,";
		$sql .= " t.virtualhost,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.entity IN ('.getEntity('website').')';
		if (null !== $ref) {
			$sql .= " AND t.ref = '" . $this->db->escape($ref) . "'";
		} else {
			$sql .= ' AND t.rowid = ' . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->ref = $obj->ref;
				$this->description = $obj->description;
				$this->status = $obj->status;
				$this->fk_default_home = $obj->fk_default_home;
				$this->virtualhost = $obj->virtualhost;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}
			$this->db->free($resql);

			if ($numrows > 0) {
				// Lines
				$this->fetchLines();
			}

			if ($numrows > 0) {
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
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object MyObjectLine

		return count($this->lines)?1:0;
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.description,";
		$sql .= " t.status,";
		$sql .= " t.fk_default_home,";
		$sql .= " t.virtualhost,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';
		$sql .= ' WHERE t.entity IN ('.getEntity('website').')';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
		 $sql .=  ' ' . $this->db->plimit($limit, $offset);
		}
		$this->records = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->ref = $obj->ref;
				$line->description = $obj->description;
				$line->status = $obj->status;
				$line->fk_default_home = $obj->fk_default_home;
				$line->virtualhost = $obj->virtualhost;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_user_modif = $obj->fk_user_modif;
				$line->date_creation = $this->db->jdate($obj->date_creation);
				$line->date_modification = $this->db->jdate($obj->date_modification);

				$this->records[$line->id] = $line;
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

		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->ref)) {
			 $this->ref = trim($this->ref);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' entity = '.(isset($this->entity)?$this->entity:"null").',';
		$sql .= ' ref = '.(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").',';
		$sql .= ' description = '.(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").',';
		$sql .= ' status = '.(isset($this->status)?$this->status:"null").',';
		$sql .= ' fk_default_home = '.(($this->fk_default_home > 0)?$this->fk_default_home:"null").',';
		$sql .= ' virtualhost = '.(($this->virtualhost != '')?"'".$this->db->escape($this->virtualhost)."'":"null").',';
		$sql .= ' fk_user_modif = '.(! isset($this->fk_user_modif) ? $user->id : $this->fk_user_modif).',';
		$sql .= ' date_creation = '.(! isset($this->date_creation) || dol_strlen($this->date_creation) != 0 ? "'".$this->db->idate($this->date_creation)."'" : 'null');
		$sql .= ', tms = '.(dol_strlen($this->date_modification) != 0 ? "'".$this->db->idate($this->date_modification)."'" : "'".$this->db->idate(dol_now())."'");
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
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
	 * Load an object from its id and create a new one in database.
	 * This copy website directories, regenerate all the pages + alias pages and recreate the medias link.
	 *
	 * @param	User	$user		User making the clone
	 * @param 	int 	$fromid 	Id of object to clone
	 * @param	string	$newref		New ref
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone($user, $fromid, $newref)
	{
        global $hookmanager, $langs;
		global $dolibarr_main_data_root;

		$error=0;

        dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

        // Check no site with ref exists
		if ($object->fetch(0, $newref) > 0)
		{
			$this->error='NewRefIsAlreadyUsed';
			return -1;
		}

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);

		$oldidforhome=$object->fk_default_home;

		$pathofwebsiteold=$dolibarr_main_data_root.'/website/'.$object->ref;
		$pathofwebsitenew=$dolibarr_main_data_root.'/website/'.$newref;
		dol_delete_dir_recursive($pathofwebsitenew);

		$fileindex=$pathofwebsitenew.'/index.php';

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref=$newref;
		$object->fk_default_home=0;
		$object->virtualhost='';

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (! $error)
		{
			dolCopyDir($pathofwebsiteold, $pathofwebsitenew, $conf->global->MAIN_UMASK, 0);

			// Check symlink to medias and restore it if ko
			$pathtomedias=DOL_DATA_ROOT.'/medias';
			$pathtomediasinwebsite=$pathofwebsitenew.'/medias';
			if (! is_link(dol_osencode($pathtomediasinwebsite)))
			{
				dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
				dol_mkdir(dirname($pathtomediasinwebsite));     // To be sure dir for website exists
				$result = symlink($pathtomedias, $pathtomediasinwebsite);
			}

			$newidforhome=0;

			// Duplicate pages
			$objectpages = new WebsitePage($this->db);
			$listofpages = $objectpages->fetchAll($fromid);
			foreach($listofpages as $pageid => $objectpageold)
			{
				// Delete old file
				$filetplold=$pathofwebsitenew.'/page'.$pageid.'.tpl.php';
				dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
				dol_delete_file($filetplold);

				// Create new file
				$objectpagenew = $objectpageold->createFromClone($user, $pageid, $objectpageold->pageurl, '', 0, $object->id);
				//print $pageid.' = '.$objectpageold->pageurl.' -> '.$objectpagenew->id.' = '.$objectpagenew->pageurl.'<br>';
				if (is_object($objectpagenew) && $objectpagenew->pageurl)
				{
		            $filealias=$pathofwebsitenew.'/'.$objectpagenew->pageurl.'.php';
					$filetplnew=$pathofwebsitenew.'/page'.$objectpagenew->id.'.tpl.php';

					// Save page alias
					$result=dolSavePageAlias($filealias, $object, $objectpagenew);
					if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');

					$result=dolSavePageContent($filetplnew, $object, $objectpagenew);
					if (! $result) setEventMessages('Failed to write file '.$filetplnew, null, 'errors');

					if ($pageid == $oldidforhome)
					{
						$newidforhome = $objectpagenew->id;
					}
				}
				else
				{
					setEventMessages($objectpageold->error, $objectpageold->errors, 'errors');
					$error++;
				}
			}
		}

		if (! $error)
		{
			// Restore id of home page
			$object->fk_default_home = $newidforhome;
		    $res = $object->update($user);
		    if (! $res > 0)
		    {
		        $error++;
		        setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		    }

		    if (! $error)
		    {
		    	$filetpl=$pathofwebsitenew.'/page'.$newidforhome.'.tpl.php';

		    	// Generate the index.php page to be the home page
		    	//-------------------------------------------------
		    	$result = dolSaveIndexPage($pathofwebsitenew, $fileindex, $filetpl);
		    }
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object;
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

        $label = '<u>' . $langs->trans("WebSite") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Nom') . ':</b> ' . $this->ref;

        $linkstart = '<a href="'.DOL_URL_ROOT.'/website/card.php?id='.$this->id.'"';
        $linkstart.= ($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss?' '.$morecss:'').'"');
        $linkstart.= '>';
		$linkend='</a>';

		$linkstart = $linkend = '';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?'':'class="classfortooltip"')).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->ref . $linkend;
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
	    global $user;

		$this->id = 0;

		$this->entity = 1;
		$this->ref = 'myspecimenwebsite';
		$this->description = 'A specimen website';
		$this->status = '';
		$this->fk_default_home = null;
		$this->virtualhost = 'http://myvirtualhost';
		$this->fk_user_create = $user->id;
		$this->fk_user_modif = $user->id;
		$this->date_creation = dol_now();
		$this->tms = dol_now();


	}


	/**
	 * Generate a zip with all data of web site.
	 *
	 * @return  string						Path to file with zip
	 */
	function exportWebSite()
	{
		global $conf;

		$website = $this;

		if (empty($website->id) || empty($website->ref))
		{
			setEventMessages("Website id or ref is not defined", null, 'errors');
			return '';
		}

		dol_syslog("Create temp dir ".$conf->website->dir_temp);
		dol_mkdir($conf->website->dir_temp);
		if (! is_writable($conf->website->dir_temp))
		{
			setEventMessages("Temporary dir ".$conf->website->dir_temp." is not writable", null, 'errors');
			return '';
		}

		$srcdir = $conf->website->dir_output.'/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/containers';

		$arrayreplacement=array();

		dol_syslog("Clear temp dir ".$destdir);
		dol_delete_dir($destdir, 1);

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

		$srcdir = DOL_DATA_ROOT.'/medias/image/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/image/'.$website->ref;

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

		$srcdir = DOL_DATA_ROOT.'/medias/js/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/js/'.$website->ref;

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

		// Build sql file
		dol_syslog("Create containers dir");
		dol_mkdir($conf->website->dir_temp.'/'.$website->ref.'/containers');

		$filesql = $conf->website->dir_temp.'/'.$website->ref.'/website_pages.sql';
		$fp = fopen($filesql,"w");
		if (empty($fp))
		{
			setEventMessages("Failed to create file ".$filesql, null, 'errors');
			return '';
		}

		$objectpages = new WebsitePage($this->db);
		$listofpages = $objectpages->fetchAll($website->id);

		// Assign ->newid and ->newfk_page
		$i=1;
		foreach($listofpages as $pageid => $objectpageold)
		{
			$objectpageold->newid=$i;
			$i++;
		}
		$i=1;
		foreach($listofpages as $pageid => $objectpageold)
		{
			// Search newid
			$newfk_page=0;
			foreach($listofpages as $pageid2 => $objectpageold2)
			{
				if ($pageid2 == $objectpageold->fk_page)
				{
					$newfk_page = $objectpageold2->newid;
					break;
				}
			}
			$objectpageold->newfk_page=$newfk_page;
			$i++;
		}
		foreach($listofpages as $pageid => $objectpageold)
		{
			$line = 'INSERT INTO llx_website_page(rowid, fk_page, fk_website, pageurl, title, description, keyword, status, date_creation, tms, lang, import_key, grabbed_from, content)';
			$line.= " VALUES(";
			$line.= $objectpageold->newid."+__MAXROWID__, ";
			$line.= ($objectpageold->newfk_page ? $this->db->escape($objectpageold->newfk_page)."+__MAXROWID__" : "null").", ";
			$line.= "__WEBSITE_ID__, ";
			$line.= "'".$this->db->escape($objectpageold->pageurl)."', ";
			$line.= "'".$this->db->escape($objectpageold->title)."', ";
			$line.= "'".$this->db->escape($objectpageold->description)."', ";
			$line.= "'".$this->db->escape($objectpageold->keyword)."', ";
			$line.= "'".$this->db->escape($objectpageold->status)."', ";
			$line.= "'".$this->db->idate($objectpageold->date_creation)."', ";
			$line.= "'".$this->db->idate($objectpageold->date_modification)."', ";
			$line.= "'".$this->db->escape($objectpageold->lang)."', ";
			$line.= ($objectpageold->import_key ? "'".$this->db->escape($objectpageold->import_key)."'" : "null").", ";
			$line.= "'".$this->db->escape($objectpageold->grabbed_from)."', ";
			$line.= "'".$this->db->escape($objectpageold->content)."'";
			$line.= ");";
			$line.= "\n";
			fputs($fp, $line);
		}

		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($filesql, octdec($conf->global->MAIN_UMASK));

		// Build zip file
		$filedir  = $conf->website->dir_temp.'/'.$website->ref;
		$fileglob = $conf->website->dir_temp.'/'.$website->ref.'/website_'.$website->ref.'-*.zip';
		$filename = $conf->website->dir_temp.'/'.$website->ref.'/website_'.$website->ref.'-'.dol_print_date(dol_now(),'dayhourlog').'.zip';

		dol_delete_file($fileglob, 0);
		dol_compress_file($filedir, $filename, 'zip');

		return $filename;
	}


	/**
	 * Open a zip with all data of web site and load it into database.
	 *
	 * @param 	string		$pathtofile		Path of zip file
	 * @return  int							<0 if KO, Id of new website if OK
	 */
	function importWebSite($pathtofile)
	{
		global $conf;

		$result = 0;

		$object = new Website($this->db);

		$filename = basename($pathtofile);
		if (! preg_match('/^website_(.*)-(.*)$/', $filename, $reg))
		{
			$this->errors[]='Bad format for filename '.$filename.'. Must be website_XXX-VERSION.';
			return -1;
		}

		$websitecode = $reg[1];

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."website(ref, entity, description, status) values('".$websitecode."', ".$conf->entity.", 'Portal to sell your SaaS. Do not remove this entry.', 1)";
		$resql = $this->db->query($sql);


		return $result;
	}

}

