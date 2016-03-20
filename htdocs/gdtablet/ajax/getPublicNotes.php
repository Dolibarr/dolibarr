<?php

require '../../main.inc.php';

$socid = GETPOST('socid', 'int');

try {
	if (!$socid) {
		throw new Exception('Socid not defined');
	}

	$societe = new Societe($db);

	if ($societe->fetch($socid) < 1) {
		throw new Exception('Societe not found');
	}

	$result = array(
		'status' => 'ok',
		'result' => (string) $societe->note_private
	);
} catch (Exception $e) {
	$result = array(
		'status' => 'error',
		'result' => $e->getMessage()
	);
}

header('Content-Type: application/json');

print json_encode($result);