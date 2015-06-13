<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/compta/facture/document.php
 *	\ingroup    facture
 *	\brief      Page for attached files on invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load('propal');
$langs->load('compta');
$langs->load('other');
$langs->load("bills");
$langs->load('companies');


$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id)
{
	$action='';
	$socid = $user->societe_id;
}
$result=restrictedArea($user,'facture',$id,'');

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

$object = new Facture($db);
if ($object->fetch($id))
{
	$object->fetch_thirdparty();
	$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

/*
 * Actions
 */
include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id,$ref) > 0)
	{
		$object->fetch_thirdparty();

		$upload_dir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = facture_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('InvoiceCustomer'), 0, 'bill');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}



		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="3">';
		$morehtmlref='';
		$discount=new DiscountAbsolute($db);
		$result=$discount->fetch(0,$object->id);
		if ($result > 0)
		{
			$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
		}
		if ($result < 0)
		{
			dol_print_error('',$discount->error);
		}
		print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
		print '</td></tr>';

		// Ref customer
		print '<tr><td width="20%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('RefCustomer');
		print '</td>';
		print '</tr></table>';
		print '</td>';
		print '<td colspan="5">';
		print $object->ref_client;
		print '</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";

		$modulepart = 'facture';
		$permission = $user->rights->facture->creer;
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
	print $langs->trans("ErrorUnknown");
}

$db->close();

llxFooter();
