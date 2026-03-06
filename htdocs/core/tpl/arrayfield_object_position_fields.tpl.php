<?php
/* 
 */

// This tpl file is included into the init part of pages, so before action.
// So no output must be done.
// Used to modify fields positions according to user parameters 

/**
 * @var User	$user
 *
 * @var string|array<string|int, mixed>			$contextpage
 * @var array<string,array{label:string,checked?:string,position?:int,help?:string,enabled?:string}>		$arrayfields
 */

'
@phan-var-force array<string,array{label:string,checked?:string,position?:int,help?:string,enabled?:string}> $arrayfields
';

// Protection to avoid direct call of template
if (empty($user) || !is_object($user)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

$tmpvar = "MAIN_POSITIONFIELDS_" . $contextpage; // To get list of saved position fields to show
if (!empty($user->conf->$tmpvar)) {        // A list of fields was already customized for user
	$tmparray = dolExplodeIntoArray($user->conf->$tmpvar, ',', ':');
	foreach ($arrayfields as $key => $val) {
		if (isset($tmparray[$key])) {
			$arrayfields[$key]['position'] = $tmparray[$key];
			$object->fields[explode('.', $key)[1]]['position'] = $tmparray[$key];
		}
	}
}