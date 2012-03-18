<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *      \file       htdocs/fourn/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture fournisseur
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');

$langs->load('bills');
$langs->load("companies");

$id = (GETPOST('id','int') ? GETPOST('id','int') : GETPOST('facid','int'));
$ref = GETPOST('ref','alpha');
$action = GETPOST('action','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

$object = new FactureFournisseur($db);
$object->fetch($id,$ref);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'setnote_public' && $user->rights->fournisseur->facture->creer)
{
    $result=$object->update_note_public(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}
elseif ($action == 'setnote' && $user->rights->fournisseur->facture->creer)
{
    $result=$object->update_note(dol_html_entity_decode(GETPOST('note'), ENT_QUOTES));
    if ($result < 0) dol_print_error($db,$object->error);
}

// Set label
if ($action == 'setlabel' && $user->rights->fournisseur->facture->creer)
{
    $object->label=$_POST['label'];
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db);
}


/*
 * View
*/

$form = new Form($db);

llxHeader();

if ($id)
{
    $object->fetch_thirdparty();

    $head = facturefourn_prepare_head($object);
    $titre=$langs->trans('SupplierInvoice');
    dol_fiche_head($head, 'note', $titre, 0, 'bill');


    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%" nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
    print $form->showrefnav($object,'facid','',1,'rowid','ref',$morehtmlref);
    print '</td>';
    print "</tr>\n";

    // Ref supplier
    print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$object->ref_supplier.'</td>';
    print "</tr>\n";

    // Company
    print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

    // Type
    print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
    print $object->getLibType();
    if ($object->type == 1)
    {
        $facreplaced=new FactureFournisseur($db);
        $facreplaced->fetch($object->fk_facture_source);
        print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
    }
    if ($object->type == 2)
    {
        $facusing=new FactureFournisseur($db);
        $facusing->fetch($object->fk_facture_source);
        print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
    }

    $facidavoir=$object->getListIdAvoirFromInvoice();
    if (count($facidavoir) > 0)
    {
        print ' ('.$langs->transnoentities("InvoiceHasAvoir");
        $i=0;
        foreach($facidavoir as $fid)
        {
            if ($i==0) print ' ';
            else print ',';
            $facavoir=new FactureFournisseur($db);
            $facavoir->fetch($fid);
            print $facavoir->getNomUrl(1);
        }
        print ')';
    }
    if ($facidnext > 0)
    {
        $facthatreplace=new FactureFournisseur($db);
        $facthatreplace->fetch($facidnext);
        print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
    }
    print '</td></tr>';

    // Label
    print '<tr><td>'.$form->editfieldkey("Label",'label',$object->label,$object,0).'</td><td colspan="3">';
    print $form->editfieldval("Label",'label',$object->label,$object,0);
    print '</td>';

    print "</table>";

    print '<br>';

    $colwidth=20;
    include(DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php');

    dol_fiche_end();
}


llxFooter();

$db->close();
?>
