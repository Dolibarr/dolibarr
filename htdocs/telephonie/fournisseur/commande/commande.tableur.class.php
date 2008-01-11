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

require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php";
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/fournisseur/commande/methode.commande.class.php";

define ('COMMANDETABLEUR_NOEMAIL', -3);

class CommandeMethodeTableur extends CommandeMethode
{

  function CommandeMethodeTableur ($DB, $USER=0, $fourn=0)
  {
    dolibarr_syslog("CommandeMethodeTableur::CommandeMethodeTableur");


    $this->nom = "Méthode Tableur joint";
    $this->db = $DB;
    $this->user = $USER;
    $this->fourn = $fourn;
  }

  function info()
  {
    return "Envoi un fichier tableur contenant la liste des lignes à commander";
  }

  function Create()
  {
    dolibarr_syslog("CommandeMethodeTableur::Create Fournisseur id : ".$this->fourn->id);
    dolibarr_syslog("CommandeMethodeTableur::Create Fournisseur email : ".$this->fourn->email_commande);

    $this->date = time();

    $this->datef = "commande-".strftime("%d%b%y-%HH%M", $this->date);

    $this->filename = $this->datef.".xls";

    $fname = DOL_DATA_ROOT ."/telephonie/ligne/commande/".$this->filename;

    if (strlen(trim($this->fourn->email_commande)) == 0)
      {
	$res = -3;
      }

    if (file_exists($fname))
      {
	$res = 2 ;
      }
    else
      {
	$res = $this->CreateFile($fname);
	$res = $res + $this->LogSql();
	$res = $res + $this->MailFile($fname);
      }

    dolibarr_syslog("CommandeMethodeTableur::CommandeMethodeTableur Return $res");

    return $res;
  }
  /**
   *
   *
   *
   *
   */
  function MailFile($filename)
  {
    $sql = "SELECT l.ligne";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sql .= " WHERE l.statut = 2";
    $sql .= " AND l.fk_fournisseur =".$this->fourn->id;
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
      }

    $subject = "Commande de Lignes N° ".$this->commande_id;

    $sendto = $this->fourn->email_commande;

    $from = TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;

    $message = "Bonjour,\n\nVeuillez trouver ci-joint notre dernière commande.\n\n";
    $message .= "Nous avons à ce jour $num ligne(s) commandée(s) pour lesquelles nous attendons la confirmation de présélection.\n";
    $message .= "\nCordialement,\n\n";

    $message .= "-- \n";
    $message .= $this->user->fullname."\n";

    $mailfile = new DolibarrMail($subject,
				 $sendto,
				 $from,
				 $message);

    $mailfile->addr_bcc = TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;

    $mailfile->PrepareFile(array($filename),
			   array("application/msexcel"),
			   array($this->datef.".xls"));



    $mailfile->write_to_file();

    if ( $mailfile->sendfile() )
      {
	return 0;
      }
    else
      {
	dolibarr_syslog("CommandeMethodeTableur::MailFile Erreur send");
      }

  }

  /*
   * Création du fichier
   *
   */

  function CreateFile($fname)
  {
    $ligne = new LigneTel($db);

    $workbook = &new writeexcel_workbook($fname);

    $worksheet = &$workbook->addworksheet();
    
    $worksheet->write(0, 0,  "Commande du ".strftime("%d %B %Y %HH%M", $this->date));
    
    $worksheet->set_column('A:A', 20);
    $worksheet->set_column('B:B', 40);
    $worksheet->set_column('C:C', 15);
    $worksheet->set_column('D:D', 9);
    $worksheet->set_column('E:E', 16);
    $worksheet->set_column('F:F', 18);   
    $worksheet->set_column('G:G', 24);
    
    $formatcc =& $workbook->addformat();
    $formatcc->set_align('center');
    $formatcc->set_align('vcenter');
    
    $formatccb =& $workbook->addformat();
    $formatccb->set_align('center');
    $formatccb->set_align('vcenter');
    $formatccb->set_bold();

    $formatccbr =& $workbook->addformat();
    $formatccbr->set_align('center');
    $formatccbr->set_align('vcenter');
    $formatccbr->set_color('red');
    $formatccbr->set_bold();

    $formatc =& $workbook->addformat();
    $formatc->set_align('vcenter');
    
    $formatcb =& $workbook->addformat();
    $formatcb->set_align('vcenter');
    $formatcb->set_bold();

    $i = 0;

    $this->ligneids = array();
    
    $sqlall = "SELECT s.nom, s.rowid as socid, f.nom as fournisseur";
    $sqlall .= ", l.ligne, l.statut, l.rowid, l.remise";
    $sqlall .= ",".$this->db->pdate("l.date_commande") . " as date_commande";
    $sqlall .= " , comm.name, comm.firstname";
    $sqlall .= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sqlall .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
    $sqlall .= " , ".MAIN_DB_PREFIX."societe as r";
    $sqlall .= " , ".MAIN_DB_PREFIX."user as comm";
    $sqlall .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
    $sqlall .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
    $sqlall .= " AND l.fk_soc_facture = r.rowid ";
    $sqlall .= " AND l.fk_commercial = comm.rowid ";
    $sqlall .= " AND f.rowid =".$this->fourn->id;
    /*
     *
     */

    $sql = $sqlall;

    $sql .= " AND l.statut in (1,4,8)";
    $sql .= " ORDER BY l.statut ASC";
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
	
	$worksheet->write(1, 0,  "Clients", $formatc);
	$worksheet->write(1, 1,  "Adresses", $formatc);
	$worksheet->write(1, 2,  "CLI", $formatcc);
	$worksheet->write(1, 3,  "Préfixe", $formatcc);
	$worksheet->write(1, 4,  "Présélection", $formatcc);
	$worksheet->write(1, 5,  "Connexion", $formatcc);
	$worksheet->write(1, 6,  "Date de la demande", $formatcc);
		
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object();	

	    if (strlen($obj->ligne)== 10)
	      {
	    
		$j = $i + 2;
		$k = $j + 1;
	    
		$soc = new Societe($this->db);
		$soc->fetch($obj->socid);
		
		$worksheet->write($j, 0,  $obj->nom, $formatc);
		$worksheet->write($j, 1,  $soc->adresse. " " . $soc->cp . " " . $soc->ville, $formatc);
		
		$worksheet->write_string($j, 2,  "$obj->ligne", $formatcc);
		
		$worksheet->write_string($j, 3,  "Non", $formatcc);
		$worksheet->write_string($j, 4,  "Oui", $formatcc);
		
		if ($obj->statut == 1)
		  {
		    $worksheet->write($j, 5,  "Ajouter", $formatccb);
		    $worksheet->write($j, 6,  strftime("%d/%m/%y",$this->date), $formatcc);
		  }
		elseif ($obj->statut == 8)
		  {
		    $worksheet->write($j, 5,  "Ajouter", $formatccb);
		    $worksheet->write($j, 6,  strftime("%d/%m/%y",$this->date), $formatcc);
		  }
		elseif($obj->statut == 4)
		  {
		    $worksheet->write($j, 5,  "A Résilier", $formatccbr);
		    $worksheet->write($j, 6,  strftime("%d/%m/%y",$this->date), $formatcc);
		  }
		else
		  {
		    $worksheet->write($j, 5,  "", $formatccb);
		    $worksheet->write($j, 6,  "", $formatccb);
		  }
		
	    
		array_push($this->ligneids, $obj->rowid);
	      }
	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	dolibarr_syslog("CommandeMethodeTableur::CreateFile Erreur SQL 1");
      }

    /*
     * Archives
     * Insertion des anciennes lignes dans le fichier Excell
     */
    
    $sql = $sqlall;

    //    $sql .= "AND l.statut > 0 AND l.statut <> 1 AND l.statut <> 4";
    // Modification on ajoute au fichier seulement les lignes en commandes
    $sql .= " AND l.statut = 2";
    $sql .= " ORDER BY l.date_commande ASC";
    
    $result = $this->db->query($sql);

    if ($result)
      {
	$num = $this->db->num_rows();
	$i = 0;
	while ($i < $num)
	  {
	    $obj = $this->db->fetch_object($i);	
	    
	    $jj = $i + $j + 2;
  
	    $soc = new Societe($this->db);
	    $soc->fetch($obj->socid);

	    $worksheet->write($jj, 0,  $obj->nom, $formatc);
	    $worksheet->write($jj, 1,  $soc->adresse. " " . $soc->cp . " " . $soc->ville, $formatc);

	    $worksheet->write_string($jj, 2,  "$obj->ligne", $formatcc);

	    $worksheet->write_string($jj, 3,  "Non", $formatcc);
	    $worksheet->write_string($jj, 4,  "Oui", $formatcc);

	    $worksheet->write($jj, 5,  "", $formatccb);
	    $worksheet->write($jj, 6,  "Commandée le ".strftime("%d/%m/%y",$obj->date_commande), $formatccb);
	    

	    $i++;
	  }
	
	$this->db->free();
      }
    else 
      {
	dolibarr_syslog("CommandeMethodeTableur::CreateFile Erreur SQL 2");
      }
   
    $workbook->close();


    /*
     * Modifie le statut des lignes commandées
     *
     */

    foreach ($this->ligneids as $lid)
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

	if ($lint->statut == 8)
	  {
	    $lint->set_statut($this->user, 2);
	  }
      }

    return 0;

  }
}
