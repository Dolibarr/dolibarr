<?php
/*
* This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// This tpl file is included into the init part of pages, so before action.
// So no output must be done.
// Used to modify fields positions according to user parameters

/**
 * @var User		 	$user
 * @var CommonObject 	$object
 *
 * @var string|array<string|int, mixed>			$contextpage
 * @var array<string,array{label:string,checked?:string,position?:int,help?:string,enabled?:string}>		$arrayfields
 */

'
@phan-var-force string|array<string|int, mixed>	$contextpage
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
