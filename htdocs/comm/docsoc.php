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

$upload_dir = SOCIETE_OUTPUTDIR . "/" . $socid ;

if (! is_dir($upload_dir))
{
  umask(0);
  mkdir($upload_dir, 0755);
}

function do_upload ($socid)
{
  global $upload_dir;
  global $local_file, $error_msg;

  if (move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
    {
      print "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; 
           avec succ&egrave;s.\n";
      //print_r($_FILES);
    }
  else
    {
      echo "Le fichier n'a pas été téléchargé";
      // print_r($_FILES);
    }

}

if ( $error_msg )
{ 
  echo "<B>$error_msg</B><BR><BR>";
}
if ($action=='delete')
{
  $file = $upload_dir . "/" . urldecode($urlfile);
  dol_delete_file($file);
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
  
  $sql = "SELECT s.idp, s.nom, ".$db->pdate("s.datec")." as dc, s.tel, s.fax, st.libelle as stcomm, s.fk_stcomm, s.url,s.address,s.cp,s.ville, s.note, t.libelle as typent, e.libelle as effectif, s.siren, s.prefix_comm, s.services,s.parent, s.description FROM llx_societe as s, c_stcomm as st, c_typent as t, c_effectif as e ";
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
      
      print "<tr><td><div class=\"titre\">Documents associés à l'entreprise : $objsoc->nom</div></td>";
      print "<td align=\"center\"><a href=\"fiche.php3?socid=$objsoc->idp\">Commercial</a></td>";
      print "<td align=\"center\"><a href=\"../compta/fiche.php3?socid=$objsoc->idp\">Compta</a></td>";
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
      if ($objsoc->prefix_comm)
	{
	  print $objsoc->prefix_comm;
	}
      else
	{
	  print "[<a href=\"$PHP_SELF?socid=$objsoc->idp&action=attribute_prefix\">Attribuer</a>]";
	}
      
      print "</td></tr>";
      
      print "</table>";
      
      /*
       *
       */
      print "</td>\n";
      
      print "</table>";

      echo '<FORM NAME="userfile" ACTION="docsoc.php?socid='.$socid.'" ENCTYPE="multipart/form-data" METHOD="POST">';      
      print '<input type="hidden" name="max_file_size" value="2000000">';
      print '<input type="file"   name="userfile" size="40" maxlength="80">';
      print '<BR>';
      print '<input type="submit" value="Upload File!" name="sendit">';
      print '<input type="submit" value="Cancel" name="cancelit"><BR>';
      print '</FORM>';

      clearstatcache();

      $handle=opendir($upload_dir);

      if ($handle)
	{

	  print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';

	  while (($file = readdir($handle))!==false)
	    {
	      if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
		{
		  print '<tr><td>';
		  echo '<a href="'.DOL_URL_ROOT.'/document/societe/'.$socid.'/'.$file.'">'.$file.'</a>';
		  print "</td>\n";
		  
		  print '<td align="right">'.filesize($upload_dir."/".$file). ' bytes</td>';
		  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($upload_dir."/".$file)).'</td>';
		  
		  print '<td>';
		  echo '<a href="'.$PHP_SELF.'?socid='.$socid.'&action=delete&urlfile='.urlencode($file).'">Delete</a>';
		  print "</td></tr>\n";
		}
	    }

	  print "</table>";

	  closedir($handle);
	}
      else
	{
	  print "<p>Impossible d'ouvrir : <b>".$upload_dir."</b>";
	}
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
