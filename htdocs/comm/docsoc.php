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
require("./pre.inc.php3");

llxHeader();

$db = new Db();


function do_upload ($socid) {
  global $uploadfile, $uploadfile_size;
  global $local_file, $error_msg;

  if ( $uploadfile == "none" )
    {
      $error_msg = "You did not specify a file for uploading.";
      return;
    }
  if ( $uploadfile_size > 2000000 )
    {
      $error_msg = "Sorry, your file is too large.";
      return;
    }

  $soc_dir = SOCIETE_DOCUMENT_DIR . "/" . $socid;

  if (! is_dir($soc_dir))
    {
      mkdir($soc_dir);
    }


  $upload_dir = "/tmp";

  $local_file = SOCIETE_DOCUMENT_DIR . "/" . $socid . "/";

  move_uploaded_file ( $userfile, $local_file );

  print $HTTP_POST_FILES['uploadfile']['name'];

}

if ( $error_msg )
{ 
  echo "<B>$error_msg</B><BR><BR>";
}
if ( $sendit ) 
{
  do_upload ($socid);
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
  
  $sql = "SELECT s.idp, s.nom, ".$db->pdate("s.datec")." as dc, s.tel, s.fax, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note, t.libelle as typent, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description FROM societe as s, c_stcomm as st, c_typent as t, c_effectif as e ";
  $sql .= " WHERE s.fk_stcomm=st.id AND s.fk_typent = t.id AND s.fk_effectif = e.id";


  $result = $db->query($sql);

  if ($result)
    {
      $objsoc = $db->fetch_object(0);

      $dac = strftime("%Y-%m-%d %H:%M", time());
      if ($errmesg)
	{
	  print "<b>$errmesg</b><br>";
	}

      /*
       *
       */
      print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";
      
      print "<tr><td><div class=\"titre\">Fiche client : $objsoc->nom</div></td>";
      print "<td align=\"center\"><a href=\"index.php3?socidp=$objsoc->idp&action=add_bookmark\">[Bookmark]</a></td>";
      print "<td align=\"center\"><a href=\"projet/fiche.php3?socidp=$objsoc->idp&action=create\">[Projet]</a></td>";
      print "<td align=\"center\"><a href=\"addpropal.php3?socidp=$objsoc->idp&action=create\">[Propal]</a></td>";
      print "<td><a href=\"socnote.php3?socid=$objsoc->idp\">Notes</a></td>";
      print "<td align=\"center\">[<a href=\"../soc.php3?socid=$objsoc->idp&action=edit\">Editer</a>]</td>";
      print "</tr></table>";
      /*
       *
       *
       */
      
      
      print "<table width=\"100%\" border=0><tr>\n";
      print "<td valign=\"top\">";
      print "<table cellspacing=\"0\" border=\"1\" width=\"100%\">";
      
      print "<tr><td>Type</td><td> $objsoc->typent</td><td>Effectif</td><td>$objsoc->effectif</td></tr>";
      print "<tr><td>Tel</td><td> $objsoc->tel&nbsp;</td><td>fax</td><td>$objsoc->fax&nbsp;</td></tr>";
      print "<tr><td>Ville</td><td colspan=\"3\">".nl2br($objsoc->address)."<br>$objsoc->cp $objsoc->ville</td></tr>";
      
      print "<tr><td>siren</td><td><a href=\"http://www.societe.com/cgi-bin/recherche?rncs=$objsoc->siren\">$objsoc->siren</a>&nbsp;</td>";
      print "<td>prefix</td><td>";
      if ($objsoc->prefix_comm) {
	print $objsoc->prefix_comm;
      } else {
	print "[<a href=\"$PHP_SELF?socid=$objsoc->idp&action=attribute_prefix\">Attribuer</a>]";
      }
      
      print "</td></tr>";
      
      print "<tr><td>Site</td><td colspan=\"3\"><a href=\"http://$objsoc->url\">$objsoc->url</a>&nbsp;</td></tr>";
      
      print "</table>";
      
      /*
       *
       */
      print "</td>\n";
      
      print "</table>";
      
      echo '<FORM NAME="userfile" ACTION="docsoc.php?socid='.$socid.'" ENCTYPE="multipart/form-data" METHOD="POST">';
      
      print '<INPUT TYPE="HIDDEN" NAME="MAX_FILE_SIZE" VALUE="2000000">';
      print '<input type="file"   name="userfile" size="40" maxlength="80">';
      print '<BR><BR>';
      print '<INPUT TYPE="SUBMIT" VALUE="Upload File!" NAME="sendit">';
      print '<INPUT TYPE="SUBMIT" VALUE="Cancel" NAME="cancelit"><BR>';
      print '</FORM>';
      

      



    }
  else
    {
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
