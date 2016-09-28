<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       resource/records.php
 *		\ingroup    resource
 *		\brief      Page to show resource change records
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/resourcelog.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load traductions files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");

$id         = GETPOST('id','int');
$action     = GETPOST('action','alpha');

$optioncss = GETPOST('optioncss','alpha');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="date_creation"; // Set here default search field
if (! $sortorder) $sortorder="DESC";

// Protection if external user
if ($user->societe_id > 0)
{
    accessforbidden();
}

if( ! $user->rights->resource->read || ! $user->rights->resource->schedule_read )
{
    accessforbidden();
}

$pagetitle = $langs->trans('ResourceRecords');
llxHeader('',$pagetitle,'');

$form = new Form($db);
$resource = new Dolresource($db);
$userstatic = new User($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('resource', 'resource_log'));

// Definition of fields for list
$arrayfields=array(
    'date_creation'=>array('label'=>$langs->trans("Date"), 'checked'=>1, 'position'=>500),
    'booker'=>array('label'=>$langs->trans("Booker"), 'checked'=>1),
    'action'=>array('label'=>$langs->trans("Action"), 'checked'=>1),
    'status'=>array('label'=>$langs->trans("Status"), 'checked'=>1),
    'date_start'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>500),
    'date_end'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>500),
    'user'=>array('label'=>$langs->trans("Author"), 'checked'=>1),
);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $search_ref_client='';
    $search_soc='';
    $search_array_options=array();
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

if ( $resource->fetch($id) > 0 )
{
    $head=resource_prepare_head($resource);
    dol_fiche_head($head, 'log', $langs->trans("ResourceSingular"),0,'resource@resource');

    /*
     * View object
     */
    print '<table width="100%" class="border">';
    print '<tr><td style="width:35%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
    print '<div class="inline-block floatleft refid">'.$resource->ref.'</div>';
    print '</td></tr>';
    print '</table>';
    print '</div>';

    /*
     * List log entries
     */
    $sql = "SELECT";
    $sql.= " p.fk_user,";
    $sql.= " p.booker_id,";
    $sql.= " p.booker_type,";
    $sql.= " p.date_creation,";
    $sql.= " p.date_start,";
    $sql.= " p.date_end,";
    $sql.= " p.status,";
    $sql.= " p.action";
    $sql.= " FROM ".MAIN_DB_PREFIX."resource_log as p";
    $sql.= " WHERE p.fk_resource = ".$resource->id;

    $no_search = empty($search_resource) && empty($sall);

    // Add where from hooks
    $parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
    $sql.=$hookmanager->resPrint;

    $sql.=$db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit+1, $offset);
    $resql=$db->query($sql);

    $num = $resql ? $db->num_rows($resql) : -1;
    if ($num < 0)
    {
        dol_print_error($db, $resql);
    }
    else if ($num == 0 && $no_search)
    {
        print '<div class="warning">'.$langs->trans('NoRecordFound').'</div>';
    }
    else
    {
        $param = '&amp;id='.$id;

        if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

        print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
        if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields

        if ($sall)
        {
            foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
            print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
        }

        if (! empty($moreforfilter))
        {
            print '<div class="liste_titre liste_titre_bydiv centpercent">';
            print $moreforfilter;
            $parameters=array();
            $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            print '</div>';
        }

        print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

        // Fields title
        print '<tr class="liste_titre">';

        $sortable = empty($action);
        if (! empty($arrayfields['date_creation']['checked']))  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],$sortable?'date_creation':'',"",$param,'',$sortfield,$sortorder);
        if (! empty($arrayfields['booker']['checked'])) print_liste_field_titre($arrayfields['booker']['label'],$_SERVER['PHP_SELF'],$sortable?'booker_id':'','',$param,'',$sortfield,$sortorder);
        if (! empty($arrayfields['action']['checked']))  print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],$sortable?'action':'',"",$param,'',$sortfield,$sortorder);
        if (! empty($arrayfields['status']['checked']))  print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],$sortable?'status':'',"",$param,'',$sortfield,$sortorder);

        // Hook fields
        $parameters=array('arrayfields'=>$arrayfields);
        $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        if (! empty($arrayfields['date_start']['checked']))  print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],$sortable?'date_start':'',"",$param,'',$sortfield,$sortorder);
        if (! empty($arrayfields['date_end']['checked']))  print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],$sortable?'date_end':'',"",$param,'',$sortfield,$sortorder);
        if (! empty($arrayfields['user']['checked']))  print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],$sortable?'fk_user':'',"",$param,'',$sortfield,$sortorder);

        print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
        print '</tr>'."\n";

        // Fields title search
        /*
        if (empty($action)) {
            print '<tr class="liste_titre">';

            if (! empty($arrayfields['date_creation']['checked'])) print '<td class="liste_titre"></td>';
            if (! empty($arrayfields['booker']['checked'])) print '<td class="liste_titre"></td>';
            if (! empty($arrayfields['action']['checked'])) print '<td class="liste_titre"></td>';
            if (! empty($arrayfields['status']['checked'])) print '<td class="liste_titre"></td>';

            // Fields from hook
            $parameters=array('arrayfields'=>$arrayfields);
            $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            if (! empty($arrayfields['date_start']['checked'])) print '<td class="liste_titre"></td>';
            if (! empty($arrayfields['date_end']['checked'])) print '<td class="liste_titre"></td>';
            if (! empty($arrayfields['user']['checked'])) print '<td class="liste_titre"></td>';

            // Action column
            print '<td class="liste_titre" align="right">';
            print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
            print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
            print '</td>';

            print '</tr>'."\n";
        }
        */

        $action_trans = ResourceLog::translated();
        $status_trans = ResourceStatus::translated();
        $i = 0;
        while ($i < $num)
        {

            $obj = $db->fetch_object($resql);
            $i++;
            if ($obj)
            {
                print '<tr>';

                // Date creation
                if (! empty($arrayfields['date_creation']['checked']))
                {
                    print '<td>';
                    print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
                    print '</td>';
                }

                // Booker
                if (! empty($arrayfields['booker']['checked']))
                {
                    print '<td>';
                    if (isset($obj->booker_id))
                    {
                        $objelement = fetchObjectByElement($obj->booker_id, $obj->booker_type);
                        if (is_object($objelement) && $objelement->id == $obj->booker_id)
                        {
                            print $objelement->getNomUrl(1);
                        }
                    }
                    print '</td>';
                }

                // Action
                if (! empty($arrayfields['action']['checked']))
                {
                    print '<td>';
                    print $action_trans[$obj->action];
                    print '</td>';
                }

                // Status
                if (! empty($arrayfields['status']['checked']))
                {
                    print '<td>';
                    print $status_trans[$obj->status];
                    print '</td>';
                }

                // Fields from hook
                $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
                $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;

                // Date start
                if (! empty($arrayfields['date_start']['checked']))
                {
                    print '<td>';
                    print dol_print_date($db->jdate($obj->date_start), 'dayhour');
                    print '</td>';
                }

                // Date end
                if (! empty($arrayfields['date_end']['checked']))
                {
                    print '<td>';
                    print dol_print_date($db->jdate($obj->date_end), 'dayhour');
                    print '</td>';
                }

                // User
                if (! empty($arrayfields['user']['checked']))
                {
                    $userstatic->fetch($obj->fk_user);
                    print '<td>';
                    print $userstatic->getNomUrl(1);
                    print '</td>';
                }

                /*
                // Action column
                print '<td align="right">';

                // Delete
                print '<a href="./log.php?id='.$obj->rowid.'&amp;action=delete">';
                print img_delete();
                print '</a>';

                print '&nbsp;&nbsp;';
                print '</td>';
                */

                print '</tr>';
            }
        }

        $db->free($resql);

        $parameters=array('sql' => $sql);
        $reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print "</table>\n";

        // Buttons
        print '<div class="tabsAction">'."\n";
        $parameters=array();
        $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        print "</form>\n";
        print '</div>'."\n";
    }
}
else
{
    dol_print_error(0,$resource->error);
}

// End of page
llxFooter();
$db->close();
