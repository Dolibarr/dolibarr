<?php
/* Copyright (C) 2007-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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

// Load translation files required by the page
$langs->loadlangs(array('users', 'hrm'));

$action=GETPOST('action','aZ09');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'defineholidaylist';

$search_name=GETPOST('search_name', 'alpha');
$search_supervisor=GETPOST('search_supervisor', 'int');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";


// Protection if external user
if ($user->societe_id > 0) accessforbidden();

// If the user does not have perm to read the page
if (!$user->rights->holiday->read) accessforbidden();


// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('defineholidaylist'));
$extrafields = new ExtraFields($db);

$holiday = new Holiday($db);


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Purge search criteria
    if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
    {
        $search_name='';
        $search_supervisor='';
        $toselect='';
        $search_array_options=array();
    }

    // Mass actions
    /*
    $objectclass='Skeleton';
    $objectlabel='Skeleton';
    $permtoread = $user->rights->skeleton->read;
    $permtodelete = $user->rights->skeleton->delete;
    $uploaddir = $conf->skeleton->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
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

    	    if (!empty($userValue) || (string) $userValue == '0')
    	    {
    	        $userValue = price2num($userValue,5);
    	    } else {
    	        $userValue = '';
    	    }

    	    //If the user set a comment, we add it to the log comment
    	    $comment = ((isset($_POST['note_holiday'][$userID]) && !empty($_POST['note_holiday'][$userID])) ? ' ('.$_POST['note_holiday'][$userID].')' : '');

    	    //print 'holiday: '.$val['rowid'].'-'.$userValue;
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
}


/*
 * View
 */

$form = new Form($db);
$userstatic=new User($db);

llxHeader('', $langs->trans('CPTitreMenu'));


$typeleaves=$holiday->getTypes(1,1);


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

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

$filters = '';

// Filter on array of ids of all childs
$userchilds=array();
if (empty($user->rights->holiday->read_all))
{
	$userchilds=$user->getAllChildIds(1);
	$filters.=' AND u.rowid IN ('.join(', ',$userchilds).')';
}
if (!empty($search_name)) {
	$filters.=natural_search(array('u.firstname','u.lastname'), $search_name);
}
if ($search_supervisor > 0) $filters.=natural_search(array('u.fk_user'), $search_supervisor, 2);
$filters.= ' AND employee = 1';	// Only employee users are visible

$listUsers = $holiday->fetchUsers(false, true, $filters);
if (is_numeric($listUsers) && $listUsers < 0)
{
    setEventMessages($holiday->error, $holiday->errors, 'errors');
}

$i = 0;


if (count($typeleaves) == 0)
{
    //print '<div class="info">';
    print $langs->trans("NoLeaveWithCounterDefined")."<br>\n";
    print $langs->trans("GoIntoDictionaryHolidayTypes");
    //print '</div>';
}
else
{
    $canedit=0;
    if (! empty($user->rights->holiday->define_holiday)) $canedit=1;

    $moreforfilter='';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" id="tablelines3">'."\n";

    print '<tr class="liste_titre_filter">';

    // User
    print '<td class="liste_titre"><input type="text" name="search_name" value="'.dol_escape_htmltag($search_name).'"></td>';

    // Supervisor
    print '<td class="liste_titre">';
    print $form->select_dolusers($search_supervisor, 'search_supervisor', 1, null, 0, null, null, 0, 0, 0, '', 0, '', 'maxwidth200');
    print '</td>';

    // Type of leave request
    if (count($typeleaves))
    {
        foreach($typeleaves as $key => $val)
        {
            print '<td class="liste_titre" style="text-align:center"></td>';
        }
    }
    else
    {
        print '<td class="liste_titre"></td>';
    }
    print '<td class="liste_titre"></td>';

    // Action column
    print '<td class="liste_titre" align="right">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';

    print '</tr>';

    print '<tr class="liste_titre">';
    print_liste_field_titre('Employee', $_SERVER["PHP_SELF"]);
    print_liste_field_titre('Supervisor', $_SERVER["PHP_SELF"]);
    if (count($typeleaves))
    {
        foreach($typeleaves as $key => $val)
        {
        	$labeltype = ($langs->trans($val['code'])!=$val['code']) ? $langs->trans($val['code']) : $langs->trans($val['label']);
        	print_liste_field_titre($labeltype, $_SERVER["PHP_SELF"], '', '', '', 'align="center"');
        }
    }
    else
    {
        print_liste_field_titre('NoLeaveWithCounterDefined', $_SERVER["PHP_SELF"], '', '', '', '');
    }
    print_liste_field_titre((empty($user->rights->holiday->define_holiday) ? '' : 'Note'), $_SERVER["PHP_SELF"]);
    print_liste_field_titre('');
    print '</tr>';

    $usersupervisor = new User($db);

    foreach($listUsers as $users)
    {
        // If user has not permission to edit/read all, we must see only subordinates
        if (empty($user->rights->holiday->read_all))
        {
            if (($users['rowid'] != $user->id) && (! in_array($users['rowid'], $userchilds))) continue;     // This user is not into hierarchy of current user, we hide it.
        }

        $userstatic->id=$users['rowid'];
        $userstatic->lastname=$users['lastname'];
        $userstatic->firstname=$users['firstname'];
        $userstatic->gender=$users['gender'];
        $userstatic->photo=$users['photo'];
        $userstatic->statut=$users['status'];
        $userstatic->employee=$users['employee'];
        $userstatic->fk_user=$users['fk_user'];

        if ($userstatic->fk_user > 0) $usersupervisor->fetch($userstatic->fk_user);

        print '<tr class="oddeven">';

        // User
        print '<td>';
        print $userstatic->getNomUrl(-1);
        print '</td>';

        // Supervisor
        print '<td>';
        if ($userstatic->fk_user > 0) print $usersupervisor->getNomUrl(-1);
        print '</td>';

        // Amount for each type
        if (count($typeleaves))
        {
        	foreach($typeleaves as $key => $val)
        	{
        		$nbtoshow='';
        		if ($holiday->getCPforUser($users['rowid'], $val['rowid']) != '') $nbtoshow=price2num($holiday->getCPforUser($users['rowid'], $val['rowid']), 5);

        		//var_dump($users['rowid'].' - '.$val['rowid']);
            	print '<td style="text-align:center">';
            	if ($canedit) print '<input type="text"'.($canedit?'':' disabled="disabled"').' value="'.$nbtoshow.'" name="nb_holiday_'.$val['rowid'].'['.$users['rowid'].']" size="5" style="text-align: center;"/>';
            	else print $nbtoshow;
        	    //print ' '.$langs->trans('days');
            	print '</td>'."\n";
        	}
        }
        else
        {
            print '<td></td>';
        }

        // Note
        print '<td>';
        if ($canedit) print '<input type="text"'.($canedit?'':' disabled="disabled"').' class="maxwidthonsmartphone" value="" name="note_holiday['.$users['rowid'].']" size="30"/>';
        print '</td>';

        // Button modify
        print '<td>';
        if (! empty($user->rights->holiday->define_holiday))	// Allowed to set the balance of any user
        {
            print '<input type="submit" name="update_cp['.$users['rowid'].']" value="'.dol_escape_htmltag($langs->trans("Update")).'" class="button"/>';
        }
        print '</td>'."\n";
        print '</tr>';

        $i++;
    }

    print '</table>';
    print '</div>';
}

print '</form>';

llxFooter();

$db->close();
