<?php
/*
 * Copyright (C) 2014-2015  Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/core/modules/printing/printipp.modules.php
 *      \ingroup    mailing
 *      \brief      File to provide printing with PrintIPP
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';

/**
 *   Class to provide printing with PrintIPP
 */
class printing_printipp extends PrintingDriver
{
    var $name='printipp';
    var $desc='PrintIPPDesc';
    var $picto='printer';
    var $active='PRINTING_PRINTIPP';
    var $conf=array();
    var $host;
    var $port;
    var $userid;    /* user login */
    var $user;
    var $password;
    var $error;
    var $errors = array();
    var $db;


    /**
     *  Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    function __construct($db)
    {
        global $conf;

        $this->db=$db;
        $this->host=$conf->global->PRINTIPP_HOST;
        $this->port=$conf->global->PRINTIPP_PORT;
        $this->user=$conf->global->PRINTIPP_USER;
        $this->password=$conf->global->PRINTIPP_PASSWORD;
        $this->conf[] = array('varname'=>'PRINTIPP_HOST', 'required'=>1, 'example'=>'localhost', 'type'=>'text');
        $this->conf[] = array('varname'=>'PRINTIPP_PORT', 'required'=>1, 'example'=>'631', 'type'=>'text');
        $this->conf[] = array('varname'=>'PRINTIPP_USER', 'required'=>0, 'example'=>'', 'type'=>'text', 'moreattributes'=>'autocomplete="off"');
        $this->conf[] = array('varname'=>'PRINTIPP_PASSWORD', 'required'=>0, 'example'=>'', 'type'=>'password', 'moreattributes'=>'autocomplete="off"');
        $this->conf[] = array('enabled'=>1, 'type'=>'submit');
    }

    /**
     *  Print selected file
     *
     * @param   string      $file       file
     * @param   string      $module     module
     * @param   string      $subdir     subdirectory of document like for expedition subdir is sendings
     *
     * @return  int                     0 if OK, >0 if KO
     */
    function print_file($file, $module, $subdir='')
    {
        global $conf, $user, $db;
        $error = 0;

        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';

        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/dolibarr_printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setJobName($file,true);
        $ipp->setUserName($this->userid);
        if (! empty($this->user)) $ipp->setAuthentication($this->user,$this->password);

        // select printer uri for module order, propal,...
        $sql = "SELECT rowid,printer_id,copy FROM ".MAIN_DB_PREFIX."printing WHERE module = '".$module."' AND driver = 'printipp' AND userid = ".$user->id;
        $result = $db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
            	dol_syslog("Found a default printer for user ".$user->id." = ".$obj->printer_id);
                $ipp->setPrinterURI($obj->printer_id);
            }
            else
            {
                if (! empty($conf->global->PRINTIPP_URI_DEFAULT))
                {
					dol_syslog("Will use default printer conf->global->PRINTIPP_URI_DEFAULT = ".$conf->global->PRINTIPP_URI_DEFAULT);
                    $ipp->setPrinterURI($conf->global->PRINTIPP_URI_DEFAULT);
                }
                else
				{
                    $this->errors[] = 'NoDefaultPrinterDefined';
                    $error++;
                    return $error;
                }
            }
        }
        else dol_print_error($db);

        // Set number of copy
        $ipp->setCopies($obj->copy);
        $fileprint=$conf->{$module}->dir_output;
        if ($subdir!='') $fileprint.='/'.$subdir;
        $fileprint.='/'.$file;
        $ipp->setData($fileprint);
        try {
            $ipp->printJob();
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $error++;
        }
        if ($error==0) $this->errors[] = 'PRINTIPP: Job added';

        return $error;
    }

    /**
     *  Return list of available printers
     *
     *  @return  int                     0 if OK, >0 if KO
     */
    function listAvailablePrinters()
    {
        global $bc, $conf, $langs;
        $error = 0;
        $var=true;

        $html = '<tr class="liste_titre">';
        $html.= '<td>'.$langs->trans('IPP_Uri').'</td>';
        $html.= '<td>'.$langs->trans('IPP_Name').'</td>';
        $html.= '<td>'.$langs->trans('IPP_State').'</td>';
        $html.= '<td>'.$langs->trans('IPP_State_reason').'</td>';
        $html.= '<td>'.$langs->trans('IPP_State_reason1').'</td>';
        $html.= '<td>'.$langs->trans('IPP_BW').'</td>';
        $html.= '<td>'.$langs->trans('IPP_Color').'</td>';
        //$html.= '<td>'.$langs->trans('IPP_Device').'</td>';
        $html.= '<td>'.$langs->trans('IPP_Media').'</td>';
        $html.= '<td>'.$langs->trans('IPP_Supported').'</td>';
        $html.= '<td align="center">'.$langs->trans("Select").'</td>';
        $html.= "</tr>\n";
        $list = $this->getlist_available_printers();
        $var = true;
        foreach ($list as $value)
        {
            $var=!$var;
            $printer_det = $this->get_printer_detail($value);
            $html.= "<tr ".$bc[$var].">";
            $html.= '<td>'.$value.'</td>';
            //$html.= '<td><pre>'.print_r($printer_det,true).'</pre></td>';
            $html.= '<td>'.$printer_det->printer_name->_value0.'</td>';
            $html.= '<td>'.$langs->trans('STATE_IPP_'.$printer_det->printer_state->_value0).'</td>';
            $html.= '<td>'.$langs->trans('STATE_IPP_'.$printer_det->printer_state_reasons->_value0).'</td>';
            $html.= '<td>'.(! empty($printer_det->printer_state_reasons->_value1)?$langs->trans('STATE_IPP_'.$printer_det->printer_state_reasons->_value1):'').'</td>';
            $html.= '<td>'.$langs->trans('IPP_COLOR_'.$printer_det->printer_type->_value2).'</td>';
            $html.= '<td>'.$langs->trans('IPP_COLOR_'.$printer_det->printer_type->_value3).'</td>';
            //$html.= '<td>'.$printer_det->device_uri->_value0.'</td>';
            $html.= '<td>'.$printer_det->media_default->_value0.'</td>';
            $html.= '<td>'.$langs->trans('MEDIA_IPP_'.$printer_det->media_type_supported->_value1).'</td>';
            // Defaut
            $html.= '<td align="center">';
            if ($conf->global->PRINTIPP_URI_DEFAULT == $value)
            {
                $html.= img_picto($langs->trans("Default"),'on');
            }
            else
			{
            	$html.= '<a href="'.$_SERVER["PHP_SELF"].'?action=setvalue&amp;mode=test&amp;varname=PRINTIPP_URI_DEFAULT&amp;driver=printipp&amp;value='.urlencode($value).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
          	}
			$html.= '</td>';
            $html.= '</tr>'."\n";
        }
        $this->resprint = $html;
        return $error;
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
        $ipp->setLog(DOL_DATA_ROOT.'/dolibarr_printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
        if (! empty($this->user)) $ipp->setAuthentication($this->user,$this->password);
        $ipp->getPrinters();
        return $ipp->available_printers;
    }

    /**
     *  Get printer detail
     *
     *  @param  string  $uri    URI
     *  @return array           List of attributes
     */
    function get_printer_detail($uri)
    {
        global $conf,$db;

        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/dolibarr_printipp.log','file',3); // logging very verbose
        $ipp->setHost($this->host);
        $ipp->setPort($this->port);
        $ipp->setUserName($this->userid);
        if (! empty($this->user)) $ipp->setAuthentication($this->user,$this->password);
        $ipp->setPrinterURI($uri);
        $ipp->getPrinterAttributes();
        return $ipp->printer_attributes;
    }

    /**
     *  List jobs print
     *
     *  @param   string      $module     module
     *
     *  @return  int                     0 if OK, >0 if KO
     */
    function list_jobs($module)
    {
        global $conf, $db, $bc;
        $error = 0;
        $html = '';
        include_once DOL_DOCUMENT_ROOT.'/includes/printipp/CupsPrintIPP.php';
        $ipp = new CupsPrintIPP();
        $ipp->setLog(DOL_DATA_ROOT.'/dolibarr_printipp.log','file',3); // logging very verbose
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
            $ipp->getJobs(false,0,'completed',false);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $error++;
        }
        $html .= '<table width="100%" class="noborder">';
        $html .= '<tr class="liste_titre">';
        $html .= '<td>Id</td>';
        $html .= '<td>Owner</td>';
        $html .= '<td>Printer</td>';
        $html .= '<td>File</td>';
        $html .= '<td>Status</td>';
        $html .= '<td>Cancel</td>';
        $html .= '</tr>'."\n";
        $jobs = $ipp->jobs_attributes;
        $var = True;
        //$html .= '<pre>'.print_r($jobs,true).'</pre>';
        foreach ($jobs as $value )
        {
            $var = !$var;
            $html .= '<tr '.$bc[$var].'>';
            $html .= '<td>'.$value->job_id->_value0.'</td>';
            $html .= '<td>'.$value->job_originating_user_name->_value0.'</td>';
            $html .= '<td>'.$value->printer_uri->_value0.'</td>';
            $html .= '<td>'.$value->job_name->_value0.'</td>';
            $html .= '<td>'.$value->job_state->_value0.'</td>';
            $html .= '<td>'.$value->job_uri->_value0.'</td>';
            $html .= '</tr>';
        }
        $html .= "</table>";
        $this->resprint = $html;
        return $error;
    }

}
