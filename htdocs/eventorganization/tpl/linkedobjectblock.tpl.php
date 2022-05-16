<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
 *
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

echo "<!-- BEGIN PHP TEMPLATE eventorganization/tpl/linkedobjectblock.tpl.php -->\n";

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
$langs->load("eventorganization");

$total = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	echo '<tr class="oddeven">';
	echo '<td>' . $langs->trans(get_class($objectlink)) . '</td>';
	echo '<td>'.$objectlink->getNomUrl(1).'</td>';
	echo '<td class="center">';
	if (get_class($objectlink)=='ConferenceOrBooth') {
		print  dol_trunc($objectlink->label, 20);
	}
	print '</td>';
	echo '<td class="center">';
	if (get_class($objectlink)=='ConferenceOrBoothAttendee') {
		print dol_print_date($objectlink->date_subscription);
	} else {
		print dol_print_date($objectlink->datep);
	}
	print '</td>';
	echo '<td class="right">';
	if (get_class($objectlink)=='ConferenceOrBoothAttendee') {
		print price($objectlink->amount);
	}
	print '</td>';
	echo '<td class="right">'.$objectlink->getLibStatut(3).'</td>';
	echo '<td class="right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
	echo '</tr>';
}

echo "<!-- END PHP TEMPLATE -->\n";
