<?php
/* Copyright (C) 2007-2018  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var mixed
	 */
	public $date_creation;
	public $date_modification;

	/**
	 * @var integer
	 */
	public $fk_default_home;
	public $fk_user_creat;

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
		if (empty($this->date_creation)) {
            $this->date_creation = $now;
        }
		if (empty($this->date_modification)) {
            $this->date_modification = $now;
        }

		// Check parameters
		if (empty($this->entity)) {
            $this->entity = $conf->entity;
        }

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'entity,';
		$sql.= 'ref,';
		$sql.= 'description,';
		$sql.= 'status,';
		$sql.= 'fk_default_home,';
		$sql.= 'virtualhost,';
		$sql.= 'fk_user_creat,';
		$sql.= 'date_creation,';
		$sql.= 'tms';
		$sql .= ') VALUES (';
		$sql .= ' '.((empty($this->entity) && $this->entity != '0')?'NULL':$this->entity).',';
		$sql .= ' '.(! isset($this->ref)?'NULL':"'".$this->db->escape($this->ref)."'").',';
		$sql .= ' '.(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").',';
		$sql .= ' '.(! isset($this->status)?'1':$this->status).',';
		$sql .= ' '.(! isset($this->fk_default_home)?'NULL':$this->fk_default_home).',';
		$sql .= ' '.(! isset($this->virtualhost)?'NULL':"'".$this->db->escape($this->virtualhost)."'").",";
		$sql .= ' '.(! isset($this->fk_user_creat)?$user->id:$this->fk_user_creat).',';
		$sql .= ' '.(! isset($this->date_creation) || dol_strlen($this->date_creation)==0?'NULL':"'".$this->db->idate($this->date_creation)."'").",";
		$sql .= ' '.(! isset($this->date_modification) || dol_strlen($this->date_modification)==0?'NULL':"'".$this->db->idate($this->date_modification)."'");
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

            // Uncomment this and change MYOBJECT to your own tag if you
            // want this action to call a trigger.
            // if (!$notrigger) {

            //     // Call triggers
            //     $result = $this->call_trigger('MYOBJECT_CREATE',$user);
            //     if ($result < 0) $error++;
            //     // End call triggers
            // }
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
		$sql .= " t.fk_user_creat,";
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
				$this->fk_user_creat = $obj->fk_user_creat;
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
		$sql .= " t.fk_user_creat,";
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
				$this->fk_user_creat = $obj->fk_user_creat;
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

		if (! $error && ! empty($this->ref))
		{
			global $dolibarr_main_data_root;
			$pathofwebsite=$dolibarr_main_data_root.'/website/'.$this->ref;

			dol_delete_dir_recursive($pathofwebsite);
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
	 * @param	string	$newlang	New language
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone($user, $fromid, $newref, $newlang='')
	{
        global $hookmanager, $langs;
		global $dolibarr_main_data_root;

		$now = dol_now();
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
		$object->date_creation = $now;
		$object->fk_user_creat = $user->id;

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
				dol_delete_file($filetplold);

				// Create new file
				$objectpagenew = $objectpageold->createFromClone($user, $pageid, $objectpageold->pageurl, '', 0, $object->id, 1);

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
		        setEventMessages($object->error, $object->errors, 'errors');
		    }

		    if (! $error)
		    {
		    	$filetpl=$pathofwebsitenew.'/page'.$newidforhome.'.tpl.php';
		    	$filewrapper=$pathofwebsitenew.'/wrapper.php';

		    	// Generate the index.php page to be the home page
		    	//-------------------------------------------------
		    	$result = dolSaveIndexPage($pathofwebsitenew, $fileindex, $filetpl, $filewrapper);
		    }
		}

		unset($object->context['createfromclone']);

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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	function LibStatut($status,$mode=0)
	{
        // phpcs:enable
		global $langs;

		if ($mode == 0 || $mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 5)
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
		$this->fk_user_creat = $user->id;
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
		global $conf, $mysoc;

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

		$destdir = $conf->website->dir_temp.'/'.$website->ref;

		dol_syslog("Clear temp dir ".$destdir);
		$count=0; $countreallydeleted=0;
		$counttodelete = dol_delete_dir_recursive($destdir, $count, 1, 0, $countreallydeleted);
		if ($counttodelete != $countreallydeleted)
		{
			setEventMessages("Failed to clean temp directory ".$destdir, null, 'errors');
			return '';
		}

		$arrayreplacement=array();

		$srcdir = $conf->website->dir_output.'/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/containers';

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

		$srcdir = DOL_DATA_ROOT.'/medias/image/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/image/websitekey';

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

		$srcdir = DOL_DATA_ROOT.'/medias/js/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/js/websitekey';

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
			$allaliases = $objectpageold->pageurl;
			$allaliases.= ($objectpageold->aliasalt ? ','.$objectpageold->aliasalt : '');

			$line = '-- Page ID '.$objectpageold->id.' -> '.$objectpageold->newid.'__+MAX_llx_website_page__ - Aliases '.$allaliases.' --;';	// newid start at 1, 2...
			$line.= "\n";
			fputs($fp, $line);

			// Warning: We must keep llx_ here. It is a generic SQL.
			$line = 'INSERT INTO llx_website_page(rowid, fk_page, fk_website, pageurl, aliasalt, title, description, image, keywords, status, date_creation, tms, lang, import_key, grabbed_from, type_container, htmlheader, content)';

			$line.= " VALUES(";
			$line.= $objectpageold->newid."__+MAX_llx_website_page__, ";
			$line.= ($objectpageold->newfk_page ? $this->db->escape($objectpageold->newfk_page)."__+MAX_llx_website_page__" : "null").", ";
			$line.= "__WEBSITE_ID__, ";
			$line.= "'".$this->db->escape($objectpageold->pageurl)."', ";
			$line.= "'".$this->db->escape($objectpageold->aliasalt)."', ";
			$line.= "'".$this->db->escape($objectpageold->title)."', ";
			$line.= "'".$this->db->escape($objectpageold->description)."', ";
			$line.= "'".$this->db->escape($objectpageold->image)."', ";
			$line.= "'".$this->db->escape($objectpageold->keywords)."', ";
			$line.= "'".$this->db->escape($objectpageold->status)."', ";
			$line.= "'".$this->db->idate($objectpageold->date_creation)."', ";
			$line.= "'".$this->db->idate($objectpageold->date_modification)."', ";
			$line.= "'".$this->db->escape($objectpageold->lang)."', ";
			$line.= ($objectpageold->import_key ? "'".$this->db->escape($objectpageold->import_key)."'" : "null").", ";
			$line.= "'".$this->db->escape($objectpageold->grabbed_from)."', ";
			$line.= "'".$this->db->escape($objectpageold->type_container)."', ";

			$stringtoexport = $objectpageold->htmlheader;
			$stringtoexport = str_replace(array("\r\n","\r","\n"), "__N__", $stringtoexport);
			$stringtoexport = str_replace('file=image/'.$website->ref.'/', "file=image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=js/'.$website->ref.'/', "file=js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/image/'.$website->ref.'/', "medias/image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/js/'.$website->ref.'/', "medias/js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_small, "file=logos%2Fthumbs%2F__LOGO_SMALL_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_mini, "file=logos%2Fthumbs%2F__LOGO_MINI_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo, "file=logos%2Fthumbs%2F__LOGO_KEY__", $stringtoexport);
			$line.= "'".$this->db->escape(str_replace(array("\r\n","\r","\n"), "__N__", $stringtoexport))."', ";	// Replace \r \n to have record on 1 line

			$stringtoexport = $objectpageold->content;
			$stringtoexport = str_replace(array("\r\n","\r","\n"), "__N__", $stringtoexport);
			$stringtoexport = str_replace('file=image/'.$website->ref.'/', "file=image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=js/'.$website->ref.'/', "file=js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/image/'.$website->ref.'/', "medias/image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/js/'.$website->ref.'/', "medias/js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_small, "file=logos%2Fthumbs%2F__LOGO_SMALL_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_mini, "file=logos%2Fthumbs%2F__LOGO_MINI_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo, "file=logos%2Fthumbs%2F__LOGO_KEY__", $stringtoexport);
			$line.= "'".$this->db->escape($stringtoexport)."'";		// Replace \r \n to have record on 1 line
			$line.= ");";
			$line.= "\n";
			fputs($fp, $line);

			// Add line to update home page id during import
			//var_dump($this->fk_default_home.' - '.$objectpageold->id.' - '.$objectpageold->newid);exit;
			if ($this->fk_default_home > 0 && ($objectpageold->id == $this->fk_default_home) && ($objectpageold->newid > 0))	// This is the record with home page
			{
			    // Warning: We must keep llx_ here. It is a generic SQL.
			    $line = "UPDATE llx_website SET fk_default_home = ".($objectpageold->newid > 0 ? $this->db->escape($objectpageold->newid)."__+MAX_llx_website_page__" : "null")." WHERE rowid = __WEBSITE_ID__;";
				$line.= "\n";
				fputs($fp, $line);
			}
		}

		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($filesql, octdec($conf->global->MAIN_UMASK));

		// Build zip file
		$filedir  = $conf->website->dir_temp.'/'.$website->ref.'/.';
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
		global $conf, $mysoc;

		$error = 0;

		$object = $this;
		if (empty($object->ref))
		{
			$this->error = 'Function importWebSite called on object not loaded (object->ref is empty)';
			return -1;
		}

		dol_delete_dir_recursive(dirname($pathtofile).'/'.$object->ref);
		dol_mkdir(dirname($pathtofile).'/'.$object->ref);

		$filename = basename($pathtofile);
		if (! preg_match('/^website_(.*)-(.*)$/', $filename, $reg))
		{
			$this->errors[]='Bad format for filename '.$filename.'. Must be website_XXX-VERSION.';
			return -1;
		}

		$result = dol_uncompress($pathtofile, $conf->website->dir_temp.'/'.$object->ref);
		if (! empty($result['error']))
		{
			$this->errors[]='Failed to unzip file '.$pathtofile.'.';
			return -1;
		}


		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/containers', $conf->website->dir_output.'/'.$object->ref, 0, 1);	// Overwrite if exists

		// Now generate the master.inc.php page
		$filemaster=$conf->website->dir_output.'/'.$object->ref.'/master.inc.php';
		$result = dolSaveMasterFile($filemaster);
		if (! $result)
		{
			$this->errors[]='Failed to write file '.$filemaster;
			$error++;
		}

		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/medias/image/websitekey', $conf->website->dir_output.'/'.$object->ref.'/medias/image/'.$object->ref, 0, 1);	// Medias can be shared, do not overwrite if exists
		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/medias/js/websitekey',    $conf->website->dir_output.'/'.$object->ref.'/medias/js/'.$object->ref, 0, 1);	    // Medias can be shared, do not overwrite if exists

		$sqlfile = $conf->website->dir_temp.'/'.$object->ref.'/website_pages.sql';

		$arrayreplacement = array();
		$arrayreplacement['__WEBSITE_ID__'] = $object->id;
		$arrayreplacement['__WEBSITE_KEY__'] = $object->ref;
		$arrayreplacement['__N__'] = $this->db->escape("\n");			// Restore \n
		$arrayreplacement['__LOGO_SMALL_KEY__'] = $this->db->escape($mysoc->logo_small);
		$arrayreplacement['__LOGO_MINI_KEY__'] = $this->db->escape($mysoc->logo_mini);
		$arrayreplacement['__LOGO_KEY__'] = $this->db->escape($mysoc->logo);
		$result = dolReplaceInFile($sqlfile, $arrayreplacement);

		$this->db->begin();

		// Search the $maxrowid because we need it later
		$sqlgetrowid='SELECT MAX(rowid) as max from '.MAIN_DB_PREFIX.'website_page';
		$resql=$this->db->query($sqlgetrowid);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$maxrowid=$obj->max;
		}

		// Load sql record
		$runsql = run_sql($sqlfile, 1, '', 0, '', 'none', 0, 1);	// The maxrowid of table is searched into this function two
		if ($runsql <= 0)
		{
			$this->errors[]='Failed to load sql file '.$sqlfile;
			$error++;
		}

		$objectpagestatic = new WebsitePage($this->db);

		// Make replacement of IDs
		$fp = fopen($sqlfile,"r");
		if ($fp)
		{
			while (! feof($fp))
			{
				// Warning fgets with second parameter that is null or 0 hang.
				$buf = fgets($fp, 65000);
				if (preg_match('/^-- Page ID (\d+)\s[^\s]+\s(\d+).*Aliases\s(.*)\s--;/i', $buf, $reg))
				{
					$oldid = $reg[1];
					$newid = ($reg[2] + $maxrowid);
					$aliasesarray = explode(',', $reg[3]);

					$objectpagestatic->fetch($newid);

					dol_syslog("Found ID ".$oldid." to replace with ID ".$newid." and shortcut aliases to create: ".$reg[3]);

					dol_move($conf->website->dir_output.'/'.$object->ref.'/page'.$oldid.'.tpl.php', $conf->website->dir_output.'/'.$object->ref.'/page'.$newid.'.tpl.php', 0, 1, 0, 0);

					// The move is not enough, so we regenerate page
					$filetpl=$conf->website->dir_output.'/'.$object->ref.'/page'.$newid.'.tpl.php';
					dolSavePageContent($filetpl, $object, $objectpagestatic);

					// Regenerate alternative aliases pages
					foreach($aliasesarray as $aliasshortcuttocreate)
					{
						$filealias=$conf->website->dir_output.'/'.$object->ref.'/'.$aliasshortcuttocreate.'.php';
						dolSavePageAlias($filealias, $object, $objectpagestatic);
					}
				}
			}
		}

		// Regenerate index page to point to new index page
		$pathofwebsite = $conf->website->dir_output.'/'.$object->ref;
		dolSaveIndexPage($pathofwebsite, $pathofwebsite.'/index.php', $pathofwebsite.'/page'.$object->fk_default_home.'.tpl.php', $pathofwebsite.'/wrapper.php');

		if ($error)
		{
			$this->db->rollback();
			return -1;
		}
		else
		{
			$this->db->commit();
			return $object->id;
		}
	}

	/**
	 * Component to select language inside a container (Full CSS Only)
	 *
	 * @param	array|string	$languagecodes			'auto' to show all languages available for page, or language codes array like array('en_US','fr_FR','de_DE','es_ES')
	 * @param	Translate		$weblangs				Language Object
	 * @param	string			$morecss				More CSS class on component
	 * @param	string			$htmlname				Suffix for HTML name
	 * @return 	string									HTML select component
	 */
	public function componentSelectLang($languagecodes, $weblangs, $morecss='', $htmlname='')
	{
		global $websitepagefile, $website;

		if (! is_object($weblangs)) return 'ERROR componentSelectLang called with parameter $weblangs not defined';

		// Load tmppage if we have $websitepagefile defined
		$tmppage=new WebsitePage($this->db);

		$pageid = 0;
		if (! empty($websitepagefile))
		{
			$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), basename($websitepagefile));
			if ($pageid > 0)
			{
				$tmppage->fetch($pageid);
			}
		}

		// Fill with existing translation, nothing if none
		if (! is_array($languagecodes) && $pageid > 0)
		{
			$languagecodes = array();

			$sql ="SELECT wp.rowid, wp.lang, wp.pageurl, wp.fk_page";
			$sql.=" FROM ".MAIN_DB_PREFIX."website_page as wp";
			$sql.=" WHERE wp.fk_website = ".$website->id;
			$sql.=" AND (wp.fk_page = ".$pageid." OR wp.rowid  = ".$pageid;
			if ($tmppage->fk_page > 0) $sql.=" OR wp.fk_page = ".$tmppage->fk_page." OR wp.rowid = ".$tmppage->fk_page;
			$sql.=")";

			$resql = $this->db->query($sql);
			if ($resql)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$newlang = $obj->lang;
					if ($obj->rowid == $pageid) $newlang = $obj->lang;
					if (! in_array($newlang, $languagecodes)) $languagecodes[]=$newlang;
				}
			}
		}
		// Now $languagecodes is always an array

		$languagecodeselected= $weblangs->defaultlang;	// Because we must init with a value, but real value is the lang of main parent container
		if (! empty($websitepagefile))
		{
			$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), basename($websitepagefile));
			if ($pageid > 0)
			{

				$languagecodeselected=$tmppage->lang;
				if (! in_array($tmppage->lang, $languagecodes)) $languagecodes[]=$tmppage->lang;	// We add language code of page into combo list
			}
		}

		$weblangs->load('languages');
		//var_dump($weblangs->defaultlang);

		$url = $_SERVER["REQUEST_URI"];
		$url = preg_replace('/(\?|&)l=([a-zA-Z_]*)/', '', $url);	// We remove param l from url
		//$url = preg_replace('/(\?|&)lang=([a-zA-Z_]*)/', '', $url);	// We remove param lang from url
		$url.= (preg_match('/\?/', $url) ? '&' : '?').'l=';

		$HEIGHTOPTION=40;
		$MAXHEIGHT = 4 * $HEIGHTOPTION;
		$nboflanguage = count($languagecodes);

		$out ='<!-- componentSelectLang'.$htmlname.' -->'."\n";

		$out.= '<style>';
		$out.= '.componentSelectLang'.$htmlname.':hover { height: '.min($MAXHEIGHT, ($HEIGHTOPTION * $nboflanguage)).'px; overflow-x: hidden; overflow-y: '.((($HEIGHTOPTION * $nboflanguage) > $MAXHEIGHT) ? ' scroll' : 'hidden').'; }'."\n";
		$out.= '.componentSelectLang'.$htmlname.' li { line-height: '.$HEIGHTOPTION.'px; }'."\n";
		$out.= '.componentSelectLang'.$htmlname.' {
			display: inline-block;
			padding: 0;
			height: '.$HEIGHTOPTION.'px;
			overflow: hidden;
			transition: all .3s ease;
			margin: 0 50px 0 0;
			vertical-align: top;
		}
		.componentSelectLang'.$htmlname.':hover, .componentSelectLang'.$htmlname.':hover a { background-color: #fff; color: #000 !important; }
		ul.componentSelectLang'.$htmlname.' { width: 150px; }
		ul.componentSelectLang'.$htmlname.':hover .fa { visibility: hidden; }
		.componentSelectLang'.$htmlname.' a { text-decoration: none; width: 100%; }
		.componentSelectLang'.$htmlname.' li { display: block; padding: 0px 20px; }
		.componentSelectLang'.$htmlname.' li:hover { background-color: #EEE; }
		';
		$out.= '</style>';
		$out.= '<ul class="componentSelectLang'.$htmlname.($morecss?' '.$morecss:'').'">';
		if ($languagecodeselected)
		{
			$shortcode = strtolower(substr($languagecodeselected, -2));
			$label = $weblangs->trans("Language_".$languagecodeselected);
			if ($shortcode == 'us') $label = preg_replace('/\s*\(.*\)/', '', $label);
			$out.= '<a href="'.$url.$languagecodeselected.'"><li><img height="12px" src="medias/image/common/flags/'.$shortcode.'.png" style="margin-right: 5px;"/>'.$label;
			$out.= '<span class="fa fa-caret-down" style="padding-left: 5px;" />';
			$out.= '</li></a>';
		}
		$i=0;
		foreach($languagecodes as $languagecode)
		{
			if ($languagecode == $languagecodeselected) continue;	// Already output
			$shortcode = strtolower(substr($languagecode, -2));
			$label = $weblangs->trans("Language_".$languagecode);
			if ($shortcode == 'us') $label = preg_replace('/\s*\(.*\)/', '', $label);
			$out.= '<a href="'.$url.$languagecode.'"><li><img height="12px" src="medias/image/common/flags/'.$shortcode.'.png" style="margin-right: 5px;"/>'.$label;
			if (empty($i) && empty($languagecodeselected)) $out.= '<span class="fa fa-caret-down" style="padding-left: 5px;" />';
			$out.= '</li></a>';
			$i++;
		}
		$out.= '</ul>';

		return $out;
	}
}
