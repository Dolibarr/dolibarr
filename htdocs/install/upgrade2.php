<?php
/* Copyright (C) 2005      Marc Barilley / Océbo <marc@ocebo.com>
 * Copyright (C) 2005      Laurent Destailleur   <eldy@users.sourceforge.net>
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
	\file       htdocs/install/paiementfourn_newstructure.php
	\brief      Migre les données de l'ancienne table des paiements fournisseur vers la nouvelle structure
	\version    $Revision$
*/

include_once('./inc.php');

$grant_query='';
$etape = 2;
$error = 0;


// Cette page peut etre longue. On augmente le délai par défaut de 30 à 60.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
set_time_limit(60);
error_reporting($err);

$setuplang=isset($_POST['selectlang'])?$_POST['selectlang']:(isset($_GET['selectlang'])?$_GET['selectlang']:'auto');
$langs->setDefaultLang($setuplang);

$langs->load('admin');
$langs->load('install');
$langs->load("bills");
$langs->load("suppliers");


pHeader($langs->trans('DataMigration'),'etape5','upgrade');


if (file_exists($conffile))
{
	include_once($conffile);
	if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_';
	define('MAIN_DB_PREFIX',$dolibarr_main_db_prefix);
}

if($dolibarr_main_db_type == 'mysql')
{
	require_once($dolibarr_main_document_root . '/lib/mysql.lib.php');
	$choix=1;
}
else
{
	require_once($dolibarr_main_document_root . '/lib/pgsql.lib.php');
	require_once($dolibarr_main_document_root . '/lib/grant.postgres.php');
	$choix=2;
}

require_once($dolibarr_main_document_root . '/conf/conf.class.php');



if (isset($_POST['action']) && $_POST['action'] == 'upgrade')
{
	print '<h2>'.$langs->trans('DataMigration').'</h2>';
	print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';

	$conf = new Conf();// on pourrait s'en passer
	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	$db = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name);
	if ($db->connected != 1)
	{
		print '<tr><td colspan="4">Erreur lors de la création de : '.$dolibarr_main_db_name.'</td><td align="right">'.$langs->trans('Error').'</td></tr>';
		$error++;
	}

	if (! $error)
	{
		if($db->database_selected == 1)
		{
			dolibarr_syslog('Connexion réussie à la base : '.$dolibarr_main_db_name);
		}
		else
		{
			$error++;
		}
	}

	/***************************************************************************************
	*
	* Migration des données
	*
	***************************************************************************************/
	if (! $error)
	{
	
		$db->begin();
		
		print '<tr><td colspan="4" nowrap="nowrap">&nbsp;</td></tr>';

        // Chaque action de migration doit renvoyer une ligne sur 4 colonnes avec
        // dans la 1ere colonne, la description de l'action a faire
        // dans la 4eme colonne, le texte 'OK' si fait ou 'AlreadyDone' si rien n'est fait ou 'Error'

        migrate_paiements($db,$langs,$conf);

        migrate_contracts_det($db,$langs,$conf);

        migrate_contracts_date1($db,$langs,$conf);

        migrate_contracts_date2($db,$langs,$conf);

        migrate_contracts_date3($db,$langs,$conf);

        migrate_contracts_open($db,$langs,$conf);

        migrate_paiementfourn_facturefourn($db,$langs,$conf);

    	// On commit dans tous les cas.
    	// La procédure etant conçus pour pouvoir passer plusieurs fois quelquesoit la situation.
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
 * Mise a jour des paiements (lien n-n paiements factures)
 */
function migrate_paiements($db,$langs,$conf)
{
    print '<br>';
    print "<b>Mise a jour des paiments (lien n-n paiements-factures)</b><br>\n";
    
    $sql = "SELECT p.rowid, p.fk_facture, p.amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " WHERE p.fk_facture > 0";
    $resql = $db->query($sql);
    
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
        dolibarr_print_error($db);   
    }
    
    if ($num)
    {
        print "$num paiement(s) à mettre à jour<br>\n";
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
        
              print "Mise a jour paiement(s) ".$row[$i]."<br>\n";
            } 
        }
        
        if ($res == (2 * sizeof($row)))
        {
          $db->commit();
          print "Mise à jour réussie<br>";
        }
        else
        {
          $db->rollback();
          print "La mise à jour à échouée<br>";
        }
    }
    else
    {
        print "Pas ou plus de paiements orphelins à corriger.<br>\n";
    }  
}


/*
 * Mise a jour des contrats (gestion du contrat + detail de contrat)
 */
function migrate_contracts_det($db,$langs,$conf)
{
    $nberr=0;
    
    print '<br>';
    print "<b>Mise a jour des contrats sans details (gestion du contrat + detail de contrat)</b><br>\n";
    
    $sql = "SELECT c.rowid as cref, c.date_contrat, c.statut, c.mise_en_service, c.fin_validite, c.date_cloture, c.fk_product, c.fk_facture, c.fk_user_author,";
    $sql.= " p.ref, p.label, p.description, p.price, p.tva_tx, p.duration, cd.rowid";
    $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
    $sql.= " ON c.fk_product = p.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd";
    $sql.= " ON c.rowid=cd.fk_contrat";
    $sql.= " WHERE cd.rowid IS NULL AND p.rowid IS NOT NULL";
    $resql = $db->query($sql);
    
    if ($resql) 
    {
        $i = 0;
        $row = array();
        $num = $db->num_rows($resql);
    
        if ($num)
        {
            print "$num contrat(s) à mettre à jour<br>\n";
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
                    print "Création ligne contrat pour contrat ref ".$obj->cref."<br>\n";
                }
                else 
                {            
                    dolibarr_print_error($db);
                    $nberr++;
                }
    
                $i++;
            }
    
            if (! $nberr)
            {
            //      $db->rollback();
                  $db->commit();
                  print "Mise à jour réussie<br>";
            }
            else
            {
                  $db->rollback();
                  print "La mise à jour à échouée<br>";
            }
        }
        else {
            print "Pas ou plus de contrats (liés à un produit) sans lignes de details à corriger.<br>\n";
        }
    }
    else
    {
        print "Le champ fk_facture n'existe plus. Pas d'opération à faire.<br>\n";
    //    dolibarr_print_error($db);   
    }    
}


/*
 * Mise a jour des date de contrats non renseignées
 */
function migrate_contracts_date1($db,$langs,$conf)
{
    print '<br>';
    print "<b>Mise a jour des dates de contrats non renseignées</b><br>\n";
    
    $sql="update llx_contrat set date_contrat=tms where date_contrat is null";
    $resql = $db->query($sql);
    if (! $resql) dolibarr_print_error($db);
    if ($db->affected_rows() > 0) print "Ok pour date de contrat<br>\n";
    else print "Pas ou plus de date de contrats à renseigner.<br>\n";
    
    $sql="update llx_contrat set datec=tms where datec is null";
    $resql = $db->query($sql);
    if (! $resql) dolibarr_print_error($db);
    if ($db->affected_rows() > 0) print "Ok pour date création<br>\n";
    else print "Pas ou plus de date de création à renseigner.<br>\n";
}


/*
 * Mise a jour date contrat avec date min effective mise en service si inférieur
 */
function migrate_contracts_date2($db,$langs,$conf)
{
    $nberr=0;
    
    print '<br>';
    print "<b>Mise a jour dates contrat incorrectes (pour contrats avec detail en service)</b><br>\n";
    
    $sql = "SELECT c.rowid as cref, c.datec, c.date_contrat, MIN(cd.date_ouverture) as datemin";
    $sql.= " FROM ".MAIN_DB_PREFIX."contrat as c,";
    $sql.= " ".MAIN_DB_PREFIX."contratdet as cd";
    $sql.= " WHERE c.rowid=cd.fk_contrat AND cd.date_ouverture IS NOT NULL";
    $sql.= " GROUP BY c.rowid, c.date_contrat";
    $resql = $db->query($sql);
    
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
                    print "Correction contrat ".$obj->cref." (Date contrat=".$obj->date_contrat.", Date mise service min=".$obj->datemin.")<br>\n";
                    $sql ="UPDATE ".MAIN_DB_PREFIX."contrat";
                    $sql.=" SET date_contrat='".$obj->datemin."'";
                    $sql.=" WHERE rowid=".$obj->cref;
                    $resql2=$db->query($sql);
                    if (! $resql2) dolibarr_print_error($db);
                    
                    $nbcontratsmodifie++;
                }
                $i++;
            }
    
            $db->commit();
    
            if ($nbcontratsmodifie) print "$nbcontratsmodifie contrats modifiés<br>\n";
            else print "Pas ou plus de contrats à corriger.<br>\n";
        }
    }
    else
    {
        dolibarr_print_error($db);
    }
}



/*
 * Mise a jour des dates de création de contrat
 */
function migrate_contracts_date3($db,$langs,$conf)
{
    print '<br>';
    print "<b>Mise a jour des dates de création de contrat qui ont une valeur incohérente</b><br>\n";
    
    $sql="update llx_contrat set datec=date_contrat where datec is null or datec > date_contrat";
    $resql = $db->query($sql);
    if (! $resql) dolibarr_print_error($db);
    if ($db->affected_rows() > 0) print "Ok<br>\n";
    else print "Pas ou plus de date de contrats à corriger.<br>\n";
}


/*
 * Reouverture des contrats qui ont au moins une ligne non fermée
 */
function migrate_contracts_open($db,$langs,$conf)
{
    print '<br>';
    print "<b>Reouverture des contrats qui ont au moins un service actif non fermé</b><br>\n";
    
    $sql = "SELECT c.rowid as cref FROM llx_contrat as c, llx_contratdet as cd";
    $sql.= " WHERE cd.statut = 4 AND c.statut=2 AND c.rowid=cd.fk_contrat";
    $resql = $db->query($sql);
    if (! $resql) dolibarr_print_error($db);
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
    
                print "Réouverture contrat ".$obj->cref."<br>\n";
                $sql ="UPDATE ".MAIN_DB_PREFIX."contrat";
                $sql.=" SET statut=1";
                $sql.=" WHERE rowid=".$obj->cref;
                $resql2=$db->query($sql);
                if (! $resql2) dolibarr_print_error($db);
                
                $nbcontratsmodifie++;
    
                $i++;
            }
    
            $db->commit();
    
            if ($nbcontratsmodifie) print "$nbcontratsmodifie contrats modifiés<br>\n";
            else print "Pas ou plus de contrats à corriger.<br>\n";
        }
    }
    else print "Pas ou plus de contrats à réouvrir.<br>\n";
}


/**
 * Factures fournisseurs
 */
function migrate_paiementfourn_facturefourn($db,$langs,$conf)
{
	global $bc;
	
	$error = 0;
    $nb=0;
	$select_sql  = 'SELECT rowid, fk_facture_fourn, amount ';
	$select_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn ';
	$select_sql .= ' WHERE fk_facture_fourn IS NOT NULL ;';
	$select_resql = $db->query($select_sql);
	if ($select_resql)
	{
		$select_num = $db->num_rows($select_resql);
		$i=0;
		$var = true;
        
		// Pour chaque paiement fournisseur, on insère une ligne dans paiementfourn_facturefourn
		while ($i < $select_num && ! $error)
		{
			$var = !$var;
			$select_obj = $db->fetch_object($select_resql);

			// Vérifier si la ligne est déjà dans la nouvelle table. On ne veut pas insérer de doublons.
			$check_sql  = 'SELECT fk_paiementfourn, fk_facturefourn';
			$check_sql .= ' FROM '.MAIN_DB_PREFIX.'paiementfourn_facturefourn';
			$check_sql .= ' WHERE fk_paiementfourn = '.$select_obj->rowid.' AND fk_facturefourn = '.$select_obj->fk_facture_fourn.';';
			$check_resql = $db->query($check_sql);
			if ($check_resql)
			{
				$check_num = $db->num_rows($check_resql);
				if ($check_num === 0)
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
   		print '<tr><td colspan="3" nowrap="nowrap"><b>'.$langs->trans('SuppliersInvoices').'</b></td><td align="right">'.$langs->trans("AlreadyDone").'</td></tr>';
    }
	if ($error) 
	{
   		print '<tr><td colspan="3" nowrap="nowrap"><b>'.$langs->trans('SuppliersInvoices').'</b></td><td align="right">'.$langs->trans("Error").'</td></tr>';
    }
}

?>