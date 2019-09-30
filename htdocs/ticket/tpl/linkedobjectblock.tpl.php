<?php
/* Copyright (C) 2010-2012 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Jean-François FERRY <hello@librethic.io>
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
?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;
global $noMoreLinkedObjectBlockAfter;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load('ticket');

$linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'datec', 'desc', 0, 0, 1);

$total=0; $ilink=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
	$ilink++;

	$trclass='oddeven';
	if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass.=' liste_sub_total';
?>
    <tr class="<?php echo $trclass; ?>" >
        <td class="linkedcol-element" ><?php echo $langs->trans("Ticket"); ?>
        <?php if(!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) print '<a class="objectlinked_importbtn" href="'.$objectlink->getNomUrl(0, '', 0, 1).'&amp;action=selectlines"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a';  ?>
        </td>
        <td class="linkedcol-name" ><?php echo $objectlink->getNomUrl(1); ?></td>
    	<td class="linkedcol-ref" align="center"><?php echo $objectlink->ref_client; ?></td>
    	<td class="linkedcol-date" align="center"><?php echo dol_print_date($objectlink->datec, 'day'); ?></td>
	    <?php
	    //$objectlink->socid = $objectlink->fk_soc;
	    //$objectlink->fetch_thirdparty();
	    ?>
    	<td class="linkedcol-amount right"><?php //echo $objectlink->thirdparty->getNomUrl(1); ?></td>
    	<td class="linkedcol-statut right"><?php echo $objectlink->getLibStatut(3); ?></td>
    	<td class="linkedcol-action right">
    		<?php
    		// For now, shipments must stay linked to order, so link is not deletable
    		if($object->element != 'shipping') {
    			?>
    			<a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a>
    			<?php
    		}
    		?>
    	</td>
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
    	<td class="right"><?php echo price($total); ?></td>
    	<td class="right"></td>
    	<td class="right"></td>
    </tr>
    <?php
}
?>

<!-- END PHP TEMPLATE -->
