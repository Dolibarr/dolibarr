<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php");
require("./fichinter.class.php");
require("../project.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($socidp)
{
  $sql = "SELECT s.nom, s.idp, s.prefix_comm FROM ".MAIN_DB_PREFIX."societe as s WHERE s.idp = $socidp;";

  $result = $db->query($sql);
  if ($result)
    {
      if ( $db->num_rows() )
	{
	  $objsoc = $db->fetch_object(0);
	}
      $db->free();
    }
}

llxHeader();
/*
 * Traitements des actions
 *
 *
 */

if ($action == 'valid')
{
  $fichinter = new Fichinter($db);
  $fichinter->id = $id;
  $fichinter->valid($user->id, $conf->fichinter->outputdir);

}

if ($action == 'add')
{
  $fichinter = new Fichinter($db);

  $fichinter->date = $db->idate(mktime(12, 1 , 1, $pmonth, $pday, $pyear));
  $fichinter->socidp = $socidp;
  $fichinter->duree = $duree;
  $fichinter->projet_id = $projetidp;
  $fichinter->author = $user->id;
  $fichinter->note = $note;
  $fichinter->ref = $ref;

  $id = $fichinter->create();
}

if ($action == 'update')
{
  $fichinter = new Fichinter($db);

  $fichinter->date = $db->idate(mktime(12, 1 , 1, $remonth, $reday, $reyear));
  $fichinter->socidp = $socidp;
  $fichinter->duree = $duree;
  $fichinter->projet_id = $projetidp;
  $fichinter->author = $user->id;
  $fichinter->note = $note;
  $fichinter->ref = $ref;

  $fichinter->update($id);
}
/*
 *
 *   Generation du pdf
 *
 */
if ($action == 'generate' && $id)
{
  fichinter_pdf_create($db, $id);
  $mesg = "PDF généré";
}
/*
 *
 * Mode creation
 * Creation d'une nouvelle fiche d'intervention
 *
 */
if ($action == 'create')
{
  print_titre("Création d'une fiche d'intervention");

  if ( $objsoc->prefix_comm )
    {
      $numpr = "FI-" . $objsoc->prefix_comm . "-" . strftime("%y%m%d", time());
      
      $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."propal WHERE ref like '$numpr%'";
      
      if ( $db->query($sql) )
	{
	  $num = $db->result(0, 0);
	  $db->free();
	  if ($num > 0)
	    {
	      $numpr .= "." . ($num + 1);
	    }
	}

      $fix = new Fichinter($db);
      $numpr = $fix->get_new_num($objsoc->prefix_comm);
    
      print "<form action=\"$PHP_SELF?socidp=$socidp\" method=\"post\">";
      
      $strmonth[1] = "Janvier";
      $strmonth[2] = "F&eacute;vrier";
      $strmonth[3] = "Mars";
      $strmonth[4] = "Avril";
      $strmonth[5] = "Mai";
      $strmonth[6] = "Juin";
      $strmonth[7] = "Juillet";
      $strmonth[8] = "Ao&ucirc;t";
      $strmonth[9] = "Septembre";
      $strmonth[10] = "Octobre";
      $strmonth[11] = "Novembre";
      $strmonth[12] = "D&eacute;cembre";
      
      $smonth = 1;
      $syear = date("Y", time());
      print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      
      print "<tr><td>Société</td><td><b>".$objsoc->nom."</td></tr>";
      
      print "<tr><td>Date</td><td>";
      $cday = date("d", time());
      print "<select name=\"pday\">";    
      for ($day = 1 ; $day < $sday + 32 ; $day++)
	{
	  if ($day == $cday)
	    {
	      print "<option value=\"$day\" SELECTED>$day";
	    }
	  else
	    {
	      print "<option value=\"$day\">$day";
	    }
	}
      print "</select>";
      $cmonth = date("n", time());
      print "<select name=\"pmonth\">";    
      for ($month = $smonth ; $month < $smonth + 12 ; $month++) {
	if ($month == $cmonth)
	  {
	    print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
	  }
	else
	  {
	    print "<option value=\"$month\">" . $strmonth[$month];
	  }
      }
      print "</select>";
    
      print "<select name=\"pyear\">";
    
      for ($year = $syear ; $year < $syear + 5 ; $year++)
	{
	  print "<option value=\"$year\">$year";
	}
      print "</select></td></tr>";
      
      print "<input type=\"hidden\" name=\"action\" value=\"add\">";
      
      print "<tr><td>Numéro</td><td><input name=\"ref\" value=\"$numpr\"></td></tr>\n";
      print "<tr><td>Durée (en jours)</td><td><input name=\"duree\"></td></tr>\n";
      
      /*
       * Projet associé
       *
       */
      print '<tr><td valign="top">Projet</td><td><select name="projetidp">';
      print '<option value="0"></option>';
      
      $sql = "SELECT p.rowid, p.title FROM ".MAIN_DB_PREFIX."projet as p WHERE p.fk_soc = $socidp";
      
      if ( $db->query($sql) )
	{
	  $i = 0 ;
	  $numprojet = $db->num_rows();
	  while ($i < $numprojet)
	    {
	      $projet = $db->fetch_object($i);
	      print "<option value=\"$projet->rowid\">$projet->title</option>";
	      $i++;
	    }
	  $db->free();
	} else {
	  print $db->error();
	}
      print '</select>';
      if ($numprojet==0) {
	print 'Cette société n\'a pas de projet.&nbsp;';
	print '<a href='.DOL_URL_ROOT.'/projet/fiche.php?socidp='.$socidp.'&action=create>Créer un projet</a>';
      }
      print '</td></tr>';
            
      print '<tr><td valign="top">Description</td>';
      print "<td><textarea name=\"note\" wrap=\"soft\" cols=\"60\" rows=\"15\"></textarea>";
      print '</td></tr>';
      
      print '<tr><td colspan="2" align="center">';
      print "<input type=\"submit\" value=\"Enregistrer\">";
      print '</td></tr>';
      print '</table>';      
      print '</form>';
      
    }
  else
    {
      print "Vous devez d'abord associer un prefixe commercial a cette societe" ;
    }
}
/*
 *
 * Mode update
 * Mise a jour de la fiche d'intervention
 *
 */
if ($action == 'edit')
{

  $fichinter = new Fichinter($db);
  $fichinter->fetch($id);

  /*
   *   Initialisation de la liste des projets
   */
  $prj = new Project($db);
  $listeprj = $prj->liste_array($fichinter->societe_id);
  

  print_titre("Mettre à jour Fiche d'intervention");

  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">";
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print "<tr><td>Date</td><td>";
  /*
   * set $reday, $remonth, $reyear
   */
  print_date_select($fichinter->date);

  print "</select></td></tr>";
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  
  print '<tr><td>Numéro</td><td>'.$fichinter->ref.'</td></tr>';
  print '<tr><td>Durée (en jours)</td><td><input name="duree" value="'.$fichinter->duree.'"></td></tr>';
  
  /*
   *
   * Projet associé
   *
   */

  print '<tr><td valign="top">Projet</td><td>';

  $sel = new Form($db);
  $sel->select_array("projetidp",$listeprj,$fichinter->projet_id);

  if (sizeof($listeprj) == 0)
    {
      print 'Cette société n\'a pas de projet.&nbsp;';
      print '<a href='.DOL_URL_ROOT.'/comm/projet/fiche.php?socidp='.$socidp.'&action=create>Créer un projet</a>';
    }
  print '</td></tr>';


  print '<tr><td valign="top">Description</td>';
  print '<td><textarea name="note" wrap="soft" cols="60" rows="15">';
  print $fichinter->note;
  print '</textarea>';
  print '</td></tr>';

  print '<tr><td colspan="2" align="center">';
  print "<input type=\"submit\" value=\"Enregistrer\">";
  print '</td></tr>';
  print "</table>";  
    
  print "</form>";
    
  print "<hr noshade>";

}

/*
 * Mode Fiche 
 * Affichage de la fiche d'intervention
 *
 *
 */

if ($id)
{
  print_fiche_titre("Fiche d'intervention",$mesg);

  $fichinter = new Fichinter($db);
  if (  $fichinter->fetch($id) )
    {
      $fichinter->fetch_client();

      print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      print '<tr><td>Société</td><td><a href="../comm/fiche.php?socid='.$fichinter->client->id.'">'.$fichinter->client->nom.'</a></td></tr>';
      print '<tr><td width="20%">Date</td><td>'.strftime("%A %d %B %Y",$fichinter->date).'</td></tr>';
      print '<tr><td>Numéro</td><td>'.$fichinter->ref.'</td></tr>';
      print '<tr><td>Durée</td><td>'.$fichinter->duree.'</td></tr>';
      print '<tr><td valign="top">Projet</td><td>&nbsp;</td></tr>';
      print '<tr><td valign="top">Description</td>';
      print '<td colspan="3">';
      print nl2br($fichinter->note);
      print '</td></tr>';
      
      print '</td></tr>';
      print "</table>";  

      /*
       *
       */
      print '<br><table class="border" cellpadding="3" cellspacing="0" width="100%"><tr>';

      if ($user->societe_id == 0)
	{
      
	  if ($fichinter->statut == 0)
	    {
	      print '<td align="center" width="20%"><a href="fiche.php?id='.$id.'&action=edit">Mettre à jour</a></td>';
	    }
	  else
	    {
	      print '<td align="center" width="20%">-</td>';
	    }
	  
	  print '<td align="center" width="20%">-</td>';
	  
	  $file = FICHEINTER_OUTPUTDIR . "/$fichinter->ref/$fichinter->ref.pdf";
	  
	  if ($fichinter->statut == 0 or !file_exists($file))
	    {
	      print '<td align="center" width="20%"><a href="fiche.php?id='.$id.'&action=generate">Génération du pdf</a></td>';
	    }
	  else
	    {
	      print '<td align="center" width="20%">-</td>';
	    }
	  
	  print '<td align="center" width="20%">-</td>';
	  
	  if ($fichinter->statut == 0)
	    {
	      print '<td align="center" width="20%"><a href="fiche.php?id='.$id.'&action=valid">Valider</a></td>';
	    }
	  else
	    {
	      print '<td align="center" width="20%">-</td>';
	    }
	  
	}
      else
	{
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	}
  
      print '</tr></table>';
  
      print '<table width="50%" cellspacing="2"><tr><td width="50%" valign="top">';
      print_titre("Documents générés");
      print '<table width="100%" cellspacing="0" class="border" cellpadding="3">';
      
      $file = FICHEINTER_OUTPUTDIR . "/$fichinter->ref/$fichinter->ref.pdf";
      if (file_exists($file))
	{
	  print "<tr $bc[0]><td>Ficheinter PDF</a></td>";
	  print '<td><a href="'.FICHEINTER_OUTPUT_URL.'/'.$fichinter->ref.'/'.$fichinter->ref.'.pdf">'.$fichinter->ref.'.pdf</a></td>';
	  print '<td align="right">'.filesize($file). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td></tr>';
	}  
      
      print "</table></td></tr></table>\n";
      
    }
  else
    {
      print "Fiche inexistante";
    }
}


$db->close();
llxFooter();
?>
