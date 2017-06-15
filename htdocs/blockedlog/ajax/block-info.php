<?php

	require '../../main.inc.php';
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	
	$id = GETPOST('id');
	
	$block = new BlockedLog($db);
	if($block->fetch($id)>0) {
		echo json_encode($block->object_data);
	}
	else {
		echo json_encode(false);
	}
	