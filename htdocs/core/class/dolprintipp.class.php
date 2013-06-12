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
 *  \brief          A set of functions for using printIPP
 */

/**
 * Class to manage printIPP
 */
class dolprintIPP
{
    var $host;
    var $port;
    var $userid;
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
     * @return  printIPP
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
     *  Return list of available printers
     *
     *  @return array                list of printers
     */
    function getlist_available_printers()
    {
        global $conf,$db;
        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
        //$ipp->setAuthentication($this->user,$this->password);
        $ipp->getPrinters();
        return $ipp->available_printers;
    }

    /**
     *  Print selected file
     *  
     * @param   string      $file       file
     * @param   string      $module     module
     *
     *  @return void
     */
    function print_file($file,$module)
    {
        global $conf,$db;
        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setJobName($file,true);
        $ipp->setUserName($this->userid);
        //$ipp->setAuthentication($this->user,$this->password);
        // select printer uri for module order, propal,...
        $sql = 'SELECT rowid,printer_uri,copy FROM '.MAIN_DB_PREFIX.'printer_ipp WHERE module="'.$module.'"';
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
                $ipp->setPrinterURI($conf->global->PRINTIPP_URI_DEFAULT);
            }
        }
        // Set number of copy
        $ipp->setCopies($obj->copy);
        $ipp->setData(DOL_DATA_ROOT.'/'.$module.'/'.$file);
        $ipp->printJob();
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
        global $conf,$db;
        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
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
        $ipp->getJobs(false,0,'completed',false);
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
        $var = True;
        //print '<pre>'.print_r($jobs,true).'</pre>';
        foreach ($jobs as $value )
        {
            $var=!$var;
            print "<tr $bc[$var]>";
            print '<td>'.$value->job_id->_value0.'</td>';
            print '<td>'.$value->job_originating_user_name->_value0.'</td>';
            print '<td>'.$value->printer_uri->_value0.'</td>';
            print '<td>'.$value->job_name->_value0.'</td>';
            print '<td>'.$value->job_state->_value0.'</td>';
            print '<td>'.$value->job_uri->_value0.'</td>';
            print '</tr>';
        }
        print "</table>";
    }

    /**
     *  Get printer detail
     *
     */
    function get_printer_detail($uri)
    {
        global $conf,$db;

        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
        $ipp->setPrinterURI($uri);
        $ipp->getPrinterAttributes();
        return $ipp->printer_attributes;
    }
}
?>
