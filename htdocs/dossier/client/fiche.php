<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once DOL_DOCUMENT_ROOT.'/client.class.php';


if (!$user->rights->telephonie->lire)
  accessforbidden();

$facs = array();
$client = new client($db, $_GET["id"]);
$client->fetch($_GET["id"]);
$client->read_factures();
$facs = $client->factures;

llxHeader("",'Dossier', $client);


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */

if ($_GET["facid"])
{
  require_once DOL_DOCUMENT_ROOT.'/facture.class.php';

  $fac = new Facture($db);
  $fac->fetch($_GET["facid"]);

  $file = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf";
  $file_img = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf.png";
  
  if (file_exists($file_img))
    {
      print '<br><img src="./image.php?file='.$file_img.'"></img>';
    }
  else
    {
      exec("/usr/bin/convert $file $file_img");

      if (file_exists($file_img))
	{
	  print '<br><img src="./image.php?file='.$file_img.'"></img>';
	}
    }

}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
