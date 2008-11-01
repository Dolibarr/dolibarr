<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$mesg = '';

llxHeader("","","Fiche client");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */

if ($_GET["id"])
{
  $soc = new TelephonieClient($db);
  $result = $soc->fetch($_GET["id"], $user);

  if (!$soc->perm_read)
    {
      print "Lecture non authoris�e";
    }

  if ( $result == 1 && $soc->perm_read)
    { 
      $soc->log_consult($user,'r');


      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  /* Commentaires */
	  $sql = "SELECT c.commentaire, ".$db->pdate("c.datec") ." as datec";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_commentaire as c";
	  $sql .= " WHERE fk_soc = ".$soc->id;
	  $sql .= " ORDER BY c.datec DESC";
	  $resql = $db->query($sql);
	  
	  if ($resql)
	    {
	      $num_comments = $db->num_rows($resql);
	      $db->free($resql);
	    }
	  /* Fin Commentaires */

	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Contrats");
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/lignes.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Lignes");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/stats.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Stats");
	  $h++;

	  $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_tarif_client";
	  $sql .= " WHERE fk_client = '".$soc->id."';";
	  $resql = $db->query($sql);

	  if ($resql)
	    {
	      $row = $db->fetch_row($resql);
	      $db->free($resql);
	    }

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Tarifs (".$row[0].")");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/commentaires.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Commentaires");
	  if ($num_comments > 0)
	    {
	      $head[$h][1] = $langs->trans("Commentaires ($num_comments)");
	    }
	  $h++;

	  if ($soc->perm_perms)
	    {
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/client/permissions.php?id=".$soc->id;
	      $head[$h][1] = $langs->trans("Permissions");
	      $h++;
	    }

	  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';
	  
	  print '<tr><td valign="top">'.$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
	  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
	  	  
	  print '<tr><td><a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'">'.img_edit() ."</a>&nbsp;";
	  print $langs->trans('RIB').'</td><td colspan="3">';
	  print $soc->display_rib();
	  print '</td></tr>';
	  
	  print '</table><br />';

	  /* Commentaires */
	  $sql = "SELECT c.commentaire";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_commentaire as c";
	  $sql .= " WHERE c.fk_soc = ".$soc->id;
	  $sql .= " ORDER BY c.datec DESC LIMIT 2";	  
	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      if ($num > 0)
		{
		  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		  print '<tr class="liste_titre"><td>2 Derniers commentaires</td></tr>';	      
		  while ($obj = $db->fetch_object($resql))
		    {
		      $var=!$var;
		      print "<tr $bc[$var]><td>";
		      print stripslashes($obj->commentaire)."</td>\n";
		      print "</tr>\n";
		    }
		  print "</table><br />";
		}
	      $db->free($resql);
	    }
	  else 
	    {
	      print $db->error() . ' ' . $sql;
	    }
	  
	  
	  /* Contrats */
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';	     
	  $sql = "SELECT count(l.rowid) as cc, c.rowid, c.ref, c.statut";
	  $sql .= ", ss.nom as agence, ss.code_client, ss.ville";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as ss";
	  $sql .= " WHERE c.fk_client_comm = ".$soc->id;
	  $sql .= " AND c.fk_soc = ss.rowid ";
	  $sql .= " AND l.fk_contrat = c.rowid";
	  $sql .= " GROUP BY c.rowid";
	  $sql .= " ORDER BY ss.rowid ASC";
	  
	  $resql = $db->query($sql);

	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      if ( $num > 0 )
		{
		  $i = 0;

		  $ligne = new LigneTel($db);

		  print '<tr class="liste_titre"><td width="15%">Contrat';
		  print '</td><td colspan="3">Agence/Filiale</td><td align="center">Nb Lignes</td>';
		  print "</tr>\n";

		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($resql);
		      $var=!$var;

		      print "<tr $bc[$var]><td>";

		      print '<img alt="" src="../contrat/statut'.$obj->statut.'.png">&nbsp;';

		      print '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->rowid.'">';
		      print img_file();
      
		      print '</a>&nbsp;';

		      print '<a href="'.DOL_URL_ROOT.'/telephonie/contrat/fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

		      print '<td>'.$obj->code_client."</td>\n";
		      print '<td>'.$obj->agence."</td>\n";
		      print '<td>'.$obj->ville."</td>\n";
		      print '<td align="center">'.$obj->cc."</td>\n";
		      print "</tr>\n";
		      $i++;
		    }
		}
	      $db->free($resql);
	      
	    }
	  else
	    {
	      print $sql;
	    }
	  
	  print "</table>";
	}
    }
}
else
{
  print "Error";
}
print '</div>';
print '<div id="version">$Revision$</div>';
/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
