<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
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
 *      \file       htdocs/user/group/index.php
 * 		\ingroup	core
 *      \brief      Page of user groups
 */

require '../../main.inc.php';

if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	if (! $user->rights->user->group_advance->read && ! $user->admin)
		accessforbidden();
}

$langs->load("users");

$sall=GETPOST('sall');
$search_group=GETPOST('search_group');
$optioncss = GETPOST('optioncss','alpha');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortfield) $sortfield="g.nom";
if (! $sortorder) $sortorder="ASC";

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'g.nom'=>"Group",
    'g.note'=>"Note"
);


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("ListOfGroups"));

$sql = "SELECT g.rowid, g.nom as name, g.entity, g.datec, COUNT(DISTINCT ugu.fk_user) as nb";
$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_usergroup = g.rowid";
if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->multicompany->transverse_mode || ($user->admin && ! $user->entity)))
{
	$sql.= " WHERE g.entity IS NOT NULL";
}
else
{
	$sql.= " WHERE g.entity IN (0,".$conf->entity.")";
}
if (! empty($search_group))
{
    $sql .= " AND (g.nom LIKE '%".$db->escape($search_group)."%' OR g.note LIKE '%".$db->escape($search_group)."%')";
}
if ($sall) $sql.= " AND (g.nom LIKE '%".$db->escape($sall)."%' OR g.note LIKE '%".$db->escape($sall)."%')";
$sql.= " GROUP BY g.rowid, g.nom, g.entity, g.datec";
$sql.= $db->order($sortfield,$sortorder);

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $param="&search_group=".urlencode($search_group)."&amp;sall=".urlencode($sall);
    if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;
    
    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    $moreforfilter='';
    
	//$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	//$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
    
    print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Group"),$_SERVER["PHP_SELF"],"g.nom",$param,"","",$sortfield,$sortorder);
    //multicompany
    if(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
    {
    	print_liste_field_titre($langs->trans("Entity"),$_SERVER["PHP_SELF"],"g.entity",$param,"",'align="center"',$sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("NbOfUsers"),$_SERVER["PHP_SELF"],"nb",$param,"",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateCreationShort"),$_SERVER["PHP_SELF"],"g.datec",$param,"",'align="right"',$sortfield,$sortorder);
    print "</tr>\n";
    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;

        print "<tr ".$bc[$var].">";
        print '<td><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->name.'</a>';
        if (! $obj->entity)
        {
        	print img_picto($langs->trans("GlobalGroup"),'redstar');
        }
        print "</td>";
        //multicompany
        if (! empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1)
        {
            $mc->getInfo($obj->entity);
            print '<td align="center">'.$mc->label.'</td>';
        }
        print '<td align="center">'.$obj->nb.'</td>';
        print '<td align="right" class="nowrap">'.dol_print_date($db->jdate($obj->datec),"dayhour").'</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free();
}
else
{
    dol_print_error($db);
}


llxFooter();
$db->close();
