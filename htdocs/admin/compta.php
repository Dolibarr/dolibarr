<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/admin/compta.php
 *	\ingroup    compta
 *	\brief      Page to setup accountancy module
 *	\version    $Id: compta.php,v 1.33 2011/07/31 22:23:22 eldy Exp $
 */

require("../main.inc.php");
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
	if (! dolibarr_set_const($db, 'COMPTA_MODE', $compta_mode,'chaine',0,'',$conf->entity)) { print $db->error(); }
	// Note: This setup differs from TAX_MODE.
	// TAX_MODE is used for VAT exigibility only.
}


$form = new Form($db);
$typeconst=array('yesno','texte','chaine');


if ($_POST['action'] == 'update' || $_POST['action'] == 'add')
{
	if (! dolibarr_set_const($db, $_POST['constname'], $_POST['constvalue'], $_POST['consttype'], 0, isset($_POST['constnote']) ? $_POST['constnote'] : '',$conf->entity));
	{
		print $db->error();
	}
}


if ($_GET['action'] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET['constname'],$conf->entity));
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
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setcomptamode">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('OptionMode').'</td><td>'.$langs->trans('Description').'</td>';
print '<td align="right"><input class="button" type="submit" value="'.$langs->trans('Modify').'"></td>';
print "</tr>\n";
print '<tr '.$bc[false].'><td width="200"><input type="radio" name="compta_mode" value="RECETTES-DEPENSES"'.($compta_mode != 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeTrue').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeTrueDesc'));
// Write info on way to count VAT
if ($conf->global->MAIN_MODULE_COMPTABILITE)
{
	//	print "<br>\n";
	//	print nl2br($langs->trans('OptionModeTrueInfoModuleComptabilite'));
}
else
{
	//	print "<br>\n";
	//	print nl2br($langs->trans('OptionModeTrueInfoExpert'));
}
print "</td></tr>\n";
print '<tr '.$bc[true].'><td width="200"><input type="radio" name="compta_mode" value="CREANCES-DETTES"'.($compta_mode == 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeVirtual').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeVirtualDesc'))."</td></tr>\n";
print '</form>';

print "</table>\n";

print "<br>\n";

// Cas des autres parametres COMPTA_*
$list=array('COMPTA_PRODUCT_BUY_ACCOUNT','COMPTA_PRODUCT_SOLD_ACCOUNT','COMPTA_SERVICE_BUY_ACCOUNT','COMPTA_SERVICE_SOLD_ACCOUNT',
'COMPTA_VAT_ACCOUNT','COMPTA_ACCOUNT_CUSTOMER','COMPTA_ACCOUNT_SUPPLIER'
);

/*$sql = "SELECT rowid, name, value, type, note";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
$sql.= " WHERE name LIKE 'COMPTA_%'";
$sql.= " AND name NOT IN ('COMPTA_MODE')";
$sql.= " AND entity = ".$conf->entity;
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;
		$list[$obj->name]=$obj->value;
		$i++;
	}
}*/

$num=sizeof($list);

if ($num)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans('OtherOptions').'</td>';
	print "</tr>\n";
}

foreach ($list as $key)
{
	$var=!$var;

	print '<form action="compta.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="consttype" value="string">';
	print '<input type="hidden" name="constname" value="'.$key.'">';
	
	print '<tr '.$bc[$var].' class="value">';

	// Param
	$libelle = $langs->trans($key); 
	print '<td>'.$libelle;
	//print ' ('.$key.')';
	print "</td>\n";

	// Value
	print '<td>';
	print '<input type="text" size="20" name="constvalue" value="'.$conf->global->$key.'">';
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


$db->close();


llxFooter('$Date: 2011/07/31 22:23:22 $ - $Revision: 1.33 $');
?>
