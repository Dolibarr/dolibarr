<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/user/hierarchy.php
 *      \ingroup    user
 *      \brief      Page of hierarchy view of user module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';

if (! $user->rights->user->user->lire && ! $user->admin)
	accessforbidden();

$langs->load("users");
$langs->load("companies");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
	$socid = $user->societe_id;

$sall=GETPOST('sall','alpha');
$search_user=GETPOST('search_user','alpha');

$userstatic=new User($db);
$search_statut=GETPOST('search_statut','int');

if ($search_statut == '') $search_statut='1';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_statut="";
}


/*
 * View
 */

$form = new Form($db);

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('',$langs->trans("ListOfUsers"). ' ('.$langs->trans("HierarchicView").')','','',0,0,$arrayofjs,$arrayofcss);

print load_fiche_titre($langs->trans("ListOfUsers"). ' ('.$langs->trans("HierarchicView").')', '<form action="'.DOL_URL_ROOT.'/user/index.php'.(($search_statut != '' && $search_statut >= 0) ?'?search_statut='.$search_statut:'').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("ViewList")).'"></form>');



// Load hierarchy of users
$user_arbo = $userstatic->get_full_tree(0, ($search_statut != '' && $search_statut >= 0) ? "statut = ".$search_statut : '');

if (! is_array($user_arbo) && $user_arbo < 0)
{
    setEventMessages($userstatic->error, $userstatic->errors, 'warnings');    
}
else
{
// Define fulltree array
$fulltree=$user_arbo;
//var_dump($fulltree);
// Define data (format for treeview)
$data=array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($fulltree as $key => $val)
{
	$userstatic->id=$val['id'];
	$userstatic->ref=$val['label'];
	$userstatic->login=$val['login'];
	$userstatic->firstname=$val['firstname'];
	$userstatic->lastname=$val['lastname'];
	$userstatic->statut=$val['statut'];
    $userstatic->email=$val['email'];
    $userstatic->gender=$val['gender'];
	$userstatic->societe_id=$val['fk_soc'];
	$userstatic->admin=$val['admin'];
	$userstatic->entity=$val['entity'];
	$userstatic->photo=$val['photo'];
	
	$entity=$val['entity'];
	$entitystring='';

	// TODO Set of entitystring should be done with a hook
	if (is_object($mc))
	{
		if (! empty($conf->multicompany->enabled))
		{
			if (empty($entity))
			{
				$entitystring=$langs->trans("AllEntities");
			}
			else
			{
				$mc->getInfo($entity);
				$entitystring=$mc->label;
			}
		}
	}

	$li=$userstatic->getNomUrl(-1,'',0,1);
	if (! empty($conf->multicompany->enabled) && $userstatic->admin && ! $userstatic->entity)
	{
		$li.=img_picto($langs->trans("SuperAdministrator"),'redstar');
	}
	else if ($userstatic->admin)
	{
		$li.=img_picto($langs->trans("Administrator"),'star');
	}
	$li.=' ('.$val['login'].($entitystring?' - '.$entitystring:'').')';
	
	$data[] = array(
		'rowid'=>$val['rowid'],
		'fk_menu'=>$val['fk_user'],
		'statut'=>$val['statut'],
		'entry'=>'<table class="nobordernopadding centpercent"><tr><td class="'.($val['statut']?'usertdenabled':'usertddisabled').'">'.$li.'</td><td align="right" class="'.($val['statut']?'usertdenabled':'usertddisabled').'">'.$userstatic->getLibStatut(3).'</td></tr></table>'
	);
}

//var_dump($data);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

$param="search_statut=".$search_statut;

print '<table class="liste nohover" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("HierarchicView"));
print '<td align="right"><div id="iddivjstreecontrol"><a href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a>';
print ' | <a href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a></div></td>';
print_liste_field_titre($langs->trans("Status"),$_SERVER['PHP_SELF'],"",'',"",'align="right"');
print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','','','','maxwidthsearch ');
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
// Status
print '<td class="liste_titre" align="right">';
print $form->selectarray('search_statut', array('-1'=>'','1'=>$langs->trans('Enabled')),$search_statut);
print '</td>';
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
print '</td>';
print '</tr>';

$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr '.$bc[false].'><td colspan="3">';
	tree_recur($data,$data[0],0);
	print '</td>';
	print '<td></td>';
	print '</tr>';
}
else
{
	print '<tr '.$bc[true].'>';
	print '<td colspan="3">';
	print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table>';
	print '</td>';
	print '<td></td>';
	print '</tr>';
}

print "</table>";
print "</form>\n";
}

//
/*print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery(".usertddisabled").hide();
	}
	init_myfunc();
});
</script>';
*/

llxFooter();

$db->close();
