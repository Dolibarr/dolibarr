<?php
/* Copyright (C) 2012-2103 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@capnetworks.com>
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
 * 	Page module configuration paid holiday.
 *
 *  \file       holiday.php
 *	\ingroup    holiday
 *	\brief      Page module configuration paid holiday.
 */

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT. '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/usergroup.class.php';

$action=GETPOST('action');
$optName=GETPOST('optName');
$optValue=GETPOST('optValue');

$langs->load("admin");
$langs->load("holiday");

// Si pas administrateur
if (! $user->admin) accessforbidden();


/*
 * View
 */

// Vérification si module activé
if (empty($conf->holiday->enabled)) print $langs->trans('NotActiveModCP');

llxheader('',$langs->trans('TitleAdminCP'));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans('ConfCP'), $linkback);

$cp = new Holiday($db);

// Contrôle du formulaire
if ($action == "add")
{
    $message = '';
    $error = false;

    // Option du groupe de validation
    /*if (!$cp->updateConfCP('userGroup',$_POST['userGroup']))
    {
        $error = true;
    }*/

    // Option du délai pour faire une demande de congés payés
    if (!$cp->updateConfCP('delayForRequest',$_POST['delayForRequest']))
    {
        $error = true;
    }

    // Option du nombre de jours à ajouter chaque mois
    $nbHolidayEveryMonth = price2num($_POST['nbHolidayEveryMonth'],5);

    if(!$cp->updateConfCP('nbHolidayEveryMonth',$nbHolidayEveryMonth))
    {
        $error = true;
    }

    // Option du nombre de jours pour un mariage
    $OptMariageCP = price2num($_POST['OptMariage'],5);

    if(!$cp->updateConfCP('OptMariage',$OptMariageCP)) {
        $error = true;
    }

    // Option du nombre de jours pour un décés d'un proche
    $OptDecesProcheCP = price2num($_POST['OptDecesProche'],5);

    if(!$cp->updateConfCP('OptDecesProche',$OptDecesProcheCP)) {
        $error = true;
    }

    // Option du nombre de jours pour un mariage d'un enfant
    $OptMariageProcheCP = price2num($_POST['OptMariageProche'],5);

    if(!$cp->updateConfCP('OptMariageProche',$OptMariageProcheCP)) {
        $error = true;
    }

    // Option du nombre de jours pour un décés d'un parent
    $OptDecesParentsCP = price2num($_POST['OptDecesParents'],5);

    if(!$cp->updateConfCP('OptDecesParents',$OptDecesParentsCP)) {
        $error = true;
    }

    // Option pour avertir le valideur si délai de demande incorrect
    if(isset($_POST['AlertValidatorDelay'])) {
        if(!$cp->updateConfCP('AlertValidatorDelay','1')) {
            $error = true;
        }
    } else {
        if(!$cp->updateConfCP('AlertValidatorDelay','0')) {
            $error = true;
        }
    }

    // Option pour avertir le valideur si solde des congés de l'utilisateur inccorect
    if(isset($_POST['AlertValidatorSolde'])) {
        if(!$cp->updateConfCP('AlertValidatorSolde','1')) {
            $error = true;
        }
    } else {
        if(!$cp->updateConfCP('AlertValidatorSolde','0')) {
            $error = true;
        }
    }

    // Option du nombre de jours à déduire pour 1 jour de congés
    $nbHolidayDeducted = price2num($_POST['nbHolidayDeducted'],2);

    if(!$cp->updateConfCP('nbHolidayDeducted',$nbHolidayDeducted)) {
        $error = true;
    }

    if ($error) {
        $message = '<div class="error">'.$langs->trans('ErrorUpdateConfCP').'</div>';
    } else {
        $message = '<div class="ok">'.$langs->trans('UpdateConfCPOK').'</div>';
    }

    // Si première mise à jour, prévenir l'utilisateur de mettre à jour le solde des congés payés
    $sql = "SELECT *";
    $sql.= " FROM ".MAIN_DB_PREFIX."holiday_users";

    $result = $db->query($sql);
    $num = $db->num_rows($sql);

    if($num < 1) {
        $cp->createCPusers();
        $message.= '<br /><div class="warning">'.$langs->trans('AddCPforUsers').'</div>';
    }

    dol_htmloutput_mesg($message);


    // Si il s'agit de créer un event
}
elseif ($action == 'create_event')
{
    $error = 0;

    $optName = trim($optName);
    $optValue = price2num($optValue,2);

    if (! $optName)
    {
    	$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Name")).'</div>';
        $error++;
    }
    if (! $optValue > 0)
    {
    	$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Value")).'</div>';
    	$error++;
    }

    $cp->optName = $optName;
    $cp->optValue = $optValue;

    if (! $error)
    {
        $result = $cp->createEventCP($user);
        if($result > 0)
        {
            $message = 'OkCreateEventCP';
            $optName='';
            $optValue='';
        }
        else
        {
            $message = '<div class="error">'.$cp->error.'</div>';
        }
    }

    dol_htmloutput_mesg($message);
}
elseif($action == 'event' && isset($_POST['update_event']))
{
    $error = false;

    $eventId = array_keys($_POST['update_event']);
    $eventId = $eventId[0];

    $eventName = $optName;
    $eventName = $eventName[$eventId];

    $eventValue = $optValue;
    $eventValue = $eventValue[$eventId];

    if(!empty($eventName)) {
        $eventName = trim($eventName);
    } else {
        $error = true;
    }

    if(!empty($eventValue)) {
        $eventValue = price2num($eventValue,2);
    } else {
        $error = true;
    }

    if(!$error)
    {
        // Mise à jour des congés de l'utilisateur
        $update = $cp->updateEventCP($eventId,$eventName,$eventValue);
        if(!$update) {
            $message='ErrorUpdateEventCP';
        } else {
            $message='UpdateEventOkCP';
        }
    } else {
        $message='ErrorUpdateEventCP';
    }

    dol_htmloutput_mesg($message);
}
elseif($action && isset($_POST['delete_event']))
{
    $eventId = array_keys($_POST['delete_event']);
    $eventId = $eventId[0];

    $result = $cp->deleteEventCP($eventId);

    if($result) {
        print '<div class="tabBar">';
        print $langs->trans('DeleteEventOkCP');
        print '</div>';
    } else {
        print '<div class="tabBar">';
        print $langs->trans('ErrorDeleteEventCP');
        print '</div>';
    }
}

print '<br>';

print_fiche_titre($langs->trans('TitleOptionMainCP'),'','');

dol_fiche_head(array(),'','');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?leftmenu=setup" name="config">'."\n";
print '<input type="hidden" name="action" value="add" />'."\n";

print '<table class="noborder" width="100%">';
print '<tbody>';
print '<tr class="liste_titre">';
print '<th class="liste_titre">'.$langs->trans('DescOptionCP').'</td>';
print '<th class="liste_titre">'.$langs->trans('ValueOptionCP').'</td>';
print '</tr>';

$var=true;

/*$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('GroupToValidateCP').'</td>'."\n";
print '<td style="padding:5px;">'.$cp->selectUserGroup('userGroup').'</td>'."\n";
print '</tr>'."\n";
*/

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('DelayForSubmitCP').'</td>'."\n";
print '<td style="padding:5px;"><input class="flat" type="text" name="delayForRequest" value="'.$cp->getConfCP('delayForRequest').'" size="2" /> '.$langs->trans('DurationDays').'</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('AlertValidatorDelayCP').'</td>'."\n";
print '<td style="padding:5px;"><input class="flat" type="checkbox" name="AlertValidatorDelay" '.$cp->getCheckOption('AlertValidatorDelay').'/></td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('AlertValidorSoldeCP').'</td>'."\n";
print '<td style="padding:5px;"><input class="flat" type="checkbox" name="AlertValidatorSolde" '.$cp->getCheckOption('AlertValidatorSolde').'/></td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('nbHolidayEveryMonthCP').'</td>'."\n";
print '<td style="padding:5px;"><input class="flat" type="text" name="nbHolidayEveryMonth" value="'.$cp->getConfCP('nbHolidayEveryMonth').'" size="5"/> '.$langs->trans('DurationDays').'</td>'."\n";
print '</tr>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td style="padding:5px;">'.$langs->trans('nbHolidayDeductedCP').'</td>'."\n";
print '<td style="padding:5px;"><input class="flat" type="text" name="nbHolidayDeducted" value="'.$cp->getConfCP('nbHolidayDeducted').'" size="2"/> '.$langs->trans('DurationDays').'</td>'."\n";
print '</tr>'."\n";

print '</tbody>'."\n";
print '</table>'."\n";

print '<div align="center"><input type="submit" value="'.$langs->trans("ConfirmConfigCP").'" name="bouton" class="button"/></div>'."\n";
print '</form>'."\n\n";

dol_fiche_end();


/*$var=!$var;
print $langs->trans('nbUserCP').': '."\n";
print $cp->getConfCP('nbUser')."<br>\n";
*/

$var=!$var;
print $langs->trans('LastUpdateCP').': '."\n";
if ($cp->getConfCP('lastUpdate')) print dol_print_date($db->jdate($cp->getConfCP('lastUpdate')),'dayhour','tzuser');
else print $langs->trans('None');
print "<br>\n";

print '<br>';

print_fiche_titre($langs->trans('TitleOptionEventCP'),'','');

dol_fiche_head(array(),'','');


$cp_events = $cp->fetchEventsCP();

if($cp_events == 1) {

    $var = false;
    $i = 0;

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?leftmenu=setup" name="event_update">'."\n";
    print '<input type="hidden" name="action" value="event" />'."\n";

    print '<table class="noborder" width="100%">'."\n";
    print '<tbody>'."\n";
    print '<tr class="liste_titre">'."\n";

    print '<td class="liste_titre" width="40%">'.$langs->trans('NameEventCP').'</td>'."\n";
    print '<td class="liste_titre" width="20%">'.$langs->trans('ValueOptionCP').'</td>'."\n";
    print '<td class="liste_titre">&nbsp;</td>'."\n";
    print '<td class="liste_titre">&nbsp;</td>'."\n";

    print '</tr>'."\n";

    foreach($cp->events as $infos_event) {

        $var=!$var;

        print '<tr '.$bc[$var].'>'."\n";
        print '<td><input class="flat" type="text" size="40" name="optName['.$infos_event['rowid'].']" value="'.$infos_event['name'].'" /></td>'."\n";
        print '<td><input class="flat" type="text" size="2" name="optValue['.$infos_event['rowid'].']" value="'.$infos_event['value'].'" /> '.$langs->trans('DurationDays').'</td>'."\n";
        print '<td><input type="submit" class="button" name="update_event['.$infos_event['rowid'].']" value="'.dol_escape_htmltag($langs->trans("Save")).'"/></td>'."\n";
        print '<td width="20px" align="right"><input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" name="delete_event['.$infos_event['rowid'].']" style="border:0;"/></td>'."\n";
        print '</tr>';

        $i++;
    }

    print '</tbody>'."\n";
    print '</table>'."\n";
    print '</form>'."\n";
    print '<br />'."\n\n";

}

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?leftmenu=setup" name="event_create">'."\n";
print '<input type="hidden" name="action" value="create_event" />'."\n";

print $langs->trans('TitleCreateEventCP');

print '<table class="noborder" width="100%">';
print '<tbody>';

print '<tr class="liste_titre">';

print '<td class="liste_titre" width="40%">'.$langs->trans('NameEventCP').'</td>';
print '<td class="liste_titre" width="20%">'.$langs->trans('ValueOptionCP').'</td>';
print '<td class="liste_titre">&nbsp;</td>';

print '</tr>';

print '<tr class="pair">';
print '<td><input class="flat" type="text" size="40" name="optName" value="'.(is_array($optName)?'':$optName).'" /></td>'."\n";
print '<td><input class="flat" type="text" size="2" name="optValue" value="'.(is_array($optValue)?'':$optValue).'" /> '.$langs->trans('DurationDays').'</td>'."\n";
print '<td><input type="submit" class="button" name="button" value="'.$langs->trans('CreateEventCP').'" /></td>'."\n";
print '</tr>'."\n";

print '</tbody>';
print '</table>';

print '</form>';

dol_fiche_end();


// Fin de page
llxFooter();

if (is_object($db)) $db->close();