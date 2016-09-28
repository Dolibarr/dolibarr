<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Ion Agorria         <ion@agorria.com>
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
 * \file    resource/class/resourcelog.class.php
 * \ingroup resource
 * \brief   Class for resource confirmation
 */

/**
 * Class ResourceLog
 *
 *	Class for resource log
 */
class ResourceLog extends CommonObject
{
    public $element = 'resourcelog';
    public $table_element = 'resource_log';

    public $fk_resource;
    public $fk_user;
    public $status;
    public $action;

    //Booker of resource
    public $booker_id = '';
    public $booker_type = '';

    //Dates
    public $date_creation = '';
    public $date_start = '';
    public $date_end = '';

    //Log action
    const STATUS_CHANGE = 0;
    const RESOURCE_OCCUPY = 1;
    const RESOURCE_FREE = 2;
    const BOOKER_SWITCH = 3;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     *
     * @return int <0 if KO, Id of created object if OK
     */
    public function create(User $user)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $error = 0;

        // Clean parameters
        if (isset($this->fk_resource)) $this->fk_resource = trim($this->fk_resource);
        if (isset($this->booker_type)) $this->booker_type = trim($this->booker_type);
        if (!is_numeric($this->action) || $this->action < 0) $this->action = ResourceLog::STATUS_CHANGE;
        if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;
        if (in_array($this->status, ResourceStatus::$AVAILABLE))
        {
            $this->booker_id = null;
            $this->booker_type = null;
        }

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql.= "fk_resource,";
        $sql.= "fk_user,";
        $sql.= "booker_id,";
        $sql.= "booker_type,";
        $sql.= "date_creation,";
        $sql.= "date_start,";
        $sql.= "date_end,";
        $sql.= "status,";
        $sql.= "action";
        $sql.= ") VALUES (";
        $sql.= " ".$this->fk_resource.",";
        $sql.= " ".$user->id.",";
        $sql.= " ".(!empty($this->booker_id) ? $this->booker_id : "null").",";
        $sql.= " ".(!empty($this->booker_type) ? "'" . $this->db->escape($this->booker_type) . "'" : "null").",";
        $sql.= " '".$this->db->idate($this->date_creation)."',";
        $sql.= " '".$this->db->idate($this->date_start)."',";
        $sql.= " '".$this->db->idate($this->date_end)."',";
        $sql.= " ".$this->status.",";
        $sql.= " ".$this->action."";
        $sql.= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error ++;
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
        }

        // Commit or rollback
        if ($error) {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
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
     * @param int    $id             Id object
     * @param int    $resource_id    Id of resource
     * @param int    $date_start     Start date
     * @param int    $date_end       End date
     *
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $resource_id = 0, $date_start = 0, $date_end = 0)
    {
        // Check parameters
        if (!$id && !($resource_id && $date_start && $date_end))
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, get_class($this)."::fetch ".$this->error);
            return -1;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT";
        $sql.= " p.rowid,";
        $sql.= " p.fk_resource,";
        $sql.= " p.fk_user,";
        $sql.= " p.booker_id,";
        $sql.= " p.booker_type,";
        $sql.= " p.date_creation,";
        $sql.= " p.date_start,";
        $sql.= " p.date_end,";
        $sql.= " p.status,";
        $sql.= " p.action";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
        if ($id) $sql.= " WHERE p.rowid = ".$this->db->escape($id);
        else
        {
            $sql.= ", ".MAIN_DB_PREFIX."resource as r";
            $sql.= " WHERE p.fk_resource = ".$this->db->escape($resource_id);
            $sql.= " AND p.date_start = '".$this->db->idate($date_start)."',";
            $sql.= " AND p.date_end = '".$this->db->idate($date_end)."',";
            $sql.= " AND p.fk_resource = r.rowid";
            $sql.= " AND r.entity IN (".getEntity('resource', 1).")";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id               = $obj->rowid;
                $this->fk_resource      = $obj->fk_resource;
                $this->fk_user          = $obj->fk_user;
                $this->booker_id        = $obj->booker_id;
                $this->booker_type      = $obj->booker_type;
                $this->date_creation    = $this->db->jdate($obj->date_creation);
                $this->date_start       = $this->db->jdate($obj->date_start);
                $this->date_end         = $this->db->jdate($obj->date_end);
                $this->status           = $obj->status;
                $this->action           = $obj->action;
            }
            $this->db->free($resql);

            return $numrows;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     *
     * @return int <0 if KO, >0 if OK
     */
    public function update(User $user)
    {
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        // Clean parameters
        if (isset($this->fk_resource)) $this->fk_resource = trim($this->fk_resource);
        if (isset($this->booker_type)) $this->booker_type = trim($this->booker_type);
        if (!is_numeric($this->action) || $this->action < 0) $this->action = ResourceLog::STATUS_CHANGE;
        if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;
        if (in_array($this->status, ResourceStatus::$AVAILABLE))
        {
            $this->booker_id = null;
            $this->booker_type = null;
        }

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " fk_resource = ".$this->fk_resource.",";
        $sql.= " fk_user = ".$this->fk_user.",";
        $sql.= " booker_id = ".(!empty($this->booker_id) ? $this->booker_id : "null").",";
        $sql.= " booker_type = ".(!empty($this->booker_type) ? "'" . $this->db->escape($this->booker_type) . "'" : "null").",";
        $sql.= " date_creation = '".$this->db->idate($this->date_creation)."',";
        $sql.= " date_start = '".$this->db->idate($this->date_start)."',";
        $sql.= " date_end = '".$this->db->idate($this->date_end)."',";
        $sql.= " status = ".$this->status.",";
        $sql.= " action = ".$this->action."";
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error ++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
        }

        // Commit or rollback
        if ($error) {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Delete object in database
     *
     * @param User $user      User that deletes
     *
     * @return int <0 if KO, >0 if OK
     */
    public function delete(User $user)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $error = 0;

        $this->db->begin();

        if (!$error) {
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
            $sql .= ' WHERE rowid='.$this->id;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $error ++;
                $this->errors[] = 'Error ' . $this->db->lasterror();
                dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            }
        }

        // Commit or rollback
        if ($error) {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return - 1 * $error;
        } else {
            $this->db->commit();
            return 1;
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
        $this->id = 0;
        $this->fk_resource = 0;
        $this->booker_id = 0;
        $this->booker_type = '';
        $this->date_creation = '';
        $this->date_start = '';
        $this->date_end = '';
        $this->status = 0;
        $this->action = 0;
    }

    /**
     * Returns a array of translated action
     *
     * @return    array                Translated array
     */
    public static function translated()
    {
        global $langs;

        return array(
            ResourceLog::RESOURCE_OCCUPY => $langs->trans("RecordResourceOccupy"),
            ResourceLog::RESOURCE_FREE   => $langs->trans("RecordResourceFree"),
            ResourceLog::BOOKER_SWITCH   => $langs->trans("RecordSwitchBooker"),
            ResourceLog::STATUS_CHANGE   => $langs->trans("RecordStatusChange"),
        );
    }
}