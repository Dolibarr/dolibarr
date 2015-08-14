<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
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
 *	    \file       htdocs/user/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

$langs->load("agenda");
$langs->load("admin");
$langs->load("other");

$def = array();
$actiontest=GETPOST('test','alpha');
$actionsave=GETPOST('save','alpha');

if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
$MAXAGENDA=empty($conf->global->AGENDA_EXT_NB)?5:$conf->global->AGENDA_EXT_NB;

// List of available colors
$colorlist=array('BECEDD','DDBECE','BFDDBE','F598B4','F68654','CBF654','A4A4A5');

// Security check
$id = GETPOST('id','int');
$fuser = new User($db);
$fuser->fetch($id);

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id)	// A user can always read its own card
{
	$feature2='';
}
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// If user is not user that read and no permission to read other users, we stop
if (($fuser->id != $user->id) && (! $user->rights->user->user->lire))
  accessforbidden();



/*
 * Actions
 */
if ($actionsave)
{
    $db->begin();

	$i=1; $errorsaved=0;
	$error=0;
	$tabparam=array();

	// Save agendas
	while ($i <= $MAXAGENDA)
	{
		$name=trim(GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$i,'alpha'));
		$src=trim(GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$i,'alpha'));
		$offsettz=trim(GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$i,'alpha'));
		$color=trim(GETPOST('AGENDA_EXT_COLOR_'.$id.'_'.$i,'alpha'));
		if ($color=='-1') $color='';
		$enabled=trim(GETPOST('AGENDA_EXT_ENABLED_'.$id.'_'.$i,'alpha'));

		if (! empty($src) && ! dol_is_url($src))
		{
			setEventMessage($langs->trans("ErrorParamMustBeAnUrl"),'errors');
			$error++;
			$errorsaved++;
			break;
		}

		$tabparam['AGENDA_EXT_NAME_'.$id.'_'.$i]=$name;
		$tabparam['AGENDA_EXT_SRC_'.$id.'_'.$i]=$src;
		$tabparam['AGENDA_EXT_OFFSETTZ_'.$id.'_'.$i]=$offsettz;
		$tabparam['AGENDA_EXT_COLOR_'.$id.'_'.$i]=$color;
		$tabparam['AGENDA_EXT_ENABLED_'.$id.'_'.$i]=$enabled;

		$i++;
	}

	if (! $error)
	{
		$result=dol_set_user_param($db, $conf, $fuser, $tabparam);
		if (! $result > 0) $error++;
	}

    if (! $error)
    {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        $db->rollback();
        if (empty($errorsaved))	setEventMessage($langs->trans("Error"),'errors');
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

llxHeader('',$langs->trans("UserSetup"),'','',0,0,$arrayofjs,$arrayofcss);


print '<form name="extsitesconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="id" value="'.$id.'">';

$head=user_prepare_head($fuser);

dol_fiche_head($head, 'extsites', $langs->trans("User"), 0, 'user');

print $langs->trans("AgendaExtSitesDesc")."<br>\n";
print "<br>\n";

$selectedvalue=$conf->global->AGENDA_DISABLE_EXT;
if ($selectedvalue==1) $selectedvalue=0; else $selectedvalue=1;

$var=true;
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("ExtSiteUrlAgenda")." (".$langs->trans("Example").': http://yoursite/agenda/agenda.ics)</td>';
print "<td>".$form->textwithpicto($langs->trans("FixTZ"), $langs->trans("FillFixTZOnlyIfRequired"), 1).'</td>';
print '<td align="right">'.$langs->trans("Color").'</td>';
print "</tr>";

$i=1;
$var=true;
while ($i <= $MAXAGENDA)
{
	$key=$i;
	$name='AGENDA_EXT_NAME_'.$id.'_'.$key;
	$src='AGENDA_EXT_SRC_'.$id.'_'.$key;
	$offsettz='AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key;
	$color='AGENDA_EXT_COLOR_'.$id.'_'.$key;

	$var=!$var;
	print "<tr ".$bc[$var].">";
	// Nb
	print '<td width="180" class="nowrap">'.$langs->trans("AgendaExtNb",$key)."</td>";
	// Name
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_NAME_'.$id.'_'.$key.'" value="'. (GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$key)?GETPOST('AGENDA_EXT_NAME_'.$id.'_'.$key):$fuser->conf->$name) . '" size="28"></td>';
	// URL
	print '<td><input type="url" class="flat hideifnotset" name="AGENDA_EXT_SRC_'.$id.'_'.$key.'" value="'. (GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$key)?GETPOST('AGENDA_EXT_SRC_'.$id.'_'.$key):$fuser->conf->$src) . '" size="60"></td>';
	// Offset TZ
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key.'" value="'. (GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key)?GETPOST('AGENDA_EXT_OFFSETTZ_'.$id.'_'.$key):$fuser->conf->$offsettz) . '" size="2"></td>';
	// Color (Possible colors are limited by Google)
	print '<td class="nowrap" align="right">';
	//print $formadmin->selectColor($conf->global->$color, "google_agenda_color".$key, $colorlist);
	print $formother->selectColor((GETPOST("AGENDA_EXT_COLOR_".$id.'_'.$key)?GETPOST("AGENDA_EXT_COLOR_".$id.'_'.$key):$fuser->conf->$color), "AGENDA_EXT_COLOR_".$id.'_'.$key, 'extsitesconfig', 1, '', 'hideifnotset');
	print '</td>';
	print "</tr>";
	$i++;
}

print '</table>';

dol_fiche_end();

print '<div class="center">';
print "<input type=\"submit\" id=\"save\" name=\"save\" class=\"button hideifnotset\" value=\"".$langs->trans("Save")."\">";
print "</div>";

print "</form>\n";


llxFooter();

$db->close();
