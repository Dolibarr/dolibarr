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
require("../contact.class.php");
require("../cactioncomm.class.php");
require("../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('commande');
$user->getrights('projet');


$langs->load("orders");
$langs->load("companies");


if ($_POST["action"] == 'setremise')
{
  $soc = New Societe($db);
  $soc->fetch($_GET["id"]);
  $soc->set_remise_client($_POST["remise"],$user);


  Header("Location: remise.php?id=".$_GET["id"]);

}


llxHeader();


/*
 *
 */
$_socid = $_GET["id"];
/*
 * Sécurité si un client essaye d'accéder à une autre fiche que la sienne
 */
if ($user->societe_id > 0) 
{
  $_socid = $user->societe_id;
}
/*********************************************************************************
 *
 * Mode fiche
 *
 *
 *********************************************************************************/  
if ($_socid > 0)
{
  // On recupere les donnees societes par l'objet
  $objsoc = new Societe($db);
  $objsoc->id=$_socid;
  $objsoc->fetch($_socid,$to);
  
  $dac = strftime("%Y-%m-%d %H:%M", time());
  if ($errmesg)
    {
      print "<b>$errmesg</b><br>";
    }
  
  /*
   * Affichage onglets
   */
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$objsoc->id;
  $head[$h][1] = $langs->trans("Company");
  $h++;
  
  if ($objsoc->client==1)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = 'Client';
      $h++;
    }
  if ($objsoc->client==2)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->socid;
      $head[$h][1] = 'Prospect';
      $h++;
    }
  if ($objsoc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objsoc->id;
      $head[$h][1] = 'Fournisseur';
      $h++;
    }
  
  if ($conf->compta->enabled) {
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = 'Comptabilité';
    $h++;
  }

    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$objsoc->id;
    $head[$h][1] = 'Note';
    $h++;

    if ($user->societe_id == 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$objsoc->id;
        $head[$h][1] = 'Documents';
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$objsoc->id;
    $head[$h][1] = 'Notifications';

      if (file_exists(DOL_DOCUMENT_ROOT.'/sl/'))
	{
	  $head[$h][0] = DOL_URL_ROOT.'/sl/fiche.php?id='.$objsoc->id;
	  $head[$h][1] = 'Fiche catalogue';
	  $h++;
	}

    if ($user->societe_id == 0)
      {
	$head[$h][0] = DOL_URL_ROOT."/comm/index.php?socidp=$objsoc->id&action=add_bookmark";
	$head[$h][1] = '<img border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/bookmark.png" alt="Bookmark" title="Bookmark">';
	$head[$h][2] = 'image';
      }

    dolibarr_fiche_head($head, $hselected, $objsoc->nom);

    /*
     *
     *
     */
    print '<form method="POST" action="remise.php?id='.$objsoc->id.'">';
    print '<input type="hidden" name="action" value="setremise">';
    print '<table width="100%" border="0">';
    print '<tr><td valign="top">';
    print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';

    print '<tr><td colspan="2">';
    print $langs->trans("CustomerDiscount").'</td><td colspan="2">'.$objsoc->remise_client."&nbsp;%</td></tr>";

    print '<tr><td colspan="2">';
    print $langs->trans("Modify").'</td><td colspan="2"><input type="text" size="5" name="remise" value="'.$objsoc->remise_client.'">&nbsp;%<input type="submit" value="'.$langs->trans("Save").'"></td></tr>';

    print "</table></form>";

    print "<br>";
    
    /*
     *
     */
    print "</td>\n";
    


    /*
     *
     *
     */
    print "</td></tr>";
    print "</table></div>\n";    
    print '<br>';        
    /*
     *
     * Notes sur la societe
     *
     */
    if ($objsoc->note)
      {
	print '<table border="1" width="100%" cellspacing="0" bgcolor="#e0e0e0">';
	print "<tr><td>".nl2br($objsoc->note)."</td></tr>";
	print "</table>";
      }
    /*
     *
     */    


    /*
     *
     * Liste des projets associés
     *
     */
    $sql  = "SELECT rc.rowid,rc.remise_client,".$db->pdate("rc.datec")." as dc, u.code";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise as rc";
    $sql .= " , ".MAIN_DB_PREFIX."user as u";
    $sql .= " WHERE rc.fk_soc =". $objsoc->id;
    $sql .= " AND u.rowid = rc.fk_user_author";
    $sql .= " ORDER BY rc.datec DESC";

    if ( $db->query($sql) )
      {
	print '<table class="border" cellspacing="0" width="100%" cellpadding="1">';
	$tag = !$tag;
	print "<tr $bc[$tag]>";
	$i = 0 ; 
	$num = $db->num_rows();

	while ($i < $num )
	  {
	    $obj = $db->fetch_object( $i);
	    $tag = !$tag;
	    print "<tr $bc[$tag]>";
	    print '<td>'.strftime("%d %B %Y",$obj->dc).'</td>';
	    print '<td>'.$obj->remise_client.' %</td>';
	    print '<td>'.$obj->code.'</td>';
	    
	    print "<td align=\"right\">".$obj->ref ."</td></tr>";
	    $i++;
	  }
	$db->free();
	print "</table>";
      }
    else
      {
	print $db->error();
      }




}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
