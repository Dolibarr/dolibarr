<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *	\file       htdocs/commande/document.php
 *	\ingroup    order
 *	\brief      Management page of documents attached to an order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT .'/commande/class/commande.class.php';


$langs->load('companies');
$langs->load('other');

$action		= GETPOST('action');
$confirm	= GETPOST('confirm');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref');

// Security check
if ($user->societe_id)
{
	$action='';
	$socid = $user->societe_id;
}
$result=restrictedArea($user,'commande',$id,'');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new Commande($db);


/*
 * Actions
 */
if ($object->fetch($id))
{
	$object->fetch_thirdparty();
	$upload_dir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id, $ref))
	{
		$object->fetch_thirdparty();

		$upload_dir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = commande_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('CustomerOrder'), 0, 'order');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
		print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";

		$modulepart = 'commande';
		$permission = $user->rights->commande->creer;
		$param = '&id=' . $object->id;
		include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	header('Location: index.php');
}


llxFooter();

$db->close();
