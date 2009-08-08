<?php
/* Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 *	\file       htdocs/install/upgrade2.php
 *	\brief      Effectue la migration de donnees diverses
 *	\version    $Id$
 */

include_once('./inc.php');
if (file_exists($conffile)) include_once($conffile);
require_once($dolibarr_main_document_root . "/lib/databases/".$dolibarr_main_db_type.".lib.php");
require_once($dolibarr_main_document_root . '/facture.class.php');
require_once($dolibarr_main_document_root . '/propal.class.php');
require_once($dolibarr_main_document_root . '/contrat/contrat.class.php');
require_once($dolibarr_main_document_root . '/commande/commande.class.php');
require_once($dolibarr_main_document_root . '/fourn/fournisseur.commande.class.php');
require_once($dolibarr_main_document_root . '/lib/price.lib.php');
require_once($dolibarr_main_document_root . '/core/menubase.class.php');

$grant_query='';
$etape = 2;
$error = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang=isset($_POST['selectlang'])?$_POST['selectlang']:(isset($_GET['selectlang'])?$_GET['selectlang']:'auto');
$langs->setDefaultLang($setuplang);

$langs->load('admin');
$langs->load('install');
$langs->load("bills");
$langs->load("suppliers");

if ($dolibarr_main_db_type == 'mysql')  $choix=1;
if ($dolibarr_main_db_type == 'mysqli') $choix=1;
if ($dolibarr_main_db_type == 'pgsql')  $choix=2;
if ($dolibarr_main_db_type == 'mssql')  $choix=3;


dolibarr_install_syslog("upgrade2: Entering upgrade2.php page");
if (! is_object($conf)) dolibarr_install_syslog("upgrade2: conf file not initialized",LOG_ERR);


pHeader('','etape5','upgrade');


if (isset($_POST['action']) && $_POST['action'] == 'upgrade')
{
	print '<h3>'.$langs->trans('DataMigration').'</h3>';

	print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';

	// decode database pass if needed
	if (! empty($dolibarr_main_db_encrypted_pass))
	{
		require_once($dolibarr_main_document_root."/lib/security.lib.php");
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}

	// $conf is already instancied inside inc.php
	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);
	if ($db->connected != 1)
	{
		print '<tr><td colspan="4">'.$langs->trans("ErrorFailedToConnectToDatabase",$conf->db->name).'</td><td align="right">'.$langs->trans('Error').'</td></tr>';
		dolibarr_install_syslog('upgrade2: Failed to connect to database : '.$conf->db->name.' on '.$conf->db->host.' for user '.$conf->db->user, LOG_ERR);
		$error++;
	}

	if (! $error)
	{
		if($db->database_selected == 1)
		{
			dolibarr_install_syslog('upgrade2: Database connection successfull : '.$dolibarr_main_db_name);
		}
		else
		{
			$error++;
		}
	}

	if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
	$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
	if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
	$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

	// Chargement config
	if (! $error) $conf->setValues($db);


	/*
	 * Pour utiliser d'autres versions des librairies externes que les
	 * versions embarquees dans Dolibarr, definir les constantes adequates:
	 * Pour FPDF:           FPDF_PATH
	 * Pour PHP_WriteExcel: PHP_WRITEEXCEL_PATH
	 * Pour MagpieRss:      MAGPIERSS_PATH
	 * Pour NuSOAP:         NUSOAP_PATH
	 * Pour TCPDF:          TCPDF_PATH
	 */
	if (! defined('FPDF_PATH'))           { define('FPDF_PATH',          DOL_DOCUMENT_ROOT .'/includes/fpdf/fpdf/'); }
	if (! defined('PHP_WRITEEXCEL_PATH')) { define('PHP_WRITEEXCEL_PATH',DOL_DOCUMENT_ROOT .'/includes/php_writeexcel/'); }
	if (! defined('MAGPIERSS_PATH'))      { define('MAGPIERSS_PATH',     DOL_DOCUMENT_ROOT .'/includes/magpierss/'); }
	if (! defined('NUSOAP_PATH'))         { define('NUSOAP_PATH',        DOL_DOCUMENT_ROOT .'/includes/nusoap/lib/'); }
	// Les autres path
	if (! defined('FPDF_FONTPATH'))       { define('FPDF_FONTPATH',      FPDF_PATH . 'font/'); }
	if (! defined('MAGPIE_DIR'))          { define('MAGPIE_DIR',         MAGPIERSS_PATH); }
	if (! defined('MAGPIE_CACHE_DIR'))    { define('MAGPIE_CACHE_DIR',   DOL_DATA_ROOT .'/rss/temp'); }


	/***************************************************************************************
	 *
	 * Migration des donnees
	 *
	 ***************************************************************************************/
	if (! $error)
	{

		$db->begin();

		// Chaque action de migration doit renvoyer une ligne sur 4 colonnes avec
		// dans la 1ere colonne, la description de l'action a faire
		// dans la 4eme colonne, le texte 'OK' si fait ou 'AlreadyDone' si rien n'est fait ou 'Error'


		// Script pour V2 -> V2.1
		migrate_paiements($db,$langs,$conf);

		migrate_contracts_det($db,$langs,$conf);

		migrate_contracts_date1($db,$langs,$conf);

		migrate_contracts_date2($db,$langs,$conf);

		migrate_contracts_date3($db,$langs,$conf);

		migrate_contracts_open($db,$langs,$conf);

		migrate_modeles($db,$langs,$conf);

		migrate_price_propal($db,$langs,$conf);

		migrate_price_commande($db,$langs,$conf);

		migrate_price_commande_fournisseur($db,$langs,$conf);

		migrate_price_facture($db,$langs,$conf);

		migrate_price_contrat($db,$langs,$conf);

		migrate_paiementfourn_facturefourn($db,$langs,$conf);


		// Script pour V2.1 -> V2.2
		migrate_paiements_orphelins_1($db,$langs,$conf);

		migrate_paiements_orphelins_2($db,$langs,$conf);

		migrate_links_transfert($db,$langs,$conf);

		migrate_delete_old_files($db,$langs,$conf);


		// Script pour V2.2 -> V2.4
		migrate_commande_expedition($db,$langs,$conf);

		migrate_commande_livraison($db,$langs,$conf);

		migrate_detail_livraison($db,$langs,$conf);

		migrate_module_menus($db,$langs,$conf);


		// Script pour V2.5 -> V2.6
		migrate_stocks($db,$langs,$conf);


		// Script pour V2.6 -> V2.7
		migrate_menus($db,$langs,$conf);

		migrate_commande_deliveryaddress($db,$langs,$conf);


		// On commit dans tous les cas.
		// La procedure etant concue pour pouvoir passer plusieurs fois quelquesoit la situation.
		$db->commit();
		$db->close();
	}

	print '</table>';

}
else
{
	print '<div class="error">'.$langs->trans('ErrorWrongParameters').'</div>';
	$error++;
}


pFooter($error,$setuplang);




/**
 * Reporte liens vers une facture de paiements sur table de jointure (lien n-n paiements factures)
 */
function migrate_paiements($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	$sql = "SELECT p.rowid, p.fk_facture, p.amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " WHERE p.fk_facture > 0";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_paiements sql=".$sql);
	if ($resql)
	{
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$row[$i][0] = $obj->rowid ;
			$row[$i][1] = $obj->fk_facture;
			$row[$i][2] = $obj->amount;
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}

	if ($num)
	{
		print $langs->trans('MigrationPaymentsNumberToUpdate', $num)."<br>\n";
		if ($db->begin())
		{
			$res = 0;
			for ($i = 0 ; $i < sizeof($row) ; $i++)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
				$sql .= " VALUES (".$row[$i][1].",".$row[$i][0].",".$row[$i][2].")";

				$res += $db->query($sql);

				$sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_facture = 0 WHERE rowid = ".$row[$i][0];

				$res += $db->query($sql);

				print $langs->trans('MigrationProcessPaymentUpdate', $row[$i][0])."<br>\n";
			}
		}

		if ($res == (2 * sizeof($row)))
		{
			$db->commit();
			print $langs->trans('MigrationSuccessfullUpdate')."<br>";
		}
		else
		{
			$db->rollback();
			print $langs->trans('MigrationUpdateFailed').'<br>';
		}
	}
	else
	{
		print $langs->trans('MigrationPaymentsNothingToUpdate')."<br>\n";
	}

	print '</td></tr>';
}


/**
 * Corrige paiement orphelins (liens paumes suite a bugs)
 * Pour verifier s'il reste des orphelins:
 * select * from llx_paiement as p left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid WHERE pf.rowid IS NULL AND (p.fk_facture = 0 OR p.fk_facture IS NULL)
 */
function migrate_paiements_orphelins_1($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	// Tous les enregistrements qui sortent de cette requete devrait avoir un pere dans llx_paiement_facture
	$sql = "SELECT distinct p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
	$sql.= " bu2.url_id as socid";
	$sql.= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
	$sql.= " left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid";
	$sql.= " left join llx_bank_url as bu2 on (bu.fk_bank=bu2.fk_bank AND bu2.type='company')";
	$sql.= " WHERE pf.rowid IS NULL AND (p.rowid=bu.url_id AND bu.type='payment') AND bu.fk_bank = b.rowid";
	$sql.= " AND b.rappro = 1";
	$sql.= " AND (p.fk_facture = 0 OR p.fk_facture IS NULL)";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_paiements_orphelins_1 sql=".$sql);
	$row = array();
	if ($resql)
	{
		$i = $j = 0;
		$num = $db->num_rows($resql);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj->pamount == $obj->bamount && $obj->socid)	// Pour etre sur d'avoir bon cas
			{
				$row[$j]['paymentid'] = $obj->rowid ;		// paymentid
				$row[$j]['pamount'] = $obj->pamount;
				$row[$j]['fk_bank'] = $obj->fk_bank;
				$row[$j]['bamount'] = $obj->bamount;
				$row[$j]['socid'] = $obj->socid;
				$row[$j]['datec'] = $obj->datec;
				$j++;
			}
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}

	if (sizeof($row))
	{
		print $langs->trans('OrphelinsPaymentsDetectedByMethod', 1).': '.sizeof($row)."<br>\n";
		$db->begin();

		$res = 0;
		for ($i = 0 ; $i < sizeof($row) ; $i++)
		{
			if ($conf->global->MAIN_FEATURES_LEVEL == 2) print '* '.$row[$i]['datec'].' paymentid='.$row[$i]['paymentid'].' pamount='.$row[$i]['pamount'].' fk_bank='.$row[$i]['fk_bank'].' bamount='.$row[$i]['bamount'].' socid='.$row[$i]['socid'].'<br>';

			// On cherche facture sans lien paiement et du meme montant et pour meme societe.
			$sql=" SELECT distinct f.rowid from ".MAIN_DB_PREFIX."facture as f";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
			$sql.=" WHERE f.fk_statut in (2,3) AND fk_soc = ".$row[$i]['socid']." AND total_ttc = ".$row[$i]['pamount'];
			$sql.=" AND pf.fk_facture IS NULL";
			$sql.=" ORDER BY f.fk_statut";
			//print $sql.'<br>';
			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				//print 'Nb of invoice found for this amount and company :'.$num.'<br>';
				if ($num >= 1)
				{
					$obj=$db->fetch_object($resql);
					$facid=$obj->rowid;

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
					$sql .= " VALUES (".$facid.",".$row[$i]['paymentid'].",".$row[$i]['pamount'].")";
					$res += $db->query($sql);

					print $langs->trans('MigrationProcessPaymentUpdate', 'facid='.$facid.'-paymentid='.$row[$i]['paymentid'].'-amount='.$row[$i]['pamount'])."<br>\n";
				}
			}
			else
			{
				print 'ERROR';
			}
		}

		if ($res > 0)
		{
			print $langs->trans('MigrationSuccessfullUpdate')."<br>";
		}
		else
		{
			print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
		}

		$db->commit();
	}
	else
	{
		print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
	}

	print '</td></tr>';
}


/**
 * Corrige paiement orphelins (liens paumes suite a bugs)
 * Pour verifier s'il reste des orphelins:
 * select * from llx_paiement as p left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid WHERE pf.rowid IS NULL AND (p.fk_facture = 0 OR p.fk_facture IS NULL)
 */
function migrate_paiements_orphelins_2($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

	// Tous les enregistrements qui sortent de cette requete devrait avoir un pere dans llx_paiement_facture
	$sql = "SELECT distinct p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
	$sql.= " bu2.url_id as socid";
	$sql.= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
	$sql.= " left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid";
	$sql.= " left join llx_bank_url as bu2 on (bu.fk_bank=bu2.fk_bank AND bu2.type='company')";
	$sql.= " WHERE pf.rowid IS NULL AND (p.fk_bank=bu.fk_bank AND bu.type='payment') AND bu.fk_bank = b.rowid";
	$sql.= " AND (p.fk_facture = 0 OR p.fk_facture IS NULL)";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_paiements_orphelins_2 sql=".$sql);
	$row = array();
	if ($resql)
	{
		$i = $j = 0;
		$num = $db->num_rows($resql);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj->pamount == $obj->bamount && $obj->socid)	// Pour etre sur d'avoir bon cas
			{
				$row[$j]['paymentid'] = $obj->rowid ;		// paymentid
				$row[$j]['pamount'] = $obj->pamount;
				$row[$j]['fk_bank'] = $obj->fk_bank;
				$row[$j]['bamount'] = $obj->bamount;
				$row[$j]['socid'] = $obj->socid;
				$row[$j]['datec'] = $obj->datec;
				$j++;
			}
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}

	if (sizeof($row))
	{
		print $langs->trans('OrphelinsPaymentsDetectedByMethod', 2).': '.sizeof($row)."<br>\n";
		$db->begin();

		$res = 0;
		for ($i = 0 ; $i < sizeof($row) ; $i++)
		{
			if ($conf->global->MAIN_FEATURES_LEVEL == 2) print '* '.$row[$i]['datec'].' paymentid='.$row[$i]['paymentid'].' '.$row[$i]['pamount'].' fk_bank='.$row[$i]['fk_bank'].' '.$row[$i]['bamount'].' socid='.$row[$i]['socid'].'<br>';

			// On cherche facture sans lien paiement et du meme montant et pour meme societe.
			$sql=" SELECT distinct f.rowid from ".MAIN_DB_PREFIX."facture as f";
			$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
			$sql.=" WHERE f.fk_statut in (2,3) AND fk_soc = ".$row[$i]['socid']." AND total_ttc = ".$row[$i]['pamount'];
			$sql.=" AND pf.fk_facture IS NULL";
			$sql.=" ORDER BY f.fk_statut";
			//print $sql.'<br>';
			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				//print 'Nb of invoice found for this amount and company :'.$num.'<br>';
				if ($num >= 1)
				{
					$obj=$db->fetch_object($resql);
					$facid=$obj->rowid;

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
					$sql .= " VALUES (".$facid.",".$row[$i]['paymentid'].",".$row[$i]['pamount'].")";
					$res += $db->query($sql);

					print $langs->trans('MigrationProcessPaymentUpdate', 'facid='.$facid.'-paymentid='.$row[$i]['paymentid'].'-amount='.$row[$i]['pamount'])."<br>\n";
				}
			}
			else
			{
				print 'ERROR';
			}
		}

		if ($res > 0)
		{
			print $langs->trans('MigrationSuccessfullUpdate')."<br>";
		}
		else
		{
			print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
		}

		$db->commit();
	}
	else
	{
		print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
	}

	print '</td></tr>';
}


function migrate_paiements_orphelins_3($db,$langs,$conf)
{

	/*
	 select p.rowid from llx_paiement as p left join llx_paiement_facture as pf on pf.fk_paiement=p.rowid WHERE pf.rowid IS NULL AND (p.fk_facture = 0 OR p.fk_facture IS NULL)
	 Poru chaque rep, test si
	 select count(*) from llx_bank where rowid = obj->fk_bank
	 select count(*) from llx_bank_url where url_id = 128 and type='payment'
	 Si partout 0, on efface ligne de llx_paiement
	 */

}


/*
 * Mise a jour des contrats (gestion du contrat + detail de contrat)
 */
function migrate_contracts_det($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	$nberr=0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsUpdate')."</b><br>\n";

	$sql = "SELECT c.rowid as cref, c.date_contrat, c.statut, c.mise_en_service, c.fin_validite, c.date_cloture, c.fk_product, c.fk_facture, c.fk_user_author,";
	$sql.= " p.ref, p.label, p.description, p.price, p.tva_tx, p.duration, cd.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
	$sql.= " ON c.fk_product = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd";
	$sql.= " ON c.rowid=cd.fk_contrat";
	$sql.= " WHERE cd.rowid IS NULL AND p.rowid IS NOT NULL";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_contracts_det sql=".$sql);
	if ($resql)
	{
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num)
		{
			print $langs->trans('MigrationContractsNumberToUpdate', $num)."<br>\n";
			$db->begin();

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet (";
				$sql.= "fk_contrat, fk_product, statut, label, description,";
				$sql.= "date_ouverture_prevue, date_ouverture, date_fin_validite, tva_tx, qty,";
				$sql.= "subprice, price_ht, fk_user_author, fk_user_ouverture)";
				$sql.= " VALUES (";
				$sql.= $obj->cref.",".($obj->fk_product?$obj->fk_product:0).",";
				$sql.= ($obj->mise_en_service?"4":"0").",";
				$sql.= "'".addslashes($obj->label)."', null,";
				$sql.= ($obj->mise_en_service?"'".$obj->mise_en_service."'":($obj->date_contrat?"'".$obj->date_contrat."'":"null")).",";
				$sql.= ($obj->mise_en_service?"'".$obj->mise_en_service."'":"null").",";
				$sql.= ($obj->fin_validite?"'".$obj->fin_validite."'":"null").",";
				$sql.= "'".$obj->tva_tx."', 1,";
				$sql.= "'".$obj->price."', '".$obj->price."',".$obj->fk_user_author.",";
				$sql.= ($obj->mise_en_service?$obj->fk_user_author:"null");
				$sql.= ")";

				if ($db->query($sql))
				{
					print $langs->trans('MigrationContractsLineCreation', $obj->cref)."<br>\n";
				}
				else
				{
					dol_print_error($db);
					$nberr++;
				}

				$i++;
			}

			if (! $nberr)
			{
				//      $db->rollback();
				$db->commit();
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			}
			else
			{
				$db->rollback();
				print $langs->trans('MigrationUpdateFailed').'<br>';
			}
		}
		else {
			print $langs->trans('MigrationContractsNothingToUpdate')."<br>\n";
		}
	}
	else
	{
		print $langs->trans('MigrationContractsFieldDontExist')."<br>\n";
		//    dol_print_error($db);
	}

	print '</td></tr>';
}


function migrate_links_transfert($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	$nberr=0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationBankTransfertsUpdate')."</b><br>\n";

	$sql = "SELECT ba.rowid as barowid, bb.rowid as bbrowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as bb, ".MAIN_DB_PREFIX."bank as ba";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = ba.rowid";
	$sql.= " WHERE ba.amount = -bb.amount AND ba.fk_account <> bb.fk_account";
	$sql.= " AND ba.datev = bb.datev AND ba.datec = bb.datec";
	$sql.= " AND bu.fk_bank IS NULL";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_links_transfert sql=".$sql);
	if ($resql)
	{
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num)
		{
			print $langs->trans('MigrationBankTransfertsToUpdate', $num)."<br>\n";
			$db->begin();

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (";
				$sql.= "fk_bank, url_id, url, label, type";
				$sql.= ")";
				$sql.= " VALUES (";
				$sql.= $obj->barowid.",".$obj->bbrowid.", '/compta/bank/ligne.php?rowid=', '(banktransfert)', 'banktransfert'";
				$sql.= ")";

				print $sql.'<br>';
				dolibarr_install_syslog("migrate_links_transfert sql=".$sql);

				if (! $db->query($sql))
				{
					dol_print_error($db);
					$nberr++;
				}

				$i++;
			}

			if (! $nberr)
			{
				//      $db->rollback();
				$db->commit();
				print $langs->trans('MigrationSuccessfullUpdate')."<br>";
			}
			else
			{
				$db->rollback();
				print $langs->trans('MigrationUpdateFailed').'<br>';
			}
		}
		else {
			print $langs->trans('MigrationBankTransfertsNothingToUpdate')."<br>\n";
		}
	}
	else
	{
		dol_print_error($db);
	}

	print '</td></tr>';
}


/*
 * Mise a jour des date de contrats non renseignees
 */
function migrate_contracts_date1($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsEmptyDatesUpdate')."</b><br>\n";

	$sql="update llx_contrat set date_contrat=tms where date_contrat is null";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date1 sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql) dol_print_error($db);
	if ($db->affected_rows($resql) > 0)
	print $langs->trans('MigrationContractsEmptyDatesUpdateSuccess')."<br>\n";
	else
	print $langs->trans('MigrationContractsEmptyDatesNothingToUpdate')."<br>\n";

	$sql="update llx_contrat set datec=tms where datec is null";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date1 sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql) dol_print_error($db);
	if ($db->affected_rows($resql) > 0)
	print $langs->trans('MigrationContractsEmptyCreationDatesUpdateSuccess')."<br>\n";
	else
	print $langs->trans('MigrationContractsEmptyCreationDatesNothingToUpdate')."<br>\n";

	print '</td></tr>';
}


/*
 * Mise a jour date contrat avec date min effective mise en service si inferieur
 */
function migrate_contracts_date2($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	$nberr=0;

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsInvalidDatesUpdate')."</b><br>\n";

	$sql = "SELECT c.rowid as cref, c.datec, c.date_contrat, MIN(cd.date_ouverture) as datemin";
	$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c,";
	$sql.= " ".MAIN_DB_PREFIX."contratdet as cd";
	$sql.= " WHERE c.rowid=cd.fk_contrat AND cd.date_ouverture IS NOT NULL";
	$sql.= " GROUP BY c.rowid, c.date_contrat";
	$resql = $db->query($sql);

	dolibarr_install_syslog("upgrade2::migrate_contracts_date2 sql=".$sql);
	if ($resql)
	{
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num)
		{
			$nbcontratsmodifie=0;
			$db->begin();

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if ($obj->date_contrat > $obj->datemin)
				{
					print $langs->trans('MigrationContractsInvalidDateFix', $obj->cref, $obj->date_contrat, $obj->datemin)."<br>\n";
					$sql ="UPDATE ".MAIN_DB_PREFIX."contrat";
					$sql.=" SET date_contrat='".$obj->datemin."'";
					$sql.=" WHERE rowid=".$obj->cref;
					$resql2=$db->query($sql);
					if (! $resql2) dol_print_error($db);

					$nbcontratsmodifie++;
				}
				$i++;
			}

			$db->commit();

			if ($nbcontratsmodifie)
			print $langs->trans('MigrationContractsInvalidDatesNumber', $nbcontratsmodifie)."<br>\n";
			else
			print  $langs->trans('MigrationContractsInvalidDatesNothingToUpdate')."<br>\n";
		}
	}
	else
	{
		dol_print_error($db);
	}

	print '</td></tr>';
}


/*
 * Mise a jour des dates de creation de contrat
 */
function migrate_contracts_date3($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContractsIncoherentCreationDateUpdate')."</b><br>\n";

	$sql="update llx_contrat set datec=date_contrat where datec is null or datec > date_contrat";
	dolibarr_install_syslog("upgrade2::migrate_contracts_date3 sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql) dol_print_error($db);
	if ($db->affected_rows() > 0)
	print $langs->trans('MigrationContractsIncoherentCreationDateUpdateSuccess')."<br>\n";
	else
	print $langs->trans('MigrationContractsIncoherentCreationDateNothingToUpdate')."<br>\n";

	print '</td></tr>';
}


/*
 * Reouverture des contrats qui ont au moins une ligne non fermee
 */
function migrate_contracts_open($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationReopeningContracts')."</b><br>\n";

	$sql = "SELECT c.rowid as cref FROM llx_contrat as c, llx_contratdet as cd";
	$sql.= " WHERE cd.statut = 4 AND c.statut=2 AND c.rowid=cd.fk_contrat";
	dolibarr_install_syslog("upgrade2::migrate_contracts_open sql=".$sql);
	$resql = $db->query($sql);
	if (! $resql) dol_print_error($db);
	if ($db->affected_rows() > 0) {
		$i = 0;
		$row = array();
		$num = $db->num_rows($resql);

		if ($num)
		{
			$nbcontratsmodifie=0;
			$db->begin();

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print $langs->trans('MigrationReopenThisContract', $obj->cref)."<br>\n";
				$sql ="UPDATE ".MAIN_DB_PREFIX."contrat";
				$sql.=" SET statut=1";
				$sql.=" WHERE rowid=".$obj->cref;
				$resql2=$db->query($sql);
				if (! $resql2) dol_print_error($db);

				$nbcontratsmodifie++;

				$i++;
			}

			$db->commit();

			if ($nbcontratsmodifie)
			print $langs->trans('MigrationReopenedContractsNumber', $nbcontratsmodifie)."<br>\n";
			else
			print $langs->trans('MigrationReopeningContractsNothingToUpdate')."<br>\n";
		}
	}
	else print $langs->trans('MigrationReopeningContractsNothingToUpdate')."<br>\n";

	print '</td></tr>';
}


/**
 * Factures fournisseurs
 */
function migrate_paiementfourn_facturefourn($db,$langs,$conf)
{
	global $bc;

	print '<tr><td colspan="4">';
	print '<br>';
	print '<b>'.$langs->trans('SuppliersInvoices')."</b><br>\n";
	print '</td></tr>';

	$error = 0;
	$nb=0;
	$select_sql  = 'SELECT rowid, fk_facture_fourn, amount ';
	$select_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn ';
	$select_sql .= ' WHERE fk_facture_fourn IS NOT NULL';

	dolibarr_install_syslog("upgrade2::migrate_paiementfourn_facturefourn sql=".$select_sql);
	$select_resql = $db->query($select_sql);
	if ($select_resql)
	{
		$select_num = $db->num_rows($select_resql);
		$i=0;
		$var = true;

		// Pour chaque paiement fournisseur, on insere une ligne dans paiementfourn_facturefourn
		while (($i < $select_num) && (! $error))
		{
			$var = !$var;
			$select_obj = $db->fetch_object($select_resql);

			// Verifier si la ligne est deja dans la nouvelle table. On ne veut pas insï¿½rer de doublons.
			$check_sql  = 'SELECT fk_paiementfourn, fk_facturefourn';
			$check_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
			$check_sql .= ' WHERE fk_paiementfourn = '.$select_obj->rowid.' AND fk_facturefourn = '.$select_obj->fk_facture_fourn.';';
			$check_resql = $db->query($check_sql);
			if ($check_resql)
			{
				$check_num = $db->num_rows($check_resql);
				if ($check_num == 0)
				{
					if ($nb == 0)
					{
						print '<tr><td colspan="4" nowrap="nowrap"><b>'.$langs->trans('SuppliersInvoices').'</b></td></tr>';
						print '<tr><td>fk_paiementfourn</td><td>fk_facturefourn</td><td>'.$langs->trans('Amount').'</td><td>&nbsp;</td></tr>';
					}

					print '<tr '.$bc[$var].'>';
					print '<td>'.$select_obj->rowid.'</td><td>'.$select_obj->fk_facture_fourn.'</td><td>'.$select_obj->amount.'</td>';

					$insert_sql  = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn SET ';
					$insert_sql .= ' fk_paiementfourn = \''.$select_obj->rowid.'\',';
					$insert_sql .= ' fk_facturefourn  = \''.$select_obj->fk_facture_fourn.'\',';
					$insert_sql .= ' amount           = \''.$select_obj->amount.'\';';
					$insert_resql = $db->query($insert_sql);
					if ($insert_resql)
					{
						$nb++;
						print '<td><span style="color:green">'.$langs->trans("OK").'</span></td>';
					}
					else
					{
						print '<td><span style="color:red">Error on insert</span></td>';
						$error++;
					}
					print '</tr>';
				}
			}
			else
			{
				$error++;
			}
			$i++;
		}
	}
	else
	{
		$error++;
	}
	if (! $nb && ! $error)
	{
		print '<tr><td>'.$langs->trans("AlreadyDone").'</td></tr>';
	}
	if ($error)
	{
		print '<tr><td>'.$langs->trans("Error").'</td></tr>';
	}
}



/*
 * Mise a jour des totaux lignes de facture
 */
function migrate_price_facture($db,$langs,$conf)
{
	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationInvoice')."</b><br>\n";

	// Liste des lignes facture non a jour
	$sql = "SELECT fd.rowid, fd.qty, fd.subprice, fd.remise_percent, fd.tva_taux, fd.total_ttc, fd.info_bits,";
	$sql.= " f.rowid as facid, f.remise_percent as remise_percent_global, f.total_ttc as total_ttc_f";
	$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fd, ".MAIN_DB_PREFIX."facture as f";
	$sql.= " WHERE fd.fk_facture = f.rowid";
	$sql.= " AND (((fd.total_ttc = 0 AND fd.remise_percent != 100) or fd.total_ttc IS NULL) or f.total_ttc IS NULL)";
	//print $sql;

	dolibarr_install_syslog("upgrade2::migrate_price_facture sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$txtva = $obj->tva_taux;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$total_ttc_f = $obj->total_ttc_f;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$facligne= new FactureLigne($db);
				$facligne->fetch($rowid);

				$result=calcul_price_total($qty,$pu,$remise_percent,$txtva,$remise_percent_global,'HT',$info_bits);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$facligne->total_ht  = $total_ht;
				$facligne->total_tva = $total_tva;
				$facligne->total_ttc = $total_ttc;

				dolibarr_install_syslog("upgrade2: Line $rowid: facid=$obj->facid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
				print ".";
				$facligne->update_total();


				/* On touche a facture mere uniquement si total_ttc = 0 */
				if (! $total_ttc_f)
				{
					$facture = new Facture($db);
					$facture->id=$obj->facid;

					if ( $facture->fetch($facture->id) >= 0)
					{
						if ( $facture->update_price() > 0 )
						{
							//print $facture->id;
						}
						else
						{
							print "Error id=".$facture->id;
							$err++;
						}
					}
					else
					{
						print "Error #3";
						$err++;
					}
				}
				print " ";

				$i++;
			}
		}
		else
		{
			print $langs->trans("AlreadyDone");
		}
		$db->free($resql);

		$db->commit();
	}
	else
	{
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}


/*
 * Mise a jour des totaux lignes de propal
 */
function migrate_price_propal($db,$langs,$conf)
{
	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationProposal')."</b><br>\n";

	// Liste des lignes propal non a jour
	$sql = "SELECT pd.rowid, pd.qty, pd.subprice, pd.remise_percent, pd.tva_tx as tva_taux, pd.info_bits,";
	$sql.= " p.rowid as propalid, p.remise_percent as remise_percent_global";
	$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pd, ".MAIN_DB_PREFIX."propal as p";
	$sql.= " WHERE pd.fk_propal = p.rowid";
	$sql.= " AND ((pd.total_ttc = 0 AND pd.remise_percent != 100) or pd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_propal sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$txtva = $obj->tva_taux;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$propalligne= new PropaleLigne($db);
				$propalligne->fetch($rowid);

				$result=calcul_price_total($qty,$pu,$remise_percent,$txtva,$remise_percent_global,'HT',$info_bits);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$propalligne->total_ht  = $total_ht;
				$propalligne->total_tva = $total_tva;
				$propalligne->total_ttc = $total_ttc;

				dolibarr_install_syslog("upgrade2: Line $rowid: propalid=$obj->rowid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
				print ". ";
				$propalligne->update_total($rowid);


				/* On touche pas a propal mere
				 $propal = new Propal($db);
				 $propal->id=$obj->rowid;
				 if ( $propal->fetch($propal->id) >= 0 )
				 {
					if ( $propal->update_price() > 0 )
					{
					print ". ";
					}
					else
					{
					print "Error id=".$propal->id;
					$err++;
					}
					}
					else
					{
					print "Error #3";
					$err++;
					}
					*/
				$i++;
			}
		}
		else
		{
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		$db->commit();
	}
	else
	{
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}



/*
 * Mise a jour des totaux lignes de propal
 */
function migrate_price_contrat($db,$langs,$conf)
{
	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationContract')."</b><br>\n";

	// Liste des lignes contrat non a jour
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as tva_taux, cd.info_bits,";
	$sql.= " c.rowid as contratid";
	$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
	$sql.= " WHERE cd.fk_contrat = c.rowid";
	$sql.= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100 AND cd.subprice > 0) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_contrat sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$txtva = $obj->tva_taux;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$contratligne= new ContratLigne($db);
				//$contratligne->fetch($rowid); Non requis car le update_total ne met a jour que chp redefinis
				$contratligne->rowid=$rowid;

				$result=calcul_price_total($qty,$pu,$remise_percent,$txtva,$remise_percent_global,'HT',$info_bits);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$contratligne->total_ht  = $total_ht;
				$contratligne->total_tva = $total_tva;
				$contratligne->total_ttc = $total_ttc;

				dolibarr_install_syslog("upgrade2: Line $rowid: contratdetid=$obj->rowid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
				print ". ";
				$contratligne->update_total($rowid);


				/* On touche pas a contrat mere
				 $propal = new Propal($db);
				 $propal->id=$obj->rowid;
				 if ( $propal->fetch($propal->id) >= 0 )
				 {
					if ( $propal->update_price() > 0 )
					{
					print ". ";
					}
					else
					{
					print "Error id=".$propal->id;
					$err++;
					}
					}
					else
					{
					print "Error #3";
					$err++;
					}
					*/
				$i++;
			}
		}
		else
		{
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		$db->commit();
	}
	else
	{
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}


/*
 * Mise a jour des totaux lignes de commande
 */
function migrate_price_commande($db,$langs,$conf)
{
	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationOrder')."</b><br>\n";

	// Liste des lignes commande non a jour
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as tva_taux, cd.info_bits,";
	$sql.= " c.rowid as commandeid, c.remise_percent as remise_percent_global";
	$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."commande as c";
	$sql.= " WHERE cd.fk_commande = c.rowid";
	$sql.= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_commande sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$txtva = $obj->tva_taux;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$commandeligne= new CommandeLigne($db);
				$commandeligne->fetch($rowid);

				$result=calcul_price_total($qty,$pu,$remise_percent,$txtva,$remise_percent_global,'HT',$info_bits);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$commandeligne->total_ht  = $total_ht;
				$commandeligne->total_tva = $total_tva;
				$commandeligne->total_ttc = $total_ttc;

				dolibarr_install_syslog("upgrade2: Line $rowid: commandeid=$obj->rowid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
				print ". ";
				$commandeligne->update_total($rowid);

				/* On touche pas a facture mere
				 $commande = new Commande($db);
				 $commande->id = $obj->rowid;
				 if ( $commande->fetch($commande->id) >= 0 )
				 {
					if ( $commande->update_price() > 0 )
					{
					print ". ";
					}
					else
					{
					print "Error id=".$commande->id;
					$err++;
					}
					}
					else
					{
					print "Error #3";
					$err++;
					}
					*/
				$i++;
			}
		}
		else
		{
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		/*
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commandedet";
		$sql.= " WHERE price = 0 and total_ttc = 0 and total_tva = 0 and total_ht = 0 AND remise_percent = 0";
		$resql=$db->query($sql);
		if (! $resql)
		{
			dol_print_error($db);
		}
		*/

		$db->commit();
	}
	else
	{
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}


/*
 * Mise a jour des totaux lignes de commande fournisseur
 */
function migrate_price_commande_fournisseur($db,$langs,$conf)
{
	$db->begin();

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationSupplierOrder')."</b><br>\n";

	// Liste des lignes commande non a jour
	$sql = "SELECT cd.rowid, cd.qty, cd.subprice, cd.remise_percent, cd.tva_tx as tva_taux, cd.info_bits,";
	$sql.= " c.rowid as commandeid, c.remise_percent as remise_percent_global";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd, ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sql.= " WHERE cd.fk_commande = c.rowid";
	$sql.= " AND ((cd.total_ttc = 0 AND cd.remise_percent != 100) or cd.total_ttc IS NULL)";

	dolibarr_install_syslog("upgrade2::migrate_price_commande_fournisseur sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$rowid = $obj->rowid;
				$qty = $obj->qty;
				$pu = $obj->subprice;
				$txtva = $obj->tva_taux;
				$remise_percent = $obj->remise_percent;
				$remise_percent_global = $obj->remise_percent_global;
				$info_bits = $obj->info_bits;

				// On met a jour les 3 nouveaux champs
				$commandeligne= new CommandeFournisseurLigne($db);
				$commandeligne->fetch($rowid);

				$result=calcul_price_total($qty,$pu,$remise_percent,$txtva,$remise_percent_global,'HT',$info_bits);
				$total_ht  = $result[0];
				$total_tva = $result[1];
				$total_ttc = $result[2];

				$commandeligne->total_ht  = $total_ht;
				$commandeligne->total_tva = $total_tva;
				$commandeligne->total_ttc = $total_ttc;

				dolibarr_install_syslog("upgrade2: Line $rowid: commandeid=$obj->rowid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
				print ". ";
				$commandeligne->update_total($rowid);

				/* On touche pas a facture mere
				 $commande = new Commande($db);
				 $commande->id = $obj->rowid;
				 if ( $commande->fetch($commande->id) >= 0 )
				 {
					if ( $commande->update_price() > 0 )
					{
					print ". ";
					}
					else
					{
					print "Error id=".$commande->id;
					$err++;
					}
					}
					else
					{
					print "Error #3";
					$err++;
					}
					*/
				$i++;
			}
		}
		else
		{
			print $langs->trans("AlreadyDone");
		}

		$db->free($resql);

		/*
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."commande_fournisseurdet";
		$sql.= " WHERE subprice = 0 and total_ttc = 0 and total_tva = 0 and total_ht = 0";
		$resql=$db->query($sql);
		if (! $resql)
		{
			dol_print_error($db);
		}
		*/

		$db->commit();
	}
	else
	{
		print "Error #1 ".$db->error();
		$err++;

		$db->rollback();
	}

	print '<br>';

	print '</td></tr>';
}


/*
 * Mise a jour des modeles selectionnes
 */
function migrate_modeles($db,$langs,$conf)
{
	//print '<br>';
	//print '<b>'.$langs->trans('UpdateModelsTable')."</b><br>\n";

	dolibarr_install_syslog("upgrade2::migrate_modeles");

	if (! empty($conf->facture->enabled))
	{
		include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
		$model=new ModelePDFFactures();
		$modellist=$model->liste_modeles($db);
		if (sizeof($modellist)==0)
		{
			// Aucun model par defaut.
			$sql=" insert into llx_document_model(nom,type) values('crabe','invoice')";
			$resql = $db->query($sql);
			if (! $resql) dol_print_error($db);
		}
	}

	if (! empty($conf->commande->enabled))
	{
		include_once(DOL_DOCUMENT_ROOT.'/includes/modules/commande/modules_commande.php');
		$model=new ModelePDFCommandes();
		$modellist=$model->liste_modeles($db);
		if (sizeof($modellist)==0)
		{
			// Aucun model par defaut.
			$sql=" insert into llx_document_model(nom,type) values('einstein','order')";
			$resql = $db->query($sql);
			if (! $resql) dol_print_error($db);
		}
	}

	if (! empty($conf->expedition->enabled))
	{
		include_once(DOL_DOCUMENT_ROOT.'/includes/modules/expedition/pdf/ModelePdfExpedition.class.php');
		$model=new ModelePDFExpedition();
		$modellist=$model->liste_modeles($db);
		if (sizeof($modellist)==0)
		{
			// Aucun model par defaut.
			$sql=" insert into llx_document_model(nom,type) values('rouget','shipping')";
			$resql = $db->query($sql);
			if (! $resql) dol_print_error($db);
		}
	}

	//print $langs->trans("AlreadyDone");
}

/*
 * Supprime fichiers obsoletes
 */
function migrate_delete_old_files($db,$langs,$conf)
{
	$result=true;

	dolibarr_install_syslog("upgrade2::migrate_delete_old_files");

	// List of files to delete
	$filetodeletearray=array(
	DOL_DOCUMENT_ROOT.'/includes/triggers/interface_demo.class.php',
	DOL_DOCUMENT_ROOT.'/includes/menus/barre_left/default.php',
	DOL_DOCUMENT_ROOT.'/includes/menus/barre_top/default.php',
	DOL_DOCUMENT_ROOT.'/includes/modules/modComptabiliteExpert.class.php'
	);

	foreach ($filetodeletearray as $filetodelete)
	{
		//print '<b>'.$filetodelete."</b><br>\n";
		if (file_exists($filetodelete))
		{
			$result=dol_delete_file($filetodelete);
		}
		if (! $result)
		{
			$langs->load("errors");
			print '<div class="error">'.$langs->trans("Error").': '.$langs->trans("ErrorFailToDeleteFile",$filetodelete);
			print ' '.$langs->trans("RemoveItManuallyAndPressF5ToContinue").'</div>';
		}
	}
	return $result;
}

/*
 * Supprime fichiers obsoletes
 */
function migrate_module_menus($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_module_menus");

	if (! empty($conf->global->MAIN_MODULE_AGENDA))
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Agenda");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modAgenda.class.php');
		$mod=new modAgenda($db);
		$mod->remove('noboxes');
		$mod->init('noboxes');
	}
	if (! empty($conf->global->MAIN_MODULE_PHENIX))
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Phenix");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modPhenix.class.php');
		$mod=new modPhenix($db);
		$mod->init();
	}
	if (! empty($conf->global->MAIN_MODULE_WEBCALENDAR))
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Webcalendar");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modWebcalendar.class.php');
		$mod=new modWebcalendar($db);
		$mod->init();
	}
	if (! empty($conf->global->MAIN_MODULE_MANTIS))
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Mantis");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modMantis.class.php');
		$mod=new modMantis($db);
		$mod->init();
	}
	if (! empty($conf->global->MAIN_MODULE_SOCIETE))
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Societe");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modSociete.class.php');
		$mod=new modSociete($db);
		$mod->remove('noboxes');
		$mod->init('noboxes');
	}
	if (! empty($conf->global->MAIN_MODULE_PRODUIT))	// Permission has changed into 2.7
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Produit");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modProduit.class.php');
		$mod=new modProduit($db);
		$mod->init();
	}
	if (! empty($conf->global->MAIN_MODULE_SERVICE))	// Permission has changed into 2.7
	{
		dolibarr_install_syslog("upgrade2::migrate_module_menus Reactivate module Service");
		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/modService.class.php');
		$mod=new modService($db);
		$mod->init();
	}
}

/*
 * Correspondance des expeditions et des commandes clients dans la table llx_co_exp
 */
function migrate_commande_expedition($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_expedition");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationShipmentOrderMatching')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."expedition","fk_commande");
	$obj = $db->fetch_object($result);
	if ($obj)
	{
		$error = 0;

		$db->begin();

		$sql = "SELECT e.rowid, e.fk_commande FROM ".MAIN_DB_PREFIX."expedition as e";
		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num)
			{
				$db->begin();

				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."co_exp (fk_expedition,fk_commande)";
					$sql.= " VALUES (".$obj->rowid.",".$obj->fk_commande.")";
					$resql2=$db->query($sql);

					if (!$resql2)
					{
						$error++;
						dol_print_error($db);
					}
					print ". ";
					$i++;
				}
			}

			if ($error == 0)
			{
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."expedition DROP COLUMN fk_commande";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			dol_print_error($db);
			$db->rollback();
		}
	}
	else
	{
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/*
 * Correspondance des livraisons et des commandes clients dans la table llx_co_liv
 */
function migrate_commande_livraison($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_livraison");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryOrderMatching')."</b><br>\n";

	$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraison","fk_commande");
	$obj = $db->fetch_object($result);
	if ($obj)
	{
		$error = 0;

		$db->begin();

		$sql = "SELECT l.rowid, l.fk_commande";
		$sql.= ", c.ref_client, c.date_livraison";
		$sql.= " FROM ".MAIN_DB_PREFIX."livraison as l, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.rowid = l.fk_commande";
		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."co_liv (fk_livraison,fk_commande)";
					$sql.= " VALUES (".$obj->rowid.",".$obj->fk_commande.")";
					$resql2=$db->query($sql);

					if ($resql2)
					{
						$sqlu = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
						$sqlu.= " ref_client='".$obj->ref_client."'";
						$sqlu.= ", date_livraison='".$obj->date_livraison."'";
						$sqlu.= " WHERE rowid = ".$obj->rowid;
						$resql3=$db->query($sqlu);
						if (!$resql3)
						{
							$error++;
							dol_print_error($db);
						}
					}
					else
					{
						$error++;
						dol_print_error($db);
					}
					print ". ";
					$i++;
				}
			}

			if ($error == 0)
			{
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraison DROP COLUMN fk_commande";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			dol_print_error($db);
			$db->rollback();
		}
	}
	else
	{
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}

/*
 * Migration des détails commandes dans les détails livraisons
 */
function migrate_detail_livraison($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_detail_livraison");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryDetail')."</b><br>\n";

	// This is done if field fk_commande_ligne exists.
	// If not this means migration was already done.
	$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraisondet","fk_commande_ligne");
	$obj = $db->fetch_object($result);
	if ($obj)
	{
		$error = 0;

		$db->begin();

		$sql = "SELECT cd.rowid, cd.fk_product, cd.description, cd.subprice, cd.total_ht";
		$sql.= ", ld.fk_livraison";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd, ".MAIN_DB_PREFIX."livraisondet as ld";
		$sql.= " WHERE ld.fk_commande_ligne = cd.rowid";
		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."livraisondet SET";
					$sql.= " fk_product=".$obj->fk_product;
					$sql.= ",description='".addslashes($obj->description)."'";
					$sql.= ",subprice='".$obj->subprice."'";
					$sql.= ",total_ht='".$obj->total_ht."'";
					$sql.= " WHERE fk_commande_ligne = ".$obj->rowid;
					$resql2=$db->query($sql);

					if ($resql2)
					{
						$sql = "SELECT total_ht";
						$sql.= " FROM ".MAIN_DB_PREFIX."livraison";
						$sql.= " WHERE rowid = ".$obj->fk_livraison;
						$resql3=$db->query($sql);

						if ($resql3)
						{
							$obju = $db->fetch_object($resql3);
							$total_ht = $obju->total_ht + $obj->total_ht;

							$sqlu = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
							$sqlu.= " total_ht='".$total_ht."'";
							$sqlu.= " WHERE rowid=".$obj->fk_livraison;
							$resql4=$db->query($sqlu);
							if (!$resql4)
							{
								$error++;
								dol_print_error($db);
							}
						}
						else
						{
							$error++;
							dol_print_error($db);
						}
					}
					else
					{
						$error++;
						dol_print_error($db);
					}
					print ". ";
					$i++;
				}

			}

			if ($error == 0)
			{
				$db->commit();
				$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraisondet CHANGE fk_commande_ligne fk_origin_line integer";
				print $langs->trans('FieldRenamed')."<br>\n";
				$db->query($sql);
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			dol_print_error($db);
			$db->rollback();
		}
	}
	else
	{
		$result = $db->DDLDescTable(MAIN_DB_PREFIX."livraisondet","fk_origin_line");
		$obj = $db->fetch_object($result);
		if (!$obj)
		{
			$sql = "ALTER TABLE ".MAIN_DB_PREFIX."livraisondet ADD COLUMN fk_origin_line integer after fk_livraison";
			$db->query($sql);
		}
		print $langs->trans('AlreadyDone')."<br>\n";
	}
	print '</td></tr>';
}



/*
 * Migration du champ stock dans produits
 */
function migrate_stocks($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_stocks");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationStockDetail')."</b><br>\n";

	$error = 0;

	$db->begin();

	$sql = "SELECT SUM(reel) as total, fk_product";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
	$sql.= " GROUP BY fk_product";
	$resql = $db->query($sql);
	if ($resql)
	{
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$sql = "UPDATE ".MAIN_DB_PREFIX."product SET";
				$sql.= " stock = '".$obj->total."'";
				$sql.= " WHERE rowid=".$obj->fk_product;

				$resql2=$db->query($sql);
				if ($resql2)
				{

				}
				else
				{
					$error++;
					dol_print_error($db);
				}
				print ". ";
				$i++;
			}

		}

		if ($error == 0)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}



/*
 * Migration of menus (use only 1 table instead of 3)
 */
function migrate_menus($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_menus");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationMenusDetail')."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."menu_constraint"))
	{
		$db->begin();

		$sql = "SELECT m.rowid, mc.action";
		$sql.= " FROM ".MAIN_DB_PREFIX."menu_constraint as mc, ".MAIN_DB_PREFIX."menu_const as md, ".MAIN_DB_PREFIX."menu as m";
		$sql.= " WHERE md.fk_menu = m.rowid AND md.fk_constraint = mc.rowid";
		$sql.= " AND m.enabled = '1'";
		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $db->num_rows($resql);
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
					$sql.= " enabled = '".$obj->action."'";
					$sql.= " WHERE rowid=".$obj->rowid;
					$sql.= " AND enabled = '1'";

					$resql2=$db->query($sql);
					if ($resql2)
					{

					}
					else
					{
						$error++;
						dol_print_error($db);
					}
					print ". ";
					$i++;
				}
			}

			if ($error == 0)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			dol_print_error($db);
			$db->rollback();
		}
	}
	else
	{
		print $langs->trans('AlreadyDone')."<br>\n";
	}

	print '</td></tr>';
}

/*
 * Migration du champ fk_adresse_livraison dans expedition
 */
function migrate_commande_deliveryaddress($db,$langs,$conf)
{
	dolibarr_install_syslog("upgrade2::migrate_commande_deliveryaddress");

	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationDeliveryAddress')."</b><br>\n";

	$error = 0;

	$db->begin();

	$sql = "SELECT c.fk_adresse_livraison, ce.fk_expedition";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."co_exp as ce";
	$sql.= " WHERE c.rowid = ce.fk_commande";
	$sql.= " AND c.fk_adresse_livraison IS NOT NULL";

	$resql = $db->query($sql);
	if ($resql)
	{
		$i = 0;
		$num = $db->num_rows($resql);

		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$sql = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
				$sql.= " fk_adresse_livraison = '".$obj->fk_adresse_livraison."'";
				$sql.= " WHERE rowid=".$obj->fk_expedition;

				$resql2=$db->query($sql);
				if ($resql2)
				{

				}
				else
				{
					$error++;
					dol_print_error($db);
				}
				print ". ";
				$i++;
			}

		}

		if ($error == 0)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
		$db->rollback();
	}

	print '</td></tr>';
}


/* A faire egalement: Modif statut paye et fk_facture des factures payes completement

On recherche facture incorrecte:
select f.rowid, f.total_ttc as t1, sum(pf.amount) as t2 from llx_facture as f, llx_paiement_facture as pf where pf.fk_facture=f.rowid and f.fk_statut in(2,3) and paye=0 and close_code is null group by f.rowid
having  f.total_ttc = sum(pf.amount)

On les corrige:
update llx_facture set paye=1, fk_statut=2 where close_code is null
and rowid in (...)
*/

?>
