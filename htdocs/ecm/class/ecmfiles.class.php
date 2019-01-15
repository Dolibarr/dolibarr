<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Francis Appels      <francis.appels@yahoo.com>
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
class EcmFiles extends CommonObject
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
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';

	/**
	 * @var string Ref hash of file path
	 */
	public $ref;

	/**
	 * hash of file content (md5_file(dol_osencode($destfull))
     * @var string Ecm Files label
     */
    public $label;

	public $share;					// hash for file sharing, empty by default (example: getRandomPassword(true))

	/**
	 * @var int Entity
	 */
	public $entity;

	public $filename;
	public $filepath;
	public $fullpath_orig;

	/**
	 * @var string description
	 */
	public $description;

	public $keywords;
	public $cover;
	public $position;
	public $gen_or_uploaded;       // can be 'generated', 'uploaded', 'unknown'
	public $extraparams;
	public $date_c = '';
	public $date_m = '';

	/**
     * @var int ID
     */
	public $fk_user_c;

	/**
     * @var int ID
     */
	public $fk_user_m;

	public $acl;
	public $src_object_type;
	public $src_object_id;


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
	 * @param  User $user      	User that creates
	 * @param  bool $notrigger 	false=launch triggers after, true=disable triggers
	 * @return int 				<0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			 $this->ref = trim($this->ref);
		}
		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->share)) {
			 $this->share = trim($this->share);
		}
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->filename)) {
			 $this->filename = trim($this->filename);
		}
		if (isset($this->filepath)) {
			 $this->filepath = trim($this->filepath);
			 $this->filepath = preg_replace('/[\\/]+$/', '', $this->filepath);		// Remove last /
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
		if (isset($this->src_object_type)) {
			$this->src_object_type = trim($this->src_object_type);
		}
		if (empty($this->date_c)) $this->date_c = dol_now();
		if (empty($this->date_m)) $this->date_m = dol_now();

		// If ref not defined
		$ref = '';
		if (! empty($this->ref))
		{
			$ref=$this->ref;
		}
		else {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
			$ref = dol_hash($this->filepath.'/'.$this->filename, 3);
		}

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
			else
			{
				$this->errors[] = 'Error ' . $this->db->lasterror();
				return --$error;
			}
			$maxposition=$maxposition+1;
		}
		else
		{
			$maxposition=$this->position;
		}

		// Check parameters
		if (empty($this->filename) || empty($this->filepath))
		{
			$this->errors[] = 'Bad property filename or filepath';
			return --$error;
		}
		if (! isset($this->entity))
		{
			$this->entity = $conf->entity;
		}
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'ref,';
		$sql.= 'label,';
		$sql.= 'share,';
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
		$sql.= 'acl,';
		$sql.= 'src_object_type,';
		$sql.= 'src_object_id';
		$sql .= ') VALUES (';
		$sql .= " '".$ref."', ";
		$sql .= ' '.(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").',';
		$sql .= ' '.(! isset($this->share)?'NULL':"'".$this->db->escape($this->share)."'").',';
		$sql .= ' '.$this->entity.',';
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
		$sql .= ' '.(! isset($this->acl)?'NULL':"'".$this->db->escape($this->acl)."'").',';
		$sql .= ' '.(! isset($this->src_object_type)?'NULL':"'".$this->db->escape($this->src_object_type)."'").',';
		$sql .= ' '.(! isset($this->src_object_id)?'NULL':$this->src_object_id);
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

			// Triggers
			if (! $notrigger)
			{
				// Call triggers
				$result=$this->call_trigger(strtoupper(get_class($this)).'_CREATE',$user);
				if ($result < 0) { $error++; }
				// End call triggers
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
	 * @param  int    $id          	   	Id object
	 * @param  string $ref         	   	Hash of file name (filename+filepath). Not always defined on some version.
	 * @param  string $relativepath    	Relative path of file from document directory. Example: path/path2/file
	 * @param  string $hashoffile      	Hash of file content. Take the first one found if same file is at different places. This hash will also change if file content is changed.
	 * @param  string $hashforshare    	Hash of file sharing.
	 * @param  string $src_object_type 	src_object_type to search
	 * @param  string $src_object_id 	src_object_id to search
	 * @return int                 	   	<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '', $relativepath = '', $hashoffile='', $hashforshare='', $src_object_type='', $src_object_id=0)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.ref,";
		$sql .= " t.label,";
		$sql .= " t.share,";
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
		$sql .= " t.acl,";
		$sql .= " t.src_object_type,";
		$sql .= " t.src_object_id";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql.= ' WHERE 1 = 1';
		/* Fetching this table depends on filepath+filename, it must not depends on entity
		if (! empty($conf->multicompany->enabled)) {
		    $sql .= " AND entity IN (" . getEntity('ecmfiles') . ")";
		}*/
		if ($relativepath) {
			$sql .= " AND t.filepath = '" . $this->db->escape(dirname($relativepath)) . "' AND t.filename = '".$this->db->escape(basename($relativepath))."'";
			$sql .= " AND t.entity = ".$conf->entity;				// unique key include the entity so each company has its own index
		}
		elseif (! empty($ref)) {		// hash of file path
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
			$sql .= " AND t.entity = ".$conf->entity;				// unique key include the entity so each company has its own index
		}
		elseif (! empty($hashoffile)) {	// hash of content
			$sql .= " AND t.label = '".$this->db->escape($hashoffile)."'";
			$sql .= " AND t.entity = ".$conf->entity;				// unique key include the entity so each company has its own index
		}
		elseif (! empty($hashforshare)) {
			$sql .= " AND t.share = '".$this->db->escape($hashforshare)."'";
			//$sql .= " AND t.entity = ".$conf->entity;							// hashforshare already unique
		}
		elseif ($src_object_type && $src_object_id)
		{
			// Warning: May return several record, and only first one is returned !
			$sql .= " AND t.src_object_type ='".$this->db->escape($src_object_type)."' AND t.src_object_id = ".$this->db->escape($src_object_id);
			$sql .= " AND t.entity = ".$conf->entity;
		}
		else {
			$sql .= ' AND t.rowid = '.$this->db->escape($id);					// rowid already unique
		}

		$this->db->plimit(1);	// When we search on src or on hash of content (hashforfile) to solve hash conflict when several files has same content, we take first one only
		$this->db->order('t.rowid', 'ASC');

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->label = $obj->label;
				$this->share = $obj->share;
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
				$this->src_object_type = $obj->src_object_type;
				$this->src_object_id = $obj->src_object_id;
			}

			// Retrieve all extrafields for invoice
			// fetch optionals attributes and labels
			// $this->fetch_optionals();

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

			return -1;
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
		$sql .= " t.share,";
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
		$sql .= " t.acl,";
		$sql .= " t.src_object_type,";
		$sql .= " t.src_object_id";
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
				$line->ref = $obj->ref;
				$line->label = $obj->label;
				$line->share = $obj->share;
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
				$line->src_object_type = $obj->src_object_type;
				$line->src_object_id = $obj->src_object_id;
				$this->lines[] = $line;
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
		global $conf;

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		if (isset($this->ref)) {
			 $this->ref = trim($this->ref);
		}
		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->share)) {
			 $this->share = trim($this->share);
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
		if (isset($this->fk_user_m)) {
			 $this->fk_user_m = trim($this->fk_user_m);
		}
		if (isset($this->acl)) {
			 $this->acl = trim($this->acl);
		}
		if (isset($this->src_object_type)) {
			$this->src_object_type = trim($this->src_object_type);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= " ref = '".dol_hash($this->filepath.'/'.$this->filename, 3)."',";
		$sql .= ' label = '.(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").',';
		$sql .= ' share = '.(! empty($this->share)?"'".$this->db->escape($this->share)."'":"null").',';
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
		$sql .= ' fk_user_m = '.($this->fk_user_m > 0?$this->fk_user_m:$user->id).',';
		$sql .= ' acl = '.(isset($this->acl)?"'".$this->db->escape($this->acl)."'":"null").',';
		$sql .= ' src_object_id = '.($this->src_object_id > 0?$this->src_object_id:"null").',';
		$sql .= ' src_object_type = '.(isset($this->src_object_type)?"'".$this->db->escape($this->src_object_type)."'":"null");
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		// Triggers
		if (! $error && ! $notrigger)
		{
			// Call triggers
			$result=$this->call_trigger(strtoupper(get_class($this)).'_MODIFY',$user);
			if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
			// End call triggers
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

		// Triggers
		if (! $notrigger)
		{
			// Call triggers
			$result=$this->call_trigger(strtoupper(get_class($this)).'_DELETE',$user);
			if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
			// End call triggers
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
        // phpcs:enable
		global $langs;
		return '';
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
		$this->src_object_type = 'product';
		$this->src_object_id = 1;
	}
}


class EcmfilesLine
{
	/**
     * @var string ECM files line label
     */
    public $label;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $filename;
	public $filepath;
	public $fullpath_orig;

	/**
	 * @var string description
	 */
	public $description;

	public $keywords;
	public $cover;
	public $position;
	public $gen_or_uploaded;       // can be 'generated', 'uploaded', 'unknown'
	public $extraparams;
	public $date_c = '';
	public $date_m = '';

	/**
     * @var int ID
     */
	public $fk_user_c;

	/**
     * @var int ID
     */
	public $fk_user_m;

	public $acl;
	public $src_object_type;
	public $src_object_id;
}
