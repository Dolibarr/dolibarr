<?PHP
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

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}

llxHeader();

function valeur($sql) 
{
  global $db;
  if ( $db->query($sql) ) 
    {
      if ( $db->num_rows() ) 
	{
	  $valeur = $db->result(0,0);
	}
      $db->free();
    }
  return $valeur;
}
/*
 *
 */


if ($action == 'add_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$socidp." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      print $db->error();
    }
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($action == 'del_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}


print_titre("Espace commercial");

print '<table border="0" width="100%" cellspacing="0" cellpadding="3">';

print '<tr><td valign="top" width="30%">';



/*
 * Recherche Propal
 */
if ($conf->propal->enabled) {
	print '<form method="post" action="propal.php">';
	print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">Rechercher une proposition</td></tr>';
	print "<tr $bc[1]><td>";
	print 'Num. : <input type="text" name="sf_ref">&nbsp;<input type="submit" value="Rechercher" class="flat"></td></tr>';
	print "</table></form><br>\n";

	$sql = "SELECT p.rowid, p.ref";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql .= " WHERE p.fk_statut = 0";
	
	if ( $db->query($sql) )
	{
	  $num = $db->num_rows();
	  $i = 0;
	  if ($num > 0 )
	    {
	      print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
	      print "<tr class=\"liste_titre\">";
	      print "<td colspan=\"2\">Propositions commerciales brouillons</td></tr>";
	      
	      while ($i < $num)
		{
		  $obj = $db->fetch_object( $i);
		  $var=!$var;
		  print "<tr $bc[$var]><td><a href=\"propal.php?propalid=".$obj->rowid."\">".$obj->ref."</a></td></tr>";
		  $i++;
		}
	      print "</table><br>";
	    }
	}
}

/*
 * Commandes à valider
 */
if ($conf->commande->enabled)
{
    $sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 0";
    if ($socidp)
    {
      $sql .= " AND c.fk_soc = $socidp";
    }
    
    if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
        {
	  print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="2">'.translate("Commandes à valider").'</td></tr>';
          $i = 0;
          $var = False;
          while ($i < $num)
	    {
	      $obj = $db->fetch_object($i);
	      print "<tr $bc[$var]><td width=\"20%\"><a href=\"../commande/fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	      $i++;
	      $var=!$var;
	    }
	  print "</table><br>";     
        }
    }
}

/*
 * Bookmark
 *
 */
$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
$sql .= " ORDER BY lower(s.nom) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
  print "<tr class=\"liste_titre\">";
  print "<td colspan=\"2\">Bookmark</td>";
  print "</tr>\n";

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var = !$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
      print '<td align="right"><a href="index.php?action=del_bookmark&bid='.$obj->bid.'">';
      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0"></a></td>';
      print '</tr>';
      $i++;
    }
  print '</table>';
}

/*
 * Actions commerciales a faire
 *
 */
print '</td><td valign="top" width="70%">';

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent < 100 AND s.idp = a.fk_soc AND a.fk_user_action = $user->id";
$sql .= " ORDER BY a.datea DESC";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();

  print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
  print '<tr class="liste_titre"><td colspan="4">Actions à faire</td></tr>';
  $var = true;
  $i = 0;

  while ($i < $num ) 
	{
	  $obj = $db->fetch_object($i);
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"action/fiche.php?id=$obj->id\">".img_file()."</a>&nbsp;";
	  print "<a href=\"action/fiche.php?id=$obj->id\">$obj->libelle $obj->label</a></td>";
	  print "<td>".strftime("%d %b %Y",$obj->da)."</td>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->sname.'</a></td>';
	  $i++;
	}
  // TODO Ajouter rappel pour "il y a des contrats à mettre en service"
  // TODO Ajouter rappel pour "il y a des contrats qui arrivent à expiration"
  print "</table><br>";

  $db->free();
} 
else
{
  print $db->error();
}

/*
 * Derniers contrat
 *
 */
if ($conf->contrat->enabled) {
    $labelservice[0]="Hors service";
    $labelservice[1]="En service";
    $labelservice[2]="Cloturé";

    $sql = "SELECT s.nom, s.idp, c.enservice, c.rowid, p.ref, c.mise_en_service as datemes, c.fin_validite as datefin, c.date_cloture as dateclo";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."product as p WHERE c.fk_soc = s.idp and c.fk_product = p.rowid";
    if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }
    $sql .= " ORDER BY c.tms DESC";
    $sql .= $db->plimit(5, 0);
    
    print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
    print '<tr class="liste_titre"><td colspan="3">Les 5 derniers contrats</td></tr>';

    if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
    
      $var=false;
      while ($i < $num)
    	{
    	  $obj = $db->fetch_object( $i);
    	  print "<tr $bc[$var]><td><a href=\"../contrat/fiche.php?id=".$obj->rowid."\">".img_file()."</a>&nbsp;";
    	  print "<a href=\"../contrat/fiche.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
    	  print "<td><a href=\"fiche.php?socid=$obj->idp\">$obj->nom</a></td>\n";      
    	  print "<td align=\"right\">".$labelservice[$obj->enservice]."</td></tr>\n";
    	  $var=!$var;
    	  $i++;
    	}
    }
    else {
        print $db->error();   
    }
    print "</table><br>";
}

/*
 * Dernières propales ouvertes
 *
 */
if ($conf->propal->enabled) {

    $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut = 1";
    if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }
    $sql .= " ORDER BY p.rowid DESC";
    $sql .= $db->plimit(5, 0);
    
    if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      if ($num > 0)
	{
	  print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
	  print '<tr class="liste_titre"><td colspan="4">Les 5 dernières propositions commerciales ouvertes</td></tr>';
	  $var=false;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      print "<tr $bc[$var]><td width=\"15%\"><a href=\"propal.php?propalid=".$obj->propalid."\">".img_file()."</a>&nbsp;";
	      print "<a href=\"propal.php?propalid=".$obj->rowid."\">".$obj->ref."</a></td>";
	      print "<td width=\"30%\"><a href=\"fiche.php?socid=$obj->idp\">$obj->nom</a></td>\n";      
	      print "<td align=\"right\">";
	      print strftime("%e %b %Y",$obj->dp)."</td>\n";	  
	      print "<td align=\"right\">".price($obj->price)."</td></tr>\n";
	      $var=!$var;
	      $i++;
	    }
	  print "</table><br>";
	}
    }
    
}

/*
 * Dernières propales fermées
 *
 */

if ($conf->propal->enabled) {

	$sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut > 1";
	if ($socidp)
	{ 
	  $sql .= " AND s.idp = $socidp"; 
	}
	$sql .= " ORDER BY p.rowid DESC";
	$sql .= $db->plimit(5, 0);
	
	if ( $db->query($sql) )
	    {
	    $num = $db->num_rows();
	      
	    $i = 0;
	    print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';      
	    print '<tr class="liste_titre"><td colspan="6">Les 5 dernières propositions commerciales traitées</td></tr>';
	    $var=False;	      
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);		  
		print "<tr $bc[$var]>";
		print '<td width="15%">';
		print '<a href="propal.php?propalid='.$objp->propalid.'">'.img_file().'</a>';
		print '&nbsp;<a href="propal.php?propalid='.$objp->propalid.'">'.$objp->ref.'</a></td>';
		print "<td width=\"30%\"><a href=\"fiche.php?socid=$objp->idp\">$objp->nom</a></TD>\n";      
		
		$now = time();
		$lim = 3600 * 24 * 15 ;
		
		if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
		  {
		    print "<td><b> &gt; 15 jours</b></td>";
		  }
		else
		  {
		    print "<td>&nbsp;</td>";
		  }
		
		print "<td align=\"right\">";
		print strftime("%e %b %Y",$objp->dp)."</td>\n";	  
		print "<td align=\"right\">".price($objp->price)."</TD>\n";
		print "<td align=\"center\">$objp->statut</TD>\n";
		print "</tr>\n";
		$i++;
		$var=!$var;
		
	      }
	    
	    print "</table>";
	    $db->free();
	    }
}


print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
