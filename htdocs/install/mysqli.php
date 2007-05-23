<?php
/* Copyright (C) 2004-2007 Cyrille de Lambert <cyrille.delambert@auguria.net>
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
 * $Source$
 */

	$listOfCharacterSet=$db->getListOfCharacterSet();
	$listOfCollation=$db->getListOfCollation();
?>
<tr>
	<td valign="top" class="label"><?php echo $langs->trans("CharacterSetDatabase"); ?></td>
	<td valign="top" class="label">
	<?php
	$listOfCharacterSet=$db->getListOfCharacterSet();
	?>
	<select name="character_set_database" <?php echo $disabled ?>>
	<?php
	$selected="";
	foreach ($listOfCharacterSet as $characterSet) {
		if ($db->getDefaultCharacterSetDatabase() ==$characterSet['charset'] ){
			$selected="selected";
		}else{
			$selected="";
		}
	?>
	<option value="<?php echo $characterSet['charset'];?>"  <?php echo $selected;?>> <?php echo $characterSet['charset'];?> (<?php echo $characterSet['description'];?>)</option>
	<?php
	}
	?>
		</select>
	</td>
	<td class="label"><div class="comment"><?php echo $langs->trans("CharacterSetDatabaseComment"); ?></div></td>
</tr>
<tr>
	<td valign="top" class="label"><?php echo $langs->trans("CollationConnection"); ?></td>
	<td valign="top" class="label">
	<?php
	$listOfCollation=$db->getListOfCollation();
	?>
	<select name="collation_connection" <?php echo $disabled ?>>
	<?php
	$selected="";
	foreach ($listOfCollation as $collation) {
		if ($db->getDefaultCollationConnection() ==$collation['collation'] ){
			$selected="selected";
		}else{
			$selected="";
		}
	?>
	<option value="<?php echo $collation['collation'];?>"  <?php echo $selected;?>> <?php echo $collation['collation'];?></option>
	<?php
	}
	?>
		</select>
		<?if ($disabled && $disabled=="disabled"){
				?>
			<input type="hidden" name="character_set_database"  value="<?php echo $db->getDefaultCharacterSetDatabase() ?>">
			<input type="hidden" name="collation_connection"  value="<?php echo $db->getDefaultCollationConnection() ?>">
			<?
	}
			?>
	</td>
	<td class="label"><div class="comment"><?php echo $langs->trans("CollationConnectionComment"); ?></div></td>
</tr>