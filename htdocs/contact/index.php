<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */


/*!
	    \file       htdocs/contact/index.php
        \ingroup    societe
		\brief      Page liste des contacts
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();


$contactname=isset($_GET["contactname"])?$_GET["contactname"]:$_POST["contactname"];
$page = $_GET["page"];
$sortfield = $_GET["sortfield"];
$sortorder = $_GET["sortorder"];

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="p.name";
}

if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($_GET["view"] == 'phone') { $text="(Vue Téléphones)"; }
if ($_GET["view"] == 'mail') { $text="(Vue EMail)"; }
if ($_GET["view"] == 'recent') { $text="(Récents)"; }

$titre = "Liste des contacts $text";


/*
 *
 * Mode liste
 *
 *
 */

$sql = "SELECT s.idp, s.nom, p.idp as cidp, p.name, p.firstname, p.email, p.phone, p.phone_mobile, p.fax ";
$sql .= "FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (s.idp = p.fk_soc)";


if (strlen($_GET["userid"]))  // statut commercial
{
  $sql .= " WHERE p.fk_user=".$_GET["userid"];
}

if (strlen($_GET["search_nom"])) // filtre sur la premiere lettre du nom
{
  $sql .= " WHERE upper(p.name) like '%".$_GET["search_nom"]."%'";
}

if (strlen($_GET["search_prenom"])) // filtre sur la premiere lettre du nom
{
  $sql .= " WHERE upper(p.firstname) like '%".$_GET["search_prenom"]."%'";
}

if ($contactname)
{
  $sql .= " WHERE (p.name like '%".$contactname."%' OR p.firstname like '%".$contactname."%') ";
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
  $num = $db->num_rows();
  $i = 0;

  print_barre_liste($titre ,$page, "index.php", '&amp;begin='.$_GET["begin"].'&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);


  print '<table class="noborder" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Lastname"),"index.php","lower(p.name)", $begin);
  print_liste_field_titre($langs->trans("Firstname"),"index.php","lower(p.firstname)", $begin);
  print_liste_field_titre($langs->trans("Company"),"index.php","lower(s.nom)", $begin);

  print '<td>'.$langs->trans("Phone").'</td>';

  if ($_GET["view"] == 'phone')
    {
      print '<td>'.$langs->trans("Mobile").'</td>';
      print '<td>'.$langs->trans("Fax").'</td>';
    }
  else
    {
      print '<td>'.$langs->trans("EMail").'</td>';
    }

  print "</tr>\n";

  print '<form method="get" action="index.php">';
  print '<tr class="liste_titre">';
  print '<td valign="right">';
  print '<input type="text" name="search_nom" value="'.$_GET["search_nom"].'">';
  print '</td>';
  print '<td valign="right">';
  print '<input type="text" name="search_prenom" value="'.$_GET["search_prenom"].'">';
  print '</td><td colspan="2"><input type="submit"></td><td>&nbsp;</td></tr>';





  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object($result);
    
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td valign="center">';
      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">';
      print img_file();
      print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">'.$obj->name.'</a></td>';
      print "<td>$obj->firstname</TD>";
      
      print '<td>';
      if ($obj->nom)
	{
	  print '<a href="contact.php?socid='.$obj->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0" alt="Filtre"></a>&nbsp;';
	}
      print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      
      
      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actionid=1&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';

      if ($_GET["view"] == 'phone')
	{      
	  print '<td>'.dolibarr_print_phone($obj->phone_mobile).'&nbsp;</td>';
      
	  print '<td>'.dolibarr_print_phone($obj->fax).'&nbsp;</td>';
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


/*
 * PhProjekt
 *
 *
 */

if (2==1 && strlen($_GET["search_nom"]) OR strlen($_GET["search_prenom"]))
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
 
  print '<p><table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre("Nom","projekt.php","lower(p.name)", $begin);
  print "</td><td>";
  print_liste_field_titre("Prénom","projekt.php","lower(p.firstname)", $begin);
  print "</td><td>";
  print_liste_field_titre("Société","projekt.php","lower(s.nom)", $begin);
  print '</td>';

  print '<td>Téléphone</td>';

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
  print $db->error();
  print "<br>".$sql;
}
}





$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
