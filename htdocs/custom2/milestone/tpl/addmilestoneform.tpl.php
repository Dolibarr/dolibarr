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

<script type="text/javascript" language="javascript">
jQuery(document).ready(function () {
	jQuery("#milestone_label").focus(function() {
		hideMessage("milestone_label","<?php echo $langs->transnoentities('Label'); ?>");
    });
    jQuery("#milestone_label").blur(function() {
        displayMessage("milestone_label","<?php echo $langs->transnoentities('Label'); ?>");
    });
	jQuery("#milestone_desc").focus(function() {
		hideMessage("milestone_desc","<?php echo $langs->transnoentities('Description'); ?>");
	});
	jQuery("#milestone_desc").blur(function() {
		displayMessage("milestone_desc","<?php echo $langs->transnoentities('Description'); ?>");
	});
	displayMessage("milestone_label","<?php echo $langs->transnoentities('Label'); ?>");
	displayMessage("milestone_desc","<?php echo $langs->transnoentities('Description'); ?>");
	jQuery("#milestone_label").css("color","grey");
	jQuery("#milestone_desc").css("color","grey");
})
</script>

<tr class="liste_titre nodrag nodrop">
	<td><?php echo $langs->trans('AddMilestone'); ?></td>
	<td colspan="10">&nbsp;</td>
</tr>

<form name="addmilestone" id="addmilestone" action="<?php echo $_SERVER["PHP_SELF"].'?id='.$GLOBALS['object']->id; ?>" method="POST">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>">
<input type="hidden" name="action" value="addmilestone">
<input type="hidden" name="id" value="<?php echo $GLOBALS['object']->id; ?>">
<input type="hidden" name="special_code" value="1790">
<input type="hidden" name="product_type" value="9">

<tr <?php echo $GLOBALS['bcnd'][$GLOBALS['var']]; ?>>
	<td colspan="5">
	<input size="30" type="text" id="milestone_label" name="milestone_label" value="<?php echo $_POST["milestone_label"]; ?>">
	</td>
		
	<td align="center" valign="middle" rowspan="2" colspan="4">
	<input type="submit" class="button" value="<?php echo $langs->trans('Add'); ?>" name="addmilestone">
	</td>
</tr>

<tr <?php echo $GLOBALS['bcnd'][$GLOBALS['var']]; ?>>
	<td colspan="5">
	
	<?php
	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
    $nbrows=ROWS_2;
    if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
	$doleditor=new DolEditor('milestone_desc',$_POST["milestone_desc"],'',100,'dolibarr_details','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
	$doleditor->Create();
	?>
	</td>
</tr>

</form>

<!-- END PHP TEMPLATE -->
