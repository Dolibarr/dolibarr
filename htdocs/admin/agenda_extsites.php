<?php
/* Copyright (C) 2008-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2015	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *	    \file       htdocs/admin/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

if (!$user->admin) accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'admin', 'other'));

$def = array();
$actiontest=GETPOST('test','alpha');
$actionsave=GETPOST('save','alpha');

if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
$MAXAGENDA=$conf->global->AGENDA_EXT_NB;

// List of aviable colors
$colorlist=array('BECEDD','DDBECE','BFDDBE','F598B4','F68654','CBF654','A4A4A5');


/*
 * Actions
 */

if ($actionsave)
{
    $db->begin();

    $disableext=GETPOST('AGENDA_DISABLE_EXT','alpha');
	$res=dolibarr_set_const($db,'AGENDA_DISABLE_EXT',$disableext,'chaine',0,'',$conf->entity);

	$i=1; $errorsaved=0;
	$error=0;

	// Save agendas
	while ($i <= $MAXAGENDA)
	{
		$name=trim(GETPOST('AGENDA_EXT_NAME'.$i,'alpha'));
		$src=trim(GETPOST('AGENDA_EXT_SRC'.$i,'alpha'));
		$offsettz=trim(GETPOST('AGENDA_EXT_OFFSETTZ'.$i,'alpha'));
		$color=trim(GETPOST('AGENDA_EXT_COLOR'.$i,'alpha'));
		if ($color=='-1') $color='';
		$enabled=trim(GETPOST('AGENDA_EXT_ENABLED'.$i,'alpha'));

		if (! empty($src) && ! dol_is_url($src))
		{
			setEventMessages($langs->trans("ErrorParamMustBeAnUrl"), null, 'errors');
			$error++;
			$errorsaved++;
			break;
		}

		//print '-name='.$name.'-color='.$color;
		$res=dolibarr_set_const($db,'AGENDA_EXT_NAME'.$i,$name,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_SRC'.$i,$src,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_OFFSETTZ'.$i,$offsettz,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_COLOR'.$i,$color,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$res=dolibarr_set_const($db,'AGENDA_EXT_ENABLED'.$i,$enabled,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		$i++;
	}

	// Save nb of agenda
	if (! $error)
	{
		$res=dolibarr_set_const($db,'AGENDA_EXT_NB',trim(GETPOST('AGENDA_EXT_NB','int')),'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
		$MAXAGENDA=empty($conf->global->AGENDA_EXT_NB)?5:$conf->global->AGENDA_EXT_NB;
	}

    if (! $error)
    {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
        if (empty($errorsaved))	setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);
$formother=new FormOther($db);

$arrayofjs=array();
$arrayofcss=array();

$wikihelp='EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda';
llxHeader('',$langs->trans("AgendaSetup"),$wikihelp,'',0,0,$arrayofjs,$arrayofcss);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"),$linkback,'title_setup');

print '<form name="extsitesconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

$head=agenda_prepare_head();

dol_fiche_head($head, 'extsites', $langs->trans("Agenda"), -1, 'action');

print $langs->trans("AgendaExtSitesDesc")."<br>\n";
print "<br>\n";


$selectedvalue=$conf->global->AGENDA_DISABLE_EXT;
if ($selectedvalue==1) $selectedvalue=0; else $selectedvalue=1;

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print '<td>'.$langs->trans("Parameter")."</td>";
print '<td align="center">'.$langs->trans("Value")."</td>";
print "</tr>";

// Show external agenda

print '<tr class="oddeven">';
print "<td>".$langs->trans("ExtSitesEnableThisTool")."</td>";
print '<td align="center">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('AGENDA_DISABLE_EXT',array('enabled'=>array(0=>'.hideifnotset')),null,1);
}
else
{
	if (empty($conf->global->AGENDA_DISABLE_EXT))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?save=1&AGENDA_DISABLE_EXT=1">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?save=1&AGENDA_DISABLE_EXT=0">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Nb of agenda

print '<tr class="oddeven">';
print "<td>".$langs->trans("ExtSitesNbOfAgenda")."</td>";
print '<td align="center">';
print '<input class="flat hideifnotset" type="text" size="2" id="AGENDA_EXT_NB" name="AGENDA_EXT_NB" value="'.$conf->global->AGENDA_EXT_NB.'">';
print "</td>";
print "</tr>";

print "</table>";
print "<br>";

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("ExtSiteUrlAgenda")." (".$langs->trans("Example").': http://yoursite/agenda/agenda.ics)</td>';
print "<td>".$form->textwithpicto($langs->trans("FixTZ"), $langs->trans("FillFixTZOnlyIfRequired"), 1).'</td>';
print '<td align="right">'.$langs->trans("Color").'</td>';
print "</tr>";

$i=1;
while ($i <= $MAXAGENDA)
{
	$key=$i;
	$name='AGENDA_EXT_NAME'.$key;
	$src='AGENDA_EXT_SRC'.$key;
	$offsettz='AGENDA_EXT_OFFSETTZ'.$key;
	$color='AGENDA_EXT_COLOR'.$key;
	$enabled='AGENDA_EXT_ENABLED'.$key;


	print '<tr class="oddeven">';
	// Nb
	print '<td width="180" class="nowrap">'.$langs->trans("AgendaExtNb",$key)."</td>";
	// Name
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_NAME'.$key.'" value="'. (GETPOST('AGENDA_EXT_NAME'.$key)?GETPOST('AGENDA_EXT_NAME'.$key, 'alpha'):$conf->global->$name) . '" size="28"></td>';
	// URL
	print '<td><input type="url" class="flat hideifnotset" name="AGENDA_EXT_SRC'.$key.'" value="'. (GETPOST('AGENDA_EXT_SRC'.$key)?GETPOST('AGENDA_EXT_SRC'.$key, 'alpha'):$conf->global->$src) . '" size="60"></td>';
	// Offset TZ
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_OFFSETTZ'.$key.'" value="'. (GETPOST('AGENDA_EXT_OFFSETTZ'.$key)?GETPOST('AGENDA_EXT_OFFSETTZ'.$key):$conf->global->$offsettz) . '" size="2"></td>';
	// Color (Possible colors are limited by Google)
	print '<td class="nowrap" align="right">';
	//print $formadmin->selectColor($conf->global->$color, "google_agenda_color".$key, $colorlist);
	print $formother->selectColor((GETPOST("AGENDA_EXT_COLOR".$key)?GETPOST("AGENDA_EXT_COLOR".$key):$conf->global->$color), "AGENDA_EXT_COLOR".$key, 'extsitesconfig', 1, '', 'hideifnotset');
	print '</td>';
	print "</tr>";
	$i++;
}

print '</table>';

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" id="save" name="save" class="button hideifnotset" value="'.$langs->trans("Save").'">';
print '</div>';

print "</form>\n";

// End of page
llxFooter();
$db->close();
