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
    	\file       htdocs/admin/index.php
		\brief      Page d'accueil de l'espace administration/configuration
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("companies");

if (!$user->admin)
  accessforbidden();

if ( (isset($_POST["action"]) && $_POST["action"] == 'update')
  || (isset($_POST["action"]) && $_POST["action"] == 'updateedit') )
{
    dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM",$_POST["nom"]);
    dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADRESSE",$_POST["address"]);
    dolibarr_set_const($db, "MAIN_INFO_SOCIETE_PAYS",$_POST["pays_id"]);
    dolibarr_set_const($db, "MAIN_MONNAIE",$_POST["currency"]);

    dolibarr_set_const($db, "MAIN_INFO_CAPITAL",$_POST["capital"]);
    dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FORME_JURIDIQUE",$_POST["forme_juridique_code"]);
    dolibarr_set_const($db, "MAIN_INFO_SIREN",$_POST["siren"]);
    dolibarr_set_const($db, "MAIN_INFO_SIRET",$_POST["siret"]);
    dolibarr_set_const($db, "MAIN_INFO_APE",$_POST["ape"]);
    dolibarr_set_const($db, "MAIN_INFO_RCS",$_POST["rcs"]);
    dolibarr_set_const($db, "MAIN_INFO_TVAINTRA",$_POST["tva"]);
    dolibarr_set_const($db, "FACTURE_TVAOPTION",$_POST["optiontva"]);

    if ($_POST['action'] != 'updateedit')
    {
        Header("Location: index.php");
    }
}



llxHeader();

$form = new Form($db);
$countrynotdefined='<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';


print_titre($langs->trans("GlobalSetup"));


print "<br>\n";

if ((isset($_GET["action"]) && $_GET["action"] == 'edit')
 || (isset($_POST["action"]) && $_POST["action"] == 'updateedit') )
{
    /*
     * Edition des paramètres
     */
    print '
    <script language="javascript" type="text/javascript">
    <!--
    function save_refresh()
    {
    	document.form_index.action.value="updateedit";
    	document.form_index.submit();
    //	location.href = "index.php?action=updateedit";
    }
    -->
    </script>
    ';

    print '<form method="post" action="index.php" name="form_index">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("CompanyInfo").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("CompanyName").'</td><td>';
    print '<input name="nom" value="'. $conf->global->MAIN_INFO_SOCIETE_NOM . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("CompanyAddress").'</td><td>';
    print '<textarea name="address" cols="50" rows="2">'. $conf->global->MAIN_INFO_SOCIETE_ADRESSE . '</textarea></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Country").'</td><td>';
    $form->select_pays($conf->global->MAIN_INFO_SOCIETE_PAYS,'pays_id',' onChange="save_refresh()"');
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("CompanyCurrency").'</td><td>';
    $form->select_currency($conf->global->MAIN_MONNAIE,"currency");
    print '</td></tr>';

    print '</table>';

    print '<br>';

    // Identifiants de la société (propre au pays)
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=true;

    $langs->load("companies");

    // Recupere code pays
    $code_pays=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
    if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
    {
        $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
        $sql .= " WHERE rowid = ".$conf->global->MAIN_INFO_SOCIETE_PAYS;
        $result=$db->query($sql);
        if ($result) {
            $obj = $db->fetch_object();
            if ($obj->code) $code_pays=$obj->code;
        }
        else {
            dolibarr_print_error($db);
        }
    }

    // Capital
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("Capital").'</td><td>';
    print '<input name="capital" size="20" value="' . $conf->global->MAIN_INFO_CAPITAL . '"></td></tr>';

    // Forme juridique
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("JuridicalStatus").'</td><td>';
    if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
    {
        $form->select_forme_juridique($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE,$code_pays);
    }
    else
    {
        print $countrynotdefined;
    }
    print '</td></tr>';

    // ProfID1
    if ($langs->transcountry("ProfId1",$code_pays) != '-')
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId1",$code_pays).'</td><td>';
        if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
        {
            print '<input name="siren" size="20" value="' . $conf->global->MAIN_INFO_SIREN . '">';
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';
    }

    // ProfId2
    if ($langs->transcountry("ProfId2",$code_pays) != '-')
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId2",$code_pays).'</td><td>';
        if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
        {
            print '<input name="siret" size="20" value="' . $conf->global->MAIN_INFO_SIRET . '">';
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';
    }

    // ProfId3
    if ($langs->transcountry("ProfId3",$code_pays) != '-')
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId3",$code_pays).'</td><td>';
        if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
        {
            print '<input name="ape" size="20" value="' . $conf->global->MAIN_INFO_APE . '">';
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';
    }

    // ProfId4
    if ($langs->transcountry("ProfId4",$code_pays) != '-')
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId4",$code_pays).'</td><td>';
        if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
        {
            print '<input name="rcs" size="20" value="' . $conf->global->MAIN_INFO_RCS . '">';
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';
    }

    // TVA Intra
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("TVAIntra").'</td><td>';
    print '<input name="tva" size="20" value="' . $conf->global->MAIN_INFO_TVAINTRA . '"></td></tr>';

    print '</table>';

    
    
    /*
     *  Options fiscale
     */
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("VATManagement").'</td><td>'.$langs->trans("Description").'</td>';
    print '<td align="right">&nbsp;</td>';
    print "</tr>\n";
    $var=true;
    
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" value=\"reel\"".($conf->global->FACTURE_TVAOPTION != "franchise"?" checked":"")."> ".$langs->trans("VATIsUsed")."</label></td>";
    print '<td colspan="2">';
    print "<table>";
    print "<tr><td>".$langs->trans("VATIsUsedDesc")."</td></tr>";
    print "<td><i>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</i></td></tr>\n";
    print "</table>";
    print "</td></tr>\n";
    
    /* Je désactive cette option "facturation" car ce statut fiscal n'existe pas. Seul le réel et franchise existe.
    Cette option ne doit donc pas etre en "exclusif" avec l'option fiscale de gestion de tva. Peut etre faut-il
    une option a part qui n'entre pas en conflit avec les choix "assujéti TVA" ou "non".
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" value=\"facturation\"".($conf->global->FACTURE_TVAOPTION == "facturation"?" checked":"")."> Option facturation</label></td>";
    print "<td colspan=\"2\">L'option 'facturation' est utilisée par les entreprises qui payent la TVA à facturation (vente de matériel).</td></tr>\n";
     */
    
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" value=\"franchise\"".($conf->global->FACTURE_TVAOPTION == "franchise"?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</label></td>";
    print '<td colspan="2">';
    print "<table>";
    print "<tr><td>".$langs->trans("VATIsNotUsedDesc")."</td></tr>";
    print "<td><i>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</i></td></tr>\n";
    print "</table>";
    print "</td></tr>\n";
    
    print "</table>";
    


    print '<br><center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';
    print '<br>';
    
    print '</form>';
}
else
{
    /*
     * Affichage des paramètres
     */

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("CompanyInfo").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=true;

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("CompanyName").'</td><td>' . $conf->global->MAIN_INFO_SOCIETE_NOM . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("CompanyAddress").'</td><td>' . nl2br($conf->global->MAIN_INFO_SOCIETE_ADRESSE) . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Country").'</td><td>';
    print $form->pays_name($conf->global->MAIN_INFO_SOCIETE_PAYS,1);
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("CompanyCurrency").'</td><td>';
    print $form->currency_name($conf->global->MAIN_MONNAIE,1);
    print '</td></tr>';

    print '</table>';

    print '<br>';

    // Identifiants de la société (propre au pays)
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=true;

    // Recupere code pays
    $code_pays=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
    if ($conf->global->MAIN_INFO_SOCIETE_PAYS)
    {
        $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
        $sql .= " WHERE rowid = ".$conf->global->MAIN_INFO_SOCIETE_PAYS;
        $result=$db->query($sql);
        if ($result)
        {
            $obj = $db->fetch_object();
            if ($obj->code) $code_pays=$obj->code;
        }
        else {
            dolibarr_print_error($db);
        }
    }

    // Capital
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("Capital").'</td><td>';
    print $conf->global->MAIN_INFO_CAPITAL . '</td></tr>';

    // Forme juridique
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("JuridicalStatus").'</td><td>';
    print $form->forme_juridique_name($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE,1);
    print '</td></tr>';

    // ProfId1
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId1",$code_pays).'</td><td>';
    if ($langs->transcountry("ProfId1",$code_pays) != '-')
    {
        print $conf->global->MAIN_INFO_SIREN;
    }
    print '</td></tr>';

    // ProfId2
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId2",$code_pays).'</td><td>';
    if ($langs->transcountry("ProfId2",$code_pays) != '-')
    {
        print $conf->global->MAIN_INFO_SIRET;
    }
    print '</td></tr>';

    // ProfId3
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId3",$code_pays).'</td><td>';
    if ($langs->transcountry("ProfId3",$code_pays) != '-')
    {
        print $conf->global->MAIN_INFO_APE;
    }
    print '</td></tr>';

    // ProfId4
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->transcountry("ProfId4",$code_pays).'</td><td>';
    if ($langs->transcountry("ProfId4",$code_pays) != '-')
    {
        print $conf->global->MAIN_INFO_RCS;
    }
    print '</td></tr>';

    // TVA Intracommunautaire
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("TVAIntra").'</td><td>' . $conf->global->MAIN_INFO_TVAINTRA . '</td></tr>';

    print '</table>';


    /*
     *  Options fiscale
     */
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("VATManagement").'</td><td>'.$langs->trans("Description").'</td>';
    print '<td align="right">&nbsp;</td>';
    print "</tr>\n";
    $var=true;
    
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" disabled value=\"reel\"".($conf->global->FACTURE_TVAOPTION != "franchise"?" checked":"")."> ".$langs->trans("VATIsUsed")."</label></td>";
    print '<td colspan="2">';
    print "<table>";
    print "<tr><td>".$langs->trans("VATIsUsedDesc")."</td></tr>";
    print "<td><i>".$langs->trans("Example").': '.$langs->trans("VATIsUsedExampleFR")."</i></td></tr>\n";
    print "</table>";
    print "</td></tr>\n";
    
    /* Je désactive cette option "facturation" car ce statut fiscal n'existe pas. Seul le réel et franchise existe.
    Cette option ne doit donc pas etre en "exclusif" avec l'option fiscale de gestion de tva. Peut etre faut-il
    une option a part qui n'entre pas en conflit avec les choix "assujéti TVA" ou "non".
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" value=\"facturation\"".($conf->global->FACTURE_TVAOPTION == "facturation"?" checked":"")."> Option facturation</label></td>";
    print "<td colspan=\"2\">L'option 'facturation' est utilisée par les entreprises qui payent la TVA à facturation (vente de matériel).</td></tr>\n";
     */
    
    $var=!$var;
    print "<tr ".$bc[$var]."><td width=\"140\"><label><input type=\"radio\" name=\"optiontva\" disabled value=\"franchise\"".($conf->global->FACTURE_TVAOPTION == "franchise"?" checked":"")."> ".$langs->trans("VATIsNotUsed")."</label></td>";
    print '<td colspan="2">';
    print "<table>";
    print "<tr><td>".$langs->trans("VATIsNotUsedDesc")."</td></tr>";
    print "<td><i>".$langs->trans("Example").': '.$langs->trans("VATIsNotUsedExampleFR")."</i></td></tr>\n";
    print "</table>";
    print "</td></tr>\n";
    
    print "</table>";
    
    
    // Boutons d'action
    print '<div class="tabsAction">';
    print '<a class="tabAction" href="index.php?action=edit">'.$langs->trans("Edit").'</a>';
    print '</div>';

}


llxFooter('$Date$ - $Revision$');

?>
