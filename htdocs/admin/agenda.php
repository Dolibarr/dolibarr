<?php
/* Copyright (C) 2008-2010	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2011-2012  Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/agenda.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");

$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');

// Get list of triggers available
$sql = "SELECT a.rowid, a.code, a.label, a.elementtype";
$sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a";
$sql.= " ORDER BY a.rang ASC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$triggers[$i]['rowid'] 		= $obj->rowid;
		$triggers[$i]['code'] 		= $obj->code;
		$triggers[$i]['element'] 	= $obj->elementtype;
		$triggers[$i]['label']		= ($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);

		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


/*
*	Actions
*/
if ($action == "save" && empty($cancel))
{
    $i=0;

    $db->begin();

	foreach ($triggers as $trigger)
	{
		$param='MAIN_AGENDA_ACTIONAUTO_'.$trigger['code'];
		//print "param=".$param." - ".$_POST[$param];
		if (GETPOST($param,'alpha')) $res = dolibarr_set_const($db,$param,GETPOST($param,'alpha'),'chaine',0,'',$conf->entity);
		else $res = dolibarr_del_const($db,$param,$conf->entity);
		if (! $res > 0) $error++;
	}

 	if (! $error)
    {
    	$db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
    	$db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}



/**
 * Affichage du formulaire de saisie
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print "<br>\n";

print $langs->trans("AgendaAutoActionDesc")."<br>\n";
print "<br>\n";

$head=agenda_prepare_head();

dol_fiche_head($head, 'autoactions', $langs->trans("Agenda"));


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("ActionsEvents").'</td>';
print '<td><a href="'.$_SERVER["PHP_SELF"].'?action=selectall">'.$langs->trans("All").'</a>/<a href="'.$_SERVER["PHP_SELF"].'?action=selectnone">'.$langs->trans("None").'</a>';
print '</tr>'."\n";
if (! empty($triggers))
{
	foreach ($triggers as $trigger)
	{
		$module = $trigger['element'];
		if ($module == 'order_supplier' || $module == 'invoice_supplier') $module = 'fournisseur';
		if ($module == 'shipping') $module = 'expedition_bon';
		if ($module == 'member') $module = 'adherent';
		//print 'module='.$module.'<br>';
		if ($conf->$module->enabled)
		{
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td>'.$trigger['code'].'</td>';
			print '<td>'.$trigger['label'].'</td>';
			print '<td align="right" width="40">';
			$key='MAIN_AGENDA_ACTIONAUTO_'.$trigger['code'];
			$value=$conf->global->$key;
			print '<input '.$bc[$var].' type="checkbox" name="'.$key.'" value="1"'.((($action=='selectall'||$value) && $action!="selectnone")?' checked="checked"':'').'>';
			print '</td></tr>'."\n";
		}
	}
}
print '</table>';

print '<br><center>';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print ' &nbsp; &nbsp; ';
print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
print "</center>";

print "</form>\n";

print '</div>';

print "<br>";

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
