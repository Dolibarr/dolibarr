<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis@dolibarr.fr>
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
        \file       htdocs/fourn/facture/document.php
        \ingroup    facture, fournisseur
        \brief      Page de gestion des documents attachées à une facture fournisseur
        \version    $Id$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load('bills');
$langs->load('other');
$langs->load("companies");


if (!$user->rights->fournisseur->facture->lire)
	accessforbidden();

$facid=empty($_GET['facid']) ? 0 : intVal($_GET['facid']);
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

// Security check
if ($user->societe_id > 0) 
{
	unset($_GET["action"]);
	$action=''; 
	$socid = $user->societe_id;
}

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * Actions
 */
 
// Envoi fichier
if ($_POST['sendit'] && $conf->upload)
{
	$facture = new FactureFournisseur($db);
	if ($facture->fetch($facid))
    {
        $upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($facture->id,2).$facture->id;

        if (! is_dir($upload_dir)) create_exdir($upload_dir);
    
        if (is_dir($upload_dir))
        {
            if (dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . '/' . $_FILES['userfile']['name'],0) > 0)
            {
                $mesg = '<div class="ok">'.$langs->trans('FileTransferComplete').'</div>';
                //print_r($_FILES);
            }
            else
            {
                // Echec transfert (fichier dépassant la limite ?)
                $mesg = '<div class="error">'.$langs->trans('ErrorFileNotUploaded').'</div>';
                // print_r($_FILES);
            }
        }
    }
}

// Delete
if ($action=='delete')
{
   	$facid=$_GET["id"];

   	$facture = new FactureFournisseur($db);
	if ($facture->fetch($facid))
    {
        $upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($facture->id,2).$facture->id;

        $file = $upload_dir . '/' . urldecode($_GET['urlfile']);
    	dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans('FileWasRemoved').'</div>';
    }
}


/*
 * Affichage
 */
 
llxHeader();

if ($facid > 0)
{
	$facture = new FactureFournisseur($db);
	if ($facture->fetch($facid))
    {
        $facture->fetch_fournisseur();

		$upload_dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($facture->id,2).$facture->id;

		$head = facturefourn_prepare_head($facture);
		dolibarr_fiche_head($head, 'documents', $langs->trans('SupplierInvoice'));


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}
		

        print '<table class="border"width="100%">';

		// Ref
		print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">'.$facture->ref.'</td>';
		print "</tr>\n";

		// Ref supplier
		print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$facture->ref_supplier.'</td>';
		print "</tr>\n";

        // Société
        print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$facture->fournisseur->getNomUrl(1).'</td></tr>';

        print '<tr><td>'.$langs->trans('NbOfAttachedFiles').'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';

        print '<tr><td>'.$langs->trans('TotalSizeOfAttachedFiles').'</td><td colspan="3">'.$totalsize.' '.$langs->trans('bytes').'</td></tr>';

        print '</table>';
        print '</div>';

        if ($mesg) { print $mesg.'<br>'; }

        
        // Affiche formulaire upload
       	$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$facture->id);


		// List of document
		$param='&facid='.$facture->id;
		$formfile->list_of_documents($filearray,$facture,'facture_fournisseur',$param);
		
	}
	else
	{
		print 'facid='.$facid.'<br>';
		dolibarr_print_error($db);
	}
}
else
{
	print $langs->trans('UnkownError');
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
