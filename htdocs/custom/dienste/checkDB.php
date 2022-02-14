<?php

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT . '/custom/dienste/class/buero.class.php';
include_once DOL_DOCUMENT_ROOT . '/custom/dienste/class/telefon.class.php';
include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$dienst = GETPOST('dienst');
$datum = GETPOST('datum');

$dt = DateTime::createFromFormat('d.m.Y', $datum);
$ref = date('Y-m-d', $dt->getTimestamp());

if ($dienst == 'buero') $obj = new Buero($db);
elseif ($dienst == 'telefon') $obj = new Telefon($db);

$obj->fetch('', $ref);
$usr = new User($db);
$usr->fetch($obj->user);

echo $usr->firstname . ' ' . $usr->lastname;

?>
