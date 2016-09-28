<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Ion Agorria          <ion@agorria.com>
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
 *       \file       htdocs/webservices/server_resource.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once '../master.inc.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';


dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

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
$server->configureWSDL('WebServicesDolibarrOther',$ns);
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
    'resource',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref' => array('name'=>'ref','type'=>'xsd:string'),
        'description' => array('name'=>'description','type'=>'xsd:string'),
        'type' => array('name'=>'type','type'=>'xsd:string'),
        'duration_value' => array('name'=>'duration_value','type'=>'xsd:string'),
        'duration_unit' => array('name'=>'duration_unit','type'=>'xsd:string'),
        'available' => array('name'=>'available','type'=>'xsd:string'),
        'management_type' => array('name'=>'management_type','type'=>'xsd:string'),
        'type_label' => array('name'=>'type_label','type'=>'xsd:string'),
    )
);

$server->wsdl->addComplexType(
    'resourceplacement',
    'complexType',
    'struct',
    'all',
    '',
    array(
        'id' => array('name'=>'id','type'=>'xsd:string'),
        'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
        'soc_id' => array('name'=>'soc_id','type'=>'xsd:string'),
        'resource_id' => array('name'=>'resource_id','type'=>'xsd:string'),
        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
        'date_start' => array('name'=>'date_start','type'=>'xsd:dateTime'),
        'date_end' => array('name'=>'date_end','type'=>'xsd:dateTime'),
        'name_client' => array('name'=>'name_client','type_label'=>'xsd:string'),
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
    'getResource',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','resource'=>'tns:resource'),
    $ns,
    $ns.'#getResource',
    $styledoc,
    $styleuse,
    'WS to get resource'
);

// Register WSDL
$server->register(
    'getStatus',
    // Entry values
    array(
        'authentication'=>'tns:authentication',
        'id'=>'xsd:string',
        'date_start'=>'xsd:dateTime',
        'date_end'=>'xsd:dateTime',
        'booker_id'=>'xsd:string',
        'booker_type'=>'xsd:string',
    ),
    // Exit values
    array('result'=>'tns:result','status'=>'xsd:string'),
    $ns,
    $ns.'#getStatus',
    $styledoc,
    $styleuse,
    'WS to get resource status'
);


// Register WSDL
$server->register(
    'createResourcePlacement',
    // Entry values
    array('authentication'=>'tns:authentication','resourceplacement'=>'tns:resourceplacement'),
    // Exit values
    array('result'=>'tns:result','id'=>'xsd:string'),
    $ns,
    $ns.'#createResourcePlacement',
    $styledoc,
    $styleuse,
    'WS to create a resource placement'
);

// Full methods code
/**
 * Get resource
 *
* @param   array    $authentication  Array of authentication information
* @param   int      $id              Id of object
* @param   string   $ref             Ref of object
 * @return  mixed
 */
function getResource($authentication, $id=0, $ref='')
{
    global $db,$conf;

    dol_syslog("Function: getResource login=".$authentication['login']." id=".$id." ref=".$ref);

    // Init and check authentication
    if ($authentication['entity']) $conf->entity=$authentication['entity'];
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

    // Check parameters
    if (! $error && (($id && $ref)))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id and ref can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->resource->read)
        {
            $resource=new Dolresource($db);
            $result=$resource->fetch($id, $ref);

            if ($result > 0)
            {
                // Create
                $objectresp = array(
                    'result'=>array('result_code'=>'OK', 'result_label'=>''),
                    'resource'=> array(
                        'id' => $resource->id,
                        'ref' => $resource->ref,
                        'description' => $resource->description,
                        'type' => $resource->fk_code_type_resource,
                        'duration_value' => $resource->duration_value,
                        'duration_unit' => $resource->duration_unit,
                        'available' => $resource->available,
                        'management_type' => $resource->management_type,
                        'type_label' => $resource->type_label,
                    )
                );
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref;
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

/**
 * Returns state in specified date range
 *
 * @param   array   $authentication Array of authentication information
 * @param   int     $id             Id of object
 * @param   int     $date_start     Start date
 * @param   int     $date_end       End date
 * @param   int     $booker_id      Booker id
 * @param   string  $booker_type    Booker type
 * @return  mixed
 */
function getStatus($authentication, $id, $date_start, $date_end, $booker_id = 0, $booker_type = '')
{
    global $db,$conf;

    dol_syslog("Function: getStatus login=".$authentication['login']." id=".$id);
    dol_syslog("date_start=".$date_start." date_end=".$date_end." booker_id=".$booker_id." booker_type".$booker_type);

    // Init and check authentication
    if ($authentication['entity']) $conf->entity=$authentication['entity'];
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

    // Check parameters
    if (! $error && ! $id) { $error++; $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id is mandatory."; }
    if (! $error && ! $date_start) { $error++; $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter date_start is mandatory."; }
    if (! $error && ! $date_end) { $error++; $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter date_end is mandatory."; }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->resource->read)
        {
            $resource=new Dolresource($db);
            $result=$resource->fetch($id);

            if ($result > 0)
            {
                $date_start = dol_stringtotime($date_start,1);
                $date_end = dol_stringtotime($date_end,1);
                $result=$resource->getStatus($date_start, $date_end, $booker_id, $booker_type);

                // Create
                $objectresp = array(
                    'result'=>array('result_code'=>'OK', 'result_label'=>''),
                    'status'=>$result
                );
            }
            else
            {
                $error++;
                $errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
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
        $objectresp = array(
            'result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel),
        );
    }

    return $objectresp;
}

/**
 * Create a resource placement
 *
 * @param   array               $authentication     Array of authentication information
 * @param   ResourcePlacement   $placement          Resource placement
 * @return  array               Array result
 */
function createResourcePlacement($authentication, $placement)
{
    require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceplacement.class.php';
    global $db,$conf;

    dol_syslog("Function: createResourcePlacement login=".$authentication['login']);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

    // Check parameters
    $soc_id = $placement['soc_id'];
    $resource_id = $placement['resource_id'];
    if (! $error && empty($soc_id)) { $error++; $errorcode='KO'; $errorlabel="Thirdparty id is mandatory."; }
    if (! $error && empty($soc_id)) { $error++; $errorcode='KO'; $errorlabel="Resource id is mandatory."; }
    if (! $error && empty($placement['date_start'])) { $error++; $errorcode='KO'; $errorlabel="Start date is mandatory."; }
    if (! $error && empty($placement['date_end'])) { $error++; $errorcode='KO'; $errorlabel="End date is mandatory."; }

    if (! $error)
    {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($db);
        $result = $soc->fetch($soc_id);
        if ($result === 0)
        {
            $error++;
            $errorcode='NOT_FOUND'; $errorlabel='Thirdparty not found for id='.$soc_id;
        }
        else if ($result < 0)
        {
            $error++;
            $errorcode='KO'; $errorlabel=$soc->error;
        }
    }

    if (! $error)
    {
        $res = new Dolresource($db);
        $result = $res->fetch($resource_id);
        if ($result === 0)
        {
            $error++;
            $errorcode='NOT_FOUND'; $errorlabel='Resource not found for id='.$resource_id;
        }
        else if ($result < 0)
        {
            $error++;
            $errorcode='KO'; $errorlabel=$res->error;
        }
    }

    if (! $error)
    {
        $newobject=new ResourcePlacement($db);
        $newobject->ref_client=$placement['ref_client'];
        $newobject->fk_soc=$soc_id;
        $newobject->fk_resource=$resource_id;
        $newobject->date_creation=dol_now();
        $newobject->date_start=dol_stringtotime($placement['date_start'], 1);
        $newobject->date_end=dol_stringtotime($placement['date_end'], 1);

        $db->begin();

        $result=$newobject->create($fuser);

        if ($result <= 0)
        {
            $error++;
        }

        if (! $error)
        {
            $db->commit();
            $objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'id'=>$newobject->id);
        }
        else
        {
            $db->rollback();
            $error++;
            $errorcode='KO';
            $errorlabel=$newobject->error;
        }
    }

    if ($error)
    {
        $objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
    }

    return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
