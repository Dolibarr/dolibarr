<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/admin/multicompany.php
 *	\ingroup    multicompany
 *	\brief      Page d'administration/configuration du module Multi-societe
 *	\version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/multicompany/multicompany.class.php');

$langs->load("admin");

if (!$user->admin || $user->entity)
accessforbidden();

$mc = new Multicompany($db);

/*
 * Actions
 */

if ($_GET["action"] == 'setactive')
{
	$mc->setEntity($_GET['id'],'active',$_GET["value"]);
	if ($_GET["value"] == 0) $mc->setEntity($_GET['id'],'visible',$_GET["value"]);
}

if ($_GET["action"] == 'setvisible')
{
	$mc->setEntity($_GET['id'],'visible',$_GET["value"]);
}


llxHeader('',$langs->trans("MultiCompanySetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MultiCompanySetup"),$linkback,'setup');

print '<br>';

$smarty->template_dir = DOL_DOCUMENT_ROOT.'/multicompany/tpl/';

/*
 * Create
 */

if ($_GET["action"] == 'create')
{
	print_titre($langs->trans("AddNewEntity"));
	
	$template = 'add-entity.tpl';
}

/*
 * Edit
 */

else if ($_GET["action"] == 'modify')
{
	print_titre($langs->trans("EditEntity"));	
	
	$template = 'edit-entity.tpl';
}

/*
 * View
 */

else
{
	print_titre($langs->trans("ListOfEntity"));
	
	$mc->getEntities(1);
	//var_dump($mc->entities);
	
	$template = 'admin-entity.tpl';
	
}

$mc->assign_smarty_values($smarty,$_GET["action"]);
$smarty->display($template);


llxFooter('$Date$ - $Revision$');
?>