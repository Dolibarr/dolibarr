<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/webservices/server_user.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 *       \version    $Id: server_user.php,v 1.7 2010/12/19 11:49:37 eldy Exp $
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP
require_once(DOL_DOCUMENT_ROOT."/core/lib/ws.lib.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");


dol_syslog("Call User webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrUser',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
    'authentication',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
    	'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
    	'login' => array('name'=>'login','type'=>'xsd:string'),
    	'password' => array('name'=>'password','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
    )
);

// Define WSDL Return object
$server->wsdl->addComplexType(
    'result',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
        'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
    )
);

// Define other specific objects
$server->wsdl->addComplexType(
    'user',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'element' => array('name'=>'element','type'=>'xsd:string'),
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'lastname' => array('name'=>'lastname','type'=>'xsd:string'),
        'firstname' => array('name'=>'firstname','type'=>'xsd:string'),
        'note' => array('name'=>'note','type'=>'xsd:string'),
        'email' => array('name'=>'email','type'=>'xsd:string'),
        'signature' => array('name'=>'signature','type'=>'xsd:string'),
        'office_phone' => array('name'=>'office_phone','type'=>'xsd:string'),
        'office_fax' => array('name'=>'office_fax','type'=>'xsd:string'),
        'user_mobile' => array('name'=>'user_mobile','type'=>'xsd:string'),
        'admin' => array('name'=>'admin','type'=>'xsd:string'),
        'login' => array('name'=>'login','type'=>'xsd:string'),
        'entity' => array('name'=>'entity','type'=>'xsd:string'),
        'pass_indatabase' => array('name'=>'pass_indatabase','type'=>'xsd:string'),
        'pass_indatabase_crypted' => array('name'=>'pass_indatabase_crypted','type'=>'xsd:string'),
        'datec' => array('name'=>'datec','type'=>'xsd:dateTime'),
        'datem' => array('name'=>'datem','type'=>'xsd:dateTime'),
        'societe_id' => array('name'=>'societe_id','type'=>'xsd:string'),
        'fk_member' => array('name'=>'fk_member','type'=>'xsd:string'),
        'datelastlogin' => array('name'=>'datelastlogin','type'=>'xsd:dateTime'),
        'datepreviouslogin' => array('name'=>'datepreviouslogin','type'=>'xsd:dateTime'),
        'statut' => array('name'=>'statut','type'=>'xsd:string'),
        'photo' => array('name'=>'photo','type'=>'xsd:string'),
        'lang' => array('name'=>'lang','type'=>'xsd:string'),
        'entrepots' => array('name'=>'entrepots','type'=>'xsd:string'),
        //'rights' => array('name'=>'rights','type'=>'xsd:string'),
        'canvas' => array('name'=>'canvas','type'=>'xsd:string')
    )
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.


// Register WSDL
$server->register(
    'getUser',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','user'=>'tns:user'),
    $ns,
    $ns.'#getUser',
    $styledoc,
    $styleuse,
    'WS to get user'
);




/**
 * Get produt or service
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @param	string		$ref				Ref of object
 * @param	ref_ext		$ref_ext			Ref external of object
 * @return	mixed
 */
function getUser($authentication,$id,$ref='',$ref_ext='')
{
    global $db,$conf,$langs;

    dol_syslog("Function: getUser login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
    // Check parameters
    if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->user->user->lire)
        {
            $user=new User($db);
            $result=$user->fetch($id,$ref,$ref_ext);
            if ($result > 0)
            {
                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'user'=>array(
'id' => $user->id,
'lastname' => $user->lastname,
'firstname' => $user->firstname,
'note' => $user->note,
'email' => $user->email,
'signature' => $user->signature,
'office_phone' => $user->office_phone,
'office_fax' => $user->office_fax,
'user_mobile' => $user->user_mobile,
'admin' => $user->admin,
'login' => $user->login,
'entity' => $user->entity,
'pass_indatabase' => $user->pass_indatabase,
'pass_indatabase_crypted' => $user->pass_indatabase_crypted,
'datec' => dol_print_date($user->datec,'dayhourrfc'),
'datem' => dol_print_date($user->datem,'dayhourrfc'),
'societe_id' => $user->societe_id,
'fk_member' => $user->fk_member,
'webcal_login' => $user->webcal_login,
'phenix_login' => $user->phenix_login,
'phenix_pass' => $user->phenix_pass,
'phenix_pass_crypted' => $user->phenix_pass_crypted,
'datelastlogin' => dol_print_date($user->datelastlogin,'dayhourrfc'),
'datepreviouslogin' => dol_print_date($user->datepreviouslogin,'dayhourrfc'),
'statut' => $user->statut,
'photo' => $user->photo,
'lang' => $user->lang,
'entrepots' => $user->entrepots,
//'rights' => $user->rights,
'canvas' => $user->canvas
                    )
                );
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
            }
        }
        else
        {
            $error++;
            $errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}



// Return the results.
$server->service($HTTP_RAW_POST_DATA);

?>
