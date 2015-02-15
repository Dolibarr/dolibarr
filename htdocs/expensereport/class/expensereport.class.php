<?php
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 * Class to manage Trips and Expenses
 */
class ExpenseReport extends CommonObject
{
	var $db;
	var $error;
	var $element='expensereport';
	var $table_element='expensereport';
	var $table_element_line = 'expensereport_det';
	var $fk_element = 'fk_expensereport';

	var $id;
	var $ref_number;
	var $lignes=array();
	var $total_ht;
	var $total_tva;
	var $total_ttc;
	var $note;
	var $date_debut;
	var $date_fin;

	var $fk_user_validator;
	var $fk_c_expensereport_statuts;		// -- 1=brouillon, 2=validé (attente approb), 4=annulé, 5=approuvé, 6=payed, 99=refusé
	var $fk_c_paiement;

	var $user_author_infos;
	var $user_validator_infos;

	var $libelle_paiement;
	var $libelle_statut;
	var $code_paiement;
	var $code_statut;

	/*
		ACTIONS
	*/

		// Enregistrement
		var $date_create;
		var $fk_user_author;

		// Refus
		var $date_refuse;
		var $detail_refuse;
		var $fk_user_refuse;

		// Annulation
		var $date_cancel;
		var $detail_cancel;
		var $fk_user_cancel;

		// Validation
		var $date_valide;
		var	$fk_user_valid;
		var $user_valid_infos;

		// Paiement
		var $date_paiement;
		var $fk_user_paid;
		var $user_paid_infos;

	/*
		END ACTIONS
	*/


   /**
	*  Constructor
	*
	*  @param  DoliDB	$db		Handler acces base de donnees
	*/
	function __construct($db)
	{
		$this->db = $db;
		$this->total_ht = 0;
		$this->total_ttc = 0;
		$this->total_tva = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[2]='Validated';
		$this->statuts[4]='Canceled';
		$this->statuts[5]='Approved';
		$this->statuts[6]='Paid';
		$this->statuts[99]='Refused';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[2]='Validated';
		$this->statuts_short[4]='Canceled';
		$this->statuts_short[5]='Approved';
		$this->statuts_short[6]='Paid';
		$this->statuts_short[99]='Refused';
		$this->statuts_logo[0]='statut0';
		$this->statuts_logo[2]='statut4';
		$this->statuts_logo[4]='statut3';
		$this->statuts_logo[5]='statut5';
		$this->statuts_logo[6]='statut6';
		$this->statuts_logo[99]='statutx';

		return 1;
	}

	/**
	 * Create object in database
	 *
	 * @param 	User	$user	User that create
	 * @return 	int				<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf;

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql.= "ref_number";
		$sql.= ",total_ht";
		$sql.= ",total_ttc";
		$sql.= ",total_tva";
		$sql.= ",date_debut";
		$sql.= ",date_fin";
		$sql.= ",date_create";
		$sql.= ",fk_user_author";
		$sql.= ",fk_user_validator";
		$sql.= ",fk_c_expensereport_statuts";
		$sql.= ",fk_c_paiement";
		$sql.= ",note";
		$sql.= ") VALUES(";
		$sql.= "'(PROV)'";
		$sql.= ", ".$this->total_ht;
		$sql.= ", ".$this->total_ttc;
		$sql.= ", ".$this->total_tva;
		$sql.= ", '".$this->db->idate($this->date_debut)."'";
		$sql.= ", '".$this->db->idate($this->date_fin)."'";
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ".($user->id > 0 ? $user->id:"null");
		$sql.= ", ".($this->fk_user_validator > 0 ? $this->fk_user_validator:2);
		$sql.= ", ".($this->fk_c_expensereport_statuts > 1 ? $this->fk_c_expensereport_statuts:1);
		$sql.= ", ".($this->fk_c_paiement > 0 ? $this->fk_c_paiement:2);
		$sql.= ", ".($this->note?"'".$this->note."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->ref_number='(PROV'.$this->id.')';

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element." SET ref_number='".$this->ref_number."' WHERE rowid=".$this->id;
			dol_syslog(get_class($this)."::create sql=".$sql);
			$resql=$this->db->query($sql);
			if (!$resql) $error++;

			foreach ($this->lignes as $i => $val)
			{
				$newndfline=new ExpenseReportLigne($this->db);
				$newndfline=$this->lignes[$i];
				$newndfline->fk_expensereport=$this->id;
				if ($result >= 0)
				{
					$result=$newndfline->insert();
				}
				if ($result < 0)
				{
					$error++;
					break;
				}
			}

			if (! $error)
			{
				$result=$this->update_price();
				if ($result > 0)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->db->rollback();
					return -3;
				}
			}
			else
			{
				dol_syslog(get_class($this)."::create error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 * update
	 *
	 * @param 	User	$user		User making change
	 * @return 	int					<0 if KO, >0 if OK
	 */
	function update($user)
	{
		global $langs;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql.= " total_ht = ".$this->total_ht;
		$sql.= " , total_ttc = ".$this->total_ttc;
		$sql.= " , total_tva = ".$this->total_tva;
		$sql.= " , date_debut = '".$this->date_debut."'";
		$sql.= " , date_fin = '".$this->date_fin."'";
		$sql.= " , fk_user_author = ".($user->id > 0 ? "'".$user->id."'":"null");
		$sql.= " , fk_user_validator = ".($this->fk_user_validator > 0 ? $this->fk_user_validator:"null");
		$sql.= " , fk_user_valid = ".($this->fk_user_valid > 0 ? $this->fk_user_valid:"null");
		$sql.= " , fk_user_paid = ".($this->fk_user_paid > 0 ? $this->fk_user_paid:"null");
		$sql.= " , fk_c_expensereport_statuts = ".($this->fk_c_expensereport_statuts > 0 ? $this->fk_c_expensereport_statuts:"null");
		$sql.= " , fk_c_paiement = ".($this->fk_c_paiement > 0 ? $this->fk_c_paiement:"null");
		$sql.= " , note = ".(!empty($this->note)?"'".$this->db->escape($this->note)."'":"''");
		$sql.= " , detail_refuse = ".(!empty($this->detail_refuse)?"'".$this->db->escape($this->detail_refuse)."'":"''");
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

   /**
	*	Load an object from database
	*
	*	@param	int		$id		Id
	*	@param	User	$user	User we want expense report for
	*/
	function fetch($id,$user='')
	{
		global $conf,$db;

		if (!$user->rights->expensereport->lire):
			$restrict = " AND fk_user_author = ".$user->id;
		else:
			$restrict = "";
		endif;

		$sql = "SELECT d.rowid, d.ref_number, d.note,"; 												// DEFAULT
		$sql.= " d.detail_refuse, d.detail_cancel, d.fk_user_refuse, d.fk_user_cancel,"; 				// ACTIONS
		$sql.= " d.date_refuse, d.date_cancel,";														// ACTIONS
		$sql.= " d.total_ht, d.total_ttc, d.total_tva,"; 												// TOTAUX (int)
		$sql.= " d.date_debut, d.date_fin, d.date_create, d.date_valide, d.date_paiement,"; 			// DATES (datetime)
		$sql.= " d.fk_user_author, d.fk_user_validator, d.fk_c_expensereport_statuts, d.fk_c_paiement,"; 	// FOREING KEY (int)
		$sql.= " d.fk_user_valid, d.fk_user_paid,";														// FOREING KEY 2 (int)
		$sql.= " dp.libelle as libelle_paiement, dp.code as code_paiement";								// INNER JOIN paiement
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." d";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."c_paiement dp ON d.fk_c_paiement = dp.id";
		$sql.= " WHERE d.rowid = ".$id;
		$sql.= $restrict;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$result = $db->query($sql) ;
		if ($result)
		{
			$obj = $db->fetch_object($result);

			$this->id       	= $obj->rowid;
			$this->ref          = $obj->ref_number;
			$this->ref_number 	= $obj->ref_number;
			$this->total_ht 	= $obj->total_ht;
			$this->total_tva 	= $obj->total_tva;
			$this->total_ttc 	= $obj->total_ttc;
			$this->note 		= $obj->note;
			$this->detail_refuse = $obj->detail_refuse;
			$this->detail_cancel = $obj->detail_cancel;

			$this->date_debut		= $obj->date_debut;
			$this->date_fin			= $obj->date_fin;
			$this->date_paiement	= $obj->date_paiement;
			$this->date_valide		= $obj->date_valide;
			$this->date_create		= $obj->date_create;
			$this->date_refuse		= $obj->date_refuse;
			$this->date_cancel		= $obj->date_cancel;

			$this->fk_user_author			= $obj->fk_user_author;
			$this->fk_user_validator		= $obj->fk_user_validator;
			$this->fk_user_valid			= $obj->fk_user_valid;
			$this->fk_user_paid				= $obj->fk_user_paid;
			$this->fk_user_refuse			= $obj->fk_user_refuse;
			$this->fk_user_cancel			= $obj->fk_user_cancel;

			$user_author = new User($this->db);
			$user_author->fetch($this->fk_user_author);
			$this->user_author_infos = dolGetFirstLastname($user_author->firstname, $user_author->lastname);

			$user_approver = new User($this->db);
			$user_approver->fetch($this->fk_user_validator);
			$this->user_validator_infos = dolGetFirstLastname($user_approver->firstname, $user_approver->lastname);

			$this->fk_c_expensereport_statuts = $obj->fk_c_expensereport_statuts;
			$this->fk_c_paiement			  = $obj->fk_c_paiement;

			if ($this->fk_c_expensereport_statuts==5 || $this->fk_c_expensereport_statuts==6)
			{
				$user_valid = new User($this->db);
				$user_valid->fetch($this->fk_user_valid);
				$this->user_valid_infos = dolGetFirstLastname($user_valid->firstname, $user_valid->lastname);
			}

			if ($this->fk_c_expensereport_statuts==6)
			{
				$user_paid = new User($this->db);
				$user_paid->fetch($this->fk_user_paid);
				$this->user_paid_infos = dolGetFirstLastname($user_paid->firstname, $user_paid->lastname);
			}

			$this->libelle_statut 	= $obj->libelle_statut;
			$this->libelle_paiement = $obj->libelle_paiement;
			$this->code_statut 		= $obj->code_statut;
			$this->code_paiement 	= $obj->code_paiement;

			$this->lignes = array();

			$result=$this->fetch_lines();

			return 1;
		}
		else
		{
			$this->error=$db->error();
			return -1;
		}
	}


	/**
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *	Returns the label of a statut
	 *
	 *	@param      int		$status     id statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
			return $langs->trans($this->statuts[$status]);

		if ($mode == 1)
			return $langs->trans($this->statuts_short[$status]);

		if ($mode == 2)
			return img_picto($langs->trans($this->statuts_short[$status]), $this->statuts_logo[$status]).' '.$langs->trans($this->statuts_short[$status]);

		if ($mode == 3)
			return img_picto($langs->trans($this->statuts_short[$status]), $this->statuts_logo[$status]);

		if ($mode == 4)
			return img_picto($langs->trans($this->statuts_short[$status]),$this->statuts_logo[$status]).' '.$langs->trans($this->statuts[$status]);

		if ($mode == 5)
			return '<span class="hideonsmartphone">'.$langs->trans($this->statuts_short[$status]).' </span>'.img_picto($langs->trans($this->statuts_short[$status]),$this->statuts_logo[$status]);

	}



	/**
	 *
	 * @param unknown_type $projectid
	 * @param unknown_type $user
	 */
	function fetch_line_by_project($projectid,$user='')
	{
		global $conf,$db,$langs;

		$langs->load('trips');

		if($user->rights->expensereport->lire) {

		   $sql = "SELECT de.fk_expensereport, de.date, de.comments, de.total_ht, de.total_ttc";
   		$sql.= " FROM ".MAIN_DB_PREFIX."expensereport_det as de";
   		$sql.= " WHERE de.fk_projet = ".$projectid;

   		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
   		$result = $db->query($sql) ;
   		if ($result)
   		{
   			$num = $db->num_rows($result);
   			$i = 0;
   			$total_HT = 0;
   		   $total_TTC = 0;

   			while ($i < $num)
   			{

   			   $objp = $db->fetch_object($result);

   				$sql2 = "SELECT d.rowid, d.fk_user_author, d.ref_number, d.fk_c_expensereport_statuts";
   				$sql2.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
   				$sql2.= " WHERE d.rowid = '".$objp->fk_expensereport."'";

   				$result2 = $db->query($sql2);
   				$obj = $db->fetch_object($result2);

   				$objp->fk_user_author = $obj->fk_user_author;
   				$objp->ref_num = $obj->ref_number;
   				$objp->fk_c_expensereport_status = $obj->fk_c_expensereport_statuts;
   				$objp->rowid = $obj->rowid;

               $total_HT = $total_HT + $objp->total_ht;
               $total_TTC = $total_TTC + $objp->total_ttc;
   				$author = new User($db);
   				$author->fetch($objp->fk_user_author);

               print '<tr>';
                  print '<td><a href="'.DOL_URL_ROOT.'/expensereport/card.php?id='.$objp->rowid.'">'.$objp->ref_num.'</a></td>';
                  print '<td align="center">'.dol_print_date($objp->date,'day').'</td>';
                  print '<td>'.$author->getNomUrl().'</td>';
                  print '<td>'.$objp->comments.'</td>';
                  print '<td align="right">'.price($objp->total_ht).'</td>';
                  print '<td align="right">'.price($objp->total_ttc).'</td>';
                  print '<td align="right">';

                  switch($objp->fk_c_expensereport_status) {
                     case 4:
                        print img_picto($langs->trans('StatusOrderCanceled'),'statut5');
                        break;
                     case 1:
                        print $langs->trans('Draft').' '.img_picto($langs->trans('Draft'),'statut0');
                        break;
                     case 2:
                        print $langs->trans('TripForValid').' '.img_picto($langs->trans('TripForValid'),'statut3');;
                        break;
                     case 5:
                        print $langs->trans('TripForPaid').' '.img_picto($langs->trans('TripForPaid'),'statut3');
                        break;
                     case 6:
                        print $langs->trans('TripPaid').' '.img_picto($langs->trans('TripPaid'),'statut4');
                        break;
                  }
                  /*
                  if ($status==4) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
         			if ($status==1) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
         			if ($status==2) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
         			if ($status==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
         			if ($status==5) return img_picto($langs->trans('StatusOrderToBill'),'statut4');
         			if ($status==6) return img_picto($langs->trans('StatusOrderOnProcess'),'statut6');
                  */
                  print '</td>';
   				print '</tr>';

   				$i++;
   			}

   			print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Number").': '.$i.'</td>';
   			print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_HT).'</td>';
   			print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_TTC).'</td>';
   			print '<td>&nbsp;</td>';
   			print '</tr>';

   		}
   		else
   		{
   			$this->error=$db->error();
   			return -1;
   		}
	   }

	}

	function recalculer($id){
		$sql = 'SELECT tt.total_ht, tt.total_ttc, tt.total_tva';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as tt';
		$sql.= ' WHERE tt.'.$this->fk_element.' = '.$id;

		$total_ht = 0; $total_tva = 0; $total_ttc = 0;

		dol_syslog('ExpenseReport::recalculer sql='.$sql,LOG_DEBUG);

		$result = $this->db->query($sql);
		if($result):
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num):
				$objp = $this->db->fetch_object($result);
				$total_ht+=$objp->total_ht;
				$total_tva+=$objp->total_tva;
				$i++;
			endwhile;

			$total_ttc = $total_ht + $total_tva;
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
			$sql.= " total_ht = ".$total_ht;
			$sql.= " , total_ttc = ".$total_ttc;
			$sql.= " , total_tva = ".$total_tva;
			$sql.= " WHERE rowid = ".$id;
			$result = $this->db->query($sql);
			if($result):
				$this->db->free($result);
				return 1;
			else:
				$this->error=$this->db->error();
				dol_syslog('ExpenseReport::recalculer: Error '.$this->error,LOG_ERR);
				return -3;
			endif;
		else:
				$this->error=$this->db->error();
				dol_syslog('ExpenseReport::recalculer: Error '.$this->error,LOG_ERR);
				return -3;
		endif;
	}

	function fetch_lines()
	{
		$sql = ' SELECT de.rowid, de.comments, de.qty, de.value_unit, de.date,';
		$sql.= ' de.'.$this->fk_element.', de.fk_c_type_fees, de.fk_projet, de.fk_c_tva,';
		$sql.= ' de.total_ht, de.total_tva, de.total_ttc,';
		$sql.= ' ctf.code as code_type_fees, ctf.label as libelle_type_fees,';
		$sql.= ' ctv.taux as taux_tva,';
		$sql.= ' p.ref as ref_projet, p.title as title_projet';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as de';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_fees ctf ON de.fk_c_type_fees = ctf.id';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_tva ctv ON de.fk_c_tva = ctv.rowid';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet p ON de.fk_projet = p.rowid';
		$sql.= ' WHERE de.'.$this->fk_element.' = '.$this->id;

		dol_syslog('ExpenseReport::fetch_lines sql='.$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$deplig = new ExpenseReportLigne($this->db);

				$deplig->rowid	    	= $objp->rowid;
				$deplig->comments		= $objp->comments;
				$deplig->qty			= $objp->qty;
				$deplig->value_unit 	= $objp->value_unit;
				$deplig->date			= $objp->date;

				$deplig->fk_expensereport = $objp->fk_expensereport;
				$deplig->fk_c_type_fees = $objp->fk_c_type_fees;
				$deplig->fk_projet		= $objp->fk_projet;
				$deplig->fk_c_tva		= $objp->fk_c_tva;

				$deplig->total_ht		= $objp->total_ht;
				$deplig->total_tva		= $objp->total_tva;
				$deplig->total_ttc		= $objp->total_ttc;

				$deplig->type_fees_code 	= $objp->code_type_fees;
				$deplig->type_fees_libelle 	= $objp->libelle_type_fees;
				$deplig->tva_taux			= $objp->taux_tva;
				$deplig->projet_ref			= $objp->ref_projet;
				$deplig->projet_title		= $objp->title_projet;

				$this->lignes[$i] = $deplig;
				$i++;
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog('ExpenseReport::fetch_lines: Error '.$this->error,LOG_ERR);
			return -3;
		}
	}

	function delete($rowid=0)
	{
		global $user,$langs,$conf;

		if (!$rowid) $rowid=$this->id;

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element_line.' WHERE '.$this->fk_element.' = '.$rowid;
		if ($this->db->query($sql))
		{
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE rowid = '.$rowid;
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog("ExpenseReport.class::delete ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -6;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog("ExpenseReport.class::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -4;
		}
	}

	function set_save($user){
		global $conf,$langs;

		$expld_car = (empty($conf->global->NDF_EXPLODE_CHAR))?"-":$conf->global->NDF_EXPLODE_CHAR;

		// Sélection du numéro de ref suivant
		$ref_next = $this->getNextNumRef();
		$ref_number_int = ($this->ref_number+1)-1;

		// Sélection de la date de début de la NDF
		$sql = 'SELECT date_debut';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		$objp = $this->db->fetch_object($result);
		$this->date_debut = $objp->date_debut;
		$expld_date_debut = explode("-",$this->date_debut);
		$this->date_debut = $expld_date_debut[0].$expld_date_debut[1].$expld_date_debut[2];

		// Création du ref_number suivant
		if($ref_next):
			$this->ref_number = strtoupper($user->login).$expld_car."NDF".$this->ref_number.$expld_car.$this->date_debut;
		endif;

		if ($this->fk_c_expensereport_statuts != 2):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET ref_number = '".$this->ref_number."', fk_c_expensereport_statuts = 2";
			$sql.= " ,ref_number_int = $ref_number_int";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_save sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;

		else:

			dol_syslog(get_class($this)."::set_save expensereport already with save status", LOG_WARNING);

		endif;
	}

	function set_save_from_refuse($user){
		global $conf,$langs;

		// Sélection de la date de début de la NDF
		$sql = 'SELECT date_debut';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql.= ' WHERE rowid = '.$this->id;

		$result = $this->db->query($sql);

		$objp = $this->db->fetch_object($result);

		$this->date_debut = $objp->date_debut;
		$expld_date_debut = explode("-",$this->date_debut);
		$this->date_debut = $expld_date_debut[0].$expld_date_debut[1].$expld_date_debut[2];

		if ($this->fk_c_expensereport_statuts != 2):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 2";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_save_from_refuse sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;

		else:

			dol_syslog(get_class($this)."::set_save_from_refuse expensereport already with save status", LOG_WARNING);

		endif;
	}

	function set_valide($user){
		// date de validation
		$this->date_valide = $this->db->idate(gmmktime());
		if ($this->fk_c_expensereport_statuts != 5):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET ref_number = '".$this->ref_number."', fk_c_expensereport_statuts = 5, fk_user_valid = ".$user->id;
			$sql.= ', date_valide='.$this->date_valide;
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_valide sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		else:
			dol_syslog(get_class($this)."::set_valide expensereport already with valide status", LOG_WARNING);
		endif;
	}

	/**
	 * Refuse
	 *
	 * @param User		$user		User
	 * @param Details	$details	Details
	 */
	function set_refuse($user,$details)
	{
		// date de refus
		$this->date_refuse = $this->db->idate(gmmktime());
		if ($this->fk_c_expensereport_statuts != 99):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET ref_number = '".$this->ref_number."', fk_c_expensereport_statuts = 99, fk_user_refuse = ".$user->id;
			$sql.= ', date_refuse='.$this->date_refuse;
			$sql.= ", detail_refuse='".addslashes($details)."'";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_refuse sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		else:
			dol_syslog(get_class($this)."::set_refuse expensereport already with refuse status", LOG_WARNING);
		endif;
	}

	function set_paid($user){
		$this->date_paiement = $this->db->idate(gmmktime());
		if ($this->fk_c_expensereport_statuts != 6):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 6, fk_user_paid = ".$user->id;
			$sql.= ', date_paiement='.$this->date_paiement;
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_paid sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		else:
			dol_syslog(get_class($this)."::set_paid expensereport already with paid status", LOG_WARNING);
		endif;
	}

	function set_unpaid($user)
	{
		if ($this->fk_c_expensereport_statuts != 5)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 5";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_unpaid sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		}
		else
		{
			dol_syslog(get_class($this)."::set_unpaid expensereport already with unpaid status", LOG_WARNING);
		}
	}

	function set_draft($user)
	{
		if ($this->fk_c_expensereport_statuts != 1)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 1,";
			//$sql.= " , ref_number = '(PROV".$this->id.")', ref_number_int = 0";
			$sql.= " ref_number_int = 0";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_draft sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)) return 1;
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::set_draft expensereport already with draft status", LOG_WARNING);
		}
	}

	function set_to_valide($user)
	{
		if ($this->fk_c_expensereport_statuts != 2):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 2, fk_user_validator = ".$this->fk_user_validator;
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_to_valide sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		else:
			dol_syslog(get_class($this)."::set_to_valide expensereport already with to-valide status", LOG_WARNING);
		endif;
	}

	function set_cancel($user,$detail){
		$this->date_cancel = $this->db->idate(gmmktime());
		if ($this->fk_c_expensereport_statuts != 4):
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql.= " SET fk_c_expensereport_statuts = 4, fk_user_cancel = ".$user->id;
			$sql.= ', date_cancel='.$this->date_cancel;
			$sql.= " ,detail_cancel='".addslashes($detail)."'";
			$sql.= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_cancel sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql)):
				return 1;
			else:
				$this->error=$this->db->error();
				return -1;
			endif;
		else:
			dol_syslog(get_class($this)."::set_cancel expensereport already with cancel status", LOG_WARNING);
		endif;
	}

	function getNextNumRef(){
		global $conf;

		$expld_car = (empty($conf->global->NDF_EXPLODE_CHAR))?"-":$conf->global->NDF_EXPLODE_CHAR;
		$num_car = (empty($conf->global->NDF_NUM_CAR_REF))?"5":$conf->global->NDF_NUM_CAR_REF;

		$sql = 'SELECT de.ref_number_int';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' de';
		$sql.= ' ORDER BY de.ref_number_int DESC';

		$result = $this->db->query($sql);

		if($this->db->num_rows($result) > 0):
			$objp = $this->db->fetch_object($result);
			$this->ref_number = $objp->ref_number_int;
			$this->ref_number++;
			while(strlen($this->ref_number) < $num_car):
				$this->ref_number = "0".$this->ref_number;
			endwhile;
		else:
			$this->ref_number = 1;
			while(strlen($this->ref_number) < $num_car):
				$this->ref_number = "0".$this->ref_number;
			endwhile;
		endif;

		if ($result):
			return 1;
		else:
			$this->error=$this->db->error();
			return -1;
		endif;
	}


	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/expensereport/card.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='trip';

		$label=$langs->trans("Show").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}


	function update_totaux_add($ligne_total_ht,$ligne_total_tva){
		$this->total_ht = $this->total_ht + $ligne_total_ht;
		$this->total_tva = $this->total_tva + $ligne_total_tva;
		$this->total_ttc = $this->total_ht + $this->total_tva;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql.= " total_ht = ".$this->total_ht;
		$sql.= " , total_ttc = ".$this->total_ttc;
		$sql.= " , total_tva = ".$this->total_tva;
		$sql.= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result):
			return 1;
		else:
			$this->error=$this->db->error();
			return -1;
		endif;
	}

	function update_totaux_del($ligne_total_ht,$ligne_total_tva){
		$this->total_ht = $this->total_ht - $ligne_total_ht;
		$this->total_tva = $this->total_tva - $ligne_total_tva;
		$this->total_ttc = $this->total_ht + $this->total_tva;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql.= " total_ht = ".$this->total_ht;
		$sql.= " , total_ttc = ".$this->total_ttc;
		$sql.= " , total_tva = ".$this->total_tva;
		$sql.= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result):
			return 1;
		else:
			$this->error=$this->db->error();
			return -1;
		endif;
	}



	function updateline($rowid, $type_fees_id, $projet_id, $c_tva, $comments, $qty, $value_unit, $date, $expensereport_id)
	{
		if ($this->fk_c_expensereport_statuts==1 || $this->fk_c_expensereport_statuts==99)
		{
			$this->db->begin();

			// Select du taux de tva par rapport au code
			$sql = "SELECT t.taux as taux_tva";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_tva t";
			$sql.= " WHERE t.rowid = ".$c_tva;
			$result = $this->db->query($sql);
			$objp_tva = $this->db->fetch_object($result);

			// calcul de tous les totaux de la ligne
			$total_ttc	= $qty*$value_unit;
			$total_ttc 	= number_format($total_ttc,2,'.','');

			$tx_tva = $objp_tva->taux_tva/100;
			$tx_tva	= $tx_tva + 1;
			$total_ht 	= $total_ttc/$tx_tva;
			$total_ht 	= number_format($total_ht,2,'.','');

			$total_tva = $total_ttc - $total_ht;
			// fin calculs

			$ligne = new ExpenseReportLigne($this->db);
			$ligne->comments		= $comments;
			$ligne->qty				= $qty;
			$ligne->value_unit 		= $value_unit;
			$ligne->date			= $date;

			$ligne->fk_expensereport 	= $expensereport_id;
			$ligne->fk_c_type_fees 	= $type_fees_id;
			$ligne->fk_projet		= $projet_id;
			$ligne->fk_c_tva		= $c_tva;

			$ligne->total_ht		= $total_ht;
			$ligne->total_tva		= $total_tva;
			$ligne->total_ttc		= $total_ttc;
			$ligne->tva_taux		= $objp_tva->taux_tva;
			$ligne->rowid			= $rowid;

			// Select des infos sur le type fees
			$sql = "SELECT c.code as code_type_fees, c.label as libelle_type_fees";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees c";
			$sql.= " WHERE c.id = ".$type_fees_id;
			$result = $this->db->query($sql);
			$objp_fees = $this->db->fetch_object($result);
			$ligne->type_fees_code 		= $objp_fees->code_type_fees;
			$ligne->type_fees_libelle 	= $objp_fees->libelle_type_fees;

			// Select des informations du projet
			$sql = "SELECT p.ref as ref_projet, p.title as title_projet";
			$sql.= " FROM ".MAIN_DB_PREFIX."projet p";
			$sql.= " WHERE p.rowid = ".$projet_id;
			$result = $this->db->query($sql);
			$objp_projet = $this->db->fetch_object($result);
			$ligne->projet_ref			= $objp_projet->ref_projet;
			$ligne->projet_title		= $objp_projet->title_projet;

			$result = $ligne->update();
			if ($result > 0):
				$this->db->commit();
				return 1;
			else:
				$this->error=$ligne->error;
				$this->db->rollback();
				return -2;
			endif;

		}
	}

	/**
	 * deleteline
	 *
	 * @param 	int		$rowid		Row id
	 * @param 	User	$user		User
	 * @return 	int					<0 if KO, >0 if OK
	 */
	function deleteline($rowid, $user='')
	{
		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql.= ' WHERE rowid = '.$rowid;

		dol_syslog(get_class($this)."::deleteline sql=".$sql);
		$result = $this->db->query($sql);
		if (!$result)
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::deleteline  Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * periode_existe
	 *
	 * @param 	User	$user			User
	 * @param 	Date	$date_debut		Start date
	 * @param 	Date	$date_fin		End date
	 * @return	int						<0 if KO, >0 if OK
	 */
	function periode_existe($user,$date_debut,$date_fin)
	{
		$sql = "SELECT rowid,date_debut,date_fin";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql.= " WHERE fk_user_author = '{$user->id}'";

		dol_syslog(get_class($this)."::periode_existe sql=".$sql);
		$result = $this->db->query($sql);
		if($result)
		{
			$num_lignes = $this->db->num_rows($result); $i = 0;

			if ($num_lignes>0)
			{
				$date_d_form = explode("-",$date_debut); 	// 1
				$date_f_form = explode("-",$date_fin);		// 2
				$date_d_form = mktime(12,0,0,$date_d_form[1],$date_d_form[2],$date_d_form[0]);
				$date_f_form = mktime(12,0,0,$date_f_form[1],$date_f_form[2],$date_f_form[0]);

				$existe = false;

				while ($i < $num_lignes)
				{
					$objp = $this->db->fetch_object($result);

					$date_d_req = explode("-",$objp->date_debut); // 3
					$date_f_req = explode("-",$objp->date_fin);	  // 4
					$date_d_req = mktime(12,0,0,$date_d_req[1],$date_d_req[2],$date_d_req[0]);
					$date_f_req = mktime(12,0,0,$date_f_req[1],$date_f_req[2],$date_f_req[0]);

					if(!($date_f_form < $date_d_req OR $date_d_form > $date_f_req)) $existe = true;

					$i++;
				}

				if($existe) return 1;
				else return 0;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::periode_existe  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Return list of people with permission to validate trips and expenses
	 *
	 * @return	array		Array of user ids
	 */
	function fetch_users_approver_expensereport()
	{
		$users_validator=array();

		$sql = "SELECT fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql.= " WHERE ur.fk_id = rd.id and module = 'expensereport' AND perms = 'to_validate'";					// Permission 'Approve';

		dol_syslog(get_class($this)."::fetch_users_approver_expensereport sql=".$sql);
		$result = $this->db->query($sql);
		if($result)
		{
			$num_lignes = $this->db->num_rows($result); $i = 0;
			while ($i < $num_lignes)
			{
				$objp = $this->db->fetch_object($result);
				array_push($users_validator,$objp->fk_user);
				$i++;
			}
			return $users_validator;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_users_approver_expensereport  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}
}


/**
 * Class of expense report details lines
 */
class ExpenseReportLigne
{
	var $db;
	var $error;

	var $rowid;
	var $comments;
	var $qty;
	var $value_unit;
	var $date;

	var $fk_c_tva;
	var $fk_c_type_fees;
	var $fk_projet;
	var $fk_expensereport;

	var $type_fees_code;
	var $type_fees_libelle;

	var $projet_ref;
	var $projet_title;

	var $tva_taux;

	var $total_ht;
	var $total_tva;
	var $total_ttc;

	/**
	 * Constructor
	 *
	 * @param DoliDB	$db		Handlet database
	 */
	function ExpenseReportLigne($db)
	{
		$this->db= $db;
	}

	/**
	 * fetch record
	 *
	 * @param	int		$rowid		Row id to fetch
	 * @return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_projet, fde.date,';
		$sql.= ' fde.fk_c_tva, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc,';
		$sql.= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
		$sql.= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref,';
		$sql.= ' tva.rowid as tva_id, tva.taux as tva_taux';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport_det fde';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_fees ctf ON fde.fk_c_type_fees=ctf.id';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet pjt ON fde.fk_projet=pjt.rowid';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_tva tva ON fde.fk_c_tva=tva.rowid';
		$sql.= ' WHERE fde.rowid = '.$rowid;

		$result = $this->db->query($sql);

		if($result) {
			$objp = $this->db->fetch_object($result);

			$this->rowid = $objp->rowid;
			$this->fk_expensereport = $objp->fk_expensereport;
			$this->comments = $objp->comments;
			$this->qty = $objp->qty;
			$this->date = $objp->date;
			$this->value_unit = $objp->value_unit;
			$this->fk_c_tva = $objp->fk_c_tva;
			$this->fk_c_type_fees = $objp->fk_c_type_fees;
			$this->fk_projet = $objp->fk_projet;
			$this->type_fees_code = $objp->type_fees_code;
			$this->type_fees_libelle = $objp->type_fees_libelle;
			$this->projet_ref = $objp->projet_ref;
			$this->projet_title = $objp->projet_title;
			$this->tva_taux = $objp->tva_taux;
			$this->total_ht = $objp->total_ht;
			$this->total_tva = $objp->total_tva;
			$this->total_ttc = $objp->total_ttc;

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * insert
	 *
	 * @param 	int		$notrigger		1=No trigger
	 * @return 	int						<0 if KO, >0 if OK
	 */
	function insert($notrigger=0)
	{
		global $langs,$user,$conf;

		dol_syslog("ExpenseReportLigne::Insert rang=".$this->rang, LOG_DEBUG);

		// Clean parameters
		$this->comments=trim($this->comments);
		if (!$this->value_unit_HT) $this->value_unit_HT=0;
		$this->qty = price2num($this->qty);

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'expensereport_det';
		$sql.= ' (fk_expensereport, fk_c_type_fees, fk_projet,';
		$sql.= ' fk_c_tva, comments, qty, value_unit, total_ht, total_tva, total_ttc, date)';
		$sql.= " VALUES (".$this->fk_expensereport.",";
		$sql.= " ".$this->fk_c_type_fees.",";
		$sql.= " ".($this->fk_projet>0?$this->fk_projet:'null').",";
		$sql.= " ".$this->fk_c_tva.",";
		$sql.= " '".$this->db->escape($this->comments)."',";
		$sql.= " ".$this->qty.",";
		$sql.= " ".$this->value_unit.",";
		$sql.= " ".$this->total_ht.",";
		$sql.= " ".$this->total_tva.",";
		$sql.= " ".$this->total_ttc.",";
		$sql.= "'".$this->date."'";
		$sql.= ")";

		dol_syslog("ExpenseReportLigne::insert sql=".$sql);

		$resql=$this->db->query($sql);

		if ($resql):
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'expensereport_det');
			$this->db->commit();
			return $this->rowid;
		else:
			$this->error=$this->db->error();
			dol_syslog("ExpenseReportLigne::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		endif;
	}

	/**
	 * update
	 *
	 * @param	User	$user		User
	 * @return 	int					<0 if KO, >0 if OK
	 */
	function update($user)
	{
		global $user,$langs,$conf;

		// Clean parameters
		$this->comments=trim($this->comments);

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det SET";
		$sql.= " comments='".$this->db->escape($this->comments)."'";
		$sql.= ",value_unit=".$this->value_unit."";
		$sql.= ",qty=".$this->qty."";
		if ($this->date) { $sql.= ",date='".$this->date."'"; }
		else { $sql.=',date=null'; }
		$sql.= ",total_ht=".$this->total_ht."";
		$sql.= ",total_tva=".$this->total_tva."";
		$sql.= ",total_ttc=".$this->total_ttc."";
		if ($this->fk_c_type_fees) $sql.= ",fk_c_type_fees=".$this->fk_c_type_fees;
		else $sql.= ",fk_c_type_fees=null";
		if ($this->fk_projet) $sql.= ",fk_projet=".$this->fk_projet;
		else $sql.= ",fk_projet=null";
		if ($this->fk_c_tva) $sql.= ",fk_c_tva=".$this->fk_c_tva;
		else $sql.= ",fk_c_tva=null";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("ExpenseReportLigne::update sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("ExpenseReportLigne::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}
}


/**
 *    Retourne la liste deroulante des differents etats d'une note de frais.
 *    Les valeurs de la liste sont les id de la table c_expensereport_statuts
 *
 *    @param    int		$selected    	etat pre-selectionne
 *    @param	string	$htmlname		Name of HTML select
 *    @param	int		$useempty		1=Add empty line
 *    @return	string					HTML select with sattus
 */
function select_expensereport_statut($selected='',$htmlname='fk_c_expensereport_statuts',$useempty=1)
{
    global $db;

    $tmpep=new ExpenseReport($db);

	print '<select class="flat" name="'.$htmlname.'">';
	if ($useempty) print '<option value="-1">&nbsp;</option>';
	foreach ($tmpep->statuts as $key => $val)
	{
		if ($selected == $key)
		{
			print '<option value="'.$key.'" selected="true">';
		}
		else
		{
			print '<option value="'.$key.'">';
		}
		print $val;
		print '</option>';
	}
	print '</select>';
}

/**
 * select_projet
 * TODO Utiliser le select project officiel
 *
 * @param 	int		$selected		Id selected
 * @param 	string	$filter			Filter
 * @param 	string	$htmlname		Select name
 * @return	int						<0 if KO, >0 if OK
 */
function select_projet($selected='',$filter='', $htmlname='fk_projet')
{
   global $conf,$user,$langs,$db;

   $out='';

	$sql = "SELECT p.rowid, p.ref, p.title";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " WHERE p.entity = ".$conf->entity;
	if (is_numeric($selected)) $sql.= " AND p.rowid = ".$selected;

   dol_syslog("Form::select_projet sql=".$sql);
   $resql=$db->query($sql);
   if ($resql)
   {
       if ($conf->use_javascript_ajax && ! $forcecombo)
       {
           	$minLength = (is_numeric($conf->global->COMPANY_USE_SEARCH_TO_SELECT)?$conf->global->COMPANY_USE_SEARCH_TO_SELECT:2);

       		$projetid = 0;

			if ($selected)
			{
				$obj = $db->fetch_object($resql);
				$projetid = $obj->rowid?$obj->rowid:'';
			}

           $out.= "\n".'<!-- Input text for third party with Ajax.Autocompleter (select_techno_ajax) -->'."\n";
           $out.= '<table class="nobordernopadding"><tr class="nocellnopadd">';
           $out.= '<td class="nobordernopadding">';
           if ($projetid == 0) {
           	$out.= '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="" />';
           } else {
               $out.= '<input type="text" size="30" id="search_'.$htmlname.'" name="search_'.$htmlname.'" value="'.$obj->ref.' - '.$obj->title.'" />';
           }
           $out.= ajax_autocompleter(($projetid?$projetid:-1),$htmlname,dol_buildpath('/expensereport/ajax/ajaxprojet.php',1).'?filter='.urlencode($filter), '', $minLength);
           $out.= '</td>';
           $out.= '</tr>';
           $out.= '</table>';
       }

   }
   else
   {
       dol_print_error($db);
   }

   return $out;
}

/**
 *	Return list of types of notes with select value = id
 *
 *	@param      int		$selected       Preselected type
 *	@param      string	$htmlname       Name of field in form
 * 	@param		int		$showempty		Add an empty field
 *  @return		string					Select html
 */
function select_type_fees_id($selected='',$htmlname='type',$showempty=0)
{
	global $db,$langs,$user;
	$langs->load("trips");

	print '<select class="flat" name="'.$htmlname.'">';
	if ($showempty)
	{
		print '<option value="-1"';
		if ($selected == -1) print ' selected="true"';
		print '>&nbsp;</option>';
	}

	$sql = "SELECT c.code, c.label as type,c.id FROM ".MAIN_DB_PREFIX."c_type_fees as c";
	$sql.= " ORDER BY c.label ASC";
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			print '<option value="'.$obj->id.'"';
			if ($obj->code == $selected) print ' selected="true"';
			print '>';
			if ($obj->code != $langs->trans($obj->code)) print $langs->trans($obj->code);
			else print $langs->trans($obj->type);
			$i++;
		}
	}
	print '</select>';
}
