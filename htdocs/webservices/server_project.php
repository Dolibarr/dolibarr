<?php
/* Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Ion Agorria          <ion@agorria.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/webservices/server_project.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 */

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1'); // Do not check anti CSRF attack test
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not check anti POST attack test
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1'); // Do not load ajax.lib.php library
}
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1'); // If this page is public (can be called outside logged session)
}
if (!defined("NOSESSION")) {
	define("NOSESSION", '1');
}

require '../main.inc.php';
require_once NUSOAP_PATH.'/nusoap.php'; // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (!getDolGlobalString('MAIN_MODULE_WEBSERVICES')) {
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive", 'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create associated types array, with each table
$listofreferent = array(
	'propal' => 'propal',
	'order' => 'commande',
	'invoice' => 'facture',
	'invoice_predefined' => 'facture_rec',
	'proposal_supplier' => 'commande_fournisseur',
	'order_supplier' => 'commande_fournisseur',
	'invoice_supplier' => 'facture_fourn',
	'contract' => 'contrat',
	'intervention' => 'fichinter',
	'trip' => 'deplacement',
	'expensereport' => 'expensereport_det',
	'donation' => 'don',
	'agenda' => 'actioncomm',
	'project_task' => 'projet_task',
);

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$ns = 'http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrOther', $ns);
$server->wsdl->schemaTargetNamespace = $ns;

// Define WSDL Authentication object
$server->wsdl->addComplexType(
	'authentication',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'dolibarrkey' => array('name'=>'dolibarrkey', 'type'=>'xsd:string'),
		'sourceapplication' => array('name'=>'sourceapplication', 'type'=>'xsd:string'),
		'login' => array('name'=>'login', 'type'=>'xsd:string'),
		'password' => array('name'=>'password', 'type'=>'xsd:string'),
		'entity' => array('name'=>'entity', 'type'=>'xsd:string'),
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
		'result_code' => array('name'=>'result_code', 'type'=>'xsd:string'),
		'result_label' => array('name'=>'result_label', 'type'=>'xsd:string'),
	)
);

// Define other specific objects
$server->wsdl->addComplexType(
	'element',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id', 'type'=>'xsd:int'),
		'user' => array('name'=>'user', 'type'=>'xsd:int'),
	)
);

$server->wsdl->addComplexType(
	'elementsArray',
	'complexType',
	'array',
	'sequence',
	'',
	array(
		'elements' => array(
			'name' => 'elementsArray',
			'type' => 'tns:element',
			'minOccurs' => '0',
			'maxOccurs' => 'unbounded'
		)
	)
);

$project_elements = array();
foreach ($listofreferent as $key => $label) {
	$project_elements[$key] = array('name'=>$key, 'type'=>'tns:elementsArray');
}
$server->wsdl->addComplexType(
	'elements',
	'complexType',
	'struct',
	'all',
	'',
	$project_elements
);

// Define project
$project_fields = array(
	'id' => array('name'=>'id', 'type'=>'xsd:string'),
	'ref' => array('name'=>'ref', 'type'=>'xsd:string'),
	'label' => array('name'=>'label', 'type'=>'xsd:string'),
	'thirdparty_id' => array('name'=>'thirdparty_id', 'type'=>'xsd:int'),
	'public' => array('name'=>'public', 'type'=>'xsd:int'),
	'status' => array('name'=>'status', 'type'=>'xsd:int'),
	'date_start' => array('name'=>'date_start', 'type'=>'xsd:date'),
	'date_end' => array('name'=>'date_end', 'type'=>'xsd:date'),
	'budget' => array('name'=>'budget', 'type'=>'xsd:int'),
	'description' => array('name'=>'description', 'type'=>'xsd:string'),
	'elements' => array('name'=>'elements', 'type'=>'tns:elements')
);

$elementtype = 'project';

//Retrieve all extrafield for thirdsparty
// fetch optionals attributes and labels
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($elementtype, true);
$extrafield_array = null;
if (is_array($extrafields->attributes) && $extrafields->attributes[$elementtype]['count'] > 0) {
	$extrafield_array = array();
}
if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
	foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
		//$value=$object->array_options["options_".$key];
		$type = $extrafields->attributes[$elementtype]['type'][$key];
		if ($type == 'date' || $type == 'datetime') {
			$type = 'xsd:dateTime';
		} else {
			$type = 'xsd:string';
		}
		$extrafield_array['options_'.$key] = array('name'=>'options_'.$key, 'type'=>$type);
	}
}
if (is_array($extrafield_array)) {
	$project_fields = array_merge($project_fields, $extrafield_array);
}

$server->wsdl->addComplexType(
	'project',
	'complexType',
	'struct',
	'all',
	'',
	$project_fields
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc = 'rpc'; // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse = 'encoded'; // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
	'createProject',
	// Entry values
	array('authentication'=>'tns:authentication', 'project'=>'tns:project'),
	// Exit values
	array('result'=>'tns:result', 'id'=>'xsd:string', 'ref'=>'xsd:string'),
	$ns,
	$ns.'#createProject',
	$styledoc,
	$styleuse,
	'WS to create project'
);

// Register WSDL
$server->register(
	'getProject',
	// Entry values
	array('authentication'=>'tns:authentication', 'id'=>'xsd:string', 'ref'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result', 'project'=>'tns:project'),
	$ns,
	$ns.'#getProject',
	$styledoc,
	$styleuse,
	'WS to get project'
);

// Full methods code
/**
 * Create project
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$project			Project info
 * @return	array							array of new order
 */
function createProject($authentication, $project)
{
	global $db, $conf;

	dol_syslog("Function: createProject login=".$authentication['login']);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (empty($project['ref'])) {
		$error++;
		$errorcode = 'KO';
		$errorlabel = "Name is mandatory.";
	}


	if (!$error) {
		$fuser->loadRights();

		if ($fuser->hasRight('projet', 'creer')) {
			$newobject = new Project($db);
			$newobject->ref = $project['ref'];
			$newobject->title = $project['label'];
			$newobject->socid = $project['thirdparty_id'];
			$newobject->public = $project['public'];
			$newobject->statut = $project['status'];
			$newobject->date_start = dol_stringtotime($project['date_start'], 'dayrfc');
			$newobject->date_end = dol_stringtotime($project['date_end'], 'dayrfc');
			$newobject->budget_amount = $project['budget'];
			$newobject->description = $project['description'];

			$elementtype = 'project';

			// Retrieve all extrafields for project
			// fetch optionals attributes and labels
			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label($elementtype, true);
			if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
				foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
					$key = 'options_'.$key;
					$newobject->array_options[$key] = $project[$key];
				}
			}

			$db->begin();

			$result = $newobject->create($fuser);
			if (!$error && $result > 0) {
				// Add myself as project leader
				$result = $newobject->add_contact($fuser->id, 'PROJECTLEADER', 'internal');
				if ($result < 0) {
					$error++;
				}
			} else {
				$error++;
			}

			if (!$error) {
				$db->commit();
				$objectresp = array('result'=>array('result_code'=>'OK', 'result_label'=>''), 'id'=>$newobject->id, 'ref'=>$newobject->ref);
			} else {
				$db->rollback();
				$error++;
				$errorcode = 'KO';
				$errorlabel = $newobject->error;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

/**
 * Get a project
 *
 * @param	array		$authentication		Array of authentication information
 * @param	string		$id		    		internal id
 * @param	string		$ref		    	internal reference
 * @return	array							Array result
 */
function getProject($authentication, $id = '', $ref = '')
{
	global $db, $conf;

	dol_syslog("Function: getProject login=".$authentication['login']." id=".$id." ref=".$ref);

	if ($authentication['entity']) {
		$conf->entity = $authentication['entity'];
	}

	// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (!$error && (($id && $ref))) {
		$error++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = "Parameter id and ref can't be both provided. You must choose one or other but not both.";
	}

	if (!$error) {
		$fuser->loadRights();

		if ($fuser->hasRight('projet', 'lire')) {
			$project = new Project($db);
			$result = $project->fetch($id, $ref);
			if ($result > 0) {
				$project_result_fields = array(
					'id' => $project->id,
					'ref' => $project->ref,
					'label' => $project->title,
					'thirdparty_id' => $project->socid,
					'public' => $project->public,
					'status' => $project->statut,
					'date_start' => $project->date_start ? dol_print_date($project->date_start, 'dayrfc') : '',
					'date_end' => $project->date_end ? dol_print_date($project->date_end, 'dayrfc') : '',
					'budget' => $project->budget_amount,
					'description' => $project->description,
				);

				$elementtype = 'project';

				//Retrieve all extrafields for project
				$extrafields = new ExtraFields($db);
				$extrafields->fetch_name_optionals_label($elementtype, true);

				//Get extrafield values
				if (isset($extrafields->attributes[$elementtype]['label']) && is_array($extrafields->attributes[$elementtype]['label']) && count($extrafields->attributes[$elementtype]['label'])) {
					$project->fetch_optionals();
					foreach ($extrafields->attributes[$elementtype]['label'] as $key => $label) {
						$project_result_fields = array_merge($project_result_fields, array('options_'.$key => $project->array_options['options_'.$key]));
					}
				}

				//Get linked elements
				global $listofreferent;
				$elements = array();
				foreach ($listofreferent as $key => $tablename) {
					$elements[$key] = array();
					$element_array = $project->get_element_list($key, $tablename);
					if (count($element_array) > 0 && is_array($element_array)) {
						foreach ($element_array as $element) {
							$tmp = explode('_', $element);
							$idofelement = count($tmp) > 0 ? $tmp[0] : "";
							$idofelementuser = count($tmp) > 1 ? $tmp[1] : "";
							$elements[$key][] = array('id' => $idofelement, 'user' => $idofelementuser);
						}
					}
				}
				$project_result_fields['elements'] = $elements;

				//Result
				$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'project'=>$project_result_fields
				);
			} else {
				$error++;
				$errorcode = 'NOT_FOUND';
				$errorlabel = 'Object not found for id='.$id.' nor ref='.$ref;
			}
		} else {
			$error++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
