<?php
/* Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */
?>

<!-- BEGIN PHP TEMPLATE -->

<?php
if ($somethingshown) { echo '<br>'; }
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
for ($i = 0 ; $i < $num ; $i++)
{
	$linkedObjectBlock->fetch($objectid[$i]);
	$var=!$var;
?>
<tr <?php echo $bc[$var]; ?> ><td>
	<a href="<?php echo DOL_URL_ROOT.'/compta/propal.php?id='.$linkedObjectBlock->id ?>"><?php echo img_object($langs->trans("ShowPropal"),"propal").' '.$linkedObjectBlock->ref; ?></a></td>
	<td><?php echo $linkedObjectBlock->ref_client; ?></td>
	<td align="center"><?php echo dol_print_date($linkedObjectBlock->date,'day'); ?></td>
	<td align="right"><?php echo price($linkedObjectBlock->total_ht); ?></td>
	<td align="right"><?php echo $linkedObjectBlock->getLibStatut(3); ?></td>
</tr>
<?php
$total = $total + $linkedObjectBlock->total_ht;
}

?>
<tr class="liste_total">
	<td align="left" colspan="3"><?php echo $langs->trans('TotalHT'); ?></td>
	<td align="right"><?php echo price($total); ?></td>
	<td>&nbsp;</td>
</tr>
</table>

<!-- END PHP TEMPLATE -->
