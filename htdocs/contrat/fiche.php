<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
        \file       htdocs/contrat/fiche.php
        \ingroup    contrat
        \brief      Fiche contrat
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$contratid,'contrat');



/*
 * Actions
 */

if ($_POST["action"] == 'confirm_active' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);

    $result = $contrat->active_line($user, $_GET["ligne"], $_GET["date"], $_GET["dateend"]);

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg=$contrat->error;   
    }
}

if ($_POST["action"] == 'confirm_closeline' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->close_line($user, $_GET["ligne"], $_GET["dateend"]);

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg=$contrat->error;   
    }
}

// Si ajout champ produit predefini
if ($_POST["mode"]=='predefined')
{
	$date_start='';
	$date_end='';
	if ($_POST["date_startmonth"] && $_POST["date_startday"] && $_POST["date_startyear"])
	{
		$date_start=dolibarr_mktime(12, 0 , 0, $_POST["date_startmonth"], $_POST["date_startday"], $_POST["date_startyear"]);
	}
	if ($_POST["date_endmonth"] && $_POST["date_endday"] && $_POST["date_endyear"])
	{
		$date_end=dolibarr_mktime(12, 0 , 0, $_POST["date_endmonth"], $_POST["date_endday"], $_POST["date_endyear"]);
	}
}

// Si ajout champ produit libre
if ($_POST["mode"]=='libre')
{
	$date_start_sl='';
	$date_end_sl='';
	if ($_POST["date_start_slmonth"] && $_POST["date_start_slday"] && $_POST["date_start_slyear"])
	{
		$date_start_sl=dolibarr_mktime(12, 0 , 0, $_POST["date_start_slmonth"], $_POST["date_start_slday"], $_POST["date_start_slyear"]);
	}
	if ($_POST["date_end_slmonth"] && $_POST["date_end_slday"] && $_POST["date_end_slyear"])
	{
		$date_end_sl=dolibarr_mktime(12, 0 , 0, $_POST["date_end_slmonth"], $_POST["date_end_slday"], $_POST["date_end_slyear"]);
	}
}

// Param si updateligne
$date_start_update='';
$date_end_update='';
$date_start_real_update='';
$date_end_real_update='';
if ($_POST["date_start_updatemonth"] && $_POST["date_start_updateday"] && $_POST["date_start_updateyear"])
{
    $date_start_update=dolibarr_mktime(12, 0 , 0, $_POST["date_start_updatemonth"], $_POST["date_start_updateday"], $_POST["date_start_updateyear"]);
}
if ($_POST["date_end_updatemonth"] && $_POST["date_end_updateday"] && $_POST["date_end_updateyear"])
{
    $date_end_update=dolibarr_mktime(12, 0 , 0, $_POST["date_end_updatemonth"], $_POST["date_end_updateday"], $_POST["date_end_updateyear"]);
}
if ($_POST["date_start_real_updatemonth"] && $_POST["date_start_real_updateday"] && $_POST["date_start_real_updateyear"])
{
    $date_start_real_update=dolibarr_mktime(12, 0 , 0, $_POST["date_start_real_updatemonth"], $_POST["date_start_real_updateday"], $_POST["date_start_real_updateyear"]);
}
if ($_POST["date_end_real_updatemonth"] && $_POST["date_end_real_updateday"] && $_POST["date_end_real_updateyear"])
{
    $date_end_real_update=dolibarr_mktime(12, 0 , 0, $_POST["date_end_real_updatemonth"], $_POST["date_end_real_updateday"], $_POST["date_end_real_updateyear"]);
}


/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
    $datecontrat = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

    $contrat = new Contrat($db);

    $contrat->socid         = $_POST["socid"];
    $contrat->date_contrat   = $datecontrat;

    $contrat->commercial_suivi_id      = $_POST["commercial_suivi_id"];
    $contrat->commercial_signature_id  = $_POST["commercial_signature_id"];

    $contrat->note           = trim($_POST["note"]);
    $contrat->projetid       = trim($_POST["projetid"]);
    $contrat->remise_percent = trim($_POST["remise_percent"]);
    $contrat->ref            = trim($_POST["ref"]);

    $result = $contrat->create($user,$langs,$conf);
    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg='<div class="error">'.$contrat->error.'</div>';
    }
    $_GET["socid"]=$_POST["socid"];
    $_GET["action"]='create';
    $action = '';
}

if ($_POST["action"] == 'classin')
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $contrat->setProject($_POST["projetid"]);
}

if ($_POST["action"] == 'addligne' && $user->rights->contrat->creer)
{
    if ($_POST["pqty"] && (($_POST["pu"] != '' && $_POST["desc"]) || $_POST["p_idprod"]))
    {
        $contrat = new Contrat($db);
        $ret=$contrat->fetch($_GET["id"]);
        if ($ret < 0)
		{
			dolibarr_print_error($db,$commande->error);
			exit;
		}
		$ret=$contrat->fetch_client();
		
		$date_start='';
		$date_end='';
		// Si ajout champ produit libre
		if ($_POST['mode'] == 'libre')
		{
			if ($_POST['date_start_slyear'] && $_POST['date_start_slmonth'] && $_POST['date_start_slday'])
			{
				$date_start=dolibarr_mktime(12,0,0,$_POST['date_start_slmonth'],$_POST['date_start_slday'],$_POST['date_start_slyear']);
			}
			if ($_POST['date_end_slyear'] && $_POST['date_end_slmonth'] && $_POST['date_end_slday'])
			{
				$date_end=dolibarr_mktime(12,0,0,$_POST['date_end_slmonth'],$_POST['date_end_slday'],$_POST['date_end_slyear']);
			}
		}
		// Si ajout champ produit pr�d�fini
		if ($_POST['mode'] == 'predefined')
		{
			if ($_POST['date_startyear'] && $_POST['date_startmonth'] && $_POST['date_startday'])
			{
				$date_start=dolibarr_mktime(12,0,0,$_POST['date_startmonth'],$_POST['date_startday'],$_POST['date_startyear']);
			}
			if ($_POST['date_endyear'] && $_POST['date_endmonth'] && $_POST['date_endday'])
			{
				$date_end=dolibarr_mktime(12,0,0,$_POST['date_endmonth'],$_POST['date_endday'],$_POST['date_endyear']);
			}
		}

		$price_base_type = 'HT';
		
		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		// Ecrase $base_price_type par celui du produit
        if ($_POST['p_idprod'])
        {
            $prod = new Product($db, $_POST['p_idprod']);
            $prod->fetch($_POST['p_idprod']);

            $tva_tx = get_default_tva($mysoc,$contrat->client,$prod->tva_tx);
            $tva_npr = get_default_npr($mysoc,$contrat->client,$prod->tva_npr);

            // On defini prix unitaire
            if ($conf->global->PRODUIT_MULTIPRICES == 1)
            {
            	$pu_ht = $prod->multiprices[$fac->client->price_level];
            	$pu_ttc = $prod->multiprices_ttc[$fac->client->price_level];
            	$price_base_type = $prod->multiprices_base_type[$fac->client->price_level];
            }
            else
            {
            	$pu_ht = $prod->price;
	            $pu_ttc = $prod->price_ttc;
				$price_base_type = $prod->price_base_type;
            }

			// On reevalue prix selon taux tva car taux tva transaction peut etre different
			// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
			if ($tva_tx != $prod->tva_tx)
			{
				if ($price_base_type != 'HT')
				{
					$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
				}
				else
				{
					$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
				}
			}
			
           	$desc = $prod->description;
			$desc.= $prod->description && $_POST['desc'] ? "\n" : "";
           	$desc.= $_POST['desc'];
        }
        else
        {
	        $pu_ht=$_POST['pu'];
	        $tva_tx=eregi_replace('\*','',$_POST['tva_tx']);
			$tva_npr=eregi('\*',$_POST['tva_tx'])?1:0;
	        $desc=$_POST['desc'];
        }

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;
       
		// Insert line
		$result = $contrat->addline(
                $desc,
                $pu_ht,
                $_POST["pqty"],
                $tva_tx,
                $_POST["p_idprod"],
                $_POST["premise"],
                $date_start,
                $date_end,
				$price_base_type,
				$pu_ttc,
				$info_bits
                );
    
		if ($result > 0)
		{
		/*
			if ($_REQUEST['lang_id'])
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			contrat_pdf_create($db, $contrat->id, $contrat->modelpdf, $outputlangs);
		*/
		}
		else
		{
            $mesg='<div class="error">'.$contrat->error.'</div>';
		}
    }
}

if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer && ! $_POST["cancel"])
{
    $contratline = new ContratLigne($db);
    if ($contratline->fetch($_POST["elrowid"]))
    {
		$db->begin();
		
		if ($date_start_real_update == '') $date_start_real_update=$contratline->date_ouverture;
		if ($date_end_real_update == '')   $date_end_real_update=$contratline->date_cloture;
		
		$contratline->description=$_POST["eldesc"];
		$contratline->price_ht=$_POST["elprice"];
		$contratline->subprice=$_POST["elprice"];
        $contratline->qty=$_POST["elqty"];
        $contratline->remise_percent=$_POST["elremise_percent"];
        $contratline->date_ouverture_prevue=$date_start_update;
		$contratline->date_ouverture=$date_start_real_update;
		$contratline->date_fin_validite=$date_end_update;
        $contratline->date_cloture=$date_end_real_update;
		$contratline->tva_tx=$_POST["eltva_tx"];
            
		$result=$contratline->update($user);
        if ($result > 0)
        {
            $db->commit();
        }
        else
        {
            dolibarr_print_error($db,'Failed to update contrat_det');
            $db->rollback();
        }        
    }
    else
    {
        dolibarr_print_error($db);
    }
}

if ($_GET["action"] == 'deleteline' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->delete_line($_GET["lineid"]);

    if ($result >= 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
	else
	{
		$mesg=$contrat->error;
	}
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->validate($user,$langs,$conf);
}

if ($_POST["action"] == 'confirm_close' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->cloture($user,$langs,$conf);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    if ($user->rights->contrat->supprimer)
    {
        $contrat = new Contrat($db);
        $contrat->id = $_GET["id"];
        $result=$contrat->delete($user,$langs,$conf);
        if ($result >= 0)
		{
			Header("Location: index.php");
			return;
		}
		else
		{
			$mesg='<div class="error">'.$contrat->error.'</div>';
		}
    }
}




llxHeader('',$langs->trans("ContractCard"),"Contrat");

$form = new Form($db);
$html = new Form($db);

$contratlignestatic=new ContratLigne($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create')
{
    dolibarr_fiche_head($head, $a, $langs->trans("AddContract"));

    if ($mesg) print $mesg;

    $new_contrat = new Contrat($db);

    $sql = "SELECT s.nom, s.prefix_comm, s.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= " WHERE s.rowid = ".$_GET["socid"];

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $obj = $db->fetch_object($resql);

            $soc = new Societe($db);
            $soc->fetch($obj->rowid);

            print '<form name="contrat" action="fiche.php" method="post">';

            print '<input type="hidden" name="action" value="add">';
            print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
            print '<input type="hidden" name="remise_percent" value="0">';

            print '<table class="border" width="100%">';

			// Ref
			print '<tr><td>'.$langs->trans("Ref").'</td>';
			print '<td><input type="text" maxlength="30" name="ref" size="20"></td></tr>';
			
            // Customer
            print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

			// Ligne info remises tiers
            print '<tr><td>'.$langs->trans('Discount').'</td><td>';
			if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			$absolute_discount=$soc->getAvailableDiscounts();
			print '. ';
			if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
			else print $langs->trans("CompanyHasNoAbsoluteDiscount");
			print '.';
			print '</td></tr>';
    
            // Commercial suivi
            print '<tr><td width="20%" nowrap>'.$langs->trans("TypeContact_contrat_internal_SALESREPFOLL").'</td><td>';
			print $form->select_users('','commercial_suivi_id',1,'');
            print '</td></tr>';

            // Commercial signature
            print '<tr><td width="20%" nowrap>'.$langs->trans("TypeContact_contrat_internal_SALESREPSIGN").'</td><td>';
			print $form->select_users('','commercial_signature_id',1,'');
            print '</td></tr>';

            print '<tr><td>'.$langs->trans("Date").'</td><td>';
            $form->select_date('','','','','',"contrat");
            print "</td></tr>";

            if ($conf->projet->enabled)
            {
                print '<tr><td>'.$langs->trans("Project").'</td><td>';
                $proj = new Project($db);
                $form->select_array("projetid",$proj->liste_array($soc->id),0,1);
                print "</td></tr>";
            }
 
            print '<tr><td>'.$langs->trans("NotePublic").'</td><td valign="top">';
            print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'"></textarea></td></tr>';

			if (! $user->societe_id)
			{
	            print '<tr><td>'.$langs->trans("NotePrivate").'</td><td valign="top">';
	            print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'"></textarea></td></tr>';
			}
			
            print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';

            print "</table>\n";

            print "</form>\n";

            if ($propalid)
            {
                /*
                 * Produits
                 */
                print '<br>';
                print_titre($langs->trans("Products"));

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Product").'</td>';
                print '<td align="right">'.$langs->trans("Price").'</td>';
                print '<td align="center">'.$langs->trans("Qty").'</td>';
                print '<td align="center">'.$langs->trans("ReductionShort").'</td>';
                print '</tr>';

                $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
                $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
                $sql .= " ORDER BY pt.rowid ASC";
                $result = $db->query($sql);
                if ($result)
                {
                    $num = $db->num_rows($result);
                    $i = 0;
                    $var=True;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
                        print '<td>'.$objp->product.'</td>';
                        print "<td align=\"right\">".price($objp->price).'</td>';
                        print '<td align="center">'.$objp->qty.'</td>';
                        print '<td align="center">'.$objp->remise_percent.'%</td>';
                        print "</tr>\n";
                        $i++;
                    }
                }
                $sql = "SELECT pt.rowid, pt.description as product, pt.price, pt.qty, pt.remise_percent";
                $sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pt";
                $sql.= " WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
                $sql.= " ORDER BY pt.rowid ASC";
                $result=$db->query($sql);
                if ($result)
                {
                    $num = $db->num_rows($result);
                    $i = 0;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print "<tr $bc[$var]><td>&nbsp;</td>\n";
                        print '<td>'.$objp->product.'</td>';
                        print '<td align="right">'.price($objp->price).'</td>';
                        print '<td align="center">'.$objp->remise_percent.'%</td>';
                        print '<td align="center">'.$objp->qty.'</td>';
                        print "</tr>\n";
                        $i++;
                    }
                }
                else
                {
                    dolibarr_print_error($db);
                }

                print '</table>';
            }
        }
    }
    else
    {
        dolibarr_print_error($db);
    }
    
    print '</div>';
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
    $id = $_GET["id"];
    if ($id > 0)
    {
        $contrat = new Contrat($db);
        $result=$contrat->fetch($id);
        if ($result > 0) $result=$contrat->fetch_lignes();
        if ($result < 0)
        {
            dolibarr_print_error($db,$contrat->error);
            exit;
        }

        if ($mesg) print $mesg;
		
        $author = new User($db);
        $author->id = $contrat->user_author_id;
        $author->fetch();
		
        $commercial_signature = new User($db);
        $commercial_signature->id = $contrat->commercial_signature_id;
        $commercial_signature->fetch();

        $commercial_suivi = new User($db);
        $commercial_suivi->id = $contrat->commercial_suivi_id;
        $commercial_suivi->fetch();

	    $head = contract_prepare_head($contrat);

        $hselected = 0;

        dolibarr_fiche_head($head, $hselected, $langs->trans("Contract"));


        /*
         * Confirmation de la suppression du contrat
         */
        if ($_GET["action"] == 'delete')
        {
            $form->form_confirm("fiche.php?id=$id",$langs->trans("DeleteAContract"),$langs->trans("ConfirmDeleteAContract"),"confirm_delete");
            print '<br>';
        }

        /*
         * Confirmation de la validation
         */
        if ($_GET["action"] == 'valid')
        {
            //$numfa = contrat_get_num($soc);
            $form->form_confirm("fiche.php?id=$id",$langs->trans("ValidateAContract"),$langs->trans("ConfirmValidateContract"),"confirm_valid");
            print '<br>';
        }

        /*
         * Confirmation de la fermeture
         */
        if ($_GET["action"] == 'close')
        {
            $form->form_confirm("fiche.php?id=$id",$langs->trans("CloseAContract"),$langs->trans("ConfirmCloseContract"),"confirm_close");
            print '<br>';
        }

        /*
         *   Contrat
         */
        if ($contrat->brouillon && $user->rights->contrat->creer)
        {
            print '<form action="fiche.php?id='.$id.'" method="post">';
            print '<input type="hidden" name="action" value="setremise">';
        }

        print '<table class="border" width="100%">';

        // Ref du contrat
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
        print $contrat->ref;
        print "</td></tr>";

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$contrat->societe->getNomUrl(1).'</td></tr>';

		// Ligne info remises tiers
        print '<tr><td>'.$langs->trans('Discount').'</td><td colspan="3">';
		if ($contrat->societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$contrat->societe->remise_client);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$contrat->societe->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';

        // Statut contrat
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        print $contrat->getLibStatut(2);
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dolibarr_print_date($contrat->date_contrat,"dayhour")."</td></tr>\n";

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            if ($_GET["action"] != "classer" && $user->rights->projet->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($_GET["action"] == "classer")
            {
                $form->form_project("fiche.php?id=$id",$contrat->socid,$contrat->fk_projet,"projetid");
            }
            else
            {
                $form->form_project("fiche.php?id=$id",$contrat->socid,$contrat->fk_projet,"none");
            }
            print "</td></tr>";
        }

        print "</table>";

        if ($contrat->brouillon == 1 && $user->rights->contrat->creer)
        {
            print '</form>';
        }

		echo '<br>';
		
		$servicepos=(isset($_REQUEST["servicepos"])?$_REQUEST["servicepos"]:1);
		$nbofservices=sizeof($contrat->lignes);
		$colorb='333333';

        /*
         * Lignes de contrats
         */

		// Menu list of services
		print '<table class="noborder" width="100%">';	// Array with (n*2)+1 lines
		$cursorline=1;
		while ($cursorline <= $nbofservices)
		{
			print '<tr height="16" '.$bc[false].'>';
			print '<td class="tab" width="90" style="border-left: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';
			print $langs->trans("ServiceNb",$cursorline).'</td>';

			print '<td class="tab" style="border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';" rowspan="2">';

			// Area with common detail of line
			print '<table class="noborder" width="100%">';

			$sql = "SELECT cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty, cd.rowid,";
			$sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
			$sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_debut, ".$db->pdate("cd.date_ouverture")." as date_debut_reelle,";
			$sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin, ".$db->pdate("cd.date_cloture")." as date_fin_reelle,";
			$sql.= " p.ref, p.label";
			$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
			$sql.= " WHERE cd.rowid = ".$contrat->lignes[$cursorline-1]->id;

			$result = $db->query($sql);
			if ($result)
			{
				$total = 0;

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Service").'</td>';
				print '<td width="50" align="center">'.$langs->trans("VAT").'</td>';
				print '<td width="50" align="right">'.$langs->trans("PriceUHT").'</td>';
				print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
				print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
				print '<td width="30">&nbsp;</td>';
				print "</tr>\n";

				$var=true;

				$objp = $db->fetch_object($result);
					
				$var=!$var;

				if ($_GET["action"] != 'editline' || $_GET["rowid"] != $objp->rowid)
				{
					print '<tr '.$bc[$var].' valign="top">';
					// Libelle
					if ($objp->fk_product > 0)
					{
						print '<td>';
						print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
						print img_object($langs->trans("ShowService"),"service").' '.$objp->ref.'</a>';
						print $objp->label?' - '.$objp->label:'';
						if ($objp->description) print '<br />'.nl2br($objp->description);
						print '</td>';
					}
					else
					{
						print "<td>".nl2br($objp->description)."</td>\n";
					}
					// TVA
					print '<td align="center">'.vatrate($objp->tva_tx,'%',$objp->info_bits).'</td>';
					// Prix
					print '<td align="right">'.price($objp->subprice)."</td>\n";
					// Quantite
					print '<td align="center">'.$objp->qty.'</td>';
					// Remise
					if ($objp->remise_percent > 0)
					{
						print '<td align="right">'.$objp->remise_percent."%</td>\n";
					}
					else
					{
						print '<td>&nbsp;</td>';
					}
					// Icon update et delete (statut contrat 0=brouillon,1=valid�,2=ferm�)
					print '<td align="right" nowrap="nowrap">';
					if ($contrat->statut != 2  && $user->rights->contrat->creer)
					{
						print '<a href="fiche.php?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
						print img_edit();
						print '</a>';
					}
					else {
						print '&nbsp;';
					}
					if ( ($contrat->statut == 0 || ($contrat->statut == 1 && $conf->global->CONTRAT_EDITWHENVALIDATED))
						&& $user->rights->contrat->creer)
					{
						print '&nbsp;';
						print '<a href="fiche.php?id='.$id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';
		
					print "</tr>\n";

					// Dates de en service prevues et effectives
					if ($objp->subprice >= 0)
					{
						print '<tr '.$bc[$var].'>';
						print '<td colspan="6">';
	
						// Date pr�vues
						print $langs->trans("DateStartPlanned").': ';
						if ($objp->date_debut) {
							print dolibarr_print_date($objp->date_debut);
							// Warning si date prevu pass�e et pas en service
							if ($objp->statut == 0 && $objp->date_debut < time() - $conf->contrat->warning_delay) { print " ".img_warning($langs->trans("Late")); }
						}
						else print $langs->trans("Unknown");
						print ' &nbsp;-&nbsp; ';
						print $langs->trans("DateEndPlanned").': ';
						if ($objp->date_fin) {
							print dolibarr_print_date($objp->date_fin);
							if ($objp->statut == 4 && $objp->date_fin < time() - $conf->contrat->services->inactifs->warning_delay) { print " ".img_warning($langs->trans("Late")); }
						}
						else print $langs->trans("Unknown");
	
						print '</td>';
						print '</tr>';
					}                  
				}
				// Ligne en mode update
				else
				{
					print "<form name='update' action=\"fiche.php?id=$id\" method=\"post\">";
					print '<input type="hidden" name="action" value="updateligne">';
					print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
					// Ligne carac
					print "<tr $bc[$var]>";
					print '<td>';
					if ($objp->fk_product)
					{
						print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
						print img_object($langs->trans("ShowService"),"service").' '.$objp->ref.'</a>';
						print $objp->label?' - '.$objp->label:'';
						print '</br>';
					}
					else
					{
						print $objp->label?$objp->label.'<br>':'';
					}
					print '<textarea name="eldesc" cols="70" rows="1">'.$objp->description.'</textarea></td>';
					print '<td align="right">';
					print $form->select_tva("eltva_tx",$objp->tva_tx,$mysoc,$contrat->societe);
					print '</td>';
					print '<td align="right"><input size="5" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
					print '<td align="center"><input size="2" type="text" name="elqty" value="'.$objp->qty.'"></td>';
					print '<td align="right"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
					print '<td align="center" colspan="3" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
					print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
					print '</td>';
					// Ligne dates prevues
					print "<tr $bc[$var]>";
					print '<td colspan="5">';
					print $langs->trans("DateStartPlanned").' ';
					$form->select_date($objp->date_debut,"date_start_update",0,0,($objp->date_debut>0?0:1),"update");
					print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
					$form->select_date($objp->date_fin,"date_end_update",0,0,($objp->date_fin>0?0:1),"update");
					print '</td>';
					print '</tr>';

					print "</form>\n";
				}
					
				$db->free($result);
			}
			else
			{
				dolibarr_print_error($db);
			}
			
			if ($contrat->statut > 0)
			{
				print '<tr '.$bc[false].'>';
				print '<td colspan="6"><hr></td>';
				print "</tr>\n";
			}
			
			print "</table>";


			/*
			 * Confirmation de la validation activation
			 */
			if ($_REQUEST["action"] == 'active' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $contrat->lignes[$cursorline-1]->id == $_GET["ligne"])
			{
				//print '<br />';
				$dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
				$dateactend   = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
				$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",strftime("%A %d %B %Y", $dateactstart)),"confirm_active");
				print '<table class="noborder" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
			}

			/*
			 * Confirmation de la validation fermeture
			 */
			if ($_REQUEST["action"] == 'closeline' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $contrat->lignes[$cursorline-1]->id == $_GET["ligne"])
			{
				//print '<br />';
				$dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
				$dateactend   = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
				$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("CloseService"),$langs->trans("ConfirmCloseService",strftime("%A %d %B %Y", $dateactend)),"confirm_closeline");
				print '<table class="noborder" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
			}		
			
			// Area with activation info
			if ($contrat->statut > 0)
			{
				print '<table class="noborder" width="100%">';

				print '<tr '.$bc[false].'>';
				print '<td>'.$langs->trans("ServiceStatus").': '.$contrat->lignes[$cursorline-1]->getLibStatut(4).'</td>';
				print '<td width="30" align="right">';
				if ($user->societe_id == 0)
				{
					if ($contrat->statut > 0 && $_REQUEST["action"] != 'activateline' && $_REQUEST["action"] != 'unactivateline')
					{
						$action='activateline';
						if ($objp->statut == 4) $action='unactivateline';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$contrat->id.'&amp;ligne='.$contrat->lignes[$cursorline-1]->id.'&amp;action='.$action.'">';
						print img_edit();
						print '</a>';
					}
				}
				print '</td>';
				print "</tr>\n";

				print '<tr '.$bc[false].'>';
				
				print '<td>';
				// Si pas encore active
				if (! $objp->date_debut_reelle) {
					print $langs->trans("DateStartReal").': ';
					if ($objp->date_debut_reelle) print dolibarr_print_date($objp->date_debut_reelle);
					else print $langs->trans("ContractStatusNotRunning");
				}
				// Si active et en cours
				if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
					print $langs->trans("DateStartReal").': ';
					print dolibarr_print_date($objp->date_debut_reelle);
				}
				// Si desactive
				if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
					print $langs->trans("DateStartReal").': ';
					print dolibarr_print_date($objp->date_debut_reelle);
					print ' &nbsp;-&nbsp; ';
					print $langs->trans("DateEndReal").': ';
					print dolibarr_print_date($objp->date_fin_reelle);
				}
				print '</td>';

				// Statut
				print '<td align="center">';
				print '&nbsp;';
				print '</td>';
				print '</tr>';
				print '</table>';
			}

			if ($user->rights->contrat->activer && $_REQUEST["action"] == 'activateline' && $contrat->lignes[$cursorline-1]->id == $_GET["ligne"])
			{
				/**
				 * Activer la ligne de contrat
				 */
				print '<form name="active" action="'.$_SERVER["PHP_SELF"].'?id='.$contrat->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=active" method="post">';

				print '<table class="noborder" width="100%">';
				//print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';

				// Definie date debut et fin par defaut
				$dateactstart = $objp->date_debut;
				if ($_POST["remonth"]) $dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
				elseif (! $dateactstart) $dateactstart = time();

				$dateactend = $objp->date_fin;
				if ($_POST["endmonth"]) $dateactend = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
				elseif (! $dateactend)
				{
					if ($objp->fk_product > 0)
					{
						$product=new Product($db);
						$product->fetch($objp->fk_product);
						$dateactend = dolibarr_time_plus_duree (time(), $product->duration_value, $product->duration_unit);
					}
				}

				print '<tr '.$bc[$var].'><td>'.$langs->trans("DateServiceActivate").'</td><td>';
				print $html->select_date($dateactstart,'','','','',"active");
				print '</td>';

				print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
				print $html->select_date($dateactend,"end",'','','',"active");
				print '</td>';
				
				print '<td align="center" rowspan="2" valign="middle">';
				print '<input type="submit" class="button" name="activate" value="'.$langs->trans("Activate").'"><br>';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';

				print '</tr>';

				print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="80" type="text" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';

				print '</table>';

				print '</form>';
			}

			if ($user->rights->contrat->activer && $_REQUEST["action"] == 'unactivateline' && $contrat->lignes[$cursorline-1]->id == $_GET["ligne"])
			{
				/**
				 * Desactiver la ligne de contrat
				 */
				print '<form name="closeline" action="'.$_SERVER["PHP_SELF"].'?id='.$contrat->id.'&amp;ligne='.$contrat->lignes[$cursorline-1]->id.'&amp;action=closeline" method="post">';

				print '<table class="noborder" width="100%">';

				// Definie date debut et fin par defaut
				$dateactstart = $objp->date_debut_reelle;
				if ($_POST["remonth"]) $dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
				elseif (! $dateactstart) $dateactstart = time();

				$dateactend = $objp->date_fin_reelle;
				if ($_POST["endmonth"]) $dateactend = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
				elseif (! $dateactend)
				{
					if ($objp->fk_product > 0)
					{
						$product=new Product($db);
						$product->fetch($objp->fk_product);
						$dateactend = dolibarr_time_plus_duree (time(), $product->duration_value, $product->duration_unit);
					}
				}
				$now=mktime();
				if ($dateactend > $now) $dateactend=$now;

				print '<tr '.$bc[$var].'><td colspan="2">';
				if ($objp->statut >= 4)
				{
					if ($objp->statut == 4)
					{
						print $langs->trans("DateEndReal").' ';
						$form->select_date($dateactend,"end",0,0,($objp->date_fin_reelle>0?0:1),"closeline");
					}
				}
				print '</td>';

				print '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="'.$langs->trans("Close").'"><br>';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</td></tr>';

				print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td><input size="70" type="text" class="flat" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';
				print '</table>';

				print '</form>';
			}		
			
			print '</td>';	// End td if line is 1
						
			print '</tr>';
			print '<tr><td style="border-right: 1px solid #'.$colorb.'">&nbsp;</td></tr>';
			$cursorline++;
		}
		print '</table>';
		
		/*
		 * Ajouter une ligne produit/service
		 */
		if ($user->rights->contrat->creer &&
			($contrat->statut == 0 || ($contrat->statut == 1  && $conf->global->CONTRAT_EDITWHENVALIDATED)) )
		{
			print '<br>';
			print '<table class="noborder" width="100%">';	// Array with (n*2)+1 lines
			
			print "<tr class=\"liste_titre\">";
			print '<td>'.$langs->trans("Service").'</td>';
			print '<td align="center">'.$langs->trans("VAT").'</td>';
			print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
			print '<td align="center">'.$langs->trans("Qty").'</td>';
			print '<td align="right">'.$langs->trans("ReductionShort").'</td>';
			print '<td>&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			$var=false;

			// Service sur produit predefini
			print '<form name="addligne" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="addligne">';
			print '<input type="hidden" name="mode" value="predefined">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print "<tr $bc[$var]>";
			print '<td colspan="3">';
			// multiprix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
				$form->select_produits('','p_idprod','',$conf->produit->limit_size,$contrat->societe->price_level);
			else
				$form->select_produits('','p_idprod','',$conf->produit->limit_size);
			if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';
			print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea>';
			print '</td>';

			print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
			print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$contrat->societe->remise_client.'">%</td>';
			print '<td align="center" colspan="2" rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
			print '</tr>'."\n";
			
			print "<tr $bc[$var]>";
			print '<td colspan="8">';
			print $langs->trans("DateStartPlanned").' ';
			$form->select_date('',"date_start",0,0,1,"addligne");
			print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
			$form->select_date('',"date_end",0,0,1,"addligne");
			print '</td>';
			print '</tr>';
			
			print '</form>';

			$var=!$var;
			
			// Service libre
			print '<form name="addligne_sl" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="addligne">';
			print '<input type="hidden" name="mode" value="libre">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print "<tr $bc[$var]>";
			print '<td><textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';

			print '<td>';
			$form->select_tva("tva_tx",$conf->defaulttx,$mysoc,$contrat->societe);
			print '</td>';
			print '<td align="right"><input type="text" class="flat" size="4" name="pu" value=""></td>';
			print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
			print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$contrat->societe->remise_client.'">%</td>';
			print '<td align="center" rowspan="2" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';

			print '</tr>'."\n";

			print "<tr $bc[$var]>";
			print '<td colspan="8">';
			print $langs->trans("DateStartPlanned").' ';
			$form->select_date('',"date_start_sl",0,0,1,"addligne_sl");
			print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
			$form->select_date('',"date_end_sl",0,0,1,"addligne_sl");
			print '</td>';
			print '</tr>';
			
			print '</form>';

			print '</table>';
		}
	

		
		//print '</td><td align="center" class="tab" style="padding: 4px; border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';

		//print '</td></tr></table>';
	
        print '</div>';


        /*************************************************************
         * Boutons Actions
         *************************************************************/

        if ($user->societe_id == 0)
        {
            print '<div class="tabsAction">';

            if ($contrat->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
				else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a>';
            }

            if ($contrat->statut > 0)
            {
                $langs->load("bills");
				if ($user->rights->facture->creer) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;contratid='.$contrat->id.'&amp;socid='.$contrat->societe->id.'">'.$langs->trans("CreateBill").'</a>';
				else print '<a class="butAction" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
            }

            $numclos=$contrat->array_detail(5); // Tableau des lignes au statut clos
            if ($contrat->statut == 1 && $nbofservices == sizeof($numclos))
            {
                print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=close">'.$langs->trans("Close").'</a>';
            }

            // On peut supprimer entite si
			// - Droit de creer + mode brouillon (erreur creation)
			// - Droit de supprimer
			if (($user->rights->contrat->creer && $contrat->statut == 0) || $user->rights->contrat->supprimer)
            {
                print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            print "</div>";
			print '<br>';
        }

    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
