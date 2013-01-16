<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("contracts");
echo '<br>';
print_titre($langs->trans('RelatedContracts'));
?>
<table class="noborder allwidth">
<tr class="liste_titre">
	<td><?php echo $langs->trans("Ref"); ?></td>
	<td align="center"><?php echo $langs->trans("Date"); ?></td>
	<td align="right">&nbsp;</td>
	<td align="right"><?php echo $langs->trans("Status"); ?></td>
</tr>
<?php
$var=true;
foreach($linkedObjectBlock as $object)
{
    $object->fetch_lines();
	$var=!$var;
?>
<tr <?php echo $bc[$var]; ?> ><td>
	<a href="<?php echo DOL_URL_ROOT.'/contrat/fiche.php?id='.$object->id ?>"><?php echo img_object($langs->trans("ShowContract"),"contract").' '.$object->ref; ?></a></td>
	<td align="center"><?php echo dol_print_date($object->date_contrat,'day'); ?></td>
	<td align="right">&nbsp;</td>
	<td align="right"><?php echo $object->getLibStatut(6); ?></td>
</tr>
<?php } ?>

</table>

<!-- END PHP TEMPLATE -->