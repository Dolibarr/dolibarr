<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/admin/compta.php
        \ingroup    comptabilite
        \brief      Page de configuration du module comptabilit�
		\version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load('admin');
$langs->load('compta');

if (!$user->admin)
  accessforbidden();


llxHeader();


$compta_mode = defined('COMPTA_MODE')?COMPTA_MODE:'RECETTES-DEPENSES';

if ($_POST['action'] == 'setcomptamode')
{
  $compta_mode = $_POST['compta_mode'];
  if (! dolibarr_set_const($db, 'COMPTA_MODE', $compta_mode)) { print $db->error(); }
}


$form = new Form($db);
$typeconst=array('yesno','texte','chaine');


if ($_POST['action'] == 'update' || $_POST['action'] == 'add')
{
	if (! dolibarr_set_const($db, $_POST['constname'], $_POST['constvalue'], $typeconst[$_POST['consttype']], 0, isset($_POST['constnote']) ? $_POST['constnote'] : ''));
	{
	  	print $db->error();
	}
}


if ($_GET['action'] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET['constname']));
	{
	  	print $db->error();
	}
}

/*
 * Affichage page
 */

$html=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans('ComptaSetup'),$linkback,'setup');


print '<br>';

print '<table class="noborder" width="100%">';

// Cas du parametre COMPTA_MODE
print '<form action="compta.php" method="post">';
print '<input type="hidden" name="action" value="setcomptamode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('OptionMode').'</td><td>'.$langs->trans('Description').'</td>';
print '<td><input class="button" type="submit" value="'.$langs->trans('Modify').'"></td>';
print "</tr>\n";
print '<tr '.$bc[false].'><td width="200"><input type="radio" name="compta_mode" value="RECETTES-DEPENSES"'.($compta_mode != 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeTrue').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeTrueDesc'))."</td></tr>\n";
print '<tr '.$bc[true].'><td width="200"><input type="radio" name="compta_mode" value="CREANCES-DETTES"'.($compta_mode == 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeVirtual').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeVirtualDesc'))."</td></tr>\n";
print '</form>';

print "</table>\n";

print "<br>\n";

// Cas des autres parametres COMPTA_*
$sql ="SELECT rowid, name, value, type, note";
$sql.=" FROM llx_const";
$sql.=" WHERE name like 'COMPTA_%' and name not in ('COMPTA_MODE')";
$result = $db->query($sql);
if ($result) 
{
	$num = $db->num_rows($result);
	$i = 0;
	$var=true;

	if ($num)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans('OtherOptions').'</td>';
		print "</tr>\n";
	}

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print '<form action="compta.php" method="POST">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="constname" value="'.$obj->name.'">';

		print '<tr '.$bc[$var].' class="value">';
		
		// Param
		print '<td>'.stripslashes(nl2br($obj->note));
		print $obj->name;
		print "</td>\n";
		
		// Value
		print '<td>';
		if ($obj->type == 'yesno')
		{
			print $form->selectyesno('constvalue',$obj->value,1);
		}
		elseif ($obj->type == 'texte')
		{
			print '<textarea name="constvalue" cols="35" rows="5" wrap="soft">';
			print $obj->value;
			print "</textarea>\n";
		}
		else
		{
			print '<input type="text" size="30" name="constvalue" value="'.stripslashes($obj->value).'">';
		}
		print '</td><td>';
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"> &nbsp; ';
		print "</td></tr>\n";

		print '</form>';

		$i++;
	}
    
	if ($num)
	{
		print "</table>\n";
	}

}

$db->close();


llxFooter('$Date$ - $Revision$');

?>
