<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *       \file       scripts/prelevement/prelevement.php
 *       \ingroup    prelevement
 *       \brief      Script de prelevement
 */

// Test si mode CLI
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];

if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
	exit;
}

// Recupere env dolibarr
$version='$Revision$';
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");

$error = 0;

$datetimeprev = time();

$month = strftime("%m", $datetimeprev);
$year = strftime("%Y", $datetimeprev);

$user = new user($db, PRELEVEMENT_USER);

/*
 *
 * Lectures des factures
 *
 */

$factures = array();
$factures_prev = array();

if (!$error)
{

	$sql = "SELECT f.rowid, pfd.rowid as pfdrowid, f.fk_soc";
	$sql .= ", pfd.code_banque, pfd.code_guichet, pfd.number, pfd.cle_rib";
	$sql .= ", pfd.amount";
	$sql .= ", s.nom";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql .= " , ".MAIN_DB_PREFIX."societe as s";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";

	$sql .= " WHERE f.rowid = pfd.fk_facture";
	$sql .= " AND s.rowid = f.fk_soc";
	$sql .= " AND f.fk_statut = 1";
	$sql .= " AND f.paye = 0";
	$sql .= " AND pfd.traite = 0";
	$sql .= " AND f.total_ttc > 0";
	$sql .= " AND f.fk_mode_reglement = 3";

	if ( $db->query($sql) )
	{
		$num = $db->num_rows();
		$i = 0;

		while ($i < $num)
		{
	  $row = $db->fetch_row();

	  $factures[$i] = $row;

	  $i++;
		}
		$db->free();
		dol_syslog("$i factures à prélever");
	}
	else
	{
		$error = 1;
		dol_syslog("Erreur -1");
		dol_syslog($db->error());
	}
}

/*
 *
 * Verif des clients
 *
 */

if (!$error)
{
	/*
	 * Vérification des RIB
	 *
	 */
	$i = 0;
	dol_syslog("Début vérification des RIB");

	if (sizeof($factures) > 0)
	{
		foreach ($factures as $fac)
		{
	  $fact = new Facture($db);

	  if ($fact->fetch($fac[0]) == 1)
	  {
	  	$soc = new Societe($db);
	  	if ($soc->fetch($fact->socid) == 1)
	  	{
	  		if ($soc->verif_rib() == 1)
	  		{
	  			$factures_prev[$i] = $fac;
	  			/* second tableau necessaire pour bon-prelevement */
	  			$factures_prev_id[$i] = $fac[0];
	  			$i++;
	  		}
	  		else
	  		{
	  			dol_syslog("Erreur de RIB societe $fact->socid $soc->nom");
	  		}
	  	}
	  	else
	  	{
	  		dol_syslog("Impossible de lire la société");
	  	}
	  }
	  else
	  {
	  	dol_syslog("Impossible de lire la facture");
	  }
		}
	}
	else
	{
		dol_syslog("Aucune factures a traiter");
	}
}

/*
 *
 *
 *
 */

dol_syslog(sizeof($factures_prev)." factures seront prélevées");
print 'eeee'.$factures_prev;
if (sizeof($factures_prev) > 0)
{
	/*
	 * Ouverture de la transaction
	 *
	 */

	if (!$db->query("BEGIN"))
	{
		$error++;
	}

	/*
	 * Traitements
	 *
	 */

	if (!$error)
	{
		$ref = "T".substr($year,-2).$month;

		/*
		 *
		 *
		 */
		$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."prelevement_bons";
		$sql .= " WHERE ref LIKE '$ref%'";

		if ($db->query($sql))
		{
	  $row = $db->fetch_row();
		}
		else
		{
	  $error++;
	  dol_syslog("Erreur recherche reference");
		}

		$ref = $ref . substr("00".($row[0]+1), -2);

		$filebonprev = $ref;

		/*
		 * Creation du bon de prelevement
		 *
		 */

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_bons (ref,datec)";
		$sql .= " VALUES ('".$ref."',".$db->idate(mktime()).")";

		if ($db->query($sql))
		{
	  $prev_id = $db->last_insert_id(MAIN_DB_PREFIX."prelevement_bons");

	  $bonprev = new BonPrelevement($db, DOL_DATA_ROOT."/prelevement/bon/".$filebonprev);
	  $bonprev->id = $prev_id;
		}
		else
		{
	  $error++;
	  dol_syslog("Erreur création du bon de prelevement");
		}

	}

	/*
	 *
	 *
	 *
	 */
	if (!$error)
	{
		dol_syslog("Début génération des paiements");
		dol_syslog("Nombre de factures ".sizeof($factures_prev));

		if (sizeof($factures_prev) > 0)
		{
			foreach ($factures_prev as $fac)
			{
				$fact = new Facture($db);
				$fact->fetch($fac[0]);

				$pai = new Paiement($db);

				$pai->amounts = array();
				$pai->amounts[$fac[0]] = $fact->total_ttc;
				$pai->datepaye = $db->idate($datetimeprev);
				$pai->paiementid = 3; // prélèvement
				$pai->num_paiement = $ref;

				if ($pai->create($user, 1) == -1)  // on appelle en no_commit
				{
					$error++;
					dol_syslog("Erreur creation paiement facture ".$fac[0]);
				}
				else
				{
					/*
					 * Validation du paiement
					 */
					$pai->valide();

					/*
					 * Ajout d'une ligne de prélèvement
					 *
					 *
					 * $fac[3] : banque
					 * $fac[4] : guichet
					 * $fac[5] : number
					 * $fac[6] : cle rib
					 * $fac[7] : amount
					 * $fac[8] : client nom
					 * $fac[2] : client id
					 */

					$ri = $bonprev->AddFacture($fac[0], $fac[2], $fac[8], $fac[7],
					$fac[3], $fac[4], $fac[5], $fac[6]);
					if ($ri <> 0)
					{
						$error++;
					}

					/*
					 * Mise à jour des demandes
					 *
					 */
					$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_facture_demande";
					$sql .= " SET traite = 1";
					$sql .= ", date_traite=".$db->idate(mktime());
					$sql .= ", fk_prelevement_bons = ".$prev_id;
					$sql .= " WHERE rowid=".$fac[1];

					if ($db->query($sql))
					{

					}
					else
					{
						$error++;
						dol_syslog("Erreur mise a jour des demandes");
						dol_syslog($db->error());
					}

				}
			}
		}

		dol_syslog("Fin des paiements");
	}

	if (!$error)
	{
		/*
		 * Bon de Prelevement
		 *
		 *
		 */

		dol_syslog("Debut prelevement");
		dol_syslog("Nombre de factures ".sizeof($factures_prev));

		if (sizeof($factures_prev) > 0)
		{
			$bonprev->date_echeance = $datetimeprev;
			$bonprev->reference_remise = $ref;


			$bonprev->numero_national_emetteur = PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR;
			$bonprev->raison_sociale = PRELEVEMENT_RAISON_SOCIALE;

			$bonprev->emetteur_code_etablissement = PRELEVEMENT_CODE_BANQUE;
			$bonprev->emetteur_code_guichet       = PRELEVEMENT_CODE_GUICHET;
			$bonprev->emetteur_numero_compte      = PRELEVEMENT_NUMERO_COMPTE;


			$bonprev->factures = $factures_prev_id;

			$bonprev->generate();
		}
		dol_syslog( $filebonprev ) ;
		dol_syslog("Fin prelevement");
	}

	/*
	 * Mise à jour du total
	 *
	 */

	$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
	$sql .= " SET amount = ".price2num($bonprev->total);
	$sql .= " WHERE rowid = ".$prev_id;

	if (!$db->query($sql))
	{
		$error++;
		dol_syslog("Erreur mise à jour du total");
		dol_syslog($sql);
	}

	/*
	 * Rollback ou Commit
	 *
	 */
	if (!$error)
	{
		$db->query("COMMIT");
		dol_syslog("COMMIT");
	}
	else
	{
		$db->query("ROLLBAK");
		dol_syslog("ROLLBACK");
	}
}

$db->close();

// FIN
?>
