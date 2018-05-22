<?php
/* Copyright (C) - 2013-2016    Jean-François FERRY    <hello@librethic.io>
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
 *  \file       ticketsup/class/ticketsuplogs.class.php
 *  \ingroup    ticketsup
 *  \brief      This file CRUD class file (Create/Read/Update/Delete) for ticket logs
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php";
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 * Class of log for ticketsup
 */
class Ticketsuplogs// extends CommonObject
{
    public $db; //!< To store db handler
    public $error; //!< To return error code (or message)
    public $errors = array(); //!< To return several error codes (or messages)
    public $element = 'ticketsuplogs'; //!< Id that identify managed objects
    public $table_element = 'ticketsuplogs'; //!< Name of table without prefix where object is stored

    public $id;

    public $fk_track_id;
    public $fk_user_create;
    public $datec = '';
    public $message;

    /**
     *  Constructor
     *
     *  @param DoliDb $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     *  @param  User $user      User that creates
     *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     *  @return int             <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_track_id)) {
            $this->fk_track_id = trim($this->fk_track_id);
        }

        if (isset($this->fk_user_create)) {
            $this->fk_user_create = trim($this->fk_user_create);
        }

        if (isset($this->message)) {
            $this->message = trim($this->message);
        }

        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "ticketsup_logs(";

        $sql .= "fk_track_id,";
        $sql .= "fk_user_create,";
        $sql .= "datec,";
        $sql .= "message";

        $sql .= ") VALUES (";

        $sql .= " " . (!isset($this->fk_track_id) ? 'NULL' : "'" . $this->db->escape($this->fk_track_id) . "'") . ",";
        $sql .= " " . (!isset($this->fk_user_create) ? 'NULL' : "'" . $this->db->escape($this->fk_user_create) . "'") . ",";
        $sql .= " " . (!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'" . $this->db->idate($this->datec). "'") . ",";
        $sql .= " " . (!isset($this->message) ? 'NULL' : "'" . $this->db->escape($this->message) . "'") . "";

        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "ticketsup_logs");

            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     *  Load object in memory from the database
     *
     *  @param  int $id 		Id object
     *  @return int              <0 if KO, >0 if OK
     */
    public function fetch($id)
    {
        global $langs;
        $sql = "SELECT";
        $sql .= " t.rowid,";

        $sql .= " t.fk_track_id,";
        $sql .= " t.fk_user_create,";
        $sql .= " t.datec,";
        $sql .= " t.message";

        $sql .= " FROM " . MAIN_DB_PREFIX . "ticketsup_logs as t";
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->fk_track_id = $obj->fk_track_id;
                $this->fk_user_create = $obj->fk_user_create;
                $this->datec = $this->db->jdate($obj->datec);
                $this->message = $obj->message;
            }
            $this->db->free($resql);

            return 1;
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Update object into database
     *
     *  @param  User $user      User that modifies
     *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
     *  @return int             <0 if KO, >0 if OK
     */
    public function update($user = 0, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_track_id)) {
            $this->fk_track_id = trim($this->fk_track_id);
        }

        if (isset($this->fk_user_create)) {
            $this->fk_user_create = trim($this->fk_user_create);
        }

        if (isset($this->message)) {
            $this->message = trim($this->message);
        }

        // Check parameters
        // Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . "ticketsup_logs SET";

        $sql .= " fk_track_id=" . (isset($this->fk_track_id) ? "'" . $this->db->escape($this->fk_track_id) . "'" : "null") . ",";
        $sql .= " fk_user_create=" . ($this->fk_user_create > 0 ? $this->fk_user_create : "null") . ",";
        $sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . ",";
        $sql .= " message=" . (isset($this->message) ? "'" . $this->db->escape($this->message) . "'" : "null") . "";

        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Delete object in database
     *
     *  @param  User $user      	User that deletes
     *  @param  int  $notrigger 	0=launch triggers after, 1=disable triggers
     *  @return int                 <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                //$interface=new Interfaces($this->db);
                //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
                //if ($result < 0) { $error++; $this->errors=$interface->errors; }
                //// End call triggers
            }
        }

        if (!$error) {
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ticketsup_logs";
            $sql .= " WHERE rowid=" . $this->id;

            dol_syslog(get_class($this) . "::delete sql=" . $sql);
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     *  Initialise object with example values
     *  Id must be 0 if object instance is a specimen
     *
     *  @return void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;

        $this->fk_track_id = '';
        $this->fk_user_create = '';
        $this->datec = '';
        $this->message = '';
    }
}
