<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
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
 * \file    ecm/ecmfiles.class.php
 * \ingroup ecm
 * \brief   Class to manage ECM Files (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class to manage ECM files
 */
class EcmFiles //extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'ecmfiles';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'ecm_files';

	/**
	 */
	public $label;
	public $entity;
	public $filename;
	public $filepath;
	public $fullpath_orig;
	public $description;
	public $keywords;
	public $cover;
	public $position;
	public $gen_or_uploaded;       // can be 'generated', 'uploaded', 'unknown'
	public $extraparams;
	public $date_c = '';
	public $date_m = '';
	public $fk_user_c;
	public $fk_user_m;
	public $acl;

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
	    global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters

		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->filename)) {
			 $this->filename = trim($this->filename);
		}
		if (isset($this->filepath)) {
			 $this->filepath = trim($this->filepath);
		}
		if (isset($this->fullpath_orig)) {
			 $this->fullpath_orig = trim($this->fullpath_orig);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->keywords)) {
			 $this->keywords = trim($this->keywords);
		}
		if (isset($this->cover)) {
			 $this->cover = trim($this->cover);
		}
		if (isset($this->gen_or_uploaded)) {
			 $this->gen_or_uploaded = trim($this->gen_or_uploaded);
		}
		if (isset($this->extraparams)) {
			 $this->extraparams = trim($this->extraparams);
		}
		if (isset($this->fk_user_c)) {
			 $this->fk_user_c = trim($this->fk_user_c);
		}
		if (isset($this->fk_user_m)) {
			 $this->fk_user_m = trim($this->fk_user_m);
		}
		if (isset($this->acl)) {
			 $this->acl = trim($this->acl);
		}
        if (empty($this->date_c)) $this->date_c = dol_now();

        $maxposition=0;
		if (empty($this->position))   // Get max used
		{
		    $sql = "SELECT MAX(position) as maxposition FROM " . MAIN_DB_PREFIX . $this->table_element;
		    $sql.= " WHERE filepath ='".$this->db->escape($this->filepath)."'";

		    $resql = $this->db->query($sql);
		    if ($resql)
		    {
		        $obj = $this->db->fetch_object($resql);
		        $maxposition = (int) $obj->maxposition;
		    }
		    else dol_print_error($this->db);
		}
		$maxposition=$maxposition+1;

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'label,';
		$sql.= 'entity,';
		$sql.= 'filename,';
		$sql.= 'filepath,';
		$sql.= 'fullpath_orig,';
		$sql.= 'description,';
		$sql.= 'keywords,';
		$sql.= 'cover,';
		$sql.= 'position,';
		$sql.= 'gen_or_uploaded,';
		$sql.= 'extraparams,';
		$sql.= 'date_c,';
		$sql.= 'date_m,';
		$sql.= 'fk_user_c,';
		$sql.= 'fk_user_m,';
		$sql.= 'acl';
		$sql .= ') VALUES (';
		$sql .= ' '.(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").',';
		$sql .= ' '.(! isset($this->entity)?$conf->entity:$this->entity).',';
		$sql .= ' '.(! isset($this->filename)?'NULL':"'".$this->db->escape($this->filename)."'").',';
		$sql .= ' '.(! isset($this->filepath)?'NULL':"'".$this->db->escape($this->filepath)."'").',';
		$sql .= ' '.(! isset($this->fullpath_orig)?'NULL':"'".$this->db->escape($this->fullpath_orig)."'").',';
		$sql .= ' '.(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").',';
		$sql .= ' '.(! isset($this->keywords)?'NULL':"'".$this->db->escape($this->keywords)."'").',';
		$sql .= ' '.(! isset($this->cover)?'NULL':"'".$this->db->escape($this->cover)."'").',';
		$sql .= ' '.$maxposition.',';
		$sql .= ' '.(! isset($this->gen_or_uploaded)?'NULL':"'".$this->db->escape($this->gen_or_uploaded)."'").',';
		$sql .= ' '.(! isset($this->extraparams)?'NULL':"'".$this->db->escape($this->extraparams)."'").',';
		$sql .= ' '."'".$this->db->idate($this->date_c)."'".',';
		$sql .= ' '.(! isset($this->date_m) || dol_strlen($this->date_m)==0?'NULL':"'".$this->db->idate($this->date_m)."'").',';
		$sql .= ' '.(! isset($this->fk_user_c)?$user->id:$this->fk_user_c).',';
		$sql .= ' '.(! isset($this->fk_user_m)?'NULL':$this->fk_user_m).',';
		$sql .= ' '.(! isset($this->acl)?'NULL':"'".$this->db->escape($this->acl)."'");
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            $this->position = $maxposition;

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
	 * @param  int    $id          Id object
	 * @param  string $ref         Not used yet. Will contains a hash id from filename+filepath
	 * @param  string $fullpath    Full path of file (relative path to document directory)
	 * @return int                 <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $fullpath = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.ref,";
		$sql .= " t.label,";
		$sql .= " t.entity,";
		$sql .= " t.filename,";
		$sql .= " t.filepath,";
		$sql .= " t.fullpath_orig,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.cover,";
		$sql .= " t.position,";
		$sql .= " t.gen_or_uploaded,";
		$sql .= " t.extraparams,";
		$sql .= " t.date_c,";
		$sql .= " t.date_m,";
		$sql .= " t.fk_user_c,";
		$sql .= " t.fk_user_m,";
		$sql .= " t.acl";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql.= ' WHERE 1 = 1';
		/* Fetching this table depends on filepath+filename, it must not depends on entity
		if (! empty($conf->multicompany->enabled)) {
		    $sql .= " AND entity IN (" . getEntity('ecmfiles') . ")";
		}*/
		if ($fullpath) {
			$sql .= " AND t.filepath = '" . $this->db->escape(dirname($fullpath)) . "' AND t.filename = '".$this->db->escape(basename($fullpath))."'";
		}
		elseif (null !== $ref) {
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= ' AND t.rowid = ' . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->label = $obj->label;
				$this->entity = $obj->entity;
				$this->filename = $obj->filename;
				$this->filepath = $obj->filepath;
				$this->fullpath_orig = $obj->fullpath_orig;
				$this->description = $obj->description;
				$this->keywords = $obj->keywords;
				$this->cover = $obj->cover;
				$this->position = $obj->position;
				$this->gen_or_uploaded = $obj->gen_or_uploaded;
				$this->extraparams = $obj->extraparams;
				$this->date_c = $this->db->jdate($obj->date_c);
				$this->date_m = $this->db->jdate($obj->date_m);
				$this->fk_user_c = $obj->fk_user_c;
				$this->fk_user_m = $obj->fk_user_m;
				$this->acl = $obj->acl;
			}

			// Retrieve all extrafields for invoice
			// fetch optionals attributes and labels
			/*
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields=new ExtraFields($this->db);
			$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
			$this->fetch_optionals($this->id,$extralabels);
            */
			// $this->fetch_lines();

			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
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
		$sql .= " t.label,";
		$sql .= " t.entity,";
		$sql .= " t.filename,";
		$sql .= " t.filepath,";
		$sql .= " t.fullpath_orig,";
		$sql .= " t.description,";
		$sql .= " t.keywords,";
		$sql .= " t.cover,";
		$sql .= " t.position,";
		$sql .= " t.gen_or_uploaded,";
		$sql .= " t.extraparams,";
		$sql .= " t.date_c,";
		$sql .= " t.date_m,";
		$sql .= " t.fk_user_c,";
		$sql .= " t.fk_user_m,";
		$sql .= " t.acl";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		$sql.= ' WHERE 1 = 1';
		/* Fetching this table depends on filepath+filename, it must not depends on entity
		if (! empty($conf->multicompany->enabled)) {
		    $sql .= " AND entity IN (" . getEntity('ecmfiles') . ")";
		}*/
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);
		}
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
            $sql .=  ' ' . $this->db->plimit($limit, $offset);
		}

		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new EcmfilesLine();

				$line->id = $obj->rowid;

				$line->label = $obj->label;
				$line->entity = $obj->entity;
				$line->filename = $obj->filename;
				$line->filepath = $obj->filepath;
				$line->fullpath_orig = $obj->fullpath_orig;
				$line->description = $obj->description;
				$line->keywords = $obj->keywords;
				$line->cover = $obj->cover;
				$line->position = $obj->position;
				$line->gen_or_uploaded = $obj->gen_or_uploaded;
				$line->extraparams = $obj->extraparams;
				$line->date_c = $this->db->jdate($obj->date_c);
				$line->date_m = $this->db->jdate($obj->date_m);
				$line->fk_user_c = $obj->fk_user_c;
				$line->fk_user_m = $obj->fk_user_m;
				$line->acl = $obj->acl;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

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

		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->filename)) {
			 $this->filename = trim($this->filename);
		}
		if (isset($this->filepath)) {
			 $this->filepath = trim($this->filepath);
		}
		if (isset($this->fullpath_orig)) {
			 $this->fullpath_orig = trim($this->fullpath_orig);
		}
		if (isset($this->description)) {
			 $this->description = trim($this->description);
		}
		if (isset($this->keywords)) {
			 $this->keywords = trim($this->keywords);
		}
		if (isset($this->cover)) {
			 $this->cover = trim($this->cover);
		}
		if (isset($this->gen_or_uploaded)) {
			 $this->gen_or_uploaded = trim($this->gen_or_uploaded);
		}
		if (isset($this->extraparams)) {
			 $this->extraparams = trim($this->extraparams);
		}
		if (isset($this->fk_user_c)) {
			 $this->fk_user_c = trim($this->fk_user_c);
		}
		if (isset($this->fk_user_m)) {
			 $this->fk_user_m = trim($this->fk_user_m);
		}
		if (isset($this->acl)) {
			 $this->acl = trim($this->acl);
		}


		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' label = '.(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").',';
		$sql .= ' entity = '.(isset($this->entity)?$this->entity:$conf->entity).',';
		$sql .= ' filename = '.(isset($this->filename)?"'".$this->db->escape($this->filename)."'":"null").',';
		$sql .= ' filepath = '.(isset($this->filepath)?"'".$this->db->escape($this->filepath)."'":"null").',';
		$sql .= ' fullpath_orig = '.(isset($this->fullpath_orig)?"'".$this->db->escape($this->fullpath_orig)."'":"null").',';
		$sql .= ' description = '.(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").',';
		$sql .= ' keywords = '.(isset($this->keywords)?"'".$this->db->escape($this->keywords)."'":"null").',';
		$sql .= ' cover = '.(isset($this->cover)?"'".$this->db->escape($this->cover)."'":"null").',';
		$sql .= ' position = '.(isset($this->position)?$this->db->escape($this->position):"0").',';
		$sql .= ' gen_or_uploaded = '.(isset($this->gen_or_uploaded)?"'".$this->db->escape($this->gen_or_uploaded)."'":"null").',';
		$sql .= ' extraparams = '.(isset($this->extraparams)?"'".$this->db->escape($this->extraparams)."'":"null").',';
		$sql .= ' date_c = '.(! isset($this->date_c) || dol_strlen($this->date_c) != 0 ? "'".$this->db->idate($this->date_c)."'" : 'null').',';
		//$sql .= ' date_m = '.(! isset($this->date_m) || dol_strlen($this->date_m) != 0 ? "'".$this->db->idate($this->date_m)."'" : 'null').','; // Field automatically updated
		$sql .= ' fk_user_c = '.(isset($this->fk_user_c)?$this->fk_user_c:"null").',';
		$sql .= ' fk_user_m = '.($this->fk_user_m > 0?$this->fk_user_m:$user->id).',';
		$sql .= ' acl = '.(isset($this->acl)?"'".$this->db->escape($this->acl)."'":"null");
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
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

		// If you need to delete child tables to, you can insert them here

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
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
		$object = new Ecmfiles($this->db);

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
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
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
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	int  	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $maxlen=24, $morecss='')
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("MyModule") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = DOL_URL_ROOT.'/ecm/'.$this->table_name.'_card.php?id='.$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowProject");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), 'label', ($notooltip?'':'class="classfortooltip"')).$linkend);
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
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
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
		if ($mode == 6)
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
	    global $conf,$user;

		$this->id = 0;

		$this->label = '0a1b2c3e4f59999999';
		$this->entity = '1';
		$this->filename = 'myspecimenfilefile.pdf';
		$this->filepath = '/aaa/bbb';
		$this->fullpath_orig = 'c:/file on my disk.pdf';
		$this->description = 'This is a long description of file';
		$this->keywords = 'key1,key2';
		$this->cover = '1';
		$this->position = '5';
		$this->gen_or_uploaded = 'uploaded';
		$this->extraparams = '';
		$this->date_c = (dol_now() - 3600 * 24 * 10);
		$this->date_m = '';
		$this->fk_user_c = $user->id;
		$this->fk_user_m = '';
		$this->acl = '';
	}

}


class EcmfilesLine
{
	public $label;
	public $entity;
	public $filename;
	public $filepath;
	public $fullpath_orig;
	public $description;
	public $keywords;
	public $cover;
	public $position;
	public $gen_or_uploaded;       // can be 'generated', 'uploaded', 'unknown'
	public $extraparams;
	public $date_c = '';
	public $date_m = '';
	public $fk_user_c;
	public $fk_user_m;
	public $acl;
}
