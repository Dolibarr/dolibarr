<?php
/* Copyright (C) 2010 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 */
?>

<!-- BEGIN PHP TEMPLATE freeproductline_view.tpl.php -->
<tr <?php echo 'id="row-'.$line->id.'" '.$bcdd[$var]; ?>>
	<td><a name="<?php echo $line->rowid; ?>"></a>
	<?php if (($line->info_bits & 2) == 2) { ?>
		<a href="<?php echo DOL_URL_ROOT.'/comm/remx.php?id='.$this->socid; ?>">
		<?php echo img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount"); ?>
		</a>
		<?php if ($line->description) {
				if ($line->description == '(CREDIT_NOTE)') {
					$discount=new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					echo ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
				} elseif ($line->description == '(DEPOSIT)') {
					$discount=new DiscountAbsolute($this->db);
					$discount->fetch($line->fk_remise_except);
					echo ' - '.$langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
				} else {
					echo ' - '.dol_htmlentitiesbr($line->description,1,true);
				}
			}
		} else {
			if (! empty($line->fk_parent_line)) echo img_picto('', 'rightarrow');
			if ($type==1) $text = img_object($langs->trans('Service'),'service');
			else $text = img_object($langs->trans('Product'),'product');
			echo $text.' '.dol_htmlentitiesbr($line->description,1,true);
			// Show range
			print_date_range($line->date_start,$line->date_end);
		} ?>
	</td>

	<td align="right" nowrap="nowrap"><?php echo vatrate($line->tva_tx,'%',$line->info_bits); ?></td>

	<td align="right" nowrap="nowrap"><?php echo price($line->subprice); ?></td>

	<td align="right" nowrap="nowrap">
	<?php if ((($line->info_bits & 2) != 2) && $line->special_code != 3) echo $line->qty;
		else echo '&nbsp;';	?>
	</td>

	<?php if (!empty($line->remise_percent) && $line->special_code != 3) { ?>
	<td align="right"><?php echo dol_print_reduction($line->remise_percent,$langs); ?></td>
	<?php } else { ?>
	<td>&nbsp;</td>
	<?php } ?>

	<?php if ($line->special_code == 3)	{ ?>
	<td align="right" nowrap="nowrap"><?php echo $langs->trans('Option'); ?></td>
	<?php } else { ?>
	<td align="right" nowrap="nowrap"><?php echo price($line->total_ht); ?></td>
	<?php } ?>

	<?php if ($this->statut == 0  && $user->rights->$element->creer) { ?>
	<td align="center">
		<?php if (($line->info_bits & 2) == 2) { ?>
		<?php } else { ?>
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#'.$line->id; ?>">
		<?php echo img_edit(); ?>
		</a>
		<?php } ?>
	</td>

	<td align="center">
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id; ?>">
		<?php echo img_delete(); ?>
		</a>
	</td>

	<?php if ($num > 1) { ?>
	<td align="center" class="tdlineupdown">
		<?php if ($i > 0) { ?>
		<a class="lineupdown" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id; ?>">
		<?php echo img_up(); ?>
		</a>
		<?php } ?>
		<?php if ($i < $num-1) { ?>
		<a class="lineupdown" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id; ?>">
		<?php echo img_down(); ?>
		</a>
		<?php } ?>
	</td>
    <?php } else { ?>
    <td align="center" class="tdlineupdown">&nbsp;</td>
	<?php } ?>
<?php } else { ?>
	<td colspan="3">&nbsp;</td>
<?php } ?>

</tr>
<!-- END PHP TEMPLATE freeproductline_view.tpl.php -->
