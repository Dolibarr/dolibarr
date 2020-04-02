<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Dimitri Mouillard 	<dmouillard@teclib.com>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018      Charlene Benke		<charlie@patas-monkey.com>
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
 * or see https://www.gnu.org/
 */

/**
 * 	  \defgroup   holiday 	Module holiday
 *    \brief      Module de gestion des congés
 *    \file       htdocs/core/modules/modHoliday.class.php
 *    \ingroup    holiday
 *    \brief      Description and activation file for module holiday
 */
include_once DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php";


/**
 *		Description and activation class for module holiday
 */
class modHoliday extends DolibarrModules
{
	/**
	 *  Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf, $user;   // Required by some include code

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 20000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'holiday';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "hr";
		$this->module_position = '42';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Leave requests";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='holiday';

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array("/holiday/temp");
		$r=0;

		// Config pages
		$this->config_page_url = array("holiday.php");


		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		// $this->config_page_url = array("holiday.php?leftmenu=setup@holiday");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("holiday");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r=0;

		$this->const[$r][0] = "HOLIDAY_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_holiday_madonna";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des congés';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "HOLIDAY_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "celebrate";
		$this->const[$r][3] = 'Name of PDF model of holiday';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "HOLIDAY_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/holiday";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		// Array to add new pages in new tabs
		//$this->tabs[] = array('data'=>'user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->read:/holiday/list.php?mainmenu=hrm&id=__ID__');	// We avoid to get one tab for each module. RH data are already in RH tab.
		$this->tabs[] = array();  					// To add a new tab identified by code tabname1

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;


		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		$this->rights[$r][0] = 20001; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read your own leave requests';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20002; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify your own leave requests';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20003; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete leave requests';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20007;
		$this->rights[$r][1] = 'Approve leave requests';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'approve';
		$r++;

		$this->rights[$r][0] = 20004; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read leave requests for everybody';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read_all';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20005; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify leave requests for everybody';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write_all';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20006; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Setup leave requests of users (setup and update balance)';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'define_holiday';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		$r=0;

		$r++;
		$this->export_code[$r]='leaverequest_'.$r;
		$this->export_label[$r]='ListeCP';
		$this->export_icon[$r]='holiday';
		$this->export_permission[$r]=array(array("holiday","read_all"));
		$this->export_fields_array[$r]=array(
			'd.rowid'=>"LeaveId",'d.fk_type'=>'TypeOfLeaveId','t.code'=>'TypeOfLeaveCode','t.label'=>'TypeOfLeaveLabel','d.fk_user'=>'UserID',
			'u.lastname'=>'Lastname','u.firstname'=>'Firstname','u.login'=>"Login",'d.date_debut'=>'DateStart','d.date_fin'=>'DateEnd','d.halfday'=>'HalfDay','none.num_open_days'=>'NbUseDaysCP',
			'd.date_valid'=>'DateApprove','d.fk_validator'=>"UserForApprovalID",'ua.lastname'=>"UserForApprovalLastname",'ua.firstname'=>"UserForApprovalFirstname",
			'ua.login'=>"UserForApprovalLogin",'d.description'=>'Description','d.statut'=>'Status'
		);
		$this->export_TypeFields_array[$r]=array(
			'd.rowid'=>"Numeric",'t.code'=>'Text', 't.label'=>'Text','d.fk_user'=>'Numeric',
			'u.lastname'=>'Text','u.firstname'=>'Text','u.login'=>"Text",'d.date_debut'=>'Date','d.date_fin'=>'Date','none.num_open_days'=>'NumericCompute',
			'd.date_valid'=>'Date','d.fk_validator'=>"Numeric",'ua.lastname'=>"Text",'ua.firstname'=>"Text",
			'ua.login'=>"Text",'d.description'=>'Text','d.statut'=>'Numeric'
		);
		$this->export_entities_array[$r]=array(
			'u.lastname'=>'user','u.firstname'=>'user','u.login'=>'user','ua.lastname'=>'user','ua.firstname'=>'user','ua.login'=>'user'
		);
		$this->export_alias_array[$r]=array('d.rowid'=>"idholiday");
		$this->export_special_array[$r] = array('none.num_open_days'=>'getNumOpenDays');
		$this->export_dependencies_array[$r]=array(); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$keyforselect='holiday'; $keyforelement='holiday'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'holiday as d';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'holiday_extrafields as extra on d.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_holiday_types as t ON t.rowid = d.fk_type';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua ON ua.rowid = d.fk_validator,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'user as u';
		$this->export_sql_end[$r] .=' WHERE d.fk_user = u.rowid';
		$this->export_sql_end[$r] .=' AND d.entity IN ('.getEntity('holiday').')';

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array(
		//	's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone',
		//	's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode',
		//	's.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",
		//	'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus',
		//	'f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",
		//	'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",
		//	'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef'
		//);
		// $this->export_entities_array[$r]=array(
		//	's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company',
		//	's.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company',
		//	'f.rowid'=>"invoice",'f.ref'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",
		//	'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",
		//	'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",
		//	'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product'
		//);
		// $this->export_alias_array[$r]=array(
		//	's.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.zip'=>'soc_zip','s.town'=>'soc_town','s.fk_pays'=>'soc_pays','s.phone'=>'soc_tel',
		//	's.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','s.code_compta'=>'soc_customer_accountancy',
		//	's.code_compta_fournisseur'=>'soc_supplier_accountancy','f.rowid'=>"invoiceid",'f.ref'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",
		//	'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid',
		//	'fd.description'=>"linedescription",'fd.price'=>"lineprice",'fd.total_ht'=>"linetotalht",'fd.total_tva'=>"linetotaltva",'fd.total_ttc'=>"linetotalttc",
		//	'fd.tva_tx'=>"linevatrate",'fd.qty'=>"lineqty",'fd.date_start'=>"linedatestart",'fd.date_end'=>"linedateend",'fd.fk_product'=>'productid',
		//	'p.ref'=>'productref'
		//);
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		//ODT template
		/*$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/holiday/template_holiday.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/holiday';
		$dest=$dirodt.'/template_order.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src, $dest, 0, 0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}
        */

		$sql = array(
		//	"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'holiday' AND entity = ".$conf->entity,
		//	"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','holiday',".$conf->entity.")"
		);

		return $this->_init($sql, $options);
	}
}
