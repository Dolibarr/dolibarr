<?php
/*
 * Copyright (C) 2014 Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/core/modules/printing/printgcp.modules.php
 *      \ingroup    printing
 *      \brief      File to provide printing with Google Cloud Print
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';

/**
 *     Class to provide printing with Google Cloud Print
 */
class printing_printgcp extends PrintingDriver
{
    var $name = 'printgcp';
    var $desc = 'PrintGCPDesc';
    var $picto = 'printer';
    var $active = 'PRINTING_PRINTGCP';
    var $conf = array();
    var $login = '';
    var $password = '';
    var $authtoken = '';
    var $db;

    const LOGIN_URL = 'https://www.google.com/accounts/ClientLogin';
    const PRINTERS_SEARCH_URL = 'https://www.google.com/cloudprint/interface/search';
    const PRINT_URL = 'https://www.google.com/cloudprint/interface/submit';

    /**
     *  Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    function __construct($db)
    {
        global $conf;

        $this->db = $db;
        $this->login = $conf->global->PRINTGCP_LOGIN;
        $this->password = $conf->global->PRINTGCP_PASSWORD;
        $this->authtoken = $conf->global->PRINTGCP_AUTHTOKEN;
        $this->conf[] = array('varname'=>'PRINTGCP_LOGIN', 'required'=>1, 'example'=>'user@gmail.com', 'type'=>'text');
        $this->conf[] = array('varname'=>'PRINTGCP_PASSWORD', 'required'=>1, 'example'=>'', 'type'=>'password');
    }

    /**
     *  Return list of available printers
     *
     *  @return string                html list of printers
     */
    function listAvailablePrinters()
    {
        global $bc, $conf, $langs;
        $langs->load('printing');
        $var=true;

        $html = '<tr class="liste_titre">';
        $html.= '<td>'.$langs->trans('GCP_Name').'</td>';
        $html.= '<td>'.$langs->trans('GCP_displayName').'</td>';
        $html.= '<td>'.$langs->trans('GCP_Id').'</td>';
        $html.= '<td>'.$langs->trans('GCP_OwnerName').'</td>';
        $html.= '<td>'.$langs->trans('GCP_State').'</td>';
        $html.= '<td>'.$langs->trans('GCP_connectionStatus').'</td>';
        $html.= '<td>'.$langs->trans('GCP_Type').'</td>';
        $html.= '<td align="center">'.$langs->trans("Select").'</td>';
        $html.= '</tr>'."\n";
        $list = $this->getlist_available_printers();
        //$html.= '<td><pre>'.print_r($list,true).'</pre></td>';
        $var = true;
        foreach ($list['available'] as $printer_det)
        {
            $var=!$var;
            $html.= "<tr ".$bc[$var].">";
            $html.= '<td>'.$printer_det['name'].'</td>';
            $html.= '<td>'.$printer_det['displayName'].'</td>';
            $html.= '<td>'.$printer_det['id'].'</td>';  // id to identify printer to use
            $html.= '<td>'.$printer_det['ownerName'].'</td>';
            $html.= '<td>'.$printer_det['status'].'</td>';
            $html.= '<td>'.$langs->trans('STATE_'.$printer_det['connectionStatus']).'</td>';
            $html.= '<td>'.$langs->trans('TYPE_'.$printer_det['type']).'</td>';
            // Defaut
            $html.= '<td align="center">';
            if ($conf->global->PRINTING_GCP_DEFAULT == $printer_det['id'])
            {
                $html.= img_picto($langs->trans("Default"),'on');
            }
            else
                $html.= '<a href="'.$_SERVER["PHP_SELF"].'?action=setvalue&amp;mode=test&amp;varname=PRINTING_GCP_DEFAULT&amp;driver=printgcp&amp;value='.urlencode($printer_det['id']).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
            $html.= '</td>';
            $html.= '</tr>'."\n";
        }

        return $html;
    }

    /**
     *  Return list of available printers
     *
     *  @return array                list of printers
     */
    function getlist_available_printers()
    {
        global $conf,$db;
        if ($this->authtoken=='') {
            $this->GoogleLogin();
        }
        $ret['available'] = $this->get_printer_detail();
        return $ret;
    }

    /**
     *  List of printers
     *
     *  @return array      list of printers
     */
    function get_printer_detail()
    {
        // Check if we have auth token
        if(empty($this->authtoken)) {
            // We don't have auth token so throw exception
            throw new Exception("Please first login to Google by calling loginToGoogle function");
        }
        // Prepare auth headers with auth token
        $authheaders = array("Authorization: GoogleLogin auth=".$this->authtoken,
                             "GData-Version: 3.0",
                            );
        // Make Http call to get printers added by user to Google Cloud Print
        $responsedata = $this->makeCurl(self::PRINTERS_SEARCH_URL,array(),$authheaders);
        $printers = json_decode($responsedata);
        // Check if we have printers?
        if(is_null($printers)) {
            // We dont have printers so return blank array
            return array();
        } else {
            // We have printers so returns printers as array
            return $this->parsePrinters($printers);
        }
    }

    /**
     *  Print selected file
     *
     * @param   string      $file       file
     * @param   string      $module     module
     * @param   string      $subdir     subdir for file
     * @return  string                  '' if OK, Error message if KO
     */
    function print_file($file, $module, $subdir='')
    {
        global $conf, $user, $db;
        if ($this->authtoken=='') {
            $this->GoogleLogin();
        }
        // si $module=commande_fournisseur alors $conf->fournisseur->commande->dir_output
        $fileprint=$conf->{$module}->dir_output;
        if ($subdir!='') $fileprint.='/'.$subdir;
        $fileprint.='/'.$file;
        // select printer uri for module order, propal,...
        $sql = "SELECT rowid, printer_id, copy FROM ".MAIN_DB_PREFIX."printing WHERE module='".$module."' AND driver='printgcp' AND userid=".$user->id;
        $result = $db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
                $printer_id=$obj->printer_id;
            }
            else
            {
                if (! empty($conf->global->PRINTING_GCP_DEFAULT))
                {
                    $printer_id=$conf->global->PRINTING_GCP_DEFAULT;
                }
                else
                {
                    return 'NoDefaultPrinterDefined';
                }
            }
        }
        else dol_print_error($db);

        $this->sendPrintToPrinter($printer_id, $file, $fileprint, 'application/pdf');
    }

    /**
     *  Sends document to the printer
     *
     *  @param  string      $printerid      Printer id returned by Google Cloud Print
     *  @param  string      $printjobtitle  Job Title
     *  @param  string      $filepath       File Path to be send to Google Cloud Print
     *  @param  string      $contenttype    File content type by example application/pdf, image/png
     *  @return array                       status array
     */
    public function sendPrintToPrinter($printerid,$printjobtitle,$filepath,$contenttype)
    {
        $errors=0;
        // Check auth token
        if(empty($this->authtoken)) {
            $errors++;
            setEventMessage('Please first login to Google', 'warning');
        }
        // Check if printer id
        if(empty($printerid)) {
            $errors++;
            setEventMessage('No provided printer ID', 'warning');
        }
        // Open the file which needs to be print
        $handle = fopen($filepath, "rb");
        if(!$handle) {
            $errors++;
            setEventMessage('Could not read the file.');
        }
        // Read file content
        $contents = fread($handle, filesize($filepath));
        fclose($handle);
        // Prepare post fields for sending print
        $post_fields = array('printerid' => $printerid,
                             'title' => $printjobtitle,
                             'contentTransferEncoding' => 'base64',
                             'content' => base64_encode($contents), // encode file content as base64
                             'contentType' => $contenttype
                            );
        // Prepare authorization headers
        $authheaders = array("Authorization: GoogleLogin auth=" . $this->authtoken);
        // Make http call for sending print Job
        $response = json_decode($this->makeCurl(self::PRINT_URL,$post_fields,$authheaders));
        // Has document been successfully sent?
        if($response->success=="1") {
            return array('status' =>true,'errorcode' =>'','errormessage'=>"");
        } else {
            return array('status' =>false,'errorcode' =>$response->errorCode,'errormessage'=>$response->message);
        }
    }


    /**
     *  Login into Google Account
     *
     *  @return boolean           true or false
     */
    function GoogleLogin()
    {
        global $db, $conf;
        // Prepare post fields required for the login
        $loginpostfields = array("accountType" => "HOSTED_OR_GOOGLE",
                                 "Email" => $this->login,
                                 "Passwd" => $this->password,
                                 "service" => "cloudprint",
                                 "source" => "GCP"
                                );
        // Get the Auth token
        $loginresponse = $this->makeCurl(self::LOGIN_URL,$loginpostfields);
        $token = $this->getAuthToken($loginresponse);
        if(! empty($token)&&!is_null($token)) {
            $this->authtoken = $token;
            $result=dolibarr_set_const($db, 'PRINTGCP_AUTHTOKEN', $token, 'chaine', 0, '', $conf->entity);
            return true;
        } else {
            return false;
        }

    }

    /**
     *  Parse json response and return printers array
     *
     *  @param  string    $jsonobj  Json response object
     *  @return array               return array of printers
     */
    private function parsePrinters($jsonobj)
    {
        $printers = array();
        if (isset($jsonobj->printers)) {
            foreach ($jsonobj->printers as $gcpprinter) {
                $printers[] = array('id' =>$gcpprinter->id,
                                    'name' =>$gcpprinter->name,
                                    'defaultDisplayName' =>$gcpprinter->defaultDisplayName,
                                    'displayName' =>$gcpprinter->displayName,
                                    'ownerId' =>$gcpprinter->ownerId,
                                    'ownerName' =>$gcpprinter->ownerName,
                                    'connectionStatus' =>$gcpprinter->connectionStatus,
                                    'status' =>$gcpprinter->status,
                                    'type' =>$gcpprinter->type
                                    );
            }
        }
        return $printers;
    }

    /**
     *  Parse data to get auth token
     *
     *  @param      string  $response   response from curl
     *  @return     string              token
     */
    private function getAuthToken($response)
    {
        // Search Auth tag
        preg_match("/Auth=([a-z0-9_-]+)/i", $response, $matches);
        $authtoken = @$matches[1];
        return $authtoken;
    }

    /**
     *  Make a curl request
     *
     *  @param  string  	$url            url to hit
     *  @param  array   	$postfields     array of post fields
     *  @param  string[]   	$headers        array of http headers
     *  @return string                   	response from curl
     */
    private function makeCurl($url, $postfields=array(), $headers=array())
    {
        // Curl Init
        $curl = curl_init($url);
        // Curl post request
        if(! empty($postfields)) {
            // As is HTTP post curl request so set post fields
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
        }
        // Curl request headers
        if(! empty($headers)) {
            // As curl requires header so set headers here
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // Execute the curl and return response
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


}
