<?php
/* Copyright (C) 2015      Ion Agorria          <ion@agorria.com>
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
 *	\file       htdocs/resource/class/resourceschedule.class.php
 *	\ingroup    resource
 *  \brief      Class for resource schedule and sections
 */

/**
 *	Class for resource schedule and sections
 */
class ResourceSchedule extends CommonObject
{
    var $element = 'resourceschedule';          //!< Id that identify managed objects
    var $table_element = 'resource_schedule';   //!< Name of table without prefix where object is stored

    var $fk_resource;
    var $schedule_year;

	/**
	 * @var ResourceScheduleSection[]
	 */
    var $sections=array();

    /**
     *  Constructor
     *
     *  @param  DoliDb  $db     Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     * @param  User    $user       User that creates
     * @param  int     $notrigger  0=launch triggers after, 1=disable triggers
     * @return int                 <0 if KO, Id of created object if OK
     */
    function create(User $user, $notrigger=0)
    {
        global $conf, $langs;

        $error=0;

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
        $sql.= " fk_resource,";
        $sql.= "schedule_year,";
        $sql.= "entity";
        $sql.= ") VALUES (";
        $sql.= " ".$this->fk_resource.",";
        $sql.= " ".$this->schedule_year.",";
        $sql.= " ".getEntity($this->element, 0);
        $sql.= ")";

        $this->db->begin();

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            if (! $notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('RESOURCE_SCHEDULE_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $this->id;
        }
    }

    /**
     *    Load object in memory from database using id or resource_id and year
     *
     *    @param      int   $id             Id of schedule
     *    @param      int   $resource_id    Id of resource
     *    @param      int   $year           Year of schedule
     *    @return     int                   <0 if KO, >0 if OK
     */
    function fetch($id = 0, $resource_id = 0, $year = 0)
    {
        // Check parameters
        if (!$id && !($resource_id && $year))
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, get_class($this)."::fetch ".$this->error);
            return -1;
        }

        //Fetch request
        $sql = "SELECT rowid, fk_resource, schedule_year";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        if ($id) $sql.= " WHERE rowid = ".$this->db->escape($id);
        else
        {
            $sql.= " WHERE entity IN (".getEntity($this->element, 1).")";
            if ($resource_id && $year) {
                $sql.= " AND fk_resource = ".$this->db->escape($resource_id);
                $sql.= " AND schedule_year = ".$this->db->escape($year);
            }
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id               = $obj->rowid;
                $this->fk_resource      = $obj->fk_resource;
                $this->schedule_year    = $obj->schedule_year;
            }
            $this->db->free($resql);

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Load sections of this schedule to $this->sections
     *`
     * @param        int    $date_start    Start date
     * @param        int    $date_end      End date
     * @param        array  $statuses      Status filter, empty for all
     * @param        int    $limit         Limit sections
     * @return       int                   < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetchSections($date_start = 0, $date_end = 0, $statuses = array(), $limit = 0)
    {
        $obj = new ResourceScheduleSection($this->db);
        $sql = "SELECT rowid, date_start, date_end, status, booker_id, booker_type, booker_count";
        $sql.= " FROM ".MAIN_DB_PREFIX.$obj->table_element;
        $sql.= " WHERE fk_schedule = ".$this->id;
        if ($date_start) $sql.= " AND date_end >= ".$date_start;
        if ($date_end) $sql.= " AND date_start <= ".$date_end;
        if ($limit) $sql .= $this->db->plimit($limit);
        if ($statuses)
        {
            $sql.= " AND status IN (";
            foreach ($statuses as $i => $status)
            {
                if ($i > 0) $sql.= ',';
                $sql.= $status;
            }
            $sql.= ")";
        }

        dol_syslog(get_class($this) . "::fetchSections");
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->sections = array();
            $num = $this->db->num_rows($resql);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);
                    $section = new ResourceScheduleSection($this->db);
                    $section->id = $obj->rowid;
                    $section->fk_schedule = $this->id;
                    $section->date_start = $obj->date_start;
                    $section->date_end = $obj->date_end;
                    $section->status = intval($obj->status);
                    $section->booker_id = $obj->booker_id;
                    $section->booker_type = $obj->booker_type;
                    $section->booker_count = intval($obj->booker_count);
                    $this->sections[$i] = $section;
                    $i++;
                }
                $this->db->free($resql);
            }
            return $num;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     *	Load $resource_id schedules into $this->lines
     *
     *    @param  int       $resource_id  Id of resource
     *    @param  string    $sortorder    sort order
     *    @param  string    $sortfield    sort field
     *    @param  int       $limit        limit page
     *    @param  int       $offset       page
     *    @return int                     <0 if KO, >0 if OK
     */
    function fetchAll($resource_id, $sortorder='', $sortfield='', $limit=0, $offset=0)
    {
        $sql = "SELECT rowid, fk_resource, schedule_year";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE fk_resource = ".$resource_id;
        $sql.= " AND entity IN (".getEntity('resource',1).")";
        $sql.= $this->db->order($sortfield,$sortorder);
        if ($limit) $sql.= $this->db->plimit($limit+1,$offset);
        dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->lines = array();
            $num = $this->db->num_rows($resql);
            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    $line = new ResourceSchedule($this->db);
                    $line->id               = $obj->rowid;
                    $line->fk_resource      = $obj->fk_resource;
                    $line->schedule_year    = $obj->schedule_year;

                    $this->lines[$i] = $line;
                    $i++;
                }
                $this->db->free($resql);
            }
            return $num;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Update object into database
     *
     * @param  User $user       User that modifies
     * @param  int  $notrigger  0=launch triggers after, 1=disable triggers
     * @return int              <0 if KO, >0 if OK
     */
    function update(User $user, $notrigger=0)
    {
        global $langs,$conf;
        $error=0;

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " fk_resource=".$this->fk_resource.",";
        $sql.= " schedule_year=".$this->schedule_year;
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_SCHEDULE_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Delete object in database
     *
     * @param   User $user      User that deletes
     * @param   int  $notrigger 0=launch triggers after, 1=disable triggers
     * @return  int             <0 if KO, >0 if OK
     */
    function delete(User $user, $notrigger=0)
    {
        $error = 0;

        $this->db->begin();

        if (!$notrigger) {
            // Call triggers
            $result=$this->call_trigger('RESOURCE_SCHEDULE_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers
        }

        //Delete sections
        if (!$error) {
            $obj = new ResourceScheduleSection($this->db);
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$obj->table_element;
            $sql.= " WHERE fk_schedule = ".$this->id;

            dol_syslog(get_class($this) . "::delete sections");
            $resql = $this->db->query($sql);
            if (!$resql) {
                $error++;
                $this->errors[] = "Error " . $this->db->lasterror();
            }
        }

        //Delete object
        if (!$error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this) . "::delete");
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
     *	Initialise object with example values
     *	Id must be 0 if object instance is a specimen
     *
     *	@return	void
     */
    function initAsSpecimen()
    {
        $this->id=0;
        $this->fk_resource=0;
        $this->schedule_year=0;
    }

    /**
     *  Generate sections to $this->sections
     *
     *  @param   bool   $preview        Preview mode, doesn't write sections to db
     *  @param   mixed  $timezone       Timezone for offset data
     *  @return  int                    <0 if KO, 0 if no section generated, >0 if OK
     */
    function generateSections($preview, $timezone)
    {
        global $conf;
        require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

        //Load resource duration
        $res = new Dolresource($this->db);
        $result = $res->fetch($this->fk_resource);
        if ($result <= 0) {
            $this->error = $res->error;
            $this->errors = $res->errors;
            return -1;
        }

        //Initial values, we use UTC to then apply the offset manually
        $first = dol_get_first_day($this->schedule_year, 1, true);
        $last = dol_get_last_day($this->schedule_year, 12, true);
        $duration = dol_time_plus_duree(0, $res->duration_value, $res->duration_unit);
        $start = dol_time_plus_duree($first, $res->starting_hour, 'h');
        $tz = new DateTimeZone($timezone);
        $transitions = $tz->getTransitions($first, $last);
        dol_syslog(__METHOD__." Parameters: schedule_year".$this->schedule_year." preview=".$preview, LOG_DEBUG);
        dol_syslog("first=".$first." last=".$last." duration=".$duration." start=".$start, LOG_DEBUG);

        $limit_status = empty($conf->global->RESOURCE_SCHEDULE_USABLE_LIMITS) ? ResourceStatus::NO_SCHEDULE : ResourceStatus::DEFAULT_STATUS;
        $end = $start + $duration - 1;
        $templates = array();
        //Add starting template if there is hole
        if ($first < $start)
        {
            $templates[] = array(
                'start' => $first,
                'end' => $start - 1,
                'status' => $limit_status
            );
        }
        //Generate the templates
        while ($end <= $last)
        {
            $templates[] = array(
                'start' => $start,
                'end' => $end,
                'status' => ResourceStatus::DEFAULT_STATUS
            );
            $start += $duration;
            $end += $duration;
        }
        //Add ending template if there is hole
        if ($start < $last)
        {
            $templates[] = array(
                'start' => $start,
                'end' => $last,
                'status' => $limit_status
            );
        }
        //Convert template to sections
        $last_end = null;
        foreach ($templates as $template)
        {
            //Check consistency
            if ($last_end !== null && $last_end + 1 != $template['start'])
            {
                dol_syslog(__METHOD__." Section at ".count($this->sections)." is not continuous:", LOG_ERR);
                dol_syslog("last=".$last_end." start=".$template['start']." end=".$template['end'], LOG_ERR);
                return -3;
            }
            $last_end = $template['end'];

            //Use timezone data for offset correction
            $date_start = $template['start'];
            $date_end = $template['end'];
            $start_offset = 0;
            $end_offset = 0;
            foreach ($transitions as $transition) {
                $offset = $transition['offset'];
                if ($transition['ts'] <= $date_start)
                {
                    $start_offset = $offset;
                }
                if ($transition['ts'] <= $date_end)
                {
                    $end_offset = $offset;
                }
            }
            $date_start -= $start_offset;
            $date_end -= $end_offset;

            //Create section
            $section = new ResourceScheduleSection($this->db);
            $section->fk_schedule = $this->id;
            $section->date_start = $date_start;
            $section->date_end = $date_end;
            $section->status = $template['status'];
            $this->sections[] = $section;
        }
        dol_syslog(__METHOD__." Created sections: ".count($this->sections), LOG_DEBUG);
        //Write to DB if is not preview mode
        if (!$preview)
        {
            $error = 0;
            $this->db->begin();
            foreach ($this->sections as $section)
            {
                // Insert request
                $sql = "INSERT INTO ".MAIN_DB_PREFIX.$section->table_element."(";
                $sql.= " fk_schedule, date_start, date_end, status, status_manual";
                $sql.= ") VALUES (";
                $sql.= " ".$section->fk_schedule.",";
                $sql.= " ".$section->date_start.",";
                $sql.= " ".$section->date_end.",";
                $sql.= " ".$section->status.",";
                $sql.= " ".($section->status == ResourceStatus::NO_SCHEDULE ? ResourceStatus::NO_SCHEDULE : ResourceStatus::DEFAULT_STATUS);
                $sql.= ")";
                $resql = $this->db->query($sql);
                if (!$resql)
                {
                    $error++;
                    $this->errors[] = "Error " . $this->db->lasterror();
                    break;
                }
            }
            // Commit or rollback
            if ($error)
            {
                foreach ($this->errors as $errmsg) {
                    dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                    $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
                }
                $this->db->rollback();
                return -1 * $error;
            }
            else
            {
                $this->db->commit();
            }
        }
        return count($this->sections);
    }
}

/**
 *	Class for resource schedule and schedule section
 */
class ResourceScheduleSection extends CommonObject
{
    var $table_element = 'resource_schedule_section';   //!< Name of table without prefix where object is stored

    var $fk_schedule;
    var $date_start;
    var $date_end;
    var $status;
    var $booker_id;
    var $booker_type;
    var $booker_count;

    /**
     *  Constructor
     *
     * @param  DoliDb $db Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *    Load object in memory from database
     *
     *    @param      int   $id             Id of section
     *    @return     int                   <0 if KO, >0 if OK
     */
    public function fetch($id)
    {
        // Check parameters
        if (!$id)
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, __METHOD__." ".$this->error);
            return -1;
        }

        //Fetch request
        $sql = "SELECT rowid, date_start, date_end, status, booker_id, booker_type";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid = ".$id;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $obj->rowid;
                $this->date_start   = $obj->date_start;
                $this->date_end     = $obj->date_end;
                $this->status       = intval($obj->status);
                $this->booker_id    = $obj->booker_id;
                $this->booker_type  = $obj->booker_type;
                $this->booker_count = intval($obj->booker_count);
            }
            $this->db->free($resql);

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(__METHOD__." ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Update sections status and booker inside date range into database
     *
     * @param  array $target        Specific statuses to update only
     * @param  bool  $check_total   Ensure that all sections are changed
     * @param  bool  $skip_same     Ignores sections that their status is in target when counting sections, affects $check_total behaviour
     * @return int                  <0 if KO, 0 if all was updated, 0> left some without update
     */
    public function updateSections($target, $check_total, $skip_same = false)
    {
        global $langs;

        $error = 0;
        $total = 0;
        $left = 0;
        $multiple_booker = in_array($this->status, ResourceStatus::$MULTIPLE_BOOKER);

        $this->db->begin();

        // Clean parameters
        if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;
        if (in_array($this->status, ResourceStatus::$AVAILABLE))
        {
            $this->booker_id = null;
            $this->booker_type = null;
        }

        //Common WHERE sql
        $where = " WHERE fk_schedule = ".$this->fk_schedule;
        $where.= " AND date_end >= ".$this->date_start;
        $where.= " AND date_start <= ".$this->date_end;
        $where.= " AND status != ".ResourceStatus::NO_SCHEDULE;

        //Get number of sections in date range
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= $where;
        //Don't skip same status if status is multiple booker
        if ($skip_same && !$multiple_booker)
        {
            $sql.= " AND status != ".$this->status;
        }

        dol_syslog(__METHOD__." TotalSections", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $total = $this->db->num_rows($resql);
            $this->db->free($resql);
        }
        else
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        // Update status request
        if (! $error)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
            $sql.= " status = ".$this->status;
            $sql.= $where;
            $sql.= " AND status IN (".implode(",", $target).")";

            dol_syslog(__METHOD__." UpdateStatus", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $error++; $this->errors[]="Error ".$this->db->lasterror();
            }
            
            //Check if section change count is correct
            //Skip counting when status is multiple booker, which having same status doesn't mean that sections didn't change
            if (! $error && ! $multiple_booker)
            {
                $changed = $this->db->affected_rows($resql);
                $left = $total - $changed;
                dol_syslog(__METHOD__." section check_total=".$check_total." total=".$total." changed=".$changed, LOG_DEBUG);
                if ($check_total && $total != $changed)
                {
                    $langs->load("other");
                    $this->errors[]=$langs->trans("ScheduleSectionsUnchanged", $left);
                    $error++;
                }
            }
        }

        // Update booker request
        if (! $error)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
            if (in_array($this->status, ResourceStatus::$MANUAL)) $sql.= " status_manual = ".$this->status.",";
            if ($multiple_booker) //Multiple bookers use counter, the rest use booker_id/type
            {
                $sql.= " booker_id = null,";
                $sql.= " booker_type = null,";
                $sql.= " booker_count = booker_count + 1";
            }
            else
            {
                $sql.= " booker_id = ".(!empty($this->booker_id) ? $this->booker_id : "null").",";
                $sql.= " booker_type = ".(!empty($this->booker_type) ? "'" . $this->db->escape($this->booker_type) . "'" : "null").",";
                $sql.= " booker_count = ".(in_array($this->status, ResourceStatus::$OCCUPATION)?'1':'0');
            }
            $sql.= $where;
            $sql.= " AND status = ".$this->status;

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $error++; $this->errors[]="Error ".$this->db->lasterror();
            }
            
            //Check if section change count is correct, only when status is multiple booker
            if (! $error && $multiple_booker)
            {
                $changed = $this->db->affected_rows($resql);
                $left = $total - $changed;
                dol_syslog(__METHOD__." booker check_total=".$check_total." total=".$total." changed=".$changed, LOG_DEBUG);
                if ($check_total && $total != $changed)
                {
                    $langs->load("other");
                    $this->errors[]=$langs->trans("ScheduleSectionsUnchanged", $left);
                    $error++;
                }
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $left;
        }
    }

    /**
     *  Update selected sections
     *
     * @param  array $selected      Section ids to update
     * @param  array $target        Specific statuses to update only
     * @param  bool  $check_total   Ensure that all sections are changed
     * @return int                  <0 if KO, 0 if all was updated, 0> left some without update
     */
    public function updateSelectedSections($selected, $target, $check_total)
    {
        global $langs;

        $error = 0;
        $total = count($selected);
        $left = 0;

        $this->db->begin();

        // Clean parameters
        if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;
        if (in_array($this->status, ResourceStatus::$AVAILABLE))
        {
            $this->booker_id = null;
            $this->booker_type = null;
        }

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " status = ".$this->status.",";
        if (in_array($this->status, ResourceStatus::$MANUAL)) $sql.= " status_manual = ".$this->status.",";
        if (in_array($this->status, ResourceStatus::$MULTIPLE_BOOKER)) //Multiple bookers use counter, the rest use booker_id/type
        {
            $sql.= " booker_id = null,";
            $sql.= " booker_type = null,";
            $sql.= " booker_count = booker_count + 1";
        }
        else
        {
            $sql.= " booker_id = ".(!empty($this->booker_id) ? $this->booker_id : "null").",";
            $sql.= " booker_type = ".(!empty($this->booker_type) ? "'" . $this->db->escape($this->booker_type) . "'" : "null").",";
            $sql.= " booker_count = ".(in_array($this->status, ResourceStatus::$OCCUPATION)?'1':'0');
        }
        $sql.= " WHERE fk_schedule = ".$this->fk_schedule;
        $sql.= " AND rowid IN (".implode(",", $selected).")";
        $sql.= " AND status != ". ResourceStatus::NO_SCHEDULE;
        $sql.= " AND status IN (".implode(",", $target).")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $changed = $this->db->affected_rows($resql);
            $left = $total - $changed;
            dol_syslog(__METHOD__." check_total=".$check_total." total=".$total." changed=".$changed, LOG_DEBUG);
            if ($check_total && $total > $changed)
            {
                $langs->load("other");
                $this->errors[]=$langs->trans("ScheduleSectionsUnchanged", $left);
                $error++;
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $left;
        }
    }

    /**
     * Switch sections booker to new one, ensures that booker and status is the same.
     *
     * @param  int    $booker_id    Booker id
     * @param  string $booker_type  Booker type
     * @param  bool   $check_total  Ensure that all sections are changed
     * @return int                  <0 if KO, 0 if all was updated, 0> if left some without updateO, 0 if all was updated, 0> left some without update
     */
    public function switchSections($booker_id, $booker_type, $check_total)
    {
        global $langs;

        //Don't change available statuses
        if (in_array($this->status, ResourceStatus::$AVAILABLE)) return 0;

        //Ensure that booker are not null
        if (empty($this->booker_id) || empty($this->booker_type) || empty($booker_id) || empty($booker_type))
        {
            dol_print_error($this->error, "Missing parameters");
            return -1;
        }

        $error = 0;
        $total = 0;
        $left = 0;
        $this->db->begin();

        // Clean parameters
        if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;

        //Get number of sections in date range
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE fk_schedule = ".$this->fk_schedule;
        $sql.= " AND date_end >= ".$this->date_start;
        $sql.= " AND date_start <= ".$this->date_end;
        $sql.= " AND status != ". ResourceStatus::NO_SCHEDULE;

        dol_syslog(__METHOD__." TotalSections", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $total = $this->db->num_rows($resql);
            $this->db->free($resql);
        }
        else
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        // Update request
        if (! $error)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
            $sql.= " booker_id = ".$booker_id.",";
            $sql.= " booker_type = '".$this->db->escape($booker_type)."'";
            $sql.= " WHERE fk_schedule = ".$this->fk_schedule;
            $sql.= " AND date_end >= ".$this->date_start;
            $sql.= " AND date_start <= ".$this->date_end;
            $sql.= " AND status != ". ResourceStatus::NO_SCHEDULE;
            $sql.= " AND status = ".$this->status;
            $sql.= " AND booker_id = ".$this->booker_id;
            $sql.= " AND booker_type = '".$this->db->escape($this->booker_type)."'";

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $error++; $this->errors[]="Error ".$this->db->lasterror();
            }

            if (! $error)
            {
                $changed = $this->db->affected_rows($resql);
                $left = $total - $changed;
                dol_syslog(__METHOD__." check_total=".$check_total." total=".$total." changed=".$changed, LOG_DEBUG);
                if ($check_total && $total > $changed)
                {
                    $langs->load("other");
                    $this->errors[]=$langs->trans("ScheduleSectionsUnchanged", $left);
                    $error++;
                }
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $left;
        }
    }

    /**
     * Restores sections status to manually set one, ensures that booker and status is the same.
     *
     * @param  array $target        Specific statuses to update only or null
     * @return int                  <0 if KO, 0 if none restored, 0> restored amount
     */
    public function restoreSections($target = null)
    {
        $error = 0;
        $changed = 0;
        $this->db->begin();

        // Clean parameters
        if (!empty($this->status))
        {
            if (!is_numeric($this->status) || $this->status < 0) $this->status = ResourceStatus::DEFAULT_STATUS;
            if (in_array($this->status, ResourceStatus::$AVAILABLE))
            {
                $this->booker_id = null;
                $this->booker_type = null;
            }
        }

        //Common WHERE sql
        $where = " WHERE fk_schedule = ".$this->fk_schedule;
        $where.= " AND date_end >= ".$this->date_start;
        $where.= " AND date_start <= ".$this->date_end;
        $where.= " AND status != ". ResourceStatus::NO_SCHEDULE;
        if (!empty($target)) $where.= " AND status IN (".implode(",", $target).")";
        if (!empty($this->status)) $where.= " AND status = ".$this->status;
        if (!empty($this->booker_id)) $where.= " AND booker_id = ".$this->booker_id;
        if (!empty($this->booker_type)) $where.= " AND booker_type = '".$this->db->escape($this->booker_type)."'";

        // Decrement count request, only if bigger than zero
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        if (!empty($this->status) && in_array($this->status, ResourceStatus::$MULTIPLE_BOOKER)) //Multiple bookers uses counting, the rest use booker_id/type
        {
            $sql.= " booker_count = booker_count - 1";
        }
        else
        {
            $sql.= " booker_count = 0";
        }
        $sql.= $where;
        $sql.= " AND booker_count > 0";

        dol_syslog(__METHOD__." booker_count", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            // Restore request, only update if there is zero count
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
            $sql.= " status = status_manual,";
            $sql.= " booker_id = null,";
            $sql.= " booker_type = null";
            $sql.= $where;
            $sql.= " AND booker_count = 0";

            dol_syslog(__METHOD__, LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (! $resql)
            {
                $error++; $this->errors[]="Error ".$this->db->lasterror();
            }
            else
            {
                $changed = $this->db->affected_rows($resql);
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
                $this->error.=($this->error?', '.$errmsg:$errmsg);
            }
            $this->db->rollback();
            return -1*$error;
        }
        else
        {
            $this->db->commit();
            return $changed;
        }
    }

    /**
     *    Initialise object with example values
     *    Id must be 0 if object instance is a specimen
     *
     * @return    void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;
        $this->fk_schedule = 0;
        $this->date_start = 0;
        $this->date_end = 0;
        $this->status = 0;
        $this->booker_id = 0;
        $this->booker_type = 0;
    }
}