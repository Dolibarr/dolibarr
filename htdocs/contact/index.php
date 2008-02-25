<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      �ric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contact/index.php
        \ingroup    societe
		\brief      Page liste des contacts
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$langs->load("companies");
$langs->load("suppliers");

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
$result = restrictedArea($user, 'contact', $contactid,'',1);

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_prenom=isset($_GET["search_prenom"])?$_GET["search_prenom"]:$_POST["search_prenom"];
$search_societe=isset($_GET["search_societe"])?$_GET["search_societe"]:$_POST["search_societe"];
$search_email=isset($_GET["search_email"])?$_GET["search_email"]:$_POST["search_email"];

$type = isset($_GET["type"])?$_GET["type"]:$_POST["type"];
$view=isset($_GET["view"])?$_GET["view"]:$_POST["view"];

$sall=isset($_GET["contactname"])?$_GET["contactname"]:$_POST["contactname"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.name";
if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$langs->load("companies");
$titre=$langs->trans("ListOfContacts");
if ($type == "c")
{
	$titre=$langs->trans("ListOfContacts").'  ('.$langs->trans("ThirdPartyCustomers").')'; 
	$urlfiche="fiche.php";
}
if ($type == "p")
{
	$titre=$langs->trans("ListOfContacts").'  ('.$langs->trans("ThirdPartyProspects").')'; 
	$urlfiche="prospect/fiche.php";
}
if ($type == "f") {
	$titre=$langs->trans("ListOfContacts").' ('.$langs->trans("ThirdPartySuppliers").')';
	$urlfiche="fiche.php";
}
if ($view == 'phone')  { $text="( Vue T�l�phones)"; }
if ($view == 'mail')   { $text=" (Vue EMail)"; }
if ($view == 'recent') { $text=" (R�cents)"; }
$titre = $titre." $text";

if ($_POST["button_removefilter"])
{
    $search_nom="";
    $search_prenom="";
    $search_societe="";
    $search_email="";
    $sall="";
}




/*
 * Affichage liste
 *
 */
 
llxHeader();

$sql = "SELECT s.rowid as socid, s.nom, ";
$sql.= " p.rowid as cidp, p.name, p.firstname, p.email, p.phone, p.phone_mobile, p.fax,";
$sql.= " ".$db->pdate("p.tms")." as tms";
$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql.= " WHERE 1=1 ";
if (!$user->rights->commercial->client->voir && !$socid) //restriction
{
	$sql .= " AND IFNULL(sc.fk_user, ".$user->id.") = " .$user->id;
}
if ($_GET["userid"])    // statut commercial
{
    $sql .= " AND p.fk_user_creat=".$_GET["userid"];
}
if ($search_nom)        // filtre sur le nom
{
    $sql .= " AND p.name like '%".addslashes($search_nom)."%'";
}
if ($search_prenom)     // filtre sur le prenom
{
    $sql .= " AND p.firstname like '%".addslashes($search_prenom)."%'";
}
if ($search_societe)    // filtre sur la societe
{
    $sql .= " AND s.nom like '%".addslashes($search_societe)."%'";
}
if ($search_email)      // filtre sur l'email
{
    $sql .= " AND p.email like '%".addslashes($search_email)."%'";
}
if ($type == "f")        // filtre sur type
{
    $sql .= " AND fournisseur = 1";
}
if ($type == "c")        // filtre sur type
{
    $sql .= " AND client = 1";
}
if ($type == "p")        // filtre sur type
{
    $sql .= " AND client = 2";
}
if ($sall)
{
    $sql .= " AND (p.name like '%".addslashes($sall)."%' OR p.firstname like '%".addslashes($sall)."%' OR p.email like '%".addslashes($sall)."%') ";
}
if ($socid)
{
    $sql .= " AND s.rowid = ".$socid;
}

if($_GET["view"] == "recent")
{
    $sql .= " ORDER BY p.datec DESC " . $db->plimit( $limit + 1, $offset);
}
else
{
    $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1, $offset);
}

dolibarr_syslog("contact/index.php sql=".$sql);
$result = $db->query($sql);

if ($result)
{
	$contactstatic=new Contact($db);
	
    $begin=$_GET["begin"];
    
    $num = $db->num_rows($result);
    $i = 0;

    print_barre_liste($titre ,$page, "index.php", '&amp;begin='.$begin.'&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);

    print '<form method="post" action="index.php">';
    print '<input type="hidden" name="view" value="'.$view.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

    print '<table class="liste" width="100%">';

    if ($sall)
    {
        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname")." ".$langs->trans("or")." ".$langs->trans("EMail")."): ".$sall;
    }

    // Ligne des titres
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Lastname"),"index.php","p.name", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Firstname"),"index.php","p.firstname", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", '', $sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),"index.php","s.nom", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", '', $sortfield,$sortorder);
    print '<td class="liste_titre">'.$langs->trans("Phone").'</td>';

    if ($_GET["view"] == 'phone')
    {
        print '<td class="liste_titre">'.$langs->trans("Mobile").'</td>';
        print '<td class="liste_titre">'.$langs->trans("Fax").'</td>';
    }
    else
    {
        print_liste_field_titre($langs->trans("EMail"),"index.php","p.email", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", "", $sortfield,$sortorder);
    }
    print_liste_field_titre($langs->trans("DateModification"),"index.php","p.tms", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", 'align="center"', $sortfield,$sortorder);
    print '<td class="liste_titre">&nbsp;</td>';
    print "</tr>\n";

    // Ligne des champs de filtres
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_nom" size="12" value="'.$search_nom.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_prenom" size="10" value="'.$search_prenom.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" name="search_societe" size="14" value="'.$search_societe.'">';
    print '</td>';
    if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    {
    	print '<td class="liste_titre">';
    	print '&nbsp;';
    	print '</td>';
    }
    
    if ($_GET["view"] == 'phone')
    {
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
    }
    else
    {
        print '<td class="liste_titre">';
        print '<input class="flat" type="text" name="search_email" size="12" value="'.$search_email.'">';
        print '</td>';
    }

	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" value="button_search" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
    print '&nbsp; <input type="image" value="button_removefilter" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" alt="'.$langs->trans("RemoveFilter").'">';
    print '</td>';
    print '</tr>';

    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);

        $var=!$var;

        print "<tr $bc[$var]>";
        
		// Name
		print '<td valign="center">';
		$contactstatic->name=$obj->name;
		$contactstatic->firstname='';
		$contactstatic->id=$obj->cidp;
		print $contactstatic->getNomUrl(1);
        print '</td>';
        
		// Firstname
        print '<td>'.$obj->firstname.'</td>';
        
		// Company
		print '<td>';
        if ($obj->socid)
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">';
            print img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,24).'</a>';
        }
        else
        {   
            print '&nbsp;';
        }
        print '</td>';
		
    	if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    	{
        	print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actioncode=AC_TEL&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->socid.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';
    	}
    	
        if ($_GET["view"] == 'phone')
        {
            print '<td>'.dolibarr_print_phone($obj->phone_mobile,$obj->fp_pays).'&nbsp;</td>';

            print '<td>'.dolibarr_print_phone($obj->fax,$obj->fp_pays).'&nbsp;</td>';
        }
        else
        {
            print '<td>';
            if (! $obj->email) {
                print '&nbsp;';
            }
            elseif (! ValidEmail($obj->email))
            {
                print "Email Invalide !";
            }
            else {
                print '<a href="mailto:'.$obj->email.'">'.$obj->email.'</a>';
            }
            print '</td>';
        }

		// Date
		print '<td align="center">'.dolibarr_print_date($obj->tms,"day").'</td>';
				
		// Link export vcard
        print '<td align="right">';
        print '<a href="'.DOL_URL_ROOT.'/contact/vcard.php?id='.$obj->cidp.'">';
        print img_vcard($langs->trans("VCard")).' ';
        print '</a></td>';

        print "</tr>\n";
        $i++;
    }
    
    if ($num > $limit) print_barre_liste($titre ,$page, "index.php", '&amp;begin='.$begin.'&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);
    
    print "</table>";

    print '</form>';

    $db->free($result);
}
else
{
    dolibarr_print_error($db);
}

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
