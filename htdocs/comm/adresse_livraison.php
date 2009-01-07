<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/comm/adresse_livraison.php
        \ingroup    societe
        \brief      Onglet adresse de livraison d'un client
        \version    $Id$
*/

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/adresse_livraison.class.php");

$langs->load("companies");
$langs->load("commercial");

$idl = isset($_GET["idl"])?$_GET["idl"]:'';
$origin = isset($_GET["origin"])?$_GET["origin"]:'';
$originid = isset($_GET["originid"])?$_GET["originid"]:'';
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if (! $socid && ($_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add' && $_REQUEST["action"] != 'update')) accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);


/*
 * Actions
 */

if ($_POST["action"] == 'add' || $_POST["action"] == 'update')
{
	$livraison = new AdresseLivraison($db);
    $livraison->socid                 = $_POST["socid"];
    $livraison->label                 = $_POST["label"];
    $livraison->nom                   = $_POST["nom"];
    $livraison->adresse               = $_POST["adresse"];
    $livraison->cp                    = $_POST["cp"];
    $livraison->ville                 = $_POST["ville"];
    $livraison->pays_id               = $_POST["pays_id"];
    $livraison->tel                   = $_POST["tel"];
    $livraison->fax                   = $_POST["fax"];
    $livraison->note                  = $_POST["note"];
    
    if ($_POST["action"] == 'add')
    {
        $socid   = $_POST["socid"];
        $origin  = $_POST["origin"];
        $originid = $_POST["originid"];
        $result  = $livraison->create($socid, $user);
    
        if ($result >= 0)
        {
        	if ($origin == commande)
        	{
        		Header("Location: ../commande/fiche.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
        		exit;
        	}
        	elseif ($origin == propal)
        	{
        		Header("Location: ../comm/propal.php?action=editdelivery_adress&socid=".$socid."&propalid=".$originid);
        		exit;
        	}
        	else
        	{
            Header("Location: adresse_livraison.php?socid=".$socid);
            exit;
          }
        }
        else
        {
            $mesg=$livraison->error;
            $_GET["action"]='create';
        }
    }
    
    if ($_POST["action"] == 'update')
    {
        $socid   = $_POST["socid"];
        $origin  = $_POST["origin"];
        $originid = $_POST["originid"];
        $result = $livraison->update($_POST["idl"], $socid, $user);
        
        if ($result >= 0)
        {
        	if ($origin == commande)
        	{
        		Header("Location: ../commande/fiche.php?id=".$originid);
        		exit;
        	}
        	elseif ($origin == propal)
        	{
        		Header("Location: ../comm/propal.php?propalid=".$originid);
        		exit;
        	}
        	else
        	{
            Header("Location: adresse_livraison.php?socid=".$socid);
            exit;
          }
		}
		else
		{
            $reload = 0;
            $mesg = $livraison->error;
            $_GET["action"]= "edit";
        }
    }

}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->societe->supprimer)
{
  $livraison = new AdresseLivraison($db);
  $result = $livraison->delete($_GET["idl"], $socid);
 
  if ($result == 0)
    {
      Header("Location: adresse_livraison.php?socid=".$socid);
      exit ;
    }
  else
    {
      $reload = 0;
      $_GET["action"]='';
    }
}

/**
 *
 *
 */

llxHeader();

$form = new Form($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
{
	if ($user->rights->societe->creer)
  {
  	/*
     * Fiche adresse de livraison en mode cr�ation
     */

		$livraison = new AdresseLivraison($db);
		
		$societe=new Societe($db);
		$societe->fetch($_GET["socid"]);
		$head = societe_prepare_head($societe);
		
		dolibarr_fiche_head($head, 'customer', $societe->nom);

        if ($_POST["label"] && $_POST["nom"])
        {
            $livraison->socid=$_POST["socid"];
            $livraison->label=$_POST["label"];
            $livraison->nom=$_POST["nom"];
            $livraison->adresse=$_POST["adresse"];
            $livraison->cp=$_POST["cp"];
            $livraison->ville=$_POST["ville"];
            $livraison->tel=$_POST["tel"];
            $livraison->fax=$_POST["fax"];
            $livraison->note=$_POST["note"];
        }

        // On positionne pays_id, pays_code et libelle du pays choisi
        $livraison->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$conf->global->MAIN_INFO_SOCIETE_PAYS;
        if ($livraison->pays_id)
        {
            $sql = "SELECT code, libelle from ".MAIN_DB_PREFIX."c_pays where rowid = ".$livraison->pays_id;
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
            }
            else
            {
                dolibarr_print_error($db);
            }
            $livraison->pays_code=$obj->code;
            $livraison->pays=$obj->libelle;
        }
    
        print_titre($langs->trans("NewDeliveryAddress"));
        print "<br>\n";
    
        if ($livraison->error)
        {
            print '<div class="error">';
            print nl2br($livraison->error);
            print '</div>';
        }
    
        print '<form action="adresse_livraison.php" method="post" name="formsoc">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="origin" value="'.$origin.'">';
        print '<input type="hidden" name="originid" value="'.$originid.'">';
        print '<input type="hidden" name="action" value="add">';
    
        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('DeliveryAddressLabel').'</td><td><input type="text" size="30" name="label" value="'.$livraison->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Name').'</td><td><input type="text" size="30" name="nom" value="'.$livraison->nom.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
        print $livraison->adresse;
        print '</textarea></td></tr>';

        print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$livraison->cp.'"';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
        print '>';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
        print '</td></tr>';
        print '<tr><td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$livraison->ville.'"></td></tr>';

        print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($livraison->pays_id,'pays_id');
        print '</td></tr>';
        
        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$livraison->tel.'"></td></tr>';
        
        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$livraison->fax.'"></td></tr>';
        
        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $livraison->note;
        print '</textarea></td></tr>';

        print '<tr><td colspan="4" align="center">';
        print '<input type="submit" class="button" value="'.$langs->trans('AddDeliveryAddress').'"></td></tr>'."\n";
    
        print '</table>'."\n";
        print '</form>'."\n";
    
    }
}
elseif ($_GET["action"] == 'edit' || $_POST["action"] == 'edit')
{
    /*
     * Fiche societe en mode edition
     */
	$livraison = new AdresseLivraison($db);
	
	$societe=new Societe($db);
  $societe->fetch($_GET["socid"]);
	$head = societe_prepare_head($societe);
		
	dolibarr_fiche_head($head, 'customer', $societe->nom);

  print_titre($langs->trans("EditDeliveryAddress"));
  print "<br>\n";

    if ($socid)
    {
        if ($reload || ! $_POST["nom"])
        {
            $livraison->socid = $socid;
            $livraison->fetch_adresse($idl);
        }
        else
        {
            $livraison->idl=$_POST["idl"];
            $livraison->socid=$_POST["socid"];
            $livraison->label=$_POST["label"];
            $livraison->nom=$_POST["nom"];
            $livraison->adresse=$_POST["adresse"];
            $livraison->zip=$_POST["zip"];
            $livraison->ville=$_POST["ville"];
            $livraison->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$conf->global->MAIN_INFO_SOCIETE_PAYS;
            $livraison->tel=$_POST["tel"];
            $livraison->fax=$_POST["fax"];
            $livraison->note=$_POST["note"];

            // On positionne pays_id, pays_code et libelle du pays choisi
            if ($livraison->pays_id)
            {
                $sql = "SELECT code, libelle from ".MAIN_DB_PREFIX."c_pays where rowid = ".$livraison->pays_id;
                $resql=$db->query($sql);
                if ($resql)
                {
                    $obj = $db->fetch_object($resql);
                }
                else
                {
                    dolibarr_print_error($db);
                }
                $livraison->pays_code=$obj->code;
                $livraison->pays=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
            }
        }

        if ($livraison->error)
        {
            print '<div class="error">';
            print $livraison->error;
            print '</div>';
        }

        print '<form action="adresse_livraison.php?socid='.$livraison->socid.'" method="post" name="formsoc">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="socid" value="'.$livraison->socid.'">';
        print '<input type="hidden" name="origin" value="'.$origin.'">';
        print '<input type="hidden" name="originid" value="'.$originid.'">';
        print '<input type="hidden" name="idl" value="'.$livraison->idl.'">';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('DeliveryAddressLabel').'</td><td colspan="3"><input type="text" size="40" name="label" value="'.$livraison->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$livraison->nom.'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
        print $livraison->adresse;
        print '</textarea></td></tr>';

        print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$livraison->cp.'"';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
        print '>';
        if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$livraison->ville.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($livraison->pays_id,'pays_id');
        print '</td></tr>';
        
        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$livraison->tel.'"></td></tr>';
        
        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$livraison->fax.'"></td></tr>';
        
        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $livraison->note;
        print '</textarea></td></tr>';

        print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

        print '</table>';
        print '</form>';
    }
}
else
{
	/*
	* Fiche soci�t� en mode visu
	*/
	$livraison = new AdresseLivraison($db);
	$result=$livraison->fetch($socid);
	if ($result < 0)
	{
		dolibarr_print_error($db,$livraison->error);
		exit;
	}

	$societe=new Societe($db);
	$societe->fetch($livraison->socid);
	$head = societe_prepare_head($societe);
	
	dolibarr_fiche_head($head, 'customer', $societe->nom);


	// Confirmation de la suppression de la facture
	if ($_GET["action"] == 'delete')
	{
		$html = new Form($db);
		$html->form_confirm("adresse_livraison.php?socid=".$livraison->socid."&amp;idl=".$_GET["idl"],$langs->trans("DeleteDeliveryAddress"),$langs->trans("ConfirmDeleteDeliveryAdress"),"confirm_delete");
		print "<br />\n";
	}

	if ($livraison->error)
	{
		print '<div class="error">';
		print $livraison->error;
		print '</div>';
	}

	$nblignes = sizeof($livraison->lignes);
	if ($nblignes)
	{
		for ($i = 0 ; $i < $nblignes ; $i++)
		{
	
			print '<table class="border" width="100%">';
	
			print '<tr><td width="20%">'.$langs->trans('DeliveryAddressLabel').'</td><td colspan="3">'.$livraison->lignes[$i]->label.'</td>';
			print '<td valign="top" colspan="2" width="50%" rowspan="6">'.$langs->trans('Note').' :<br>'.nl2br($livraison->lignes[$i]->note).'</td></tr>';
			print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$livraison->lignes[$i]->nom.'</td></tr>';
	
			print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($livraison->lignes[$i]->adresse)."</td></tr>";
	
			print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$livraison->lignes[$i]->cp."</td></tr>";
			print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$livraison->lignes[$i]->ville."</td></tr>";
	
			print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$livraison->lignes[$i]->pays.'</td>';
			
			print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($livraison->lignes[$i]->tel,$livraison->lignes[$i]->pays_code,0,$livraison->socid).'</td></tr>';
        
      print '<tr><td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($livraison->lignes[$i]->fax,$livraison->lignes[$i]->pays_code,0,$livraison->socid).'</td></tr>';
			
			print '</td></tr>';
	
			print '</table>';
	
	
			/*
			*
			*/
	
			print '<div class="tabsAction">';
	
			if ($user->rights->societe->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&amp;idl='.$livraison->lignes[$i]->idl.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
			}
	
			if ($user->rights->societe->supprimer)
			{
				print '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&amp;idl='.$livraison->lignes[$i]->idl.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}
	
	
			print '</div>';
			print '<br>';
		}
	}
	else
	{
		print $langs->trans("None");	
	}
	print '</div>';


	/*
	 * Bouton actions
	 */
	 
	if ($_GET["action"] == '')
	{
		print '<div class="tabsAction">';

		if ($user->rights->societe->creer)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&amp;action=create">'.$langs->trans("Add").'</a>';
		}
		print '</div>';
	}

}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
