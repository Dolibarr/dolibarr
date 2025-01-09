<?php
/* Copyright (C) 2019		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) ---Replace with your own copyright and developer email---
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

/**
 * @var CommonObject $object
 * @var Translate $langs
 * @var User $user
 */
// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}


print "<!-- BEGIN PHP TEMPLATE mymodule/core/tpl/linkedobjectblock_myobject.tpl.php  -->\n";


global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
'@phan-var-force Translate $langs';
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
'@phan-var-force array<string,MyObject> $linkedObjectBlock';

// Load translation files required by the page
$langs->load("mymodule");

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;

	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	} ?>
<tr class="<?php echo $trclass; ?>">
	<td><?php echo $langs->trans("MyObject"); ?></td>
	<td><?php echo $objectlink->getNomUrl(1); ?></td>
	<td></td>
	<td class="center"><?php echo dol_print_date($objectlink->date_creation, 'day'); ?></td>
	<td class="right"><?php echo ''; ?></td>
	<td class="right"><?php echo $objectlink->getLibStatut(7); ?></td>
	<td class="right"><a class="reposition" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a></td>
</tr>
	<?php
}

print "<!-- END PHP TEMPLATE -->\n";
