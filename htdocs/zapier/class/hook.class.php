<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric France     <frederic.france@netlogic.fr>
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
 * \file        htdocs/zapier/class/hook.class.php
 * \ingroup     zapier
 * \brief       This file is a CRUD class file for Hook (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for Hook
 */
class Hook extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'hook';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'zapier_hook';

    /**
     * @var int  Does hook support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does hook support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for hook. Must be the part after the 'object_' into object_hook.png
     */
    public $picto = 'hook@zapier';


    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_DISABLED = -1;


    /**
     *  'type' if the field format ('integer', 'integer:Class:pathtoclass', 'varchar(x)', 'double(24,8)', 'text', 'html', 'datetime', 'timestamp', 'float')
     *  'label' the translation key.
     *  'enabled' is a condition when the field must be managed.
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
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

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
        'rowid' => array(
            'type' => 'integer',
            'label' => 'TechnicalID',
            'enabled' => 1,
            'visible' => -2,
            'noteditable' => 1,
            'notnull' => 1,
            'index' => 1,
            'position' => 1,
            'comment' => 'Id',
        ),
        'entity' => array(
            'type' => 'integer',
            'label' => 'Entity',
            'enabled' => 1,
            'visible' => 0,
            'notnull' => 1,
            'default' => 1,
            'index' => 1,
            'position' => 20,
        ),
        'fk_user' => array(
            'type' => 'integer',
            'label' => 'UserOwner',
            'enabled' => 1,
            'visible' => -2,
            'notnull' => 1,
            'position' => 510,
            'foreignkey' => 'llx_user.rowid',
        ),
        'url' => array(
            'type' => 'varchar(255)',
            'label' => 'Url',
            'enabled' => 1,
            'visible' => 1,
            'position' => 30,
            'searchall' => 1,
            'css' => 'minwidth200',
            'help' => 'Hook url',
            'showoncombobox' => 1,
        ),
        'module' => array(
            'type' => 'varchar(128)',
            'label' => 'Url',
            'enabled' => 1,
            'visible' => 1,
            'position' => 30,
            'searchall' => 1,
            'css' => 'minwidth200',
            'help' => 'Hook module',
            'showoncombobox' => 1,
        ),
        'action' => array(
            'type' => 'varchar(128)',
            'label' => 'Url',
            'enabled' => 1,
            'visible' => 1,
            'position' => 30,
            'searchall' => 1,
            'css' => 'minwidth200',
            'help' => 'Hook action trigger',
            'showoncombobox' => 1,
        ),
        'event' => array(
            'type' => 'varchar(255)',
            'label' => 'Event',
            'enabled' => 1,
            'visible' => 1,
            'position' => 30,
            'searchall' => 1,
            'css' => 'minwidth200',
            'help' => 'Event',
            'showoncombobox' => 1,
        ),
        'date_creation' => array(
            'type' => 'datetime',
            'label' => 'DateCreation',
            'enabled' => 1,
            'visible' => -2,
            'notnull' => 1,
            'position' => 500,
        ),
        'import_key' => array(
            'type' => 'varchar(14)',
            'label' => 'ImportId',
            'enabled' => 1,
            'visible' => -2,
            'notnull' => -1,
            'index' => 0,
            'position' => 1000,
        ),
        'status' => array(
            'type' => 'integer',
            'label' => 'Status',
            'enabled' => 1,
            'visible' => 1,
            'notnull' => 1,
            'default' => 0,
            'index' => 1,
            'position' => 1000,
            'arrayofkeyval' => array(
                0 => 'Draft',
                1 => 'Active',
                -1 => 'Canceled',
            ),
        ),
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
     * @var string amount
     */
    public $amount;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var integer|string date_creation
     */
    public $date_creation;

    /**
     * @var integer tms
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
     * @var string import_key
     */
    public $import_key;


    // If this object has a subtable with lines

    /**
     * @var int    Name of subtable line
     */
    //public $table_element_line = 'hookdet';

    /**
     * @var int    Field with ID of parent key if this field has a parent
     */
    //public $fk_element = 'fk_hook';

    /**
     * @var int    Name of subtable class that manage subtable lines
     */
    //public $class_element_line = 'MyObjectline';

    /**
     * @var array  Array of child tables (child tables to delete before deleting a record)
     */
    //protected $childtables=array('hookdet');

    /**
     * @var MyObjectLine[]     Array of subtable lines
     */
    //public $lines = array();



    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs, $user;

        $this->db = $db;

        if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        foreach ($this->fields as $key => $val) {
            if (is_array($this->fields['status']['arrayofkeyval'])) {
                foreach ($this->fields['status']['arrayofkeyval'] as $key2 => $val2) {
                    $this->fields['status']['arrayofkeyval'][$key2] = $langs->trans($val2);
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
     * Clone an object into another one
     *
     * @param   User    $user       User that creates
     * @param   int     $fromid     Id of object to clone
     * @return  mixed               New object created, <0 if KO
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
        if (is_array($object->array_options) && count($object->array_options) > 0) {
            $extrafields->fetch_name_optionals_label($this->table_element);
            foreach ($object->array_options as $key => $option) {
                $shortkey = preg_replace('/options_/', '', $key);
                if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
                    // var_dump($key);
                    // var_dump($clonedObj->array_options[$key]);
                    // exit;
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
        if ($result > 0 && !empty($this->table_element_line)) {
            $this->fetchLines();
        }
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

        // Load lines with object MyObjectLine

        return count($this->lines)?1:0;
    }*/

    /**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = 'SELECT';
        $sql .= ' t.rowid';
        // TODO Get all fields
        $sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
        $sql .= ' WHERE t.entity = '.$conf->entity;
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key.'='.$value;
                } elseif (strpos($key, 'date') !== false) {
                    $sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } else {
                    $sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= ' '.$this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);

            while ($obj = $this->db->fetch_object($resql)) {
                $record = new self($this->db);

                $record->id = $obj->rowid;
                // TODO Get other fields

                //var_dump($record->id);
                $records[$record->id] = $record;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

            return -1;
        }
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
     *  Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers after, true=disable triggers
     * @return int             <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $db, $conf, $langs, $hookmanager, $action;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (!empty($conf->dol_no_mouse_hover)) {
            // Force disable tooltips
            $notooltip = 1;
        }

        $result = '';

        $label = '<u>'.$langs->trans("Hook").'</u>';
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/zapier/hook_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink') {
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
        if (empty($notooltip)) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("ShowMyObject");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

            /*
             $hookmanager->initHooks(array('hookdao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        } else {
            $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        }
        if ($withpicto != 2) {
            $result .= $this->ref;
        }
        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        $hookmanager->initHooks(array('hookdao'));
        $parameters = array(
            'id' => $this->id,
            'getnomurl' => $result,
        );
        // Note that $action and $object may have been modified by some hooks
        $reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     *  Return label of the status
     *
     *  @param  int     $mode       0 = long label
     *                              1 = short label
     *                              2 = Picto + short label
     *                              3 = Picto, 4=Picto + long label
     *                              5 = Short label + Picto
     *                              6 = Long label + Picto
     *  @return	string              Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return the status
     *
     *  @param  int     $status     Id status
     *  @param  int     $mode       0 = long label,
     *                              1 = short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return string              Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
    	global $langs;

    	if (empty($this->labelStatus) || empty($this->labelStatusShort))
    	{
    		global $langs;
    		//$langs->load("mymodule");
    		$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Disabled');
    		$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
    		$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Disabled');
    		$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
    	}

    	$statusType = 'status5';
    	if ($status == self::STATUS_VALIDATED) $statusType = 'status4';

    	return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     *  Load the info information in the object
     *
     *  @param  int     $id       Id of object
     *  @return	void
     */
    public function info($id)
    {
        $sql = 'SELECT rowid, date_creation as datec, tms as datem,';
        $sql .= ' fk_user_creat, fk_user_modif';
        $sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
        $sql .= ' WHERE t.rowid = '.$id;
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation = $cuser;
                }

                if ($obj->fk_user_valid) {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }

                if ($obj->fk_user_cloture) {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture = $cluser;
                }

                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
                $this->date_validation = $this->db->jdate($obj->datev);
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
     * Action executed by scheduler
     * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
     *
     * @return  int         0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
    public function doScheduledJob()
    {
        global $conf, $langs;

        //$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

        $error = 0;
        $this->output = '';
        $this->error = '';

        dol_syslog(__METHOD__, LOG_DEBUG);

        $now = dol_now();

        $this->db->begin();

        // ...

        $this->db->commit();

        return $error;
    }
}

/**
 * Class MyObjectLine. You can also remove this and generate a CRUD class for lines objects.
 */
/*
class MyObjectLine
{
    // @var int ID
    public $id;
    // @var mixed Sample line property 1
    public $prop1;
    // @var mixed Sample line property 2
    public $prop2;
}
*/
