<?php
/* Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/install/upgrade2.php
 *	\brief      Upgrade some data
 */

include_once 'inc.php';
if (! file_exists($conffile))
{
    print 'Error: Dolibarr config file was not found. This may means that Dolibarr is not installed yet. Please call the page "/install/index.php" instead of "/install/upgrade.php").';
}
require_once $conffile; if (! isset($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// For backward compatibility
require_once $dolibarr_main_document_root . '/compta/facture/class/facture.class.php';
require_once $dolibarr_main_document_root . '/comm/propal/class/propal.class.php';
require_once $dolibarr_main_document_root . '/contrat/class/contrat.class.php';
require_once $dolibarr_main_document_root . '/commande/class/commande.class.php';
require_once $dolibarr_main_document_root . '/fourn/class/fournisseur.commande.class.php';
require_once $dolibarr_main_document_root . '/core/lib/price.lib.php';
require_once $dolibarr_main_document_root . '/core/class/menubase.class.php';
require_once $dolibarr_main_document_root . '/core/lib/files.lib.php';

$grant_query='';
$etape = 2;
$error = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):'auto';
$langs->setDefaultLang($setuplang);
$versionfrom=GETPOST("versionfrom",'',3)?GETPOST("versionfrom",'',3):(empty($argv[1])?'':$argv[1]);
$versionto=GETPOST("versionto",'',3)?GETPOST("versionto",'',3):(empty($argv[2])?'':$argv[2]);

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



/*
 * View
 */

pHeader('','etape5',GETPOST("action")?GETPOST("action"):'upgrade','versionfrom='.$versionfrom.'&versionto='.$versionto);


if (! GETPOST("action") || preg_match('/upgrade/i',GETPOST('action')))
{
    print '<h3>'.$langs->trans('DataMigration').'</h3>';

    print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';

    // If password is encoded, we decode it
    if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
    {
        require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
        if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
        {
            $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
            $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
            $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
        }
        else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
    }

    // $conf is already instancied inside inc.php
    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->port = $dolibarr_main_db_port;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;

    $db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

    // Create the global $hookmanager object
    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
    $hookmanager=new HookManager($db);

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


    /***************************************************************************************
     *
     * Migration des donnees
     *
     ***************************************************************************************/
    if (! $error)
    {
        // Current version is $conf->global->MAIN_VERSION_LAST_UPGRADE
        // Version to install is DOL_VERSION
        $dolibarrlastupgradeversionarray=preg_split('/[\.-]/',isset($conf->global->MAIN_VERSION_LAST_UPGRADE)?$conf->global->MAIN_VERSION_LAST_UPGRADE:$conf->global->MAIN_VERSION_LAST_INSTALL);

        $db->begin();

        // Chaque action de migration doit renvoyer une ligne sur 4 colonnes avec
        // dans la 1ere colonne, la description de l'action a faire
        // dans la 4eme colonne, le texte 'OK' si fait ou 'AlreadyDone' si rien n'est fait ou 'Error'

        $versiontoarray=explode('.',$versionto);

        $afterversionarray=explode('.','2.0.0');
        $beforeversionarray=explode('.','2.7.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
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

            migrate_price_contrat($db,$langs,$conf);

            migrate_paiementfourn_facturefourn($db,$langs,$conf);


            // Script pour V2.1 -> V2.2
            migrate_paiements_orphelins_1($db,$langs,$conf);

            migrate_paiements_orphelins_2($db,$langs,$conf);

            migrate_links_transfert($db,$langs,$conf);


            // Script pour V2.2 -> V2.4
            migrate_commande_expedition($db,$langs,$conf);

            migrate_commande_livraison($db,$langs,$conf);

            migrate_detail_livraison($db,$langs,$conf);


            // Script pour V2.5 -> V2.6
            migrate_stocks($db,$langs,$conf);


            // Script pour V2.6 -> V2.7
            migrate_menus($db,$langs,$conf);

            migrate_commande_deliveryaddress($db,$langs,$conf);

            migrate_restore_missing_links($db,$langs,$conf);

            migrate_directories($db,$langs,$conf,'/compta','/banque');

            migrate_directories($db,$langs,$conf,'/societe','/mycompany');

            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<2.8) -> V2.8
        $afterversionarray=explode('.','2.7.9');
        $beforeversionarray=explode('.','2.8.9');
        //print $versionto.' '.versioncompare($versiontoarray,$afterversionarray).' '.versioncompare($versiontoarray,$beforeversionarray);
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
            migrate_price_facture($db,$langs,$conf);     // Code of this function works for 2.8+ because need a field tva_tx

            migrate_relationship_tables($db,$langs,$conf,'co_exp','fk_commande','commande','fk_expedition','shipping');

            migrate_relationship_tables($db,$langs,$conf,'pr_exp','fk_propal','propal','fk_expedition','shipping');

            migrate_relationship_tables($db,$langs,$conf,'pr_liv','fk_propal','propal','fk_livraison','delivery');

            migrate_relationship_tables($db,$langs,$conf,'co_liv','fk_commande','commande','fk_livraison','delivery');

            migrate_relationship_tables($db,$langs,$conf,'co_pr','fk_propale','propal','fk_commande','commande');

            migrate_relationship_tables($db,$langs,$conf,'fa_pr','fk_propal','propal','fk_facture','facture');

            migrate_relationship_tables($db,$langs,$conf,'co_fa','fk_commande','commande','fk_facture','facture');

            migrate_project_user_resp($db,$langs,$conf);

            migrate_project_task_actors($db,$langs,$conf);

            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<2.9) -> V2.9
        $afterversionarray=explode('.','2.8.9');
        $beforeversionarray=explode('.','2.9.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
            migrate_project_task_time($db,$langs,$conf);

            migrate_customerorder_shipping($db,$langs,$conf);

            migrate_shipping_delivery($db,$langs,$conf);

            migrate_shipping_delivery2($db,$langs,$conf);

            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<3.0) -> V3.0
        $afterversionarray=explode('.','2.9.9');
        $beforeversionarray=explode('.','3.0.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<3.1) -> V3.1
        $afterversionarray=explode('.','3.0.9');
        $beforeversionarray=explode('.','3.1.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
            migrate_directories($db,$langs,$conf,'/rss','/externalrss');

            migrate_actioncomm_element($db,$langs,$conf);

            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<3.2) -> V3.2
        $afterversionarray=explode('.','3.1.9');
        $beforeversionarray=explode('.','3.2.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
            migrate_price_contrat($db,$langs,$conf);

        	migrate_mode_reglement($db,$langs,$conf);

            // Reload modules
            migrate_reload_modules($db,$langs,$conf);

            // Reload menus
            migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        // Script for VX (X<3.3) -> V3.3
        $afterversionarray=explode('.','3.2.9');
        $beforeversionarray=explode('.','3.3.9');
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
        	migrate_categorie_association($db,$langs,$conf);
        }

		// Script for VX (X<3.4) -> V3.4
		// No specific scripts

        // Tasks to do always and only into last targeted version
        $afterversionarray=explode('.','3.4.9');	// target is after this
        $beforeversionarray=explode('.','3.5.9');	// target is before this
        if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
        {
        	// Reload modules (this must be always and only into last targeted version)
        	migrate_reload_modules($db,$langs,$conf);

        	// Reload menus (this must be always and only into last targeted version)
        	migrate_reload_menu($db,$langs,$conf,$versionto);
        }

        print '<tr><td colspan="4"><br>'.$langs->trans("MigrationFinished").'</td></tr>';

        // On commit dans tous les cas.
        // La procedure etant concue pour pouvoir passer plusieurs fois quelquesoit la situation.
        $db->commit();
        $db->close();

        // Actions for all version (not in database)
        migrate_delete_old_files($db,$langs,$conf);

        migrate_delete_old_dir($db,$langs,$conf);
    }

    print '</table>';
}
else
{
    print '<div class="error">'.$langs->trans('ErrorWrongParameters').'</div>';
    $error++;
}

$ret=0;
if ($error && isset($argv[1])) $ret=1;
dol_syslog("Exit ".$ret);

pFooter($error,$setuplang);

if ($db->connected) $db->close();

// Return code if ran from command line
if ($ret) exit($ret);



/**
 * Reporte liens vers une facture de paiements sur table de jointure (lien n-n paiements factures)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement","fk_facture");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
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
        else
        {
            dol_print_error($db);
        }

        if ($num)
        {
            print $langs->trans('MigrationPaymentsNumberToUpdate', $num)."<br>\n";
            if ($db->begin())
            {
                $res = 0;
                $num=count($row);
                for ($i = 0; $i < $num; $i++)
                {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
                    $sql.= " VALUES (".$row[$i][1].",".$row[$i][0].",".$row[$i][2].")";

                    $res += $db->query($sql);

                    $sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET fk_facture = 0 WHERE rowid = ".$row[$i][0];

                    $res += $db->query($sql);

                    print $langs->trans('MigrationProcessPaymentUpdate', $row[$i][0])."<br>\n";
                }
            }

            if ($res == (2 * count($row)))
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
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements_orphelins_1($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement","fk_facture");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
        // Tous les enregistrements qui sortent de cette requete devrait avoir un pere dans llx_paiement_facture
        $sql = "SELECT distinct p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
        $sql.= " bu2.url_id as socid";
        $sql.= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
        $sql.= " LEFT JOIN llx_paiement_facture as pf ON pf.fk_paiement = p.rowid";
        $sql.= " LEFT JOIN llx_bank_url as bu2 ON (bu.fk_bank=bu2.fk_bank AND bu2.type = 'company')";
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
        else
        {
            dol_print_error($db);
        }

        if (count($row))
        {
            print $langs->trans('OrphelinsPaymentsDetectedByMethod', 1).': '.count($row)."<br>\n";
            $db->begin();

            $res = 0;
            $num=count($row);
            for ($i = 0; $i < $num; $i++)
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
                        $sql.= " VALUES (".$facid.",".$row[$i]['paymentid'].",".$row[$i]['pamount'].")";

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
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiements_orphelins_2($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationPaymentsUpdate')."</b><br>\n";

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."paiement","fk_facture");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
        // Tous les enregistrements qui sortent de cette requete devrait avoir un pere dans llx_paiement_facture
        $sql = "SELECT distinct p.rowid, p.datec, p.amount as pamount, bu.fk_bank, b.amount as bamount,";
        $sql.= " bu2.url_id as socid";
        $sql.= " FROM (".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."bank_url as bu, ".MAIN_DB_PREFIX."bank as b)";
        $sql.= " LEFT JOIN llx_paiement_facture as pf ON pf.fk_paiement = p.rowid";
        $sql.= " LEFT JOIN llx_bank_url as bu2 ON (bu.fk_bank = bu2.fk_bank AND bu2.type = 'company')";
        $sql.= " WHERE pf.rowid IS NULL AND (p.fk_bank = bu.fk_bank AND bu.type = 'payment') AND bu.fk_bank = b.rowid";
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
        else
        {
            dol_print_error($db);
        }

        $nberr=0;

        $num=count($row);
        if ($num)
        {
            print $langs->trans('OrphelinsPaymentsDetectedByMethod', 2).': '.count($row)."<br>\n";
            $db->begin();

            $res = 0;
            for ($i = 0; $i < $num; $i++)
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
                        $sql.= " VALUES (".$facid.",".$row[$i]['paymentid'].",".$row[$i]['pamount'].")";
                        $res += $db->query($sql);

                        print $langs->trans('MigrationProcessPaymentUpdate', 'facid='.$facid.'-paymentid='.$row[$i]['paymentid'].'-amount='.$row[$i]['pamount'])."<br>\n";
                    }
                }
                else
                {
                    print 'ERROR';
                    $nberr++;
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

        // Delete obsolete fields fk_facture
        $db->begin();

        $sql = "ALTER TABLE ".MAIN_DB_PREFIX."paiement DROP COLUMN fk_facture";
        $db->query($sql);

        if (!$nberr)
        {
            $db->commit();
        }
        else
        {
            print 'ERROR';
            $db->rollback();
        }
    }
    else
    {
        print $langs->trans('MigrationPaymentsNothingUpdatable')."<br>\n";
    }

    print '</td></tr>';
}


/**
 * Mise a jour des contrats (gestion du contrat + detail de contrat)
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
                $sql.= "'".$db->escape($obj->label)."', null,";
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
        else
        {
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

/**
 * Function to migrate links into llx_bank_url
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
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

/**
 * Mise a jour des date de contrats non renseignees
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

/**
 * Mise a jour des dates de creation de contrat
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
    if ($db->affected_rows($resql) > 0)
    print $langs->trans('MigrationContractsIncoherentCreationDateUpdateSuccess')."<br>\n";
    else
    print $langs->trans('MigrationContractsIncoherentCreationDateNothingToUpdate')."<br>\n";

    print '</td></tr>';
}

/**
 * Reouverture des contrats qui ont au moins une ligne non fermee
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
    if ($db->affected_rows($resql) > 0) {
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
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_paiementfourn_facturefourn($db,$langs,$conf)
{
    global $bc;

    print '<tr><td colspan="4">';
    print '<br>';
    print '<b>'.$langs->trans('SuppliersInvoices')."</b><br>\n";
    print '</td></tr>';

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."paiementfourn","fk_facture_fourn");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
        $error=0;
        $nb=0;

        $select_sql = 'SELECT rowid, fk_facture_fourn, amount';
        $select_sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn';
        $select_sql.= ' WHERE fk_facture_fourn IS NOT NULL';

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

                // Verifier si la ligne est deja dans la nouvelle table. On ne veut pas inserer de doublons.
                $check_sql = 'SELECT fk_paiementfourn, fk_facturefourn';
                $check_sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
                $check_sql.= ' WHERE fk_paiementfourn = '.$select_obj->rowid.' AND fk_facturefourn = '.$select_obj->fk_facture_fourn;
                $check_resql = $db->query($check_sql);
                if ($check_resql)
                {
                    $check_num = $db->num_rows($check_resql);
                    if ($check_num == 0)
                    {
                        $db->begin();

                        if ($nb == 0)
                        {
                            print '<tr><td colspan="4" class="nowrap"><b>'.$langs->trans('SuppliersInvoices').'</b></td></tr>';
                            print '<tr><td>fk_paiementfourn</td><td>fk_facturefourn</td><td>'.$langs->trans('Amount').'</td><td>&nbsp;</td></tr>';
                        }

                        print '<tr '.$bc[$var].'>';
                        print '<td>'.$select_obj->rowid.'</td><td>'.$select_obj->fk_facture_fourn.'</td><td>'.$select_obj->amount.'</td>';

                        $insert_sql = 'INSERT INTO '.MAIN_DB_PREFIX.'paiementfourn_facturefourn SET ';
                        $insert_sql.= ' fk_paiementfourn = \''.$select_obj->rowid.'\',';
                        $insert_sql.= ' fk_facturefourn  = \''.$select_obj->fk_facture_fourn.'\',';
                        $insert_sql.= ' amount           = \''.$select_obj->amount.'\'';
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

        if (!$error)
        {
            if (!$nb)
            {
                print '<tr><td>'.$langs->trans("AlreadyDone").'</td></tr>';
            }
            $db->commit();

            $sql = "ALTER TABLE ".MAIN_DB_PREFIX."paiementfourn DROP COLUMN fk_facture_fourn";
            $db->query($sql);
        }
        else
        {
            print '<tr><td>'.$langs->trans("Error").'</td></tr>';
            $db->rollback();
        }
    }
    else
    {
        print '<tr><td>'.$langs->trans("AlreadyDone").'</td></tr>';
    }
}

/**
 * Mise a jour des totaux lignes de facture
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_price_facture($db,$langs,$conf)
{
    $err=0;

    $db->begin();

    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationInvoice')."</b><br>\n";

    // Liste des lignes facture non a jour
    $sql = "SELECT fd.rowid, fd.qty, fd.subprice, fd.remise_percent, fd.tva_tx as tva_taux, fd.total_ttc, fd.info_bits,";
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

                $result=calcul_price_total($qty,$pu,$remise_percent,$txtva, 0, 0,$remise_percent_global,'HT',$info_bits,0);
                $total_ht  = $result[0];
                $total_tva = $result[1];
                $total_ttc = $result[2];

                $facligne->total_ht  = $total_ht;
                $facligne->total_tva = $total_tva;
                $facligne->total_ttc = $total_ttc;

                dolibarr_install_syslog("upgrade2: Line $rowid: facid=$obj->facid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent remise_global=$remise_percent_global -> $total_ht, $total_tva, $total_ttc");
                print ". ";
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

/**
 * Mise a jour des totaux lignes de propal
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

                $result=calcul_price_total($qty,$pu,$remise_percent,$txtva,0,0,$remise_percent_global,'HT',$info_bits,0);
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
                 }
                 }
                 else
                 {
                 print "Error #3";
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

        $db->rollback();
    }

    print '<br>';

    print '</td></tr>';
}

/**
 * Mise a jour des totaux lignes de propal
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
                $info_bits = $obj->info_bits;

                // On met a jour les 3 nouveaux champs
                $contratligne= new ContratLigne($db);
                //$contratligne->fetch($rowid); Non requis car le update_total ne met a jour que chp redefinis
                $contratligne->rowid=$rowid;

                $result=calcul_price_total($qty,$pu,$remise_percent,$txtva,0,0,0,'HT',$info_bits,0);
                $total_ht  = $result[0];
                $total_tva = $result[1];
                $total_ttc = $result[2];

                $contratligne->total_ht  = $total_ht;
                $contratligne->total_tva = $total_tva;
                $contratligne->total_ttc = $total_ttc;

                dolibarr_install_syslog("upgrade2: Line $rowid: contratdetid=$obj->rowid pu=$pu qty=$qty tva_taux=$txtva remise_percent=$remise_percent -> $total_ht, $total_tva, $total_ttc");
                print ". ";
                $contratligne->update_total($rowid);

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

        $db->rollback();
    }

    print '<br>';

    print '</td></tr>';
}

/**
 * Mise a jour des totaux lignes de commande
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
                $commandeligne= new OrderLine($db);
                $commandeligne->fetch($rowid);

                $result=calcul_price_total($qty,$pu,$remise_percent,$txtva,0,0,$remise_percent_global,'HT',$info_bits,0);
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
                 }
                 }
                 else
                 {
                 print "Error #3";
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

        $db->rollback();
    }

    print '<br>';

    print '</td></tr>';
}

/**
 * Mise a jour des totaux lignes de commande fournisseur
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

                $result=calcul_price_total($qty,$pu,$remise_percent,$txtva,0,0,$remise_percent_global,'HT',$info_bits,0);
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
                 }
                 }
                 else
                 {
                 print "Error #3";
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

        $db->rollback();
    }

    print '<br>';

    print '</td></tr>';
}

/**
 * Mise a jour des modeles selectionnes
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_modeles($db,$langs,$conf)
{
    //print '<br>';
    //print '<b>'.$langs->trans('UpdateModelsTable')."</b><br>\n";

    dolibarr_install_syslog("upgrade2::migrate_modeles");

    if (! empty($conf->facture->enabled))
    {
        include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
        $modellist=ModelePDFFactures::liste_modeles($db);
        if (count($modellist)==0)
        {
            // Aucun model par defaut.
            $sql=" insert into llx_document_model(nom,type) values('crabe','invoice')";
            $resql = $db->query($sql);
            if (! $resql) dol_print_error($db);
        }
    }

    if (! empty($conf->commande->enabled))
    {
        include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
        $modellist=ModelePDFCommandes::liste_modeles($db);
        if (count($modellist)==0)
        {
            // Aucun model par defaut.
            $sql=" insert into llx_document_model(nom,type) values('einstein','order')";
            $resql = $db->query($sql);
            if (! $resql) dol_print_error($db);
        }
    }

    if (! empty($conf->expedition->enabled))
    {
        include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
        $modellist=ModelePDFExpedition::liste_modeles($db);
        if (count($modellist)==0)
        {
            // Aucun model par defaut.
            $sql=" insert into llx_document_model(nom,type) values('rouget','shipping')";
            $resql = $db->query($sql);
            if (! $resql) dol_print_error($db);
        }
    }

    //print $langs->trans("AlreadyDone");
}


/**
 * Correspondance des expeditions et des commandes clients dans la table llx_co_exp
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

/**
 * Correspondance des livraisons et des commandes clients dans la table llx_co_liv
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

/**
 * Migration des details commandes dans les details livraisons
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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
                    $sql.= ",description='".$db->escape($obj->description)."'";
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

/**
 * Migration du champ stock dans produits
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

/**
 * Migration of menus (use only 1 table instead of 3)
 * 2.6 -> 2.7
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
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

/**
 * Migration du champ fk_adresse_livraison dans expedition
 * 2.6 -> 2.7
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_commande_deliveryaddress($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_commande_deliveryaddress");

    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationDeliveryAddress')."</b><br>\n";

    $error = 0;

    if ($db->DDLInfoTable(MAIN_DB_PREFIX."co_exp"))
    {
        $db->begin();

        $sql = "SELECT c.fk_adresse_livraison, ce.fk_expedition";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
        $sql.= ", ".MAIN_DB_PREFIX."co_exp as ce";
        $sql.= " WHERE c.rowid = ce.fk_commande";
        $sql.= " AND c.fk_adresse_livraison IS NOT NULL AND c.fk_adresse_livraison != 0";

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
                    if (!$resql2)
                    {
                        $error++;
                        dol_print_error($db);
                    }
                    print ". ";
                    $i++;
                }
            }
            else
            {
                print $langs->trans('AlreadyDone')."<br>\n";
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

/**
 * Migration du champ fk_remise_except dans llx_facturedet doit correspondre a
 * lien dans llx_societe_remise_except vers llx_facturedet
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_restore_missing_links($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_restore_missing_links");

    if (($db->type == 'mysql' || $db->type == 'mysqli'))
    {
        if (versioncompare($db->getVersionArray(),array(4,0)) < 0)
        {
            dolibarr_install_syslog("upgrade2::migrate_restore_missing_links Version of database too old to make this migrate action");
            return 0;
        }
    }
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationFixData')."</b> (1)<br>\n";

    $error = 0;


    // Restore missing link for this cross foreign key (link 1 <=> 1). Direction 1.
    $table1='facturedet'; $field1='fk_remise_except';
    $table2='societe_remise_except'; $field2='fk_facture_line';

    $db->begin();

    $sql = "SELECT t1.rowid, t1.".$field1." as field";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table1." as t1";
    $sql.= " WHERE t1.".$field1." IS NOT NULL AND t1.".$field1." NOT IN";
    $sql.= " (SELECT t2.rowid FROM ".MAIN_DB_PREFIX.$table2." as t2";
    $sql.= " WHERE t1.rowid = t2.".$field2.")";

    dolibarr_install_syslog("upgrade2:migrate_restore_missing_links DIRECTION 1 sql=".$sql);
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

                print 'Line '.$obj->rowid.' in '.$table1.' is linked to record '.$obj->field.' in '.$table2.' that has no link to '.$table1.'. We fix this.<br>';
                $sql = "UPDATE ".MAIN_DB_PREFIX.$table2." SET";
                $sql.= " ".$field2." = '".$obj->rowid."'";
                $sql.= " WHERE rowid=".$obj->field;

                $resql2=$db->query($sql);
                if (! $resql2)
                {
                    $error++;
                    dol_print_error($db);
                }
                //print ". ";
                $i++;
            }

        }
        else print $langs->trans('AlreadyDone')."<br>\n";

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


    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationFixData')."</b> (2)<br>\n";

    // Restore missing link for this cross foreign key (link 1 <=> 1). Direction 2.
    $table2='facturedet'; $field2='fk_remise_except';
    $table1='societe_remise_except'; $field1='fk_facture_line';

    $db->begin();

    $sql = "SELECT t1.rowid, t1.".$field1." as field";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table1." as t1";
    $sql.= " WHERE t1.".$field1." IS NOT NULL AND t1.".$field1." NOT IN";
    $sql.= " (SELECT t2.rowid FROM ".MAIN_DB_PREFIX.$table2." as t2";
    $sql.= " WHERE t1.rowid = t2.".$field2.")";

    dolibarr_install_syslog("upgrade2:migrate_restore_missing_links DIRECTION 2 sql=".$sql);
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

                print 'Line '.$obj->rowid.' in '.$table1.' is linked to record '.$obj->field.' in '.$table2.' that has no link to '.$table1.'. We fix this.<br>';
                $sql = "UPDATE ".MAIN_DB_PREFIX.$table2." SET";
                $sql.= " ".$field2." = '".$obj->rowid."'";
                $sql.= " WHERE rowid=".$obj->field;

                $resql2=$db->query($sql);
                if (! $resql2)
                {
                    $error++;
                    dol_print_error($db);
                }
                //print ". ";
                $i++;
            }

        }
        else
        {
            print $langs->trans('AlreadyDone')."<br>\n";
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

/**
 * Migration du champ fk_user_resp de llx_projet vers llx_element_contact
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_project_user_resp($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_project_user_resp");

    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationProjectUserResp')."</b><br>\n";

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."projet","fk_user_resp");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
        $error = 0;

        $db->begin();

        $sql = "SELECT rowid, fk_user_resp FROM ".MAIN_DB_PREFIX."projet";
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

                    $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."element_contact (";
                    $sql2.= "datecreate";
                    $sql2.= ", statut";
                    $sql2.= ", element_id";
                    $sql2.= ", fk_c_type_contact";
                    $sql2.= ", fk_socpeople";
                    $sql2.= ") VALUES (";
                    $sql2.= $db->idate(dol_now());
                    $sql2.= ", '4'";
                    $sql2.= ", ".$obj->rowid;
                    $sql2.= ", '160'";
                    $sql2.= ", ".$obj->fk_user_resp;
                    $sql2.= ")";

                    if ($obj->fk_user_resp > 0)
                    {
                        $resql2=$db->query($sql2);
                        if (!$resql2)
                        {
                            $error++;
                            dol_print_error($db);
                        }
                    }
                    print ". ";

                    $i++;
                }
            }

            if ($error == 0)
            {
                $sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."projet DROP COLUMN fk_user_resp";
                if ($db->query($sqlDrop))
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

/**
 * Migration de la table llx_projet_task_actors vers llx_element_contact
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_project_task_actors($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_project_task_actors");

    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationProjectTaskActors')."</b><br>\n";

    if ($db->DDLInfoTable(MAIN_DB_PREFIX."projet_task_actors"))
    {
        $error = 0;

        $db->begin();

        $sql = "SELECT fk_projet_task, fk_user FROM ".MAIN_DB_PREFIX."projet_task_actors";
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

                    $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."element_contact (";
                    $sql2.= "datecreate";
                    $sql2.= ", statut";
                    $sql2.= ", element_id";
                    $sql2.= ", fk_c_type_contact";
                    $sql2.= ", fk_socpeople";
                    $sql2.= ") VALUES (";
                    $sql2.= $db->idate(dol_now());
                    $sql2.= ", '4'";
                    $sql2.= ", ".$obj->fk_projet_task;
                    $sql2.= ", '180'";
                    $sql2.= ", ".$obj->fk_user;
                    $sql2.= ")";

                    $resql2=$db->query($sql2);

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
                $sqlDrop = "DROP TABLE ".MAIN_DB_PREFIX."projet_task_actors";
                if ($db->query($sqlDrop))
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

/**
 * Migration des tables de relation
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @param	string		$table			Table name
 * @param	int			$fk_source		Id of element source
 * @param	type		$sourcetype		Type of element source
 * @param	int			$fk_target		Id of element target
 * @param	type		$targettype		Type of element target
 * @return	void
 */
function migrate_relationship_tables($db,$langs,$conf,$table,$fk_source,$sourcetype,$fk_target,$targettype)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationRelationshipTables',MAIN_DB_PREFIX.$table)."</b><br>\n";

    $error = 0;

    if ($db->DDLInfoTable(MAIN_DB_PREFIX.$table))
    {
        dolibarr_install_syslog("upgrade2::migrate_relationship_tables table = ".MAIN_DB_PREFIX.$table);

        $db->begin();

        $sqlSelect = "SELECT ".$fk_source.", ".$fk_target;
        $sqlSelect.= " FROM ".MAIN_DB_PREFIX.$table;

        $resql = $db->query($sqlSelect);
        if ($resql)
        {
            $i = 0;
            $num = $db->num_rows($resql);

            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);

                    $sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
                    $sqlInsert.= "fk_source";
                    $sqlInsert.= ", sourcetype";
                    $sqlInsert.= ", fk_target";
                    $sqlInsert.= ", targettype";
                    $sqlInsert.= ") VALUES (";
                    $sqlInsert.= $obj->$fk_source;
                    $sqlInsert.= ", '".$sourcetype."'";
                    $sqlInsert.= ", ".$obj->$fk_target;
                    $sqlInsert.= ", '".$targettype."'";
                    $sqlInsert.= ")";

                    $result=$db->query($sqlInsert);
                    if (! $result)
                    {
                        $error++;
                        dol_print_error($db);
                    }
                    print ". ";
                    $i++;
                }
            }
            else
            {
                print $langs->trans('AlreadyDone')."<br>\n";
            }

            if ($error == 0)
            {
                $sqlDrop = "DROP TABLE ".MAIN_DB_PREFIX.$table;
                if ($db->query($sqlDrop))
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

/**
 * Migrate duration in seconds
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_project_task_time($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_project_task_time");

    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationProjectTaskTime')."</b><br>\n";

    $error = 0;

    $db->begin();

    $sql = "SELECT rowid, fk_task, task_duration";
    $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time";
    $resql = $db->query($sql);
    if ($resql)
    {
        $i = 0;
        $num = $db->num_rows($resql);

        if ($num)
        {
            $totaltime = array();
            $oldtime = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);

                if ($obj->task_duration > 0)
                {
                    // convert to second
                    // only for int time and float time ex: 1,75 for 1h45
                    list($hour,$min) = explode('.',$obj->task_duration);
                    $hour = $hour*60*60;
                    $min = ($min/100)*60*60;
                    $newtime = $hour+$min;

                    $sql2 = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
                    $sql2.= " task_duration = ".$newtime;
                    $sql2.= " WHERE rowid = ".$obj->rowid;

                    $resql2=$db->query($sql2);
                    if (!$resql2)
                    {
                        $error++;
                        dol_print_error($db);
                    }
                    print ". ";
                    $oldtime++;
                    if (! empty($totaltime[$obj->fk_task])) $totaltime[$obj->fk_task] += $newtime;
                    else $totaltime[$obj->fk_task] = $newtime;
                }
                else
                {
                    if (! empty($totaltime[$obj->fk_task])) $totaltime[$obj->fk_task] += $obj->task_duration;
                    else $totaltime[$obj->fk_task] = $obj->task_duration;
                }

                $i++;
            }

            if ($error == 0)
            {
                if ($oldtime > 0)
                {
                    foreach($totaltime as $taskid => $total_duration)
                    {
                        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
                        $sql.= " duration_effective = ".$total_duration;
                        $sql.= " WHERE rowid = ".$taskid;

                        $resql=$db->query($sql);
                        if (!$resql)
                        {
                            $error++;
                            dol_print_error($db);
                        }
                    }
                }
                else
                {
                    print $langs->trans('AlreadyDone')."<br>\n";
                }
            }
            else
            {
                dol_print_error($db);
            }
        }
        else
        {
            print $langs->trans('AlreadyDone')."<br>\n";
        }
    }
    else
    {
        dol_print_error($db);
    }

    if ($error == 0)
    {
        $db->commit();
    }
    else
    {
        $db->rollback();
    }

    print '</td></tr>';
}

/**
 * Migrate order ref_customer and date_delivery fields to llx_expedition
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_customerorder_shipping($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationCustomerOrderShipping')."</b><br>\n";

    $error = 0;

    $result1 = $db->DDLDescTable(MAIN_DB_PREFIX."expedition","ref_customer");
    $result2 = $db->DDLDescTable(MAIN_DB_PREFIX."expedition","date_delivery");
    $obj1 = $db->fetch_object($result1);
    $obj2 = $db->fetch_object($result2);
    if (!$obj1 && !$obj2)
    {
        dolibarr_install_syslog("upgrade2::migrate_customerorder_shipping");

        $db->begin();

        $sqlAdd1 = "ALTER TABLE ".MAIN_DB_PREFIX."expedition ADD COLUMN ref_customer varchar(30) AFTER entity";
        $sqlAdd2 = "ALTER TABLE ".MAIN_DB_PREFIX."expedition ADD COLUMN date_delivery date DEFAULT NULL AFTER date_expedition";

        if ($db->query($sqlAdd1) && $db->query($sqlAdd2))
        {
            $sqlSelect = "SELECT e.rowid as shipping_id, c.ref_client, c.date_livraison";
            $sqlSelect.= " FROM ".MAIN_DB_PREFIX."expedition as e";
            $sqlSelect.= ", ".MAIN_DB_PREFIX."element_element as el";
            $sqlSelect.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON c.rowid = el.fk_source AND el.sourcetype = 'commande'";
            $sqlSelect.= " WHERE e.rowid = el.fk_target";
            $sqlSelect.= " AND el.targettype = 'shipping'";

            $resql = $db->query($sqlSelect);
            if ($resql)
            {
                $i = 0;
                $num = $db->num_rows($resql);

                if ($num)
                {
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);

                        $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."expedition SET";
                        $sqlUpdate.= " ref_customer = '".$obj->ref_client."'";
                        $sqlUpdate.= ", date_delivery = '".($obj->date_livraison?$obj->date_livraison:'null')."'";
                        $sqlUpdate.= " WHERE rowid = ".$obj->shipping_id;

                        $result=$db->query($sqlUpdate);
                        if (! $result)
                        {
                            $error++;
                            dol_print_error($db);
                        }
                        print ". ";
                        $i++;
                    }
                }
                else
                {
                    print $langs->trans('AlreadyDone')."<br>\n";
                }

                if ($error == 0)
                {
                    $db->commit();
                }
                else
                {
                    dol_print_error($db);
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

/**
 * Migrate link stored into fk_expedition into llx_element_element
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_shipping_delivery($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationShippingDelivery')."</b><br>\n";

    $error = 0;

    $result = $db->DDLDescTable(MAIN_DB_PREFIX."livraison","fk_expedition");
    $obj = $db->fetch_object($result);
    if ($obj)
    {
        dolibarr_install_syslog("upgrade2::migrate_shipping_delivery");

        $db->begin();

        $sqlSelect = "SELECT rowid, fk_expedition";
        $sqlSelect.= " FROM ".MAIN_DB_PREFIX."livraison";
        $sqlSelect.= " WHERE fk_expedition is not null";

        $resql = $db->query($sqlSelect);
        if ($resql)
        {
            $i = 0;
            $num = $db->num_rows($resql);

            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);

                    $sqlInsert = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
                    $sqlInsert.= "fk_source";
                    $sqlInsert.= ", sourcetype";
                    $sqlInsert.= ", fk_target";
                    $sqlInsert.= ", targettype";
                    $sqlInsert.= ") VALUES (";
                    $sqlInsert.= $obj->fk_expedition;
                    $sqlInsert.= ", 'shipping'";
                    $sqlInsert.= ", ".$obj->rowid;
                    $sqlInsert.= ", 'delivery'";
                    $sqlInsert.= ")";

                    $result=$db->query($sqlInsert);
                    if ($result)
                    {
                        $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."livraison SET fk_expedition = NULL";
                        $sqlUpdate.= " WHERE rowid = ".$obj->rowid;

                        $result=$db->query($sqlUpdate);
                        if (! $result)
                        {
                            $error++;
                            dol_print_error($db);
                        }
                        print ". ";
                    }
                    else
                    {
                        $error++;
                        dol_print_error($db);
                    }
                    $i++;
                }
            }
            else
            {
                print $langs->trans('AlreadyDone')."<br>\n";
            }

            if ($error == 0)
            {
                $sqlDelete = "DELETE FROM ".MAIN_DB_PREFIX."element_element WHERE sourcetype = 'commande' AND targettype = 'delivery'";
                $db->query($sqlDelete);

                $db->commit();

                // DDL commands must not be inside a transaction
                $sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."livraison DROP COLUMN fk_expedition";
                $db->query($sqlDrop);
            }
            else
            {
                dol_print_error($db);
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

/**
 * We try to complete field ref_customer and date_delivery that are empty into llx_livraison.
 * We set them with value from llx_expedition.
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_shipping_delivery2($db,$langs,$conf)
{
    print '<tr><td colspan="4">';

    print '<br>';
    print '<b>'.$langs->trans('MigrationShippingDelivery2')."</b><br>\n";

    $error = 0;

    dolibarr_install_syslog("upgrade2::migrate_shipping_delivery2");

    $db->begin();

    $sqlSelect = "SELECT l.rowid as delivery_id, e.ref_customer, e.date_delivery";
    $sqlSelect.= " FROM ".MAIN_DB_PREFIX."livraison as l,";
    $sqlSelect.= " ".MAIN_DB_PREFIX."element_element as el,";
    $sqlSelect.= " ".MAIN_DB_PREFIX."expedition as e";
    $sqlSelect.= " WHERE l.rowid = el.fk_target";
    $sqlSelect.= " AND el.targettype = 'delivery'";
    $sqlSelect.= " AND e.rowid = el.fk_source AND el.sourcetype = 'shipping'";
    $sqlSelect.= " AND (e.ref_customer IS NOT NULL OR e.date_delivery IS NOT NULL)";   // Useless to process this record if both are null
    // Add condition to know if we never migrate this record
    $sqlSelect.= " AND (l.ref_customer IS NULL".($db->type!='pgsql'?" or l.ref_customer = ''":"").")";
    $sqlSelect.= " AND (l.date_delivery IS NULL".($db->type!='pgsql'?" or l.date_delivery = ''":"").")";

    $resql = $db->query($sqlSelect);
    if ($resql)
    {
        $i = 0;
        $num = $db->num_rows($resql);

        if ($num)
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);

                $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."livraison SET";
                $sqlUpdate.= " ref_customer = '".$obj->ref_customer."',";
                $sqlUpdate.= " date_delivery = ".($obj->date_delivery?"'".$obj->date_delivery."'":'null');
                $sqlUpdate.= " WHERE rowid = ".$obj->delivery_id;

                $result=$db->query($sqlUpdate);
                if (! $result)
                {
                    $error++;
                    dol_print_error($db);
                }
                print ". ";
                $i++;
            }
        }
        else
        {
            print $langs->trans('AlreadyDone')."<br>\n";
        }

        if ($error == 0)
        {
            $db->commit();
        }
        else
        {
            dol_print_error($db);
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

/**
 * Migrate link stored into fk_xxxx into fk_element and elementtype
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_actioncomm_element($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationActioncommElement')."</b><br>\n";

	$elements = array(
		'propal' => 'propalrowid',
		'order' => 'fk_commande',
		'invoice' => 'fk_facture',
		'contract' => 'fk_contract',
		'order_supplier' => 'fk_supplier_order',
		'invoice_supplier' => 'fk_supplier_invoice'
	);

	foreach($elements as $type => $field)
	{
		$result = $db->DDLDescTable(MAIN_DB_PREFIX."actioncomm",$field);
		$obj = $db->fetch_object($result);
		if ($obj)
		{
			dolibarr_install_syslog("upgrade2::migrate_actioncomm_element field=".$field);

			$db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm SET ";
			$sql.= "fk_element = ".$field.", elementtype = '".$type."'";
			$sql.= " WHERE ".$field." IS NOT NULL";
			$sql.= " AND fk_element IS NULL";
			$sql.= " AND elementtype IS NULL";

			$resql = $db->query($sql);
			if ($resql)
			{
				$db->commit();

				// DDL commands must not be inside a transaction
				// We will drop at next version because a migrate should be runnable several times if it fails.
				//$sqlDrop = "ALTER TABLE ".MAIN_DB_PREFIX."actioncomm DROP COLUMN ".$field;
				//$db->query($sqlDrop);
				//print ". ";
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
	}

	print '</td></tr>';
}

/**
 * Migrate link stored into fk_mode_reglement
 *
 * @param	DoliDB		$db		Database handler
 * @param	Translate	$langs	Object langs
 * @param	Conf		$conf	Object conf
 * @return	void
 */
function migrate_mode_reglement($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationPaymentMode')."</b><br>\n";

	$elements = array(
		'old_id' => array(5,8,9,10,11),
		'new_id' => array(50,51,52,53,54),
		'code' => array('VAD','TRA','LCR','FAC','PRO'),
		'tables' => array('commande_fournisseur','commande','facture_rec','facture','propal')
	);
	$count=0;

	foreach($elements['old_id'] as $key => $old_id)
	{
		$error=0;

		dolibarr_install_syslog("upgrade2::migrate_mode_reglement code=".$elements['code'][$key]);

		$sqlSelect = "SELECT id";
		$sqlSelect.= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sqlSelect.= " WHERE id = ".$old_id;
		$sqlSelect.= " AND code = '".$elements['code'][$key]."'";

		$resql = $db->query($sqlSelect);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			if ($num)
			{
				$count++;

				$db->begin();

				$sqla = "UPDATE ".MAIN_DB_PREFIX."paiement SET ";
				$sqla.= "fk_paiement = ".$elements['new_id'][$key];
				$sqla.= " WHERE fk_paiement = ".$old_id;
				$sqla.= " AND fk_paiement IN (SELECT id FROM ".MAIN_DB_PREFIX."c_paiement WHERE id = ".$old_id." AND code = '".$elements['code'][$key]."')";
				$resqla = $db->query($sqla);

				$sql = "UPDATE ".MAIN_DB_PREFIX."c_paiement SET ";
				$sql.= "id = ".$elements['new_id'][$key];
				$sql.= " WHERE id = ".$old_id;
				$sql.= " AND code = '".$elements['code'][$key]."'";
				$resql = $db->query($sql);

				if ($resqla && $resql)
				{
					foreach($elements['tables'] as $table)		// FIXME We must not update tables if oldid is not renamed
					{
						$sql = "UPDATE ".MAIN_DB_PREFIX.$table." SET ";
						$sql.= "fk_mode_reglement = ".$elements['new_id'][$key];
						$sql.= " WHERE fk_mode_reglement = ".$old_id;

						$resql = $db->query($sql);
						if (! $resql)
						{
							dol_print_error($db);
							$error++;
						}
						print ". ";
					}

					if (! $error)
					{
						$db->commit();
					}
					else
					{
						dol_print_error($db);
						$db->rollback();
					}
				}
				else
				{
					dol_print_error($db);
					$db->rollback();
				}
			}
		}
	}

	if ($count == 0) print $langs->trans('AlreadyDone')."<br>\n";


	print '</td></tr>';
}

/**
 * Migrate categorie association
 *
 * @param	DoliDB		$db				Database handler
 * @param	Translate	$langs			Object langs
 * @param	Conf		$conf			Object conf
 * @return	void
 */
function migrate_categorie_association($db,$langs,$conf)
{
	print '<tr><td colspan="4">';

	print '<br>';
	print '<b>'.$langs->trans('MigrationCategorieAssociation')."</b><br>\n";

	$error = 0;

	if ($db->DDLInfoTable(MAIN_DB_PREFIX."categorie_association"))
	{
		dolibarr_install_syslog("upgrade2::migrate_categorie_association");

		$db->begin();

		$sqlSelect = "SELECT fk_categorie_mere, fk_categorie_fille";
		$sqlSelect.= " FROM ".MAIN_DB_PREFIX."categorie_association";

		$resql = $db->query($sqlSelect);
		if ($resql)
		{
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."categorie SET ";
					$sqlUpdate.= "fk_parent = ".$obj->fk_categorie_mere;
					$sqlUpdate.= " WHERE rowid = ".$obj->fk_categorie_fille;

					$result=$db->query($sqlUpdate);
					if (! $result)
					{
						$error++;
						dol_print_error($db);
					}
					print ". ";
					$i++;
				}
			}
			else
			{
				print $langs->trans('AlreadyDone')."<br>\n";
			}

			if (! $error)
			{
				// TODO DROP table in the next release
				/*
				$sqlDrop = "DROP TABLE ".MAIN_DB_PREFIX."categorie_association";
				if ($db->query($sqlDrop))
				{
					$db->commit();
				}
				else
				{
					$db->rollback();
				}
				*/

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

/**
 * Migration directory
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @param	string		$oldname	Old name
 * @param	string		$newname	New name
 * @return	void
 */
function migrate_directories($db,$langs,$conf,$oldname,$newname)
{
    dolibarr_install_syslog("upgrade2::migrate_directories");

    if (is_dir(DOL_DATA_ROOT.$oldname) && ! file_exists(DOL_DATA_ROOT.$newname))
    {
        dolibarr_install_syslog("upgrade2::migrate_directories move ".DOL_DATA_ROOT.$oldname.' into '.DOL_DATA_ROOT.$newname);
        @rename(DOL_DATA_ROOT.$oldname,DOL_DATA_ROOT.$newname);
    }
}


/**
 * Delete deprecated files
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	void
 */
function migrate_delete_old_files($db,$langs,$conf)
{
    $result=true;

    dolibarr_install_syslog("upgrade2::migrate_delete_old_files");

    // List of files to delete
    $filetodeletearray=array(
    DOL_DOCUMENT_ROOT.'/core/triggers/interface_demo.class.php',
    DOL_DOCUMENT_ROOT.'/core/menus/barre_left/default.php',
    DOL_DOCUMENT_ROOT.'/core/menus/barre_top/default.php',
    DOL_DOCUMENT_ROOT.'/core/modules/modComptabiliteExpert.class.php',
    DOL_DOCUMENT_ROOT.'/core/modules/modCommercial.class.php',
    DOL_DOCUMENT_ROOT.'/core/modules/modProduit.class.php',
    DOL_DOCUMENT_ROOT.'/phenix/inc/triggers/interface_modPhenix_Phenixsynchro.class.php',
    DOL_DOCUMENT_ROOT.'/webcalendar/inc/triggers/interface_modWebcalendar_webcalsynchro.class.php',
    DOL_DOCUMENT_ROOT.'/core/triggers/interface_modWebcalendar_Webcalsynchro.class.php',
    DOL_DOCUMENT_ROOT.'/core/triggers/interface_modCommande_Ecotax.class.php',
    DOL_DOCUMENT_ROOT.'/core/triggers/interface_modCommande_fraisport.class.php',
    DOL_DOCUMENT_ROOT.'/core/triggers/interface_modPropale_PropalWorkflow.class.php',
    DOL_DOCUMENT_ROOT.'/core/menus/smartphone/iphone.lib.php',
    DOL_DOCUMENT_ROOT.'/core/menus/smartphone/iphone_backoffice.php',
    DOL_DOCUMENT_ROOT.'/core/menus/smartphone/iphone_frontoffice.php',
    DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria_backoffice.php',
    DOL_DOCUMENT_ROOT.'/core/menus/standard/auguria_frontoffice.php',
    DOL_DOCUMENT_ROOT.'/core/menus/standard/eldy_backoffice.php',
    DOL_DOCUMENT_ROOT.'/core/menus/standard/eldy_frontoffice.php',
    DOL_DOCUMENT_ROOT.'/core/modules/mailings/dolibarr_services_expired.modules.php',
    DOL_DOCUMENT_ROOT.'/core/modules/mailings/peche.modules.php',
    DOL_DOCUMENT_ROOT.'/core/modules/mailings/poire.modules.php',
    DOL_DOCUMENT_ROOT.'/core/modules/mailings/kiwi.modules.php',
    DOL_DOCUMENT_ROOT.'/core/modules/facture/pdf_crabe.modules.php',
    DOL_DOCUMENT_ROOT.'/core/modules/facture/pdf_oursin.modules.php'
    );

    foreach ($filetodeletearray as $filetodelete)
    {
        //print '<b>'.$filetodelete."</b><br>\n";
        $result=1;
        if (file_exists($filetodelete))
        {
            $result=dol_delete_file($filetodelete);
            if (! $result)
            {
                $langs->load("errors");
                print '<div class="error">'.$langs->trans("Error").': '.$langs->trans("ErrorFailToDeleteFile",$filetodelete);
                print ' '.$langs->trans("RemoveItManuallyAndPressF5ToContinue").'</div>';
            }
            else
            {
                //print $langs->trans("FileWasRemoved",$filetodelete);
            }
        }
    }
    return $result;
}

/**
 * Remove deprecated directories
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	void
 */
function migrate_delete_old_dir($db,$langs,$conf)
{
    $result=true;

    dolibarr_install_syslog("upgrade2::migrate_delete_old_dir");

    // List of files to delete
    $filetodeletearray=array(
    DOL_DOCUMENT_ROOT.'/core/modules/facture/terre',
    DOL_DOCUMENT_ROOT.'/core/modules/facture/mercure'
    );

    foreach ($filetodeletearray as $filetodelete)
    {
        //print '<b>'.$filetodelete."</b><br>\n";
        if (file_exists($filetodelete))
        {
            $result=dol_delete_dir_recursive($filetodelete);
        }
        if (! $result)
        {
            $langs->load("errors");
            print '<div class="error">'.$langs->trans("Error").': '.$langs->trans("ErrorFailToDeleteDir",$filetodelete);
            print ' '.$langs->trans("RemoveItManuallyAndPressF5ToContinue").'</div>';
        }
    }
    return $result;
}


/**
 * Disable/Reenable features modules.
 * We must do this when internal menu of module or permissions has changed
 * or when triggers have moved.
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @return	void
 */
function migrate_reload_modules($db,$langs,$conf)
{
    dolibarr_install_syslog("upgrade2::migrate_reload_modules");

    if (! empty($conf->global->MAIN_MODULE_AGENDA))
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Agenda");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modAgenda.class.php';
        if ($res) {
            $mod=new modAgenda($db);
            $mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_SOCIETE))
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Societe");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modSociete.class.php';
        if ($res) {
            $mod=new modSociete($db);
            $mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_PRODUIT))    // Permission has changed into 2.7
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Produit");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modProduct.class.php';
        if ($res) {
            $mod=new modProduct($db);
            //$mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_SERVICE))    // Permission has changed into 2.7
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Service");
        if ($res) {
            $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modService.class.php';
            $mod=new modService($db);
            //$mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_COMMANDE))   // Permission has changed into 2.9
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Commande");
        if ($res) {
            $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modCommande.class.php';
            $mod=new modCommande($db);
            //$mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_FACTURE))    // Permission has changed into 2.9
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Facture");
        if ($res) {
            $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modFacture.class.php';
            $mod=new modFacture($db);
            //$mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_FOURNISSEUR))    // Permission has changed into 2.9
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Fournisseur");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modFournisseur.class.php';
        if ($res) {
            $mod=new modFournisseur($db);
            //$mod->remove('noboxes');
            $mod->init('newboxdefonly');
        }
    }

    if (! empty($conf->global->MAIN_MODULE_USER))    // Permission has changed into 3.0
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module User");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modUser.class.php';
        if ($res) {
            $mod=new modUser($db);
            //$mod->remove('noboxes');  // We need to remove because id of module has changed
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_DEPLACEMENT))    // Permission has changed into 3.0
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Deplacement");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modDeplacement.class.php';
        if ($res) {
            $mod=new modDeplacement($db);
            //$mod->remove('noboxes');	// We need to remove because a permission id has been removed
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_DON))    // Permission has changed into 3.0
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Don");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modDon.class.php';
        if ($res) {
            $mod=new modDon($db);
            //$mod->remove('noboxes');	// We need to remove because a permission id has been removed
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_ECM))    // Permission has changed into 3.0 and 3.1
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module ECM");
        if ($res) {
            $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modECM.class.php';
            $mod=new modECM($db);
            $mod->remove('noboxes');	// We need to remove because a permission id has been removed
            $mod->init('newboxdefonly');
        }
    }
    if (! empty($conf->global->MAIN_MODULE_PAYBOX))    // Permission has changed into 3.0
    {
        dolibarr_install_syslog("upgrade2::migrate_reload_modules Reactivate module Paybox");
        $res=@include_once DOL_DOCUMENT_ROOT.'/core/modules/modPaybox.class.php';
        if ($res) {
            $mod=new modPaybox($db);
            $mod->remove('noboxes');  // We need to remove because id of module has changed
            $mod->init('newboxdefonly');
        }
    }

}



/**
 * Reload menu if dynamic menus, if modified by version
 *
 * @param	DoliDB		$db			Database handler
 * @param	Translate	$langs		Object langs
 * @param	Conf		$conf		Object conf
 * @param	string		$versionto	Version target
 * @return	void
 */
function migrate_reload_menu($db,$langs,$conf,$versionto)
{
    global $conf;
    dolibarr_install_syslog("upgrade2::migrate_reload_menu");

    // Define list of menu handlers to initialize
    $listofmenuhandler=array();

    $versiontoarray=explode('.',$versionto);

    // Script for VX (X<2.9) -> V2.9
    $afterversionarray=explode('.','2.8.9');
    $beforeversionarray=explode('.','2.9.9');
    if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
    {
        $listofmenuhandler['auguria']=1;   // We set here only dynamic menu handlers
    }

    // Script for VX (X<3.2) -> V3.2
    $afterversionarray=explode('.','3.1.9');
    $beforeversionarray=explode('.','3.2.9');
    if (versioncompare($versiontoarray,$afterversionarray) >= 0 && versioncompare($versiontoarray,$beforeversionarray) <= 0)
    {
        $listofmenuhandler['auguria']=1;   // We set here only dynamic menu handlers
    }

    foreach ($listofmenuhandler as $key => $val)
    {
        print '<tr><td colspan="4">';

        //print "x".$key;
        print '<br>';
        print '<b>'.$langs->trans('Upgrade').'</b>: '.$langs->trans('MenuHandler')." ".$key."<br>\n";

        // Load sql ini_menu_handler.sql file
        $dir = DOL_DOCUMENT_ROOT."/core/menus/";
        $file='init_menu_'.$key.'.sql';
        if (file_exists($dir.$file))
        {
            $result=run_sql($dir.$file,1,'',1,$key);
        }

        print '</td></tr>';
    }
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