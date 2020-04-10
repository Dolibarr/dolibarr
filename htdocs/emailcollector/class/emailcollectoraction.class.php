<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/emailcollectoraction.class.php
 * \ingroup     emailcollector
 * \brief       This file is a CRUD class file for EmailCollectorAction (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for EmailCollectorAction
 */
class EmailCollectorAction extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'emailcollectoraction';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'emailcollector_emailcollectoraction';

    /**
     * @var int  Does emailcollectoraction support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does emailcollectoraction support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string String with name of icon for emailcollectoraction. Must be the part after the 'object_' into object_emailcollectoraction.png
     */
    public $picto = 'emailcollectoraction@emailcollector';


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
        'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
        'fk_emailcollector' => array('type'=>'integer', 'label'=>'Id of emailcollector', 'foreignkey'=>'emailcollector.rowid'),
        'type' => array('type'=>'varchar(128)', 'label'=>'Type', 'enabled'=>1, 'visible'=>1, 'position'=>10, 'notnull'=>1, 'index'=>1),
        'actionparam' => array('type'=>'varchar(255)', 'label'=>'ParamForAction', 'enabled'=>1, 'visible'=>1, 'position'=>40, 'notnull'=>-1),
        'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'position'=>500, 'notnull'=>1,),
        'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'position'=>501, 'notnull'=>1,),
        'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'position'=>510, 'notnull'=>1, 'foreignkey'=>'llx_user.rowid',),
        'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'position'=>511, 'notnull'=>-1,),
        'position' => array('type'=>'integer', 'label'=>'Position', 'enabled'=>1, 'visible'=>1, 'position'=>600, 'default'=>'0',),
        'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>1000, 'notnull'=>-1,),
        'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'position'=>1000, 'notnull'=>1, 'default'=>1, 'arrayofkeyval'=>array('0'=>'Disabled', '1'=>'Enabled')),
    );
    public $rowid;
    public $fk_emailcollector;
    public $type;
    public $actionparam;

    /**
     * @var integer|string date_creation
     */
    public $date_creation;


    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $position;
    public $import_key;
    public $status;
    // END MODULEBUILDER PROPERTIES



    // If this object has a subtable with lines

    // /**
    //  * @var int    Name of subtable line
    //  */
    //public $table_element_line = 'emailcollectoractiondet';

    // /**
    //  * @var int    Field with ID of parent key if this field has a parent
    //  */
    //public $fk_element = 'fk_emailcollectoraction';

    // /**
    //  * @var int    Name of subtable class that manage subtable lines
    //  */
    //public $class_element_line = 'EmailcollectorActionline';

    // /**
    //	* @var array	List of child tables. To test if we can delete object.
    //  */
    //protected $childtables=array();

    // /**
    //  * @var EmailcollectorActionLine[]     Array of subtable lines
    //  */
    //public $lines = array();



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
            if (is_array($val['arrayofkeyval']))
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
        global $langs;
        if (empty($this->type))
        {
            $langs->load("errors");
            $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"));
            return -1;
        }

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
        if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
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

        // Load lines with object EmailcollectorActionLine

        return count($this->lines)?1:0;
    }*/

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
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $db, $conf, $langs, $hookmanager;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

        $result = '';
        $companylink = '';

        $label = '<u>'.$langs->trans("EmailcollectorAction").'</u>';
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/emailcollector/emailcollectoraction_card.php', 1).'?id='.$this->id;

        if ($option != 'nolink')
        {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
            if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        }

        $linkclose = '';
        if (empty($notooltip))
        {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label = $langs->trans("ShowEmailcollectorAction");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

            /*
             $hookmanager->initHooks(array('emailcollectoractiondao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        }
        else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        if ($withpicto != 2) $result .= $this->ref;
        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array('emailcollectoractiondao'));
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
        if (empty($this->labelStatus))
        {
            global $langs;
            //$langs->load("emailcollector");
            $this->labelStatus[1] = $langs->trans('Enabled');
            $this->labelStatus[0] = $langs->trans('Disabled');
        }

        if ($mode == 0)
        {
            return $this->labelStatus[$status];
        }
        elseif ($mode == 1)
        {
            return $this->labelStatus[$status];
        }
        elseif ($mode == 2)
        {
            if ($status == 1) return img_picto($this->labelStatus[$status], 'statut4', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelStatus[$status];
            elseif ($status == 0) return img_picto($this->labelStatus[$status], 'statut5', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelStatus[$status];
        }
        elseif ($mode == 3)
        {
            if ($status == 1) return img_picto($this->labelStatus[$status], 'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return img_picto($this->labelStatus[$status], 'statut5', '', false, 0, 0, '', 'valignmiddle');
        }
        elseif ($mode == 4)
        {
            if ($status == 1) return img_picto($this->labelStatus[$status], 'statut4', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelStatus[$status];
            elseif ($status == 0) return img_picto($this->labelStatus[$status], 'statut5', '', false, 0, 0, '', 'valignmiddle').' '.$this->labelStatus[$status];
        }
        elseif ($mode == 5)
        {
            if ($status == 1) return $this->labelStatus[$status].' '.img_picto($this->labelStatus[$status], 'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return $this->labelStatus[$status].' '.img_picto($this->labelStatus[$status], 'statut5', '', false, 0, 0, '', 'valignmiddle');
        }
        elseif ($mode == 6)
        {
            if ($status == 1) return $this->labelStatus[$status].' '.img_picto($this->labelStatus[$status], 'statut4', '', false, 0, 0, '', 'valignmiddle');
            elseif ($status == 0) return $this->labelStatus[$status].' '.img_picto($this->labelStatus[$status], 'statut5', '', false, 0, 0, '', 'valignmiddle');
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
}
