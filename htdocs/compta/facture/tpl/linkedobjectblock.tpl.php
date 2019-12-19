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
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE -->\n";

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("bills");

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

$total=0;
$ilink=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
    $ilink++;

    $trclass='oddeven';
    if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass.=' liste_sub_total';
	print '<tr class="'.$trclass.'" data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'" >';
	print '<td class="linkedcol-element">';
	switch ($objectlink->type) {
		case Facture::TYPE_REPLACEMENT:
			echo $langs->trans("InvoiceReplacement");
			break;
		case Facture::TYPE_CREDIT_NOTE:
			echo $langs->trans("InvoiceAvoir");
			break;
		case Facture::TYPE_DEPOSIT:
			echo $langs->trans("InvoiceDeposit");
			break;
		case Facture::TYPE_PROFORMA:
			echo $langs->trans("InvoiceProForma");
			break;
		case Facture::TYPE_SITUATION:
			echo $langs->trans("InvoiceSituation");
			break;
		default:
			echo $langs->trans("CustomerInvoice");
			break;
	}
	print '</td>';
    print '<td class="linkedcol-name nowraponall">'.$objectlink->getNomUrl(1).'</td>';
    print '<td class="linkedcol-ref left">'.$objectlink->ref_client.'</td>';
    print '<td class="linkedcol-date center">'.dol_print_date($objectlink->date, 'day').'</td>';
    print '<td class="linkedcol-amount right">';
    if ($user->rights->facture->lire) {
    	$sign = 1;
    	if ($object->type == Facture::TYPE_CREDIT_NOTE) $sign = -1;
    	if ($objectlink->statut != 3) {
			// If not abandonned
    		$total = $total + $sign * $objectlink->total_ht;
    		echo price($objectlink->total_ht);
    	}
    	else
    	{
    		echo '<strike>'.price($objectlink->total_ht).'</strike>';
    	}
	}
    print '</td>';
    print '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(3).'</td>';
    print '<td class="linkedcol-action right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
    print "</tr>\n";
}
if (count($linkedObjectBlock) > 1)
{
    print '<tr class="liste_total '.(empty($noMoreLinkedObjectBlockAfter)?'liste_sub_total':'').'">';
    print '<td>'.$langs->trans("Total").'</td>';
    print '<td></td>';
    print '<td class="center"></td>';
    print '<td class="center"></td>';
    print '<td class="right">'.price($total).'</td>';
    print '<td class="right"></td>';
    print '<td class="right"></td>';
    print '</tr>';
}

print "<!-- END PHP TEMPLATE -->\n";
