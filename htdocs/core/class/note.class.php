<?php
/* Copyright (C) 2016		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *	\file       htdocs/core/class/note.class.php
 *	\ingroup    note
 *	\brief      File for note class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage notes
 */
class Note extends CommonObject
{
    public $element = 'note';
    public $table_element = 'note';

    public $entity;
    public $datec;
    public $title;
    public $text;
    public $objecttype;
    public $objectid;


    /**
     *    Constructor
     *
     *    @param	DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        return 1;
    }


    /**
     *    Create note in database
     *
     *    @param	User	$user       Object of user that ask creation
     *    @return   int         		>= 0 if OK, < 0 if KO
     */
    public function create($user='')
    {
        global $langs,$conf;

        $error=0;
        $langs->load("errors");
        // Clean parameters
        if (empty($this->text)) {
            $this->text = $this->title;
        }
        if (empty($this->datec)) {
            $this->datec = dol_now();
        }

        dol_syslog(get_class($this)."::create ".$this->title);

        // Check parameters
        if (empty($this->text)) {
            $this->error = $langs->trans("NoText");
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."note (entity, datec, title, text, objecttype, objectid)";
        $sql .= " VALUES ('".$conf->entity."', '".$this->db->idate($this->datec)."'";
        $sql .= ", '" . $this->db->escape($this->title) . "'";
        $sql .= ", '" . $this->db->escape($this->text) . "'";
        $sql .= ", '" . $this->objecttype . "'";
        $sql .= ", " . $this->objectid . ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "note");

            if ($this->id > 0) {
                // Call trigger
                $result=$this->call_trigger('NOTE_CREATE',$user);
                if ($result < 0) $error++;            
                // End call triggers
            } else {
                $error++;
            }

            if (! $error)
            {
                dol_syslog(get_class($this)."::Create success id=" . $this->id);
                $this->db->commit();
                return $this->id;
            }
            else
            {
                dol_syslog(get_class($this)."::Create echec update " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -3;
            }
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {

                $this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->name);
                $result=-1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                $result=-2;
            }
            $this->db->rollback();
            return $result;
        }
    }

    /**
     *      Update parameters of third party
     *
     *      @param  User	$user            			User executing update
     *      @param  int		$call_trigger    			0=no, 1=yes
     *      @return int  			           			<0 if KO, >=0 if OK
     */
    public function update($user='', $call_trigger=1)
    {
        global $langs,$conf;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        $langs->load("errors");
        $error=0;

        dol_syslog(get_class($this)."::Update id = " . $this->id . " call_trigger = " . $call_trigger);

        // Check parameters
        if (empty($this->title))
        {
            $this->error = $langs->trans("NoTitle");
            return -1;
        }

        // Clean parameters
        if (empty($this->text)) $this->text = $this->title;

        $this->db->begin();

        $sql  = "UPDATE " . MAIN_DB_PREFIX . "note SET ";
        $sql .= "entity = '" . $conf->entity ."'";
        $sql .= ", datec = '" . $this->db->idate(dol_now()) . "'";
        $sql .= ", title = '" . $this->db->escape($this->title) . "'";
        $sql .= ", text = '" . $this->db->escape($this->text) . "'";
        $sql .= ", objecttype = '" . $this->objecttype . "'";
        $sql .= ", objectid = " . $this->objectid;
        $sql .= " WHERE rowid = '" . $this->id ."'";

        dol_syslog(get_class($this)."::update sql = " .$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($call_trigger)
            {
                // Call trigger
                $result=$this->call_trigger('NOTE_MODIFY',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (! $error)
            {
                dol_syslog(get_class($this) . "::Update success");
                $this->db->commit();
                return 1;
            } else {
                setEventMessages('', $this->errors, 'errors');
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                // Doublon
                $this->error = $langs->trans("ErrorDuplicateField");
                $result =  -1;
            }
            else
            {
                $this->error = $langs->trans("Error sql = " . $sql);
                $result =  -2;
            }
            $this->db->rollback();
            return $result;
        }
    }

    /**
     *  Loads all notes from database
     *
     *  @param  array   $note		array of note objects to fill
     *  @param  string  $objecttype	type of the associated object in dolibarr
     *  @param  int     $objectid	id of the associated object in dolibarr
     *  @param  string  $sortfield  field used to sort
     *  @param  string  $sortorder  sort order
     *  @return int                 1 if ok, 0 if no records, -1 if error
     **/
    public function fetchAll(&$notes, $objecttype, $objectid, $sortfield=null, $sortorder=null)
    {
        global $conf;

        $sql = "SELECT rowid, entity, datec, title, text, objecttype, objectid FROM " . MAIN_DB_PREFIX . "note";
        $sql .= " WHERE objecttype = '" . $objecttype . "' AND objectid = " . $objectid;
        if ($conf->entity != 0) $sql .= " AND entity = " . $conf->entity;
        if ($sortfield) {
            if (empty($sortorder)) {
                $sortorder = "ASC";
            }
            $sql .= " ORDER BY " . $sortfield . " " . $sortorder;
        }

        dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            dol_syslog(get_class($this)."::fetchAll " . $num . "records", LOG_DEBUG);
            if ($num > 0)
            {
                while ($obj = $this->db->fetch_object($resql))
                {
                    $note = new Note($this->db);
                    $note->id = $obj->rowid;
                    $note->entity = $obj->entity;
                    $note->datec = $this->db->jdate($obj->datec);
                    $note->title = $obj->title;
                    $note->text = $obj->text;
                    $note->objecttype = $obj->objecttype;
                    $note->objectid = $obj->objectid;
                    $notes[] = $note;
                }
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    /**
     *  Return nb of notes
     *
     *  @param  DoliDb  $db         Database handler
     *  @param  string  $objecttype	Type of the associated object in dolibarr
     *  @param  int     $objectid 	Id of the associated object in dolibarr
     *  @return int                 Nb of notes, -1 if error
     **/
    public static function count($db, $objecttype, $objectid)
    {
        global $conf;
    
        $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "note";
        $sql .= " WHERE objecttype = '" . $objecttype . "' AND objectid = " . $objectid;
        if ($conf->entity != 0) $sql .= " AND entity = " . $conf->entity;
    
        $resql = $db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) return $obj->nb;
        } 
        return -1;
    }
    
    /**
     *  Loads a note from database
     *
     *  @param 	int		$rowid 		Id of note to load
     *  @return int 				1 if ok, 0 if no record found, -1 if error
     **/
    public function fetch($rowid=null)
    {
        global $conf;

        if (empty($rowid)) {
            $rowid = $this->id;
        }

        $sql = "SELECT rowid, entity, title, text, objecttype, objectid FROM " . MAIN_DB_PREFIX . "note";
        $sql .= " WHERE rowid = " . $rowid;
        if($conf->entity != 0) $sql .= " AND entity = " . $conf->entity;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if($this->db->num_rows($resql) > 0)
            {
                $obj = $this->db->fetch_object($resql);
                $this->entity = $obj->entity;
                $this->title = $obj->title;
                $this->text = $obj->text;
                $this->objecttype = $obj->objecttype;
                $this->objectid = $obj->objectid;
                return 1;
            }
            else
			{
                return 0;
            }
        } else {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    Delete a note from database
     *
     *    @return	int				<0 if KO, 0 if nothing done, >0 if OK
     */
    public function delete()
    {
        global $user, $langs, $conf;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $error = 0;

        // Call trigger
        $result=$this->call_trigger('NOTE_DELETE',$user);
        if ($result < 0) return -1;            
        // End call triggers         

        $this->db->begin();

        // Remove note
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "note";
        $sql.= " WHERE rowid = " . $this->id;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if (! $this->db->query($sql))
        {
            $error++;
            $this->error = $this->db->lasterror();
        }

        if (! $error) {
            $this->db->commit();

            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }

    }

}
