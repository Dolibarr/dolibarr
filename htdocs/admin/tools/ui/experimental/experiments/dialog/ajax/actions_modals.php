<?php
if (!defined('NOTOKENRENEWAL')) {define('NOTOKENRENEWAL', 1);}
if (!defined('NOREQUIREMENU')) {define('NOREQUIREMENU', 1);}
if (!defined('NOREQUIREAJAX')) {define('NOREQUIREAJAX', 1);}
if (!defined('NOREQUIRESOC')) {define('NOREQUIRESOC', 1);}
if (!defined('NOCSRFCHECK')) {define('NOCSRFCHECK', 1);}

$res=0;
if (! $res && file_exists("../../../../../../../main.inc.php")): $res=@include '../../../../../../../main.inc.php'; endif;

/**
 * @var DoliDB      $db
 * @var Translate   $langs
 * @var User        $user
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/jsonResponse.class.php';

//
top_httphead('application/json');

//
$langs->load('errors');

//
$action = GETPOST('action');

if ($action == 'addticketexample') {
	$ref = GETPOST('ref', 'aZ09');
	$desc = GETPOST('description', 'alphanohtml');

	$response = new JsonResponse();

	$error = 0;
	$errors = array();

	if (empty($ref)) {
		$error++;
		$errors[] = $langs->trans('ErrorFieldRequired', 'Ref');
	}
	if (empty($desc)) {
		$error++;
		$errors[] = $langs->trans('ErrorFieldRequired', 'Description');
	}

	if ($error > 0) {
		$response->result = 0;
		$response->msg = implode("\r\n", $errors);
		print $response->getResponse();
		exit;
	}

	//
	$successMsg = 'You have submitted a form to create a new ticket<br><br>';
	$successMsg .= '<b>Ref:</b> '.GETPOST('ref').'<br>';
	$successMsg .= '<b>Request type:</b> '.ucfirst(GETPOST('type_code')).'<br>';
	$successMsg .= '<b>Socid:</b> '.GETPOSTINT('socid').'<br>';
	$successMsg .= '<b>Description:</b> '.GETPOST('description');
	$response->result = 1;
	$response->msg = $successMsg;
	print $response->getResponse();
	exit;
}
