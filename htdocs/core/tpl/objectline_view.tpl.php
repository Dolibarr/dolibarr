<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
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
 * To use this template, the following var must be defined
 * $type, $text, $description, $line
 */
?>
<?php $coldisplay=0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr <?php echo 'id="row-'.$line->id.'" '.$bcdd[$var]; ?>>
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td align="center"><?php $coldisplay++; ?><?php echo ($i+1); ?></td>
	<?php } ?>
	<td><?php $coldisplay++; ?><div id="<?php echo $line->rowid; ?>"></div>
	<?php if (($line->info_bits & 2) == 2) { ?>
		<a href="<?php echo DOL_URL_ROOT.'/comm/remx.php?id='.$this->socid; ?>">
		<?php
		$txt='';
		print img_object($langs->trans("ShowReduc"),'reduc').' ';
		if ($line->description == '(DEPOSIT)') $txt=$langs->trans("Deposit");
		//else $txt=$langs->trans("Discount");
		print $txt;
		?>
		</a>
		<?php
		if ($line->description)
		{
			if ($line->description == '(CREDIT_NOTE)' && $objp->fk_remise_except > 0)
			{
				$discount=new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				echo ($txt?' - ':'').$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
			}
			elseif ($line->description == '(DEPOSIT)' && $objp->fk_remise_except > 0)
			{
				$discount=new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				echo ($txt?' - ':'').$langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
				// Add date of deposit
				if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec).')';
			}
			else
			{
				echo ($txt?' - ':'').dol_htmlentitiesbr($line->description);
			}
		}
	}
	else
	{
		if ($line->fk_product > 0) {

			echo $form->textwithtooltip($text,$description,3,'','',$i,0,(!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));

			// Show range
			echo get_date_range($line->date_start, $line->date_end);

			// Add description in form
			if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
			{
				print (! empty($line->description) && $line->description!=$line->product_label)?'<br>'.dol_htmlentitiesbr($line->description):'';
			}

		} else {

			if ($type==1) $text = img_object($langs->trans('Service'),'service');
			else $text = img_object($langs->trans('Product'),'product');

			if (! empty($line->label)) {
				$text.= ' <strong>'.$line->label.'</strong>';
				echo $form->textwithtooltip($text,dol_htmlentitiesbr($line->description),3,'','',$i,0,(!empty($line->fk_parent_line)?img_picto('', 'rightarrow'):''));
			} else {
				if (! empty($line->fk_parent_line)) echo img_picto('', 'rightarrow');
				echo $text.' '.dol_htmlentitiesbr($line->description);
			}

			// Show range
			echo get_date_range($line->date_start,$line->date_end);
		}
	}
	?>
	</td>

	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo vatrate($line->tva_tx,'%',$line->info_bits); ?></td>

	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo price($line->subprice); ?></td>

	<?php if ($conf->global->MAIN_FEATURES_LEVEL > 1) { ?>
	<td align="right" class="nowrap"><?php $coldisplay++; ?>&nbsp;</td>
	<?php } ?>

	<td align="right" class="nowrap"><?php $coldisplay++; ?>
	<?php if ((($line->info_bits & 2) != 2) && $line->special_code != 3) {
			// I comment this because it shows info even when not required
			// for example always visible on invoice but must be visible only if stock module on and stock decrease option is on invoice validation and status is not validated
			// must also not be output for most entities (proposal, intervention, ...)
			//if($line->qty > $line->stock) print img_picto($langs->trans("StockTooLow"),"warning", 'style="vertical-align: bottom;"')." ";
			echo $line->qty;
		} else echo '&nbsp;';	?>
	</td>

	<?php if (!empty($line->remise_percent) && $line->special_code != 3) { ?>
	<td align="right"><?php $coldisplay++; ?><?php echo dol_print_reduction($line->remise_percent,$langs); ?></td>
	<?php } else { ?>
	<td><?php $coldisplay++; ?>&nbsp;</td>
	<?php }

  if (! empty($conf->margin->enabled) && empty($user->societe_id)) {
	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT);
  ?>
  	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo price($line->pa_ht); ?></td>
  	<?php if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous) {?>
  	  <td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo (($line->pa_ht == 0)?'n/a':price($line->marge_tx, null, null, null, null, $rounding).'%'); ?></td>
  	<?php
  }
  if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous) {?>
  	  <td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo price($line->marque_tx, null, null, null, null, $rounding).'%'; ?></td>
  <?php } } ?>

	<?php if ($line->special_code == 3)	{ ?>
	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo $langs->trans('Option'); ?></td>
	<?php } else { ?>
	<td align="right" class="nowrap"><?php $coldisplay++; ?><?php echo price($line->total_ht); ?></td>
	<?php } ?>

	<?php if ($this->statut == 0  && $user->rights->$element->creer) { ?>
	<td align="center"><?php $coldisplay++; ?>
		<?php if (($line->info_bits & 2) == 2) { ?>
		<?php } else { ?>
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#'.$line->id; ?>">
		<?php echo img_edit(); ?>
		</a>
		<?php } ?>
	</td>

	<td align="center"><?php $coldisplay++; ?>
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id; ?>">
		<?php echo img_delete(); ?>
		</a>
	</td>

	<?php if ($num > 1 && empty($conf->browser->phone)) { ?>
	<td align="center" class="tdlineupdown"><?php $coldisplay++; ?>
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
    <td align="center"<?php echo (empty($conf->browser->phone)?' class="tdlineupdown"':''); ?>><?php $coldisplay++; ?></td>
	<?php } ?>
<?php } else { ?>
	<td colspan="3"><?php $coldisplay=$coldisplay+3; ?></td>
<?php } ?>

<?php
//Line extrafield
if (!empty($extrafieldsline)) {
	print $line->showOptionals($extrafieldsline,'view',array('style'=>$bcdd[$var],'colspan'=>$coldisplay));
}
?>

</tr>
<!-- END PHP TEMPLATE objectline_view.tpl.php -->
