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
 * \file    htdocs/resource/class/resourceplacement.class.php
 * \ingroup resource
 * \brief   Class for resource placement
 */

/**
 * Class ResourcePlacement
 *
 *	Class for resource placement
 */
class ResourcePlacement extends CommonObject
{
    public $element = 'resourceplacement';
    public $table_element = 'resource_placement';

    public $ref_client;
    public $fk_soc;
    public $fk_resource;
    public $fk_user;
    public $date_creation = '';
    public $date_start = '';
    public $date_end = '';

    public $name_client;

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
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $error = 0;

        // Clean parameters
        if (isset($this->ref_client)) $this->ref_client = trim($this->ref_client);
        if (isset($this->fk_soc)) $this->fk_soc = trim($this->fk_soc);
        if (isset($this->fk_resource)) $this->fk_resource = trim($this->fk_resource);

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX . $this->table_element . "(";
        $sql.= "entity,";
        $sql.= "ref_client,";
        $sql.= "fk_soc,";
        $sql.= "fk_resource,";
        $sql.= "fk_user,";
        $sql.= "date_creation,";
        $sql.= "date_start,";
        $sql.= "date_end";
        $sql.= ") VALUES (";
        $sql.= " ".getEntity($this->element, 0).",";
        $sql.= " ".(! isset($this->ref_client)?"NULL":"'".$this->db->escape($this->ref_client)."'").",";
        $sql.= " ".$this->fk_soc.",";
        $sql.= " ".$this->fk_resource.",";
        $sql.= " ".$user->id.",";
        $sql.= " '".$this->db->idate($this->date_creation)."',";
        $sql.= " '".$this->db->idate($this->date_start)."',";
        $sql.= " '".$this->db->idate($this->date_end)."'";
        $sql.= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error ++;
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
        }

        // Occupy sections
        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
            $resource = new Dolresource($this->db);
            $result = $resource->fetch($this->fk_resource);
            if ($result <= 0)
            {
                $error++;
                $this->errors[] = $resource->error;
            }
            else
            {
                $result = $resource->setStatus($user, $this->date_start, $this->date_end, ResourceStatus::$AVAILABLE, ResourceStatus::OCCUPIED, $this->id, $this->element, false, ResourceLog::RESOURCE_OCCUPY, $notrigger);
                if ($result < 0)
                {
                    $error++;
                    $this->errors[] = $resource->error;
                }
            }
        }

        if (!$error) {
            if (!$notrigger) {
                // Call triggers
                $result=$this->call_trigger('RESOURCE_PLACEMENT_CREATE',$user);
                if ($result < 0) $error++;
                // End call triggers
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
            return $this->id;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int    $id  Id object
     *
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT";
        $sql.= " p.rowid,";
        $sql.= " p.ref_client,";
        $sql.= " p.fk_soc,";
        $sql.= " p.fk_resource,";
        $sql.= " p.fk_user,";
        $sql.= " p.date_creation,";
        $sql.= " p.date_start,";
        $sql.= " p.date_end,";
        $sql.= " s.nom as name_client";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as p";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE p.rowid = ".$id;
        $sql.= " AND p.fk_soc = s.rowid";

        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            if ($numrows) {
                $obj = $this->db->fetch_object($resql);

                $this->id               = $obj->rowid;
                $this->ref_client       = $obj->ref_client;
                $this->fk_soc           = $obj->fk_soc;
                $this->fk_resource      = $obj->fk_resource;
                $this->fk_user          = $obj->fk_user;
                $this->date_creation    = $this->db->jdate($obj->date_creation);
                $this->date_start       = $this->db->jdate($obj->date_start);
                $this->date_end         = $this->db->jdate($obj->date_end);
                $this->name_client      = $obj->name_client;
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
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        // Clean parameters
        if (isset($this->ref_client)) $this->ref_client = trim($this->ref_client);
        if (isset($this->fk_soc)) $this->fk_soc = trim($this->fk_soc);
        if (isset($this->fk_resource)) $this->fk_resource = trim($this->fk_resource);

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
        $sql .= " ref_client = ".(isset($this->ref_client)?"'".$this->db->escape($this->ref_client)."'":"null").",";
        $sql .= " fk_soc = ".$this->fk_soc.",";
        $sql .= " fk_resource = ".$this->fk_resource.",";
        $sql .= " fk_user = ".$this->fk_user.",";
        $sql .= " date_creation = '".$this->db->idate($this->date_creation)."',";
        $sql .= " date_start = '".$this->db->idate($this->date_start)."',";
        $sql .= " date_end = '".$this->db->idate($this->date_end)."'";
        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error ++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
        }

        if (!$error && !$notrigger) {
            // Call triggers
            $result=$this->call_trigger('RESOURCE_PLACEMENT_MODIFY',$user);
            if ($result < 0) $error++; //Do also what you must do to rollback action if trigger fail}
            // End call triggers
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
     * @param bool $notrigger false=launch triggers after, true=disable triggers
     *
     * @return int <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);
        $error = 0;

        $this->db->begin();

        if (!$error) {
            if (!$notrigger) {
                // Call triggers
                $result=$this->call_trigger('RESOURCE_PLACEMENT_DELETE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

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
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$text			Text to show instead of id
	 *	@return		string					String with URL
	 */
    function getNomUrl($withpicto=0, $text='')
    {
        global $langs;

        $result='';
        $text = empty($text)?$this->id:$text;
        $label=$langs->trans("ShowResourcePlacement").': '.$text;
        $link = '<a href="'.dol_buildpath('/resource/placement.php',1).'?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $picto='resource@resource';
        $linkend='</a>';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.$text.$linkend;
        return $result;
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
        $this->ref_client = '';
        $this->fk_soc = 0;
        $this->fk_resource = 0;
        $this->date_creation = '';
        $this->date_start = '';
        $this->date_end = '';
    }
}