<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@uers.sourceforge.net>
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

/**     \file       htdocs/comm/mailing/cibles.php
        \brief      Page des cibles de mailing
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("mails");

$mesg = '';


llxHeader("","",$langs->trans("MailCard"));

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
}


/*
 * Fiche mailing en mode création
 *
 */

$mil = new Mailing($db);


$html = new Form($db);
if ($mil->fetch($_GET["id"]) == 0)
{

  $h=0;
  $head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
  $head[$h][1] = $langs->trans("MailCard");
  $h++;
      
  $head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
  $head[$h][1] = $langs->trans("MailTargets");
  $hselected = $h;
  $h++;
      
  dolibarr_fiche_head($head, $hselected, substr($mil->titre,0,20));
      
      
  print '<table class="border" width="100%">';
      
  print '<tr><td width="20%">'.$langs->trans("MailTitle").'</td><td>'.$mil->titre.'</td></tr>';
  print '</table>';
      
  /*
   *
   *
   *
   */
  $sql = "SELECT mc.nom, mc.prenom, mc.email";
  $sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
  $sql .= " WHERE mc.fk_mailing=".$mil->id;

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();

      print '<br /><table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("MailTargets").'</td></tr>';
      $var = true;
      $i = 0;
      
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td>'.stripslashes($obj->prenom).'</a></td>';
	  print '<td>'.stripslashes($obj->nom).'</a></td>';
	  print '<td>'.$obj->email.'</td>';
	  
	  $i++;
	}
      
      print "</table><br>";
      
      $db->free();
    } 
  else
    {
      dolibarr_print_error($db);
    }
}
  




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
