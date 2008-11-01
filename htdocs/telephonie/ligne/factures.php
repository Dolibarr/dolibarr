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

require("./pre.inc.php");

$mesg = '';

require_once DOL_DOCUMENT_ROOT."/facture.class.php";

llxHeader("","","Fiche Ligne");

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
	  
      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/fiche.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans("Ligne");
      $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/factures.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Factures');
      $hselected = $h;
      $h++;
	  
      /*
      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/facturesdet.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Factures d�taill�es');
      $h++;
      */

      $head[$h][0] = DOL_URL_ROOT."/telephonie/ligne/infoc.php?id=".$ligne->id;
      $head[$h][1] = $langs->trans('Infos');
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


      $sql = "SELECT f.fk_facture";
      $sql .= " ,s.nom, s.rowid as socid";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
      $sql .= " , ".MAIN_DB_PREFIX."societe as s";
      $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
      $sql .= " WHERE s.rowid = l.fk_soc_facture AND l.rowid = f.fk_ligne";  
      $sql .= " AND f.ligne ='".$ligne->numero."'";  
      $sql .= " ORDER BY f.fk_facture DESC";
      
      $i = 1;
      $facs = array();
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  
	  while ($i < ($num+1))
	    {
	      $row = $db->fetch_row();
	      
	      $facs[$i] = $row[0];
	      
	      $i++;
	    }
	}
      else
	{
	  print $sql;
	}
      
      
      if ($_GET["facnum"])
	{
	  $facnum = $_GET["facnum"];
	}
      else
	{
	  $facnum = 1;
	}
            
      dolibarr_fiche_head($head, $hselected, 'Ligne : '.$ligne->numero);

      print_fiche_titre('Factures Ligne', $mesg);
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';

      print '<tr><td width="25%">Num�ro</td><td>'.dolibarr_print_phone($ligne->numero,0,0,true).'</td>';
      print '<td>';

      if ($facnum > 1)
	{
	  print '<a href="factures.php?id='.$ligne->id.'&amp;facnum='.($facnum-1).'">';
	  print '<- Facture pr�c�dente</a>';
	}

      print "&nbsp;</td><td>";

      if ($facnum < sizeof($facs))
	{
	  print '<a href="factures.php?id='.$ligne->id.'&amp;facnum='.($facnum+1).'">';
	  print 'Facture suivante -></a>';
	}

      print '&nbsp;</td></tr>';
	      	     
      $client = new Societe($db, $ligne->client_id);
      $client->fetch($ligne->client_id);

      print '<tr><td width="25%">Client</td><td>';

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$ligne->client_id.'">';
      print $client->nom.'</a></td>';

      $client_facture = new Societe($db);
      $client_facture->fetch($ligne->client_facture_id);

      print '<td width="25%">Client Factur�</td><td>'.$client_facture->nom.'</td></tr>';

      $fac = new Facture($db);
      $fac->fetch($facs[$facnum]);
      
      print '<tr><td>Facture</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/telephonie/client/facture.php?facid='.$fac->id.'">'.$fac->ref.'</a></td></tr>';

      print "</table>\n";

      /*
       *
       *
       *
       */
      
      $file = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf";
      $file_img = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf.png";
      
      if (file_exists($file_img))
	{
	  print '<br><img src="../showfacture.php?uid='.$user->id.'&amp;facref='.$fac->id.'"></img>';
	}
      else
	{
	  if (file_exists("/usr/bin/convert"))
	    {
	      exec("/usr/bin/convert $file $file_img");
	      
	      if (file_exists($file_img))
		{
		  print '<br><img src="../showfacture.php?uid='.$user->id.'&amp;facref='.$fac->id.'"></img>';
		}      
	      else
		{
		  print "Erreur ";
		}
	    }
	}
    }
}
else
{
  print "Error";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
