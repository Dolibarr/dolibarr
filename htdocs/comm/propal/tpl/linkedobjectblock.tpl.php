<?php
/* Copyright (C) 2010-2011 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * $Id: linkedobjectblock.tpl.php,v 1.8 2011/08/03 00:46:31 eldy Exp $
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['object']->linkedObjectBlock;

echo '<br />';
print_titre($langs->trans('RelatedCommercialProposals'));
?>
<table class="noborder" width="100%">
<tr class="liste_titre">
	<td><?php echo $langs->trans("Ref"); ?></td>
	<td><?php echo $langs->trans('RefCustomer'); ?></td>
	<td align="center"><?php echo $langs->trans("Date"); ?></td>
	<td align="right"><?php echo $langs->trans("AmountHTShort"); ?></td>
	<td align="right"><?php echo $langs->trans("Status"); ?></td>
</tr>
<?php
$var=true;
foreach($linkedObjectBlock as $object)
{
	$var=!$var;
?>
<tr <?php echo $bc[$var]; ?> ><td>
	<a href="<?php echo DOL_URL_ROOT.'/comm/propal.php?id='.$object->id ?>"><?php echo img_object($langs->trans("ShowPropal"),"propal").' '.$object->ref; ?></a></td>
	<td><?php echo $object->ref_client; ?></td>
	<td align="center"><?php echo dol_print_date($object->date,'day'); ?></td>
	<td align="right"><?php echo price($object->total_ht); ?></td>
	<td align="right"><?php echo $object->getLibStatut(3); ?></td>
</tr>
<?php
$total = $total + $object->total_ht;
}

?>
<tr class="liste_total">
	<td align="left" colspan="3"><?php echo $langs->trans('TotalHT'); ?></td>
	<td align="right"><?php echo price($total); ?></td>
	<td>&nbsp;</td>
</tr>
</table>

<!-- END PHP TEMPLATE -->
