<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
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
 */

require "./pre.inc.php";
$user->getrights();

if (!$user->rights->categorie->lire)
  accessforbidden();
  
llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("ModifCat"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

$categorie = new Categorie ($db, $_REQUEST['id']);
$html = new Form($db);


// Action mise à jour d'une catégorie
if ($_POST["action"] == 'update' && $user->rights->categorie->creer)
{

	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	if($_POST['catMere'] != "-1")
		$categorie->id_mere = $_POST['catMere'];
	else
		$categorie->id_mere = "";
	

	if (!$categorie->label || !$categorie->description)
	{
		$_GET["action"] = 'create';
		$categorie->error = "Le libellé ou la description n'a pas été renseigné";
	}
	if ($categorie->error =="")
	{
		if ($categorie->update() > 0)
		{
			$_GET["action"] = 'confirmed';
			$_POST["addcat"] = '';
		}
	}
}
if($categorie->error != "")
{
			print '<div class="error">';
			print $categorie->error;
			print '</div>';
}
print '<tr><td valign="top" width="30%">';
?>
<form method="post" action="<?php print $_SERVER['REQUEST_URI']; ?>">
<input type="hidden" name="action" value="update">
<table class="border">
	<tr>
		<td><?php print $langs->trans("Label"); ?>&nbsp;:</td>

		<td><input type='text' size='25' id='nom' name ='nom' value="<?php 
				print $categorie->label; 
			?>" />
	</tr>
	<tr>
		<td><?php print $langs->trans("Description"); ?>&nbsp;:</td>
		
		<td><textarea name='description' cols='40' rows='6' id='description'><?php
				print $categorie->description; 
		?></textarea></td>
		
	</tr>
	
	
	<tr><td><?php print $langs->trans ("AddIn"); ?></td><td>
	<?php print $html->select_all_categories($categorie->id_mere);?>
	</td></tr>
		
	
	<tr>
		<td colspan="2" align="center"><input type='submit' value='<?php print $langs->trans ("Modify"); ?>'></td>
	</tr>
</table>
</form>
		
	<?php


print '</td></tr></table>';

$db->close();
?>
