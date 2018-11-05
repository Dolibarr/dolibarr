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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file        emailcollector/class/emailcollector.class.php
 * \ingroup     emailcollector
 * \brief       This file is a CRUD class file for EmailCollector (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

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
	public $ismultientitymanaged = 0;
	/**
	 * @var int  Does emailcollector support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;
	/**
	 * @var string String with name of icon for emailcollector. Must be the part after the 'object_' into object_emailcollector.png
	 */
	public $picto = 'generic';


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
	public $fields=array(
	    'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID','visible'=>2, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1),
		'entity'        =>array('type'=>'integer',      'label'=>'Entity',           'enabled'=>1, 'visible'=>0,  'default'=>1, 'notnull'=>1,  'index'=>1, 'position'=>20),
		'ref'           =>array('type'=>'varchar(128)', 'label'=>'Ref',              'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'help'=>'Example: MyCollector1'),
		'label'         => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'notnull'=>-1, 'searchall'=>1, 'help'=>'Example: My Email collector'),
		'description'   => array('type'=>'text', 'label'=>'Description', 'visible'=>-1, 'enabled'=>1, 'position'=>60, 'notnull'=>-1),
		'host'          => array('type'=>'varchar(255)', 'label'=>'EMailHost', 'visible'=>1, 'enabled'=>1, 'position'=>100, 'notnull'=>1, 'searchall'=>1, 'comment'=>"IMAP server", 'help'=>'Example: imap.gmail.com'),
		'user'          => array('type'=>'varchar(128)', 'label'=>'Login', 'visible'=>1, 'enabled'=>1, 'position'=>101, 'notnull'=>1, 'index'=>1, 'comment'=>"IMAP login", 'help'=>'Example: myacount@gmail.com'),
		'password'      => array('type'=>'password', 'label'=>'Password', 'visible'=>-1, 'enabled'=>1, 'position'=>102, 'notnull'=>1, 'comment'=>"IMAP password"),
		'source_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxSourceDirectory', 'visible'=>-1, 'enabled'=>1, 'position'=>103, 'notnull'=>1, 'default' => 'Inbox'),
		//'filter'		=> array('type'=>'text', 'label'=>'Filter', 'visible'=>1, 'enabled'=>1, 'position'=>105),
		//'actiontodo'	=> array('type'=>'varchar(255)', 'label'=>'ActionToDo', 'visible'=>1, 'enabled'=>1, 'position'=>106),
		'target_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxTargetDirectory', 'visible'=>1, 'enabled'=>1, 'position'=>110, 'notnull'=>0, 'comment'=>"Where to store messages once processed"),
		'datelastresult' => array('type'=>'datetime', 'label'=>'DateLastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>121, 'notnull'=>-1,),
		'lastresult'    => array('type'=>'varchar(255)', 'label'=>'LastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>122, 'notnull'=>-1,),
		'note_public'   => array('type'=>'html', 'label'=>'NotePublic', 'visible'=>0, 'enabled'=>1, 'position'=>61, 'notnull'=>-1,),
		'note_private'  => array('type'=>'html', 'label'=>'NotePrivate', 'visible'=>0, 'enabled'=>1, 'position'=>62, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>501, 'notnull'=>1,),
		//'date_validation'    =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'visible'=>-2, 'enabled'=>1, 'position'=>510, 'notnull'=>1,),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'visible'=>-2, 'enabled'=>1, 'position'=>511, 'notnull'=>-1,),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'    => array('type'=>'varchar(14)', 'label'=>'ImportId', 'visible'=>-2, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1,),
		'status'        => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Inactive', '1'=>'Active'))
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

	public $date_creation;
	public $tms;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	public $import_key;


	public $host;
	public $user;
	public $password;
	public $source_directory;
    public $target_directory;
    public $datelastresult;
	public $lastresult;
	// END MODULEBUILDER PROPERTIES

	public $rules;
	public $actions;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs, $user;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;

		// Unset fields that are disabled
		foreach($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach($this->fields as $key => $val)
		{
			if (is_array($this->fields['status']['arrayofkeyval']))
			{
				foreach($this->fields['status']['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields['status']['arrayofkeyval'][$key2]=$langs->trans($val2);
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
		return $this->createCommon($user, $notrigger);
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
		global $langs, $hookmanager, $extrafields;
		$error = 0;

	    dol_syslog(__METHOD__, LOG_DEBUG);

	    $object = new self($this->db);

	    $this->db->begin();

	    // Load source object
	    $object->fetchCommon($fromid);
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
	    	$extrafields->fetch_name_optionals_label($this->element);
	    	foreach($object->array_options as $key => $option)
	    	{
	    		$shortkey = preg_replace('/options_/', '', $key);
	    		if (! empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
	    		{
	    			//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
	    			unset($object->array_options[$key]);
	    		}
	    	}
	    }

	    // Create clone
		$object->context['createfromclone'] = 'createfromclone';
	    $result = $object->createCommon($user);
	    if ($result < 0) {
	        $error++;
	        $this->error = $object->error;
	        $this->errors = $object->errors;
	    }

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
	/*public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object EmailCollectorLine

		return count($this->lines)?1:0;
	}*/

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

        $socid = $user->societe_id ? $user->societe_id : '';

        // If the internal user must only see his customers, force searching by him
        if (! $user->rights->societe->client->voir && !$socid) {
            $search_sale = $user->id;
        }
		$sql = "SELECT s.rowid";
        //if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        //    $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        //}
        $sql.= " FROM ".MAIN_DB_PREFIX."emailcollector as s";

        //if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        //    $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        //}
        //$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
        //$sql.= " WHERE s.fk_stcomm = st.id";

		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' WHERE s.entity IN ('.getEntity('emailcollector').')';
        //if ((!$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
        //    $sql.= " AND s.fk_soc = sc.fk_soc";
        //}
        //if ($socid) {
        //    $sql.= " AND s.fk_soc = ".$socid;
        //}
        if ($activeOnly) {
            $sql.= " AND s.status = 1";
        }
        $sql.= $this->db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $this->db->plimit($limit + 1, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            while ($i < $num) {
                $obj = $this->db->fetch_object($result);
                $emailcollector_static = new EmailCollector($this->db);
                if ($emailcollector_static->fetch($obj->rowid)) {
                    $obj_ret[] = $emailcollector_static;
                }
                $i++;
            }
        } else {
            dol_syslog(__METHOD__.':: Error when retrieve emailcollector list', LOG_ERR);
            $ret = -1;
        }
        if (! count($obj_ret)) {
            dol_syslog(__METHOD__.':: No emailcollector found', LOG_DEBUG);
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
		return $this->deleteCommon($user, $notrigger);
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
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $save_lastsearch_value=-1)
	{
		global $db, $conf, $langs, $hookmanager;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("EmailCollector") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/admin/emailcollector_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink')
        {
	        // Add param to save lastsearch_values or not
	        $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
	        if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
                $add_save_lastsearch_values=1;
            }
	        if ($add_save_lastsearch_values) {
                $url.='&save_lastsearch_values=1';
            }
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowEmailCollector");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

            /*
             $hookmanager->initHooks(array('myobjectdao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action,$hookmanager;
		$hookmanager->initHooks(array('emailcollectordao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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
	public function getLibStatut($mode=0)
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
	public function LibStatut($status, $mode=0)
	{
		// phpcs:enable
		if (empty($this->labelstatus))
		{
			global $langs;
			//$langs->load("mymodule");
			$this->labelstatus[1] = $langs->trans('Enabled');
			$this->labelstatus[0] = $langs->trans('Disabled');
		}

		if ($mode == 0)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 1)
		{
			return $this->labelstatus[$status];
		}
		elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut4').' '.$this->labelstatus[$status];
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut4');
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5');
		}
		elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($this->labelstatus[$status],'statut4').' '.$this->labelstatus[$status];
			elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5').' '.$this->labelstatus[$status];
		}
		elseif ($mode == 5)
		{
			if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut4');
			elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut5');
		}
		elseif ($mode == 6)
		{
			if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut4');
			elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut5');
		}
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
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
					$this->user_creation   = $cuser;
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
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		}
		else
		{
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
	 * Fetch rules
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
	public function fetch_rules()
	{
		$this->rules = array();

		$sql='SELECT type, rulevalue FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectorfilter WHERE status = 1 AND fk_emailcollector = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i = 0;
			while($i < $num)
			{
				$obj=$this->db->fetch_object($resql);
				$this->rules[]=array('type'=>$obj->type, 'rulevalue'=>$obj->rulevalue);
			}
			$this->db->free($resql);
		}

		return 1;
	}

	/**
	 * Fetch actions
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
	public function fetch_actions()
	{
		$this->actions = array();

		$sql='SELECT type, actionparam FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectoraction WHERE status = 1 AND fk_emailcollector = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i = 0;
			while($i < $num)
			{
				$obj=$this->db->fetch_object($resql);
				$this->rules[]=array('type'=>$obj->type, 'actionparam'=>$obj->actionparam);
			}
			$this->db->free($resql);
		}
	}


	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, paramerts come from the schedule job setup field 'Parameters'
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	//public function doScheduledJob($param1, $param2, ...)
	public function doCollect()
	{
		global $conf, $langs, $user;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		dol_syslog("EmailCollector::doCollect start", LOG_DEBUG);

		$error = 0;
		$this->output = '';
		$this->error='';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		if (empty($this->host))
		{
			$this->error=$langs->trans('ErrorFieldRequired', 'EMailHost');
			return -1;
		}
		if (empty($this->user))
		{
			$this->error=$langs->trans('ErrorFieldRequired', 'Login');
			return -1;
		}
		if (empty($this->source_directory))
		{
			$this->error=$langs->trans('ErrorFieldRequired', 'MailboxSourceDirectory');
			return -1;
		}
		if (! function_exists('imap_open'))
		{
			$this->error='IMAP function not enabled on your PHP';
			return -2;
		}

		$this->fetch_rules();
		$this->fetch_actions();

		$sourcedir = $this->source_directory;
		$targetdir = ($this->target_directory ? $server.$this->target_directory : '');

		// Connect to IMAP
		$flags ='/service=imap';		// IMAP
		$flags.='/ssl';					// '/tls'
		$flags.='/novalidate-cert';
		//$flags.='/readonly';
		//$flags.='/debug';

		$connectstring = '{'.$this->host.':993'.$flags.'}';
		$connectstring.=imap_utf7_encode($sourcedir);

		$connection = imap_open($connectstring, $this->user, $this->password);
		if (! $connection)
		{
			$this->error = 'Failed to open IMAP connection '.$connectstring;
			return -3;
		}

		//$search='ALL';
		$search='UNDELETED';
		foreach($this->rules as $key => $rulevalue)
		{
			if ($key == 'to')      $search=($search?' ':'').'TO "'.str_replace('"', '', $rulevalue).'"';
			if ($key == 'bcc')     $search=($search?' ':'').'BCC';
			if ($key == 'cc')      $search=($search?' ':'').'CC';
			if ($key == 'from')    $search=($search?' ':'').'FROM "'.str_replace('"', '', $rulevalue).'"';
			if ($key == 'subject') $search=($search?' ':'').'SUBJECT "'.str_replace('"', '', $rulevalue).'"';
			if ($key == 'body')    $search=($search?' ':'').'BODY "'.str_replace('"', '', $rulevalue).'"';
			if ($key == 'seen')    $search=($search?' ':'').'SEEN';
			if ($key == 'unseen')  $search=($search?' ':'').'UNSEEN';
		}

		if (empty($targetdir))	// Use last date as filter if there is no targetdir defined.
		{
			$fromdate=0;
			if ($this->datelastresult) $fromdate = $this->datelastresult;
			if ($fromdate > 0) $search.=($search?' ':'').'SINCE '.dol_print_date($fromdate - 1,'dayhourrfc');
		}
		dol_syslog("search string = ".$search);

		$nbemailprocessed=0; $nbactiondone=0;

		// Scan IMAP inbox
		$arrayofemail= imap_search($connection, $search);
		//var_dump($arrayofemail);

		// Loop on each email found
		if (! empty($arrayofemail) && count($arrayofemail) > 0)
		{
			foreach($arrayofemail as $imapemail)
			{
				$errorforactions = 0;

				$this->db->begin();

				// Record email
				foreach($this->actions as $actionkey => $actionvalue)
				{
					if ($errorforactions) break;

					// Make action


					if (! $errorforactions)
					{
						$nbactiondone++;
					}
				}

				// Move email
				if (! $errorforactions && $targetdir)
				{
					//imap_mail_move($connection, $sourcedir, $targetdir);
				}

				$nbemailprocessed++;

				$this->db->commit();
			}

			$this->output=$langs->trans('XEmailsDoneYActionsDone', $nbemailprocessed, $nbactiondone);
		}
		else
		{
			$this->output=$langs->trans('NoNewEmailToProcess');
		}

		//imap_expunge($connection);
		imap_close($connection);

		$this->datelastresult = $now;
		$this->lastresult = $this->output;
		$this->update($user);

		dol_syslog("EmailCollector::doCollect end", LOG_DEBUG);

		return $error;
	}
}
