<?php
	
	require '../../master.inc.php';
	
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/authority.class.php';

	$user=new User($db);
	$user->fetch(1); //TODO conf user authority
	
	$auth = new BlockedLogAuthority($db);
	
	$signature = GETPOST('s');
	$newblock = GETPOST('b');
	$hash = GETPOST('h');
	
	if($auth->fetch(0, $signature)<=0) {
		$auth->signature = $signature;
		$auth->create($user);
	}
	
	
	if(!empty($hash)) {
		
		echo $auth->checkBlockchain($hash) ? 'hashisok' : 'hashisjunk';
			
	}
	elseif(!empty($newblock)){
		if($auth->checkBlock($newblock)) {
			$auth->addBlock($newblock);
			$auth->update($user);
			
			echo 'blockadded';
		}
		else{
				
			echo 'blockalreadyadded';
				
		}
	}
	else{
		echo 'idontunderstandwhatihavetodo';
	}
	
	
