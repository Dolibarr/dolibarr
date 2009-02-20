<?php
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
 */

/**
 *	\file       htdocs/admin/import/dolibarrimport.class.php
 *	\ingroup    import
 *	\brief      Fichier de la classe des imports
 *	\version    $Id$
 */

/**
 *	\class 		DolibarrImport
 *	\brief 		Classe permettant la gestion des imports
 */
class DolibarrImport
{
	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB        Handler accès base de données
	 */
	function DolibarrImport($DB)
	{
		$this->db=$DB;
	}
	/*
	 \brief Importe un fichier clients
	 */
	function ImportClients($file)
	{
		$this->nb_import_ok = 0;
		$this->nb_import_ko = 0;
		$this->nb_import = 0;

		dol_syslog("DolibarrImport::ImportClients($file)", LOG_DEBUG);

		$this->ReadFile($file);

		foreach ($this->lines as $this->line)
		{
			$societe = new Societe($this->db);

			$this->SetInfosTiers($societe);

			$societe->client = 1;
			$societe->tva_assuj   = $this->line[12];
			$societe->code_client = $this->line[13];
			$societe->tva_intra   = $this->line[14];

			$this->nb_import++;

			if ( $societe->create($user) == 0)
	  {
	  	dol_syslog("DolibarrImport::ImportClients ".$societe->nom." SUCCESS", LOG_DEBUG);
	  	$this->nb_import_ok++;
	  }
	  else
	  {
	  	dol_syslog("DolibarrImport::ImportClients ".$societe->nom." ERROR", LOG_ERR);
	  	$this->nb_import_ko++;
	  }
		}

	}


	function SetInfosTiers(&$obj)
	{
		$obj->nom     = $this->line[0];
		$obj->adresse = $this->line[1];

		if (strlen(trim($this->line[2])) > 0)
		$obj->adresse .= "\n". trim($this->line[2]);

		if (strlen(trim($this->line[3])) > 0)
		$obj->adresse .= "\n". trim($this->line[3]);

		$obj->cp      = $this->line[4];
		$obj->ville   = $this->line[5];
		$obj->tel     = $this->line[6];
		$obj->fax     = $this->line[7];
		$obj->email   = $this->line[8];
		$obj->url     = $this->line[9];
		$obj->siren   = $this->line[10];
		$obj->siret   = $this->line[11];
	}


	function ReadFile($file)
	{
		$this->errno = 0;

		if (is_readable($file))
		{
			dol_syslog("DolibarrImport::ReadFile Lecture du fichier $file", LOG_DEBUG);

			$line = 0;
			$hf = fopen ($file, "r");
			$line = 0;
			$i=0;

			$this->lines = array();


			while (!feof($hf) )
	  {
	  	$cont = fgets($hf, 1024);
	  	 
	  	if (strlen(trim($cont)) > 0)
	  	{
	  		$this->lines[$i] = explode(";", $cont);
	  	}
	  	$i++;
	  	 
	  }
		}
		else
		{
			$this->errno = -2;
		}

		return $errno;
	}
	
	/*
	 \brief Cree le repertoire de backup
	 */
	function CreateBackupDir()
	{
		$time = time();

		$upload_dir = DOL_DATA_ROOT."/import/";

		if (! is_dir($upload_dir))
		{
			umask(0);
			if (! mkdir($upload_dir, 0755))
		  {
		  	dol_syslog("DolibarrImport::ReadFile Impossible de créer $upload_dir",LOG_ERR);
		  }
		}

		$upload_dir = DOL_DATA_ROOT."/import/".strftime("%Y",$time);

		if (! is_dir($upload_dir))
		{
			umask(0);
			if (! mkdir($upload_dir, 0755))
		  {
		  	dol_syslog("DolibarrImport::ReadFile Impossible de créer $upload_dir",LOG_ERR);
		  }
		}

		$upload_dir = DOL_DATA_ROOT."/import/".strftime("%Y",$time)."/".strftime("%d-%m-%Y",$time);

		if (! is_dir($upload_dir))
		{
			umask(0);
			if (! mkdir($upload_dir, 0755))
		  {
		  	dol_syslog("DolibarrImport::ReadFile Impossible de créer $upload_dir",LOG_ERR);
		  }
		}

		$this->upload_dir = DOL_DATA_ROOT."/import/".strftime("%Y",$time)."/".strftime("%d-%m-%Y",$time);

	}
}
?>
