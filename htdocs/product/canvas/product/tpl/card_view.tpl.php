<?php
/* Copyright (C) 2010-2018 Regis Houssin <regis.houssin@inodbox.com>
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


$object=$GLOBALS['object'];
?>

<!-- BEGIN PHP TEMPLATE -->
<?php
$head=product_prepare_head($object);
$titre=$langs->trans("CardProduct".$object->type);

dol_fiche_head($head, 'card', $titre, -1, 'product');

$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter=" fk_product_type = ".$object->type;

$shownav = 1;
if ($user->societe_id && ! in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');
?>

<?php dol_htmloutput_errors($object->error, $object->errors); ?>

<table class="border allwidth">

<tr>
<td width="15%"><?php echo $langs->trans("Ref"); ?></td>
<td colspan="2"><?php echo $object->ref; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Label") ?></td>
<td><?php echo $object->label; ?></td>

<?php if ($object->photos) { ?>
<td valign="middle" align="center" width="30%" rowspan="<?php echo $object->nblignes; ?>">
<?php echo $object->photos; ?>
</td>
<?php } ?>

</tr>

<tr>
<td class="tdtop"><?php echo $langs->trans("Description"); ?></td>
<td colspan="2"><?php echo $object->description; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Nature"); ?></td>
<td colspan="2"><?php echo $object->finished; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Weight"); ?></td>
<td colspan="2"><?php echo $object->weight; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Length"); ?></td>
<td colspan="2"><?php echo $object->length; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Surface"); ?></td>
<td colspan="2"><?php echo $object->surface; ?></td>
</tr>

<tr>
<td><?php echo $langs->trans("Volume"); ?></td>
<td colspan="2"><?php echo $object->volume; ?></td>
</tr>

<tr>
<td class="tdtop"><?php echo $langs->trans("Note"); ?></td>
<td colspan="2"><?php echo $object->note; ?></td>
</tr>

</table>

<!-- END PHP TEMPLATE -->