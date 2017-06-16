<?php 

	require '../../main.inc.php';
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	
	$id = GETPOST('id','int');
	$element = GETPOST('element','alpha');
	$action = GETPOST('action','alpha');
	
	if($element === 'facture') {
		dol_include_once('/compta/facture/class/facture.class.php');
		
		$facture = new Facture($db);
		if($facture->fetch($id)>0) {
			$facture->call_trigger($action, $user);
		}
		
	}
	