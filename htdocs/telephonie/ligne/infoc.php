<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**	        \file       htdocs/telephonie/ligne/infoc.php
	        \ingroup    telephonie
	        \brief      Lignes telephonie
	        \version    $Revision$
*/

require("./pre.inc.php");

$mesg = '';

if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel") && $user->rights->telephonie->ligne->creer)
{
  $ligne = new LigneTel($db);
  $ligne->id = $_GET["id"];

  $ligne->code_analytique = $_POST["code_ana"];

  if ( $ligne->update_infoc($user) )

    {
      $action = '';
      $mesg = 'Fiche mise � jour';
      Header("Location: infoc.php?id=".$ligne->id);
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise � jour !' . "<br>" . $entrepot->mesg_error;
    }
}


llxHeader("","","Fiche Ligne");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */


if ($_GET["id"] or $_GET["numero"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $ligne = new LigneTel($db);
      if ($_GET["id"])
	{
	  $result = $ligne->fetch_by_id($_GET["id"]);
	}
      if ($_GET["numero"])
	{
	  $result = $ligne->fetch($_GET["numero"]);
	}
    }
  
  if ($result == 1)
    {
      $client_comm = new Societe($db);
      $client_comm->fetch($ligne->client_comm_id, $user);
    }
  
  if (!$client_comm->perm_read)
    {
      print "Lecture non authoris�e";
    }
  
  
  if ($result == 1 && $client_comm->perm_read)
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans("Ligne");
	  $h++;
	  
	  if ($user->rights->telephonie->facture->lire)
	    {
	      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/factures.php?id=".$ligne->id;
	      $head[$h][1] = $langs->trans('Factures');
	      $h++;
	    }

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Infos');
	  $hselected = $h;
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/history.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Historique');
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/conso.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Conso');
	  $h++;
	  
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/stat.php?id=".$ligne->id;
	  $head[$h][1] = $langs->trans('Stats');
	  $h++;
	  
	  dol_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);
	  
	  print_fiche_titre('Informations compl�mentaires', $mesg);
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<tr><td width="20%">Num�ro</td><td>'.dol_print_phone($ligne->numero,0,0,true).'</td>';
	  print '<td>Factur�e : '.$ligne->facturable.'</td></tr>';
	  
	  $client = new Societe($db, $ligne->client_id);
	  $client->fetch($ligne->client_id);
	  
	  print '<tr><td width="20%">Client</td><td colspan="2">'.$client->nom.'</td></tr>';
	  
	  $client_facture = new Societe($db);
	  $client_facture->fetch($ligne->client_facture_id);
	  
	  
	  print '<tr><td width="20%">Remise LMN</td><td colspan="2">'.$ligne->remise.'&nbsp;%</td></tr>';

	  $cuser = new User($db, $ligne->user_creat);
	  if ($ligne->user_creat)
	    {
	      $cuser->fetch();
	    }

	  print '<tr><td width="20%">Ligne creee par</td><td colspan="2">'.$cuser->fullname.'</td></tr>';

	  
	  print '<tr><td width="20%">Code analytique</td><td colspan="2">'.$ligne->code_analytique.'&nbsp;</td></tr>';

	  print '<tr><td width="20%">Modele de facture utilise</td><td colspan="2">'.$ligne->pdfdetail.'</td></tr>';

	  
	  print "</table>";
	}
      
      
      if ($_GET["action"] == 'edit' || $action == 're-edit')
	{
	  print_fiche_titre('Edition des informations complementaires de la ligne', $mesg);
	  
	  print "<form action=\"infoc.php?id=$ligne->id\" method=\"post\">\n";
	  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<tr><td width="20%">Numero</td><td>'.$ligne->numero.'</td></tr>';
	  
	  $client = new Societe($db, $ligne->client_id);
	  $client->fetch($ligne->client_id);
	  
	  print '<tr><td width="20%">Client</td><td colspan="2">'.$client->nom;
	  print '</td></tr>';
	  
	  print '<tr><td width="20%">Remise LMN</td><td>'.$ligne->remise.' %</td></tr>';
	  
	  print '<tr><td width="20%">Code Analytique</td><td><input name="code_ana" size="13" maxlength="12" value="'.$ligne->code_analytique.'">&nbsp;</td></tr>';
	  
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="Mettre a jour">';
	  print '<a href="infoc.php?id='.$ligne->id.'">Annuler</a></td></tr>';
	  print '</table>';
	  print '</form>';	  
	}
      
      /*
       *
       *
       *
       */
      
      print '</div>';
      
      /* ************************************************************************** */
      /*                                                                            */ 
      /* Barre d'action                                                             */ 
      /*                                                                            */ 
      /* ************************************************************************** */
      
      print "<br><div class=\"tabsAction\">\n";
      
      if ($_GET["action"] == '')
	{
	  print "<a class=\"butAction\" href=\"infoc.php?action=edit&amp;id=$ligne->id\">".$langs->trans("Modify")."</a>";
	}
      
      print "</div>";
    }
  
}
else
{
  print "Error";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
