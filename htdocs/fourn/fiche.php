<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="nom";
}

if ($action == 'attribute_prefix')
{
  $societe = new Societe($db, $socid);
  $societe->attribute_prefix($db, $socid);
}

if ($action == 'recontact')
{
  $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
  $sql = "INSERT INTO llx_soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'". $GLOBALS["REMOTE_USER"]."')";
  $result = $db->query($sql);
}

if ($action == 'note') {
  $sql = "UPDATE llx_societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'stcomm') {
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm) {
    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
    $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = @$db->query($sql);

    if ($result) {
      $sql = "UPDATE llx_societe SET fk_stcomm=$stcommid WHERE idp=$socid";
      $result = $db->query($sql);
    } else {
      $errmesg = "ERREUR DE DATE !";
    }
  }

  if ($actioncommid)
    {
      $sql = "INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
      $result = @$db->query($sql);
      
      if (!$result)
	{
	  $errmesg = "ERREUR DE DATE !";
	}
    }
}

/**
 * Recherche
 *
 *
 */
if ($mode == 'search') {
  if ($mode-search == 'soc') {
    $sql = "SELECT s.idp FROM llx_societe as s ";
    $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
  }
      
  if ( $db->query($sql) ) {
    if ( $db->num_rows() == 1) {
      $obj = $db->fetch_object(0);
      $socid = $obj->idp;
    }
    $db->free();
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
  
  $sql = "SELECT s.idp, s.nom, ".$db->pdate("s.datec")." as dc, s.tel, s.fax, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note, t.libelle as typent, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description";
  $sql .= " FROM llx_societe as s, c_stcomm as st, c_typent as t, c_effectif as e ";
  $sql .= " WHERE s.fk_stcomm=st.id AND s.fk_typent = t.id AND s.fk_effectif = e.id";

  if ($to == 'next')
    {
      $sql .= " AND s.idp > $socid ORDER BY idp ASC LIMIT 1";
    }
  elseif ($to == 'prev')
    {
      $sql .= " AND s.idp < $socid ORDER BY idp DESC LIMIT 1";
    }
  else
    {
      $sql .= " AND s.idp = $socid";
    }
  
  $result = $db->query($sql);

  if ($result)
    {
      $objsoc = $db->fetch_object(0);

      $dac = strftime("%Y-%m-%d %H:%M", time());
      if ($errmesg) {
	print "<b>$errmesg</b><br>";
      }

    /*
     *
     */
    print '<table width="100%" class="noborder" cellspacing="1">';

    print "<tr><td><div class=\"titre\">Fiche fournisseur : $objsoc->nom</div></td>";

    print '<td><a href="facture/fiche.php?action=create&socid='.$objsoc->idp.'">Nouvelle Facture <img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Nouvelle facture"></a></td>';

    print "<td align=\"center\">[<a href=\"../soc.php?socid=$objsoc->idp&action=edit\">Editer</a>]</td>";
    print '<td align="center"><a href="'.DOL_URL_ROOT.'/product/liste.php?type=0&fourn_id='.$objsoc->idp.'">Produits</a></td>';

    print "</tr></table>";
    /*
     *
     *
     */
    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="1">';
    print '<tr><td valign="top" width="50%">';
    /*
     *
     */
    print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
    print '<tr><td>Tél</td><td>'.$objsoc->tel.'&nbsp;</td><td>fax</td><td>'.$objsoc->fax.'&nbsp;</td></tr>';
    print '<tr><td>Adresse</td><td colspan="3">'.nl2br($objsoc->address).'<br>'.$objsoc->cp.' '.$objsoc->ville.'</td></tr>';

    print '</table>';
    /*
     *
     */
    print '</td><td valign="top" width="50%">';
    /*
     *
     * Liste des projets associés
     *
     */
    $sql  = "SELECT p.rowid,p.libelle,p.facnumber,".$db->pdate("p.datef")." as df";
    $sql .= " FROM llx_facture_fourn as p WHERE p.fk_soc = $objsoc->idp";
    $sql .= " ORDER BY p.datef DESC LIMIT 0,4";
    if ( $db->query($sql) )
      {
	print '<table class="noborder" cellspacing="0" width="100%" cellpadding="1">';
	$i = 0 ; 
	$num = $db->num_rows();
	if ($num > 0)
	  {
	    print '<tr class="liste_titre">';
	    print "<td colspan=\"2\"><a href=\"facture/index.php?socid=$objsoc->idp\">liste des factures</td></tr>";
	  }
	while ($i < $num && $i < 5)
	  {
	    $obj = $db->fetch_object( $i);
	    $tag = !$tag;
	    print "<tr $bc[$tag]>";
	    print '<td>';
	    print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
	    print img_file();
	    print $obj->facnumber.'</a> '.$obj->libelle.'</td>';	    
	    print "<td align=\"right\">".strftime("%d %b %Y", $obj->df) ."</td></tr>";
	    $i++;
	  }
	$db->free();
	print "</table>";
      }
    else
      {
	print $db->error();
      }

    /*
     *
     *
     */
    print '</td></tr>';
    print '</table>' . "<br>\n";


      /*
       *
       * Liste des contacts
       *
       */
      print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';

      print "<tr><td><b>Pr&eacute;nom Nom</b></td>";
      print '<td><b>Poste</b></td><td><b>T&eacute;l</b></td>';
      print "<td><b>Fax</b></td><td><b>Email</b></td>";
      print "<td><a href=\"people.php?socid=$objsoc->idp&action=addcontact\">Ajouter</a></td></tr>";
    
      $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
      $sql .= " FROM llx_socpeople as p WHERE p.fk_soc = $objsoc->idp  ORDER by p.datec";
      $result = $db->query($sql);
      $i = 0 ; $num = $db->num_rows(); $tag = True;
      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);
	  if ($tag)
	    {
	      print "<tr bgcolor=\"e0e0e0\">";
	    }
	  else
	    {
	      print "<tr>";
	    }
	  print "<td>$obj->firstname $obj->name";
	  if ($obj->note)
	    {
	      print "<br><b>".nl2br($obj->note);
	    }
	  print "</td>";
	  print "<td>$obj->poste&nbsp;</td>";
	  print '<td><a href="actioncomm.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->phone.'</a>&nbsp;</td>';
	  print '<td><a href="actioncomm.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->fax.'</a>&nbsp;</td>';
	  print '<td><a href="actioncomm.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$objsoc->idp.'">'.$obj->email.'</a>&nbsp;</td>';
	  print "<td><a href=\"people.php?socid=$objsoc->idp&action=editcontact&contactid=$obj->idp\">Modifier</a></td>";
	  print "</tr>\n";
	  $i++;
	  $tag = !$tag;
	}
      print "</table>";
      
      
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
      $sql .= " FROM llx_actioncomm as a, c_actioncomm as c, llx_user as u ";
      $sql .= " WHERE a.fk_soc = $objsoc->idp ";
      $sql .= " AND u.rowid = a.fk_user_author";
      $sql .= " AND c.id=a.fk_action ";
      $sql .= " ORDER BY a.datea DESC, a.id DESC";

      if ( $db->query($sql) )
	{
	  print "<table width=\"100%\" cellspacing=0 border=0 cellpadding=2>\n";
	  print '<tr><td><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$objsoc->idp.'">Actions</a></td></tr>';

	  $i = 0 ; $num = $db->num_rows(); $tag = True;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      if ($tag)
		{
		  print "<tr bgcolor=\"e0e0e0\">";
		}
	      else
		{
		  print "<tr>";
		}
	      
	      if ($oldyear == strftime("%Y",$obj->da) )
		{
		  print '<td align="center">|</td>';
		}
	      else
		{
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
	      print '<td width="20%"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->fk_user_author.'">'.$obj->code.'</a></td>';
	      print "</tr>\n";
	      $i++;
	      $tag = !$tag;
	    }
	  print "</table>";
	  
	  $db->free();
	} else {
	  print $db->error() . "<br>" . $sql;
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
    
  }
  else {
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
