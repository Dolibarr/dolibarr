<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");
require("../contact.class.php");
require("../lib/webcal.class.php");
require("../cactioncomm.class.php");
require("../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("companies");


llxHeader();


$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}


if ($_GET["action"] == 'attribute_prefix') {
  $societe = new Societe($db, $_GET["socid"]);
  $societe->attribute_prefix($db, $_GET["socid"]);
}

if ($action == 'recontact') {
  $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'".  $user->login ."')";
  $result = $db->query($sql);
}

if ($action == 'stcomm') {
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm) {
    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
    $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $user->login . "')";
    $result = @$db->query($sql);

    if ($result) {
      $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=$stcommid WHERE idp=$socid";
      $result = $db->query($sql);
    } else {
      $errmesg = "ERREUR DE DATE !";
    }
  }

  if ($actioncommid) {
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
    $result = @$db->query($sql);

    if (!$result) {
      $errmesg = "ERREUR DE DATE !";
    }
  }
}

/*
 * Recherche
 *
 *
 */
if ($mode == 'search') {
  if ($mode-search == 'soc') {
    $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
    $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
  }
      
  if ( $db->query($sql) ) {
    if ( $db->num_rows() == 1) {
      $obj = $db->fetch_object();
      $socid = $obj->idp;
    }
    $db->free();
  }
}
/*
 *
 */
$_socid = $_GET["socid"];
/*
 * Sécurité si un client essaye d'accéder à une autre fiche que la sienne
 */
if ($user->societe_id > 0) 
{
  $_socid = $user->societe_id;
}
/*********************************************************************************
 *
 * Mode fiche
 *
 *
 *********************************************************************************/  
if ($_socid > 0)
{
  // On recupere les donnees societes par l'objet
  $objsoc = new Societe($db);
  $objsoc->id=$_socid;
  $objsoc->fetch($_socid,$to);
  
  $dac = strftime("%Y-%m-%d %H:%M", time());
  if ($errmesg)
    {
      print "<b>$errmesg</b><br>";
    }
  
  /*
   * Affichage onglets
   */
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
  $head[$h][1] = "Fiche société";
  $h++;
  
  if ($objsoc->client==1)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = 'Client';
      $h++;
    }
  if ($objsoc->client==2)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->socid;
      $head[$h][1] = 'Prospect';
      $h++;
    }
  if ($objsoc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = 'Fournisseur';
      $h++;
    }
  
  if ($conf->compta->enabled) {
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = 'Comptabilité';
    $h++;
  }

    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = 'Note';
    $h++;

    if ($user->societe_id == 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
        $head[$h][1] = 'Documents';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = 'Notifications';

      if (file_exists(DOL_DOCUMENT_ROOT.'/sl/'))
	{
	  $head[$h][0] = DOL_URL_ROOT.'/sl/fiche.php?id='.$objsoc->id;
	  $head[$h][1] = 'Fiche catalogue';
	  $h++;
	}

    if ($user->societe_id == 0)
      {
	$head[$h][0] = DOL_URL_ROOT."/comm/index.php?socidp=$objsoc->id&action=add_bookmark";
	$head[$h][1] = '<img border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/bookmark.png" alt="Bookmark" title="Bookmark">';
	$head[$h][2] = 'image';
      }

    dolibarr_fiche_head($head, $hselected, $objsoc->nom);

    /*
     *
     *
     */
    print '<table width="100%" border="0">';
    print '<tr><td valign="top">';
    print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';

    print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">';
    print $objsoc->nom;
    print '</td></tr>';
    print "<tr><td valign=\"top\">".$langs->trans("Address")."</td><td colspan=\"3\">".nl2br($objsoc->adresse)."<br>".$objsoc->cp." ".$objsoc->ville." ".$objsoc->pays."</td></tr>";
    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($objsoc->tel).'&nbsp;</td><td>Fax</td><td>'.dolibarr_print_phone($objsoc->fax).'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$objsoc->url\">$objsoc->url</a>&nbsp;</td></tr>";

    print "<tr><td>Siren</td><td><a href=\"http://www.societe.com/cgi-bin/recherche?rncs=$objsoc->siren\">$objsoc->siren</a>&nbsp;</td>";
    print "<td>prefix</td><td>";
    if ($objsoc->prefix_comm)
      {
	print $objsoc->prefix_comm;
      }
    else
      {
	print "[<a href=\"fiche.php?socid=$objsoc->id&action=attribute_prefix\">Attribuer</a>]";
      }

    print "</td></tr>";

    print "<tr><td>".$langs->trans("Type")."</td><td> $objsoc->typent</td><td>Effectif</td><td>$objsoc->effectif</td></tr>";
    print '<tr><td colspan="2"><a href="remise.php?id='.$objsoc->id.'">';
    print img_edit("Modifier la remise");
    print "</a>";
    print $langs->trans("CustomerDiscount").'</td><td colspan="2">'.$objsoc->remise_client."&nbsp;%</td></tr>";

    print "</table>";

    print "<br>";
    
    /*
     *
     */
    print "</td>\n";
    
    //if ($conf->propal->enabled) {
    
    print '<td valign="top" width="50%">';

    /*
     *
     * Propales
     *
     */
    $var = true;
    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="1">';
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.remise, ".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
    $sql .= " AND s.idp = ".$objsoc->id." ORDER BY p.datep DESC";

    if ( $db->query($sql) )
      {
	$num = $db->num_rows();
	if ($num >0 )
	  {
	    print "<tr $bc[$var]><td colspan=\"4\"><a href=\"propal.php?socidp=$objsoc->id\">Liste des propales ($num)</td></tr>";
	    $var=!$var;
	  }
	$i = 0;	$now = time(); $lim = 3600 * 24 * 15 ;
	while ($i < $num && $i < 2)
	  {
	    $objp = $db->fetch_object();
	    print "<tr $bc[$var]>";
	    print "<td><a href=\"propal.php?propalid=$objp->propalid\">$objp->ref</a>\n";
	    if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	      {
		print " <b>&gt; 15 jours</b>";
	      }
	    print "</td><td align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	    print '<td align="right" width="120">'.price($objp->price).'</td>';
	    print '<td width="100" align="center">'.$objp->statut.'</td></tr>';
	    $var=!$var;
	    $i++;
	  }
	$db->free();
      }
    else {
        print "Erreur ".$db->error()."<br>".$sql;    
    }
    /*
     * Commandes
     *
     */
    print '<table class="border" width="100%" cellspacing="0" cellpadding="1">';
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.total_ht, p.ref, ".$db->pdate("p.date_commande")." as dp";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as p WHERE p.fk_soc = s.idp ";
    $sql .= " AND s.idp = $objsoc->id ORDER BY p.date_commande DESC";

    if ( $db->query($sql) )
      {
	$num = $db->num_rows();
	if ($num >0 )
	  {
	    print "<tr $bc[$var]>";
	    print '<td colspan="4"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socidp='.$objsoc->id.'">Liste des commandes ('.$num.')</td></tr>';
	  }
	$i = 0;	$now = time(); $lim = 3600 * 24 * 15 ;
	while ($i < $num && $i < 2)
	  {
	    $objp = $db->fetch_object();
	    $var=!$var;
	    print "<tr $bc[$var]>";
	    print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->propalid.'">'.$objp->ref."</a>\n";
	    if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	      {
		print " <b>&gt; 15 jours</b>";
	      }
	    print "</td><td align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	    print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
	    print '<td align="center" width="100">'.$objp->statut.'</td></tr>';
	    $i++;
	  }
	$db->free();
      }    

    /*
     *
     * Liste des projets associés
     *
     */
    $sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
    $sql .= " FROM ".MAIN_DB_PREFIX."projet as p WHERE p.fk_soc = $objsoc->id";
    if ( $db->query($sql) ) {
      print "<table class=\"border\" cellspacing=0 width=100% cellpadding=\"1\">";
      $i = 0 ; 
      $num = $db->num_rows();
      if ($num > 0) {
	$tag = !$tag; print "<tr $bc[$tag]>";
	print "<td colspan=\"2\"><a href=\"../projet/index.php?socidp=$objsoc->id\">liste des projets ($num)</td></tr>";
      }
      while ($i < $num && $i < 5) {
	$obj = $db->fetch_object();
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.$obj->title.'</a></td>';

	print "<td align=\"right\">".$obj->ref ."</td></tr>";
	$i++;
      }
      $db->free();
      print "</table>";
    } else {
      print $db->error();
    }

    /*
     *
     *
     */
    print "</td></tr>";
    print "</table></div>\n";
    
    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if ($conf->propal->enabled && $user->rights->propale->creer)
      {
	print '<a class="tabAction" href="addpropal.php?socidp='.$objsoc->id.'&amp;action=create">Proposition</a>';
      }

    if ($conf->commande->enabled && $user->rights->commande->creer)
      {
	print '<a class="tabAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socidp='.$objsoc->id.'&amp;action=create">'.$langs->trans("Order").'</a>';
      }

    if ($conf->projet->enabled && $user->rights->projet->creer)
      {
	print '<a class="tabAction" href="../projet/fiche.php?socidp='.$objsoc->id.'&action=create">'.$langs->trans("Project").'</a>';
      }

    if ($conf->fichinter->enabled)
      {
	print '<a class="tabAction" href="../fichinter/fiche.php?socidp='.$objsoc->id.'&amp;action=create">Intervention</a>';
      }
  
    print '</div>';
    print '<br>';
    
    /*
     *
     *
     *
     */
    if ($action == 'changevalue') {
      print "<hr noshade>";
      print "<form action=\"index.php?socid=$objsoc->id\" method=\"post\">";
      print "<input type=\"hidden\" name=\"action\" value=\"cabrecrut\">";
      print "Cette société est un cabinet de recrutement : ";
      print "<select name=\"selectvalue\">";
      print "<option value=\"\">";
      print "<option value=\"t\">Oui";
      print "<option value=\"f\">Non";
      print "</select>";
      print "<input type=\"submit\" value=\"".$langs->trans("Valid")."\">";
      print "</form>\n";
    } else {
      /*
       *
       * Liste des contacts
       *
       */
      print '<table class="noborder" width="100%" cellspacing="1" cellpadding="2">';

      print '<tr class="liste_titre"><td>'.$langs->trans("Firstname").' '.$langs->trans("LastName").'</td>';
      print '<td>'.$langs->trans("Poste").'</td><td>'.$langs->trans("Tel").'</td>';
      print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
      print "<td align=\"center\"><a href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$objsoc->id."&amp;action=create\">".$langs->trans("AddContact")."</a></td>";
      print '<td>&nbsp;</td>';
      print "</tr>";
    
      $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note FROM ".MAIN_DB_PREFIX."socpeople as p WHERE p.fk_soc = $objsoc->id  ORDER by p.datec";
      $result = $db->query($sql);
      $i = 0 ; $num = $db->num_rows(); $tag = True;
      while ($i < $num)
	{
	$obj = $db->fetch_object();
	$var = !$var;
	print "<tr $bc[$var]>";

	print '<td>';
    print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->idp.'">';
    print img_file();
    print '&nbsp;'.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

	if ($obj->note)
	  {
	    print "<br>".nl2br($obj->note);
	  }
	print "</td>";
	print "<td>$obj->poste&nbsp;</td>";
	print '<td><a href="action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$objsoc->id.'">'.$obj->phone.'</a>&nbsp;</td>';
	print '<td><a href="action/fiche.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$objsoc->id.'">'.$obj->fax.'</a>&nbsp;</td>';
	print '<td><a href="action/fiche.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$objsoc->id.'">'.$obj->email.'</a>&nbsp;</td>';

	print '<td align="center">';
	print "<a href=\"../contact/fiche.php?action=edit&amp;id=$obj->idp\">";
	print img_edit();
	print '</a></td>';
	
    print '<td align="center"><a href="action/fiche.php?action=create&actionid=5&contactid='.$obj->idp.'&socid='.$objsoc->id.'">';
    print img_actions();
    print '</a></td>';
	
	print "</tr>\n";
	$i++;
	$tag = !$tag;
      }
      print "</table>";

      print "<p />";

      /*
       *
       *      Listes des actions a faire
       *
       */
      print '<table width="100%" cellspacing=0 class="noborder" cellpadding=2>';
      print '<tr class="liste_titre"><td><a href="action/index.php?socid='.$objsoc->id.'">'.$langs->trans("ActionsToDo").'</a></td><td align="right"> <a href="action/fiche.php?action=create&socid='.$objsoc->id.'&afaire=1">'.$langs->trans("AddActionToDo").'</a></td></tr>';
      print '<tr>';
      print '<td colspan="2" valign="top">';

      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $objsoc->id ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action AND a.percent < 100";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) ) {
	print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";

	$i = 0 ; $num = $db->num_rows();
	while ($i < $num) {
	  $var = !$var;

	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]>";

	  if ($oldyear == strftime("%Y",$obj->da) ) 
		{
	    //print '<td align="center">|</td>';
			print "<td align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
	  } 
		else 
		{
	    print "<td align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
	    $oldyear = strftime("%Y",$obj->da);
	  }

	  if ($oldmonth == strftime("%Y%b",$obj->da) ) 
		{
	    //print '<td align="center">|</td>';
			print "<td align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
	  } 
		else 
		{
	    print "<td align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
	    $oldmonth = strftime("%Y%b",$obj->da);
	  }
	  
	  print "<td>" .strftime("%d",$obj->da)."</td>\n"; 
	  print "<td>" .strftime("%H:%M",$obj->da)."</td>\n";

	  print '<td width="10%">&nbsp;</td>';

	  if ($obj->propalrowid)
	    {
	      print '<td width="40%"><a href="propal.php?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a></td>';
	    }
	  else
	    {
	      print '<td width="40%"><a href="action/fiche.php?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
	    }
	  /*
	   * Contact pour cette action
	   *
	   */
	  if ($obj->fk_contact) {
	    $contact = new Contact($db);
	    $contact->fetch($obj->fk_contact);
	    print '<td width="40%"><a href="people.php?socid='.$objsoc->id.'&contactid='.$contact->id.'">'.$contact->fullname.'</a></td>';
	  } else {
	    print '<td width="40%">&nbsp;</td>';
	  }
	  /*
	   */
	  print '<td width="20%"><a href="../user/fiche.php?id='.$obj->fk_user_author.'">'.$obj->code.'</a></td>';
	  print "</tr>\n";
	  $i++;
	}
	print "</table>";

	$db->free();
      } else {
	print $db->error();
      }
      print "</td></tr></table>";

      /*
       *
       *      Listes des actions effectuees
       *
       */
      print '<table class="noborder" width="100%" cellspacing=0 cellpadding=2>';
      print '<tr class="liste_titre"><td><a href="action/index.php?socid='.$objsoc->id.'">'.$langs->trans("ActionsDone").'</a></td></tr>';
      print '<tr>';
      print '<td valign="top">';

      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $objsoc->id ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action AND a.percent = 100";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) )
	{
	  print '<table width="100%" cellspacing="0" border="0" cellpadding="2">';
	  
	  $i = 0 ; 
	  $num = $db->num_rows();
	  $oldyear='';
	  $oldmonth='';
	  while ($i < $num)
	    {
	      $var = !$var;
	      
	      $obj = $db->fetch_object();
	      print "<tr $bc[$var]>";
	      
	      if ($oldyear == strftime("%Y",$obj->da) )
		{
		  print '<td align="center">|</td>';
		}
	      else
		{
		  print "<TD align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
		  $oldyear = strftime("%Y",$obj->da);
		}
	      
	      if ($oldmonth == strftime("%Y%b",$obj->da) )
		{
		  print '<td align="center">|</td>';
		}
	      else
		{
		  print "<TD align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
		  $oldmonth = strftime("%Y%b",$obj->da);
		}
	  
	      print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
	      print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";
	      
	      print '<td width="10%">&nbsp;</td>';
	      
	      if ($obj->propalrowid)
		{
		  print '<td width="40%"><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a></td>';
		}
	      else
		{
		  print '<td width="40%"><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
		}
	      /*
	       * Contact pour cette action
	       *
	       */
	      if ($obj->fk_contact)
		{
		  $contact = new Contact($db);
		  $contact->fetch($obj->fk_contact);
		  print '<td width="40%"><a href="people.php?socid='.$objsoc->id.'&contactid='.$contact->id.'">'.$contact->fullname.'</a></td>';
		}
	      else
		{
		  print '<td width="40%">&nbsp;</td>';
		}
	      /*
	       */
	      print '<td width="20%"><a href="../user/fiche.php?id='.$obj->fk_user_author.'">'.$obj->code.'</a></td>';
	      print "</tr>\n";
	      $i++;
	    }
	  print "</table>";
	  
	  $db->free();
	}
      else
	{
	  print $db->error();
	}
      print "</td></tr></table>";
      /*
       *
       * Notes sur la societe
       *
       */
      if ($objsoc->note)
	{
	  print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
	  print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
	  print "</table>";
	}
      /*
       *
       *
       *
       */

    }
  } else {
    print $db->error() . "<br>" . $sql;
  }

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
