<?php


/**
 * class to manage element properties
 * this class cannot extend common object
 */
class ElementProperties
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var int The object identifier
	 */
	public $id;

	/**
	 * @var string 		Error string containing last error
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'element_properties';


	/**
	 * @var string default usage is a concat like $this->element.'_'.$this->>module_name but old modules or external modules could have old bad practices
	 */
	public $element_type;

	/**
	 * @var string ID to identify targeted managed object in common object
	 */
	public $element;


	/**
	 * @var string the module name in lowercase
	 */
	public $module_name;

	/**
	 * @var string the location of the class without the file name and trailing slash (/).
	 * Will be used for dol_include_once.
	 * Ex: compta/facture/class
	 */
	public $class_dir = null;

	/**
	 * @var string the class filename
	 */
	public $class_file;

	/**
	 * @var string the class name
	 */
	public $class_name;

	/**
	 * @var int creation date
	 */
	public $datec;

	/**
	 * @var int creation date
	 */
	public $tms;

	/**
	 * class constructor
	 * @param DoliDB $db the doliDb instance
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Load object in memory from the database using either the ID, element type or a custom filter
	 * (parameters are mutually exclusive)
	 *
	 * @param	int    $id           Id object
	 * @param	string $element_type element_type
	 * @param	string $moreWhere    More SQL filters (' AND ...')
	 * @return 	int         			<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = null, $element_type = null, $moreWhere = '')
	{
		$sql = /** @lang MySQL */ "SELECT rowid, element_type, element, module_name, class_dir, class_file,class_name, datec, tms";
		$sql .= " FROM ".$this->db->prefix().$this->table_element.' as t';

		if (!empty($id)) {
			$sql .= ' WHERE t.rowid = '.((int) $id);
		} elseif (!empty($element_type)) {
			$sql .= " WHERE t.element_type = '".$this->db->escape($element_type)."'";
		} elseif (!empty($moreWhere)) {
			$sql .= ' WHERE 1 = 1';
		} else {
			$this->setError('Missing fetching parameters');
			return -1;
		}

		if ($moreWhere) {
			$sql .= $moreWhere;
		}

		$obj = $this->db->getRow($sql);
		if ($obj === false) {
			$this->setError('SQL query error');
			return -1;
		} elseif ($obj) {
			$this->id = $obj->rowid;
			$this->element_type = $obj->element_type;
			$this->element = $obj->element;
			$this->module_name = $obj->module_name;
			$this->class_dir = $obj->class_dir;
			$this->class_file = $obj->class_file;
			$this->class_name = $obj->class_name;
			$this->datec = $this->db->jdate($obj->datec);
			$this->tms = $this->db->jdate($obj->tms);

			return 1;
		}

		// no results
		return 0;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	string $element element
	 * @param	string $module_name module name
	 * @param	string $moreWhere    More SQL filters (' AND ...')
	 * @return 	ElementProperties[]|int         			<0 if KO, array of ElementProperties on success
	 */
	public function fetchAll($element = null, $module_name = null, $moreWhere = '')
	{

		$elementsProperties = array();

		$sql = /** @lang MySQL */  "SELECT rowid";
		$sql .= " FROM ".$this->db->prefix().$this->table_element.' as t';
		$sql .= ' WHERE 1 = 1';

		if (!empty($element)) {
			$sql .= " AND t.element = '".$this->db->escape($element)."'";
		}

		if (!empty($module_name)) {
			$sql .= " AND t.module_name = '".$this->db->escape($module_name)."'";
		}

		$sql .= $moreWhere;
		$objs = $this->db->getRows($sql);
		if ($objs === false) {
			$this->setError('SQL query error');
			return -1;
		} elseif (!empty($objs)) {
			foreach ($objs as $obj) {
				$elementProperties = new self($this->db);
				if ($elementProperties->fetch($obj->rowid) > 0) {
					$elementsProperties[$obj->rowid] = $elementProperties;
				} else {
					$elementProperties->setError($elementProperties->error);
					return -1;
				}
			}
		}

		return $elementsProperties;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  bool $notrigger 	false=launch triggers after, true=disable triggers
	 * @return int             	<0 if KO, >0 if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf, $langs;
		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$error = 0;

		if (empty($this->tms)) {
			$this->tms = dol_now();
		}

		if (empty($this->datec)) {
			$this->datec = dol_now();
		}


		$sql = /** @lang MySQL */ 'INSERT INTO  '.$this->db->prefix().$this->table_element;
		$sql .=  "(";
		$sql .= " rowid, element_type, element, module_name, class_dir, class_file, class_name, datec, tms ";
		$sql .= ")  VALUES (";
		$sql .= " NULL ,";
		$sql .= "'".$this->db->escape($this->element_type)."',";
		$sql .= "'".$this->db->escape($this->element)."',";
		$sql .= "'".$this->db->escape($this->module_name)."',";
		$sql .= "'".$this->db->escape($this->class_dir)."',";
		$sql .= "'".$this->db->escape($this->class_file)."',";
		$sql .= "'".$this->db->escape($this->class_name)."',";
		$sql .= "'".$this->db->idate($this->datec)."',";
		$sql .= "'".$this->db->idate($this->tms)."'";
		$sql .= ")";

		$this->db->begin();

		if (!$error) {
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}

			if (!$error) {
				$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			}
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper(get_class($this)).'_CREATE', $user);
			if ($result < 0) {
				$error++;
			} //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  bool $notrigger 	false=launch triggers after, true=disable triggers
	 * @return int             	<0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		global $conf, $langs;
		dol_syslog(get_class($this)."::update", LOG_DEBUG);

		$error = 0;

		$this->tms = dol_now();

		$sql = /** @lang MySQL */ 'UPDATE '.$this->db->prefix().$this->table_element;
		$sql .= ' SET ';
		$sql .= " element_type = '".$this->db->escape($this->element_type)."' ,";
		$sql .= " element = '".$this->db->escape($this->element)."' ,";
		$sql .= " module_name = '".$this->db->escape($this->module_name)."' ,";
		$sql .= " class_dir = '".$this->db->escape($this->class_dir)."' ,";
		$sql .= " class_file = '".$this->db->escape($this->class_file)."' ,";
		$sql .= " class_name = '".$this->db->escape($this->class_name)."' ,";
		$sql .= " datec = '".$this->db->idate($this->datec)."' ,";
		$sql .= " tms = '".$this->db->idate($this->tms)."'";
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		if (!$error) {
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
			if ($result < 0) {
				$error++;
			} //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * set error for this object
	 * @param $msg error message
	 * @return void
	 */
	protected function setError($msg)
	{
		if (!empty($msg)) {
			$this->error = $msg;
			$this->errors[] = $msg;
		}
	}
}
