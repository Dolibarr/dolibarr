<?php
/* Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/fileupload.php
 *       \brief      File to return Ajax response on common file upload. For large files, see flowjs-server.php
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fileupload.class.php';	// Class to upload common files
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$id = GETPOSTINT('fk_element');
$element = GETPOST('element', 'alpha');	// 'myobject' (myobject=mymodule) or 'myobject@mymodule' or 'myobject_mysubobject' (myobject=mymodule)
$elementupload = $element;

// Load object according to $id and $element
$object = fetchObjectByElement($id, $element);

$module = $object->module;
$element = $object->element;

$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

//print 'fileupload.php: '.$object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
	if (!empty($object->socid) && $socid != $object->socid) {
		httponly_accessforbidden("Access on object not allowed for this external user.");	// This includes the exit.
	}
}

$result = restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission, 'fk_soc', 'rowid', 0, 1);	// Call with mode return
if (!$result) {
	httponly_accessforbidden('Not allowed by restrictArea (module='.$object->module.' table_element='.$object->table_element.')');
}

// The call of restrictedArea() above only checks the permission to read the object. Storing a file into its documents also
// needs the permission to add a file, which is the one of the "Attached files" tab of the object.
$permissiontoadd = false;
switch ($object->element) {
	case 'user':	// A user can add a file on his own card with user->self->creer
		$permissiontoadd = (($user->id == $object->id && $user->hasRight('user', 'self', 'creer')) || ($user->id != $object->id && $user->hasRight('user', 'user', 'creer')));
		break;
	case 'product':	// Same element for a product and a service
		$permissiontoadd = ($object->type == Product::TYPE_SERVICE ? $user->hasRight('service', 'creer') : $user->hasRight('produit', 'creer'));
		break;
	case 'productlot':
		$permissiontoadd = $user->hasRight('produit', 'creer');
		break;
	case 'societe':
		$permissiontoadd = $user->hasRight('societe', 'creer');
		break;
	case 'contact':
		$permissiontoadd = $user->hasRight('societe', 'contact', 'creer');
		break;
	case 'project':
	case 'project_task':
	case 'conferenceorbooth':
		$permissiontoadd = $user->hasRight('projet', 'creer');
		break;
	case 'action':
		$permissiontoadd = ($user->hasRight('agenda', 'allactions', 'create') || (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create')));
		break;
	case 'facture':
	case 'payment':
		$permissiontoadd = $user->hasRight('facture', 'creer');
		break;
	case 'invoice_supplier':
	case 'payment_supplier':
		$permissiontoadd = ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer'));
		break;
	case 'order_supplier':
		$permissiontoadd = ($user->hasRight('fournisseur', 'commande', 'creer') || $user->hasRight('supplier_order', 'creer'));
		break;
	case 'reception':
		$permissiontoadd = (isModEnabled('reception') ? $user->hasRight('reception', 'creer') : $user->hasRight('fournisseur', 'commande', 'receptionner'));
		break;
	case 'shipping':
		$permissiontoadd = $user->hasRight('expedition', 'creer');
		break;
	case 'fichinter':
		$permissiontoadd = $user->hasRight('ficheinter', 'creer');
		break;
	case 'member':
		$permissiontoadd = $user->hasRight('adherent', 'creer');
		break;
	case 'bank_account':
	case 'payment_various':
		$permissiontoadd = $user->hasRight('banque', 'modifier');
		break;
	case 'chargesociales':
	case 'tva':
		$permissiontoadd = $user->hasRight('tax', 'charges', 'creer');
		break;
	case 'expensereport':	// Only for an expense report of the user or of one of his subordinates, or with writeall_advance
		$permissiontoadd = ($user->hasRight('expensereport', 'creer') && (in_array($object->fk_user_author, $user->getAllChildIds(1)) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expensereport', 'writeall_advance'))));
		break;
	case 'holiday':	// The leave request must also be readable: readall, or of the user or a subordinate, or to approve by him
		$permissiontoadd = ($user->hasRight('holiday', 'write') && ($user->hasRight('holiday', 'readall') || ($user->hasRight('holiday', 'read') && in_array($object->fk_user, $user->getAllChildIds(1))) || ($user->hasRight('holiday', 'approve') && $object->fk_validator == $user->id && !getDolGlobalString('HOLIDAY_CAN_APPROVE_ONLY_THE_SUBORDINATES'))));
		break;
	case 'salary':	// The salary must also be readable: readall, or of the user or a subordinate
		$permissiontoadd = ($user->hasRight('salaries', 'write') && ($user->hasRight('salaries', 'readall') || ($user->hasRight('salaries', 'read') && $object->fk_user > 0 && in_array($object->fk_user, $user->getAllChildIds(1)))));
		break;
	case 'job':
	case 'position':
	case 'skill':
		$permissiontoadd = $user->hasRight('hrm', 'all', 'write');
		break;
	case 'evaluation':
		$permissiontoadd = $user->hasRight('hrm', 'evaluation', 'write');
		break;
	case 'recruitmentjobposition':
	case 'recruitmentcandidature':
		$permissiontoadd = $user->hasRight('recruitment', 'recruitmentjobposition', 'write');
		break;
	case 'inventory':
		$permissiontoadd = $user->hasRight('stock', 'creer');
		break;
	case 'dolresource':
		$permissiontoadd = $user->hasRight('resource', 'write');
		break;
	case 'fiscalyear':
		$permissiontoadd = (empty($user->socid) && $user->hasRight('accounting', 'fiscalyear', 'write'));
		break;
	case 'accounting_transaction_template':
		$permissiontoadd = $user->hasRight('accounting', 'chartofaccount');
		break;
	case 'webhook_triggerhistory':
		$permissiontoadd = !empty($user->admin);
		break;
	case 'propal':
		$permissiontoadd = $user->hasRight('propal', 'creer');
		break;
	case 'commande':
		$permissiontoadd = $user->hasRight('commande', 'creer');
		break;
	case 'contrat':
		$permissiontoadd = $user->hasRight('contrat', 'creer');
		break;
	case 'supplier_proposal':
		$permissiontoadd = $user->hasRight('supplier_proposal', 'creer');
		break;
	case 'don':
		$permissiontoadd = $user->hasRight('don', 'creer');
		break;
	case 'ticket':
		$permissiontoadd = $user->hasRight('ticket', 'write');
		break;
	case 'asset':
		$permissiontoadd = $user->hasRight('asset', 'write');
		break;
	case 'loan':
		$permissiontoadd = $user->hasRight('loan', 'write');
		break;
	case 'partnership':
		$permissiontoadd = $user->hasRight('partnership', 'write');
		break;
	case 'mo':
		$permissiontoadd = $user->hasRight('mrp', 'write');
		break;
	case 'bom':
		$permissiontoadd = $user->hasRight('bom', 'write');
		break;
	case 'knowledgerecord':
		$permissiontoadd = $user->hasRight('knowledgemanagement', 'knowledgerecord', 'write');
		break;
	case 'workstation':
		$permissiontoadd = $user->hasRight('workstation', 'workstation', 'write');
		break;
	case 'stocktransfer':
		$permissiontoadd = $user->hasRight('stocktransfer', 'stocktransfer', 'write');
		break;
	case 'availabilities':
		$permissiontoadd = $user->hasRight('bookcal', 'availabilities', 'write');
		break;
	case 'calendar':
		$permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write');
		break;
	case 'intracommreport':
		$permissiontoadd = $user->hasRight('intracommreport', 'write');
		break;
	default:	// Permission on the object (module->object->write) or on the module (module->creer or module->write)
		$permissiontoadd = ($user->hasRight($object->module, $object->element, 'write') || $user->hasRight($object->module, $object->element, 'creer') || $user->hasRight($object->module, 'write') || $user->hasRight($object->module, 'creer'));
		break;
}
if (!$permissiontoadd) {
	dol_syslog("fileupload.php not allowed to add a file on object ".$object->element." with id ".$object->id, LOG_WARNING);
	httponly_accessforbidden('Not allowed');
}


/*
 * View
 */

top_httphead();

header('Pragma: no-cache');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: '.getRootURLFromURL(DOL_MAIN_URL_ROOT));
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD']) {
	case 'OPTIONS':
		break;
	/*case 'HEAD':
	case 'GET':
		$upload_handler->get();
		break;
	*/
	case 'POST':
		$upload_handler = new FileUpload(null, $id, $elementupload);

		/*if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
			$file = GETPOST('file');
			$upload_handler->delete($file);
		} else {*/
		$upload_handler->post();
		// Note: even if this return an error on 1 file in post(), we will return http code 200 because error must be managed by the caller (some files may be ok and some in error)
		//}
		break;
	/*case 'DELETE':
		$file = GETPOST('file');
		$upload_handler->delete($file);
		break;*/
	default:
		header('HTTP/1.0 405 Method Not Allowed');
		exit;
}

$db->close();
