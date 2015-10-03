<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       resource/index.php
 *		\ingroup    resource
 *		\brief      Page to manage resource objects
 */


// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
$res=@include("../main.inc.php");				// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require 'class/resource.class.php';

// Load traductions files requiredby by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');

$lineid				= GETPOST('lineid','int');
$element 			= GETPOST('element','alpha');
$element_id			= GETPOST('element_id','int');
$resource_id		= GETPOST('resource_id','int');

$sortorder	= GETPOST('sortorder','alpha');
$sortfield	= GETPOST('sortfield','alpha');
$page		= GETPOST('page','int');

$object = new Resource($db);

$hookmanager->initHooks(array('resource_list'));

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="t.rowid";
if (empty($arch)) $arch = 0;

if ($page == -1) {
	$page = 0 ;
}

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if( ! $user->rights->resource->read)
	accessforbidden();


/*
 * Action
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */
$pagetitle=$langs->trans('ResourcePageIndex');
llxHeader('',$pagetitle,'');



$form=new Form($db);

print load_fiche_titre($pagetitle,'','title_generic');

// Confirmation suppression resource line
if ($action == 'delete_resource')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_resource",'','',1);
}

// Load object list
$ret = $object->fetch_all($sortorder, $sortfield, $limit, $offset);
if($ret == -1) {
	dol_print_error($db,$object->error);
	exit;
}
if(!$ret) {
	print '<div class="warning">'.$langs->trans('NoResourceInDatabase').'</div>';
}
else
{
	$var=true;

	print '<table class="noborder" width="100%">'."\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Id'),$_SERVER['PHP_SELF'],'t.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'t.ref','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('ResourceType'),$_SERVER['PHP_SELF'],'ty.code','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Action'),"","","","",'width="60" align="center"',"","");
	print "</tr>\n";

	foreach ($object->lines as $resource)
	{
		$var=!$var;

		$style='';
		if($resource->id == GETPOST('lineid'))
			$style='style="background: orange;"';

		print '<tr '.$bc[$var].' '.$style.'><td>';
		print '<a href="./card.php?id='.$resource->id.'">'.$resource->id.'</a>';
		print '</td>';

		print '<td>';
		print $resource->ref;
		print '</td>';

		print '<td>';
		print $resource->type_label;
		print '</td>';

		print '<td align="center">';
		print '<a href="./card.php?action=edit&id='.$resource->id.'">';
		print img_edit();
		print '</a>';
		print '&nbsp;';
		print '<a href="./card.php?action=delete&id='.$resource->id.'">';
		print img_delete();
		print '</a>';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

}

/*
 * Boutons actions
*/
print '<div class="tabsAction">';
$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
// modified by hook
if (empty($reshook))
{
	if ($action != "edit" )
	{
		// Edit resource
		if($user->rights->resource->write)
		{
			print '<div class="inline-block divButAction">';
			print '<a href="add.php" class="butAction">'.$langs->trans('AddResource').'</a>';
			print '</div>';
		}
	}
}
print '</div>';

llxFooter();

$db->close();


