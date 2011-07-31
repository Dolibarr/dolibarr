<?php
/* Copyright (C) 2010-2011 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $Id: predefinedproductlinediv_view.tpl.php,v 1.2 2011/07/31 23:45:11 eldy Exp $
 */
?>

<!-- BEGIN PHP TEMPLATE predefinedproductline_view.tpl.php -->

<div class="tr pair" <?php echo 'id="row-'.$line->id.'"'; ?>>
	<div class="td firstcol">
	<a name="<?php echo $line->id; ?>"></a>
	<?php
	echo $html->textwithtooltip($text,$description,3,'','',$i,0,($line->fk_parent_line?img_picto('', 'rightarrow'):''));

	// Show range
	print_date_range($line->date_start, $line->date_end);

	// Add description in form
	if ($conf->global->PRODUIT_DESC_IN_FORM)
	{
		print ($line->description && $line->description!=$line->product_label)?'<br>'.dol_htmlentitiesbr($line->description):'';
	}
	?>
	</div>

	<div class="td"><?php echo vatrate($line->tva_tx,'%',$line->info_bits); ?></div>

	<div class="td"><?php echo price($line->subprice); ?></div>

	<div class="td">
	<?php if ((($line->info_bits & 2) != 2) && $line->special_code != 3) echo $line->qty;
		else echo '&nbsp;';	?>
	</div>

	<div class="td">
	<?php if (!empty($line->remise_percent) && $line->special_code != 3) {
		echo dol_print_reduction($line->remise_percent,$langs);
	} else {
		echo '&nbsp;';
	} ?>
	</div>

	<div class="td">
	<?php if ($line->special_code == 3)	{
		echo $langs->trans('Option');
	} else {
		echo price($line->total_ht);
	} ?>
	</div>

	<?php if ($this->statut == 0  && $user->rights->$element->creer) { ?>
	<div class="td endcol">
		<?php if (($line->info_bits & 2) == 2) { ?>
		<?php } else { ?>
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#'.$line->id; ?>">
		<?php echo img_edit(); ?>
		</a>
		<?php } ?>
	</div>

	<div class="td endcol">
		<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=ask_deleteline&amp;lineid='.$line->id; ?>">
		<?php echo img_delete(); ?>
		</a>
	</div>

	<?php if ($num > 1) { ?>
	<div class="td end tdlineupdown">
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
	</div>
	<?php } else { ?>
		<div class="td endcol tdlineupdown">&nbsp;</div>
	<?php } ?>
<?php } else { ?>
	<div class="td endcol">&nbsp;</div>
	<div class="td endcol">&nbsp;</div>
	<div class="td end">&nbsp;</div>
<?php } ?>

</div>
<!-- END PHP TEMPLATE predefinedproductline_view.tpl.php -->
