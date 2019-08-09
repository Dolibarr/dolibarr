<?php
/* Copyright (C) 2006-2016 	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012	 	Florian Henry			<florian.henry@open-concept.pro>
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
 *
 * Path to WSDL is: http://localhost/dolibarr/webservices/server_actioncomm.php?wsdl
 */

/**
 *       \file       htdocs/webservices/server_actioncomm.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK", '1');

require "../master.inc.php";
require_once NUSOAP_PATH.'/nusoap.php';		// Include SOAP
require_once DOL_DOCUMENT_ROOT."/core/lib/ws.lib.php";

require_once DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_syslog("Call ActionComm webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
    $langs->load("admin");
    dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
    print $langs->trans("WarningModuleNotActive", 'WebServices').'.<br><br>';
    print $langs->trans("ToActivateModule");
    exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrActionComm', $ns);
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


$actioncomm_fields= array(
    'id' => array('name'=>'id','type'=>'xsd:string'),
	'ref' => array('name'=>'ref','type'=>'xsd:string'),
	'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
	'type_id' => array('name'=>'type_id','type'=>'xsd:string'),
	'type_code' => array('name'=>'type_code','type'=>'xsd:string'),
	'type' => array('name'=>'type','type'=>'xsd:string'),
	'label' => array('name'=>'label','type'=>'xsd:string'),
	'datep' => array('name'=>'datep','type'=>'xsd:dateTime'),
	'datef' => array('name'=>'datef','type'=>'xsd:dateTime'),
	'datec' => array('name'=>'datec','type'=>'xsd:dateTime'),
	'datem' => array('name'=>'datem','type'=>'xsd:dateTime'),
	'note' => array('name'=>'note','type'=>'xsd:string'),
	'percentage' => array('name'=>'percentage','type'=>'xsd:string'),
	'author' => array('name'=>'author','type'=>'xsd:string'),
	'usermod' => array('name'=>'usermod','type'=>'xsd:string'),
	'userownerid' => array('name'=>'userownerid','type'=>'xsd:string'),
	'priority' => array('name'=>'priority','type'=>'xsd:string'),
	'fulldayevent' => array('name'=>'fulldayevent','type'=>'xsd:string'),
	'location' => array('name'=>'location','type'=>'xsd:string'),
	'socid' => array('name'=>'socid','type'=>'xsd:string'),
	'contactid' => array('name'=>'contactid','type'=>'xsd:string'),
	'projectid' => array('name'=>'projectid','type'=>'xsd:string'),
	'fk_element' => array('name'=>'fk_element','type'=>'xsd:string'),
	'elementtype' => array('name'=>'elementtype','type'=>'xsd:string'));

//Retreive all extrafield for actioncomm
// fetch optionals attributes and labels
$extrafields=new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label('actioncomm', true);
$extrafield_array=null;
if (is_array($extrafields) && count($extrafields)>0) {
	$extrafield_array = array();
}
foreach($extrafields->attribute_label as $key=>$label)
{
	$type =$extrafields->attribute_type[$key];
	if ($type=='date' || $type=='datetime') {$type='xsd:dateTime';}
	else {$type='xsd:string';}

	$extrafield_array['options_'.$key]=array('name'=>'options_'.$key,'type'=>$type);
}

if (is_array($extrafield_array)) $actioncomm_fields=array_merge($actioncomm_fields, $extrafield_array);

// Define other specific objects
$server->wsdl->addComplexType(
    'actioncomm',
    'complexType',
    'struct',
    'all',
    '',
	$actioncomm_fields
);


$server->wsdl->addComplexType(
	'actioncommtype',
	'complexType',
	'array',
	'sequence',
	'',
	array(
	'code' => array('name'=>'code','type'=>'xsd:string'),
	'libelle' => array('name'=>'libelle','type'=>'xsd:string')
	)
);

$server->wsdl->addComplexType(
	'actioncommtypes',
	'complexType',
	'array',
	'sequence',
	'',
	 array(
        'actioncommtype' => array(
            'name' => 'actioncommtype',
            'type' => 'tns:actioncommtype',
            'minOccurs' => '0',
            'maxOccurs' => 'unbounded'
        )
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
	'getListActionCommType',
	// Entry values
	array('authentication'=>'tns:authentication'),
	// Exit values
	array('result'=>'tns:result','actioncommtypes'=>'tns:actioncommtypes'),
	$ns,
	$ns.'#getListActionCommType',
	$styledoc,
	$styleuse,
	'WS to get actioncommType'
);

// Register WSDL
$server->register(
    'getActionComm',
    // Entry values
    array('authentication'=>'tns:authentication','id'=>'xsd:string'),
    // Exit values
    array('result'=>'tns:result','actioncomm'=>'tns:actioncomm'),
    $ns,
    $ns.'#getActionComm',
    $styledoc,
    $styleuse,
    'WS to get actioncomm'
);

// Register WSDL
$server->register(
	'createActionComm',
	// Entry values
	array('authentication'=>'tns:authentication','actioncomm'=>'tns:actioncomm'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#createActionComm',
	$styledoc,
	$styleuse,
	'WS to create a actioncomm'
);

// Register WSDL
$server->register(
	'updateActionComm',
	// Entry values
	array('authentication'=>'tns:authentication','actioncomm'=>'tns:actioncomm'),
	// Exit values
	array('result'=>'tns:result','id'=>'xsd:string'),
	$ns,
	$ns.'#updateActionComm',
	$styledoc,
	$styleuse,
	'WS to update a actioncomm'
);




/**
 * Get ActionComm
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @return	mixed
 */
function getActionComm($authentication, $id)
{
    global $db,$conf,$langs;

    dol_syslog("Function: getActionComm login=".$authentication['login']." id=".$id);

    if ($authentication['entity']) $conf->entity=$authentication['entity'];

    // Init and check authentication
    $objectresp=array();
    $errorcode='';$errorlabel='';
    $error=0;
    $fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);
    // Check parameters
    if ($error || (! $id))
    {
        $error++;
        $errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
    }

    if (! $error)
    {
        $fuser->getrights();

        if ($fuser->rights->agenda->allactions->read)
        {
            $actioncomm=new ActionComm($db);
            $result=$actioncomm->fetch($id);
            if ($result > 0)
            {
            	$actioncomm_result_fields=array(
						'id' => $actioncomm->id,
						'ref'=> $actioncomm->ref,
			        	'ref_ext'=> $actioncomm->ref_ext,
			        	'type_id'=> $actioncomm->type_id,
			        	'type_code'=> $actioncomm->type_code,
			        	'type'=> $actioncomm->type,
			        	'label'=> $actioncomm->label,
			        	'datep'=> dol_print_date($actioncomm->datep, 'dayhourrfc'),
			        	'datef'=> dol_print_date($actioncomm->datef, 'dayhourrfc'),
			        	'datec'=> dol_print_date($actioncomm->datec, 'dayhourrfc'),
			        	'datem'=> dol_print_date($actioncomm->datem, 'dayhourrfc'),
			        	'note'=> $actioncomm->note,
			        	'percentage'=> $actioncomm->percentage,
			        	'author'=> $actioncomm->authorid,
			        	'usermod'=> $actioncomm->usermodid,
			        	'userownerid'=> $actioncomm->userownerid,
			        	'priority'=> $actioncomm->priority,
			        	'fulldayevent'=> $actioncomm->fulldayevent,
			        	'location'=> $actioncomm->location,
			        	'socid'=> $actioncomm->socid,
			        	'contactid'=> $actioncomm->contactid,
			        	'projectid'=> $actioncomm->fk_project,
			        	'fk_element'=> $actioncomm->fk_element,
			        	'elementtype'=> $actioncomm->elementtype
            	);

	        	// Retreive all extrafield for actioncomm
	        	// fetch optionals attributes and labels
	        	$extrafields=new ExtraFields($db);
	        	$extralabels=$extrafields->fetch_name_optionals_label('actioncomm', true);
	        	//Get extrafield values
	        	$actioncomm->fetch_optionals();

	        	foreach($extrafields->attribute_label as $key=>$label)
	        	{
	        		$actioncomm_result_fields=array_merge($actioncomm_result_fields, array('options_'.$key => $actioncomm->array_options['options_'.$key]));
	        	}

                // Create
                $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'actioncomm'=>$actioncomm_result_fields);
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


/**
 * Get getListActionCommType
 *
 * @param	array		$authentication		Array of authentication information
 * @return	mixed
 */
function getListActionCommType($authentication)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getListActionCommType login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->agenda->myactions->read)
		{
			$cactioncomm=new CActionComm($db);
			$result=$cactioncomm->liste_array('', 'code');
			if ($result > 0)
			{
				$resultarray=array();
				foreach($cactioncomm->liste_array as $code=>$libeller) {
					$resultarray[]=array('code'=>$code,'libelle'=>$libeller);
				}

				 $objectresp = array(
			    	'result'=>array('result_code'=>'OK', 'result_label'=>''),
			        'actioncommtypes'=>$resultarray);
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


/**
 * Create ActionComm
 *
 * @param	array		$authentication		Array of authentication information
 * @param	ActionComm	$actioncomm		    $actioncomm
 * @return	array							Array result
 */
function createActionComm($authentication, $actioncomm)
{
	global $db,$conf,$langs;

	$now=dol_now();

	dol_syslog("Function: createActionComm login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);

	if (! $error)
	{
		$newobject=new ActionComm($db);

		$newobject->datep=$actioncomm['datep'];
		$newobject->datef=$actioncomm['datef'];
		$newobject->type_code=$actioncomm['type_code'];
		$newobject->socid=$actioncomm['socid'];
		$newobject->fk_project=$actioncomm['projectid'];
		$newobject->note=$actioncomm['note'];
		$newobject->contactid=$actioncomm['contactid'];
		$newobject->userownerid=$actioncomm['userownerid'];
		$newobject->label=$actioncomm['label'];
		$newobject->percentage=$actioncomm['percentage'];
		$newobject->priority=$actioncomm['priority'];
		$newobject->fulldayevent=$actioncomm['fulldayevent'];
		$newobject->location=$actioncomm['location'];
		$newobject->fk_element=$actioncomm['fk_element'];
		$newobject->elementtype=$actioncomm['elementtype'];

		//Retreive all extrafield for actioncomm
		// fetch optionals attributes and labels
		$extrafields=new ExtraFields($db);
		$extralabels=$extrafields->fetch_name_optionals_label('actioncomm', true);
		foreach($extrafields->attribute_label as $key=>$label)
		{
			$key='options_'.$key;
			$newobject->array_options[$key]=$actioncomm[$key];
		}

		$db->begin();

		$result=$newobject->create($fuser);
		if ($result <= 0)
		{
			$error++;
		}

		if (! $error)
		{
			$db->commit();
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id);
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

/**
 * Create ActionComm
 *
 * @param	array		$authentication		Array of authentication information
 * @param	ActionComm	$actioncomm		    $actioncomm
 * @return	array							Array result
 */
function updateActionComm($authentication, $actioncomm)
{
	global $db,$conf,$langs;

	$now=dol_now();

	dol_syslog("Function: updateActionComm login=".$authentication['login']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($actioncomm['id']))	{
		$error++; $errorcode='KO'; $errorlabel="Actioncomm id is mandatory.";
	}

	if (! $error)
	{
		$objectfound=false;

		$object=new ActionComm($db);
		$result=$object->fetch($actioncomm['id']);

		if (!empty($object->id)) {

			$objectfound=true;

			$object->datep=$actioncomm['datep'];
			$object->datef=$actioncomm['datef'];
			$object->type_code=$actioncomm['type_code'];
			$object->socid=$actioncomm['socid'];
			$object->contactid=$actioncomm['contactid'];
			$object->fk_project=$actioncomm['projectid'];
			$object->note=$actioncomm['note'];
			$object->userownerid=$actioncomm['userownerid'];
			$object->label=$actioncomm['label'];
			$object->percentage=$actioncomm['percentage'];
			$object->priority=$actioncomm['priority'];
			$object->fulldayevent=$actioncomm['fulldayevent'];
			$object->location=$actioncomm['location'];
			$object->fk_element=$actioncomm['fk_element'];
			$object->elementtype=$actioncomm['elementtype'];

			//Retreive all extrafield for actioncomm
			// fetch optionals attributes and labels
			$extrafields=new ExtraFields($db);
			$extralabels=$extrafields->fetch_name_optionals_label('actioncomm', true);
			foreach($extrafields->attribute_label as $key=>$label)
			{
				$key='options_'.$key;
				$object->array_options[$key]=$actioncomm[$key];
			}

			$db->begin();

			$result=$object->update($fuser);
			if ($result <= 0) {
				$error++;
			}
		}

		if ((! $error) && ($objectfound))
		{
			$db->commit();
			$objectresp=array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'id'=>$object->id
			);
		}
		elseif ($objectfound)
		{
			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$object->error;
		} else {
			$error++;
			$errorcode='NOT_FOUND';
			$errorlabel='Actioncomm id='.$actioncomm['id'].' cannot be found';
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
