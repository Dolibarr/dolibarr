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
?>
<tr>
	<td valign="top" class="label"><?php echo $langs->trans("CharacterSetDatabase"); ?></td>
	<td valign="top" class="label">
		<input name="character_set_database" <?php echo $disabled ?> value="<?php echo $db->getDefaultCharacterSetDatabase()?>">
	</td>
	<td class="label"><div class="comment"><?php echo $langs->trans("CharacterSetDatabaseComment"); ?></div></td>
</tr>