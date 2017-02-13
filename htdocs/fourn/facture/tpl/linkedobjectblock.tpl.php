<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2014      Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2015      Charlie Benke <charlie@patas-monkey.com>
 * Copyright (C) 2016      Laurent Destailleur <eldy@users.sourceforge.net>
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
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("bills");

$total=0; $ilink=0;
$var=true;
foreach($linkedObjectBlock as $key => $objectlink)
{
    $ilink++;
    $var=!$var;
    $trclass=($var?'pair':'impair');
    if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass.=' liste_sub_total';
?>
    <tr class="<?php echo $trclass; ?>">
        <td><?php echo $langs->trans("SupplierInvoice"); ?></td>
    	<td><a href="<?php echo DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$objectlink->id ?>"><?php echo img_object($langs->trans("ShowBill"),"bill").' '.$objectlink->ref; ?></a></td>
    	<td align="left"><?php echo $objectlink->ref_supplier; ?></td>
    	<td align="center"><?php echo dol_print_date($objectlink->date,'day'); ?></td>
    	<td align="right"><?php
    		if ($user->rights->fournisseur->facture->lire) {
    		    $sign = 1;
    			if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE) $sign = -1;
    			if ($objectlink->statut != 3)		// If not abandonned
    			{
    				$total = $total + $sign * $objectlink->total_ht;
    				echo price($objectlink->total_ht);
    			}
    			else 
    			{
    				echo '<strike>'.price($objectlink->total_ht).'</strike>';
    			}
    		} ?></td>
    	<td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
    	<td align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a></td>
    </tr>
<?php 
}
if (count($linkedObjectBlock) > 1)
{
    ?>
    <tr class="liste_total <?php echo (empty($noMoreLinkedObjectBlockAfter)?'liste_sub_total':''); ?>">
        <td><?php echo $langs->trans("Total"); ?></td>
        <td></td>
    	<td align="center"></td>
    	<td align="center"></td>
    	<td align="right"><?php echo price($total); ?></td>
    	<td align="right"></td>
    	<td align="right"></td>
    </tr>
    <?php  
}
?>

<!-- END PHP TEMPLATE -->
