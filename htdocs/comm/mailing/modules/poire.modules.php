<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 *
 * L'utilisation d'adresses de courriers électroniques dans les opérations
 * de prospection commerciale est subordonnée au recueil du consentement 
 * préalable des personnes concernées.
 *
 * Le dispositif juridique applicable a été introduit par l'article 22 de 
 * la loi du 21 juin 2004  pour la confiance dans l'économie numérique.
 *
 * Les dispositions applicables sont définies par les articles L. 34-5 du 
 * code des postes et des télécommunications et L. 121-20-5 du code de la 
 * consommation. L'application du principe du consentement préalable en 
 * droit français résulte de la transposition de l'article 13 de la Directive 
 * européenne du 12 juillet 2002 « Vie privée et communications électroniques ». 
 *
 */

/*!	
  \file 
  \ingroup    mailing
  \brief 
  \version    $Revision$
*/

class mailing_poire
{
  function mailing_poire()
  {
    $this->desc = 'Tous les contacts assaociés aux clients';    
  }
  
   
  function prepare_cible($db, $mailing_id)
  {     
    $cibles = array();
    
    $sql = "SELECT distinct(c.email), c.idp, c.name, c.firstname, s.nom ";
    $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    $sql .= ", ".MAIN_DB_PREFIX."societe as s";
    $sql .= " WHERE s.idp = c.fk_soc";
    $sql .= " AND s.client = 1";
    $sql .= " AND c.email IS NOT NULL";
    $sql .= " ORDER BY c.email ASC";
 
    if ( $db->query($sql) ) 
      {
	$num = $db->num_rows();
	$i = 0;
	$j = 0;
	
	dolibarr_syslog("mailing-prepare: mailing $num cibles trouvées");

	$olde = '';

	while ($i < $num)
	  {
	    $row = $db->fetch_row();

	    if ($olde <> $row[0])
	      {
		$cibles[$j] = $row;
		$olde = $row[0];
		$j++;
	      }

	    $i++;	    
	  }
      }
    else
      {
	dolibarr_syslog($db->error());
      }
    
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles";
    $sql .= " WHERE fk_mailing = ".$mailing_id;
    
    if (!$db->query($sql))
      {
	dolibarr_syslog($db->error());
      }

    $num = sizeof($cibles);

    for ($i = 0 ; $i < $num ; $i++)
      {
	
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
	$sql .= " (fk_mailing, fk_contact, nom, prenom, email)";
	$sql .= " VALUES (".$mailing_id.",";
	$sql .=  $cibles[$i][1] .",";
	$sql .=  "'".$cibles[$i][2] ."',";
	$sql .=  "'".$cibles[$i][3] ."',";
	$sql .=  "'".$cibles[$i][0] ."')";
	
	if (!$db->query($sql))
	  {
	    dolibarr_syslog($db->error());
	  }
      }

    dolibarr_syslog("mailing-prepare: mailing $i cibles ajoutées");

    $sql = "UPDATE ".MAIN_DB_PREFIX."mailing";
    $sql .= " SET nbemail = ".$i." WHERE rowid = ".$mailing_id;
    
    if (!$db->query($sql))
      {
	dolibarr_syslog($db->error());
      }
    
    return 0;
    
  }
  
}

?>
