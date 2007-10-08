<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");

class WorkflowTelephonie {
  var $db;
  var $id;

  /**
   * Créateur
   *
   */
  function WorkflowTelephonie($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id;
    return 0;
  }
  /**
   *
   *
   */
  function Create($module, $user_id, $statut_id)
  {
    // $module contient une des valeurs du champs de type enum de la table

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_workflow";
    $sql .= " (module,fk_user,fk_statut)";
    $sql .= " VALUES ('".$module."','".$user_id."','".$statut_id."');";
    
    if ($this->db->query($sql) )
      {
	$res = 0;
      }
    else
      {
	dolibarr_syslog("WorkflowTelephonie::Create ".$this->db->error,LOG_ERR);
	$res = -1;
      }

    return $res;
  }

  function Notify($statut_id, $numero)
  {
    dolibarr_syslog("WorkflowTelephonie::Notify statut_id=$statut_id",LOG_DEBUG);


    $sql = "SELECT u.rowid";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."telephonie_workflow as w";
    $sql .= " WHERE u.rowid = w.fk_user AND w.fk_statut = '".$statut_id."' AND module='xdsl';";

    if ( $resql = $this->db->query( $sql) )
      {
	while ($row = $this->db->fetch_row($resql))
	  {
	    $this->SendMail($row[0],$statut_id, $numero);
	  }
	$this->db->free($resql);
      }       
    else
      {
	dolibarr_syslog("WorkflowTelephonie::SendMail ".$this->db->error,LOG_ERR);
      }
  }
  
  
  function SendMail($user_id, $statut_id, $numero)
  {
    dolibarr_syslog("WorkflowTelephonie::SendMail user_id=$user_id,statut_id=$statut_id",LOG_DEBUG);

    $comm = new User($this->db,$user_id);
    $comm->fetch();


    $ligne = new LigneAdsl($this->db);

    $subject = "Evénement sur une ligne xDSL";
    $sendto = $comm->prenom . " " .$comm->nom . "<".$comm->email.">";
    $from = "Unknown";
    
    $message = "Bonjour,\n\n";
    $message .= "Nous vous informons de l'événement suivant :\n\n";

    $message .= "Ligne numéro : ".$numero."\n";
    $message .= "Evénement    : ".$ligne->statuts[$statut_id]."\n";

    $message .= "\n\n--\n";
    $message .= "Ceci est un message automatique envoyé par Dolibarr\n";
    $message .= "Merci de ne pas y répondre.";

    $mailfile = new CMailFile($subject,
			      $sendto,
			      'unknown',
			      $message);
    $mailfile->sendfile();
  }

}
?>
