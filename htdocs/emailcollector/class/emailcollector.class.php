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
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';


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
    public $picto = 'generic';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    public $fk_element = 'fk_emailcollector';

    /**
     * @var array  Array of child tables (child tables to delete before deleting a record)
     */
    protected $childtables=array('emailcollector_emailcollectorfilter', 'emailcollector_emailcollectoraction');


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
        'login'         => array('type'=>'varchar(128)', 'label'=>'Login', 'visible'=>1, 'enabled'=>1, 'position'=>101, 'notnull'=>-1, 'index'=>1, 'comment'=>"IMAP login", 'help'=>'Example: myaccount@gmail.com'),
        'password'      => array('type'=>'password', 'label'=>'Password', 'visible'=>-1, 'enabled'=>1, 'position'=>102, 'notnull'=>-1, 'comment'=>"IMAP password"),
        'source_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxSourceDirectory', 'visible'=>-1, 'enabled'=>1, 'position'=>103, 'notnull'=>1, 'default' => 'Inbox', 'help'=>'Example: INBOX'),
        //'filter'		=> array('type'=>'text', 'label'=>'Filter', 'visible'=>1, 'enabled'=>1, 'position'=>105),
        //'actiontodo'	=> array('type'=>'varchar(255)', 'label'=>'ActionToDo', 'visible'=>1, 'enabled'=>1, 'position'=>106),
        'target_directory' => array('type'=>'varchar(255)', 'label'=>'MailboxTargetDirectory', 'visible'=>1, 'enabled'=>1, 'position'=>110, 'notnull'=>0, 'comment'=>"Where to store messages once processed"),
        'datelastresult' => array('type'=>'datetime', 'label'=>'DateLastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>121, 'notnull'=>-1,),
        'codelastresult' => array('type'=>'varchar(16)', 'label'=>'CodeLastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>122, 'notnull'=>-1,),
        'lastresult'    => array('type'=>'varchar(255)', 'label'=>'LastResult', 'visible'=>1, 'enabled'=>'$action != "create" && $action != "edit"', 'position'=>123, 'notnull'=>-1,),
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
    public $login;
    public $password;
    public $source_directory;
    public $target_directory;
    public $datelastresult;
    public $lastresult;
    // END MODULEBUILDER PROPERTIES

    public $filters;
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

        $sql = "SELECT s.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."emailcollector_emailcollector as s";
        $sql.= ' WHERE s.entity IN ('.getEntity('emailcollector').')';
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
        if (! count($obj_ret)) {
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
            if ($status == 1) return img_picto($this->labelstatus[$status],'statut4', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
            elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
        }
        elseif ($mode == 3)
        {
            if ($status == 1) return img_picto($this->labelstatus[$status],'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5', '', false, 0, 0, '', 'valignmiddle');
        }
        elseif ($mode == 4)
        {
            if ($status == 1) return img_picto($this->labelstatus[$status],'statut4', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
            elseif ($status == 0) return img_picto($this->labelstatus[$status],'statut5', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelstatus[$status];
        }
        elseif ($mode == 5)
        {
            if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut5', '', false, 0, 0, '', 'valignmiddle');
        }
        elseif ($mode == 6)
        {
            if ($status == 1) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return $this->labelstatus[$status].' '.img_picto($this->labelstatus[$status],'statut5', '', false, 0, 0, '', 'valignmiddle');
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
     * Fetch filters
     *
     * @return 	int		<0 if KO, >0 if OK
     */
    public function fetchFilters()
    {
        $this->filters = array();

        $sql = 'SELECT rowid, type, rulevalue, status';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectorfilter';
        $sql.= ' WHERE fk_emailcollector = '.$this->id;
        //$sql.= ' ORDER BY position';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i = 0;
            while($i < $num)
            {
                $obj=$this->db->fetch_object($resql);
                $this->filters[$obj->rowid]=array('id'=>$obj->rowid, 'type'=>$obj->type, 'rulevalue'=>$obj->rulevalue, 'status'=>$obj->status);
                $i++;
            }
            $this->db->free($resql);
        }
        else dol_print_error($this->db);

        return 1;
    }

    /**
     * Fetch actions
     *
     * @return 	int		<0 if KO, >0 if OK
     */
    public function fetchActions()
    {
        $this->actions = array();

        $sql = 'SELECT rowid, type, actionparam, status';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'emailcollector_emailcollectoraction';
        $sql.= ' WHERE fk_emailcollector = '.$this->id;
        $sql.= ' ORDER BY position';

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            $i = 0;
            while($i < $num)
            {
                $obj=$this->db->fetch_object($resql);
                $this->actions[$obj->rowid]=array('id'=>$obj->rowid, 'type'=>$obj->type, 'actionparam'=>$obj->actionparam, 'status'=>$obj->status);
                $i++;
            }
            $this->db->free($resql);
        }
        else dol_print_error($this->db);
    }


    /**
     * Return the connectstring to use with IMAP connection function
     *
     * @return string
     */
    function getConnectStringIMAP()
    {
        // Connect to IMAP
        $flags ='/service=imap';		// IMAP
        $flags.='/ssl';					// '/tls'
        $flags.='/novalidate-cert';
        //$flags.='/readonly';
        //$flags.='/debug';

        $connectstringserver = '{'.$this->host.':993'.$flags.'}';

        return $connectstringserver;
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
        foreach($arrayofcollectors as $emailcollector)
        {
            $result = $emailcollector->doCollectOneCollector();
            dol_syslog("doCollect result = ".$result." for emailcollector->id = ".$emailcollector->id);

            $this->error.='EmailCollector ID '.$emailcollector->id.':'.$emailcollector->error.'<br>';
            if (! empty($emailcollector->errors)) $this->error.=join('<br>', $emailcollector->errors);
            $this->output.='EmailCollector ID '.$emailcollector->id.': '.$emailcollector->lastresult.'<br>';
        }

        return $nberror;
    }

    /**
     * overwitePropertiesOfObject
     *
     * @return	int		0=OK, Nb of error if error
     */

    /**
     * overwitePropertiesOfObject
     *
     * @param	object	$object			Current object
     * @param	string	$actionparam	Action parameters
     * @param	string	$messagetext	Body
     * @param	string	$subject		Subject
     * @return	int						0=OK, Nb of error if error
     */
    private function overwritePropertiesOfObject(&$object, $actionparam, $messagetext, $subject)
    {
        $errorforthisaction = 0;

        // Overwrite values with values extracted from source email
        // $this->actionparam = 'opportunity_status=123;abc=REGEX:BODY:....'
        $arrayvaluetouse = dolExplodeIntoArray($actionparam, ';', '=');
        foreach($arrayvaluetouse as $propertytooverwrite => $valueforproperty)
        {
            $tmpclass=''; $tmpproperty='';
            $tmparray=explode('.', $propertytooverwrite);
            if (count($tmparray) == 2)
            {
                $tmpclass=$tmparray[0];
                $tmpproperty=$tmparray[1];
            }
            else
            {
                $tmpproperty=$tmparray[0];
            }
            if ($tmpclass && ($tmpclass != $object->element)) continue;	// Property is for another type of object

            if (property_exists($object, $tmpproperty))
            {
                $sourcestring='';
                $sourcefield='';
                $regexstring='';
                //$transformationstring='';
                $regforregex=array();
                if (preg_match('/^REGEX:([a-zA-Z0-9]+):(.*):([^:])$/', $valueforproperty, $regforregex))
                {
                    $sourcefield=$regforregex[1];
                    $regexstring=$regforregex[2];
                    //$transofrmationstring=$regforregex[3];
                }
                elseif (preg_match('/^REGEX:([a-zA-Z0-9]+):(.*)$/', $valueforproperty, $regforregex))
                {
                    $sourcefield=$regforregex[1];
                    $regexstring=$regforregex[2];
                }

                if (! empty($sourcefield) && ! empty($regexstring))
                {
                    if (strtolower($sourcefield) == 'body') $sourcestring=$messagetext;
                    elseif (strtolower($sourcefield) == 'subject') $sourcestring=$subject;

                    $regforval=array();
                    if (preg_match('/'.preg_quote($regexstring, '/').'/', $sourcestring, $regforval))
                    {
                        // Overwrite param $tmpproperty
                        $object->$tmpproperty = $regforval[1];
                    }
                    else
                    {
                        // Nothing can be done for this param
                    }
                }
                elseif (preg_match('/^VALUE:(.*)$/', $valueforproperty, $reg))
                {
                    $object->$tmpproperty = $reg[1];
                }
                else
                {
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

        $langs->loadLangs(array("project", "companies", "mails", "errors"));

        $error = 0;
        $this->output = '';
        $this->error='';

        $now = dol_now();

        if (empty($this->host))
        {
            $this->error=$langs->trans('ErrorFieldRequired', 'EMailHost');
            return -1;
        }
        if (empty($this->login))
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

        $this->fetchFilters();
        $this->fetchActions();

        $sourcedir = $this->source_directory;
        $targetdir = ($this->target_directory ? $this->target_directory : '');			// Can be '[Gmail]/Trash' or 'mytag'

        $connectstringserver = $this->getConnectStringIMAP();
        $connectstringsource = $connectstringserver.imap_utf7_encode($sourcedir);
        $connectstringtarget = $connectstringserver.imap_utf7_encode($targetdir);

        $connection = imap_open($connectstringsource, $this->login, $this->password);
        if (! $connection)
        {
            $this->error = 'Failed to open IMAP connection '.$connectstringsource;
            return -3;
        }

        //$search='ALL';
        $search='UNDELETED';
        $searchfilterdoltrackid=0;
        $searchfilternodoltrackid=0;
        foreach($this->filters as $rule)
        {
            if (empty($rule['status'])) continue;

            if ($rule['type'] == 'to')      $search.=($search?' ':'').'TO "'.str_replace('"', '', $rule['rulevalue']).'"';
            if ($rule['type'] == 'bcc')     $search.=($search?' ':'').'BCC';
            if ($rule['type'] == 'cc')      $search.=($search?' ':'').'CC';
            if ($rule['type'] == 'from')    $search.=($search?' ':'').'FROM "'.str_replace('"', '', $rule['rulevalue']).'"';
            if ($rule['type'] == 'subject') $search.=($search?' ':'').'SUBJECT "'.str_replace('"', '', $rule['rulevalue']).'"';
            if ($rule['type'] == 'body')    $search.=($search?' ':'').'BODY "'.str_replace('"', '', $rule['rulevalue']).'"';
            if ($rule['type'] == 'seen')    $search.=($search?' ':'').'SEEN';
            if ($rule['type'] == 'unseen')  $search.=($search?' ':'').'UNSEEN';
            if ($rule['type'] == 'withtrackingid')    $searchfilterdoltrackid++;
            if ($rule['type'] == 'withouttrackingid') $searchfilternodoltrackid++;
        }

        if (empty($targetdir))	// Use last date as filter if there is no targetdir defined.
        {
            $fromdate=0;
            if ($this->datelastresult && $this->codelastresult == 'OK') $fromdate = $this->datelastresult;
            if ($fromdate > 0) $search.=($search?' ':'').'SINCE '.dol_print_date($fromdate - 1,'dayhourrfc');
        }
        dol_syslog("IMAP search string = ".$search);
        //var_dump($search);

        $nbemailprocessed=0;
        $nbemailok=0;
        $nbactiondone=0;

        // Scan IMAP inbox
        $arrayofemail= imap_search($connection, $search);
        //var_dump($arrayofemail);exit;

        // Loop on each email found
        if (! empty($arrayofemail) && count($arrayofemail) > 0)
        {
            foreach($arrayofemail as $imapemail)
            {
                if ($nbemailprocessed > 100) break;			// Do not process more than 100 email per launch

                $header = imap_fetchheader($connection, $imapemail, 0);
                $matches=array();
                preg_match_all('/([^: ]+): (.+?(?:\r\n\s(?:.+?))*)\r\n/m', $header, $matches);
                $headers = array_combine($matches[1], $matches[2]);
                //var_dump($headers);

                // $conf->global->MAIL_PREFIX_FOR_EMAIL_ID must be defined
                $host=dol_getprefix('email');

                // If there is a filter on trackid
                //var_dump($host);exit;
                if ($searchfilterdoltrackid > 0)
                {
                    //if (empty($headers['X-Dolibarr-TRACKID'])) continue;
                    if (empty($headers['References']) || ! preg_match('/@'.preg_quote($host,'/').'/', $headers['References']))
                    {
                        $nbemailprocessed++;
                        continue;
                    }
                }
                if ($searchfilternodoltrackid > 0)
                {
                    if (! empty($headers['References']) && preg_match('/@'.preg_quote($host,'/').'/', $headers['References']))
                    {
                        $nbemailprocessed++;
                        continue;
                    }
                    //if (! empty($headers['X-Dolibarr-TRACKID']) continue;
                }

                $thirdpartystatic=new Societe($this->db);
                $contactstatic=new Contact($this->db);
                $projectstatic=new Project($this->db);

                $nbactiondoneforemail = 0;
                $errorforemail = 0;
                $errorforactions = 0;
                $thirdpartyfoundby = '';
                $contactfoundby = '';
                $projectfoundby = '';

                $this->db->begin();

                //$message = imap_body($connection, $imapemail, 0);
                $overview = imap_fetch_overview($connection, $imapemail, 0);
                $structure = imap_fetchstructure($connection, $imapemail, 0);

                $partplain = $parthtml = -1;
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
                function createPartArray($structure, $prefix="")
                {
                    //print_r($structure);
                    if (count($structure->parts) > 0) {    // There some sub parts
                        foreach ($structure->parts as $count => $part) {
                            add_part_to_array($part, $prefix.($count+1), $part_array);
                        }
                    }else{    // Email does not have a seperate mime attachment for text
                        $part_array[] = array('part_number' => $prefix.'1', 'part_object' => $obj);
                    }
                    return $part_array;
                }

                /**
                 * Sub function for createPartArray(). Only called by createPartArray() and itself.
                 *
                 * @param 	Object		$obj			Structure
                 * @param 	string		$partno			Part no
                 * @param 	array		$part_array		array
                 * @return	void
                 */
                function addPartToArray($obj, $partno, &$part_array)
                {
                    $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
                    if ($obj->type == 2) { // Check to see if the part is an attached email message, as in the RFC-822 type
                        //print_r($obj);
                        if (array_key_exists('parts',$obj)) {    // Check to see if the email has parts
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
                        if (array_key_exists('parts',$obj)) {
                            foreach ($obj->parts as $count => $p) {
                                addPartToArray($p, $partno.".".($count+1), $part_array);
                            }
                        }
                    }
                }

                $result = createPartArray($structure, '');
                //var_dump($result);exit;
                foreach($result as $part)
                {
                    if ($part['part_object']->subtype == 'HTML')  $parthtml=$part['part_number'];
                    if ($part['part_object']->subtype == 'PLAIN') $partplain=$part['part_number'];
                }

                /* OLD CODE to get parthtml and partplain
                 if (count($structure->parts) > 0) {    // There some sub parts
                 foreach($structure->parts as $key => $part)
                 {
                 if ($part->subtype == 'HTML') $parthtml=($key+1);									// For example: $parthtml = 1 or 2
                 if ($part->subtype == 'PLAIN') $partplain=($key+1);
                 if ($part->subtype == 'ALTERNATIVE')
                 {
                 if (count($part->parts) > 0)
                 {
                 foreach($part->parts as $key2 => $part2)
                 {
                 if ($part2->subtype == 'HTML') $parthtml=($key+1).'.'.($key2+1);		// For example: $parthtml = 1.1 or 1.2
                 if ($part2->subtype == 'PLAIN') $partplain=($key+1).'.'.($key2+1);
                 }
                 }
                 else
                 {
                 $partplain=($key+1).'.1';
                 }
                 }
                 }
                 }
                 else
                 {
                 $partplain=1;
                 }*/

                //var_dump($structure);
                //var_dump($parthtml);var_dump($partplain);

                $messagetext = imap_fetchbody($connection, $imapemail, ($parthtml != '-1' ? $parthtml : ($partplain != '-1' ? $partplain : 0)), FT_PEEK);

                //var_dump($overview);
                //var_dump($header);
                //var_dump($message);
                //var_dump($structure->parts[0]->parts);
                //var_dump($messagetext);exit;
                $fromstring=$overview[0]->from;
                $sender=$overview[0]->sender;
                $to=$overview[0]->to;
                $sendtocc=$overview[0]->cc;
                $sendtobcc=$overview[0]->bcc;
                $date=$overview[0]->udate;
                $msgid=str_replace(array('<','>'), '', $overview[0]->message_id);
                $subject=$overview[0]->subject;
                //var_dump($msgid);exit;

                $reg=array();
                if (preg_match('/^(.*)<(.*)>$/', $fromstring, $reg))
                {
                    $from=$reg[2];
                    $fromtext=$reg[1];
                }
                else
                {
                    $from = $fromstring;
                    $fromtext='';
                }
                $fk_element_id = 0; $fk_element_type = '';

                $contactid = 0; $thirdpartyid = 0; $projectid = 0;

                // Analyze TrackId in field References
                // For example: References: <1542377954.SMTPs-dolibarr-thi649@8f6014fde11ec6cdec9a822234fc557e>
                $trackid = '';
                $reg=array();
                if (! empty($headers['References']) && preg_match('/dolibarr-([a-z]+)([0-9]+)@'.preg_quote($host,'/').'/', $headers['References'], $reg))
                {
                    $trackid = $reg[1].$reg[2];

                    $objectid = 0;
                    $objectemail = null;
                    if ($reg[0] == 'inv')
                    {
                        $objectid = $reg[1];
                        $objectemail = new Facture($this->db);
                    }
                    if ($reg[0] == 'proj')
                    {
                        $objectid = $reg[1];
                        $objectemail = new Project($this->db);
                    }
                    if ($reg[0] == 'con')
                    {
                        $objectid = $reg[1];
                        $objectemail = new Contact($this->db);
                    }
                    if ($reg[0] == 'thi')
                    {
                        $objectid = $reg[1];
                        $objectemail = new Societe($this->db);
                    }
                    if ($reg[0] == 'use')
                    {
                        $objectid = $reg[1];
                        $objectemail = new User($this->db);
                    }

                    if (is_object($objectemail))
                    {
                        $result = $objectemail->fetch($objectid);
                        if ($result > 0)
                        {
                            $fk_element_id = $objectemail->id;
                            $fk_element_type = $objectemail->element;
                            // Fix fk_element_type
                            if ($fk_element_type == 'facture') $fk_element_type = 'invoice';

                            $thirdpartyid = $objectemail->fk_soc;
                            $contactid = $objectemail->fk_socpeople;
                            $projectid = isset($objectemail->fk_project)?$objectemail->fk_project:$objectemail->fk_projet;
                        }
                    }

                    // Project
                    if ($projectid > 0)
                    {
                        $result = $projectstatic->fetch($projectid);
                        if ($result <= 0) $projectstatic->id = 0;
                        else
                        {
                            $projectid = $projectstatic->id;
                            $projectfoundby = 'trackid ('.$trackid.')';
                            if (empty($contactid)) $contactid = $projectstatic->fk_contact;
                            if (empty($thirdpartyid)) $thirdpartyid = $projectstatic->fk_soc;
                        }
                    }
                    // Contact
                    if ($contactid > 0)
                    {
                        $result = $contactstatic->fetch($contactid);
                        if ($result <= 0) $contactstatic->id = 0;
                        else
                        {
                            $contactid = $contactstatic->id;
                            $contactfoundby = 'trackid ('.$trackid.')';
                            if (empty($thirdpartyid)) $thirdpartyid = $contactstatic->fk_soc;
                        }
                    }
                    // Thirdparty
                    if ($thirdpartyid > 0)
                    {
                        $result = $thirdpartystatic->fetch($thirdpartyid);
                        if ($result <= 0) $thirdpartystatic->id = 0;
                        else
                        {
                            $thirdpartyid = $thirdpartystatic->id;
                            $thirdpartyfoundby = 'trackid ('.$trackid.')';
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
                        if ($contactstatic->fk_soc > 0)
                        {
                            $result = $thirdpartystatic->fetch($contactstatic->fk_soc);
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
                foreach($this->actions as $operation)
                {
                    if ($errorforactions) break;
                    if (empty($operation['status'])) continue;

                    // Make Operation
                    dol_syslog("Execute action ".$operation['type']." actionparam=".$operation['actionparam'].' thirdpartystatic->id='.$thirdpartystatic->id.' contactstatic->id='.$contactstatic->id.' projectstatic->id='.$projectstatic->id);

                    // Search and create thirdparty
                    if ($operation['type'] == 'loadthirdparty' || $operation['type'] == 'loadandcreatethirdparty')
                    {
                        if (empty($operation['actionparam']))
                        {
                            $errorforactions++;
                            $this->error = "Action loadthirdparty or loadandcreatethirdparty has empty parameter. Must be 'VALUE:xxx' or 'REGEX:(body|subject):regex' to define how to extract data";
                            $this->errors[] = $this->error;
                        }
                        else
                        {
                            $actionparam = $operation['actionparam'];
                            $nametouseforthirdparty='';

                            // $this->actionparam = 'VALUE:aaa' or 'REGEX:BODY:....'
                            $arrayvaluetouse = dolExplodeIntoArray($actionparam, ';', '=');
                            foreach($arrayvaluetouse as $propertytooverwrite => $valueforproperty)
                            {
                                $sourcestring='';
                                $sourcefield='';
                                $regexstring='';
                                $regforregex=array();

                                if (preg_match('/^REGEX:([a-zA-Z0-9]+):(.*)$/', $valueforproperty, $regforregex))
                                {
                                    $sourcefield=$regforregex[1];
                                    $regexstring=$regforregex[2];
                                }

                                if (! empty($sourcefield) && ! empty($regexstring))
                                {
                                    if (strtolower($sourcefield) == 'body') $sourcestring=$messagetext;
                                    elseif (strtolower($sourcefield) == 'subject') $sourcestring=$subject;

                                    $regforval=array();
                                    if (preg_match('/'.$regexstring.'/', $sourcestring, $regforval))	// Do not use preg_quote here, string is already a regex syntax, for example string is 'Name:\s([^\s]*)'
                                    {
                                        // Overwrite param $tmpproperty
                                        $nametouseforthirdparty = $regforval[1];
                                    }
                                    else
                                    {
                                        // Nothing can be done for this param
                                    }
                                    //var_dump($sourcestring); var_dump($regexstring);var_dump($nametouseforthirdparty);exit;
                                }
                                elseif (preg_match('/^VALUE:(.*)$/', $valueforproperty, $reg))
                                {
                                    $nametouseforthirdparty = $reg[1];
                                }
                                else
                                {
                                    $errorforactions++;
                                    $this->error = 'Bad syntax for description of action parameters: '.$actionparam;
                                    $this->errors[] = $this->error;
                                    break;
                                }
                            }

                            if (! $errorforactions && $nametouseforthirdparty)
                            {
                                $result = $thirdpartystatic->fetch(0, $nametouseforthirdparty);
                                if ($result < 0)
                                {
                                    $errorforactions++;
                                    $this->error = 'Error when getting thirdparty with name '.$nametouseforthirdparty.' (may be 2 record exists with same name ?)';
                                    $this->errors[] = $this->error;
                                    break;
                                }
                                elseif ($result == 0)
                                {
                                    if ($operation['type'] == 'loadandcreatethirdparty')
                                    {
                                        dol_syslog("Third party with name ".$nametouseforthirdparty." was not found. We try to create it.");

                                        // Create thirdparty
                                        $thirdpartystatic->name = $nametouseforthirdparty;
                                        if ($fromtext != $nametouseforthirdparty) $thirdpartystatic->name_alias = $fromtext;
                                        $thirdpartystatic->email = $from;

                                        // Overwrite values with values extracted from source email
                                        $errorforthisaction = $this->overwritePropertiesOfObject($thirdpartystatic, $operation['actionparam'], $messagetext, $subject);

                                        if ($errorforthisaction)
                                        {
                                            $errorforactions++;
                                        }
                                        else
                                        {
                                            $result = $thirdpartystatic->create($user);
                                            if ($result <= 0)
                                            {
                                                $errorforactions++;
                                                $this->error = $thirdpartystatic->error;
                                                $this->errors = $thirdpartystatic->errors;
                                            }
                                        }
                                    }
                                    else
                                    {
                                        dol_syslog("Third party with name ".$nametouseforthirdparty." was not found");
                                    }
                                }
                            }
                        }
                    }
                    // Create event
                    elseif ($operation['type'] == 'recordevent')
                    {
                        $actioncode = 'EMAIL_IN';

                        // Insert record of emails sent
                        $actioncomm = new ActionComm($this->db);

                        $actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                        $actioncomm->code        = 'AC_'.$actioncode;
                        $actioncomm->label       = $langs->trans("ActionAC_EMAIL_IN").' - '.$langs->trans("MailFrom").' '.$from;
                        $actioncomm->note        = $messagetext;
                        $actioncomm->fk_project  = $projectstatic->id;
                        $actioncomm->datep       = $date;
                        $actioncomm->datef       = $date;
                        $actioncomm->percentage  = -1;   // Not applicable
                        $actioncomm->socid       = $thirdpartystatic->id;
                        $actioncomm->contactid   = $contactstatic->id;
                        $actioncomm->authorid    = $user->id;   // User saving action
                        $actioncomm->userownerid = $user->id;	// Owner of action
                        // Fields when action is an email (content should be added into note)
                        $actioncomm->email_msgid = $msgid;
                        $actioncomm->email_from  = $fromstring;
                        $actioncomm->email_sender= $sender;
                        $actioncomm->email_to    = $to;
                        $actioncomm->email_tocc  = $sendtocc;
                        $actioncomm->email_tobcc = $sendtobcc;
                        $actioncomm->email_subject = $subject;
                        $actioncomm->errors_to   = '';

                        if (! in_array($fk_element_type, array('societe','contact','project','user')))
                        {
                            $actioncomm->fk_element  = $fk_element_id;
                            $actioncomm->elementtype = $fk_element_type;
                        }

                        //$actioncomm->extraparams = $extraparams;

                        // Overwrite values with values extracted from source email
                        $errorforthisaction = $this->overwritePropertiesOfObject($actioncommn, $operation['actionparam'], $messagetext, $subject);

                        if ($errorforthisaction)
                        {
                            $errorforactions++;
                        }
                        else
                        {
                            $result = $actioncomm->create($user);
                            if ($result <= 0)
                            {
                                $errorforactions++;
                                $this->errors = $actioncomm->errors;
                            }
                        }
                    }
                    // Create event
                    elseif ($operation['type'] == 'project')
                    {
                        $note_private = $langs->trans("ProjectCreatedByEmailCollector", $msgid);
                        $projecttocreate = new Project($this->db);
                        if ($thirdpartystatic->id > 0)
                        {
                            $projecttocreate->socid = $thirdpartystatic->id;
                            if ($thirdpartyfoundby) $note_private .= ' - Third party found from '.$thirdpartyfoundby;
                        }
                        if ($contactstatic->id > 0)
                        {
                            $projecttocreate->contact_id = $contactstatic->id;
                            if ($contactfoundby) $note_private .= ' - Contact/address found from '.$contactfoundby;
                        }

                        $id_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'rowid');
                        $percent_opp_status = dol_getIdFromCode($this->db, 'PROSP', 'c_lead_status', 'code', 'percent');

                        $projecttocreate->title = $subject;
                        $projecttocreate->date_start = $now;
                        $projecttocreate->date_end = '';
                        $projecttocreate->opp_status = $id_opp_status;
                        $projecttocreate->opp_percent = $percent_opp_status;
                        $projecttocreate->description = dol_concatdesc(dol_concatdesc($note_private, dolGetFirstLineOfText(dol_string_nohtmltag($messagetext, 2), 3)), '...'.$langs->transnoentities("SeePrivateNote").'...');
                        $projecttocreate->note_private = dol_concatdesc($note_private, dol_string_nohtmltag($messagetext, 2));
                        $projecttocreate->entity = $conf->entity;

                        // Get next project Ref
                        $defaultref='';
                        $modele = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;

                        // Search template files
                        $file=''; $classname=''; $filefound=0; $reldir='';
                        $dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
                        foreach($dirmodels as $reldir)
                        {
                            $file=dol_buildpath($reldir."core/modules/project/".$modele.'.php',0);
                            if (file_exists($file))
                            {
                                $filefound=1;
                                $classname = $modele;
                                break;
                            }
                        }

                        if ($filefound)
                        {
                            $result=dol_include_once($reldir."core/modules/project/".$modele.'.php');
                            $modProject = new $classname;

                            $defaultref = $modProject->getNextValue(($thirdpartystatic->id > 0 ? $thirdpartystatic : null), $projecttocreate);
                        }

                        $projecttocreate->ref = $defaultref;

                        // Overwrite values with values extracted from source email
                        $errorforthisaction = $this->overwritePropertiesOfObject($projecttocreate, $operation['actionparam'], $messagetext, $subject);

                        if ($errorforthisaction)
                        {
                            $errorforactions++;
                        }
                        else
                        {
                            if (is_numeric($projecttocreate->ref) && $projecttocreate->ref <= 0)
                            {
                                $errorforactions++;
                                $this->error = 'Failed to create project: Can\'t get a valid value for project Ref';
                            }
                            else
                            {
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

                    if (! $errorforactions)
                    {
                        $nbactiondoneforemail++;
                    }
                }

                // Error for email or not ?
                if (! $errorforactions)
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
                    }
                    else
                    {
                        dol_syslog("EmailCollector::doCollectOneCollector message ".$imapemail." to ".$connectstringtarget." was set to read", LOG_DEBUG);
                    }
                }
                else
                {
                    $errorforemail++;
                }

                if (! $errorforemail)
                {
                    $nbactiondone += $nbactiondoneforemail;
                    $nbemailok++;

                    $this->db->commit();
                }
                else
                {
                    $error++;

                    $this->db->rollback();
                }

                $nbemailprocessed++;

                unset($objectemail);
                unset($projectstatic);
                unset($thirdpartystatic);
                unset($contactstatic);
            }

            $output=$langs->trans('XEmailsDoneYActionsDone', $nbemailprocessed, $nbemailok, $nbactiondone);
        }
        else
        {
            $output=$langs->trans('NoNewEmailToProcess');
        }

        imap_expunge($connection);	// To validate any move

        imap_close($connection);

        $this->datelastresult = $now;
        $this->lastresult = $output;

        if (! empty($this->errors)) $this->lastresult.= " - ".join(" - ", $this->errors);
        $this->codelastresult = ($error ? 'KO' : 'OK');
        $this->update($user);

        dol_syslog("EmailCollector::doCollectOneCollector end", LOG_DEBUG);

        return $error?-1:1;
    }
}
