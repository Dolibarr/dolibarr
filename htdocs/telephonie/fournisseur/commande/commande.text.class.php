<?PHP
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
 *
 * $Id$
 * $Source$
 *
 * Classe de commande de ligne au format Texte
 *
 *
 */
require_once DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php";

define ('COMMANDETEXT_NOEMAIL', -3);

class CommandeMethodeText
{

  function CommandeMethodeText ($DB, $USER=0, $fourn=0)
  {
    $this->nom = "Méthode texte";
    $this->db = $DB;
    $this->user = $USER;
    $this->fournisseur = $fourn;
  }

  function info()
  {
    return "Envoi un fichier texte contenant la liste des lignes à commander";
  }

  function Create()
  {
    $this->date = time();

    $this->datef = "commande-".strftime("%d%b%y-%HH%M", $this->date);

    $fname = DOL_DATA_ROOT ."/telephonie/ligne/commande/".$this->datef.".txt";

    if (strlen(trim($this->fournisseur->email_commande)) == 0)
      {
	return -3;
      }

    if (file_exists($fname))
      {
	return 2;
      }
    else
      {
	$res = $this->CreateFile($fname);

	if ($res == 0)
	  {
	    $res = $res + $this->LogSql();
	    $res = $res + $this->MailFile($fname);
	  }

	return $res;
      }
  }
  /**
   *
   *
   */
  function MailFile($filename)
  {
    $sql = "SELECT l.ligne";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.statut = 2";
    $sql .= " AND l.fk_fournisseur =".$this->fournisseur->id;
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
      }

    $subject = "Commande de Lignes";

    $sendto = $this->fournisseur->email_commande;

    $from = TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;

    $message = "Bonjour,\n\nVeuillez trouver ci-joint notre dernière commande.\n\n";
    $message .= "\n\nCordialement,\n\n";

    $message .= "-- \n";
    $message .= $this->user->fullname."\n";


    $mailfile = new DolibarrMail($subject,
				 $sendto,
				 $from,
				 $message);

    $mailfile->addr_bcc = TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;

    $mailfile->PrepareFile(array($filename),
			   array("plain/text"),
			   array($this->datef.".txt"));

    if ( $mailfile->sendfile() )
      {
	return 0;
      }

  }
  /**
   *
   *
   *
   */
  function LogSql()
  {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_commande";
    $sql .= " (datec, fk_user_creat, fk_fournisseur, filename)";
    $sql .= " VALUES (now(),".$this->user->id.",".$this->fournisseur->id.",'".$this->datef.".txt')";

    $result = $this->db->query($sql);

    if ($result)
      {
	return 0;
      }    
  }
  /**
   * Creation du fichier
   *
   */

  function CreateFile($fname)
  {
    $fp = fopen($fname, "w");

    if ($fp)
      {
	$ligneids = array();
	
	$sqlall = "SELECT s.nom, s.idp as socid, l.ligne, l.statut, l.rowid";
	$sqlall .= " , comm.name, comm.firstname";
	
	$sqlall .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sqlall .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	$sqlall .= " , ".MAIN_DB_PREFIX."user as comm";
	$sqlall .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";

	$sqlall .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid";

	$sqlall .= " AND l.fk_commercial = comm.rowid ";
	$sqlall .= " AND f.rowid =".$this->fournisseur->id;
	/*
	 *
	 */
	
	$sql = $sqlall;
	
	$sql .= " AND l.statut in (1,4)";
	$sql .= " ORDER BY l.statut ASC";
	
	$result = $this->db->query($sql);
	
	if ($result)
	  {
	    $i = 0;
	    $num = $this->db->num_rows();
	    
	    while ($i < $num)
	      {
		$obj = $this->db->fetch_object();	
		
		if (strlen($obj->ligne)== 10)
		  {		    
		    $soc = new Societe($this->db);
		    $soc->fetch($obj->socid);
		    
		    fwrite ($fp, $this->fournisseur->num_client);
		    fwrite ($fp, ";");
		    fwrite ($fp, $obj->nom);
		    fwrite ($fp, ";");
		    fwrite ($fp, $obj->ligne);
		    fwrite ($fp, "\n");
		    
		    array_push($ligneids, $obj->rowid);
		  }
		$i++;
	      }
	    
	    $this->db->free();
	  }
	else 
	  {
	    print $this->db->error() . ' ' . $sql;
	  }

	fclose($fp);
	
	/*
	 *
	 *
	 */
	
	foreach ($ligneids as $lid)
	  {
	    
	    $lint = new LigneTel($this->db);
	    $lint->fetch_by_id($lid);
	    if ($lint->statut == 1)
	      {
		$lint->set_statut($this->user, 2);
	      }
	    if ($lint->statut == 4)
	      {
		$lint->set_statut($this->user, 5);
	      }
	  }

	
	return 0;
	
      }
    else
      {
	return -1;
      }
  }
}
