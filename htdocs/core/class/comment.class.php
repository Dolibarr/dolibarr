<?php
/* Copyright (C) 2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 * 	Class to manage comment
 */
class Comment extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'comment';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'comment';

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element = '';

	public $element_type;

	/**
	 * @var string description
	 */
	public $description;

	/**
     * Date modification record (tms)
     *
     * @var integer
     */
	public $tms;

	/**
     * Date creation record (datec)
     *
     * @var integer
     */
    public $datec;

	/**
     * @var int ID
     */
	public $fk_user_author;

	/**
     * @var int ID
     */
	public $fk_user_modif;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $import_key;

	public $comments = array();

	public $oldcopy;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create into database
	 *
	 *  @param	User	$user        	User that create
	 *  @param 	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int 		        	<0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $user;

		$error = 0;

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "description";
		$sql .= ", datec";
		$sql .= ", fk_element";
		$sql .= ", element_type";
		$sql .= ", fk_user_author";
		$sql .= ", fk_user_modif";
		$sql .= ", entity";
		$sql .= ", import_key";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->description)."'";
		$sql .= ", ".($this->datec != '' ? "'".$this->db->idate($this->datec)."'" : 'null');
		$sql .= ", '".(isset($this->fk_element) ? $this->fk_element : "null")."'";
		$sql .= ", '".$this->db->escape($this->element_type)."'";
		$sql .= ", '".(isset($this->fk_user_author) ? $this->fk_user_author : "null")."'";
		$sql .= ", ".$user->id."";
		$sql .= ", ".(!empty($this->entity) ? $this->entity : '1');
		$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= ")";

		//var_dump($this->db);
		//echo $sql;

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (!$error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('TASK_COMMENT_CREATE', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id			Id object
	 *  @param	int		$ref		ref object
	 *  @return int 		        <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid,";
		$sql .= " c.description,";
		$sql .= " c.datec,";
		$sql .= " c.tms,";
		$sql .= " c.fk_element,";
		$sql .= " c.element_type,";
		$sql .= " c.fk_user_author,";
		$sql .= " c.fk_user_modif,";
		$sql .= " c.entity,";
		$sql .= " c.import_key";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
		$sql .= " WHERE c.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num_rows = $this->db->num_rows($resql);

			if ($num_rows)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->description = $obj->description;
				$this->element_type = $obj->element_type;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_element			= $obj->fk_element;
				$this->entity = $obj->entity;
				$this->import_key			= $obj->import_key;
			}

			$this->db->free($resql);

			if ($num_rows) return 1;
			else return 0;
		}
		else
		{
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int			         	<=0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $user;
		$error = 0;

		// Clean parameters
		if (isset($this->fk_element)) $this->fk_project = (int) trim($this->fk_element);
		if (isset($this->description)) $this->description = trim($this->description);


		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " description=".(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
		$sql .= " datec=".($this->datec != '' ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " fk_element=".(isset($this->fk_element) ? $this->fk_element : "null").",";
		$sql .= " element_type='".$this->db->escape($this->element_type)."',";
		$sql .= " fk_user_modif=".$user->id.",";
		$sql .= " entity=".(!empty($this->entity) ? $this->entity : '1').",";
		$sql .= " import_key=".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (!$error)
		{
			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('TASK_COMMENT_MODIFY', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Delete task from database
	 *
	 *	@param	User	$user        	User that delete
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid=".$this->id;

		$resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (!$error)
		{
			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('TASK_COMMENT_DELETE', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 * Load comments linked with current task
	 *
	 * @param	string		$element_type		Element type
	 * @param	int			$fk_element			Id of element
	 * @return 	array							Comment array
	 */
	public function fetchAllFor($element_type, $fk_element)
	{
		global $db, $conf;
		$this->comments = array();
		if (!empty($element_type) && !empty($fk_element)) {
			$sql = "SELECT";
			$sql .= " c.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as c";
			$sql .= " WHERE c.fk_element = ".$fk_element;
			$sql .= " AND c.element_type = '".$db->escape($element_type)."'";
			$sql .= " AND c.entity = ".$conf->entity;
			$sql .= " ORDER BY c.tms DESC";

			dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num_rows = $db->num_rows($resql);
				if ($num_rows > 0)
				{
					while ($obj = $db->fetch_object($resql))
					{
						$comment = new self($db);
						$comment->fetch($obj->rowid);
						$this->comments[] = $comment;
					}
				}
				$db->free($resql);
			} else {
				$this->errors[] = "Error ".$this->db->lasterror();
				return -1;
			}
		}

		return count($this->comments);
	}
}
