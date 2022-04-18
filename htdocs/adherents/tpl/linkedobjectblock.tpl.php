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

echo "<!-- BEGIN PHP TEMPLATE adherents/tpl/linkedobjectblock.tpl.php -->\n";

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
$langs->load("members");

$total = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	echo '<tr class="oddeven">';
	echo '<td>'.$langs->trans("Subscription").'</td>';
	echo '<td class="nowraponall">'.$objectlink->getNomUrl(1).'</td>';
	echo '<td class="center"></td>';
	echo '<td class="center">'.dol_print_date($objectlink->dateh, 'day').'</td>';
	echo '<td class="right">';
	if ($user->rights->adherent->lire) {
		$total = $total + $objectlink->amount;
		echo price($objectlink->amount);
	}
	echo '</td>';
	echo '<td class="right"></td>';
	echo '<td class="right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
	echo '</tr>';
}

echo "<!-- END PHP TEMPLATE -->\n";
