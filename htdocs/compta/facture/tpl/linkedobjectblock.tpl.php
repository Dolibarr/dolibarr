<?php
/* Copyright (C) 2010-2011	Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
	exit(1);
}

print "<!-- BEGIN PHP TEMPLATE compta/facture/tpl/linkedobjectblock.tpl.php -->\n";

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

$langs->load("bills");

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);
'@phan-var-force array<string,CommonObject> $linkedObjectBlock';

$total = 0;
$ilink = 0;
foreach ($linkedObjectBlock as $key => $objectlink) {
	$ilink++;
	$trclass = 'oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
		$trclass .= ' liste_sub_total';
	}
	print '<tr class="'.$trclass.'" data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'" >';
	print '<td class="linkedcol-element tdoverflowmax100">';
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
	if (!empty($showImportButton) && getDolGlobalString('MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES')) {
		print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines" data-element="'.$objectlink->element.'" data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';
	}
	print '</td>';
	print '<td class="linkedcol-name tdoverflowmax150">'.$objectlink->getNomUrl(1).'</td>';
	print '<td class="linkedcol-ref tdoverflowmax150" title="'.dol_escape_htmltag($objectlink->ref_client).'">'.dol_escape_htmltag($objectlink->ref_client).'</td>';
	print '<td class="linkedcol-date center">'.dol_print_date($objectlink->date, 'day').'</td>';
	print '<td class="linkedcol-amount right nowraponall">';
	if (!empty($objectlink) && $objectlink->element == 'facture' && $user->hasRight('facture', 'lire')) {
		if ($objectlink->statut != 3) {
			// If not abandoned
			$total += $objectlink->total_ht;
			echo price($objectlink->total_ht);
		} else {
			echo '<strike>'.price($objectlink->total_ht).'</strike>';
		}
	}

	print '</td>';
	print '<td class="linkedcol-statut right">';
	if (method_exists($objectlink, 'getSommePaiement')) {
		print $objectlink->getLibStatut(3, $objectlink->getSommePaiement());
	} else {
		print $objectlink->getLibStatut(3);
	}
	print '</td>';
	print '<td class="linkedcol-action right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&token='.newToken().'&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
	print "</tr>\n";
}
if (count($linkedObjectBlock) > 1) {
	print '<tr class="liste_total '.(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : '').'">';
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
