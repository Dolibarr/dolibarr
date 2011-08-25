<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011 	   Juanjo Menent        <jmenent@2byte.es>
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
 *	    \file       htdocs/admin/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 *      \version    $Id: agenda_extsites.php,v 1.9 2011/07/31 22:23:21 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

if (!$user->admin)
    accessforbidden();

$langs->load("agenda");
$langs->load("admin");
$langs->load("other");

$def = array();
$actiontest=GETPOST("test");
$actionsave=GETPOST("save");

if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
$MAXAGENDA=empty($conf->global->AGENDA_EXT_NB)?5:$conf->global->AGENDA_EXT_NB;

// List of aviable colors
$colorlist=array('BECEDD','DDBECE','BFDDBE','F598B4','F68654','CBF654','A4A4A5');

/*
 * Actions
 */
if ($actionsave)
{
    $db->begin();

    $disableext=GETPOST("AGENDA_DISABLE_EXT");
    if ($disableext) $disableext=0; else $disableext=1;
	$res=dolibarr_set_const($db,'AGENDA_DISABLE_EXT',$disableext,'chaine',0);

	$i=1;
	$error=0;

	// Save agendas
	while ($i <= $MAXAGENDA)
	{
		$color=trim(GETPOST("agenda_ext_color".$i));
		if ($color=='-1') $color='';

		//print 'color='.$color;
		$res=dolibarr_set_const($db,'AGENDA_EXT_NAME'.$i,trim(GETPOST("agenda_ext_name".$i)),'chaine',0);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_SRC'.$i,trim(GETPOST("agenda_ext_src".$i)),'chaine',0);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_COLOR'.$i,$color,'chaine',0);
		if (! $res > 0) $error++;
		$i++;
	}
	// Save nb of agenda
	$res=dolibarr_set_const($db,'AGENDA_EXT_NB',trim(GETPOST("AGENDA_EXT_NB")),'chaine',0);
	if (! $res > 0) $error++;
	if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
	$MAXAGENDA=empty($conf->global->AGENDA_EXT_NB)?5:$conf->global->AGENDA_EXT_NB;

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

/*
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);
$formother=new FormOther($db);

//$arrayofjs=array('/includes/jquery/plugins/colorpicker/jquery.colorpicker.js');
//$arrayofcss=array('/includes/jquery/plugins/colorpicker/jquery.colorpicker.css');
$arrayofjs=array();
$arrayofcss=array();

llxHeader('',$langs->trans("AgendaSetup"),'','',0,0,$arrayofjs,$arrayofcss);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print '<br>';

print $langs->trans("AgendaExtSitesDesc")."<br>\n";
print "<br>\n";

$head=agenda_prepare_head();

dol_fiche_head($head, 'extsites', $langs->trans("Agenda"));

print '<form name="extsitesconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';

$selectedvalue=(GETPOST("AGENDA_DISABLE_AGENDA"))?GETPOST("AGENDA_DISABLE_EXT"):$conf->global->AGENDA_DISABLE_EXT;
if ($selectedvalue==1) $selectedvalue=0; else $selectedvalue=1;
print $langs->trans("ExtSitesEnableThisTool").' '.$form->selectyesno("AGENDA_DISABLE_EXT",$selectedvalue,1).'<br><br>';

$var=false;
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print '<td width="180">'.$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "</tr>";

// Nb of agenda
print "<tr ".$bc[$var].">";
print "<td>".$langs->trans("ExtSitesNbOfAgenda")."</td>";
print "<td>";
print '<input class="flat" type="text" size="2" name="AGENDA_EXT_NB" value="'.$conf->global->AGENDA_EXT_NB.'">';
print "</td>";
print "</tr>";

print "</table>";
print "<br>";

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("ExtSiteUrlAgenda")." (".$langs->trans("Example").': http://yoursite/agenda/agenda.ics)</td>';
print '<td align="center">'.$langs->trans("Color").'</td>';
print "</tr>";

$i=1;
$var=true;
while ($i <= $MAXAGENDA)
{
	$key=$i;
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="180" nowrap="nowrap">'.$langs->trans("AgendaExtNb",$key)."</td>";
	$name='AGENDA_EXT_NAME'.$key;
	$src='AGENDA_EXT_SRC'.$key;
	$color='AGENDA_EXT_COLOR'.$key;
	print "<td><input type=\"text\" class=\"flat\" name=\"agenda_ext_name".$key."\" value=\"". $conf->global->$name . "\" size=\"28\"></td>";
	print "<td><input type=\"text\" class=\"flat\" name=\"agenda_ext_src".$key."\" value=\"". $conf->global->$src . "\" size=\"60\"></td>";
	print '<td nowrap="nowrap" align="center">';
	// Possible colors are limited by Google
	//print $formadmin->select_colors($conf->global->$color, "google_agenda_color".$key, $colorlist);
	print $formother->select_color($conf->global->$color, "agenda_ext_color".$key, 'extsitesconfig', 1, '');
	print '</td>';
	print "</tr>";
	$i++;
}

print '</table>';
print '<br>';

print '<center>';

print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";

dol_fiche_end();

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.9 $');
?>