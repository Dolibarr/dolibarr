<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2018      Juanjo Menent <jmenent@2byte.es>
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
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("contracts");

$total=0; $ilink=0;
$var=true;
foreach($linkedObjectBlock as $key => $objectlink)
{
    $ilink++;

    $trclass=($var?'pair':'impair');
    if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass.=' liste_sub_total';
?>
<tr class="<?php echo $trclass; ?>">
    <td><?php echo $langs->trans("Contract"); ?></td>
    <td><?php echo $objectlink->getNomUrl(1); ?></td>
    <td></td>
	<td align="center"><?php echo dol_print_date($objectlink->date_contrat,'day'); ?></td>
    <td align="right"><?php
		// Price of contract is not shown by default because a contract is a list of service with 
		// start and end date that change with time andd that may be different that the period of reference for price.
		// So price of a contract does often means nothing. Prices is on the different invoices done on same contract.
		if ($user->rights->contrat->lire && empty($conf->global->CONTRACT_SHOW_TOTAL_OF_PRODUCT_AS_PRICE)) 
		{
			$totalcontrat = 0;
			foreach ($objectlink->lines as $linecontrat) {
				$totalcontrat = $totalcontrat + $linecontrat->total_ht;
			    $total = $total + $linecontrat->total_ht;
			}
			echo price($totalcontrat);
		} ?></td>
	<td align="right"><?php echo $objectlink->getLibStatut(7); ?></td>
	<td align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a></td>
</tr>
<?php } ?>

<!-- END PHP TEMPLATE -->
