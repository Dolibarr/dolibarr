<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/product/dynamic_price/class/price_global_variable_updater.class.php
 *	\ingroup    product
 *  \brief      Class for price global variable updaters table
 */


/**
 *	Class for price global variable updaters table
 */
class PriceGlobalVariableUpdater
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error='';

    /**
     * @var string[] Error codes (or messages)
     */
    public $errors = array();

    public $types=array(0, 1);				//!< Updater types
    public $update_min = 5;				//!< Minimal update rate

    /**
     * @var int ID
     */
    public $id;

    public $type;

    /**
     * @var string description
     */
    public $description;

    public $parameters;

    /**
     * @var int ID
     */
    public $fk_variable;

    public $update_interval;				//!< Interval in mins
    public $next_update;					//!< Next update timestamp
    public $last_status;

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = "c_price_global_variable_updater";

    /**
     *  Constructor
     *
     *  @param  DoliDb      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        $error=0;

        $this->checkParameters();

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql.= "type, description, parameters, fk_variable, update_interval, next_update, last_status";
        $sql.= ") VALUES (";
        $sql.= " ".$this->type.",";
        $sql.= " ".(isset($this->description)?"'".$this->db->escape($this->description)."'":"''").",";
        $sql.= " ".(isset($this->parameters)?"'".$this->db->escape($this->parameters)."'":"''").",";
        $sql.= " ".$this->fk_variable.",";
        $sql.= " ".$this->update_interval.",";
        $sql.= " ".$this->next_update.",";
        $sql.= " ".(isset($this->last_status)?"'".$this->db->escape($this->last_status)."'":"''");
        $sql.= ")";

        $this->db->begin();

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            if (! $notrigger)
            {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //$result=$this->call_trigger('MYOBJECT_CREATE',$user);
                //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
                //// End call triggers
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
            return $this->id;
        }
    }


    /**
     *  Load object in memory from the database
     *
     *  @param		int		$id    	Id object
     *  @return		int			    < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    public function fetch($id)
    {
        $sql = "SELECT type, description, parameters, fk_variable, update_interval, next_update, last_status";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid = ".$id;

        dol_syslog(__METHOD__);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->id				= $id;
                $this->type				= $obj->type;
                $this->description		= $obj->description;
                $this->parameters		= $obj->parameters;
                $this->fk_variable		= $obj->fk_variable;
                $this->update_interval	= $obj->update_interval;
                $this->next_update		= $obj->next_update;
                $this->last_status		= $obj->last_status;
                $this->checkParameters();
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update($user = 0, $notrigger = 0)
    {
        $error=0;

        $this->checkParameters();

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " type = ".$this->type.",";
        $sql.= " description = ".(isset($this->description)?"'".$this->db->escape($this->description)."'":"''").",";
        $sql.= " parameters = ".(isset($this->parameters)?"'".$this->db->escape($this->parameters)."'":"''").",";
        $sql.= " fk_variable = ".$this->fk_variable.",";
        $sql.= " update_interval = ".$this->update_interval.",";
        $sql.= " next_update = ".$this->next_update.",";
        $sql.= " last_status = ".(isset($this->last_status)?"'".$this->db->escape($this->last_status)."'":"''");
        $sql.= " WHERE rowid = ".$this->id;

        $this->db->begin();

        dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // if (! $error)
        // {
        //     if (! $notrigger)
        //     {
        //         // Uncomment this and change MYOBJECT to your own tag if you
        //         // want this action calls a trigger.

        //         //// Call triggers
        //         //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
        //         //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
        //         //// End call triggers
        //     }
        // }

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
     *  Delete object in database
     *
     * 	@param	int		$rowid		 Row id of global variable
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return	int					 <0 if KO, >0 if OK
     */
    public function delete($rowid, $user, $notrigger = 0)
    {
        $error=0;

        $this->db->begin();

        //if (! $error)
        //{
        //    if (! $notrigger)
        //    {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //$result=$this->call_trigger('MYOBJECT_DELETE',$user);
                //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
                //// End call triggers
        //    }
        //}

        if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql.= " WHERE rowid = ".$rowid;

            dol_syslog(__METHOD__);
            $resql = $this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
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
     *	Initialise object with example values
     *	Id must be 0 if object instance is a specimen
     *
     *	@return	void
     */
    public function initAsSpecimen()
    {
        $this->id=0;
        $this->type=0;
        $this->description='';
        $this->parameters='';
        $this->fk_variable=0;
        $this->update_interval=0;
        $this->next_update=0;
        $this->last_status='';
    }

    /**
     *  Returns the last updated time in string html format, returns "never" if its less than 1
     *
     *  @return	string
     */
    public function getLastUpdated()
    {
        global $langs;
        $last = $this->next_update - ($this->update_interval * 60);
        if ($last < 1) {
            return $langs->trans("Never");
        }
        $status = empty($this->last_status) ? $langs->trans("CorrectlyUpdated") : $this->last_status;
        return $status.'<br>'.dol_print_date($last, '%d/%m/%Y %H:%M:%S');
    }

    /**
     *	Checks if all parameters are in order
     *
     *	@return	void
     */
    public function checkParameters()
    {
        // Clean parameters
        if (isset($this->description)) $this->description=trim($this->description);
        if (isset($this->parameters)) $this->parameters=trim($this->parameters);
        else $this->parameters="";
        if (isset($this->last_status)) $this->last_status=trim($this->last_status);

        // Check parameters
        if (empty($this->type) || !is_numeric($this->type) || !in_array($this->type, $this->types)) $this->type=0;
        if (empty($this->update_interval) || !is_numeric($this->update_interval) || $this->update_interval < 1) $this->update_interval=$this->update_min;
        if (empty($this->next_update) || !is_numeric($this->next_update) || $this->next_update < 0) $this->next_update=0;
    }

    /**
     *  List all price global variables
     *
     *  @return	array				Array of price global variable updaters
     */
    public function listUpdaters()
    {
        $sql = "SELECT rowid, type, description, parameters, fk_variable, update_interval, next_update, last_status";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $retarray = array();

            while ($record = $this->db->fetch_array($resql))
            {
                $updater_obj = new PriceGlobalVariableUpdater($this->db);
                $updater_obj->id				= $record["rowid"];
                $updater_obj->type				= $record["type"];
                $updater_obj->description		= $record["description"];
                $updater_obj->parameters		= $record["parameters"];
                $updater_obj->fk_variable		= $record["fk_variable"];
                $updater_obj->update_interval	= $record["update_interval"];
                $updater_obj->next_update		= $record["next_update"];
                $updater_obj->last_status		= $record["last_status"];
                $updater_obj->checkParameters();
                $retarray[]=$updater_obj;
            }

            $this->db->free($resql);
            return $retarray;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  List all updaters which need to be processed
     *
     *  @return	array				Array of price global variable updaters
     */
    public function listPendingUpdaters()
    {
        $sql = "SELECT rowid, type, description, parameters, fk_variable, update_interval, next_update, last_status";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE next_update < ".dol_now();

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $retarray = array();

            while ($record = $this->db->fetch_array($resql))
            {
                $updater_obj = new PriceGlobalVariableUpdater($this->db);
                $updater_obj->id				= $record["rowid"];
                $updater_obj->type				= $record["type"];
                $updater_obj->description		= $record["description"];
                $updater_obj->parameters		= $record["parameters"];
                $updater_obj->fk_variable		= $record["fk_variable"];
                $updater_obj->update_interval	= $record["update_interval"];
                $updater_obj->next_update		= $record["next_update"];
                $updater_obj->last_status		= $record["last_status"];
                $updater_obj->checkParameters();
                $retarray[]=$updater_obj;
            }

            $this->db->free($resql);
            return $retarray;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  Handles the processing of this updater
     *
     *  @return	int					 <0 if KO, 0 if OK but no global variable found, >0 if OK
     */
    public function process()
    {
        global $langs, $user;
        $langs->load("errors");
        dol_syslog(__METHOD__, LOG_DEBUG);

        $this->error = null;
        $this->checkParameters();

        //Try to load the target global variable and abort if fails
        if ($this->fk_variable < 1) {
            $this->error = $langs->trans("ErrorGlobalVariableUpdater5");
            return 0;
        }
        $price_globals = new PriceGlobalVariable($this->db);
        $res = $price_globals->fetch($this->fk_variable);
        if ($res < 1) {
            $this->error = $langs->trans("ErrorGlobalVariableUpdater5");
            return 0;
        }

        //Process depending of type
        if ($this->type == 0 || $this->type == 1) {
            //Get and check if required parameters are present
            $parameters = json_decode($this->parameters, true);
            if (!isset($parameters)) {
                $this->error = $langs->trans("ErrorGlobalVariableUpdater1", $this->parameters);
                return -1;
            }
            $url = $parameters['URL'];
            if (!isset($url)) {
                $this->error = $langs->trans("ErrorGlobalVariableUpdater2", 'URL');
                return -1;
            }
            $value = $parameters['VALUE'];
            if (!isset($value)) {
                $this->error = $langs->trans("ErrorGlobalVariableUpdater2", 'VALUE');
                return -1;
            }
            $result = "";
            if ($this->type == 0) {
                // Call JSON request
                include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
                $tmpresult=getURLContent($url);
                $code=$tmpresult['http_code'];
                $result=$tmpresult['content'];

                if (!isset($result)) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater0", "empty response");
                    return -1;
                }
                if ($code !== 200) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater0", $code.' '.$tmpresult['curl_error_msg']);
                    return -1;
                }

                //Decode returned response
                $result = json_decode($result, true);
            } elseif ($this->type == 1) {
                $ns = $parameters['NS'];
                if (!isset($ns)) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater2", 'NS');
                    return -1;
                }
                $method = $parameters['METHOD'];
                if (!isset($method)) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater2", 'METHOD');
                    return -1;
                }
                $data = $parameters['DATA'];
                if (!isset($data)) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater2", 'DATA');
                    return -1;
                }

                //SOAP client
                require_once NUSOAP_PATH.'/nusoap.php';
                $soap_client = new nusoap_client($url);
                $soap_client->soap_defencoding='UTF-8';
                $soap_client->decodeUTF8(false);
                $result = $soap_client->call($method, $data, $ns, '');

                //Check if result is a error
                if ($result === false) {
                    $this->error = $langs->trans("ErrorGlobalVariableUpdater4", $soap_client->error_str);
                    return -1;
                }
            }

            //Explode value and walk for each key in value array to get the relevant key
            $value = explode(',', $value);
            foreach ($value as $key) {
                $result = $result[$key];
            }
            if (!isset($result)) {
                $this->error = $langs->trans("ErrorGlobalVariableUpdater3");
                return -1;
            }

            //Save data to global and update it
            $price_globals->value = $result;
            $price_globals->update($user);
        }
        return 1;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Update next_update into database
     *
     *  @param	string	$next_update Next update to write
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update_next_update($next_update, $user = 0, $notrigger = 0)
    {
        // phpcs:enable
        $error=0;

        $this->next_update = $next_update;
        $this->checkParameters();

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " next_update = ".$this->next_update;
        $sql.= " WHERE rowid = ".$this->id;

        $this->db->begin();

        dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Update last_status into database
     *
     *  @param	string	$last_status Status to write
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update_status($last_status, $user = 0, $notrigger = 0)
    {
        // phpcs:enable
        $error=0;

        $this->last_status = $last_status;
        $this->checkParameters();

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " last_status = ".(isset($this->last_status)?"'".$this->db->escape($this->last_status)."'":"''");
        $sql.= " WHERE rowid = ".$this->id;

        $this->db->begin();

        dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

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
}
