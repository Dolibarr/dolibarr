<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Script d'import des CDR
 */
require_once(DOL_DOCUMENT_ROOT."/telephonie/fournisseurtel.class.php");

class FacturationImportCdr {

  function FacturationImportCdr($dbh)
  {
    $this->db = $dbh;
    $this->messages = array();
    $this->message_bad_file_format = array();
  }

  function CountDataImport()
  {
    $nb = 0;
    $sql = "SELECT count(*)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
    if ($this->db->query($sql))
      {  
	while ($row = $this->db->fetch_row($resql))
	  {
	    $nb = $row[0];
	  }
	$this->db->free($resql);
      }
    return $nb;
  }

  function Import($id_fourn)
  {
    $dir = DOL_DATA_ROOT."/telephonie/cdr/atraiter/".$id_fourn."/";
    
    /*
     * Traitement
     */
    $files = array();
    $unknown_lines = array();
  
    if (is_dir($dir))
      {
	$handle=opendir($dir);
	
	if ($handle)
	  {
	    $i = 0 ;
	    $var=True;
	    
	    while (($xfile = readdir($handle))!==false)
	      {
		if (is_file($dir.$xfile) && substr($xfile, -4) == ".csv")
		  {
		    $files[$i] = $dir.$xfile;
		    dol_syslog("FacturationImportCdr::Import ".$xfile." ajoute");
		    $i++;
		  }
		else
		  {
		    dol_syslog("FacturationImportCdr::Import ".$xfile." ignore");
		  }
	      }
	    
	    closedir($handle);
	  }
	else
	  {
	    dol_syslog("FacturationImportCdr::Import Impossible de lire $dir");
	    exit ;
	  }
      }
    else
      {
	dol_syslog("FacturationImportCdr::Import Impossible de lire $file");
	exit ;
      }
    
    /*
     * Verification du fournisseur
     *
     */
    $fourn = new FournisseurTelephonie($this->db);
        
    if ($fourn->fetch($id_fourn) <> 0)
      {  
	dol_syslog("FacturationImportCdr::Import Erreur recherche fournisseur", LOG_ERR);
      }
    
    /*
     * Verification des fichiers charges
     *
     */
    $fichiers = array();
    $sql = "SELECT distinct(fichier)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_import_cdr";
    if ($this->db->query($sql))
      {  
	while ($row = $this->db->fetch_row($resql))
	  {
	    array_push($fichiers, $row[0]);
	  }
	$this->db->free($resql);
      }
    else
      {
	dol_syslog("FacturationImportCdr::Import Erreur recherche si fichiers deja charges");
      }

    /*
     * Verification des fichiers traites
     *
     */
    $sql = "SELECT distinct(fichier_cdr)";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_communications_details";
    if ($this->db->query($sql))
      {  
	while ($row = $this->db->fetch_row($resql))
	  {
	    array_push($fichiers, $row[0]);
	  }
	$this->db->free($resql);
      }
    else
      {
	dol_syslog("FacturationImportCdr::Import Erreur recherche si fichiers deja traites");
      }

    /*
     * Charge les ID de lignes
     *
     */    
    $sql = "SELECT ligne, rowid ";
    $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne";
    
    $resql = $this->db->query($sql);

    if ($resql)
      {  
	$i = 0;
	$ligneids = array();
	
	while ($row = $this->db->fetch_row($resql))
	  {
	    $ligneids[$row[0]] = $row[1];
	    $i++;
	  }
      }
    else
      {
	dol_syslog("FacturationImportCdr::Import Erreur chargement des lignes", LOG_DEBUG);
	exit ;
      }
    
    if (strlen($fourn->cdrformat))
      {
	if (@require_once(DOL_DOCUMENT_ROOT."/telephonie/fournisseur/cdrformat/cdrformat.".$fourn->cdrformat.".class.php"))
	  {
	    $format = "CdrFormat".ucfirst($fourn->cdrformat);
	    $cdrformat = new $format();
	  }
      }


    foreach ($files as $xfile)
      {
	if (is_readable($xfile))
	  {
	    if ( $this->_verif($this->db, $xfile, $fichiers) == 0)
	      {      
		$error = 0;
		dol_syslog("FacturationImportCdr::Import Lecture du fichier $xfile", LOG_DEBUG);
		array_push($this->messages,array('info',"Fichier ".basename($xfile)." : utilisation format ".$cdrformat->nom));

		$error = $cdrformat->ReadFile($xfile);

		$this->messages=array_merge($this->messages, $cdrformat->messages);


		$line = 0;
		$line_inserted = 0;
		$hf = fopen ($xfile, "r");
		$line = 0;
		
		if ($this->db->query("BEGIN") && $error == 0)
		  {  
		    foreach ($cdrformat->datas as $data)
		      {
			$ligne = $data['ligne'];
			$duree_secondes = $data['duree'];
			      
			if ($ligneids[$ligne] > 0)
			  {
			    if ($duree_secondes > 0)
			      {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_import_cdr";
				$sql .= "(idx,fk_ligne,ligne,date,heure,num,dest,dureetext,tarif,montant,duree";
				$sql .= ", fichier, fk_fournisseur)";			  
				$sql .= " VALUES (";
				$sql .= $data['index'];
				$sql .= ",'".$ligneids[$ligne]."'";
				$sql .= ",'".$ligne."'";
				$sql .= ",'".$data['date']."'";
				$sql .= ",'".$data['heure']."'";
				$sql .= ",'".$data['numero']."'";
				$sql .= ",'".addslashes(ereg_replace('"','',$data['tarif']))."'";
				$sql .= ",'".ereg_replace('"','',$duree_text)."'";
				$sql .= ",'".ereg_replace('"','',$tarif_fourn)."'";
				$sql .= ",".ereg_replace(',','.',$data['montant']);
				$sql .= ",".$duree_secondes;
				$sql .= ",'".basename($xfile)."'";
				$sql .= " ,".$id_fourn;
				$sql .= ")";
				      
				if(ereg("^[0-9]+$", $duree_secondes))
				  {
				    if ($this->db->query($sql))
				      {
					$line_inserted++;
				      }
				    else
				      {
					dol_syslog("FacturationImportCdr::Import Erreur de traitement de ligne $index", LOG_ERR);
					dol_syslog("FacturationImportCdr::Import $sql", LOG_DEBUG);
					$error++;
				      }
				  }
				else
				  {
				    dol_syslog("FacturationImportCdr::Import Ligne : $cont ignoree", LOG_INFO);
				    $error++;
				  }
			      }
			    else
			      {
				dol_syslog("FacturationImportCdr::Import Duree nulle Ligne : $cont ignoree", LOG_INFO);
			      }
			  }
			else
			  {
			    if (!in_array($ligne, $unknown_lines))
			      {
				dol_syslog("FacturationImportCdr::Import Ligne $ligne inconnue Ligne : $cont ignoree", LOG_INFO);
				array_push($this->messages,array('warning',"Ligne $ligne inconnue"));
			      }
			    array_push($unknown_lines, $ligne);
			  }
			$line++;
		      }		    

		    dol_syslog("FacturationImportCdr::Import $line lignes traitees dans le fichier", LOG_INFO);
		    $level = ($line > 0) ? 'info':'warning';
		    array_push($this->messages,array($level,"$line lignes traitees dans le fichier"));		
		    dol_syslog("FacturationImportCdr::Import $line_inserted insert effectues", LOG_INFO);
		    $level = ($line_inserted > 0) ? 'info':'warning';
		    array_push($this->messages,array($level,"$line_inserted ajout dans la table des CDR a traiter"));
		    
		    if (sizeof($this->message_bad_file_format))
		      {
			foreach ($this->message_bad_file_format as $key => $value)
			  {
			    array_push($this->messages,array('warning',"$value ligne(s) au mauvais format dans $key"));
			  }
		      }
		    
		    if ($error == 0)
		      {	  
			$this->db->query("COMMIT");
			array_push($this->messages, array('info',"Fichier ".basename($xfile)." : importation reussie"));
		      }
		    else
		      {
			$this->db->query("ROLLBACK");
			dol_syslog("ROLLBACK");
			array_push($this->messages, array('error',"Fichier ".basename($xfile)." : echec de l'importation"));
		      }		    
		  }
		else
		  {
		    array_push($this->messages, array('error',"Fichier ".basename($xfile)." : echec de l'importation"));
		  }
	      }
	  }
	else
	  {
	    print "Erreur lecture : $xfile";
	    dol_syslog($xfile . " not readable");
	    array_push($this->messages, "Fichier ".basename($xfile)." not readable");
	  }
      }	

    return $error;    
  }

  /**
     \brief Verifie que le fichier n'a pas deja ete charge/traite
  */    
  function _verif($db, $file, $fichiers)
  {
    $result = 0;

    if (in_array (basename($file), $fichiers))
      {
	dol_syslog ("Fichier ".basename($file)." deja charge/traite");
	array_push($this->messages, array('warning',"Fichier ".basename($file)." deja charge/traite"));
	$result = -1;
      }
        
    return $result;
  }

}
