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
 *	\file       htdocs/product/class/priceschedule.class.php
 *	\ingroup    product
 *  \brief      Class for price schedule and sections
 */

/**
 *	Class for price schedule and sections
 */
class PriceSchedule extends CommonObject
{
    var $element = 'priceschedule';          //!< Id that identify managed objects
    var $table_element = 'product_price_schedule';   //!< Name of table without prefix where object is stored

    var $fk_product;
    var $fk_product_supplier;
    var $schedule_type;
    var $schedule_year;
    var $starting_hour;

    const TYPE_SERVICE = 1;
    const TYPE_SUPPLIER_SERVICE = 2;

    const MODE_SUM = 1;
    const MODE_AVERAGE = 2;
    const MODE_MEDIAN = 3;
    const DEFAULT_MODE = self::MODE_SUM;

    /**
     * @var PriceSchedule[]
     */
    public $lines;
    /**
     * @var PriceScheduleSection[]
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
        $sql.= " fk_product,";
        $sql.= " fk_product_supplier,";
        $sql.= " schedule_type,";
        $sql.= " schedule_year,";
        $sql.= " starting_hour,";
        $sql.= " entity";
        $sql.= ") VALUES (";
        $sql.= " ".$this->fk_product.",";
        $sql.= " ".(isset($this->fk_product_supplier)?"'".$this->db->escape($this->fk_product_supplier)."'":"null").",";
        $sql.= " ".$this->schedule_type.",";
        $sql.= " ".$this->schedule_year.",";
        $sql.= " ".$this->starting_hour.",";
        $sql.= " ".getEntity('product', 0);
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
                $result=$interface->run_triggers('PRICE_SCHEDULE_CREATE',$this,$user,$langs,$conf);
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
     *    Load object in memory from database using id or product_id and year
     *
     *    @param      int   $id                 Id of schedule
     *    @param      int   $product_id         Id of product
     *    @param      int   $type               Type of schedule
     *    @param      int   $product_supplier   Id of product supplier price
     *    @param      int   $year               Year of schedule
     *    @return     int                       <0 if KO, >0 if OK
     */
    function fetch($id = 0, $product_id = 0, $type = 0, $product_supplier = 0, $year = 0)
    {
        // Check parameters
        if (!$id && !($product_id && $year && $type))
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, get_class($this)."::fetch ".$this->error);
            return -1;
        }

        //Fetch request
        $sql = "SELECT rowid, fk_product, fk_product_supplier, schedule_type, schedule_year, starting_hour";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        if ($id) $sql.= " WHERE rowid = ".$this->db->escape($id);
        else
        {
            $sql.= " WHERE entity IN (".getEntity('product', 1).")";
            if ($product_id && $type && $year) {
                $sql.= " AND fk_product = ".$this->db->escape($product_id);
                $sql.= " AND schedule_type = ".$this->db->escape($type);
                $sql.= " AND schedule_year = ".$this->db->escape($year);
                if ($product_supplier) $sql.= " AND fk_product_supplier = ".$this->db->escape($product_supplier);
            }
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id                   = $obj->rowid;
                $this->fk_product           = $obj->fk_product;
                $this->fk_product_supplier  = $obj->fk_product_supplier;
                $this->schedule_type        = $obj->schedule_type;
                $this->schedule_year        = $obj->schedule_year;
                $this->starting_hour        = $obj->starting_hour;
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
     * @param        int    $limit         Limit sections
     * @return       int                   < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetchSections($date_start = 0, $date_end = 0, $limit = 0)
    {
        $obj = new PriceScheduleSection($this->db);
        $sql = "SELECT rowid, date_start, date_end, price";
        $sql.= " FROM ".MAIN_DB_PREFIX.$obj->table_element;
        $sql.= " WHERE fk_schedule = ".$this->id;
        if ($date_start) $sql.= " AND date_end >= ".$date_start;
        if ($date_end) $sql.= " AND date_start <= ".$date_end;
        if ($limit) $sql .= $this->db->plimit($limit);

        dol_syslog(get_class($this) . "::fetchSections");
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->sections = array();
            $num = $this->db->num_rows($resql);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);
                    $section = new PriceScheduleSection($this->db);
                    $section->id = $obj->rowid;
                    $section->fk_schedule = $this->id;
                    $section->date_start = $obj->date_start;
                    $section->date_end = $obj->date_end;
                    $section->price = $obj->price;
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
     *	Load $product_id schedules into $this->lines
     *
     *    @param  int       $product_id         Id of product
     *    @param  int       $type               Type of schedule
     *    @param  int       $product_supplier   Id of product supplier price
     *    @param  string    $sortorder          sort order
     *    @param  string    $sortfield          sort field
     *    @param  int       $limit              limit page
     *    @param  int       $offset             page
     *    @return int                           <0 if KO, >0 if OK
     */
    function fetchAll($product_id, $type = 0, $product_supplier = 0, $sortorder='', $sortfield='', $limit=0, $offset=0)
    {
        $sql = "SELECT rowid, fk_product, fk_product_supplier, schedule_type, schedule_year, starting_hour";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE fk_product = ".$product_id;
        if ($type) $sql.= " AND schedule_type = ".$type;
        if ($product_supplier) $sql.= " AND fk_product_supplier = ".$product_supplier;
        $sql.= " AND entity IN (".getEntity('product',1).")";
        $sql.= " GROUP BY rowid";
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
                    $line = new PriceSchedule($this->db);
                    $line->id                   = $obj->rowid;
                    $line->fk_product           = $obj->fk_product;
                    $line->fk_product_supplier  = $obj->fk_product_supplier;
                    $line->schedule_type        = $obj->schedule_type;
                    $line->schedule_year        = $obj->schedule_year;
                    $line->starting_hour        = $obj->starting_hour;

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
        $sql.= " fk_product=".$this->fk_product.",";
        $sql.= " fk_product_supplier=".(isset($this->fk_product_supplier)?"'".$this->db->escape($this->fk_product_supplier)."'":"null").",";
        $sql.= " schedule_type=".$this->schedule_type.",";
        $sql.= " schedule_year=".$this->schedule_year.",";
        $sql.= " starting_hour=".$this->starting_hour."";
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
                $result=$interface->run_triggers('PRICE_SCHEDULE_MODIFY',$this,$user,$langs,$conf);
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
            $result=$this->call_trigger('PRICE_SCHEDULE_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers
        }

        //Delete sections
        if (!$error) {
            $obj = new PriceScheduleSection($this->db);
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
        $this->fk_product=0;
        $this->schedule_year=0;
    }

    /**
     *  Generate sections to $this->sections
     *
     *  @param   bool   $preview    Preview mode, doesn't write sections to db
     *  @param   mixed  $timezone   Timezone for offset data
     *  @return  int                <0 if KO, 0 if no section generated, >0 if OK
     */
    function generateSections($preview, $timezone)
    {
        global $conf;
        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

        //Load service duration
        $product= new Product($this->db);
        $result = $product->fetch($this->fk_product);
        if ($result <= 0) {
            return -1;
        }
        if (!$product->isService()) {
            return -2;
        }

        //Initial values, we use UTC to then apply the offset manually
        $first = dol_get_first_day($this->schedule_year, 1, true);
        $last = dol_get_last_day($this->schedule_year, 12, true);
        $duration = dol_time_plus_duree(0, $product->duration_value, $product->duration_unit);
        $start = dol_time_plus_duree($first, $this->starting_hour, 'h');
        $tz = new DateTimeZone($timezone);
        $transitions = $tz->getTransitions($first, $last);
        dol_syslog(__METHOD__." Parameters: schedule_year=".$this->schedule_year." preview=".$preview, LOG_DEBUG);
        dol_syslog("first=".$first." last=".$last." duration=".$duration." start=".$start, LOG_DEBUG);

        $end = $start + $duration - 1;
        $templates = array();
        //Add starting template if there is hole
        if ($first < $start && empty($conf->global->PRICE_SCHEDULE_IGNORE_LIMITS))
        {
            $templates[] = array(
                'start' => $first,
                'end' => $start - 1
            );
        }
        //Generate the templates
        while ($end <= $last)
        {
            $templates[] = array(
                'start' => $start,
                'end' => $end
            );
            $start += $duration;
            $end += $duration;
        }
        //Add ending template if there is hole
        if ($start < $last && empty($conf->global->PRICE_SCHEDULE_IGNORE_LIMITS))
        {
            $templates[] = array(
                'start' => $start,
                'end' => $last
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
            $section = new PriceScheduleSection($this->db);
            $section->fk_schedule = $this->id;
            $section->date_start = $date_start;
            $section->date_end = $date_end;
            $section->price = 0;
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
                $sql.= " fk_schedule, date_start, date_end, price";
                $sql.= ") VALUES (";
                $sql.= " ".$section->fk_schedule.",";
                $sql.= " ".$section->date_start.",";
                $sql.= " ".$section->date_end.",";
                $sql.= " ".$section->price;
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

    /**
     *  Copy sections from provided object to $this->sections
     *
     *  @param   PriceSchedule  $object     Object to copy sections from
     *  @return  int                        <0 if KO, 0 if no section generated, >0 if OK
     */
    public function copySections($object)
    {
        dol_syslog(__METHOD__." Object id=".$object->id." sections=".count($object->sections), LOG_DEBUG);

        //Convert template to sections
        $object->fetchSections();
        foreach ($object->sections as $other_section)
        {
            $section = new PriceScheduleSection($this->db);
            $section->fk_schedule = $this->id;
            $section->date_start = $other_section->date_start;
            $section->date_end = $other_section->date_end;
            $section->price = $other_section->price;
            $this->sections[] = $section;
        }

        $error = 0;
        $this->db->begin();
        foreach ($this->sections as $section)
        {
            // Insert request
            $sql = "INSERT INTO ".MAIN_DB_PREFIX.$section->table_element."(";
            $sql.= " fk_schedule, date_start, date_end, price";
            $sql.= ") VALUES (";
            $sql.= " ".$section->fk_schedule.",";
            $sql.= " ".$section->date_start.",";
            $sql.= " ".$section->date_end.",";
            $sql.= " ".$section->price;
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
        return count($this->sections);
    }

    /**
     * Gets the price
     *
     *  @param   int    $product_id             Product id
     *  @param   int    $type                   Type of schedule
     *  @param   int    $product_supplier       Id of product supplier price
     *  @param   int    $date_start             Start date
     *  @param   int    $date_end               End date
     *  @return  int|null                       Price or null if could not get price
     */
    public static function getPrice($product_id, $type, $product_supplier, $date_start, $date_end)
    {
        global $db, $conf;
        dol_syslog(__METHOD__." product_id=".$product_id." type=".$type, LOG_DEBUG);
        dol_syslog("product_supplier=".$product_supplier." date_start=".$date_start." date_end=".$date_end, LOG_DEBUG);

        $prices = array();
        $start = dol_print_date($date_start, '%Y');
        $end = dol_print_date($date_end, '%Y');
        //Iterate each year between dates, only store if is valid
        for ($year = $start; $year <= $end; $year++)
        {
            $schedule = new PriceSchedule($db);
            $result = $schedule->fetch(0, $product_id, $type, $product_supplier, $year);
            if ($result > 0)
            {
                $result = $schedule->fetchSections($date_start, $date_end);
            }
            if ($result > 0)
            {
                foreach ($schedule->sections as $section)
                {
                    $prices[] = $section->price;
                }
            }
        }

        //Calculate
        $price = null;
        $amount = count($prices);
        if ($amount == 1)
        {
            $price = $prices[0];
        }
        if ($amount > 1)
        {
            $mode = $conf->global->PRICE_SCHEDULE_CALCULATION_MODE;
            if (empty($mode)) $mode = self::DEFAULT_MODE;
            if ($mode == self::MODE_SUM || $mode == self::MODE_AVERAGE)
            {
                $price = array_sum($prices);
                if ($mode == self::MODE_AVERAGE)
                {
                    $price = $price / $amount;
                }
            }
            else if ($mode == self::MODE_MEDIAN)
            {
                sort($prices, SORT_NUMERIC);
                $middle = intval(floor($amount / 2));
                $price = $prices[$middle];
                if ($amount % 2 == 0) //Even
                {
                    $price = ($price + $prices[$middle-1]) / 2;
                }
            }
        }

        if ($price === null && !empty($conf->global->PRICE_SCHEDULE_MISSING_AS_ZERO))
        {
            $price = 0;
        }

        return $price;
    }

    /**
     * Returns a array of translated calculation modes
     *
     *    @return	array				Translated array
     */
    static function calculation_modes_trans()
    {
        global $langs;

        return array(
            self::MODE_SUM => $langs->trans("Sum"),
            self::MODE_AVERAGE => $langs->trans("Average"),
            self::MODE_MEDIAN => $langs->trans("Median"),
        );
    }
}

/**
 *	Class for price schedule and schedule section
 */
class PriceScheduleSection extends CommonObject
{
    var $table_element = 'product_price_schedule_section';   //!< Name of table without prefix where object is stored

    var $fk_schedule;
    var $date_start;
    var $date_end;
    var $price;

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
        $sql = "SELECT rowid, date_start, date_end, price";
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
                $this->price        = $obj->price;
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
     *  Update sections price inside date range into database
     *
     * @param  User  $user          User that updates
     * @param  bool  $notrigger     false=launch triggers after, true=disable triggers
     * @return int                  <0 if KO, 0> if OK
     */
    public function updateSections(User $user, $notrigger = false)
    {
        global $langs, $user, $conf;

        $error = 0;

        $this->db->begin();

        // Clean parameters
        if (!is_numeric($this->price) || $this->price < 0) $this->price = 0;

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element . " SET";
        $sql.= " price = ".$this->price;
        $sql.= " WHERE fk_schedule = ".$this->fk_schedule;
        $sql.= " AND date_end >= ".$this->date_start;
        $sql.= " AND date_start <= ".$this->date_end;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('PRICE_SCHEDULE_SECTIONS_UPDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
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
            return 1;
        }
    }

    /**
     *  Update selected sections
     *
     * @param  User  $user          User that updates
     * @param  array $selected      Section ids to update
     * @param  bool  $notrigger     false=launch triggers after, true=disable triggers
     * @return int                  <0 if KO, 0> if OK
     */
    public function updateSelectedSections(User $user, $selected, $notrigger = false)
    {
        global $langs, $user, $conf;

        $error = 0;
        $selectedstr = "";

        $this->db->begin();

        // Clean parameters
        if (!is_numeric($this->price) || $this->price < 0) $this->price = 0;

        //Get selected string
        foreach ($selected as $i => $selection)
        {
            if ($i > 0) $selectedstr.= ',';
            $selectedstr.= $selection;
        }

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " price = ".$this->price;
        $sql.= " WHERE fk_schedule = ".$this->fk_schedule;
        $sql.= " AND rowid IN (".$selectedstr.")";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('PRICE_SCHEDULE_SECTIONS_UPDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
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
            return 1;
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
        $this->price = 0;
    }
}