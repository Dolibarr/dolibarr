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
require("../../contact.class.php");
require("../../cactioncomm.class.php");
require("../../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');

$langs->load('companies');


if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"];
  $sql .= " WHERE idp = ".$_GET["id"];
  $db->query($sql);
}

llxHeader();

/*
 *
 */
$socid = isset($_GET["id"])?$_GET["id"]:$_GET["socid"];		// Fonctionne si on passe id ou socid
/*
 * Sécurité si un client essaye d'accéder à une autre fiche que la sienne
 */
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}
/*********************************************************************************
 *
 * Mode fiche
 *
 *
 *********************************************************************************/  
if ($socid > 0)
{
  $societe = new Societe($db, $socid);
  $result = $societe->fetch($socid);  

  /* TODO Finir verification PagesJaunes
   * print '<form action="http://www.pagesjaunes.fr/pj.cgi" method="post" target="_blank">';
   * print '<input type="hidden" name="FRM_NOM" value="'.$societe->nom.'">';
   * print '<input type="hidden" name="FRM_LOCALITE" value="'.$societe->ville.'">';
   * print '<input type="submit">';
   * print '</form>';
   */

  if ($result)
    {
      $h=0;
      $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$societe->id;
      $head[$h][1] = "Fiche société";
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
      $head[$h][1] = 'Prospect';
      $h++;

      if (file_exists(DOL_DOCUMENT_ROOT.'/sl/'))
	{
	  $head[$h][0] = DOL_URL_ROOT.'/sl/fiche.php?id='.$societe->id;
	  $head[$h][1] = 'Fiche catalogue';
	  $h++;
	}
      
      if ($societe->fournisseur)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
	  $head[$h][1] = 'Fiche fournisseur';
	  $h++;
	}
      $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
      $head[$h][1] = 'Note';      
      $h++;
      if ($user->societe_id == 0)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
	  $head[$h][1] = 'Documents';
	  $h++;
	}

      $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
      $head[$h][1] = 'Notifications';

      dolibarr_fiche_head($head, 1, $societe->nom);

    /*
     *
     *
     */
    print '<table width="100%" border="0">';
    print '<tr><td valign="top">';
    print '<table class="border" cellspacing="0" cellpadding="3" border="1" width="100%">';

    print "<tr><td>Téléphone</td><td align=\"center\">".dolibarr_print_phone($societe->tel)."&nbsp;</td><td>fax</td><td align=\"center\">".dolibarr_print_phone($societe->fax)."&nbsp;</td></tr>";
    print '<tr><td valign="top">Adresse</td><td colspan="3">'.nl2br($societe->address)."<br>$societe->cp $societe->ville</td></tr>";

    print '<tr><td>Siret</td><td>'.$societe->siret.'</td>';
    print '<td>Capital</td><td>'.$societe->capital.'</td></tr>';

    print "<tr><td>".$langs->trans("Type")."</td><td> $societe->typent</td><td>Effectif</td><td>$societe->effectif</td></tr>";

    if ($societe->url)
      {
	print "<tr><td>Site</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";
      }

    if ($societe->rubrique)
      {
	print "<tr><td>Rubrique</td><td colspan=\"3\">$societe->rubrique</td></tr>";
      }

    print "<tr><td>Forme juridique</td><td colspan=\"3\">$societe->forme_juridique</td></tr>";
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$societe->statut_commercial.'</td>';
    print '<td> ';
    print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=-1&amp;action=cstc">';
    print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm-1.png" border="0" alt="Ne pas contacter" title="Ne pas contacter">';
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=0&amp;action=cstc">';
    print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm0.png" border="0" alt="Jamais contactée" title="Jamais contactée">';
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=1&amp;action=cstc">';
    print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm1.png" border="0" alt="A contacter" title="A contacter">';
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=2&amp;action=cstc">';
    print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm2.png" border="0" alt="Contact en cours" title="Contact en cours">';
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=3&amp;action=cstc">';
    print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm3.png" border="0" alt="Contactée" title="Contactée">';

    print '</a>';
    print '</td></tr>';
    print "</table></div>";

    /*
     *
     */
    print "</td>\n";
    print '<td valign="top" width="50%">';

    /*
     *
     * Propales
     *
     */
    $var = true;
    print '<table border="0" width="100%" cellspacing="0" cellpadding="1">';
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.remise, ".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
    $sql .= " AND s.idp = $societe->id ORDER BY p.datep DESC";

    if ( $db->query($sql) )
      {
	$num = $db->num_rows();
	if ($num >0 )
	  {
	    print "<tr $bc[$var]><td colspan=\"4\"><a href=\"../propal.php?socidp=$societe->id\">Liste des propositions commerciales ($num)</td></tr>";
	    $var=!$var;
	  }
	$i = 0;	$now = time(); $lim = 3600 * 24 * 15 ;
	while ($i < $num && $i < 2)
	  {
	    $objp = $db->fetch_object();
	    print "<tr $bc[$var]>";
	    print "<td><a href=\"../propal.php?propalid=$objp->propalid\">";
	    print img_file();
	    print "</a>&nbsp;<a href=\"../propal.php?propalid=$objp->propalid\">$objp->ref</a>\n";
	    if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	      {
		print " <b>&gt; 15 jours</b>";
	      }
	    print "</td><td align=\"right\">".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	    print "<td align=\"right\">".price($objp->price)."</TD>\n";
	    print "<td align=\"center\">$objp->statut</TD></tr>\n";
	    $var=!$var;
	    $i++;
	  }
	$db->free();
      }
    
    print "</table>";
    /*
     *
     *
     */
    print "</td></tr>";
    print "</table>\n</div>\n";
    /*
     * Barre d'action
     *
     */

    print '<div class="tabsAction">';

    if ($conf->propal->enabled && defined("MAIN_MODULE_PROPALE") && MAIN_MODULE_PROPALE && $user->rights->propale->creer)
      {
	print '<a class="tabAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socidp='.$societe->id.'&amp;action=create">Créer une proposition</a>';
      }

    if ($conf->projet->enabled && $user->rights->projet->creer)
      {
	print '<a class="tabAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socidp='.$socid.'&action=create">Créer un projet</a>';
      }
    print '</div>';

    print '<br>';
	
    /*
     *
     * Liste des contacts
     *
     */
    print '<table width="100%" cellspacing="1" border="0" cellpadding="2">';

    print '<tr class="liste_titre"><td>'.$langs->trans("Firstname").' '.$langs->trans("Name").'</td>';
    print '<td>Poste</td><td colspan="2">'.$langs->trans("Tel").'</td>';
    print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
    print "<td align=\"center\"><a href=\"../../contact/fiche.php?socid=$societe->id&action=create\">".$langs->trans("Add")."</a></td></tr>";
    
    $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note FROM ".MAIN_DB_PREFIX."socpeople as p WHERE p.fk_soc = $societe->id  ORDER by p.datec";
    $result = $db->query($sql);
    $i = 0 ; $num = $db->num_rows(); $tag = True;
    while ($i < $num)
      {
	$obj = $db->fetch_object($result);
	$var = !$var;
	print "<tr $bc[$var]>";
	
	print '<td>';
	
	print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actionid=5&amp;contactid='.$obj->idp.'&amp;socid='.$societe->id.'">'.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

	if ($obj->note)
	  {
	    print "<br>".nl2br($obj->note);
	  }
	print "</td>";
	print "<td>$obj->poste&nbsp;</td>";

	print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$societe->id.'">';
	print img_phone_out("Appel émis") ;
	print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$societe->id.'">';
	print img_phone_in("Appel reçu") .'</a></td>';

	print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$societe->id.'">';

	print ' '.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';
	print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->fax.'</a>&nbsp;</td>';
	print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->email.'</a>&nbsp;</td>';
	print "<td align=\"center\">";
	print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?action=edit&amp;id='.$obj->idp.'">';
	print img_edit();
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
      print '<table width="100%" cellspacing=0 border=0 cellpadding=2>';
      print '<tr class="liste_titre"><td>Actions à faire</td><td align="right"> [<a href="../action/fiche.php?action=create&socid='.$socid.'&afaire=1">Nouvelle action</a>]</td></tr>';
      print '<tr>';
      print '<td colspan="2" valign="top">';

      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $societe->id ";
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
				 print '<td align="center">' .strftime("%Y",$obj->da)."</TD>\n";
	    } 
	  else 
	    {
	    print '<td align="center">' .strftime("%Y",$obj->da)."</TD>\n"; 
	    $oldyear = strftime("%Y",$obj->da);
	    }

	  if ($oldmonth == strftime("%Y%b",$obj->da) )
		 {
	   // print '<td align="center">|</td>';
		 print "<TD align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
	  } 
		else 
		{
	    print "<TD align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
	    $oldmonth = strftime("%Y%b",$obj->da);
	  }
	  
	  print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
	  print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";

	  print '<td width="10%">&nbsp;</td>';


	  print '<td width="40%">';
	  if ($obj->propalrowid)
	    {
	      print '<a href="../propal.php?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a>';
	    }
	  else
	    {
	      print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_file().'</a> ';
	      print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.$obj->libelle.'</a>';
	    }
	  print '</td>';
	  /*
	   * Contact pour cette action
	   *
	   */
	  if ($obj->fk_contact)
	    {
	      $contact = new Contact($db);
	      $contact->fetch($obj->fk_contact);
	      //	      print '<td width="40%"><a href="people.php?socid='.$societe->id.'&contactid='.$contact->id.'">'.$contact->fullname.'</a></td>';
	      print '<td width="40%">'.$contact->fullname.'</td>';
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
      } else {
	print $db->error();
      }
      print "</td></tr></table>";

      /*
       *
       *      Listes des actions effectuees
       *
       */
      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid, a.note ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $societe->id ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action AND a.percent = 100";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";
      if ( $db->query($sql) )
	{

	  $i = 0 ; 
	  $num = $db->num_rows();

	  if ($num)
	    {

	      print '<table width="100%" class="noborder">';
	      print '<tr class="liste_titre"><td><a href="action/index.php?socid='.$socid.'">Actions effectuées</a></td></tr>';
	      print '<tr>';
	      print '<td valign="top">';
	      	      
	      print '<table width="100%" border="0">';
	      
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
		      print "<td align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
		      $oldmonth = strftime("%Y%b",$obj->da);
		    }
		  
		  print "<td>" .strftime("%d",$obj->da)."</td>\n"; 
		  print "<td>" .strftime("%H:%M",$obj->da)."</td>\n";
		  
		  print '<td width="10%">&nbsp;</td>';
		  
		  print '<td width="40%">';      
		  if ($obj->propalrowid)
		    {
		      print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_file().'</a> ';
		      print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a></td>';
		    }
		  else
		    {
		      print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_file().'</a> ';
		      print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
		    }
		  /*
		   * Contact pour cette action
		   *
		   */
		  if ($obj->fk_contact)
		    {
		      $contact = new Contact($db);
		      $contact->fetch($obj->fk_contact);
		      print '<td width="40%">'.$contact->fullname.'</td>';
		    }
		  else
		    {
		      print '<td width="40%">&nbsp;</td>';
		    }
		  /*
		   */
		  print '<td width="20%"><a href="../user/fiche.php?id='.$obj->fk_user_author.'">'.$obj->code.'</a></td>';
		  print "</tr>\n";

		  if ($i < 2 && strlen($obj->note))
		    {
		      print "<tr $bc[$var]>";
		      print '<td colspan="5">&nbsp;</td><td colspan="3">';
		      print stripslashes(nl2br($obj->note));
		      print '</td></tr>';
		    }

		  $i++;
		}
	      print "</table>";
	      print "</td></tr></table>";
	    }	  
	  $db->free();
	}
      else
	{
	  print $db->error();
	}

      /*
       *
       * Notes sur la societe
       *
       */
      if (strlen(trim($societe->note)))
	{
	  print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
	  print "<tr><td>".nl2br($societe->note)."</td></tr>";
	  print "</table>";
	}
      /*
       *
       *
       *
       */


    } 
  else
    {
      print $db->error() . "<br>" . $sql;
    }
}
else
{
  print "Erreur";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
