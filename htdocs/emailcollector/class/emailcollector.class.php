<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file        emailcollector/class/emailcollector.class.php
 * \ingroup     emailcollector
 * \brief       This file is a CRUD class file for EmailCollector (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';


/**
 * Class for EmailCollector
 */
class EmailCollector extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'emailcollector';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'emailcollector_emailcollector';
	/**
	 * @var int  Does emailcollector support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;
	/**
	 * @var int  Does emailcollector support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for emailcollector. Must be the part after the 'object_' into object_emailcollector.png
	 */
	public $picto = 'email';

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_emailcollector';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();
	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('emailcollector_emailcollectorfilter', 'emailcollector_emailcollectoraction');


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>2, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'default'=>1, 'notnull'=>1, 'index'=>1, 'position'=>20),
		'ref'           => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'help'=>'Example: MyCollector1'),
		'label'         => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1, 'help'=>'Example: My Email collector'),
		'description'   => array('type'=>'text', 'label'=>'Description', 'visible'=>-1, 'enabled'=>1, 'position'=>60, 'notnull'=>-1),
		'host'          => array('type'=>'varchar(255)', 'label'=>'EMailHost', 'visible'=>1, 'enabled'=>1, 'position'=>90, 'notnull'=>1, 'searchall'=>1, 'comment'=>"IMAP server", 'help'=>'Example: imap.gmail.com'),
		'hostcharset'   => array('type'=>'varchar(16)', 'label'=>'HostCharset', 'visible'=>-1, 'enabled'=>1, 'position'=>91, 'notnull'=>0, 'searchall'=>0, 'comment'=>"IMAP server charset", 'help'=>'Example: "UTF-8" (May be "US-ASCII" with some Office365)'),
		'login'         => array('type'=>'varchar(128)', 'label'=>'Login', 'visible'=>1, 'enabled'=>1, 'position'=>101, 'notnull'=>-1, 'index'=>1, 'comment'=>"IMAP login", 'help'=>'Example: myaccount@gmail.com'),
		'password'      => array('type'=>'password', 'label'=>'Password', 'visible'=>-1, 'enabled'=>1, 'position'=>102, 'notnull'=>-1, 'comment'=>"IMAP password", 'help'=>'WithGMailYouCanCreateADedicatedPassword'),
		'source_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxSourceDirectory', 'visible'=>-1, 'enabled'=>1, 'position'=>103, 'notnull'=>1, 'default' => 'Inbox', 'help'=>'Example: INBOX'),
		//'filter' => array('type'=>'text', 'label'=>'Filter', 'visible'=>1, 'enabled'=>1, 'position'=>105),
		//'actiontodo' => array('type'=>'varchar(255)', 'label'=>'ActionToDo', 'visible'=>1, 'enabled'=>1, 'position'=>106),
		'target_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxTargetDirectory', 'visible'=>1, 'enabled'=>1, 'position'=>110, 'notnull'=>0, 'help'=>"EmailCollectorTargetDir"),
		'maxemailpercollect' => array('type'=>'integer', 'label'=>'MaxEmailCollectPerCollect', 'visible'=>-1, 'enabled'=>1, 'position'=>111, 'default'=>100),
		'datelastresult' => array('type'=>'datetime', 'label'=>'DateLastCollectResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>121, 'notnull'=>-1,),
		'codelastresult' => array('type'=>'varchar(16)', 'label'=>'CodeLastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>122, 'notnull'=>-1,),
		'lastresult' => array('type'=>'varchar(255)', 'label'=>'LastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>123, 'notnull'=>-1,),
		'datelastok' => array('type'=>'datetime', 'label'=>'DateLastcollectResultOk', 'visible'=>1, 'enabled'=>'$action != "create"', 'position'=>125, 'notnull'=>-1,),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'visible'=>0, 'enabled'=>1, 'position'=>61, 'notnull'=>-1,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'visible'=>0, 'enabled'=>1, 'position'=>62, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		//'date_validation' => array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'visible'=>-2, 'enabled'=>1, 'position'=>510, 'notnull'=>1,),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'visible'=>-2, 'enabled'=>1, 'position'=>511, 'notnull'=>-1,),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'visible'=>-2, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Inactive', '1'=>'Active'))
	);


	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string label
	 */
	public $label;


	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var int timestamp
	 */
	public $tms;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var string import key
	 */
	public $import_key;


	public $host;
	public $hostcharset;
	public $login;
	public $password;
	public $source_directory;
	public $target_directory;
	public $maxemailpercollect;

	/**
	 * @var integer|string $datelastresult
	 */
	public $datelastresult;


	public $lastresult;
	// END MODULEBUILDER PROPERTIES

	public $filters;
	public $actions;

	public $debuginfo;

	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach ($this->fields as $key => $val)
		{
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
			{
				foreach ($val['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$id = $this->createCommon($user, $notrigger);

		if (is_array($this->filters) && count($this->filters)) {
			$emailcollectorfilter = new EmailCollectorFilter($this->db);

			foreach ($this->filters as $filter) {
				$emailcollectorfilter->type = $filter['type'];
				$emailcollectorfilter->rulevalue = $filter['rulevalue'];
				$emailcollectorfilter->fk_emailcollector = $this->id;
				$emailcollectorfilter->status = $filter['status'];

				$emailcollectorfilter->create($user);
			}
		}

		if (is_array($this->filters) && count($this->filters)) {
			$emailcollectoroperation = new EmailCollectorAction($this->db);

			foreach ($this->actions as $operation) {
				$emailcollectoroperation->type = $operation['type'];
				$emailcollectoroperation->actionparam = $operation['actionparam'];
				$emailcollectoroperation->fk_emailcollector = $this->id;
				$emailcollectoroperation->status = $operation['status'];
				$emailcollectoroperation->position = $operation['position'];

				$emailcollectoroperation->create($user);
			}
		}

		return $id;
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);

		$object->fetchFilters(); // Rules
		$object->fetchActions(); // Operations

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		$object->title = $langs->trans("CopyOf")." ".$object->title;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0)
		{
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option)
			{
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
				{
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		//if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	/*
    public function fetchLines()
    {
        $this->lines=array();

        // Load lines with object EmailCollectorLine

        return count($this->lines)?1:0;
    }
    */

	/**
	 * Fetch all account and load objects into an array
	 *
	 * @param   User    $user           User
	 * @param   int     $activeOnly     filter if active
	 * @param   string  $sortfield      field for sorting
	 * @param   string  $sortorder      sorting order
	 * @param   int     $limit          sort limit
	 * @param   int     $page           page to start on
	 * @return  array   Array with key => EmailCollector object
	 */
	public function fetchAll(User $user, $activeOnly = 0, $sortfield = 's.rowid', $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		global $langs;

		$obj_ret = array();

		$sql = "SELECT s.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector as s";
		$sql .= ' WHERE s.entity IN ('.getEntity('emailcollector').')';
		if ($activeOnly) {
			$sql .= " AND s.status = 1";
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$emailcollector_static = new EmailCollector($this->db);
				if ($emailcollector_static->fetch($obj->rowid)) {
					$obj_ret[] = $emailcollector_static;
				}
				$i++;
			}
		} else {
			$this->errors[] = 'EmailCollector::fetchAll Error when retrieve emailcollector list';
			dol_syslog('EmailCollector::fetchAll Error when retrieve emailcollector list', LOG_ERR);
			$ret = -1;
		}
		if (!count($obj_ret)) {
			dol_syslog('EmailCollector::fetchAll No emailcollector found', LOG_DEBUG);
		}

		return $obj_ret;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>'.$langs->trans("EmailCollector").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/admin/emailcollector_card.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowEmailCollector");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

			/*
             $hookmanager->initHooks(array('myobjectdao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
		} else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('emailcollectordao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_ENABLED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->trans('Disabled');
		}

		$statusType = 'status5';
		if ($status == self::STATUS_ENABLED) $statusType = 'status4';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as 	datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
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
		$this->initAsSpecimenCommon();
	}

	/**
	 * Fetch filters
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 * @see fetchActions()
	 */
	public function fetchFilters()
	{
		$this->filters = array();

		$sql = 'SELECT rowid, type, rulevalue, status';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectorfilter';
		$sql .= ' WHERE fk_emailcollector = '.$this->id;
		//$sql.= ' ORDER BY position';

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->filters[$obj->rowid] = array('id'=>$obj->rowid, 'type'=>$obj->type, 'rulevalue'=>$obj->rulevalue, 'status'=>$obj->status);
				$i++;
			}
			$this->db->free($resql);
		} else dol_print_error($this->db);

		return 1;
	}

	/**
	 * Fetch actions
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 * @see fetchFilters()
	 */
	public function fetchActions()
	{
		$this->actions = array();

		$sql = 'SELECT rowid, type, actionparam, status';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectoraction';
		$sql .= ' WHERE fk_emailcollector = '.$this->id;
		$sql .= ' ORDER BY position';

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->actions[$obj->rowid] = array('id'=>$obj->rowid, 'type'=>$obj->type, 'actionparam'=>$obj->actionparam, 'status'=>$obj->status);
				$i++;
			}
			$this->db->free($resql);
		} else dol_print_error($this->db);
	}


	/**
	 * Return the connectstring to use with IMAP connection function
	 *
	 * @param	int		$ssl		Add /ssl tag
	 * @param	int		$norsh		Add /norsh to connectstring
	 * @return string
	 */
	public function getConnectStringIMAP($ssl = 1, $norsh = 0)
	{
		global $conf;

		// Connect to IMAP
		$flags = '/service=imap'; // IMAP
		if (!empty($conf->global->IMAP_FORCE_TLS)) {
			$flags .= '/tls';
		} elseif (empty($conf->global->IMAP_FORCE_NOSSL)) {
			if ($ssl) $flags .= '/ssl';
		}
		$flags .= '/novalidate-cert';
		//$flags.='/readonly';
		//$flags.='/debug';
		if ($norsh || !empty($conf->global->IMAP_FORCE_NORSH)) $flags .= '/norsh';

		$connectstringserver = '{'.$this->host.':993'.$flags.'}';

		return $connectstringserver;
	}

	/**
	 * Convert str to UTF-7 imap default mailbox names
	 *
	 * @param 	string $str			String to encode
	 * @return 	string				Encode string
	 */
	public function getEncodedUtf7($str)
	{
		if (function_exists('mb_convert_encoding')) {
			// change spaces by entropy because mb_convert fail with spaces
			$str = preg_replace("/ /", "xyxy", $str);
			// if mb_convert work
			if ($str = mb_convert_encoding($str, "UTF-7")) {
				// change characters
				$str = preg_replace("/\+A/", "&A", $str);
				// change to spaces again
				$str = preg_replace("/xyxy/", " ", $str);
				return $str;
			} else {
				// print error and return false
				$this->error = "error: is not possible to encode this string '".$str."'";
		 		return false;
			}
		} else {
			return $str;
		}
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, paramerts come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doCollect()
	{
		global $user;

		$nberror = 0;

		$arrayofcollectors = $this->fetchAll($user, 1);

		// Loop on each collector
		foreach ($arrayofcollectors as $emailcollector)
		{
			$result = $emailcollector->doCollectOneCollector();
			dol_syslog("doCollect result = ".$result." for emailcollector->id = ".$emailcollector->id);

			$this->error .= 'EmailCollector ID '.$emailcollector->id.':'.$emailcollector->error.'<br>';
			if (!empty($emailcollector->errors)) $this->error .= join('<br>', $emailcollector->errors);
			$this->output .= 'EmailCollector ID '.$emailcollector->id.': '.$emailcollector->lastresult.'<br>';
		}

		return $nberror;
	}

	/**
	 * overwitePropertiesOfObject
	 *
	 * @param	object	$object			Current object
	 * @param	string	$actionparam	Action parameters
	 * @param	string	$messagetext	Body
	 * @param	string	$subject		Subject
	 * @param   string  $header         Header
	 * @return	int						0=OK, Nb of error if error
	 */
	private function overwritePropertiesOfObject(&$object, $actionparam, $messagetext, $subject, $header)
	{
		$errorforthisaction = 0;

		// Overwrite values with values extracted from source email
		// $this->actionparam = 'opportunity_status=123;abc=EXTRACT:BODY:....'
		$arrayvaluetouse = dolExplodeIntoArray($actionparam, ';', '=');
		foreach ($arrayvaluetouse as $propertytooverwrite => $valueforproperty)
		{
			$tmpclass = ''; $tmpproperty = '';
			$tmparray = explode('.', $propertytooverwrite);
			if (count($tmparray) == 2)
			{
				$tmpclass = $tmparray[0];
				$tmpproperty = $tmparray[1];
			} else {
				$tmpproperty = $tmparray[0];
			}
			if ($tmpclass && ($tmpclass != $object->element)) continue; // Property is for another type of object

			//if (property_exists($object, $tmpproperty) || preg_match('/^options_/', $tmpproperty))
			if ($tmpproperty)
			{
				$sourcestring = '';
				$sourcefield = '';
				$regexstring = '';
				//$transformationstring='';
				$regforregex = array();
				if (preg_match('/^EXTRACT:([a-zA-Z0-9]+):(.*):([^:])$/', $valueforproperty, $regforregex))
				{
					$sourcefield = $regforregex[1];
					$regexstring = $regforregex[2];
					//$transofrmationstring=$regforregex[3];
				} elseif (preg_match('/^EXTRACT:([a-zA-Z0-9]+):(.*)$/', $valueforproperty, $regforregex))
				{
					$sourcefield = $regforregex[1];
					$regexstring = $regforregex[2];
				}
				if (!empty($sourcefield) && !empty($regexstring))
				{
					if (strtolower($sourcefield) == 'body') $sourcestring = $messagetext;
					elseif (strtolower($sourcefield) == 'subject') $sourcestring = $subject;
					elseif (strtolower($sourcefield) == 'header') $sourcestring = $header;

					if ($sourcestring)
					{
						$regforval = array();
						$regexoptions = '';
						if (strtolower($sourcefield) == 'body') $regexoptions = 'ms'; // The m means ^ and $ char is valid at each new line. The s means the char '.' is valid for new lines char too
						if (strtolower($sourcefield) == 'header') $regexoptions = 'm'; // The m means ^ and $ char is valid at each new line.

						//var_dump($tmpproperty.' - '.$regexstring.' - '.$regexoptions.' - '.$sourcestring);
						if (preg_match('/'.$regexstring.'/'.$regexoptions, $sourcestring, $regforval))
						{
							//var_dump($regforval[count($regforval)-1]);exit;
							// Overwrite param $tmpproperty
							$valueextracted = isset($regforval[count($regforval) - 1]) ?trim($regforval[count($regforval) - 1]) : null;
							if (strtolower($sourcefield) == 'header') {
								$object->$tmpproperty = $this->decodeSMTPSubject($valueextracted);
							} else {
								$object->$tmpproperty = $valueextracted;
							}
						} else {
							// Regex not found
							$object->$tmpproperty = null;
						}
					} else {
						// Nothing can be done for this param
						$errorforthisaction++;
						$this->error = 'The extract rule to use has on an unknown source (must be HEADER, SUBJECT or BODY)';
						$this->errors[] = $this->error;
					}
				} elseif (preg_match('/^(SET|SETIFEMPTY):(.*)$/', $valueforproperty, $regforregex))
				{
					$valuecurrent = '';
					if (preg_match('/^options_/', $tmpproperty)) $valuecurrent = $object->array_options[preg_replace('/^options_/', '', $tmpproperty)];
					else $valuecurrent = $object->$tmpproperty;

					if ($regforregex[1] == 'SET' || empty($valuecurrent))
					{
						$valuetouse = $regforregex[2];
						$substitutionarray = array();
						$matcharray = array();
						preg_match_all('/__([a-z0-9]+(?:_[a-z0-9]+)?)__/i', $valuetouse, $matcharray);
						//var_dump($tmpproperty.' - '.$object->$tmpproperty.' - '.$valuetouse); var_dump($matcharray);
						if (is_array($matcharray[1]))    // $matcharray[1] is array with list of substitution key found without the __
						{
							foreach ($matcharray[1] as $keytoreplace)
							{
								if ($keytoreplace && isset($object->$keytoreplace))
								{
									$substitutionarray['__'.$keytoreplace.'__'] = $object->$keytoreplace;
								}
							}
						}
						//var_dump($substitutionarray);
						dol_syslog(var_export($substitutionarray, true));
						//var_dump($substitutionarray);
						$valuetouse = make_substitutions($valuetouse, $substitutionarray);
						if (preg_match('/^options_/', $tmpproperty)) $object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $valuetouse;
						else $object->$tmpproperty = $valuetouse;
					}
				} else {
					$errorforthisaction++;
					$this->error = 'Bad syntax for description of action parameters: '.$actionparam;
					$this->errors[] = $this->error;
				}
			}
		}

		return $errorforthisaction;
	}

	/**
	 * Execute collect for current collector loaded previously with fetch.
	 *
	 * @return	int			<0 if KO, >0 if OK
	 */
	public function doCollectOneCollector()
	{
		global $conf, $langs, $user;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		dol_syslog("EmailCollector::doCollectOneCollector start", LOG_DEBUG);

		$langs->loadLangs(array("project", "companies", "mails", "errors", "ticket", "agenda"));

		$error = 0;
		$this->output = '';
		$this->error = '';

		$now = dol_now();

		if (empty($this->host))
		{
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('EMailHost'));
			return -1;
		}
		if (empty($this->login))
		{
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Login'));
			return -1;
		}
		if (empty($this->source_directory))
		{
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('MailboxSourceDirectory'));
			return -1;
		}
		if (!function_exists('imap_open'))
		{
			$this->error = 'IMAP function not enabled on your PHP';
			return -2;
		}

		$this->fetchFilters();
		$this->fetchActions();

		$sourcedir = $this->source_directory;
		$targetdir = ($this->target_directory ? $this->target_directory : ''); // Can be '[Gmail]/Trash' or 'mytag'

		$connectstringserver = $this->getConnectStringIMAP();
		$connectstringsource = $connectstringserver.imap_utf7_encode($sourcedir);
		$connectstringtarget = $connectstringserver.imap_utf7_encode($targetdir);

		$connection = imap_open($connectstringsource, $this->login, $this->password);
		if (!$connection)
		{
			$this->error = 'Failed to open IMAP connection '.$connectstringsource;
			return -3;
		}
		imap_errors(); // Clear stack of errors.

		$host = dol_getprefix('email');
		//$host = '123456';

		// Define the IMAP search string
		// See https://tools.ietf.org/html/rfc3501#section-6.4.4 for IMAPv4 (PHP not yet compatible)
		// See https://tools.ietf.org/html/rfc1064 page 13 for IMAPv2
		//$search='ALL';
		$search = 'UNDELETED'; // Seems not supported by some servers
		$searchhead = '';
		$searchfilterdoltrackid = 0;
		$searchfilternodoltrackid = 0;
		$searchfilterisanswer = 0;
		$searchfilterisnotanswer = 0;
		foreach ($this->filters as $rule)
		{
			if (empty($rule['status'])) continue;

			if ($rule['type'] == 'to') {
				$tmprulevaluearray = explode('*', $rule['rulevalue']);
				if (count($tmprulevaluearray) >= 2) {
					foreach ($tmprulevaluearray as $tmprulevalue) {
						$search .= ($search ? ' ' : '').'TO "'.str_replace('"', '', $tmprulevalue).'"';
					}
				} else {
					$search .= ($search ? ' ' : '').'TO "'.str_replace('"', '', $rule['rulevalue']).'"';
				}
			}
			if ($rule['type'] == 'bcc')     $search .= ($search ? ' ' : '').'BCC';
			if ($rule['type'] == 'cc')      $search .= ($search ? ' ' : '').'CC';
			if ($rule['type'] == 'from')    $search .= ($search ? ' ' : '').'FROM "'.str_replace('"', '', $rule['rulevalue']).'"';
			if ($rule['type'] == 'subject') $search .= ($search ? ' ' : '').'SUBJECT "'.str_replace('"', '', $rule['rulevalue']).'"';
			if ($rule['type'] == 'body')    $search .= ($search ? ' ' : '').'BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
			if ($rule['type'] == 'header')  $search .= ($search ? ' ' : '').'HEADER '.$rule['rulevalue'];

			if ($rule['type'] == 'notinsubject') $search .= ($search ? ' ' : '').'SUBJECT NOT "'.str_replace('"', '', $rule['rulevalue']).'"';
			if ($rule['type'] == 'notinbody') $search .= ($search ? ' ' : '').'BODY NOT "'.str_replace('"', '', $rule['rulevalue']).'"';

			if ($rule['type'] == 'seen')    $search .= ($search ? ' ' : '').'SEEN';
			if ($rule['type'] == 'unseen')  $search .= ($search ? ' ' : '').'UNSEEN';
			if ($rule['type'] == 'unanswered') $search .= ($search ? ' ' : '').'UNANSWERED';
			if ($rule['type'] == 'answered')   $search .= ($search ? ' ' : '').'ANSWERED';
			if ($rule['type'] == 'smaller') $search .= ($search ? ' ' : '').'SMALLER "'.str_replace('"', '', $rule['rulevalue']).'"';
			if ($rule['type'] == 'larger')  $search .= ($search ? ' ' : '').'LARGER "'.str_replace('"', '', $rule['rulevalue']).'"';

			if ($rule['type'] == 'withtrackingidinmsgid') { $searchfilterdoltrackid++; $searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/'; }
			if ($rule['type'] == 'withouttrackingidinmsgid') { $searchfilterdoltrackid++; $searchhead .= '/Message-ID.*@'.preg_quote($host, '/').'/'; }
			if ($rule['type'] == 'withtrackingid') { $searchfilterdoltrackid++; $searchhead .= '/References.*@'.preg_quote($host, '/').'/'; }
			if ($rule['type'] == 'withouttrackingid') { $searchfilternodoltrackid++; $searchhead .= '! /References.*@'.preg_quote($host, '/').'/'; }

			if ($rule['type'] == 'isanswer') { $searchfilterisanswer++; $searchhead .= '/References.*@.*/'; }
			if ($rule['type'] == 'isnotanswer') { $searchfilterisnotanswer++; $searchhead .= '! /References.*@.*/'; }
		}

		if (empty($targetdir))	// Use last date as filter if there is no targetdir defined.
		{
			$fromdate = 0;
			if ($this->datelastok) $fromdate = $this->datelastok;
			if ($fromdate > 0) $search .= ($search ? ' ' : '').'SINCE '.date('j-M-Y', $fromdate - 1); // SENTSINCE not supported. Date must be X-Abc-9999 (X on 1 digit if < 10)
			//$search.=($search?' ':'').'SINCE 8-Apr-2018';
		}
		dol_syslog("IMAP search string = ".$search);
		//var_dump($search);

		$nbemailprocessed = 0;
		$nbemailok = 0;
		$nbactiondone = 0;
		$charset = ($this->hostcharset ? $this->hostcharset : "UTF-8");

		// Scan IMAP inbox
		$arrayofemail = imap_search($connection, $search, null, $charset);
		if ($arrayofemail === false)
		{
			// Nothing found or search string not understood
			$mapoferrrors = imap_errors();
			if ($mapoferrrors !== false)
			{
				$error++;
				$this->error = "Search string not understood - ".join(',', $mapoferrrors);
				$this->errors[] = $this->error;
			}
		}

		// Loop on each email found
		if (!$error && !empty($arrayofemail) && count($arrayofemail) > 0)
		{
			// Loop to get part html and plain
			/*
             0 multipart/mixed
             1 multipart/alternative
             1.1 text/plain
             1.2 text/html
             2 message/rfc822
             2 multipart/mixed
             2.1 multipart/alternative
             2.1.1 text/plain
             2.1.2 text/html
             2.2 message/rfc822
             2.2 multipart/alternative
             2.2.1 text/plain
             2.2.2 text/html
             */
			/**
			 * create_part_array
			 *
			 * @param 	Object $structure	Structure
			 * @param 	string $prefix		prefix
			 * @return 	array				Array with number and object
			 */
			/*function createPartArray($structure, $prefix = "")
            {
                //print_r($structure);
                $part_array=array();
                if (count($structure->parts) > 0) {    // There some sub parts
                    foreach ($structure->parts as $count => $part) {
                        addPartToArray($part, $prefix.($count+1), $part_array);
                    }
                }else{    // Email does not have a seperate mime attachment for text
                    $part_array[] = array('part_number' => $prefix.'1', 'part_object' => $structure);
                }
                return $part_array;
            }*/

			/**
			 * Sub function for createPartArray(). Only called by createPartArray() and itself.
			 *
			 * @param 	Object		$obj			Structure
			 * @param 	string		$partno			Part no
			 * @param 	array		$part_array		array
			 * @return	void
			 */
			/*function addPartToArray($obj, $partno, &$part_array)
            {
                $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
                if ($obj->type == 2) { // Check to see if the part is an attached email message, as in the RFC-822 type
                    //print_r($obj);
                    if (array_key_exists('parts', $obj)) {    // Check to see if the email has parts
                        foreach ($obj->parts as $count => $part) {
                            // Iterate here again to compensate for the broken way that imap_fetchbody() handles attachments
                            if (count($part->parts) > 0) {
                                foreach ($part->parts as $count2 => $part2) {
                                    addPartToArray($part2, $partno.".".($count2+1), $part_array);
                                }
                            }else{    // Attached email does not have a seperate mime attachment for text
                                $part_array[] = array('part_number' => $partno.'.'.($count+1), 'part_object' => $obj);
                            }
                        }
                    }else{    // Not sure if this is possible
                        $part_array[] = array('part_number' => $partno.'.1', 'part_object' => $obj);
                    }
                }else{    // If there are more sub-parts, expand them out.
                    if (array_key_exists('parts', $obj)) {
                        foreach ($obj->parts as $count => $p) {
                            addPartToArray($p, $partno.".".($count+1), $part_array);
                        }
                    }
                }
            }*/

			dol_syslog("Start of loop on email", LOG_INFO, 1);

			$iforemailloop = 0;
			foreach ($arrayofemail as $imapemail)
			{
				if ($nbemailprocessed > 1000)
				{
					break; // Do not process more than 1000 email per launch (this is a different protection than maxnbcollectedpercollect
				}

				$iforemailloop++;

				$header = imap_fetchheader($connection, $imapemail, 0);
				$header = preg_replace('/\r\n\s+/m', ' ', $header); // When a header line is on several lines, merge lines
				/*print $header;
                print $header;*/

				$matches = array();
				preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)\r\n/m', $header, $matches);
				$headers = array_combine($matches[1], $matches[2]);

				if (!empty($headers['in-reply-to']) && empty($headers['In-Reply-To'])) { $headers['In-Reply-To'] = $headers['in-reply-to']; }
				if (!empty($headers['references']) && empty($headers['References'])) { $headers['References'] = $headers['references']; }
				if (!empty($headers['message-id']) && empty($headers['Message-ID'])) { $headers['Message-ID'] = $headers['message-id']; }

				$headers['Subject'] = $this->decodeSMTPSubject($headers['Subject']);


				dol_syslog("** Process email ".$iforemailloop." References: ".$headers['References']);
				//print "Process mail ".$iforemailloop." Subject: ".dol_escape_htmltag($headers['Subject'])." References: ".dol_escape_htmltag($headers['References'])." In-Reply-To: ".dol_escape_htmltag($headers['In-Reply-To'])."<br>\n";

				// If there is a filter on trackid
				if ($searchfilterdoltrackid > 0)
				{
					if (empty($headers['References']) || !preg_match('/@'.preg_quote($host, '/').'/', $headers['References']))
					{
						$nbemailprocessed++;
						continue; // Exclude email
					}
				}
				if ($searchfilternodoltrackid > 0)
				{
					if (!empty($headers['References']) && preg_match('/@'.preg_quote($host, '/').'/', $headers['References']))
					{
						$nbemailprocessed++;
						continue; // Exclude email
					}
				}

				if ($searchfilterisanswer > 0) {
					if (empty($headers['In-Reply-To']))
					{
						$nbemailprocessed++;
						continue; // Exclude email
					}
					// Note: we can have
					// Message-ID=A, In-Reply-To=B, References=B and message can BE an answer or NOT (a transfer rewriten)
					$isanswer = 0;
					if (preg_match('/Re\s*:\s+/i', $headers['Subject'])) $isanswer = 1;
					//if ($headers['In-Reply-To'] != $headers['Message-ID'] && empty($headers['References'])) $isanswer = 1;	// If in-reply-to differs of message-id, this is a reply
					//if ($headers['In-Reply-To'] != $headers['Message-ID'] && !empty($headers['References']) && strpos($headers['References'], $headers['Message-ID']) !== false) $isanswer = 1;

					if (!$isanswer) {
						$nbemailprocessed++;
						continue; // Exclude email
					}
				}
				if ($searchfilterisnotanswer > 0) {
					if (!empty($headers['In-Reply-To']))
					{
						// Note: we can have
						// Message-ID=A, In-Reply-To=B, References=B and message can BE an answer or NOT (a transfer rewriten)
						$isanswer = 0;
						if (preg_match('/Re\s*:\s+/i', $headers['Subject'])) $isanswer = 1;
						//if ($headers['In-Reply-To'] != $headers['Message-ID'] && empty($headers['References'])) $isanswer = 1;	// If in-reply-to differs of message-id, this is a reply
						//if ($headers['In-Reply-To'] != $headers['Message-ID'] && !empty($headers['References']) && strpos($headers['References'], $headers['Message-ID']) !== false) $isanswer = 1;
						if ($isanswer) {
							$nbemailprocessed++;
							continue; // Exclude email
						}
					}
				}

				//print "Process mail ".$iforemailloop." Subject: ".dol_escape_htmltag($headers['Subject'])." selected<br>\n";

				$thirdpartystatic = new Societe($this->db);
				$contactstatic = new Contact($this->db);
				$projectstatic = new Project($this->db);

				$nbactiondoneforemail = 0;
				$errorforemail = 0;
				$errorforactions = 0;
				$thirdpartyfoundby = '';
				$contactfoundby = '';
				$projectfoundby = '';
				$ticketfoundby = '';
				$candidaturefoundby = '';

				$this->db->begin();


				// GET Email meta datas
				$overview = imap_fetch_overview($connection, $imapemail, 0);

				dol_syslog("msgid=".$overview[0]->message_id." date=".dol_print_date($overview[0]->udate, 'dayrfc', 'gmt')." from=".$overview[0]->from." to=".$overview[0]->to." subject=".$overview[0]->subject);

				$overview[0]->subject = $this->decodeSMTPSubject($overview[0]->subject);

				$overview[0]->from = $this->decodeSMTPSubject($overview[0]->from);

				// Removed emojis
				$overview[0]->subject = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $overview[0]->subject);

				// Parse IMAP email structure
				global $htmlmsg, $plainmsg, $charset, $attachments;
				$this->getmsg($connection, $imapemail);

				//$htmlmsg,$plainmsg,$charset,$attachments
				$messagetext = $plainmsg ? $plainmsg : dol_string_nohtmltag($htmlmsg, 0);
				// Removed emojis
				$messagetext = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xEF\xBF\xBD", $messagetext);

				/*var_dump($plainmsg);
                var_dump($htmlmsg);
                var_dump($messagetext);*/
				/*var_dump($charset);
                var_dump($attachments);
                exit;*/

				// Parse IMAP email structure
				/*
                $structure = imap_fetchstructure($connection, $imapemail, 0);

                $partplain = $parthtml = -1;
                $encodingplain = $encodinghtml = '';

                $result = createPartArray($structure, '');

                foreach($result as $part)
                {
                    // $part['part_object']->type seems 0 for content
                    // $part['part_object']->type seems 5 for attachment
                    if (empty($part['part_object'])) continue;
                    if ($part['part_object']->subtype == 'HTML')
                    {
                        $parthtml=$part['part_number'];
                        if ($part['part_object']->encoding == 4)
                        {
                            $encodinghtml = 'aaa';
                        }
                    }
                    if ($part['part_object']->subtype == 'PLAIN')
                    {
                        $partplain=$part['part_number'];
                        if ($part['part_object']->encoding == 4)
                        {
                            $encodingplain = 'rr';
                        }
                    }
                }
                //var_dump($result); var_dump($partplain); var_dump($parthtml);

                var_dump($structure);
                var_dump($parthtml);
                var_dump($partplain);

                $messagetext = imap_fetchbody($connection, $imapemail, ($parthtml != '-1' ? $parthtml : ($partplain != '-1' ? $partplain : 1)), FT_PEEK);
                */

				//var_dump($messagetext);
				//var_dump($structure->parts[0]->parts);
				//print $header;
				//print $messagetext;
				//exit;

				$fromstring = $overview[0]->from;

				$sender = $overview[0]->sender;
				$to = $overview[0]->to;
				$sendtocc = $overview[0]->cc;
				$sendtobcc = $overview[0]->bcc;
				$date = $overview[0]->udate;
				$msgid = str_replace(array('<', '>'), '', $overview[0]->message_id);
				$subject = $overview[0]->subject;
				//var_dump($msgid);exit;

				$reg = array();
				if (preg_match('/^(.*)<(.*)>$/', $fromstring, $reg))
				{
					$from = $reg[2];
					$fromtext = $reg[1];
				} else {
					$from = $fromstring;
					$fromtext = '';
				}
				$fk_element_id = 0; $fk_element_type = '';


				$contactid = 0; $thirdpartyid = 0; $projectid = 0; $ticketid = 0;

				// Analyze TrackId in field References. For example:
				// References: <1542377954.SMTPs-dolibarr-thi649@8f6014fde11ec6cdec9a822234fc557e>
				// References: <1542377954.SMTPs-dolibarr-tic649@8f6014fde11ec6cdec9a822234fc557e>
				// References: <1542377954.SMTPs-dolibarr-abc649@8f6014fde11ec6cdec9a822234fc557e>
				$trackid = '';
				$objectid = 0;
				$objectemail = null;

				$reg = array();
				if (!empty($headers['References']))
				{
					$arrayofreferences = preg_split('/(,|\s+)/', $headers['References']);
					//var_dump($headers['References']);
					//var_dump($arrayofreferences);

					foreach ($arrayofreferences as $reference) {
						//print "Process mail ".$iforemailloop." email_msgid ".$msgid.", date ".dol_print_date($date, 'dayhour').", subject ".$subject.", reference ".dol_escape_htmltag($reference)."<br>\n";
						if (preg_match('/dolibarr-([a-z]+)([0-9]+)@'.preg_quote($host, '/').'/', $reference, $reg)) {
							// This is a Dolibarr reference
							$trackid = $reg[1].$reg[2];

							$objectid = $reg[2];
							// See also list into interface_50_modAgenda_ActionsAuto
							if ($reg[1] == 'thi')
							{
								$objectemail = new Societe($this->db);
							}
							if ($reg[1] == 'ctc')
							{
								$objectemail = new Contact($this->db);
							}
							if ($reg[1] == 'inv')
							{
								$objectemail = new Facture($this->db);
							}
							if ($reg[1] == 'proj')
							{
								$objectemail = new Project($this->db);
							}
							if ($reg[1] == 'tas')
							{
								$objectemail = new Task($this->db);
							}
							if ($reg[1] == 'con')
							{
								$objectemail = new Contact($this->db);
							}
							if ($reg[1] == 'use')
							{
								$objectemail = new User($this->db);
							}
							if ($reg[1] == 'tic')
							{
								$objectemail = new Ticket($this->db);
							}
							if ($reg[1] == 'recruitmentcandidature')
							{
								$objectemail = new RecruitmentCandidature($this->db);
							}
							if ($reg[1] == 'mem')
							{
								$objectemail = new Adherent($this->db);
							}
						} elseif (preg_match('/<(.*@.*)>/', $reference, $reg)) {
							// This is an external reference, we check if we have it in our database
							if (!is_object($objectemail)) {
								$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."ticket where email_msgid = '".$this->db->escape($reg[1])."'";
								$resql = $this->db->query($sql);
								if ($resql) {
									$obj = $this->db->fetch_object($resql);
									if ($obj) {
										$objectid = $obj->rowid;
										$objectemail = new Ticket($this->db);
										$ticketfoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
									}
								} else {
									$errorforemail++;
								}
							}

							if (!is_object($objectemail)) {
								$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."projet where email_msgid = '".$this->db->escape($reg[1])."'";
								$resql = $this->db->query($sql);
								if ($resql) {
									$obj = $this->db->fetch_object($resql);
									if ($obj) {
										$objectid = $obj->rowid;
										$objectemail = new Project($this->db);
										$projectfoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
									}
								} else {
									$errorforemail++;
								}
							}

							if (!is_object($objectemail)) {
								$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature where email_msgid = '".$this->db->escape($reg[1])."'";
								$resql = $this->db->query($sql);
								if ($resql) {
									$obj = $this->db->fetch_object($resql);
									if ($obj) {
										$objectid = $obj->rowid;
										$objectemail = new RecruitmentCandidature($this->db);
										$candidaturefoundby = $langs->transnoentitiesnoconv("EmailMsgID").' ('.$reg[1].')';
									}
								} else {
									$errorforemail++;
								}
							}
						}

						// Load object linked to email
						if (is_object($objectemail))
						{
							$result = $objectemail->fetch($objectid);
							if ($result > 0)
							{
								$fk_element_id = $objectemail->id;
								$fk_element_type = $objectemail->element;
								// Fix fk_element_type
								if ($fk_element_type == 'facture') $fk_element_type = 'invoice';

								if (get_class($objectemail) != 'Societe') $thirdpartyid = $objectemail->fk_soc;
								else $thirdpartyid = $objectemail->id;

								if (get_class($objectemail) != 'Contact') $contactid = $objectemail->fk_socpeople;
								else $contactid = $objectemail->id;

								if (get_class($objectemail) != 'Project') $projectid = isset($objectemail->fk_project) ? $objectemail->fk_project : $objectemail->fk_projet;
								else $projectid = $objectemail->id;
							}
						}

						// Project
						if ($projectid > 0)
						{
							$result = $projectstatic->fetch($projectid);
							if ($result <= 0) $projectstatic->id = 0;
							else {
								$projectid = $projectstatic->id;
								if ($trackid) $projectfoundby = 'trackid ('.$trackid.')';
								if (empty($contactid)) $contactid = $projectstatic->fk_contact;
								if (empty($thirdpartyid)) $thirdpartyid = $projectstatic->fk_soc;
							}
						}
						// Contact
						if ($contactid > 0)
						{
							$result = $contactstatic->fetch($contactid);
							if ($result <= 0) $contactstatic->id = 0;
							else {
								$contactid = $contactstatic->id;
								if ($trackid) $contactfoundby = 'trackid ('.$trackid.')';
								if (empty($thirdpartyid)) $thirdpartyid = $contactstatic->fk_soc;
							}
						}
						// Thirdparty
						if ($thirdpartyid > 0)
						{
							$result = $thirdpartystatic->fetch($thirdpartyid);
							if ($result <= 0) $thirdpartystatic->id = 0;
							else {
								$thirdpartyid = $thirdpartystatic->id;
								if ($trackid) $thirdpartyfoundby = 'trackid ('.$trackid.')';
							}
						}

						if (is_object($objectemail))
						{
							break; // Exit loop of references. We already found an accurate reference
						}
					}
				}

				if (empty($contactid))		// Try to find contact using email
				{
					$result = $contactstatic->fetch(0, null, '', $from);

					if ($result > 0)
					{
						$contactid = $contactstatic->id;
						$contactfoundby = 'email of contact ('.$from.')';
						if (empty($thirdpartyid) && $contactstatic->socid > 0)
						{
							$result = $thirdpartystatic->fetch($contactstatic->socid);
							if ($result > 0)
							{
								$thirdpartyid = $thirdpartystatic->id;
								$thirdpartyfoundby = 'email of contact ('.$from.')';
							}
						}
					}
				}

				if (empty($thirdpartyid))		// Try to find thirdparty using email
				{
					$result = $thirdpartystatic->fetch(0, '', '', '', '', '', '', '', '', '', $from);
					if ($result > 0) $thirdpartyfoundby = 'email ('.$from.')';
				}

				// Do operations
				foreach ($this->actions as $operation)
				{
					$errorforthisaction = 0;

					if ($errorforactions) break;
					if (empty($operation['status'])) continue;

					// Make Operation
					dol_syslog("Execute action ".$operation['type']." actionparam=".$operation['actionparam'].' thirdpartystatic->id='.$thirdpartystatic->id.' contactstatic->id='.$contactstatic->id.' projectstatic->id='.$projectstatic->id);
					dol_syslog("Execute action fk_element_id=".$fk_element_id." fk_element_type=".$fk_element_type);

					$actioncode = 'EMAIL_IN';
					// If we scan the Sent box, we use the code for out email
					if ($this->source_directory == 'Sent') $actioncode = 'EMAIL_OUT';

					$description = $descriptiontitle = $descriptionmeta = $descriptionfull = '';

					$descriptiontitle = $langs->trans("RecordCreatedByEmailCollector", $this->ref, $msgid);

					$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailTopic").' : '.dol_escape_htmltag($subject));
					$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailFrom").($langs->trans("MailFrom") != 'From' ? ' (From)' : '').' : '.dol_escape_htmltag($fromstring));
					if ($sender) $descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("Sender").($langs->trans("Sender") != 'Sender' ? ' (Sender)' : '').' : '.dol_escape_htmltag($sender));
					$descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailTo").($langs->trans("MailTo") != 'To' ? ' (To)' : '').' : '.dol_escape_htmltag($to));
					if ($sendtocc) $descriptionmeta = dol_concatdesc($descriptionmeta, $langs->trans("MailCC").($langs->trans("MailCC") != 'CC' ? ' (CC)' : '').' : '.dol_escape_htmltag($sendtocc));

					// Search and create thirdparty
					if ($operation['type'] == 'loadthirdparty' || $operation['type'] == 'loadandcreatethirdparty')
					{
						if (empty($operation['actionparam'])) {
							$errorforactions++;
							$this->error = "Action loadthirdparty or loadandcreatethirdparty has empty parameter. Must be a rule like 'SET:xxx' or 'EXTRACT:(body|subject):regex' to define how to set or extract data";
							$this->errors[] = $this->error;
						} else {
							$actionparam = $operation['actionparam'];
							$nametouseforthirdparty = '';

							// $this->actionparam = 'SET:aaa' or 'EXTRACT:BODY:....'
							$arrayvaluetouse = dolExplodeIntoArray($actionparam, ';', '=');
							foreach ($arrayvaluetouse as $propertytooverwrite => $valueforproperty)
							{
								$sourcestring = '';
								$sourcefield = '';
								$regexstring = '';
								$regforregex = array();

								if (preg_match('/^EXTRACT:([a-zA-Z0-9]+):(.*)$/', $valueforproperty, $regforregex))
								{
									$sourcefield = $regforregex[1];
									$regexstring = $regforregex[2];
								}

								if (!empty($sourcefield) && !empty($regexstring))
								{
									if (strtolower($sourcefield) == 'body') $sourcestring = $messagetext;
									elseif (strtolower($sourcefield) == 'subject') $sourcestring = $subject;
									elseif (strtolower($sourcefield) == 'header') $sourcestring = $header;

									if ($sourcestring)
									{
										$regforval = array();
										//var_dump($regexstring);var_dump($sourcestring);
										if (preg_match('/'.$regexstring.'/ms', $sourcestring, $regforval))
										{
											//var_dump($regforval[count($regforval)-1]);exit;
											// Overwrite param $tmpproperty
											$nametouseforthirdparty = isset($regforval[count($regforval) - 1]) ? trim($regforval[count($regforval) - 1]) : null;
										} else {
											// Regex not found
											$nametouseforthirdparty = null;
										}
										//var_dump($object->$tmpproperty);exit;
									} else {
										// Nothing can be done for this param
										$errorforactions++;
										$this->error = 'The extract rule to use to load thirdparty has on an unknown source (must be HEADER, SUBJECT or BODY)';
										$this->errors[] = $this->error;
									}
								} elseif (preg_match('/^(SET|SETIFEMPTY):(.*)$/', $valueforproperty, $reg))
								{
									//if (preg_match('/^options_/', $tmpproperty)) $object->array_options[preg_replace('/^options_/', '', $tmpproperty)] = $reg[1];
									//else $object->$tmpproperty = $reg[1];
									$nametouseforthirdparty = $reg[2];
								} else {
									$errorforactions++;
									$this->error = 'Bad syntax for description of action parameters: '.$actionparam;
									$this->errors[] = $this->error;
									break;
								}
							}

							if (!$errorforactions && $nametouseforthirdparty)
							{
								$result = $thirdpartystatic->fetch(0, $nametouseforthirdparty);
								if ($result < 0)
								{
									$errorforactions++;
									$this->error = 'Error when getting thirdparty with name '.$nametouseforthirdparty.' (may be 2 record exists with same name ?)';
									$this->errors[] = $this->error;
									break;
								} elseif ($result == 0)
								{
									if ($operation['type'] == 'loadthirdparty')
									{
										dol_syslog("Third party with name ".$nametouseforthirdparty." was not found");

										$errorforactions++;
										$this->error = 'ErrorFailedToLoadThirdParty';
										$this->errors[] = 'ErrorFailedToLoadThirdParty';
									} elseif ($operation['type'] == 'loadandcreatethirdparty')
									{
										dol_syslog("Third party with name ".$nametouseforthirdparty." was not found. We try to create it.");

										// Create thirdparty
										$thirdpartystatic->name = $nametouseforthirdparty;
										if ($fromtext != $nametouseforthirdparty) $thirdpartystatic->name_alias = $fromtext;
										$thirdpartystatic->email = $from;

										// Overwrite values with values extracted from source email
										$errorforthisaction = $this->overwritePropertiesOfObject($thirdpartystatic, $operation['actionparam'], $messagetext, $subject, $header);

										if ($errorforthisaction)
										{
											$errorforactions++;
										} else {
											$result = $thirdpartystatic->create($user);
											if ($result <= 0)
											{
												$errorforactions++;
												$this->error = $thirdpartystatic->error;
												$this->errors = $thirdpartystatic->errors;
											}
										}
									}
								}
							}
						}
					}
					// Create event
					elseif ($operation['type'] == 'recordevent')
					{
						$actioncomm = new ActionComm($this->db);

						$alreadycreated = $actioncomm->fetch(0, '', '', $msgid);
						if ($alreadycreated == 0)
						{
							if ($projectstatic->id > 0)
							{
								if ($projectfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Project found from '.$projectfoundby);
							}
							if ($thirdpartystatic->id > 0)
							{
								if ($thirdpartyfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
							}
							if ($contactstatic->id > 0)
							{
								if ($contactfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
							}

							$description = $descriptiontitle;
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $descriptionmeta);
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $messagetext);

							$descriptionfull = $description;
							if (empty($conf->global->MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER)) {
								$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
								$descriptionfull = dol_concatdesc($descriptionfull, $header);
							}

							// Insert record of emails sent
							$actioncomm->type_code   = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
							$actioncomm->code        = 'AC_'.$actioncode;
							$actioncomm->label       = $langs->trans("ActionAC_".$actioncode).' - '.$langs->trans("MailFrom").' '.$from;
							$actioncomm->note_private = $descriptionfull;
							$actioncomm->fk_project  = $projectstatic->id;
							$actioncomm->datep       = $date;
							$actioncomm->datef       = $date;
							$actioncomm->percentage  = -1; // Not applicable
							$actioncomm->socid       = $thirdpartystatic->id;
							$actioncomm->contact_id = $contactstatic->id;
							$actioncomm->socpeopleassigned = (!empty($contactstatic->id) ? array($contactstatic->id => '') : array());
							$actioncomm->authorid    = $user->id; // User saving action
							$actioncomm->userownerid = $user->id; // Owner of action
							// Fields when action is an email (content should be added into note)
							$actioncomm->email_msgid = $msgid;
							$actioncomm->email_from  = $fromstring;
							$actioncomm->email_sender = $sender;
							$actioncomm->email_to    = $to;
							$actioncomm->email_tocc  = $sendtocc;
							$actioncomm->email_tobcc = $sendtobcc;
							$actioncomm->email_subject = $subject;
							$actioncomm->errors_to   = '';

							if (!in_array($fk_element_type, array('societe', 'contact', 'project', 'user')))
							{
								$actioncomm->fk_element  = $fk_element_id;
								$actioncomm->elementid = $fk_element_id;
								$actioncomm->elementtype = $fk_element_type;
								if (is_object($objectemail) && $objectemail->module) {
									$actioncomm->elementtype .= '@'.$objectemail->module;
								}
							}

							//$actioncomm->extraparams = $extraparams;

							// Overwrite values with values extracted from source email
							$errorforthisaction = $this->overwritePropertiesOfObject($actioncomm, $operation['actionparam'], $messagetext, $subject, $header);

							/*var_dump($fk_element_id);
	                        var_dump($fk_element_type);
	                        var_dump($alreadycreated);
	                        var_dump($operation['type']);
	                        var_dump($actioncomm);
	                        exit;*/

							if ($errorforthisaction)
							{
								$errorforactions++;
							} else {
								$result = $actioncomm->create($user);
								if ($result <= 0)
								{
									$errorforactions++;
									$this->errors = $actioncomm->errors;
								}
							}
						}
					}
					// Create project / lead
					elseif ($operation['type'] == 'project')
					{
						$projecttocreate = new Project($this->db);

						$alreadycreated = $projecttocreate->fetch(0, '', '', $msgid);
						if ($alreadycreated == 0)
						{
							if ($thirdpartystatic->id > 0)
							{
								$projecttocreate->socid = $thirdpartystatic->id;
								if ($thirdpartyfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
							}
							if ($contactstatic->id > 0)
							{
								$projecttocreate->contact_id = $contactstatic->id;
								if ($contactfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
							}

							$description = $descriptiontitle;
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $descriptionmeta);
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $messagetext);

							$descriptionfull = $description;
							if (empty($conf->global->MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER)) {
								$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
								$descriptionfull = dol_concatdesc($descriptionfull, $header);
							}

							$id_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'rowid');
							$percent_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'percent');

							$projecttocreate->title = $subject;
							$projecttocreate->date_start = $date;
							$projecttocreate->date_end = '';
							$projecttocreate->opp_status = $id_opp_status;
							$projecttocreate->opp_percent = $percent_opp_status;
							$projecttocreate->description = dol_concatdesc(dolGetFirstLineOfText(dol_string_nohtmltag($description, 2), 10), '...'.$langs->transnoentities("SeePrivateNote").'...');
							$projecttocreate->note_private = $descriptionfull;
							$projecttocreate->entity = $conf->entity;
							$projecttocreate->email_msgid = $msgid;

							$savesocid = $projecttocreate->socid;

							// Overwrite values with values extracted from source email.
							// This may overwrite any $projecttocreate->xxx properties.
							$errorforthisaction = $this->overwritePropertiesOfObject($projecttocreate, $operation['actionparam'], $messagetext, $subject, $header);

							// Set project ref if not yet defined
							if (empty($projecttocreate->ref))
							{
								// Get next Ref
								$defaultref = '';
								$modele = empty($conf->global->PROJECT_ADDON) ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

								// Search template files
								$file = ''; $classname = ''; $filefound = 0; $reldir = '';
								$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
								foreach ($dirmodels as $reldir)
								{
									$file = dol_buildpath($reldir."core/modules/project/".$modele.'.php', 0);
									if (file_exists($file))
									{
										$filefound = 1;
										$classname = $modele;
										break;
									}
								}

								if ($filefound)
								{
									if ($savesocid > 0)
									{
										if ($savesocid != $projecttocreate->socid)
										{
											$errorforactions++;
											setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$projecttocreate->socid.') by setting socid in operation with a different value', null, 'errors');
										}
									} else {
										if ($projecttocreate->socid > 0)
										{
											$thirdpartystatic->fetch($projecttocreate->socid);
										}
									}

									$result = dol_include_once($reldir."core/modules/project/".$modele.'.php');
									$modModuleToUseForNextValue = new $classname;
									$defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $projecttocreate);
								}
								$projecttocreate->ref = $defaultref;
							}

							if ($errorforthisaction)
							{
								$errorforactions++;
							} else {
								if (empty($projecttocreate->ref) || (is_numeric($projecttocreate->ref) && $projecttocreate->ref <= 0))
								{
									$errorforactions++;
									$this->error = 'Failed to create project: Can\'t get a valid value for the field ref with numbering template = '.$modele.', thirdparty id = '.$thirdpartystatic->id;
								} else {
									// Create project
									$result = $projecttocreate->create($user);
									if ($result <= 0)
									{
										$errorforactions++;
										$this->error = 'Failed to create project: '.$langs->trans($projecttocreate->error);
										$this->errors = $projecttocreate->errors;
									}
								}
							}
						}
					}
					// Create ticket
					elseif ($operation['type'] == 'ticket')
					{
						$tickettocreate = new Ticket($this->db);

						$alreadycreated = $tickettocreate->fetch(0, '', '', $msgid);
						if ($alreadycreated == 0)
						{
							if ($thirdpartystatic->id > 0)
							{
								$tickettocreate->socid = $thirdpartystatic->id;
								$tickettocreate->fk_soc = $thirdpartystatic->id;
								if ($thirdpartyfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Third party found from '.$thirdpartyfoundby);
							}
							if ($contactstatic->id > 0)
							{
								$tickettocreate->contact_id = $contactstatic->id;
								if ($contactfoundby) $descriptionmeta = dol_concatdesc($descriptionmeta, 'Contact/address found from '.$contactfoundby);
							}

							$description = $descriptiontitle;
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $descriptionmeta);
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $messagetext);

							$descriptionfull = $description;
							if (empty($conf->global->MAIN_EMAILCOLLECTOR_MAIL_WITHOUT_HEADER)) {
								$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
								$descriptionfull = dol_concatdesc($descriptionfull, $header);
							}

							$tickettocreate->subject = $subject;
							$tickettocreate->message = $description;
							$tickettocreate->type_code = (!empty($conf->global->MAIN_EMAILCOLLECTOR_TICKET_TYPE_CODE) ? $conf->global->MAIN_EMAILCOLLECTOR_TICKET_TYPE_CODE : dol_getIdFromCode($this->db, 1, 'c_ticket_type', 'use_default', 'code', 1));
							$tickettocreate->category_code = (!empty($conf->global->MAIN_EMAILCOLLECTOR_TICKET_CATEGORY_CODE) ? $conf->global->MAIN_EMAILCOLLECTOR_TICKET_CATEGORY_CODE : dol_getIdFromCode($this->db, 1, 'c_ticket_category', 'use_default', 'code', 1));
							$tickettocreate->severity_code = (!empty($conf->global->MAIN_EMAILCOLLECTOR_TICKET_SEVERITY_CODE) ? $conf->global->MAIN_EMAILCOLLECTOR_TICKET_SEVERITY_CODE : dol_getIdFromCode($this->db, 1, 'c_ticket_severity', 'use_default', 'code', 1));
							$tickettocreate->origin_email = $from;
							$tickettocreate->fk_user_create = $user->id;
							$tickettocreate->datec = $date;
							$tickettocreate->fk_project = $projectstatic->id;
							$tickettocreate->notify_tiers_at_create = 0;
							$tickettocreate->note_private = $descriptionfull;
							$tickettocreate->entity = $conf->entity;
							$tickettocreate->email_msgid = $msgid;
							//$tickettocreate->fk_contact = $contactstatic->id;

							$savesocid = $tickettocreate->socid;

							// Overwrite values with values extracted from source email.
							// This may overwrite any $projecttocreate->xxx properties.
							$errorforthisaction = $this->overwritePropertiesOfObject($tickettocreate, $operation['actionparam'], $messagetext, $subject, $header);

							// Set ticket ref if not yet defined
							if (empty($tickettocreate->ref))
							{
								// Get next Ref
								$defaultref = '';
								$modele = empty($conf->global->TICKET_ADDON) ? 'mod_ticket_simple' : $conf->global->TICKET_ADDON;

								// Search template files
								$file = ''; $classname = ''; $filefound = 0; $reldir = '';
								$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
								foreach ($dirmodels as $reldir)
								{
									$file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
									if (file_exists($file))
									{
										$filefound = 1;
										$classname = $modele;
										break;
									}
								}

								if ($filefound)
								{
									if ($savesocid > 0)
									{
										if ($savesocid != $tickettocreate->socid)
										{
											$errorforactions++;
											setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$tickettocreate->socid.') by setting socid in operation with a different value', null, 'errors');
										}
									} else {
										if ($tickettocreate->socid > 0)
										{
											$thirdpartystatic->fetch($tickettocreate->socid);
										}
									}

									$result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
									$modModuleToUseForNextValue = new $classname;
									$defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $tickettocreate);
								}
								$tickettocreate->ref = $defaultref;
							}

							if ($errorforthisaction)
							{
								$errorforactions++;
							} else {
								if (is_numeric($tickettocreate->ref) && $tickettocreate->ref <= 0)
								{
									$errorforactions++;
									$this->error = 'Failed to create ticket: Can\'t get a valid value for the field ref with numbering template = '.$modele.', thirdparty id = '.$thirdpartystatic->id;
								} else {
									// Create project
									$result = $tickettocreate->create($user);
									if ($result <= 0)
									{
										$errorforactions++;
										$this->error = 'Failed to create ticket: '.$langs->trans($tickettocreate->error);
										$this->errors = $tickettocreate->errors;
									}
								}
							}
						}
					}
					// Create candidature
					elseif ($operation['type'] == 'candidature')
					{
						$candidaturetocreate = new RecruitmentCandidature($this->db);

						$alreadycreated = $candidaturetocreate->fetch(0, '', $msgid);
						if ($alreadycreated == 0)
						{
							$description = $descriptiontitle;
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $descriptionmeta);
							$description = dol_concatdesc($description, "-----");
							$description = dol_concatdesc($description, $messagetext);

							$descriptionfull = $description;
							$descriptionfull = dol_concatdesc($descriptionfull, "----- Header");
							$descriptionfull = dol_concatdesc($descriptionfull, $header);

							$candidaturetocreate->subject = $subject;
							$candidaturetocreate->message = $description;
							$candidaturetocreate->type_code = 0;
							$candidaturetocreate->category_code = null;
							$candidaturetocreate->severity_code = null;
							$candidaturetocreate->email = $from;
							//$candidaturetocreate->lastname = $langs->trans("Anonymous").' - '.$from;
							$candidaturetocreate->fk_user_creat = $user->id;
							$candidaturetocreate->date_creation = $date;
							$candidaturetocreate->fk_project = $projectstatic->id;
							$candidaturetocreate->description = $description;
							$candidaturetocreate->note_private = $descriptionfull;
							$candidaturetocreate->entity = $conf->entity;
							$candidaturetocreate->email_msgid = $msgid;
							$candidaturetocreate->status = $candidaturetocreate::STATUS_DRAFT;
							//$candidaturetocreate->fk_contact = $contactstatic->id;

							// Overwrite values with values extracted from source email.
							// This may overwrite any $projecttocreate->xxx properties.
							$errorforthisaction = $this->overwritePropertiesOfObject($candidaturetocreate, $operation['actionparam'], $messagetext, $subject, $header);

							// Set candidature ref if not yet defined
							/*if (empty($candidaturetocreate->ref))				We do not need this because we create object in draft status
	                    	{
	                    		// Get next Ref
	                    		$defaultref = '';
	                    		$modele = empty($conf->global->CANDIDATURE_ADDON) ? 'mod_candidature_simple' : $conf->global->CANDIDATURE_ADDON;

	                    		// Search template files
	                    		$file = ''; $classname = ''; $filefound = 0; $reldir = '';
	                    		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	                    		foreach ($dirmodels as $reldir)
	                    		{
	                    			$file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
	                    			if (file_exists($file))
	                    			{
	                    				$filefound = 1;
	                    				$classname = $modele;
	                    				break;
	                    			}
	                    		}

	                    		if ($filefound)
	                    		{
	                    			if ($savesocid > 0)
	                    			{
	                    				if ($savesocid != $candidaturetocreate->socid)
	                    				{
	                    					$errorforactions++;
	                    					setEventMessages('You loaded a thirdparty (id='.$savesocid.') and you force another thirdparty id (id='.$candidaturetocreate->socid.') by setting socid in operation with a different value', null, 'errors');
	                    				}
	                    			} else {
	                    				if ($candidaturetocreate->socid > 0)
	                    				{
	                    					$thirdpartystatic->fetch($candidaturetocreate->socid);
	                    				}
	                    			}

	                    			$result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
	                    			$modModuleToUseForNextValue = new $classname;
	                    			$defaultref = $modModuleToUseForNextValue->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $tickettocreate);
	                    		}
	                    		$candidaturetocreate->ref = $defaultref;
	                    	}*/

							if ($errorforthisaction)
							{
								$errorforactions++;
							} else {
								// Create project
								$result = $candidaturetocreate->create($user);
								if ($result <= 0)
								{
									$errorforactions++;
									$this->error = 'Failed to create ticket: '.join(', ', $candidaturetocreate->errors);
									$this->errors = $candidaturetocreate->errors;
								}
							}
						}
					}
					// Create event specific on hook
					// this code action is hook..... for support this call
					elseif (substr($operation['type'], 0, 4) == 'hook') {
						global $hookmanager;

						if (!is_object($hookmanager)) {
							$hookmanager->initHooks(array('emailcollectorcard'));
						}

						$parameters = array(
							'connection'=>  $connection,
							'imapemail'=>$imapemail,
							'overview'=>$overview,

							'from' => $from,
							'fromtext' => $fromtext,

							'actionparam'=>  $operation['actionparam'],

							'thirdpartyid' => $thirdpartyid,
							'objectid'=> $objectid,
							'objectemail'=> $objectemail,

							'messagetext'=>$messagetext,
							'subject'=>$subject,
							'header'=>$header,
						);
						$res = $hookmanager->executeHooks('doCollectOneCollector', $parameters, $this, $operation['type']);

						if ($res < 0) {
							$errorforthisaction++;
							$this->error = $hookmanager->resPrint;
						}
						if ($errorforthisaction)
						{
							$errorforactions++;
						}
					}

					if (!$errorforactions)
					{
						$nbactiondoneforemail++;
					}
				}

				// Error for email or not ?
				if (!$errorforactions)
				{
					if ($targetdir)
					{
						dol_syslog("EmailCollector::doCollectOneCollector move message ".$imapemail." to ".$connectstringtarget, LOG_DEBUG);
						$res = imap_mail_move($connection, $imapemail, $targetdir, 0);
						if ($res == false) {
							$errorforemail++;
							$this->error = imap_last_error();
							$this->errors[] = $this->error;
							dol_syslog(imap_last_error());
						}
					} else {
						dol_syslog("EmailCollector::doCollectOneCollector message ".$imapemail." to ".$connectstringtarget." was set to read", LOG_DEBUG);
					}
				} else {
					$errorforemail++;
				}

				unset($objectemail);
				unset($projectstatic);
				unset($thirdpartystatic);
				unset($contactstatic);

				$nbemailprocessed++;

				if (!$errorforemail)
				{
					$nbactiondone += $nbactiondoneforemail;
					$nbemailok++;

					$this->db->commit();

					// Stop the loop to process email if we reach maximum collected per collect
					if ($this->maxemailpercollect > 0 && $nbemailok >= $this->maxemailpercollect)
					{
						dol_syslog("EmailCollect::doCollectOneCollector We reach maximum of ".$nbemailok." collected with success, so we stop this collector now.");
						break;
					}
				} else {
					$error++;

					$this->db->rollback();
				}
			}

			$output = $langs->trans('XEmailsDoneYActionsDone', $nbemailprocessed, $nbemailok, $nbactiondone);

			dol_syslog("End of loop on emails", LOG_INFO, -1);
		} else {
			$output = $langs->trans('NoNewEmailToProcess');
		}

		imap_expunge($connection); // To validate any move

		imap_close($connection);

		$this->datelastresult = $now;
		$this->lastresult = $output;
		$this->debuginfo = 'IMAP search string used : '.$search;
		if ($searchhead) $this->debuginfo .= '<br>Then search string into email header : '.$searchhead;

		if (!$error) $this->datelastok = $now;

		if (!empty($this->errors)) $this->lastresult .= " - ".join(" - ", $this->errors);
		$this->codelastresult = ($error ? 'KO' : 'OK');
		$this->update($user);

		dol_syslog("EmailCollector::doCollectOneCollector end", LOG_DEBUG);

		return $error ?-1 : 1;
	}



	// Loop to get part html and plain. Code found on PHP imap_fetchstructure documentation

	/**
	 * getmsg
	 *
	 * @param 	Object $mbox     	Structure
	 * @param 	string $mid		    prefix
	 * @return 	array				Array with number and object
	 */
	private function getmsg($mbox, $mid)
	{
		// input $mbox = IMAP stream, $mid = message id
		// output all the following:
		global $charset, $htmlmsg, $plainmsg, $attachments;
		$htmlmsg = $plainmsg = $charset = '';
		$attachments = array();

		// HEADER
		//$h = imap_header($mbox,$mid);
		// add code here to get date, from, to, cc, subject...

		// BODY
		$s = imap_fetchstructure($mbox, $mid);

		if (!$s->parts) {
			// simple
			$this->getpart($mbox, $mid, $s, 0); // pass 0 as part-number
		} else {
			// multipart: cycle through each part
			foreach ($s->parts as $partno0 => $p) {
				$this->getpart($mbox, $mid, $p, $partno0 + 1);
			}
		}
	}

	/* partno string
     0 multipart/mixed
     1 multipart/alternative
     1.1 text/plain
     1.2 text/html
     2 message/rfc822
     2 multipart/mixed
     2.1 multipart/alternative
     2.1.1 text/plain
     2.1.2 text/html
     2.2 message/rfc822
     2.2 multipart/alternative
     2.2.1 text/plain
     2.2.2 text/html
     */
	/**
	 * Sub function for getpart(). Only called by createPartArray() and itself.
	 *
	 * @param 	Object		$mbox			Structure
	 * @param 	string		$mid			Part no
	 * @param 	Object		$p              Object p
	 * @param   string      $partno         Partno
	 * @return	void
	 */
	private function getpart($mbox, $mid, $p, $partno)
	{
		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
		global $htmlmsg, $plainmsg, $charset, $attachments;

		// DECODE DATA
		$data = ($partno) ?
		imap_fetchbody($mbox, $mid, $partno) : // multipart
		imap_body($mbox, $mid); // simple
		// Any part may be encoded, even plain text messages, so check everything.
		if ($p->encoding == 4)
			$data = quoted_printable_decode($data);
		elseif ($p->encoding == 3)
			$data = base64_decode($data);

		// PARAMETERS
		// get all parameters, like charset, filenames of attachments, etc.
		$params = array();
		if ($p->parameters)
		{
			foreach ($p->parameters as $x)
			{
				$params[strtolower($x->attribute)] = $x->value;
			}
		}
		if ($p->dparameters)
		{
			foreach ($p->dparameters as $x)
			{
				$params[strtolower($x->attribute)] = $x->value;
			}
		}

		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		if ($params['filename'] || $params['name']) {
			// filename may be given as 'Filename' or 'Name' or both
			$filename = ($params['filename']) ? $params['filename'] : $params['name'];
			// filename may be encoded, so see imap_mime_header_decode()
			$attachments[$filename] = $data; // this is a problem if two files have same name
		}

		// TEXT
		if ($p->type == 0 && $data) {
			if (!empty($params['charset'])) {
				$data = $this->convertStringEncoding($data, $params['charset']);
			}
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if (strtolower($p->subtype) == 'plain')
				$plainmsg .= trim($data)."\n\n";
			else $htmlmsg .= $data."<br><br>";
			$charset = $params['charset']; // assume all parts are same charset
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p->type == 2 && $data) {
			if (!empty($params['charset'])) {
				$data = $this->convertStringEncoding($data, $params['charset']);
			}
			$plainmsg .= $data."\n\n";
		}

		// SUBPART RECURSION
		if ($p->parts) {
			foreach ($p->parts as $partno0=>$p2)
			{
				$this->getpart($mbox, $mid, $p2, $partno.'.'.($partno0 + 1)); // 1.2, 1.2.1, etc.
			}
		}
	}

	/**
	 * Converts a string from one encoding to another.
	 *
	 * @param string $string		String to convert
	 * @param string $fromEncoding	String encoding
	 * @param string $toEncoding	String return encoding
	 * @return string 				Converted string if conversion was successful, or the original string if not
	 * @throws Exception
	 */
	protected function convertStringEncoding($string, $fromEncoding, $toEncoding = 'UTF-8')
	{
  		if (!$string || $fromEncoding == $toEncoding) {
  			return $string;
  		}
  		$convertedString = function_exists('iconv') ? @iconv($fromEncoding, $toEncoding.'//IGNORE', $string) : null;
  		if (!$convertedString && extension_loaded('mbstring')) {
  			$convertedString = @mb_convert_encoding($string, $toEncoding, $fromEncoding);
  		}
  		if (!$convertedString) {
  			throw new Exception('Mime string encoding conversion failed');
  		}
  		return $convertedString;
  	}

  	/**
  	 * Decode a subject string according to RFC2047
  	 * Example: '=?Windows-1252?Q?RE=A0:_ABC?=' => 'RE : ABC...'
  	 * Example: '=?UTF-8?Q?A=C3=A9B?=' => 'AéB'
  	 * Example: '=?UTF-8?B?2KLYstmF2KfbjNi0?=' =>
  	 * Example: '=?utf-8?B?UkU6IG1vZHVsZSBkb2xpYmFyciBnZXN0aW9ubmFpcmUgZGUgZmljaGllcnMg?= =?utf-8?B?UsOpZsOpcmVuY2UgZGUgbGEgY29tbWFuZGUgVFVHRURJSklSIOKAkyBwYXNz?= =?utf-8?B?w6llIGxlIDIyLzA0LzIwMjA=?='
  	 *
  	 * @param 	string	$subject		Subject
  	 * @return 	string					Decoded subject (in UTF-8)
  	 */
  	protected function decodeSMTPSubject($subject)
  	{
  		// Decode $overview[0]->subject according to RFC2047
  		// Can use also imap_mime_header_decode($str)
  		// Can use also mb_decode_mimeheader($str)
  		// Can use also iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8')
		if (function_exists('imap_mime_header_decode') && function_exists('iconv_mime_decode')) {
  			$elements = imap_mime_header_decode($subject);
  			$newstring = '';
  			if (!empty($elements)) {
  				$num = count($elements);
  				for ($i = 0; $i < $num; $i++) {
  					$stringinutf8 = (in_array(strtoupper($elements[$i]->charset), array('DEFAULT', 'UTF-8')) ? $elements[$i]->text : iconv_mime_decode($elements[$i]->text, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, $elements[$i]->charset));
  					$newstring .= $stringinutf8;
  				}
  				$subject = $newstring;
  			}
		} elseif (!function_exists('mb_decode_mimeheader')) {
			$subject = mb_decode_mimeheader($subject);
		} elseif (function_exists('iconv_mime_decode')) {
  			$subject = iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
  		}

  		return $subject;
  	}
}
