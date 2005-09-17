<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/addpropal.php
        \ingroup    propal
		\brief      Page d'ajout d'une proposition commmercial
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("propal");
$langs->load("projects");
$langs->load("companies");


$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


if (defined("PROPALE_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php"))
{
  require(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php");
}

llxHeader();

print_titre($langs->trans("NewProp"));

$form=new Form($db);


/*
 *
 * Creation d'une nouvelle propale
 *
 */
if ($_GET["action"] == 'create')
{
    $soc = new Societe($db);
    $result=$soc->fetch($_GET["socidp"]);
    if ($result < 0)
    {
        dolibarr_print_error($db,$soc->error);
        exit;
    }

    $obj = PROPALE_ADDON;
    $modPropale = new $obj;
    $numpr = $modPropale->propale_get_num($soc);
    $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."propal WHERE ref like '$numpr%'";

    if ( $db->query($sql) )
    {
        $num = $db->result(0, 0);
        $db->free();
        if ($num > 0)
        {
            $numpr .= "." . ($num + 1);
        }
    }

    print "<form action=\"propal.php?socidp=".$soc->id."\" method=\"post\">";
    print "<input type=\"hidden\" name=\"action\" value=\"add\">";

    print '<table class="border" width="100%">';

    print '<tr><td>'.$langs->trans("Ref").'</td><td><input name="ref" value="'.$numpr.'"></td>';

    print '<td valign="top" colspan="2">';
    print $langs->trans("Comments").'</td></tr>';

    print '<tr><td>'.$langs->trans("Company").'</td><td><a href="fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
    print '<td rowspan="5" colspan="2" valign="top">';
    print '<textarea name="note" wrap="soft" cols="40" rows="6"></textarea>';
    print '</tr>';

    print "<tr><td>".$langs->trans("Date")."</td><td>";
    $form->select_date();
    print "</td></tr>";

    print '<tr><td>'.$langs->trans("Author").'</td><td>'.$user->fullname.'</td></tr>';
    print '<tr><td>'.$langs->trans("ValidityDuration").'</td><td><input name="duree_validite" size="5" value="15"> '.$langs->trans("days").'</td></tr>';

    /*
    * Destinataire de la propale
    */
    print "<tr><td>".$langs->trans("Contact")."</td><td>\n";
    $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email FROM ".MAIN_DB_PREFIX."socpeople as p";
    $sql .= " WHERE p.fk_soc = ".$soc->id;

    if ( $db->query($sql) )
    {
        $i = 0 ;
        $numdest = $db->num_rows();

        if ($numdest==0)
        {
            print '<font class="error">Cette societe n\'a pas de contact, veuillez en créer un avant de faire votre proposition commerciale</font><br>';
            print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans("AddContact").'</a>';
        }
        else
        {
            print "<select name=\"contactidp\">\n";

            while ($i < $numdest)
            {
                $contact = $db->fetch_object();
                print '<option value="'.$contact->idp.'"';
                if ($contact->idp == $setcontact)
                {
                    print ' selected';
                }
                print '>'.$contact->firstname.' '.$contact->name;
                if ($contact->email) { print ' &lt;'.$contact->email.'&gt;'; }
                print '</option>';
                $i++;
            }
            print '</select>';
        }

        $db->free();
    }
    else
    {
        dolibarr_print_error($db);
    }

    print '</td></tr>';

    print '<tr>';
    if ($conf->projet->enabled)
    {
        /*
         * Projet associ
         */
        print '<td valign="top">'.$langs->trans("Project").'</td><td>';

        $numprojet=$form->select_projects($soc->id,0,'projetidp');
        if ($numprojet==0)
        {
            print ' &nbsp; <a href=../projet/fiche.php?socidp='.$soc->id.'&action=create>'.$langs->trans("AddProject").'</a>';
        }
        print '</td>';
    }
    else {
        print '<td colspan="2">&nbsp;</td>';
    }

    print '<td>Modèle</td>';
    print '<td>';
    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
    $model=new ModelePDFPropales();
    $liste=$model->liste_modeles($db);
    $form->select_array("modelpdf",$liste,$conf->global->PROPALE_ADDON_PDF);
    print "</td></tr></table>";

    print '<br>';

    if ($conf->produit->enabled || $conf->service->enabled)
    {
        $titre=$langs->trans("ProductsAndServices");
        $lib=$langs->trans("Product").'/'.$langs->trans("Services");

        print_titre($titre);

        print '<table class="border">';
        print '<tr><td>'.$lib.'</td><td>'.$langs->trans("Qty").'</td><td>'.$langs->trans("Discount").'</td></tr>';
        for ($i = 1 ; $i <= PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
        {
            print '<tr><td>';
            $form->select_produits('',"idprod".$i);
            print '</td>';
            print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
            print '<td><input type="text" size="3" name="remise'.$i.'" value="'.$soc->remise_client.'"> %</td></tr>';
        }

        print "</table>";

        print '<br>';
    }

    /*
    * Si il n'y a pas de contact pour la societe on ne permet pas la creation de propale
    */
    if ($numdest > 0)
    {
        $langs->load("bills");
        print '<center>';
        print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
        print '</center>';
    }
    print "</form>";
}

$db->close();
llxFooter('$Date$ - $Revision$');
?>
