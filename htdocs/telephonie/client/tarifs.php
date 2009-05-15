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

$message_erreur = '';


if ($_POST["action"] == 'addtarif' && $user->rights->telephonie->tarif->client_modifier)
{
  $error = 0;
  $saisieok = 1;

  if (strlen(trim($_POST["temporel"])) == 0 OR strlen(trim($_POST["fixe"])) == 0)
    {
      $saisieok = 0;
    }
  else
    {
      $temporel = ereg_replace(",",".",trim($_POST["temporel"]));
      $fixe = ereg_replace(",",".",trim($_POST["fixe"]));
    }
  
  if(! is_numeric($temporel))
    {
      $saisieok = 0;
    }

  if(! is_numeric($fixe))
    {
      $saisieok = 0;
    }

  if ($temporel <  0 OR $fixe <  0)
    {
      $saisieok = 0;
    }

  if ($saisieok)
    {
      
      $db->begin();

      $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_client";
      $sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user) VALUES ";
      $sql .= " (".$_POST["tarifid"].",".$_GET["id"].",'".$temporel."','".$fixe."',".$user->id.")";
      
      if (! $db->query($sql) )
	{
	  $error++;
	}

      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_client_log";
      $sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user, datec) VALUES ";
      $sql .= " (".$_POST["tarifid"].",".$_GET["id"].",'".$temporel."','".$fixe."',".$user->id.",now())";

      if (! $db->query($sql) )
	{
	  $error++;
	}

      if ( $error == 0 )
	{
	  $db->commit();
	  Header("Location: tarifs.php?id=".$_GET["id"]);
	}
      else
	{
	  $db->rollback();
	  print $db->error();
	}
    }
  else
    {
      $message_erreur = " Saisie invalide";
    }
}


/*
if ($_GET["special"] == 'done')
{

  $sql = "SELECT DISTINCT(fk_tarif), fixe, temporel  FROM llx_telephonie_prefix as p, llx_telephonie_tarif_vente as v where p.prefix=v.prefix and fixe= 0.14; ";

  if ( $db->query( $sql) )
    {
      $tt = array();

      $num = $db->num_rows();
      if ( $num > 0 )
	{
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $row = $db->fetch_row();	
	      
	      $tt[$row[0]] = $row[2];

	      $i++;
	    }
	}

      $db->free();

      foreach($tt as $key=>$value)
	{
	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_client";
	  $sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user) VALUES ";
	  $sql .= " (".$key.",".$_GET["id"].",'".$value."','0.07',".$user->id.")";
	}
      
    }
}
*/

if ($_GET["action"] == 'delete' && $user->rights->telephonie->tarif->client_modifier)
{

  if (strlen(trim($_GET["tid"])) > 0)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_tarif_client";
      $sql .= " WHERE rowid = ".$_GET["tid"];
      
      if ( $db->query($sql) )
	{
	  Header("Location: tarifs.php?id=".$_GET["id"]);
	}
      else
	{
	  print $db->error();
	}
    }
}

llxHeader("","T�l�phonie - Fiche Tarif client");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}

/*
 * 
 *
 */

if ($_GET["id"])
{
  $soc = new Societe($db);
  $result = $soc->fetch($_GET["id"], $user);

  if (!$soc->perm_read)
    {
      print "Lecture non authoris�e";
    }

  if ( $result == 1 && $soc->perm_read)
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  $h=0;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Contrats");
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

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Tarifs");
	  $hselected = $h;
	  $h++;
	  	  
	  dol_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';
	  
	  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
	  print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
	  	  
	  print "</table>\n<br />\n";

	  print '<form action="tarifs.php?id='.$soc->id.'" method="POST">';
	  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	  print '<input type="hidden" name="action" value="addtarif">';
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<tr class="liste_titre"><td width="15%" valign="center">Tarif (co�t en euros par minutes)';
	  print '</td><td align="center">Temporel</td><td align="center">Fixe</td>';
	  if ($user->rights->telephonie->tarif->client_modifier)
	    {
	      print '<td>&nbsp;</td>';
	    }
	  print '<td>&nbsp;</td>';	  
	  print "</tr>\n";

	  if($message_erreur)
	    {
	      print '<tr class="liste_titre"><td align="center" bgcolor="red" colspan="5">'.$message_erreur.'</td></tr>';
	    }

	  if ($user->rights->telephonie->tarif->client_modifier)
	    {
	      print "<tr><td>\n";
	      print '<select name="tarifid">';

	      $sql = "SELECT t.rowid , t.libelle";
	      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif as t";	  
	      $sql .= " ORDER BY t.libelle";
	      $resql = $db->query($sql) ;
	      if ($resql)
		{
		  $num = $db->num_rows($resql);
		  if ( $num > 0 )
		    {
		      $i = 0;
		      
		      while ($i < $num)
			{
			  $obj = $db->fetch_object($resql);	
			  print '<option value="'.$obj->rowid.'">'.$obj->libelle;
			  $i++;
			}
		    }
		}
	      print "</select></td>\n";
	      
	      print '<td align="center"><input name="temporel" type="text" value="'.$_POST["temporel"].'" "size="5"></td>';
	      print '<td align="center"><input name="fixe" value="'.$_POST["fixe"].'" type="text" size="5"></td>';
	      print '<td align="center"><input type="submit" value="'.$langs->trans("Save").'"></td><td>&nbsp;</td>';
	      print "</tr>\n";
	    }	  

	  /* Tarifs */
	  
	  $sql = "SELECT t.rowid , t.libelle, tc.temporel, tc.fixe, u.login, tc.rowid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif as t";
	  $sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_client as tc";
	  $sql .= "," . MAIN_DB_PREFIX."societe as s";
	  $sql .= "," . MAIN_DB_PREFIX."user as u";
	  
	  $sql .= " WHERE t.rowid = tc.fk_tarif AND tc.fk_client = s.rowid";
   	  $sql .= " AND u.rowid = tc.fk_user";
	  $sql .= " AND s.rowid = ".$soc->id;
	  $sql .= " ORDER BY t.libelle ASC";

	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;

		  $ligne = new LigneTel($db);

		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($i);	
		      $var=!$var;

		      print "<tr $bc[$var]><td>\n";

		      print $obj->libelle."</td>\n";

		      print '<td align="center">'.$obj->temporel."</td>\n";
		      print '<td align="center">'.$obj->fixe."</td>\n";

		      if ($user->rights->telephonie->tarif->client_modifier)
			{
			  print '<td align="center"><a href="'.DOL_URL_ROOT.'/telephonie/client/tarifs.php?action=delete&amp;tid='.$obj->rowid.'&amp;id='.$soc->id.'">';
		      print img_delete()."</a></td>\n";
			}
		      print '<td align="center">'.$obj->login."</td>\n";
		      print '</tr>';

		      $i++;
		    }
		}
	      $db->free();     
	      
	    }
	  else
	    {
	      print $sql;
	    }
	  
	  print "</table>\n</form>\n";
	}
    }
}
else
{
  print "Error";
}


print '</div>';

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
