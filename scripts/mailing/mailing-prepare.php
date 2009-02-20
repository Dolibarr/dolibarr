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
 *
 * Export simple des contacts
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
 */

/**
        \file       scripts/mailing/mailing-prepare.php
        \ingroup    mailing
        \brief      Script pour préparer les destinataires d'un mailing
*/

require_once("../../htdocs/master.inc.php");

$error = 0;

$sql = "SELECT m.rowid, m.cible";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.statut in (0,1)";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $row = $db->fetch_row();

      dol_syslog("mailing-prepare: mailing $row[0]");
      dol_syslog("mailing-prepare: mailing module $row[1]");

      require_once(DOL_DOCUMENT_ROOT.'/includes/modules/mailings/'.$row[1].'.modules.php');

      $classname = "mailing_".$row[1];

      $obj = new $classname($db);
      $obj->add_to_target($row[0]);

      $i++;
      
    }
}

?>
