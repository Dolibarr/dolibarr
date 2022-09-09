<?php

if ($action == 'confirm_setapproved' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->validate($user);
	$result = $object->setApprovalApproved($user);
	if ($result >= 0) {
		// Nothing else done
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

if ($action == 'confirm_setrejected' && $confirm == 'yes' && $permissiontoadd) {
	$result = $object->setApprovalRejected($user);
	if ($result >= 0) {
		// Nothing else done
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}
