<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
$companystatic = new Societe($db);



/*
 * View
 */

$form = new Form($db);

$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('',$langs->trans("ListOfUsers"). ' ('.$langs->trans("HierarchicView").')','','',0,0,$arrayofjs,$arrayofcss);

print_fiche_titre($langs->trans("ListOfUsers"). ' ('.$langs->trans("HierarchicView").')', '<form action="'.DOL_URL_ROOT.'/user/index.php" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("ViewList")).'"></form>');



// Charge tableau des categories
$user_arbo = $userstatic->get_full_tree();

// Define fulltree array
$fulltree=$user_arbo;

// Define data (format for treeview)
$data=array();
$data[] = array('rowid'=>0,'fk_menu'=>-1,'title'=>"racine",'mainmenu'=>'','leftmenu'=>'','fk_mainmenu'=>'','fk_leftmenu'=>'');
foreach($fulltree as $key => $val)
{
	$userstatic->id=$val['id'];
	$userstatic->ref=$val['label'];
	$userstatic->firstname=$val['firstname'];
	$userstatic->lastname=$val['name'];
	$userstatic->statut=$val['statut'];
	$li=$userstatic->getNomUrl(1,'').' ('.$val['login'].')';

	$data[] = array(
		'rowid'=>$val['rowid'],
		'fk_menu'=>$val['fk_user'],
		'entry'=>'<table class="nobordernopadding centpercent"><tr><td>'.$li.'</td><td align="right">'.$userstatic->getLibStatut(5).'</td></tr></table>'
	);
}


print '<table class="liste" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("HierarchicView").'</td><td></td><td align="right"><div id="iddivjstreecontrol"><a href="#">'.img_picto('','object_category').' '.$langs->trans("UndoExpandAll").'</a>';
print ' | <a href="#">'.img_picto('','object_category-expanded').' '.$langs->trans("ExpandAll").'</a></div></td></tr>';

$nbofentries=(count($data) - 1);

if ($nbofentries > 0)
{
	print '<tr '.$bc[true].'><td colspan="3">';
	tree_recur($data,$data[0],0);
	print '</td></tr>';
}
else
{
	print '<tr '.$bc[true].'>';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('','treemenu/branchbottom.gif').'</td>';
	print '<td valign="middle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";


llxFooter();

$db->close();
?>
