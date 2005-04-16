<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
	    \file       htdocs/comm/prospect/fiche.php
        \ingroup    prospect
		\brief      Page de la fiche prospect
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../contact.class.php");
require("../../actioncomm.class.php");

$langs->load('companies');
$langs->load('projects');
$langs->load('propal');

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


$socid = isset($_GET["id"])?$_GET["id"]:$_GET["socid"];		// Fonctionne si on passe id ou socid

if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"];
  $sql .= " WHERE idp = ".$_GET["id"];
  $db->query($sql);
}

/*
 * Sécurité si un client essaye d'accéder à une autre fiche que la sienne
 */
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}


llxHeader();

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
      $head[$h][1] = $langs->trans("Company");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
      $head[$h][1] = $langs->trans("Prospect");
      $hselected=$h;
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
	  $head[$h][1] = $langs->trans("Supplier");
	  $h++;
	}

      if ($conf->compta->enabled) {
          $langs->load("compta");
          $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
          $head[$h][1] = $langs->trans("Accountancy");
          $h++;
      }

      $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
      $head[$h][1] = $langs->trans("Note");      
      $h++;
      if ($user->societe_id == 0)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
	  $head[$h][1] = $langs->trans("Documents");
	  $h++;
	}

      $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
      $head[$h][1] = $langs->trans("Notifications");


      dolibarr_fiche_head($head, $hselected, $societe->nom);

   /*
     *
     */
    print "<table width=\"100%\">\n";
    print '<tr><td valign="top" width="50%">'; 

    print '<table class="border" width="100%">';
    print '<tr><td width="25%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";
    
    print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td colspan="3">'.$societe->cp." ".$societe->ville.'</td></tr>';
    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$societe->pays.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.$societe->tel.'&nbsp;</td><td>Fax</td><td>'.$societe->fax.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";

    if ($societe->rubrique)
      {
	print "<tr><td>Rubrique</td><td colspan=\"3\">$societe->rubrique</td></tr>";
      }

    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$societe->forme_juridique.'</td></tr>';
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$societe->statut_commercial.'</td>';
    print '<td> ';
    print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=-1&amp;action=cstc">';
    print img_action(0,-1);
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=0&amp;action=cstc">';
    print img_action(0,0);
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=1&amp;action=cstc">';
    print img_action(0,1);
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=2&amp;action=cstc">';
    print img_action(0,2);
    print '</a> <a href="fiche.php?id='.$societe->id.'&amp;stcomm=3&amp;action=cstc">';
    print img_action(0,3);
    print '</a>';
    print '</td></tr>';
    print "</table><br></div>";

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
    print '<table class="border" width="100%">';
    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref, p.remise, ".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";
    $sql .= " AND s.idp = $societe->id ORDER BY p.datep DESC";

    if ( $db->query($sql) )
      {
	$num = $db->num_rows();
	if ($num >0 )
	  {
	    print "<tr $bc[$var]><td colspan=\"4\">";
        print '<table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProposals").'</td>';
        print '<td align="right"><a href="../propal.php?socidp='.$societe->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td>';
        print '</tr></table>';
	    print '</td></tr>';
	    $var=!$var;
	  }
	$i = 0;	$now = time(); $lim = 3600 * 24 * 15 ;
	while ($i < $num && $i < 2)
	  {
	    $objp = $db->fetch_object();
	    print "<tr $bc[$var]>";
	    print "<td><a href=\"../propal.php?propalid=$objp->propalid\">";
	    print img_object($langs->trans("ShowPropal"),"propal");
	    print " $objp->ref</a>\n";
	    if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	      {
		print " <b>&gt; 15 jours</b>";
	      }
	    print "</td><td align=\"right\">".strftime("%d %B %Y",$objp->dp)."</td>\n";
	    print "<td align=\"right\">".price($objp->price)."</td>\n";
	    print "<td align=\"center\">$objp->statut</td></tr>\n";
	    $var=!$var;
	    $i++;
	  }
	$db->free();
      }
    
    print "</table>";

    print "</td></tr>";
    print "</table>\n</div>\n";


    /*
     * Barre d'action
     *
     */

    print '<div class="tabsAction">';

    print '<a class="tabAction" href="../../contact/fiche.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

    print '<a class="tabAction" href="../action/fiche.php?action=create&socid='.$socid.'&afaire=1">'.$langs->trans("AddAction").'</a>';


    if ($conf->propal->enabled && defined("MAIN_MODULE_PROPALE") && MAIN_MODULE_PROPALE && $user->rights->propale->creer)
      {
	print '<a class="tabAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socidp='.$societe->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
      }
    
    if ($conf->projet->enabled && $user->rights->projet->creer)
      {
	print '<a class="tabAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socidp='.$socid.'&action=create">'.$langs->trans("AddProject").'</a>';
      }
    print '</div>';
    
    print '<br>';
	
    /*
     *
     * Liste des contacts
     *
     */
    if (defined("MAIN_MODULE_CLICKTODIAL") && MAIN_MODULE_CLICKTODIAL==1)
      {
	$user->fetch_clicktodial(); // lecture des infos de clicktodial
      }

    print '<table width="100%" class="noborder">';

    print '<tr class="liste_titre"><td>'.$langs->trans("Firstname").' '.$langs->trans("Name").'</td>';
    print '<td>Poste</td><td colspan="2">'.$langs->trans("Tel").'</td>';
    print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
    print '<td>&nbsp;</td>';
    
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

	print '<td>';

	/*
	 * Lien click to dial
	 */

	if (strlen($obj->phone) && $user->clicktodial_enabled == 1)
	  {
	    print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&amp;socid='.$societe->id.'&amp;call='.$obj->phone.'">';
	    print img_phone_out("Appel émis") ;
	  }
	print '</td>';

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
       *      Listes des actions a faire
       */
      print '<table width="100%" class="noborder">';
      print '<tr class="liste_titre"><td colspan="8">'.$langs->trans("ActionsToDo").'</td></tr>';
      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code as acode, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid, a.note ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $societe->id ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action AND a.percent < 100";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) )
	{
	  
	  $i = 0 ; $num = $db->num_rows();
	  while ($i < $num)
	    {
	      $var = !$var;
	      
	      $obj = $db->fetch_object();
	      
	      print "<tr $bc[$var]>";
	      
	      if ($oldyear == strftime("%Y",$obj->da) )
		{
		  //print '<td align="center">|</td>';
		  print '<td align="center">' .strftime("%Y",$obj->da)."</td>\n";
		} 
	      else 
		{
		  print '<td align="center">' .strftime("%Y",$obj->da)."</td>\n"; 
		  $oldyear = strftime("%Y",$obj->da);
		}
	      
	      if ($oldmonth == strftime("%Y%b",$obj->da) )
		{
		  print '<td align="center">' .strftime("%b",$obj->da)."</td>\n"; 
		} 
	      else 
		{
		  print "<td align=\"center\">" .strftime("%b",$obj->da)."</td>\n"; 
		  $oldmonth = strftime("%Y%b",$obj->da);
		}
	      
	      print "<td>" .strftime("%d",$obj->da)."</td>\n"; 
	      print "<td>" .strftime("%H:%M",$obj->da)."</td>\n";
	      
	      print '<td width="10%">&nbsp;</td>';
	      
	      
	      print '<td width="40%">';
	      if ($obj->propalrowid)
		{
		  print '<a href="../propal.php?propalid='.$obj->propalrowid.'">';
          $transcode=$langs->trans("Action".$obj->acode);
          $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
          print $libelle;
		  print '</a>';
		}
	      else
		{
		  print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowAction"),"task").' ';
          $transcode=$langs->trans("Action".$obj->acode);
          $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
          print $libelle;
		  print '</a>';
		}
	      print '</td>';
	      
	      /*
	       * Contact pour cette action
	       *
	       */
	      print '<td width="40%">';
	      if ($obj->fk_contact)
		{
		  $contact = new Contact($db);
		  $contact->fetch($obj->fk_contact);
		  print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$contact->id.'">'.$contact->fullname.'</a></td>';
		}
	      else
		{
		  print '&nbsp;</td>';
		}
	      /*
	       *
	       */
	      print '<td>';
	      print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->fk_user_author.'">';
	      print $obj->code.'</a></td>';
	      print "</tr>\n";

	      if ($obj->note)
		{
		  print "<tr $bc[$var]>";
		  print '<td colspan="5">&nbsp;</td><td colspan="3">'.stripslashes($obj->note).'</td></tr>';
		}

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
       *      Listes des actions effectuees
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
	      print '<tr class="liste_titre"><td><a href="action/index.php?socid='.$socid.'">'.$langs->trans("ActionsDone").'</a></td></tr>';
	      print '<tr>';
	      print '<td valign="top">';
	      	      
	      print '<table width="100%" class="noborder">';
	      
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
		      print "<td align=\"center\">" .strftime("%Y",$obj->da)."</td>\n"; 
		      $oldyear = strftime("%Y",$obj->da);
		    }
		  
		  if ($oldmonth == strftime("%Y%b",$obj->da) )
		    {
		      print '<td align="center">|</td>';
		    }
		  else
		    {
		      print "<td align=\"center\">" .strftime("%b",$obj->da)."</td>\n"; 
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
	  dolibarr_print_error($db);
	}

      /*
       *
       * Notes sur la societe
       *
       */
      if (strlen(trim($societe->note)))
	{
	  print '<table class="border" width="100%" bgcolor="#e0e0e0">';
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
	  dolibarr_print_error($db);
    }
}
else
{
  print "Erreur";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
