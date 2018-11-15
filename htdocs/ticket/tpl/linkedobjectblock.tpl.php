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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
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

$langs = $GLOBALS['langs'];
$langs->load('ticket');
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];
echo '<br>';
print_titre($langs->trans('RelatedTickets'));
?>
<table class="noborder" width="100%">
<tr class="liste_titre">
    <td><?php echo $langs->trans("Subject"); ?></td>
    <td align="center"><?php echo $langs->trans("DateCreation"); ?></td>
    <td align="center"><?php echo $langs->trans("Customer"); ?></td>
    <td align="center"><?php echo $langs->trans("Status"); ?></td>
</tr>
<?php
foreach ($linkedObjectBlock as $object) {
?>
<tr class="oddeven">
    <td>
        <a href="<?php echo dol_buildpath("/ticket/card.php", 1).'?track_id='.$object->track_id; ?>">
    <?php echo img_object($langs->trans("ShowTicket"), "ticket") . ' ' . (! empty($object->subject) ? ' '.$object->subject : ''); ?>
        </a>
    </td>
    <td align="center"><?php echo dol_print_date($object->datec, 'day'); ?></td>
    <?php
    $object->socid = $object->fk_soc;
    $object->fetch_thirdparty();
    ?>
    <td align="center"><?php echo $object->thirdparty->getNomUrl(1); ?></td>
    <td align="center"><?php echo $object->getLibstatut(2); ?></td>
</tr>
<?php } ?>
</table>

<!-- END PHP TEMPLATE -->
