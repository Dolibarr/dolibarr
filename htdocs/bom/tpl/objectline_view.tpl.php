<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
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
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || ! is_object($object))
{
	print "Error, template page can't be called as URL";
	exit;
}


global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

if (empty($dateSelector)) $dateSelector=0;
if (empty($forceall)) $forceall=0;
if (empty($senderissupplier)) $senderissupplier=0;
if (empty($inputalsopricewithtax)) $inputalsopricewithtax=0;
if (empty($outputalsopricetotalwithtax)) $outputalsopricetotalwithtax=0;

// add html5 elements
$domData  = ' data-element="'.$line->element.'"';
$domData .= ' data-id="'.$line->id.'"';
$domData .= ' data-qty="'.$line->qty.'"';
$domData .= ' data-product_type="'.$line->product_type.'"';

// Lines for extrafield
$objectline = new BOMLine($object->db);

?>
<?php $coldisplay=0; ?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr  id="row-<?php echo $line->id?>" class="drag drop oddeven" <?php echo $domData; ?> >
	<?php if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
	<td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i+1); ?></td>
	<?php } ?>
	<td class="linecoldescription minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php echo $line->id; ?>"></div>
	<?php
	$tmpproduct = new Product($object->db);
	$tmpproduct->fetch($line->fk_product);
	print $tmpproduct->getNomUrl(1);
	?>
	</td>
	<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
    <?php
	echo price($line->qty, 0, '', 0, 0);  // Yes, it is a quantity, not a price, but we just want the formating role of function price
    ?>
	</td>

	<?php
	if($conf->global->PRODUCT_USE_UNITS)
	{
		print '<td class="linecoluseunit nowrap left">';
		$label = $line->getLabelOfUnit('short');
		if ($label !== '') {
			print $langs->trans($label);
		}
		print '</td>';
	}
	?>
	<td class="linecolqty nowrap right"><?php $coldisplay++; ?>
    <?php
	echo $line->efficiency;
    ?>
	</td>
	<?php

	if ($this->statut == 0  && ($object_rights->write) && $action != 'selectlines' ) { ?>
		<td class="linecoledit center"><?php $coldisplay++; ?>
			<?php if (($line->info_bits & 2) == 2 || ! empty($disableedit)) { ?>
			<?php } else { ?>
			<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=editline&amp;lineid='.$line->id.'#line_'.$line->id; ?>">
			<?php echo img_edit(); ?>
			</a>
			<?php } ?>
		</td>

		<td class="linecoldelete center"><?php $coldisplay++; ?>
			<?php
			if (($line->fk_prev_id == null ) && empty($disableremove)) { //La suppression n'est autorisée que si il n'y a pas de ligne dans une précédente situation
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&amp;action=deleteline&amp;lineid=' . $line->id . '">';
				print img_delete();
				print '</a>';
			}
			?>
		</td>

		<?php
		if ($num > 1 && $conf->browser->layout != 'phone' && empty($disablemove)) { ?>
		<td class="linecolmove tdlineupdown center"><?php $coldisplay++; ?>
			<?php if ($i > 0) { ?>
			<a class="lineupdown" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=up&amp;rowid='.$line->id; ?>">
			<?php echo img_up('default', 0, 'imgupforline'); ?>
			</a>
			<?php } ?>
			<?php if ($i < $num-1) { ?>
			<a class="lineupdown" href="<?php echo $_SERVER["PHP_SELF"].'?id='.$this->id.'&amp;action=down&amp;rowid='.$line->id; ?>">
			<?php echo img_down('default', 0, 'imgdownforline'); ?>
			</a>
			<?php } ?>
		</td>
	    <?php } else { ?>
	    <td <?php echo (($conf->browser->layout != 'phone' && empty($disablemove)) ?' class="linecolmove tdlineupdown center"':' class="linecolmove center"'); ?>><?php $coldisplay++; ?></td>
		<?php } ?>
	<?php
    } else {
	?>
		<td colspan="3"><?php $coldisplay=$coldisplay+3; ?></td>
	<?php
    }

    if($action == 'selectlines'){ ?>
		<td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php echo $i+1; ?>]" value="<?php echo $line->id; ?>" ></td>
	<?php } ?>

</tr>

<?php
//Line extrafield
if (!empty($extrafieldsline))
{
	print $line->showOptionals($extrafieldsline, 'view', array('style'=>'class="drag drop oddeven"','colspan'=>$coldisplay), '', '', empty($conf->global->MAIN_EXTRAFIELDS_IN_ONE_TD)?0:1);
}
?>

<!-- END PHP TEMPLATE objectline_view.tpl.php -->
