<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$user->getrights('facture');

llxHeader();




if ($action=='add_action') {
  /*
   * Vient de actioncomm.php
   *
   */
  $actioncomm = new ActionComm($db);
  $actioncomm->date = $date;
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;

  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add($user);

  $societe = new Societe($db);
  $societe->fetch($socid);
}


if ($action == 'attribute_prefix')
{
  $societe = new Societe($db, $socid);
  $societe->attribute_prefix($db, $socid);
}

if ($action == 'recontact')
{
  $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'". $GLOBALS["REMOTE_USER"]."')";
  $result = $db->query($sql);
}

if ($action == 'stcomm')
{
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm)
    {
      $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
      $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
      $result = @$db->query($sql);
      
      if ($result)
	{
	  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=$stcommid WHERE idp=$socid";
	  $result = $db->query($sql);
	}
      else
	{
	  $errmesg = "ERREUR DE DATE !";
	}
    }
  
  if ($actioncommid)
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
      $result = @$db->query($sql);
      
      if (!$result)
	{
	  $errmesg = "ERREUR DE DATE !";
	}
    }
}


/*
 * Recherche
 *
 *
 */
if ($mode == 'search')
{
  if ($mode-search == 'soc')
    {
      $sql = "SELECT s.idp FROM llx_societe as s ";
      $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }
  
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 1)
	{
	  $obj = $db->fetch_object(0);
	  $socid = $obj->idp;
	}
      $db->free();
    }

  if ($user->societe_id > 0)
    {
      $socid = $user->societe_id;
    }

}



/*
 *
 * Mode fiche
 *
 *
 */  
if ($socid > 0)
{
  $societe = new Societe($db, $socid);
  
  $sql = "SELECT s.idp as idp, s.nom, ".$db->pdate("s.datec")." as dc, s.tel, s.fax, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note, t.libelle as typent, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st, ".MAIN_DB_PREFIX."c_typent as t, ".MAIN_DB_PREFIX."c_effectif as e ";
  $sql .= " WHERE s.fk_stcomm=st.id AND s.fk_typent = t.id AND s.fk_effectif = e.id";

  if ($to == 'next')
    {
      $sql .= " AND s.idp > $socid ORDER BY idp ASC LIMIT 1";
    }
  elseif ($to == 'prev') {
    $sql .= " AND s.idp < $socid ORDER BY idp DESC LIMIT 1";
  } else {
    $sql .= " AND s.idp = $socid";
  }

  $result = $db->query($sql);

  if ($result) {
    $objsoc = $db->fetch_object(0);

	if (! $objsoc->idp) { 
		print("Cette societe n'existe pas ou plus");
		$db->close();

		llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
		exit;
    }  
  
    $dac = strftime("%Y-%m-%d %H:%M", time());
    if ($errmesg) {
      print "<b>$errmesg</b><br>";
    }

    /*
     *
     */
    print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";

    print "<tr><td><div class=\"titre\">Fiche client : $objsoc->nom</div></td>";

    if ($user->societe_id == 0)
      {
	print '<td align="center"><a href="../comm/fiche.php?socid='.$objsoc->idp.'">Commercial</a></td>';
	print "<td align=\"center\">[<a href=\"../comm/docsoc.php?socid=$objsoc->idp\">Documents</a>]</td>";
	print "<td align=\"center\">[<a href=\"index.php?socidp=$objsoc->idp&action=add\">Bookmark</a>]</td>";
	if ($user->rights->facture->creer)
	  print "<td>[<a href=\"facture.php?action=create&socidp=$objsoc->idp\">".translate("Facturer")."</a>]</td>";
	print "<td>[<a href=\"../socnote.php?socid=$objsoc->idp\">Notes</a>]</td>";
	print "<td align=\"center\">[<a href=\"deplacement/fiche.php?socid=$objsoc->idp&action=create\">Déplacement</a>]</td>";
      }
    print "</tr></table>";
    /*
     *
     *
     */

    print '<table width="100%" border="0"><tr>';
    print '<tr><td valign="top">';
    print '<table class="border" cellspacing="0" width="100%">';
    print '<tr><td>Tel</td><td>'.$objsoc->tel.'&nbsp;</td><td>Fax</td><td>'.$objsoc->fax.'&nbsp;</td></tr>';
    print '<tr><td>Ville</td><td colspan="3">'.nl2br($objsoc->address)."<br>$objsoc->cp $objsoc->ville</td></tr>";

    print '<tr><td>siren</td><td><a href="http://www.societe.com/cgi-bin/recherche?rncs='.$objsoc->siren.'">'.$objsoc->siren.'</a>&nbsp;</td>';
    print "<td>prefix</td><td>";
    if ($objsoc->prefix_comm)
      {
	print $objsoc->prefix_comm;
      }
    else
      {
	print "[<a href=\"$PHP_SELF?socid=$objsoc->idp&action=attribute_prefix\">Attribuer</a>]";
      }

    print "</td></tr>";

    print "<tr><td>Site</td><td colspan=\"3\"><a href=\"http://$objsoc->url\">$objsoc->url</a>&nbsp;</td></tr>";

    print "</table>";

    /*
     *
     */
    print "</td>\n";
    print '<td valign="top" width="50%">';

    /*
     *   Factures
     */
    if ($user->rights->facture->lire)
      {
	print '<table class="border" width="100%" cellspacing="0" cellpadding="1">';
	$var=!$var;
	$sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, ".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
	$sql .= " FROM llx_societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND s.idp = ".$objsoc->idp." ORDER BY f.datef DESC";
	
	if ( $db->query($sql) )
	  {
	    $num = $db->num_rows(); $i = 0; 
	    if ($num > 0)
	      {
		print "<tr $bc[$var]>";
		print "<td colspan=\"4\"><a href=\"facture.php?socidp=$objsoc->idp\">liste des factures ($num)</td></tr>";
	      }
	    
	    while ($i < $num && $i < 5)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		print "<TR $bc[$var]>";
		print "<TD><a href=\"../compta/facture.php?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
		if ($objp->df > 0 )
		  {
		    print "<TD align=\"right\">".strftime("%d %B %Y",$objp->df)."</TD>\n";
		  }
		else
		  {
		    print "<TD align=\"right\"><b>!!!</b></TD>\n";
		  }
		print "<TD align=\"right\">".number_format($objp->amount, 2, ',', ' ')."</TD>\n";
		$paye[1] = "payée";
		$paye[0] = "<b>impayée</b>";
		print "<TD align=\"center\">".$paye[$objp->paye]."</TD>\n";
		print "</TR>\n";
		$i++;
	      }
	    $db->free();
	  }
	else
	  {
	    print $db->error();
	  }
	print "</table>";
      }


    /*
     *
     * Liste des projets associés
     *
     */
    $sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
    $sql .= " FROM llx_projet as p WHERE p.fk_soc = $objsoc->idp";
    if ( $db->query($sql) ) {
      print "<table border=1 cellspacing=0 width=100% cellpadding=\"1\">";
      $i = 0 ; 
      $num = $db->num_rows();
      if ($num > 0) {
	$tag = !$tag; print "<tr $bc[$tag]>";
	print "<td colspan=\"2\"><a href=\"../projet/index.php?socidp=$objsoc->idp\">liste des projets ($num)</td></tr>";
      }
      while ($i < $num && $i < 5) {
	$obj = $db->fetch_object( $i);
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.$obj->title.'</a></td>';

	print "<td align=\"right\">".strftime("%d %b %Y", $obj->do) ."</td></tr>";
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
    print "</table>\n";
    /*
     *
     *
     *
     */
    if ($action == 'changevalue') {

      print "<HR noshade>";
      print "<form action=\"index.php?socid=$objsoc->idp\" method=\"post\">";
      print "<input type=\"hidden\" name=\"action\" value=\"cabrecrut\">";
      print "Cette société est un cabinet de recrutement : ";
      print "<select name=\"selectvalue\">";
      print "<option value=\"\">";
      print "<option value=\"t\">Oui";
      print "<option value=\"f\">Non";
      print "</select>";
      print "<input type=\"submit\" value=\"Mettre &agrave; jour\">";
      print "</form>\n";
    } else {
      /*
       *
       * Liste des contacts
       *
       */
      print '<table width="100%" cellspacing="0" border="0" cellpadding="2">';

      print '<tr class="liste_titre"><td><b>Pr&eacute;nom Nom</b></td>';
      print '<td><b>Poste</b></td><td><b>T&eacute;l</b></td>';
      print "<td><b>Fax</b></td><td><b>Email</b></td>";
      print "<td><a href=\"../comm/people.php?socid=$objsoc->idp&action=addcontact\">Ajouter</a></td></tr>";
    
      $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note FROM llx_socpeople as p WHERE p.fk_soc = $objsoc->idp  ORDER by p.datec";
      $result = $db->query($sql);
      $i = 0 ; $num = $db->num_rows(); 

      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);
	  $var = !$var;

	  print "<tr $bc[$var]>";

	  print '<td>';
	  //print '<a href="action/fiche.php?action=create&actionid=5&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">';
	  //print '<img border="0" src="/theme/'.$conf->theme.'/img/filenew.png"></a>&nbsp;';
	  print '<a href="../comm/action/fiche.php?action=create&actionid=5&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->firstname.' '. $obj->name.'</a>&nbsp;</td>';
	  
	  if ($obj->note)
	    {
	      print "<br><b>".nl2br($obj->note);
	    }
	  print "</td>";
	  print "<td>$obj->poste&nbsp;</td>";
	  print '<td><a href="../comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->phone.'</a>&nbsp;</td>';
	  print '<td><a href="../comm/action/fiche.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->fax.'</a>&nbsp;</td>';
	  print '<td><a href="../comm/action/fiche.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->email.'</a>&nbsp;</td>';
	  print "<td><a href=\"../comm/people.php?socid=$objsoc->idp&action=editcontact&contactid=$obj->idp\">Modifier</a></td>";
	  print "</tr>\n";
	  $i++;
	  $tag = !$tag;
	}
      print "</table>";
      
      print "\n<hr noshade size=1>\n";
      /*
       *
       */
      print '<table width="100%" cellspacing=0 border=0 cellpadding=2>';
      print '<tr>';
      print '<td valign="top">';
      /*
       *
       *      Listes des actions
       *
       */
      $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, u.code, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
      $sql .= " WHERE a.fk_soc = $objsoc->idp ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action ";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) ) {
	print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";

	$i = 0 ; $num = $db->num_rows();
	while ($i < $num) {
	  $var = !$var;

	  $obj = $db->fetch_object( $i);
	  print "<tr $bc[$var]>";

	  if ($oldyear == strftime("%Y",$obj->da) ) {
	    print '<td align="center">|</td>';
	  } else {
	    print "<TD align=\"center\">" .strftime("%Y",$obj->da)."</TD>\n"; 
	    $oldyear = strftime("%Y",$obj->da);
	  }

	  if ($oldmonth == strftime("%Y%b",$obj->da) ) {
	    print '<td align="center">|</td>';
	  } else {
	    print "<TD align=\"center\">" .strftime("%b",$obj->da)."</TD>\n"; 
	    $oldmonth = strftime("%Y%b",$obj->da);
	  }
	  
	  print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
	  print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";

	  print '<td width="10%">&nbsp;</td>';

	  if ($obj->propalrowid) {
	    print '<td width="40%"><a href="propal.php?propalid='.$obj->propalrowid.'">'.$obj->libelle.'</a></td>';
	  } else {
	    print '<td width="40%">'.$obj->libelle.'</td>';
	  }
	  /*
	   * Contact pour cette action
	   *
	   */
	  if ($obj->fk_contact) {
	    $contact = new Contact($db);
	    $contact->fetch($obj->fk_contact);
	    print '<td width="40%"><a href="people.php?socid='.$objsoc->idp.'&contactid='.$contact->id.'">'.$contact->fullname.'</a></td>';
	  } else {
	    print '<td width="40%">&nbsp;</td>';
	  }
	  /*
	   */
	  print '<td width="20%"><a href="../user.php">'.$obj->code.'</a></td>';
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
       * Notes sur la societe
       *
       */
      print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
      print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
      print "</table>";
      /*
       *
       *
       *
       */

    }
  } else {
    print $db->error() . "<br>" . $sql;
  }
} else {
  print "Erreur";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
