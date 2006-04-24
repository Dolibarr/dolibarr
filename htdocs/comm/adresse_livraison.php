<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/comm/adresse_livraison.php
        \ingroup    societe
        \brief      Onglet adresse de livraison d'un client
        \version    $Revision$
*/

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/adresse_livraison.class.php");

$user->getrights('societe');
$user->getrights('commercial');

$langs->load("companies");
$langs->load("commercial");
 
if (! $user->rights->societe->creer)
{
    if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
    {
        accessforbidden();
    }
}

$idl = isset($_GET["idl"])?$_GET["idl"]:'';
$origin = isset($_GET["origin"])?$_GET["origin"]:'';
$originid = isset($_GET["originid"])?$_GET["originid"]:'';
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if (! $socid && ($_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add' && $_REQUEST["action"] != 'update')) accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $_GET["action"] = '';
  $_POST["action"] = '';
  $socid = $user->societe_id;
}

// Protection restriction commercial
if (!$user->rights->commercial->client->voir && $socid && !$user->societe_id > 0)
{
        $sql = "SELECT sc.fk_soc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " WHERE sc.fk_soc = ".$socid." AND sc.fk_user = ".$user->id;

        if ( $db->query($sql) )
        {
          if ( $db->num_rows() == 0) accessforbidden();
        }
}

$livraison = new Livraison($db);



/*
 * Actions
 */

if ($_POST["action"] == 'add' || $_POST["action"] == 'update')
{
    $livraison->socid                 = $_POST["socid"];
    $livraison->label                 = $_POST["label"];
    $livraison->nom                   = $_POST["nom"];
    $livraison->adresse               = $_POST["adresse"];
    $livraison->cp                    = $_POST["cp"];
    $livraison->ville                 = $_POST["ville"];
    $livraison->pays_id               = $_POST["pays_id"];
    $livraison->departement_id        = $_POST["departement_id"];
    $livraison->note                  = $_POST["note"];
    
    if ($_POST["action"] == 'add')
    {
        $socid   = $_POST["socid"];
        $origin  = $_POST["origin"];
        $orignid = $_POST["origind"]
        $result  = $livraison->create($socid, $user);
    
        if ($result >= 0)
        {
        	if ($origin == commande)
        	{
        		Header("Location: ../commande/fiche.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
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
        $result = $livraison->update($_POST["idl"], $socid, $user);
        if ($result >= 0)
        {
            Header("Location: adresse_livraison.php?socid=".$socid);
            exit;
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
  $livraison = new Livraison($db);
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
         * Fiche adresse de livraison en mode création
         */

        if ($_POST["label"] && $_POST["nom"])
        {
            $livraison->socid=$_POST["socid"];
            $livraison->label=$_POST["label"];
            $livraison->nom=$_POST["nom"];
            $livraison->adresse=$_POST["adresse"];
            $livraison->cp=$_POST["cp"];
            $livraison->ville=$_POST["ville"];
            $livraison->departement_id=$_POST["departement_id"];
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
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
        print '>';
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
        print '</td></tr>';
        print '<tr><td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$livraison->ville.'"></td></tr>';

        print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($livraison->pays_id,'pays_id',$conf->use_javascript?' onChange="autofilltownfromzip_save_refresh_create()"':'');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
        if ($livraison->pays_id)
        {
            $form->select_departement($livraison->departement_id,$livraison->pays_code);
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';
        
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

    print_titre($langs->trans("EditDeliveyAdress"));

    if ($socid)
    {
        if ($reload || ! $_POST["nom"])
        {
            $livraison = new Livraison($db);
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
            $livraison->departement_id=$_POST["departement_id"];
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
        print '<input type="hidden" name="idl" value="'.$livraison->idl.'">';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('DeliveryAddressLabel').'</td><td colspan="3"><input type="text" size="40" name="label" value="'.$livraison->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$livraison->nom.'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
        print $livraison->adresse;
        print '</textarea></td></tr>';

        print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$livraison->cp.'"';
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
        print '>';
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$livraison->ville.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($livraison->pays_id,'pays_id',$conf->use_javascript?' onChange="autofilltownfromzip_save_refresh_edit()"':'');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
        $form->select_departement($livraison->departement_id,$livraison->pays_code);
        print '</td></tr>';
        
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
     * Fiche société en mode visu
     */
    $livraison = new Livraison($db);
    $result=$livraison->fetch($socid);
    $nblignes = sizeof($livraison->lignes);
    if ($result < 0)
    {
        dolibarr_print_error($db,$livraison->error);
        //exit;     
    }


	  $head = societe_prepare_head($livraison);
    
    dolibarr_fiche_head($head, 'company', $livraison->nom_societe);


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

    for ($i = 0 ; $i < $nblignes ; $i++)
    {
    
      print '<table class="border" width="100%">';

      print '<tr><td width="20%">'.$langs->trans('DeliveryAddressLabel').'</td><td colspan="3">'.$livraison->lignes[$i]->label.'</td>';
      print '<td valign="top" colspan="2" width="50%" rowspan="7">'.$langs->trans('Note').' :<br>'.nl2br($livraison->lignes[$i]->note).'</td></tr>';
      print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$livraison->lignes[$i]->nom.'</td></tr>';
    
      print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($livraison->lignes[$i]->adresse)."</td></tr>";

      print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$livraison->lignes[$i]->cp."</td></tr>";
      print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$livraison->lignes[$i]->ville."</td></tr>";

      print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$livraison->lignes[$i]->pays.'</td>';
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$livraison->lignes[$i]->departement.'</td></tr>';

      print '</table>';
    
    
    /*
    *
    */

        print '<div class="tabsAction">';
      
        if ($user->rights->societe->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&amp;idl='.$livraison->lignes[$i]->idl.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
        }
        
        if ($user->rights->societe->supprimer)
        {
	        print '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&amp;idl='.$livraison->lignes[$i]->idl.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
        }

        
        print '</div>';
        print '<br>';
   }
    print '</div>';
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
