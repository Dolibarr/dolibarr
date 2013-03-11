<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent	      <jmenent@2byte.es>
 * Copyright (C) 2011-2012 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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

/**
 *	\file       htdocs/admin/accounting.php
 *	\ingroup    compta
 *	\brief      Page to setup accountancy module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load('admin');
$langs->load('compta');

if (!$user->admin)
accessforbidden();

$action = GETPOST('action','alpha');

$compta_mode = defined('COMPTA_MODE')?COMPTA_MODE:'RECETTES-DEPENSES';

if ($action == 'setcomptamode')
{
	$compta_mode = GETPOST('compta_mode','alpha');

	$res = dolibarr_set_const($db, 'COMPTA_MODE', $compta_mode,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }

}

if ($action == 'setchart')
{
	$chartofaccounts = GETPOST('chartofaccounts','alpha');

	$res = dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}

if ($action == 'update' || $action == 'add')
{
	$constname = GETPOST('constname','alpha');
	$constvalue = GETPOST('constvalue','alpha');
	$consttype = GETPOST('consttype','alpha');
	$constnote = GETPOST('constnote','alpha');

	$res = dolibarr_set_const($db, $constname, $constvalue, $consttype, 0, $constnote, $conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*if ($action == 'delete')
{
	if (! dolibarr_del_const($db, $_GET['constname'],$conf->entity));
	{
		print $db->error();
	}
}*/

/*
 * Affichage page
 */

llxHeader();

$form=new Form($db);

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
if (! empty($conf->global->MAIN_MODULE_COMPTABILITE))
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

/*
 *  Define Chart of accounts
 *
 */
if (! empty($conf->global->ACCOUNTING_SELECTCHART) && ! empty($conf->accounting->enabled))
{
  print '<br>';
  print_titre($langs->trans("Definechartofaccounts"));

  print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';

  print '<table class="noborder" width="100%">';
  $var=True;

  print '<tr class="liste_titre">';
  print '<td>';
  print '<input type="hidden" name="action" value="setchart">';
  print $langs->trans("Chartofaccounts").'</td>';
  print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
  print "</tr>\n";
  $var=!$var;
  print '<tr '.$bc[$var].'>';
  print "<td>".$langs->trans("Selectchartofaccounts")."</td>";
  print "<td>";
  print '<select class="flat" name="chartofaccounts" id="chartofaccounts">';
  print '<option value="0">'.$langs->trans("DoNotSuggestChart").'</option>';

  $sql = "SELECT rowid, pcg_version, fk_pays, label, active";
  $sql.= " FROM ".MAIN_DB_PREFIX."accounting_system";
  $sql.= " WHERE active = 1";
  $sql.= " AND fk_pays = ".$mysoc->country_id;
  $var=True;
  $resql=$db->query($sql);
  if ($resql)
  {
      $num = $db->num_rows($resql);
      $i = 0;
      while ($i < $num)
      {
          $var=!$var;
          $row = $db->fetch_row($resql);

          print '<option value="'.$row[0].'"';
          print $conf->global->CHARTOFACCOUNTS == $row[0] ? ' selected="selected"':'';
          print '>'.$row[1].' - '.$row[3].'</option>';

          $i++;
      }
  }
  print "</select>";
  print "</td></tr>";
  print "</table>";
  print "</form>";
}

print "<br>\n";

// Cas des autres parametres COMPTA_*
$list=array('COMPTA_PRODUCT_BUY_ACCOUNT','COMPTA_PRODUCT_SOLD_ACCOUNT','COMPTA_SERVICE_BUY_ACCOUNT','COMPTA_SERVICE_SOLD_ACCOUNT',
'COMPTA_VAT_ACCOUNT','COMPTA_ACCOUNT_CUSTOMER','COMPTA_ACCOUNT_SUPPLIER'
);

$num=count($list);
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

	print '<form action="accounting.php" method="POST">';
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

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
