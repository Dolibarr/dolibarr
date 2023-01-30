<?php

if ($action == 'confirm_setapproved' && $confirm == 'yes' && $permissiontoapprove) {
	$result = $object->validate($user);
	$result = $object->setApprovalApproved($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}

if ($action == 'confirm_setrejected' && $confirm == 'yes' && $permissiontoapprove) {
	$result = $object->setApprovalRejected($user);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}
if ($action == 'confirm_settripstarted' && $confirm == 'yes' && $permissiontorequest) {
	$result = $object->validate($user);
	$result = $object->setTripStatus($user, $object::TRIPSTATUS_ONTRIP);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}
if ($action == 'confirm_settripcompleted' && $confirm == 'yes' && $permissiontorequest) {
	$result = $object->validate($user);
	$result = $object->setTripStatus($user, $object::TRIPSTATUS_COMPLETED);
	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF'].'?id='.$object->id);
		exit;
	} else {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}
