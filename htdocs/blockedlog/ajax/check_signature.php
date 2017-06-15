<?php

	require '../../main.inc.php';

	if(empty($conf->global->BLOCKEDLOG_AUTHORITY_URL)) exit('BLOCKEDLOG_AUTHORITY_URL not set');
	
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/authority.class.php';
	
	$auth=new BlockedLogAuthority($db);
	$auth->syncSignatureWithAuthority();
	
	$block_static = new BlockedLog($db);
	
	$blocks = $block_static->getLog('just_certified', 0, 0, 1) ;

	$auth->signature = $block_static->getSignature();
	
	foreach($blocks as &$b) {
		$auth->blockchain.=$b->signature;	
			
	}
	
	$hash = $auth->getBlockchainHash();
	
	$url = $conf->global->BLOCKEDLOG_AUTHORITY_URL.'/blockedlog/ajax/authority.php?s='.$auth->signature.'&h='.$hash;

	$res = file_get_contents($url);
	//echo $url;
	echo $res;