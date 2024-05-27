<?php
/* Copyright (C) 2023       Progiseize        <contact@progiseize.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

class FactureSituationMigration
{


	/**
	 * @var string Table migration name.
	 */
	public $table_migration = 'facture_situation_migration';

	/**
	 * @var string Table backup.
	 */
	public $table_backupdet = 'facture_situation_migration_backup';

	/**
	 * @var string Table facture name.
	 */
	public $table_facture = 'facture';

	/**
	 * @var string Table facturedet name.
	 */
	public $table_facturedet = 'facturedet';

	/**
	 * @var string Table const
	 */
	public $table_const = 'const';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var int Cycle limit
	 */
	public $cycle_limit = 50;

	/**
	 * @var bool log_detail
	 */
	private $log_detail = 0;



	/**
	 * @var DoliDB Database handler.
	 */
	public function __construct($_db)
	{
		global $db;
		$this->db = is_object($_db) ? $_db : $db;
	}

	/**
	 * put -1 as flag on done field to mark errors
	 *
	 * @param   [type]  $facture_id  [$facture_id description]
	 *
	 * @return  [type]               [return description]
	 */
	public function setFactureError($facture_id)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_migration;
		$sql.= " SET done = -1 ";
		$sql.= " WHERE rowid = '".$this->db->escape($facture_id)."'";
		$res = $this->db->query($sql);
		if (!$res) : return 0; endif;
		return 1;
	}

	public function setFactureDone($facture_id)
	{

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_migration;
		$sql.= " SET done = 1 ";
		$sql.= " WHERE rowid = '".$this->db->escape($facture_id)."'";
		$res = $this->db->query($sql);

		if (!$res) : return 0; endif;
		return 1;
	}

	public function countMigrationToDo()
	{

		global $conf;

		$sql = "SELECT COUNT(DISTINCT situation_cycle_ref) as nbcycle";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_migration;
		$sql.= " WHERE done = '0' AND entity = '".$conf->entity."'";
		$res =  $this->db->query($sql);

		if (!$res) : return -1; endif;
		$obj = $this->db->fetch_object($res);
		return intval($obj->nbcycle);
	}

	public function countMigrationAll()
	{

		global $conf;

		$sql = "SELECT COUNT(DISTINCT situation_cycle_ref) as nbcycle";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_migration;
		$sql.= " WHERE entity = '".$conf->entity."'";
		$res =  $this->db->query($sql);
		if (!$res) : return -1; endif;
		$obj = $this->db->fetch_object($res);
		return intval($obj->nbcycle);
	}

	/**
	 *  We make an invoice data backup
	 */
	public function migration_step_1()
	{

		global $conf;

		// IF ALREADY DONE
		if (isset($conf->global->MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP) && intval($conf->global->MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP) > 0) : return -3; endif;

		dol_syslog('START MIGRATION STEP 1', LOG_DEBUG, 0, '_situationmigration');

		// CREATE
		dol_syslog('Create a backup table ('.MAIN_DB_PREFIX.$this->table_backupdet.')', LOG_DEBUG, 0, '_situationmigration');
		$sql_create = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX.$this->table_backupdet." LIKE ".MAIN_DB_PREFIX.$this->table_facturedet;
		dol_syslog('sql='.$sql_create, LOG_DEBUG, 0, '_situationmigration');
		$result_create = $this->db->query($sql_create);
		if (!$result_create) :
			dol_syslog('Error create backup table'.$sql_create, LOG_ERR, 0, '_situationmigration');
			return -1;
		endif;

		// BACKUP
		dol_syslog('Store '.MAIN_DB_PREFIX.'_facturedet into backup table ('.MAIN_DB_PREFIX.$this->table_backupdet.')', LOG_DEBUG, 0, '_situationmigration');
		$sql_copy = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_backupdet;
		$sql_copy .= " SELECT fd.* FROM ".MAIN_DB_PREFIX.$this->table_facturedet." as fd";
		$sql_copy .= " INNER JOIN ".MAIN_DB_PREFIX.$this->table_facture." as f ON f.rowid = fd.fk_facture";
		$sql_copy .= " WHERE f.entity = '".$conf->entity."'";
		$sql_copy .= " AND f.type = '".facture::TYPE_SITUATION."'";
		dol_syslog('sql='.$sql_copy, LOG_DEBUG, 0, '_situationmigration');
		$result_copy = $this->db->query($sql_copy);
		if (!$result_copy) :
			dol_syslog('Error copy _facturedet into backup table'.$sql_create, LOG_ERR, 0, '_situationmigration');
			return -2;
		endif;

		dol_syslog('END MIGRATION STEP 1', LOG_DEBUG, 0, '_situationmigration');
		return 1;
	}

	/**
	 * retourne la liste des sequences concernees par la migration:
	 * cycle non terminé ou terminé cette année
	 *
	 * @return  [type]  [return description]
	 */
	public function get_sequences_to_migrate()
	{
		$ret = [];
		if (getDolGlobalString('FACTURESITUATIONMIGRATION_CURRENT_YEAR', '') == '') {
			return null;
		}
		$sql = "SELECT DISTINCT situation_cycle_ref FROM ".MAIN_DB_PREFIX."facture";
		$res = $this->db->query($sql);
		if ($res) {
			while ($obj = $this->db->fetch_object($res)) {
				if (!empty($obj->situation_cycle_ref)) {
					$sql2 = "SELECT rowid,situation_counter,situation_final,situation_cycle_ref,YEAR(datef) as year FROM ".MAIN_DB_PREFIX."facture WHERE situation_cycle_ref='" . $obj->situation_cycle_ref . "' ORDER BY situation_counter DESC LIMIT 1";
					$res2 = $this->db->query($sql2);
					if ($res2) {
						$obj2 = $this->db->fetch_object($res2);
						// if ($obj2->situation_final == 0 && $obj2->year > (date('Y') - 1)) {
						//     dol_syslog('  Add that cycle number '.$obj2->situation_cycle_ref.' to migration due to cycle not ended and last invoice edited in '.$obj2->year,LOG_DEBUG,0,'_situationmigration');
						//     $ret[] = $obj2->situation_cycle_ref;
						// }
						//les cycles en cours ou terminés cette année
						if ($obj2->year == date('Y')) {
							dol_syslog('  Add that cycle number '.$obj2->situation_cycle_ref.' to migration due to cycle not ended and last invoice edited in '.$obj2->year, LOG_DEBUG, 0, '_situationmigration');
							$ret[] = $obj2->situation_cycle_ref;
						}
					}
				}
			}
		}
		return implode(',', $ret);
	}

	/**
	 *  Store ID facture_situation into facture_situation_migration
	 *  in order to check if migration is done
	 */
	public function migration_step_2()
	{

		global $conf;

		dol_syslog('START MIGRATION STEP 2', LOG_DEBUG, 0, '_situationmigration');

		// On récupère tous les identifiants des factures de situations
		dol_syslog('Select all situation invoices with cycle ref', LOG_DEBUG, 0, '_situationmigration');
		$sql = "SELECT rowid, entity, situation_cycle_ref FROM ".MAIN_DB_PREFIX.$this->table_facture;
		$sql.= " WHERE type = '".facture::TYPE_SITUATION."' AND entity = '".$conf->entity."'";
		//uniquement une certaine selection de factures
		$liste_seq_operate = $this->get_sequences_to_migrate();
		if (null !== $liste_seq_operate) {
			$sql.= " AND situation_cycle_ref IN(".$liste_seq_operate.")";
		}
		$res = $this->db->query($sql);
		dol_syslog('sql='.$sql, LOG_DEBUG, 0, '_situationmigration');

		if ($res) {
			// SI FACTURES DE SITUATIONS
			if ($res->num_rows > 0) {
				$insert_success = 0;
				$insert_exist = 0;

				dol_syslog('Result : '.$res->num_rows.' invoices founded', LOG_DEBUG, 0, '_situationmigration');
				dol_syslog('We store results in migration table ('.MAIN_DB_PREFIX.$this->table_migration.')', LOG_DEBUG, 0, '_situationmigration');

				while ($obj = $this->db->fetch_object($res)) {
					$sql_insert = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_migration;
					$sql_insert.= " (rowid,situation_cycle_ref,entity,done) VALUES (";
					$sql_insert.= "'".$this->db->escape($obj->rowid)."',";
					$sql_insert.= "'".$this->db->escape($obj->situation_cycle_ref)."',";
					$sql_insert.= "'".$this->db->escape($obj->entity)."',";
					$sql_insert.= "'0'";
					$sql_insert.= ")";
					dol_syslog('sql='.$sql_insert, LOG_DEBUG, 0, '_situationmigration');

					$res_insert = $this->db->query($sql_insert);
					if ($res_insert) {$insert_success++;} elseif (!$res_insert && $this->db->db->lasterrno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
						dol_syslog('ID('.$obj->rowid.') already exist, we continue', LOG_DEBUG, 0, '_situationmigration');
						$insert_exist++;
					}
				}

				// SUCCES
				if ($insert_success == $res->num_rows) {
					dol_syslog('Step2 result = 1 (All insert at the same time)', LOG_DEBUG, 0, '_situationmigration');
					dol_syslog('END MIGRATION STEP 2', LOG_DEBUG, 0, '_situationmigration');
					return 1;
				}
				// SUCCES EN CAS D'ERREURS PRECEDENTES
				elseif ($insert_success + $insert_exist == $res->num_rows && $insert_exist != $res->num_rows) {
					dol_syslog('Step2 result = 2 (All insert ok, in several times)', LOG_DEBUG, 0, '_situationmigration');
					dol_syslog('END MIGRATION STEP 2', LOG_DEBUG, 0, '_situationmigration');
					return 2;
				}
				// AUCUNE INSERTION NECESSAIRE
				elseif ($insert_exist == $res->num_rows) {
					dol_syslog('Step2 result = 3 (Reload step 2 with all results already stored, we can go to step 3)', LOG_DEBUG, 0, '_situationmigration');
					dol_syslog('END MIGRATION STEP 2', LOG_DEBUG, 0, '_situationmigration');
					return 3;
				}
				// ERREUR INSERTION
				else {
					$this->error = 'Error while process, reload step 2 please';
					dol_syslog('Error while process, we must reload step 2', LOG_WARNING, 0, '_situationmigration');
					return -2;
				}
			}
			// AUCUNE FACTURE DE SITUATION
			else {
				dol_syslog('No cycles found, no migration needed', LOG_DEBUG, 0, '_situationmigration');
				return 0;
			}

			// ERREUR SQL SELECT
		} else {
			$this->error = 'Erreur SQL';
			dol_syslog('Error sql', LOG_ERR, 0, '_situationmigration');
			return -1;
		}
	}

	/**
	 *  Convert DATA to the new method by cycle_ref
	 *  situation_percent, total_ht, total_tva, total_ttc, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc
	 *  localtax ?
	 *
	 *  $listOfErrors : tableau de liste des factures pour lesquelles un pb est arrivé
	 */
	public function migration_step_3(&$listOfErrors)
	{

		global $conf;

		dol_syslog('START MIGRATION STEP 3', LOG_DEBUG, 0, '_situationmigration');

		// ON RECUPERE ET REGROUPE LES REFERENCES DE CYCLE
		$sql = "SELECT";
		$sql.= " DISTINCT situation_cycle_ref";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_migration;
		$sql.= " WHERE done = '0'";
		$sql.= " AND entity = '".$conf->entity."' LIMIT ".$this->cycle_limit;
		dol_syslog('We Select and group ref cycle not done', LOG_DEBUG, 0, '_situationmigration');
		dol_syslog('sql='.$sql, LOG_DEBUG, 0, '_situationmigration');

		$res = $this->db->query($sql);
		if ($res) :
			// RETURN 1 IF ALL ALREADY DONE
			if ($res->num_rows == 0) :
				dol_syslog('All cycles are already done', LOG_DEBUG, 0, '_situationmigration');
				return 1;
			endif;

			$nb_update = 0;
			$nb_update_success = 0;
			$nb_update_error = 0;

			//
			$isset_fact = array();

			// POUR CHAQUE CYCLE
			dol_syslog('For each cycle not done', LOG_DEBUG, 0, '_situationmigration');
			while ($obj = $this->db->fetch_object($res)) : $nb_update++;

				$this->db->begin();

				$sql_bis = "SELECT";
				$sql_bis.= " f.rowid as facture_id, f.ref as facture_ref, f.situation_cycle_ref as facture_cycle_ref, f.situation_counter as facture_situation_counter, f.situation_final as facture_situation_final";
				$sql_bis.= " , fd.rowid as ligne_id, fd.situation_percent as ligne_percent, fd.fk_prev_id as ligne_prev_id";
				$sql_bis.= " , fd.subprice as ligne_subprice, fd.total_ht as ligne_total_ht, fd.total_tva as ligne_total_tva, fd.total_ttc as ligne_total_ttc, fd.special_code as special_code";
				$sql_bis.= " , fd.multicurrency_subprice as ligne_multicurrency_subprice, fd.multicurrency_total_ht as ligne_multicurrency_total_ht, fd.multicurrency_total_tva as ligne_multicurrency_total_tva, fd.multicurrency_total_ttc as ligne_multicurrency_total_ttc";
				$sql_bis.= " FROM ".MAIN_DB_PREFIX.$this->table_facture." AS f";
				$sql_bis.= " INNER JOIN ".MAIN_DB_PREFIX.$this->table_facturedet." AS fd ON f.rowid = fd.fk_facture";
				$sql_bis.= " WHERE situation_cycle_ref = '".$obj->situation_cycle_ref."' AND entity = '".$conf->entity."'";
				$sql_bis.= " ORDER BY f.situation_cycle_ref DESC, f.situation_counter DESC";
				dol_syslog('START CYCLE_REF:: '.$obj->situation_cycle_ref, LOG_DEBUG, 0, '_situationmigration');
				dol_syslog('sql='.$sql_bis, LOG_DEBUG, 0, '_situationmigration');

				$res_bis = $this->db->query($sql_bis);
				if ($res_bis) :
					/* -------------------------------------------------------- */
					/* CONSTRUCTION TABLEAU ----------------------------------- */
					/* -------------------------------------------------------- */

					$cycle_array = array();

					// POUR CHAQUE LIGNE DU CYCLE
					while ($obj_bis = $this->db->fetch_object($res_bis)) {
						if (!isset($cycle_array[$obj_bis->facture_situation_counter])) {
							$cycle_array[$obj_bis->facture_situation_counter] = array(
								'facture_year' => $obj_bis->facture_year,
								'situation_final' => $obj_bis->situation_final,
								'facture_id' => $obj_bis->facture_id,
								'facture_ref' => $obj_bis->facture_ref,
								'lines' => array(),
							);
						}

						$cycle_array[$obj_bis->facture_situation_counter]['lines'][$obj_bis->ligne_id] = array(
							'line_percent' => $obj_bis->ligne_percent,
							'fk_prev_id' => $obj_bis->ligne_prev_id,
							'subprice' => $obj_bis->ligne_subprice,
							'ligne_total_ht' => $obj_bis->ligne_total_ht,
							'ligne_total_tva' => $obj_bis->ligne_total_tva,
							'ligne_total_ttc' => $obj_bis->ligne_total_ttc,
							'multicurrency_subprice' => $obj_bis->ligne_multicurrency_subprice,
							'multicurrency_ligne_total_ht' => $obj_bis->ligne_multicurrency_total_ht,
							'multicurrency_ligne_total_tva' => $obj_bis->ligne_multicurrency_total_tva,
							'multicurrency_ligne_total_ttc' => $obj_bis->ligne_multicurrency_total_ttc,
							'special_code' => $obj_bis->special_code,
						);
					}

					/* -------------------------------------------------------- */
					/* PARCOURS TABLEAU ----------------------------------- */
					/* -------------------------------------------------------- */
					//var_dump('-- CYCLE N°'.$obj->situation_cycle_ref);
					//var_dump($cycle_array);

					//
					$facture_update = 0;
					$facture_update_success = 0;
					$facture_update_error = 0;

					// TRI DECROISSANT
					krsort($cycle_array);

					// print json_encode($cycle_array);exit;
					// POUR CHAQUE SITUATION DU CYCLE
					foreach ($cycle_array as $cycle_counter => $cycle_infos) : $facture_update++;

						if ($cycle_infos['situation_final'] == 1 && $cycle_infos['facture_year'] < date('Y')) {
							dol_syslog('SituationCounter::'.$cycle_counter.' ('.$cycle_infos['facture_ref'].') is final and year ['.$cycle_infos['facture_year'].'] is less than current year, do not migrate', LOG_DEBUG, 0, '_situationmigration');
							$facture_update_success ++; $this->setFactureDone($cycle_infos['facture_id']);
							continue;
						}
						// print json_encode($cycle_infos);exit;
						//
						//var_dump('---- SITU '.$cycle_counter.' :: '.count($cycle_infos['lines']).' lignes :: '.$cycle_infos['facture_id'].' :: '.$cycle_infos['facture_ref']);

						$facture_ref = $cycle_infos['facture_ref'];
						$factureline_update = 0;
						$factureline_update_success = 0;
						$factureline_update_error = 0;

						dol_syslog('SituationCounter::'.$cycle_counter.' ('.$cycle_infos['facture_ref'].')', LOG_DEBUG, 0, '_situationmigration');

						// Si on est sur une situation > 1 dans le cycle, on recalcule, la situation 1 est toujours correcte
						if (intval($cycle_counter) > 1) :
							//
							$cycle_counter_before = intval($cycle_counter) - 1;

							// Pour chaque ligne de la facture
							foreach ($cycle_infos['lines'] as $line_id => $line_infos) :
								//plus facile à suivre, l'id de la ligne sur la facture précédente
								$fk_prev_id = $line_infos['fk_prev_id'];
								//et la ligne complète
								$prev_line_infos = $cycle_array[$cycle_counter_before]['lines'][$fk_prev_id];
								$prev_facture_ref = $cycle_array[$cycle_counter_before]['facture_ref'];

								// Check if special code or subtotal
								if ($line_infos['special_code'] == '104777') : continue; endif;
								// Check if is a previous id
								if (empty($fk_prev_id)) : continue; endif;

								$factureline_update++;
								if (!isset($line_infos['line_percent'])) {
									dol_syslog('Invoice::' . $facture_ref . ' - Line::'.$line_id.' - PreviousInvoice::'. $prev_facture_ref.' - PreviousLine::'.$fk_prev_id." ERROR: line_percent is not set (a)", LOG_ERR, 0, '_situationmigration');
									$factureline_update_error++;
								} else {
									$percent = $line_infos['line_percent'];
									$this->_handle_line_percent_value($percent, $line_id, $fk_prev_id, $line_infos, $factureline_update_error);
								}
								if (!isset($prev_line_infos['line_percent'])) {
									dol_syslog('Invoice::' .$cycle_infos['facture_ref'] . ' - Line::'.$line_id.' - PreviousInvoice::'. $prev_facture_ref.' - PreviousLine::'.$fk_prev_id." ERROR: prev cycle counter line_percent is not set (b)", LOG_ERR, 0, '_situationmigration');
									$factureline_update_error++;
								} else {
									$percent = $prev_line_infos['line_percent'];
									$this->_handle_line_percent_value($percent, $line_id, $fk_prev_id, $prev_line_infos, $factureline_update_error);
								}

								dol_syslog('Invoice::' . $cycle_infos['facture_ref'] . ' - Line::'.$line_id.' - PreviousInvoice::'. $prev_facture_ref.' - factureline_update_error='.$factureline_update_error, LOG_ERR, 0, '_situationmigration');
								if ($factureline_update_error > 0) {
									$listOfErrors[] = $cycle_infos['facture_ref'];
									$this->setFactureError($cycle_infos['facture_id']);
									$nb_update_success++; $this->db->commit();
									continue 2;
								}

								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['line_percent'] = number_format(floatval($line_infos['line_percent']) - floatval($prev_line_infos['line_percent']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ht'] = number_format(floatval($line_infos['ligne_total_ht']) - floatval($prev_line_infos['ligne_total_ht']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_tva'] = number_format(floatval($line_infos['ligne_total_tva']) - floatval($prev_line_infos['ligne_total_tva']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ttc'] = number_format(floatval($line_infos['ligne_total_ttc']) - floatval($prev_line_infos['ligne_total_ttc']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ht'] = number_format(floatval($line_infos['multicurrency_ligne_total_ht']) - floatval($prev_line_infos['multicurrency_ligne_total_ht']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_tva'] = number_format(floatval($line_infos['multicurrency_ligne_total_tva']) - floatval($prev_line_infos['multicurrency_ligne_total_tva']), 2, '.', '');
								$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ttc'] = number_format(floatval($line_infos['multicurrency_ligne_total_ttc']) - floatval($prev_line_infos['multicurrency_ligne_total_ttc']), 2, '.', '');

								// LOGS
								if ($this->log_detail > 0) :
									$log_percent = 'New Percent = Actual('.$line_infos['line_percent'].') - PreviousLine('.$prev_line_infos['line_percent'].') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['line_percent'].'%';
									$log_ht = 'New TotalHT = Actual('.floatval($line_infos['ligne_total_ht']).') - PreviousLine('.floatval($prev_line_infos['ligne_total_ht']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ht'].'€';
									$log_tva = 'New TotalTVA = Actual('.floatval($line_infos['ligne_total_tva']).') - PreviousLine('.floatval($prev_line_infos['ligne_total_tva']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_tva'].'€';
									$log_ttc = 'New TotalTTC = Actual('.floatval($line_infos['ligne_total_ttc']).') - PreviousLine('.floatval($prev_line_infos['ligne_total_ttc']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ttc'].'€';
									$log_multiht = 'New MulticurrencyTotalHT = Actual('.floatval($line_infos['multicurrency_ligne_total_ht']).') - PreviousLine('.floatval($prev_line_infos['multicurrency_ligne_total_ht']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ht'].'€';
									$log_multitva = 'New MulticurrencyTotalTVA = Actual('.floatval($line_infos['multicurrency_ligne_total_tva']).') - PreviousLine('.floatval($prev_line_infos['multicurrency_ligne_total_tva']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_tva'].'€';
									$log_multittc = 'New MulticurrencyTotalTTC = Actual('.floatval($line_infos['multicurrency_ligne_total_ttc']).') - PreviousLine('.floatval($prev_line_infos['multicurrency_ligne_total_ttc']).') = '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ttc'].'€';
									dol_syslog('Invoice::' . $cycle_infos['facture_ref'] . ' - Line::'.$line_id.' - PreviousLine::'.$fk_prev_id, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_percent, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_ht, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_tva, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_ttc, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_multiht, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_multitva, LOG_DEBUG, 0, '_situationmigration');
									dol_syslog($log_multittc, LOG_DEBUG, 0, '_situationmigration');
								endif;

								//var_dump('------------- NEW HT:'.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ht'].'€ || '.$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['line_percent'].'%');

								$sql_update = "UPDATE ".MAIN_DB_PREFIX.$this->table_facturedet." SET";
								$sql_update.= " situation_percent = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['line_percent']."',";
								$sql_update.= " total_ht = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ht']."',";
								$sql_update.= " total_tva = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_tva']."',";
								$sql_update.= " total_ttc = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['ligne_total_ttc']."',";
								$sql_update.= " multicurrency_total_ht = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ht']."',";
								$sql_update.= " multicurrency_total_tva = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_tva']."',";
								$sql_update.= " multicurrency_total_ttc = '".$cycle_array[$cycle_counter]['lines'][$fk_prev_id]['multicurrency_ligne_total_ttc']."'";
								$sql_update.= " WHERE rowid = '".$line_id."';";
								dol_syslog('sql='.$sql_update, LOG_DEBUG, 0, '_situationmigration');

								$res_update = $this->db->query($sql_update);

								if ($res_update) : $factureline_update_success++;
								else :
									dol_syslog('Error SQL', LOG_ERR, 0, '_situationmigration');
									$factureline_update_error++;
								endif;
							endforeach;

							if ($factureline_update == $factureline_update_success) : $facture_update_success++; $this->setFactureDone($cycle_infos['facture_id']);
							else : $facture_update_error++; endif;

							//var_dump('----- NB line: '.$factureline_update.' :: Success: '.$factureline_update_success.' | Err: '.$factureline_update_error);
						else :
							dol_syslog('We do nothing, first situation', LOG_DEBUG, 0, '_situationmigration');
							$facture_update_success ++; $this->setFactureDone($cycle_infos['facture_id']);
							// foreach($cycle_infos['lines'] as $lid => $l): var_dump('-------- LIGNE ID:'.$lid.' ||  HT:'.$l['ligne_total_ht'].'€ || '.$l['line_percent'].'%'); endforeach;
						endif;
					endforeach;

					//var_dump('NB fact cycle: '.$facture_update.' :: Success: '.$facture_update_success.' | Err: '.$facture_update_error);

					if ($facture_update == $facture_update_success) : $nb_update_success++; $this->db->commit();
					else :
						dol_syslog('Error, so invoice and cycle can\'t be set done', LOG_ERR, 0, '_situationmigration');
						$nb_update_error++; $this->db->rollback();
					endif;
				else : $nb_update_error++; $this->db->rollback(); endif;

				dol_syslog('END CYCLEREF', LOG_DEBUG, 0, '_situationmigration');
			endwhile;

			// SI TOUT EST FAIT
			if ($nb_update == $nb_update_success) :
				dol_syslog('Step3 result = All success', LOG_DEBUG, 0, '_situationmigration');
				dol_syslog('END MIGRATION STEP 3', LOG_DEBUG, 0, '_situationmigration');
				return $nb_update_success;
				// SI RESULTATS POSITIFS ET NEGATIFS
			elseif ($nb_update_success > 0 && $nb_update_error > 0) :
				dol_syslog('Step3 result = Success and errors', LOG_ERR, 0, '_situationmigration');
				dol_syslog('END MIGRATION STEP 3', LOG_DEBUG, 0, '_situationmigration');
				return -1;
				// TOUT EN ERREUR
			elseif ($nb_update == $nb_update_error) :
				dol_syslog('Step3 result = All update errors', LOG_ERR, 0, '_situationmigration');
				dol_syslog('END MIGRATION STEP 3', LOG_DEBUG, 0, '_situationmigration');
				return -2;
			endif;
		endif;
	}

	//
	public function rollbackMigration()
	{

		global $conf;

		dol_syslog('START MIGRATION ROLLBACK', LOG_DEBUG, 0, '_situationmigration');

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_facturedet."";
		$sql.= " WHERE rowid IN (";
			$sql.= "SELECT * FROM (SELECT fd.rowid FROM ".MAIN_DB_PREFIX.$this->table_facturedet." as fd";
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX.$this->table_facture." as f ON f.rowid = fd.fk_facture AND f.type = '".facture::TYPE_SITUATION."') as tmp";
		$sql.= ")";
		dol_syslog('sql='.$sql, LOG_DEBUG, 0, '_situationmigration');

		$res = $this->db->query($sql);
		if (!$res) : $this->db->rollback(); return - 1; endif;

		$sql2 = "INSERT ".MAIN_DB_PREFIX.$this->table_facturedet." SELECT * FROM ".MAIN_DB_PREFIX.$this->table_backupdet;
		dol_syslog('sql='.$sql2, LOG_DEBUG, 0, '_situationmigration');

		$res2 = $this->db->query($sql2);
		if (!$res2) : $this->db->rollback(); return - 2; endif;

		if (!dolibarr_set_const($this->db, 'MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP', '0', 'chaine', 0, '', $conf->entity)) : $this->db->rollback(); return -3; endif;
		if (!dolibarr_set_const($this->db, 'FACTURESITUATIONMIGRATION_ISDONE', '0', 'chaine', 0, '', $conf->entity)) : $this->db->rollback(); return -3; endif;
		if (!dolibarr_set_const($this->db, 'INVOICE_USE_SITUATION', '1', 'chaine', 0, '', $conf->entity)) : $this->db->rollback(); return -3; endif;
		dol_syslog('Reset MAIN_MODULE_FACTURESITUATIONMIGRATION_STEP=0 || FACTURESITUATIONMIGRATION_ISDONE=0 || INVOICE_USE_SITUATION=1', LOG_DEBUG, 0, '_situationmigration');

		// VIDER LES TABLES MIGRATION ET BACKUP
		$sql_migration = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_migration;
		dol_syslog('sql='.$sql_migration, LOG_DEBUG, 0, '_situationmigration');

		$res_migration = $this->db->query($sql_migration);
		if (!$res_migration) : $this->db->rollback(); return - 4; endif;

		$sql_backup = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_backupdet;
		dol_syslog('sql='.$sql_backup, LOG_DEBUG, 0, '_situationmigration');

		$res_backup = $this->db->query($sql_backup);
		if (!$res_backup) : $this->db->rollback(); return - 5; endif;

		$this->db->commit();
		return 1;
	}

	/**
	 * factorisation de gestion des pourcentages "foireux"
	 *
	 * @param   [type]  $percent       valeur du poucentage
	 * @param   [type]  $lineID        id de la ligne
	 * @param   [type]  $linePrevID    id de la ligne précédente
	 * @param   [type]  $lineInfos     line info modifiable
	 * @param   [type]  $update_error  flag de gestion du nombre d'erreurs
	 *
	 * @return  [type]                 [return description]
	 */
	private function _handle_line_percent_value($percent, $lineID, $linePrevID, &$lineInfos, &$update_error)
	{
		if ($percent > 100) {
			if (getDolGlobalString('FACTURESITUATIONMIGRATION_PERCENT_MORE_100', '') != '') {
				dol_syslog('Line::'.$lineID.' - PreviousLine::'.$linePrevID." ERROR: line_percent is > 100", LOG_ERR, 0, '_situationmigration');
				$update_error++;
			}
			if (getDolGlobalString('FACTURESITUATIONMIGRATION_PERCENT_MORE_100_FIX', '') != '') {
				dol_syslog('Line::'.$lineID.' - PreviousLine::'.$linePrevID." FIX: line_percent is > 100, force to 100", LOG_ERR, 0, '_situationmigration');
				$lineInfos['line_percent'] = 100;
			}
		} elseif ($percent < 0) {
			if (getDolGlobalString('FACTURESITUATIONMIGRATION_PERCENT_LESS_0', '') != '') {
				dol_syslog('Line::'.$lineID.' - PreviousLine::'.$linePrevID." ERROR: line_percent is < 0", LOG_ERR, 0, '_situationmigration');
				$update_error++;
			}
			if (getDolGlobalString('FACTURESITUATIONMIGRATION_PERCENT_LESS_0_FIX', '') != '') {
				dol_syslog('Line::'.$lineID.' - PreviousLine::'.$linePrevID." FIX: line_percent is < 0, force to 0", LOG_ERR, 0, '_situationmigration');
				$lineInfos['line_percent'] = 0;
			}
		}
	}
}
