<?php
/* Copyright (C) 2007-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Regis Houssin		<regis.houssin@capnetworks.com>
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
 *		File that defines the balance of paid holiday of users.
 *
 *   	\file       htdocs/holiday/define_holiday.php
 *		\ingroup    holiday
 *		\brief      File that defines the balance of paid holiday of users.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

// If the user does not have perm to read the page
if(!$user->rights->holiday->define_holiday) accessforbidden();

$action=GETPOST('action');

$holiday = new Holiday($db);

$langs->load('users');
$langs->load('hrm');


/*
 * Actions
 */

// Si il y a une action de mise à jour
if ($action == 'update' && isset($_POST['update_cp']))
{
	$error = 0;

	$typeleaves=$holiday->getTypes(1,1);

    $userID = array_keys($_POST['update_cp']);
    $userID = $userID[0];

    foreach($typeleaves as $key => $val)
    {
	    $userValue = $_POST['nb_holiday_'.$val['rowid']];
	    $userValue = $userValue[$userID];

	    if (!empty($userValue))
	    {
	        $userValue = price2num($userValue,5);
	    } else {
	        $userValue = '';
	    }

	    //If the user set a comment, we add it to the log comment
	    $comment = ((isset($_POST['note_holiday'][$userID]) && !empty($_POST['note_holiday'][$userID])) ? ' ('.$_POST['note_holiday'][$userID].')' : '');

	    //print 'eee'.$val['rowid'].'-'.$userValue;
		if ($userValue != '')
		{
			// We add the modification to the log (must be before update of sold because we read current value of sold)
		    $result=$holiday->addLogCP($user->id, $userID, $langs->transnoentitiesnoconv('ManualUpdate').$comment, $userValue, $val['rowid']);
			if ($result < 0)
			{
				setEventMessages($holiday->error, $holiday->errors, 'errors');
				$error++;
			}

			// Update of the days of the employee
		    $result = $holiday->updateSoldeCP($userID, $userValue, $val['rowid']);
			if ($result < 0)
			{
				setEventMessages($holiday->error, $holiday->errors, 'errors');
				$error++;
			}

		    // If it first update of balance, we set date to avoid to have sold incremented by new month
		    /*
			$now=dol_now();
		    $sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
		    $sql.= " value = '".dol_print_date($now,'%Y%m%d%H%M%S')."'";
		    $sql.= " WHERE name = 'lastUpdate' and value IS NULL";	// Add value IS NULL to be sure to update only at init.
		    dol_syslog('define_holiday update lastUpdate entry', LOG_DEBUG);
		    $result = $db->query($sql);
		    */
		}
    }

    if (! $error) setEventMessages('UpdateConfCPOK', '', 'mesgs');
}
elseif($action == 'add_event')
{
    $error = 0;

	$typeleaves=$holiday->getTypes(1,1);

    if(!empty($_POST['list_event']) && $_POST['list_event'] > 0) {
        $event = $_POST['list_event'];
    } else { $error++;
    }

    if(!empty($_POST['userCP']) && $_POST['userCP'] > 0) {
        $userCP = $_POST['userCP'];
    } else { $erro++;
    }

    if ($error)
    {
	    setEventMessages('ErrorAddEventToUserCP', '', 'errors');
    }
    else
	{
        $nb_holiday = $holiday->getCPforUser($userCP);
        $add_holiday = $holiday->getValueEventCp($event);
        $new_holiday = $nb_holiday + $add_holiday;

        // add event to existing types of vacation
        foreach ($typeleaves as $key => $leave)
        {
        	$vacationTypeID = $leave['rowid'];

	        // On ajoute la modification dans le LOG
	        $holiday->addLogCP($user->id,$userCP, $holiday->getNameEventCp($event),$new_holiday, $vacationTypeID);

	        $holiday->updateSoldeCP($userCP,$new_holiday, $vacationTypeID);
        }

		setEventMessages('AddEventToUserOkCP', '', 'mesgs');
    }
}


/*
 * View
 */

$form = new Form($db);
$userstatic=new User($db);

llxHeader(array(),$langs->trans('CPTitreMenu'));

print load_fiche_titre($langs->trans('MenuConfCP'), '', 'title_hrm.png');

print '<div class="info">'.$langs->trans('LastUpdateCP').': '."\n";
$lastUpdate = $holiday->getConfCP('lastUpdate');
if ($lastUpdate)
{
    $monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
    $yearLastUpdate = $lastUpdate[0].$lastUpdate[1].$lastUpdate[2].$lastUpdate[3];
    print '<strong>'.dol_print_date($db->jdate($holiday->getConfCP('lastUpdate')),'dayhour','tzuser').'</strong>';
    print '<br>'.$langs->trans("MonthOfLastMonthlyUpdate").': <strong>'.$yearLastUpdate.'-'.$monthLastUpdate.'</strong>'."\n";
}
else print $langs->trans('None');



print "</div><br>\n";

$result = $holiday->updateBalance();	// Create users into table holiday if they don't exists. TODO Remove this whif we use field into table user.
if ($result < 0)
{
	setEventMessages($holiday->error, $holiday->errors, 'errors');
}

$listUsers = $holiday->fetchUsers(false,true);

$var=true;
$i = 0;

$cp_events = $holiday->fetchEventsCP();
if ($cp_events == 1)
{
	print '<br><form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="action" value="add_event" />';

	print load_fiche_titre($langs->trans('DefineEventUserCP'),'','');

	print $langs->trans('MotifCP').' : ';
	print $holiday->selectEventCP();
	print ' &nbsp; '.$langs->trans('UserCP').' : ';
	print $form->select_dolusers('', 'userCP', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	print ' <input type="submit" value="'.$langs->trans("addEventToUserCP").'" name="bouton" class="button"/>';

	print '</form><br>';
}


$typeleaves=$holiday->getTypes(1,1);

if (count($typeleaves) == 0)
{
    //print '<div class="info">';
    print $langs->trans("NoLeaveWithCounterDefined")."<br>\n";
    print $langs->trans("GoIntoDictionaryHolidayTypes");
    //print '</div>';
}
else
{
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    print '<input type="hidden" name="action" value="update" />';

    print '<table class="noborder" width="100%;">';
    print "<tr class=\"liste_titre\">";
    print '<td width="55%">'.$langs->trans('Employee').'</td>';
    if (count($typeleaves))
    {
        foreach($typeleaves as $key => $val)
        {
        	print '<td width="20%" style="text-align:center">'.$val['label'].'</td>';
        }
    }
    else
    {
        print '<td>'.$langs->trans("NoLeaveWithCounterDefined").'</td>';
    }
    print '<td width="20%" style="text-align:center">'.$langs->trans('Note').'</td>';
    print '<td></td>';
    print '</tr>';


    foreach($listUsers as $users)
    {
        $var=!$var;

        print '<tr '.$bc[$var].' style="height: 20px;">';
        print '<td>';
        $userstatic->id=$users['rowid'];
        $userstatic->lastname=$users['name'];
        $userstatic->firstname=$users['firstname'];
        print $userstatic->getNomUrl(1);
        print '</td>';

        if (count($typeleaves))
        {
        	foreach($typeleaves as $key => $val)
        	{
        		$nbtoshow='';
        		if ($holiday->getCPforUser($users['rowid'], $val['rowid']) != '') $nbtoshow=price2num($holiday->getCPforUser($users['rowid'], $val['rowid']), 5);
            	print '<td style="text-align:center">';
            	print '<input type="text" value="'.$nbtoshow.'" name="nb_holiday_'.$val['rowid'].'['.$users['rowid'].']" size="5" style="text-align: center;"/>';
        	    //print ' '.$langs->trans('days');
            	print '</td>'."\n";
        	}
        }
        else
        {
            print '<td></td>';
        }
        print '<td style="text-align:center"><input type="text" value="" name="note_holiday['.$users['rowid'].']" size="30"/></td>';
        print '<td><input type="submit" name="update_cp['.$users['rowid'].']" value="'.dol_escape_htmltag($langs->trans("Update")).'" class="button"/></td>'."\n";
        print '</tr>';

        $i++;
    }

    print '</table>';

    print '</form>';
}

llxFooter();

$db->close();
