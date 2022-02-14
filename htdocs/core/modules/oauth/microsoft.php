<?php
session_start();
$code = $_GET['code'];
if($code == null) print 'No Code';

$ch = curl_init();
curl_setopt_array($ch, array(
	CURLOPT_HTTPHEADER => array('Content-type: application/x-www-form-urlencoded'),
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_POST => true,
	CURLOPT_URL => 'https://login.microsoftonline.com/organizations/oauth2/v2.0/token',
	CURLOPT_POSTFIELDS => 'client_id=855b4c8d-9121-4acf-86fc-f8b9f34dbb54&client_secret=fqxuni.3rq__-ZsJ-XiW7fl90LpP1OD2r-&grant_type=authorization_code&redirect_uri=https://dev.draht.hands-on-technology.org/core/modules/oauth/microsoft.php&scope=https://graph.microsoft.com/User.Read&code=' . $code,
	CURLOPT_RETURNTRANSFER => true,
));

$result = curl_exec($ch);
$result = json_decode($result);
curl_close($ch);

$ch = curl_init();
curl_setopt_array($ch, array(
	CURLOPT_HTTPHEADER => array('Content-type: application/x-www-form-urlencoded', 'Authorization: ' . $result->access_token),
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_HTTPGET => true,
	CURLOPT_URL => 'https://graph.microsoft.com/v1.0/me/mail',
	CURLOPT_RETURNTRANSFER => true,
));

$result = curl_exec($ch);
$result = json_decode($result);
curl_close($ch);

$email = $result->value;

if($email) {
	include('../../../master.inc.php');
	$user->fetch(false, false,false,true,-1,$email);
	setcookie('login_dolibarr', $user->login);
	$_SESSION["dol_login"] = $user->login;
	$_SESSION["dol_entity"] = $user->entity;
	include('../../../main.inc.php');
	//var_dump(get_defined_vars());
	header('Location: https://dev.draht.hands-on-technology.org');
}

?>
