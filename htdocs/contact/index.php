<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
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
	    \file       htdocs/contact/index.php
        \ingroup    societe
		\brief      Page liste des contacts
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");
$langs->load("suppliers");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();


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

if ($type == "f") { $text.=$langs->trans("Suppliers"); }
if ($type == "c") { $text.=$langs->trans("Customers"); }
if ($view == 'phone')  { $text="(Vue Téléphones)"; }
if ($view == 'mail')   { $text="(Vue EMail)"; }
if ($view == 'recent') { $text="(Récents)"; }
$titre = $langs->trans("ListOfContacts")." $text";

if ($_POST["button_removefilter"])
{
    $search_nom="";
    $search_prenom="";
    $search_societe="";
    $search_email="";
    $sall="";
}


/*
 * Mode liste
 *
 */

$sql = "SELECT s.idp, s.nom, p.idp as cidp, p.name, p.firstname, p.email, p.phone, p.phone_mobile, p.fax ";
$sql .= "FROM ".MAIN_DB_PREFIX."socpeople as p ";
$sql .= "LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (s.idp = p.fk_soc) ";
$sql .= "WHERE 1=1 ";

if ($_GET["userid"])    // statut commercial
{
    $sql .= " AND p.fk_user=".$_GET["userid"];
}
if ($search_nom)        // filtre sur le nom
{
    $sql .= " AND p.name like '%".$search_nom."%'";
}
if ($search_prenom)     // filtre sur le prenom
{
    $sql .= " AND p.firstname like '%".$search_prenom."%'";
}
if ($search_societe)    // filtre sur la societe
{
    $sql .= " AND s.nom like '%".$search_societe."%'";
}
if ($search_email)      // filtre sur l'email
{
    $sql .= " AND p.email like '%".$search_email."%'";
}
if ($type == "f")        // filtre sur type
{
    $sql .= " AND fournisseur = 1";
}
if ($type == "c")        // filtre sur type
{
    $sql .= " AND client = 1";
}
if ($sall)
{
    $sql .= " AND (p.name like '%".$sall."%' OR p.firstname like '%".$sall."%' OR p.email like '%".$sall."%') ";
}
if ($socid)
{
    $sql .= " AND s.idp = $socid";
}

if($_GET["view"] == "recent")
{
    $sql .= " ORDER BY p.datec DESC " . $db->plimit( $limit + 1, $offset);
}
else
{
    $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1, $offset);
}

$result = $db->query($sql);

if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print_barre_liste($titre ,$page, "index.php", '&amp;begin='.$_GET["begin"].'&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);


    print '<table class="liste" width="100%">';

    if ($sall)
    {
        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname")." ".$langs->trans("or")." ".$langs->trans("EMail")."): ".$sall;
    }

    // Ligne des titres
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Lastname"),"index.php","p.name", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", "", $sortfield);
    print_liste_field_titre($langs->trans("Firstname"),"index.php","p.firstname", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", "", $sortfield);
    print_liste_field_titre($langs->trans("Company"),"index.php","s.nom", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", "", $sortfield);
    print '<td class="liste_titre">'.$langs->trans("Phone").'</td>';

    if ($_GET["view"] == 'phone')
    {
        print '<td class="liste_titre">'.$langs->trans("Mobile").'</td>';
        print '<td class="liste_titre">'.$langs->trans("Fax").'</td>';
    }
    else
    {
        print_liste_field_titre($langs->trans("EMail"),"index.php","p.email", $begin, "&type=$type&view=$view&search_nom=$search_nom&search_prenom=$search_prenom&search_societe=$search_societe&search_email=$search_email", "", $sortfield);
    }
    print '<td class="liste_titre">&nbsp;</td>';
    print "</tr>\n";

    // Ligne des champs de filtres
    print '<form method="post" action="index.php">';
    print '<input type="hidden" name="view" value="'.$view.'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
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
    print '<td class="liste_titre">';
    print '&nbsp;';
    print '</td>';

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

    print '<td class="liste_titre" align="right">';
    print '<input type="image" value="button_search" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" alt="'.$langs->trans("Search").'">';
    print '&nbsp; <input type="image" value="button_removefilter" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" alt="'.$langs->trans("RemoveFilter").'">';
    print '</td>';
    print '</tr>';
    print '</form>';

    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);

        $var=!$var;

        print "<tr $bc[$var]>";

        print '<td valign="center">';
        print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">';
        print img_object($langs->trans("ShowContact"),"contact");
        print ' '.$obj->name.'</a></td>';
        print '<td>'.$obj->firstname.'</td>';
        print '<td>';
        if ($obj->idp)
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">';
            print img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,40).'</a>';
        }
        else
        {   
            print '&nbsp;';
        }
        print '</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actionid=1&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';

        if ($_GET["view"] == 'phone')
        {
            print '<td>'.dolibarr_print_phone($obj->phone_mobile,$obj->fp_pays).'&nbsp;</td>';

            print '<td colspan="2">'.dolibarr_print_phone($obj->fax,$obj->fp_pays).'&nbsp;</td>';
        }
        else
        {
            print '<td colspan="2">';
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

        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free();
}
else
{
    dolibarr_print_error($db);
}

print '<br>';


/*
 * TODO A virer ?
 * PhProjekt
 */

if (2==1 && (strlen($_GET["search_nom"]) OR strlen($_GET["search_prenom"])))
{


  $sortfield = "p.nachname";
  $sortorder = "ASC";
  
  $sql = "SELECT p.vorname, p.nachname, p.firma, p.email";
  $sql .= " FROM phprojekt.contacts as p";
  $sql .= " WHERE upper(p.nachname) like '%".$_GET["search_nom"]."%'";


$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1, $offset);


$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
 
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre($langs->trans("Name"),"projekt.php","lower(p.name)", $begin);
  print "</td><td>";
  print_liste_field_titre($langs->trans("Fristname"),"projekt.php","lower(p.firstname)", $begin);
  print "</td><td>";
  print_liste_field_titre($langs->trans("Company"),"projekt.php","lower(s.nom)", $begin);
  print '</td>';
  print '<td>'.$langs->trans("Phone").'</td>';

  if ($_GET["view"] == 'phone')
    {
      print '<td>Portable</td>';
      print '<td>Fax</td>';
    }
  else
    {
      print '<td>email</td>';
    }

  print "</tr>\n";
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
    
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td valign="center">';
      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">';
      print img_file();
      print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">'.$obj->nachname.'</a></td>';
      print '<td>'.$obj->vorname.'</td>';
      
      print '<td>';
      print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=$obj->idp\">$obj->firma</A></td>\n";
      
      
      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actionid=1&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';

      if ($_GET["view"] == 'phone')
	{      
	  print '<td>'.dolibarr_print_phone($obj->phone_mobile).'&nbsp;</td>';
      
	  print '<td>'.dolibarr_print_phone($obj->fax).'&nbsp;</td>';
	}
      else
	{
	  print '<td><a href="mailto:'.$obj->email.'">'.$obj->email.'</a>&nbsp;';
	  if (!valid_email($obj->email))
	    {
	      print "Email Invalide !";
	    }
	  print '</td>';
	}

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
