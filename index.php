<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       place/index.php
 *		\ingroup    place
 *		\brief      Page to manage place object
 */


// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
$res=@include("../main.inc.php");				// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require 'class/resource.class.php';



// Load traductions files requiredby by page
$langs->load("resource@resource");
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

$hookmanager->initHooks(array('element_resource'));

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks



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

if( ! $user->rights->place->read)
	accessforbidden();

/***************************************************
 * VIEW
*
* Put here all code to build page
****************************************************/

$pagetitle=$langs->trans('ResourcePageIndex');
llxHeader('',$pagetitle,'');



$form=new Form($db);

print_fiche_titre($pagetitle,'','resource_32.png@resource');

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

	$var=false;

	print '<table class="noborder" width="100%">'."\n";
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Resource'),$_SERVER['PHP_SELF'],'t.resource_id','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Element'),$_SERVER['PHP_SELF'],'t.element_id','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Edit'));
	print '</tr>';

	foreach ($object->lines as $resource)
	{
		$var=!$var;

		$style='';
		if($resource->id == GETPOST('lineid'))
			$style='style="background: orange;"';

		print '<tr '.$bc[$var].' '.$style.'><td>';
		//print $resource->getNomUrl(1);
		if(is_object($resource->objresource))
			print $resource->objresource->getNomUrl(1);
		print '</td>';

		print '<td>';
		if(is_object($resource->objelement))
			print $resource->objelement->getNomUrl(1);
		print '</td>';

		print '<td>';
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete_resource&element='.$resource->element_type.'&element_id='.$resource->element_id.'&lineid='.$resource->id.'">'.$langs->trans('Delete').'</a>';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

}



// Action Bar
print '<div class="tabsAction">';
print '<div class="inline-block divButAction">';
print '<a href="resource_planning.php" class="butAction">'.$langs->trans('ShowResourcePlanning').'</a>';
print '</div>';
print '</div>';




llxFooter();

$db->close();


