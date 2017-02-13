<?php
/*
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
 * or see http://www.gnu.org/
 */

/**
 *  \file           htdocs/core/class/dolprintipp.class.php
 *  \brief          List jobs printed with driver printipp
 */

/**
 * Class to manage printIPP
 */
class dolprintIPP
{
    var $host;
    var $port;
    var $userid;    /* user login */
    var $user;
    var $password;
    var $error;
    var $db;



    /**
     * Constructor
     *
     * @param   DoliDB      $db         database
     * @param   string      $host       host of Cups
     * @param   string      $port       port
     * @param   string      $userid     userid
     * @param   string      $user       user
     * @param   string      $password   password
     */
    function __construct($db,$host,$port,$userid,$user,$password)
    {
        $this->db=$db;
        $this->host=$host;
        $this->port=$port;
        $this->userid=$userid;
        $this->user=$user;
        $this->password=$password;
    }


    /**
     *  List jobs print
     *
     * @param   string      $module     module
     *
     *  @return void
     */
    function list_jobs($module)
    {
        global $conf, $db, $bc, $langs;
        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
        if (! empty($this->user)) $ipp->setAuthentication($this->user,$this->password);
        // select printer uri for module order, propal,...
        $sql = 'SELECT rowid,printer_uri,printer_name FROM '.MAIN_DB_PREFIX.'printer_ipp WHERE module="'.$module.'"';
        $result = $this->db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
                $ipp->setPrinterURI($obj->printer_uri);
            }
            else
            {
                // All printers
                $ipp->setPrinterURI("ipp://localhost:631/printers/");
            }
        }
        // Getting Jobs
        try {
        	$ipp->getJobs(false,0,'completed',false);			// May return errors if setup not correct
        }
        catch(Exception $e)
        {
            setEventMessage('[printipp] '.$langs->trans('CoreErrorMessage'), 'errors');
            dol_syslog($e->getMessage(), LOG_ERR);
        }

        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre">';
        print "<td>Id</td>";
        print "<td>Owner</td>";
        print "<td>Printer</td>";
        print "<td>File</td>";
        print "<td>Status</td>";
        print "<td>Cancel</td>";
        print "</tr>\n";
        $jobs = $ipp->jobs_attributes;
        $var = true;
        //print '<pre>'.print_r($jobs,true).'</pre>';
        if (is_array($jobs))
        {
	        foreach ($jobs as $value)
	        {
	            $var=!$var;
	            print "<tr ".$bc[$var].">";
	            print '<td>'.$value->job_id->_value0.'</td>';
	            print '<td>'.$value->job_originating_user_name->_value0.'</td>';
	            print '<td>'.$value->printer_uri->_value0.'</td>';
	            print '<td>'.$value->job_name->_value0.'</td>';
	            print '<td>'.$value->job_state->_value0.'</td>';
	            print '<td>'.$value->job_uri->_value0.'</td>';
	            print '</tr>';
	        }
        }
        print "</table>";
    }

}
