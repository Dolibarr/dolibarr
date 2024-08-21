<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023       Nick Fragoulis
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

/**
 *	\file       htdocs/compta/facture/class/facture-rec.class.php
 *	\ingroup    invoice
 *	\brief      File of class to manage recurring invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to manage invoice templates
 */
class FactureRec extends CommonInvoice
{
	const TRIGGER_PREFIX = 'BILLREC';
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'facturerec';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facture_rec';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'facturedet_rec';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_facture';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'bill';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'titre';

	/**
	 * @var string The label of recurring invoice
	 */
	public $title;

	/**
	 * @var string 	The label of recurring invoice
	 * @deprecated 	Use $title instead
	 */
	public $titre;

	/**
	 * @var double
	 */
	public $multicurrency_subprice;
	public $socid;
	public $number;
	public $date;
	//public $remise;
	//public $remise_absolue;
	//public $remise_percent;

	/**
	 * @deprecated
	 * @see $total_ht
	 */
	public $total;

	/**
	 * @deprecated
	 * @see $total_tva
	 */
	public $tva;

	public $date_last_gen;
	public $date_when;
	public $nb_gen_done;
	public $nb_gen_max;

	public $user_author;

	/**
	 * @var int Frequency
	 */
	public $frequency;

	/**
	 * @var string Unit frequency
	 */
	public $unit_frequency;

	public $rang;

	/**
	 * @var int special code
	 */
	public $special_code;

	public $usenewprice = 0;

	/**
	 * @var int Deadline for payment
	 */
	public $date_lim_reglement;
	public $cond_reglement_code; // Code in llx_c_paiement
	public $mode_reglement_code; // Code in llx_c_paiement

	public $suspended; // status

	public $auto_validate; // 0 to create in draft, 1 to create and validate the new invoice
	public $generate_pdf; // 1 to generate PDF on invoice generation (default)


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'titre' => array('type' => 'varchar(100)', 'label' => 'Titre', 'enabled' => 1, 'showoncombobox' => 1, 'visible' => -1, 'position' => 15),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 20, 'index' => 1),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'notnull' => 1, 'position' => 25),
		'subtype' => array('type' => 'smallint(6)', 'label' => 'InvoiceSubtype', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 30),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 35),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'Tva', 'enabled' => 1, 'visible' => -1, 'position' => 55, 'isameasure' => 1),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'Localtax1', 'enabled' => 1, 'visible' => -1, 'position' => 60, 'isameasure' => 1),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'Localtax2', 'enabled' => 1, 'visible' => -1, 'position' => 65, 'isameasure' => 1),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'Total', 'enabled' => 1, 'visible' => -1, 'position' => 70, 'isameasure' => 1),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'Total ttc', 'enabled' => 1, 'visible' => -1, 'position' => 75, 'isameasure' => 1),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fk user author', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Fk projet', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 85),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'Fk cond reglement', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'Fk mode reglement', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'date_lim_reglement' => array('type' => 'date', 'label' => 'Date lim reglement', 'enabled' => 1, 'visible' => -1, 'position' => 100),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 105),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 110),
		'modelpdf' => array('type' => 'varchar(255)', 'label' => 'Modelpdf', 'enabled' => 1, 'visible' => -1, 'position' => 115),
		'date_when' => array('type' => 'datetime', 'label' => 'Date when', 'enabled' => 1, 'visible' => -1, 'position' => 130),
		'date_last_gen' => array('type' => 'datetime', 'label' => 'Date last gen', 'enabled' => 1, 'visible' => -1, 'position' => 135),
		'nb_gen_done' => array('type' => 'integer', 'label' => 'Nb gen done', 'enabled' => 1, 'visible' => -1, 'position' => 140),
		'nb_gen_max' => array('type' => 'integer', 'label' => 'Nb gen max', 'enabled' => 1, 'visible' => -1, 'position' => 145),
		'frequency' => array('type' => 'integer', 'label' => 'Frequency', 'enabled' => 1, 'visible' => -1, 'position' => 150),
		'unit_frequency' => array('type' => 'varchar(2)', 'label' => 'UnitFrequency', 'enabled' => 1, 'visible' => -1, 'position' => 152),
		'usenewprice' => array('type' => 'integer', 'label' => 'UseNewPrice', 'enabled' => 1, 'visible' => 0, 'position' => 155),
		'revenuestamp' => array('type' => 'double(24,8)', 'label' => 'RevenueStamp', 'enabled' => 1, 'visible' => -1, 'position' => 160, 'isameasure' => 1),
		'auto_validate' => array('type' => 'integer', 'label' => 'Auto validate', 'enabled' => 1, 'visible' => -1, 'position' => 165),
		'generate_pdf' => array('type' => 'integer', 'label' => 'Generate pdf', 'enabled' => 1, 'visible' => -1, 'position' => 170),
		'fk_account' => array('type' => 'integer', 'label' => 'Fk account', 'enabled' => 'isModEnabled("bank")', 'visible' => -1, 'position' => 175),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'Fk multicurrency', 'enabled' => 1, 'visible' => -1, 'position' => 180),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'Multicurrency code', 'enabled' => 1, 'visible' => -1, 'position' => 185),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'Multicurrency tx', 'enabled' => 1, 'visible' => -1, 'position' => 190, 'isameasure' => 1),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'Multicurrency total ht', 'enabled' => 1, 'visible' => -1, 'position' => 195, 'isameasure' => 1),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'Multicurrency total tva', 'enabled' => 1, 'visible' => -1, 'position' => 200, 'isameasure' => 1),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'Multicurrency total ttc', 'enabled' => 1, 'visible' => -1, 'position' => 205, 'isameasure' => 1),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 210),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 215),
		'suspended' => array('type' => 'integer', 'label' => 'Suspended', 'enabled' => 1, 'visible' => -1, 'position' => 225),
	);
	// END MODULEBUILDER PROPERTIES

	const STATUS_NOTSUSPENDED = 0;
	const STATUS_SUSPENDED = 1;



	/**
	 *	Constructor
	 *
	 * 	@param		DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * 	Create a predefined invoice
	 *
	 * 	@param		User	$user		User object
	 * 	@param		int		$facid		Id of source invoice
	 *  @param		int		$notrigger	No trigger
	 *  @param		array	$onlylines	Only the lines of the array
	 *	@return		int					Return integer <0 if KO, id of invoice created if OK
	 */
	public function create($user, $facid, $notrigger = 0, $onlylines = array())
	{
		global $conf;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->titre = trim(isset($this->titre) ? $this->titre : $this->title); // deprecated
		$this->title = trim($this->title);
		$this->usenewprice = empty($this->usenewprice) ? 0 : $this->usenewprice;
		if (empty($this->suspended)) {
			$this->suspended = 0;
		}

		// No frequency defined then no next date to execution
		if (empty($this->frequency)) {
			$this->frequency = 0;
			$this->date_when = null;
		}

		$this->frequency = abs($this->frequency);
		$this->nb_gen_done = 0;
		$this->nb_gen_max = empty($this->nb_gen_max) ? 0 : $this->nb_gen_max;
		$this->auto_validate = empty($this->auto_validate) ? 0 : $this->auto_validate;
		$this->generate_pdf = empty($this->generate_pdf) ? 0 : $this->generate_pdf;

		$this->db->begin();

		// Load invoice template
		$facsrc = new Facture($this->db);
		$result = $facsrc->fetch($facid);
		if ($result > 0) {
			$this->socid = $facsrc->socid;

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_rec (";
			$sql .= "titre";
			$sql .= ", fk_soc";
			$sql .= ", subtype";
			$sql .= ", entity";
			$sql .= ", datec";
			$sql .= ", amount";
			//$sql .= ", remise";
			$sql .= ", note_private";
			$sql .= ", note_public";
			$sql .= ", modelpdf";
			$sql .= ", fk_user_author";
			$sql .= ", fk_projet";
			$sql .= ", fk_account";
			$sql .= ", fk_cond_reglement";
			$sql .= ", fk_mode_reglement";
			$sql .= ", usenewprice";
			$sql .= ", frequency";
			$sql .= ", unit_frequency";
			$sql .= ", date_when";
			$sql .= ", date_last_gen";
			$sql .= ", nb_gen_done";
			$sql .= ", nb_gen_max";
			$sql .= ", auto_validate";
			$sql .= ", generate_pdf";
			$sql .= ", fk_multicurrency";
			$sql .= ", multicurrency_code";
			$sql .= ", multicurrency_tx";
			$sql .= ", suspended";
			$sql .= ") VALUES (";
			$sql .= "'".$this->db->escape($this->titre ? $this->titre : $this->title)."'";
			$sql .= ", ".((int) $this->socid);
			$sql .= ", ".($this->subtype ? "'".$this->db->escape($this->subtype)."'" : "null");
			$sql .= ", ".((int) $conf->entity);
			$sql .= ", '".$this->db->idate($now)."'";
			$sql .= ", ".(!empty($facsrc->total_ttc) ? ((float) $facsrc->total_ttc) : '0');
			//$sql .= ", ".(!empty($facsrc->remise_absolue) ? ((float) $this->remise_absolue) : '0');
			$sql .= ", ".(!empty($this->note_private) ? ("'".$this->db->escape($this->note_private)."'") : "NULL");
			$sql .= ", ".(!empty($this->note_public) ? ("'".$this->db->escape($this->note_public)."'") : "NULL");
			$sql .= ", ".(!empty($this->model_pdf) ? ("'".$this->db->escape($this->model_pdf)."'") : "NULL");
			$sql .= ", ".((int) $user->id);
			$sql .= ", ".(!empty($this->fk_project) ? ((int) $this->fk_project) : "null");
			$sql .= ", ".(!empty($facsrc->fk_account) ? ((int) $facsrc->fk_account) : "null");
			$sql .= ", ".($this->cond_reglement_id > 0 ? ((int) $this->cond_reglement_id) : "null");
			$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : "null");
			$sql .= ", ".((int) $this->usenewprice);
			$sql .= ", ".((int) $this->frequency);
			$sql .= ", '".$this->db->escape($this->unit_frequency)."'";
			$sql .= ", ".(!empty($this->date_when) ? "'".$this->db->idate($this->date_when)."'" : 'NULL');
			$sql .= ", ".(!empty($this->date_last_gen) ? "'".$this->db->idate($this->date_last_gen)."'" : 'NULL');
			$sql .= ", ".((int) $this->nb_gen_done);
			$sql .= ", ".((int) $this->nb_gen_max);
			$sql .= ", ".((int) $this->auto_validate);
			$sql .= ", ".((int) $this->generate_pdf);
			$sql .= ", ".((int) $facsrc->fk_multicurrency);
			$sql .= ", '".$this->db->escape($facsrc->multicurrency_code)."'";
			$sql .= ", ".((float) $facsrc->multicurrency_tx);
			$sql .= ", ".((int) $this->suspended);
			$sql .= ")";

			if ($this->db->query($sql)) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				// Fields used into addline later
				$this->fk_multicurrency = $facsrc->fk_multicurrency;
				$this->multicurrency_code = $facsrc->multicurrency_code;
				$this->multicurrency_tx = $facsrc->multicurrency_tx;

				// Add lines
				$fk_parent_line = 0;

				$num = count($facsrc->lines);
				for ($i = 0; $i < $num; $i++) {
					if (!empty($onlylines) && !in_array($facsrc->lines[$i]->id, $onlylines)) {
						continue; // Skip unselected lines
					}

					// Reset fk_parent_line for no child products and special product
					if (($facsrc->lines[$i]->product_type != 9 && empty($facsrc->lines[$i]->fk_parent_line)) || $facsrc->lines[$i]->product_type == 9) {
						$fk_parent_line = 0;
					}

					$tva_tx = $facsrc->lines[$i]->tva_tx;
					if (!empty($facsrc->lines[$i]->vat_src_code) && !preg_match('/\(/', (string) $tva_tx)) {
						$tva_tx .= ' ('.$facsrc->lines[$i]->vat_src_code.')';
					}

					$default_start_fill = getDolGlobalInt('INVOICEREC_SET_AUTOFILL_DATE_START');
					$default_end_fill = getDolGlobalInt('INVOICEREC_SET_AUTOFILL_DATE_END');

					$result_insert = $this->addline(
						$facsrc->lines[$i]->desc,
						$facsrc->lines[$i]->subprice,
						$facsrc->lines[$i]->qty,
						$tva_tx,
						$facsrc->lines[$i]->localtax1_tx,
						$facsrc->lines[$i]->localtax2_tx,
						$facsrc->lines[$i]->fk_product,
						$facsrc->lines[$i]->remise_percent,
						'HT',
						$facsrc->lines[$i]->info_bits,
						'',
						0,
						$facsrc->lines[$i]->product_type,
						$facsrc->lines[$i]->rang,
						$facsrc->lines[$i]->special_code,
						$facsrc->lines[$i]->label,
						$facsrc->lines[$i]->fk_unit,
						$facsrc->lines[$i]->multicurrency_subprice,
						$default_start_fill,
						$default_end_fill,
						null,
						$facsrc->lines[$i]->pa_ht,
						$fk_parent_line
					);

					// Defined the new fk_parent_line
					if ($result_insert > 0 && $facsrc->lines[$i]->product_type == 9) {
						$fk_parent_line = $result_insert;
					}

					if ($result_insert < 0) {
						$error++;
					} else {
						$objectline = new FactureLigneRec($this->db);

						$result2 = $objectline->fetch($result_insert);
						if ($result2 > 0) {
							// Extrafields
							if (method_exists($facsrc->lines[$i], 'fetch_optionals')) {
								$facsrc->lines[$i]->fetch_optionals($facsrc->lines[$i]->id);
								$objectline->array_options = $facsrc->lines[$i]->array_options;
							}

							$result = $objectline->insertExtraFields();
							if ($result < 0) {
								$error++;
							}
						} elseif ($result2 < 0) {
							$this->errors[] = $objectline->error;
							$error++;
						}
					}
				}

				if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
					$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
					foreach ($this->linked_objects as $origin => $tmp_origin_id) {
						if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
							foreach ($tmp_origin_id as $origin_id) {
								$ret = $this->add_object_linked($origin, $origin_id);
								if (!$ret) {
									$this->error = $this->db->lasterror();
									$error++;
								}
							}
						} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
							$origin_id = $tmp_origin_id;
							$ret = $this->add_object_linked($origin, $origin_id);
							if (!$ret) {
								$this->error = $this->db->lasterror();
								$error++;
							}
						}
					}
				}

				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('BILLREC_CREATE', $user);
					if ($result < 0) {
						$this->db->rollback();
						return -2;
					}
					// End call triggers
				}

				if ($error) {
					$this->db->rollback();
					return -3;
				} else {
					$this->db->commit();
					return $this->id;
				}
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * 	Update a line invoice_rec.
	 *
	 *  @param		User	$user					User
	 *  @param		int		$notrigger				No trigger
	 *	@return    	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_rec SET";
		$sql .= " entity = ".((int) $this->entity).",";
		$sql .= " titre = '".$this->db->escape($this->title)."',";
		$sql .= " suspended = ".((int) $this->suspended).",";
		$sql .= " fk_soc = ".((int) $this->socid).",";
		$sql .= " total_tva = ".((float) $this->total_tva).",";
		$sql .= " localtax1 = ".((float) $this->total_localtax1).",";
		$sql .= " localtax2 = ".((float) $this->total_localtax2).",";
		$sql .= " total_ht = ".((float) $this->total_ht).",";
		$sql .= " total_ttc = ".((float) $this->total_ttc);
		// TODO Add missing fields
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -2;
				}
				// End call triggers
			}
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Load object and lines
	 *
	 *	@param      int		$rowid       	Id of object to load
	 * 	@param		string	$ref			Reference of recurring invoice
	 * 	@param		string	$ref_ext		External reference of invoice
	 *  @param		int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 *  @param		int		$nolines		0=Default to load lines, 1=No lines
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $noextrafields = 0, $nolines = 0)
	{
		dol_syslog('FactureRec::fetch', LOG_DEBUG);

		$sql = 'SELECT f.rowid, f.entity, f.titre as title, f.suspended, f.fk_soc, f.subtype, f.total_tva, f.localtax1, f.localtax2, f.total_ht, f.total_ttc';
		$sql .= ', f.date_lim_reglement as dlr';
		$sql .= ', f.note_private, f.note_public, f.fk_user_author';
		$sql .= ', f.modelpdf as model_pdf';
		$sql .= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet as fk_project';
		$sql .= ', f.fk_account';
		$sql .= ', f.frequency, f.unit_frequency, f.date_when, f.date_last_gen, f.nb_gen_done, f.nb_gen_max, f.usenewprice, f.auto_validate';
		$sql .= ', f.generate_pdf';
		$sql .= ", f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc";
		$sql .= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		//$sql.= ', el.fk_source';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_rec as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_target = f.rowid AND el.targettype = 'facture'";
		$sql .= ' WHERE f.entity IN ('.getEntity('invoice').')';
		if ($rowid) {
			$sql .= ' AND f.rowid = '.((int) $rowid);
		} elseif ($ref) {
			$sql .= " AND f.titre = '".$this->db->escape($ref)."'";
		} else {
			$sql .= ' AND f.rowid = 0';
		}
		/* This field are not used for template invoice
		 if ($ref_ext) $sql.= " AND f.ref_ext='".$this->db->escape($ref_ext)."'";
		 */

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id                     = $obj->rowid;
				$this->entity                 = $obj->entity;
				$this->titre                  = $obj->title; // deprecated
				$this->title                  = $obj->title;
				$this->ref                    = $obj->title;
				$this->subtype				  = $obj->subtype;
				$this->suspended              = $obj->suspended;
				$this->total_ht               = $obj->total_ht;
				$this->total_tva              = $obj->total_tva;
				$this->total_localtax1        = $obj->localtax1;
				$this->total_localtax2        = $obj->localtax2;
				$this->total_ttc              = $obj->total_ttc;
				$this->socid                  = $obj->fk_soc;
				$this->date_lim_reglement     = $this->db->jdate($obj->dlr);
				$this->mode_reglement_id      = $obj->fk_mode_reglement;
				$this->mode_reglement_code    = $obj->mode_reglement_code;
				$this->mode_reglement         = $obj->mode_reglement_libelle;
				$this->cond_reglement_id      = $obj->fk_cond_reglement;
				$this->cond_reglement_code    = $obj->cond_reglement_code;
				$this->cond_reglement         = $obj->cond_reglement_libelle;
				$this->cond_reglement_doc     = $obj->cond_reglement_libelle_doc;
				$this->fk_project             = $obj->fk_project;
				$this->fk_account             = $obj->fk_account;
				$this->note_private           = $obj->note_private;
				$this->note_public            = $obj->note_public;
				$this->user_author            = $obj->fk_user_author;
				$this->model_pdf              = $obj->model_pdf;
				//$this->special_code = $obj->special_code;
				$this->frequency			  = $obj->frequency;
				$this->unit_frequency = $obj->unit_frequency;
				$this->date_when			  = $this->db->jdate($obj->date_when);
				$this->date_last_gen = $this->db->jdate($obj->date_last_gen);
				$this->nb_gen_done			  = $obj->nb_gen_done;
				$this->nb_gen_max = $obj->nb_gen_max;
				$this->usenewprice			  = $obj->usenewprice;
				$this->auto_validate = $obj->auto_validate;
				$this->generate_pdf = $obj->generate_pdf;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				if (empty($noextrafields)) {
					$result = $this->fetch_optionals();
					if ($result < 0) {
						$this->error = $this->db->lasterror();
						return -4;
					}
				}

				// Retrieve lines
				if (empty($nolines)) {
					$result = $this->fetch_lines();
					if ($result < 0) {
						$this->error = $this->db->lasterror();
						return -3;
					}
				}

				return 1;
			} else {
				$this->error = 'Bill with id '.$rowid.' or ref '.$ref.' not found';
				dol_syslog('Facture::Fetch Error '.$this->error, LOG_ERR);
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 * 	Create an array of invoice lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		return $this->fetch_lines();
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Get lines of template invoices into this->lines
	 *
	 *  @return     int         1 if OK, < 0 if KO
	 */
	public function fetch_lines()
	{
		// phpcs:enable

		$this->lines = array();

		dol_syslog('FactureRec::fetch_lines', LOG_DEBUG);

		$sql = 'SELECT l.rowid, l.fk_facture, l.fk_product, l.fk_parent_line, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx, ';
		$sql .= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.subprice,';
		$sql .= ' l.info_bits, l.date_start_fill, l.date_end_fill, l.total_ht, l.total_tva, l.total_ttc, l.fk_product_fournisseur_price, l.buy_price_ht as pa_ht,';
		$sql .= ' l.rang, l.special_code,';
		$sql .= ' l.fk_unit, l.fk_contract_line,';
		$sql .= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql .= ' WHERE l.fk_facture = '.((int) $this->id);
		$sql .= ' ORDER BY l.rang';

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$objp = $this->db->fetch_object($result);
				$line = new FactureLigneRec($this->db);

				$line->id				= $objp->rowid;
				$line->rowid			= $objp->rowid;
				$line->fk_facture		= $objp->fk_facture;
				$line->fk_parent_line	= $objp->fk_parent_line;
				$line->desc				= $objp->description; // Description line
				$line->description		= $objp->description; // Description line
				$line->ref				= $objp->product_ref; // Ref product
				$line->product_ref		= $objp->product_ref; // Ref product
				$line->libelle			= $objp->product_label; // deprecated
				$line->product_label	= $objp->product_label; // Label product
				$line->product_desc		= $objp->product_desc; // Description product
				$line->product_type		= $objp->product_type; // Type of line
				$line->fk_product_type	= $objp->fk_product_type; // Type of product
				$line->qty 				= $objp->qty;
				$line->subprice			= $objp->subprice;

				$line->label			= $objp->custom_label; // @deprecated

				$line->vat_src_code		= $objp->vat_src_code;
				$line->tva_tx			= $objp->tva_tx;
				$line->localtax1_tx		= $objp->localtax1_tx;
				$line->localtax2_tx		= $objp->localtax2_tx;
				$line->localtax1_type	= $objp->localtax1_type;
				$line->localtax2_type	= $objp->localtax2_type;
				$line->remise_percent	= $objp->remise_percent;
				//$line->fk_remise_except = $objp->fk_remise_except;
				$line->fk_product		= $objp->fk_product;
				$line->date_start_fill	= $objp->date_start_fill;
				$line->date_end_fill	= $objp->date_end_fill;
				$line->info_bits		= $objp->info_bits;
				$line->total_ht			= $objp->total_ht;
				$line->total_tva		= $objp->total_tva;
				$line->total_ttc		= $objp->total_ttc;

				$line->fk_product_fournisseur_price = $objp->fk_product_fournisseur_price;
				$line->fk_fournprice = $objp->fk_product_fournisseur_price; // For backward compatibility

				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $objp->fk_product_fournisseur_price, $objp->pa_ht);

				$line->buyprice			= $marginInfos[0];
				$line->pa_ht			= $marginInfos[0]; // For backward compatibility
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->rang				= $objp->rang;
				$line->special_code		= $objp->special_code;
				$line->fk_unit			= $objp->fk_unit;
				$line->fk_contract_line	= $objp->fk_contract_line;

				// Ne plus utiliser
				$line->price			= $objp->price;
				$line->remise			= $objp->remise;

				$line->fetch_optionals();

				// Multicurrency
				$line->fk_multicurrency = $objp->fk_multicurrency;
				$line->multicurrency_code = $objp->multicurrency_code;
				$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				$this->lines[$i] = $line;

				$i++;
			}

			$this->db->free($result);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -3;
		}
	}


	/**
	 * 	Delete template invoice
	 *
	 *	@param     	User	$user          	User that delete.
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@param		int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0, $idwarehouse = -1)
	{
		$rowid = $this->id;

		dol_syslog(get_class($this)."::delete rowid=".((int) $rowid), LOG_DEBUG);

		$error = 0;
		$this->db->begin();

		$main = MAIN_DB_PREFIX.'facturedet_rec';
		$ef = $main."_extrafields";

		$sqlef = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM ".$main." WHERE fk_facture = ".((int) $rowid).")";
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facturedet_rec WHERE fk_facture = ".((int) $rowid);

		if ($this->db->query($sqlef) && $this->db->query($sql)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_rec WHERE rowid = ".((int) $rowid);
			dol_syslog($sql);
			if ($this->db->query($sql)) {
				// Delete linked object
				$res = $this->deleteObjectLinked();
				if ($res < 0) {
					$error = -3;
				}
				// Delete extrafields
				$res = $this->deleteExtraFields();
				if ($res < 0) {
					$error = -4;
				}
			} else {
				$this->error = $this->db->lasterror();
				$error = -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$error = -2;
		}
		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('BILLREC_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}
		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return $error;
		}
	}


	/**
	 * 	Add a line to invoice
	 *
	 *	@param    	string		$desc            	Description de la ligne
	 *	@param    	double		$pu_ht              Prix unitaire HT (> 0 even for credit note)
	 *	@param    	double		$qty             	Quantite
	 *	@param    	double		$txtva           	Taux de tva force, sinon -1
	 * 	@param		double		$txlocaltax1		Local tax 1 rate (deprecated)
	 *  @param		double		$txlocaltax2		Local tax 2 rate (deprecated)
	 *	@param    	int			$fk_product      	Product/Service ID predefined
	 *	@param    	double		$remise_percent  	Percentage discount of the line
	 *	@param		string		$price_base_type	HT or TTC
	 *	@param    	int			$info_bits			VAT npr or not ?
	 *	@param    	int			$fk_remise_except	Id remise
	 *	@param    	double		$pu_ttc             Prix unitaire TTC (> 0 even for credit note)
	 *	@param		int			$type				Type of line (0=product, 1=service)
	 *	@param      int			$rang               Position of line
	 *	@param		int			$special_code		Special code
	 *	@param		string		$label				Label of the line
	 *	@param		string		$fk_unit			Unit
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 *  @param		int			$date_start_fill	1=Flag to fill start date when generating invoice
	 *  @param		int			$date_end_fill		1=Flag to fill end date when generating invoice
	 * 	@param		int			$fk_fournprice		Supplier price id (to calculate margin) or ''
	 * 	@param		int			$pa_ht				Buying price of line (to calculate margin) or ''
	 *  @param		int			$fk_parent_line		Id of parent line
	 *	@return    	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $fk_product = 0, $remise_percent = 0, $price_base_type = 'HT', $info_bits = 0, $fk_remise_except = 0, $pu_ttc = 0, $type = 0, $rang = -1, $special_code = 0, $label = '', $fk_unit = null, $pu_ht_devise = 0, $date_start_fill = 0, $date_end_fill = 0, $fk_fournprice = null, $pa_ht = 0, $fk_parent_line = 0)
	{
		global $mysoc;

		$facid = $this->id;

		dol_syslog(get_class($this)."::addline facid=$facid,desc=$desc,pu_ht=$pu_ht,qty=$qty,txtva=$txtva,txlocaltax1=$txlocaltax1,txlocaltax2=$txlocaltax2,fk_product=$fk_product,remise_percent=$remise_percent,info_bits=$info_bits,fk_remise_except=$fk_remise_except,price_base_type=$price_base_type,pu_ttc=$pu_ttc,type=$type,fk_unit=$fk_unit,pu_ht_devise=$pu_ht_devise,date_start_fill=$date_start_fill,date_end_fill=$date_end_fill,pa_ht=$pa_ht", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Check parameters
		if ($type < 0) {
			return -1;
		}

		$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

		// Clean vat code
		$reg = array();
		$vat_src_code = '';
		if (preg_match('/\((.*)\)/', (string) $txtva, $reg)) {
			$vat_src_code = $reg[1];
			$txtva = preg_replace('/\s*\(.*\)/', '', (string) $txtva); // Remove code from vatrate.
		}


		// Clean parameters
		$remise_percent = price2num($remise_percent);
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}
		$qty = price2num($qty);
		$pu_ht = price2num($pu_ht);
		$pu_ttc = price2num($pu_ttc);
		if (!preg_match('/\((.*)\)/', $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);
		if (empty($txtva)) {
			$txtva = 0;
		}
		if (empty($txlocaltax1)) {
			$txlocaltax1 = 0;
		}
		if (empty($txlocaltax2)) {
			$txlocaltax2 = 0;
		}
		if (empty($info_bits)) {
			$info_bits = 0;
		}

		if ($price_base_type == 'HT') {
			$pu = $pu_ht;
		} else {
			$pu = $pu_ttc;
		}

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$total_localtax1 = $tabprice[9];
		$total_localtax2 = $tabprice[10];
		$pu_ht = $tabprice[3];

		// MultiCurrency
		$multicurrency_total_ht  = $tabprice[16];
		$multicurrency_total_tva = $tabprice[17];
		$multicurrency_total_ttc = $tabprice[18];
		$pu_ht_devise = $tabprice[19];

		$product_type = $type;
		if ($fk_product) {
			$product = new Product($this->db);
			$result = $product->fetch($fk_product);
			$product_type = $product->type;
		}

		if (empty($fk_parent_line) || $fk_parent_line < 0) {
			$fk_parent_line = 0;
		}

		// Rank to use
		$ranktouse = $rang;
		if ($ranktouse == -1) {
			$rangmax = $this->line_max(0);
			$ranktouse = $rangmax + 1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facturedet_rec (";
		$sql .= "fk_facture";
		$sql .= ", fk_parent_line";
		$sql .= ", label";
		$sql .= ", description";
		$sql .= ", price";
		$sql .= ", qty";
		$sql .= ", tva_tx";
		$sql .= ", vat_src_code";
		$sql .= ", localtax1_tx";
		$sql .= ", localtax1_type";
		$sql .= ", localtax2_tx";
		$sql .= ", localtax2_type";
		$sql .= ", fk_product";
		$sql .= ", product_type";
		$sql .= ", remise_percent";
		$sql .= ", subprice";
		$sql .= ", remise";
		$sql .= ", total_ht";
		$sql .= ", total_tva";
		$sql .= ", total_localtax1";
		$sql .= ", total_localtax2";
		$sql .= ", total_ttc";
		$sql .= ", date_start_fill";
		$sql .= ", date_end_fill";
		$sql .= ", fk_product_fournisseur_price";
		$sql .= ", buy_price_ht";
		$sql .= ", info_bits";
		$sql .= ", rang";
		$sql .= ", special_code";
		$sql .= ", fk_unit";
		$sql .= ', fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= ") VALUES (";
		$sql .= " ".((int) $facid);
		$sql .= ", ".($fk_parent_line > 0 ? ((int) $fk_parent_line) : "null");
		$sql .= ", ".(!empty($label) ? "'".$this->db->escape($label)."'" : "null");
		$sql .= ", '".$this->db->escape($desc)."'";
		$sql .= ", ".price2num($pu_ht);
		$sql .= ", ".price2num($qty);
		$sql .= ", ".price2num($txtva);
		$sql .= ", '".$this->db->escape($vat_src_code)."'";
		$sql .= ", ".price2num($txlocaltax1);
		$sql .= ", '".$this->db->escape(isset($localtaxes_type[0]) ? $localtaxes_type[0] : '')."'";
		$sql .= ", ".price2num($txlocaltax2);
		$sql .= ", '".$this->db->escape(isset($localtaxes_type[2]) ? $localtaxes_type[2] : '')."'";
		$sql .= ", ".(!empty($fk_product) ? "'".$this->db->escape($fk_product)."'" : "null");
		$sql .= ", ".((int) $product_type);
		$sql .= ", ".price2num($remise_percent);
		$sql .= ", ".price2num($pu_ht);
		$sql .= ", null";
		$sql .= ", ".price2num($total_ht);
		$sql .= ", ".price2num($total_tva);
		$sql .= ", ".price2num($total_localtax1);
		$sql .= ", ".price2num($total_localtax2);
		$sql .= ", ".price2num($total_ttc);
		$sql .= ", ".(int) $date_start_fill;
		$sql .= ", ".(int) $date_end_fill;
		$sql .= ", ".($fk_fournprice > 0 ? $fk_fournprice : 'null');
		$sql .= ", ".($pa_ht ? price2num($pa_ht) : 0);
		$sql .= ", ".((int) $info_bits);
		$sql .= ", ".((int) $ranktouse);
		$sql .= ", ".((int) $special_code);
		$sql .= ", ".($fk_unit ? ((int) $fk_unit) : "null");
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($pu_ht_devise, 'CU');
		$sql .= ", ".price2num($multicurrency_total_ht, 'CT');
		$sql .= ", ".price2num($multicurrency_total_tva, 'CT');
		$sql .= ", ".price2num($multicurrency_total_ttc, 'CT');
		$sql .= ")";

		dol_syslog(get_class($this)."::addline", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$lineId = $this->db->last_insert_id(MAIN_DB_PREFIX."facturedet_rec");
			$this->id = $facid;
			$this->update_price(1);
			return $lineId;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Update a line to invoice
	 *
	 *  @param     	int			$rowid           	Id of line to update
	 *	@param    	string		$desc            	Description de la ligne
	 *	@param    	double		$pu_ht              Prix unitaire HT (> 0 even for credit note)
	 *	@param    	double		$qty             	Quantite
	 *	@param    	double		$txtva           	Taux de tva force, sinon -1
	 * 	@param		double		$txlocaltax1		Local tax 1 rate (deprecated)
	 *  @param		double		$txlocaltax2		Local tax 2 rate (deprecated)
	 *	@param    	int			$fk_product      	Product/Service ID predefined
	 *	@param    	double		$remise_percent  	Percentage discount of the line
	 *	@param		string		$price_base_type	HT or TTC
	 *	@param    	int			$info_bits			Bits of type of lines
	 *	@param    	int			$fk_remise_except	Id remise
	 *	@param    	double		$pu_ttc             Prix unitaire TTC (> 0 even for credit note)
	 *	@param		int			$type				Type of line (0=product, 1=service)
	 *	@param      int			$rang               Position of line
	 *	@param		int			$special_code		Special code
	 *	@param		string		$label				Label of the line
	 *	@param		string		$fk_unit			Unit
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 * 	@param		int			$notrigger			disable line update trigger
	 *  @param		int			$date_start_fill	1=Flag to fill start date when generating invoice
	 *  @param		int			$date_end_fill		1=Flag to fill end date when generating invoice
	 * 	@param		int			$fk_fournprice		Id of origin supplier price
	 * 	@param		int			$pa_ht				Price (without tax) of product for margin calculation
	 *  @param		int			$fk_parent_line		Id of parent line
	 *	@return    	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function updateline($rowid, $desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $fk_product = 0, $remise_percent = 0, $price_base_type = 'HT', $info_bits = 0, $fk_remise_except = 0, $pu_ttc = 0, $type = 0, $rang = -1, $special_code = 0, $label = '', $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0, $date_start_fill = 0, $date_end_fill = 0, $fk_fournprice = null, $pa_ht = 0, $fk_parent_line = 0)
	{
		global $mysoc;

		$facid = $this->id;

		dol_syslog(get_class($this)."::updateline facid=".$facid." rowid=$rowid, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product, remise_percent=$remise_percent, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, type=$type, fk_unit=$fk_unit, pu_ht_devise=$pu_ht_devise", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}

		// Check parameters
		if ($type < 0) {
			return -1;
		}

		// Clean parameters
		$remise_percent = price2num($remise_percent);
		$qty = price2num($qty);
		if (empty($info_bits)) {
			$info_bits = 0;
		}
		$pu_ht          = price2num($pu_ht);
		$pu_ttc         = price2num($pu_ttc);
		$pu_ht_devise = price2num($pu_ht_devise);
		if (!preg_match('/\((.*)\)/', (string) $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1	= price2num($txlocaltax1);
		$txlocaltax2	= price2num($txlocaltax2);
		if (empty($txlocaltax1)) {
			$txlocaltax1 = 0;
		}
		if (empty($txlocaltax2)) {
			$txlocaltax2 = 0;
		}

		if (empty($this->multicurrency_subprice)) {
			$this->multicurrency_subprice = 0;
		}
		if (empty($this->multicurrency_total_ht)) {
			$this->multicurrency_total_ht = 0;
		}
		if (empty($this->multicurrency_total_tva)) {
			$this->multicurrency_total_tva = 0;
		}
		if (empty($this->multicurrency_total_ttc)) {
			$this->multicurrency_total_ttc = 0;
		}

		if ($price_base_type == 'HT') {
			$pu = $pu_ht;
		} else {
			$pu = $pu_ttc;
		}

		// Calculate total with, without tax and tax from qty, pu, remise_percent and txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

		// Clean vat code
		$vat_src_code = '';
		$reg = array();
		if (preg_match('/\((.*)\)/', $txtva, $reg)) {
			$vat_src_code = $reg[1];
			$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
		}

		$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$total_localtax1 = $tabprice[9];
		$total_localtax2 = $tabprice[10];
		$pu_ht  = $tabprice[3];
		$pu_tva = $tabprice[4];
		$pu_ttc = $tabprice[5];

		// MultiCurrency
		$multicurrency_total_ht  = $tabprice[16];
		$multicurrency_total_tva = $tabprice[17];
		$multicurrency_total_ttc = $tabprice[18];
		$pu_ht_devise = $tabprice[19];

		$product_type = $type;
		if ($fk_product) {
			$product = new Product($this->db);
			$result = $product->fetch($fk_product);
			$product_type = $product->type;
		}

		if (empty($fk_parent_line) || $fk_parent_line < 0) {
			$fk_parent_line = 0;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet_rec SET ";
		$sql .= "fk_facture = ".((int) $facid);
		$sql .= ", fk_parent_line = ".($fk_parent_line > 0 ? ((int) $fk_parent_line) : "null");
		$sql .= ", label=".(!empty($label) ? "'".$this->db->escape($label)."'" : "null");
		$sql .= ", description='".$this->db->escape($desc)."'";
		$sql .= ", price=".price2num($pu_ht);
		$sql .= ", qty=".price2num($qty);
		$sql .= ", tva_tx=".price2num($txtva);
		$sql .= ", vat_src_code='".$this->db->escape($vat_src_code)."'";
		$sql .= ", localtax1_tx=".((float) $txlocaltax1);
		$sql .= ", localtax1_type='".$this->db->escape($localtaxes_type[0])."'";
		$sql .= ", localtax2_tx=".((float) $txlocaltax2);
		$sql .= ", localtax2_type='".$this->db->escape($localtaxes_type[2])."'";
		$sql .= ", fk_product=".(!empty($fk_product) ? "'".$this->db->escape($fk_product)."'" : "null");
		$sql .= ", product_type=".((int) $product_type);
		$sql .= ", remise_percent='".price2num($remise_percent)."'";
		$sql .= ", subprice='".price2num($pu_ht)."'";
		$sql .= ", total_ht='".price2num($total_ht)."'";
		$sql .= ", total_tva='".price2num($total_tva)."'";
		$sql .= ", total_localtax1='".price2num($total_localtax1)."'";
		$sql .= ", total_localtax2='".price2num($total_localtax2)."'";
		$sql .= ", total_ttc='".price2num($total_ttc)."'";
		$sql .= ", date_start_fill=".((int) $date_start_fill);
		$sql .= ", date_end_fill=".((int) $date_end_fill);
		$sql .= ", fk_product_fournisseur_price=".($fk_fournprice > 0 ? $fk_fournprice : 'null');
		$sql .= ", buy_price_ht=".($pa_ht ? price2num($pa_ht) : 0);
		$sql .= ", info_bits=".((int) $info_bits);
		$sql .= ", rang=".((int) $rang);
		$sql .= ", special_code=".((int) $special_code);
		$sql .= ", fk_unit=".($fk_unit ? "'".$this->db->escape($fk_unit)."'" : "null");
		$sql .= ', multicurrency_subprice = '.price2num($pu_ht_devise);
		$sql .= ', multicurrency_total_ht = '.price2num($multicurrency_total_ht);
		$sql .= ', multicurrency_total_tva = '.price2num($multicurrency_total_tva);
		$sql .= ', multicurrency_total_ttc = '.price2num($multicurrency_total_ttc);
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->id = $facid;
			$this->update_price(1);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 * Return the next date of
	 *
	 * @return  int|false   false if KO, timestamp if OK
	 */
	public function getNextDate()
	{
		if (empty($this->date_when)) {
			return false;
		}
		return dol_time_plus_duree($this->date_when, $this->frequency, $this->unit_frequency);
	}

	/**
	 * Return if maximum number of generation is reached
	 *
	 * @return	boolean			False by default, True if maximum number of generation is reached
	 */
	public function isMaxNbGenReached()
	{
		$ret = false;
		if ($this->nb_gen_max > 0 && ($this->nb_gen_done >= $this->nb_gen_max)) {
			$ret = true;
		}
		return $ret;
	}

	/**
	 * Format string to output with by striking the string if max number of generation was reached
	 *
	 * @param	string		$ret	Default value to output
	 * @return	string				html formatted string
	 */
	public function strikeIfMaxNbGenReached($ret)
	{
		return $this->isMaxNbGenReached() ? '<strike>'.$ret.'</strike>' : $ret;
	}

	/**
	 *  Create all recurrents invoices (for all entities if multicompany is used).
	 *  A result may also be provided into this->output.
	 *
	 *  WARNING: This method change temporarily context $conf->entity to be in correct context for each recurring invoice found.
	 *
	 *  @param	int		$restrictioninvoiceid		0=All qualified template invoices found. > 0 = restrict action on invoice ID
	 *  @param	int		$forcevalidation		1=Force validation of invoice whatever is template auto_validate flag.
	 *	@param     	int 	$notrigger 			Disable the trigger
	 *  @return	int								0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function createRecurringInvoices($restrictioninvoiceid = 0, $forcevalidation = 0, $notrigger = 0)
	{
		global $conf, $langs, $db, $user, $hookmanager;

		$error = 0;
		$nb_create = 0;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "bills"));

		$now = dol_now();
		$tmparray = dol_getdate($now);
		$today = dol_mktime(23, 59, 59, $tmparray['mon'], $tmparray['mday'], $tmparray['year']); // Today is last second of current day

		$this->output = '';

		dol_syslog("createRecurringInvoices restrictioninvoiceid=".$restrictioninvoiceid." forcevalidation=".$forcevalidation);

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facture_rec';
		$sql .= ' WHERE frequency > 0'; // A recurring invoice is an invoice with a frequency
		$sql .= " AND (date_when IS NULL OR date_when <= '".$this->db->idate($today)."')";
		$sql .= ' AND (nb_gen_done < nb_gen_max OR nb_gen_max = 0)';
		$sql .= ' AND suspended = 0';
		$sql .= ' AND entity = '.$conf->entity; // MUST STAY = $conf->entity here
		if ($restrictioninvoiceid > 0) {
			$sql .= ' AND rowid = '.((int) $restrictioninvoiceid);
		}
		$sql .= $this->db->order('entity', 'ASC');
		//print $sql;exit;
		$parameters = array(
			'restrictioninvoiceid' => $restrictioninvoiceid,
			'forcevalidation' => $forcevalidation,
		);
		$reshook = $hookmanager->executeHooks('beforeCreationOfRecurringInvoices', $parameters, $sql); // note that $sql might be modified by hooks

		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $this->db->num_rows($resql);

			if ($num) {
				$this->output .= $langs->trans("FoundXQualifiedRecurringInvoiceTemplate", $num)."\n";
			} else {
				$this->output .= $langs->trans("NoQualifiedRecurringInvoiceTemplateFound");
			}

			$saventity = $conf->entity;

			while ($i < $num) {     // Loop on each template invoice. If $num = 0, test is false at first pass.
				$line = $this->db->fetch_object($resql);

				$this->db->begin();

				$invoiceidgenerated = 0;

				$facture = null;
				$facturerec = new FactureRec($this->db);
				$facturerec->fetch($line->rowid);

				if ($facturerec->id > 0) {
					// Set entity context
					$conf->entity = $facturerec->entity;

					dol_syslog("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref.", entity=".$facturerec->entity);

					$facture = new Facture($this->db);
					$facture->fac_rec = $facturerec->id; // We will create $facture from this recurring invoice
					$facture->fk_fac_rec_source = $facturerec->id; // We will create $facture from this recurring invoice

					$facture->type = self::TYPE_STANDARD;
					$facture->subtype = $facturerec->subtype;
					$facture->statut = self::STATUS_DRAFT;	// deprecated
					$facture->status = self::STATUS_DRAFT;
					$facture->date = (empty($facturerec->date_when) ? $now : $facturerec->date_when); // We could also use dol_now here but we prefer date_when so invoice has real date when we would like even if we generate later.
					$facture->socid = $facturerec->socid;
					if (!empty($facturerec->fk_multicurrency)) {
						$facture->fk_multicurrency = $facturerec->fk_multicurrency;
						$facture->multicurrency_code = $facturerec->multicurrency_code;
						$facture->multicurrency_tx = $facturerec->multicurrency_tx;
					}

					$invoiceidgenerated = $facture->create($user);
					if ($invoiceidgenerated <= 0) {
						$this->errors = $facture->errors;
						$this->error = $facture->error;
						$error++;
					}


					if (!$error && ($facturerec->auto_validate || $forcevalidation)) {
						$result = $facture->validate($user);
						if ($result <= 0) {
							$this->errors = $facture->errors;
							$this->error = $facture->error;
							$error++;
						}
					}
					if (!$error && $facturerec->generate_pdf) {
						// We refresh the object in order to have all necessary data (like date_lim_reglement)
						$facture->fetch($facture->id);
						$result = $facture->generateDocument($facturerec->model_pdf, $langs);
						if ($result <= 0) {
							$this->errors = $facture->errors;
							$this->error = $facture->error;
							$error++;
						}
					}
				} else {
					$error++;
					$this->error = "Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity."\n";
					$this->errors[] = "Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity;
					dol_syslog("createRecurringInvoices Failed to load invoice template with id=".$line->rowid.", entity=".$conf->entity);
				}

				if (!$error && $invoiceidgenerated >= 0) {
					$this->db->commit("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref);
					dol_syslog("createRecurringInvoices Process invoice template ".$facturerec->ref." is finished with a success generation");
					$nb_create++;
					$this->output .= $langs->trans("InvoiceGeneratedFromTemplate", $facture->ref, $facturerec->ref)."\n";
				} else {
					$this->db->rollback("createRecurringInvoices Process invoice template id=".$facturerec->id.", ref=".$facturerec->ref);
				}

				$parameters = array(
					'cpt'        => $i,
					'total'      => $num,
					'errorCount' => $error,
					'invoiceidgenerated' => $invoiceidgenerated,
					'facturerec' => $facturerec, // it's an object which PHP passes by "reference", so modifiable by hooks.
					'this'       => $this, // it's an object which PHP passes by "reference", so modifiable by hooks.
				);
				$reshook = $hookmanager->executeHooks('afterCreationOfRecurringInvoice', $parameters, $facture); // note: $facture can be modified by hooks (warning: $facture can be null)

				$i++;
			}

			$conf->entity = $saventity; // Restore entity context
		} else {
			dol_print_error($this->db);
		}

		$this->output = trim($this->output);

		return $error ? $error : 0;
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 * @param	int		$withpicto       			Add picto into link
	 * @param  string	$option          			Where point the link
	 * @param  int		$max             			Maxlength of ref
	 * @param  int		$short           			1=Return just URL
	 * @param  string   $moretitle       			Add more text to title tooltip
	 * @param	int  	$notooltip		 			1=Disable tooltip
	 * @param  int		$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string 			         			String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $langs, $hookmanager;

		$result = '';

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("RepeatableInvoice").'</u>';
		if (!empty($this->ref)) {
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if ($this->frequency > 0) {
			$label .= '<br><b>'.$langs->trans('Frequency').':</b> '.$langs->trans('FrequencyPer_'.$this->unit_frequency, $this->frequency);
		}
		if (!empty($this->date_last_gen)) {
			$label .= '<br><b>'.$langs->trans('DateLastGeneration').':</b> '.dol_print_date($this->date_last_gen, 'dayhour');
		}
		if ($this->frequency > 0) {
			if (!empty($this->date_when)) {
				$label .= '<br><b>'.$langs->trans('NextDateToExecution').':</b> ';
				$label .= (empty($this->suspended) ? '' : '<strike>').dol_print_date($this->date_when, 'day').(empty($this->suspended) ? '' : '</strike>'); // No hour for this property
				if (!empty($this->suspended)) {
					$label .= ' ('.$langs->trans("Disabled").')';
				}
			}
		}

		$url = DOL_URL_ROOT.'/compta/facture/card-rec.php?facid='.$this->id;

		if ($short) {
			return $url;
		}

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= dol_trunc($this->ref, $max);
		}
		$result .= $linkend;
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param      integer	$alreadypaid    Not used on recurring invoices
	 *  @return     string			        Label of status
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->frequency ? 1 : 0, $this->suspended, $mode, $alreadypaid, empty($this->type) ? 0 : $this->type);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of a status
	 *
	 *	@param    	int  	$recur         	Is it a recurring invoice ?
	 *	@param      int		$status        	Id status (suspended or not)
	 *	@param      int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=long label + picto
	 *	@param		integer	$alreadypaid	Not used for recurring invoices
	 *	@param		int		$type			Type invoice
	 *	@return     string        			Label of status
	 */
	public function LibStatut($recur, $status, $mode = 0, $alreadypaid = -1, $type = 0)
	{
		// phpcs:enable
		global $langs;
		$langs->load('bills');

		$labelStatus = $langs->transnoentitiesnoconv('Active');
		$statusType = 'status0';

		//print "$recur,$status,$mode,$alreadypaid,$type";
		if ($mode == 0) {
			if ($recur) {
				if ($status == self::STATUS_SUSPENDED) {
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$labelStatus = $langs->transnoentitiesnoconv("Draft");
				}
			}
		} elseif ($mode == 1) {
			$prefix = 'Short';
			if ($recur) {
				if ($status == self::STATUS_SUSPENDED) {
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$labelStatus = $langs->transnoentitiesnoconv("Draft");
				}
			}
		} elseif ($mode == 2) {
			if ($recur) {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status4';
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status0';
					$labelStatus = $langs->transnoentitiesnoconv('Draft');
				}
			}
		} elseif ($mode == 3) {
			if ($recur) {
				$prefix = 'Short';
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status4';
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status0';
					$labelStatus = $langs->transnoentitiesnoconv('Draft');
				}
			}
		} elseif ($mode == 4) {
			$prefix = '';
			if ($recur) {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status4';
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status0';
					$labelStatus = $langs->transnoentitiesnoconv('Draft');
				}
			}
		} elseif ($mode == 5 || $mode == 6) {
			$prefix = '';
			if ($mode == 5) {
				$prefix = 'Short';
			}
			if ($recur) {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status4';
					$labelStatus = $langs->transnoentitiesnoconv('Active');
				}
			} else {
				if ($status == self::STATUS_SUSPENDED) {
					$statusType = 'status6';
					$labelStatus = $langs->transnoentitiesnoconv('Disabled');
				} else {
					$statusType = 'status0';
					$labelStatus = $langs->transnoentitiesnoconv('Draft');
				}
			}
		}

		$labelStatusShort = $labelStatus;

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}

	/**
	 * Return next reference of invoice not already used (or last reference)
	 *
	 * @param	 Societe	$soc		Thirdparty object
	 * @param    string		$mode		'next' for next value or 'last' for last value
	 * @return   string					free ref or last ref
	 */
	public function getNextNumRef($soc, $mode = 'next')
	{
		// Not used for recurring invoices
		return '';
	}

	/**
	 *	Load miscellaneous information for tab "Info"
	 *
	 *	@param  int		$id		Id of object to load
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, datec, tms as datem,';
		$sql .= ' fk_user_author, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_rec as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	int
	 */
	public function initAsSpecimen($option = '')
	{
		global $langs;

		$now = dol_now();
		$arraynow = dol_getdate($now);
		$nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialize parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->title = 'SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = $nownotime;
		$this->date_lim_reglement = $nownotime + 3600 * 24 * 30;
		$this->cond_reglement_id   = 1;
		$this->cond_reglement_code = 'RECEP';
		$this->date_lim_reglement = $this->calculate_date_lim_reglement();
		$this->mode_reglement_id   = 0; // Not forced to show payment mode CHQ + VIR
		$this->mode_reglement_code = ''; // Not forced to show payment mode CHQ + VIR
		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';
		$this->note = 'This is a comment (private)';
		$this->fk_incoterms = 0;
		$this->location_incoterms = '';

		if (empty($option) || $option != 'nolines') {
			// Lines
			$nbp = 5;
			$xnbp = 0;
			while ($xnbp < $nbp) {
				$line = new FactureLigne($this->db);
				$line->desc = $langs->trans("Description")." ".$xnbp;
				$line->qty = 1;
				$line->subprice = 100;
				$line->tva_tx = 19.6;
				$line->localtax1_tx = 0;
				$line->localtax2_tx = 0;
				$line->remise_percent = 0;
				if ($xnbp == 1) {        // Qty is negative (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->qty = -1;
					$line->total_ht = -100;
					$line->total_ttc = -119.6;
					$line->total_tva = -19.6;
				} elseif ($xnbp == 2) {    // UP is negative (free line)
					$line->subprice = -100;
					$line->total_ht = -100;
					$line->total_ttc = -119.6;
					$line->total_tva = -19.6;
					$line->remise_percent = 0;
				} elseif ($xnbp == 3) {    // Discount is 50% (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->total_ht = 50;
					$line->total_ttc = 59.8;
					$line->total_tva = 9.8;
					$line->remise_percent = 50;
				} else { // (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->total_ht = 100;
					$line->total_ttc = 119.6;
					$line->total_tva = 19.6;
					$line->remise_percent = 00;
				}

				$this->lines[$xnbp] = $line;
				$xnbp++;

				$this->total_ht       += $line->total_ht;
				$this->total_tva      += $line->total_tva;
				$this->total_ttc      += $line->total_ttc;
			}
			$this->revenuestamp = 0;

			// Add a line "offered"
			$line = new FactureLigne($this->db);
			$line->desc = $langs->trans("Description")." (offered line)";
			$line->qty = 1;
			$line->subprice = 100;
			$line->tva_tx = 19.6;
			$line->localtax1_tx = 0;
			$line->localtax2_tx = 0;
			$line->remise_percent = 100;
			$line->total_ht = 0;
			$line->total_ttc = 0; // 90 * 1.196
			$line->total_tva = 0;
			$prodid = mt_rand(1, $num_prods);
			$line->fk_product = $prodids[$prodid];

			$this->lines[$xnbp] = $line;
			$xnbp++;
		}

		$this->usenewprice = 0;

		return 1;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'facture_rec'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old product id
	 * @param 	int 	$dest_id 	New product id
	 * @return 	bool
	 */
	public static function replaceProduct(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'facturedet_rec'
		);

		return CommonObject::commonReplaceProduct($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 *	Update frequency and unit
	 *
	 *	@param     	int		$frequency		value of frequency
	 *	@param     	string	$unit 			unit of frequency  (d, m, y)
	 *	@param     	int 	$notrigger 		Disable the trigger
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function setFrequencyAndUnit($frequency, $unit, $notrigger = 0)
	{
		global $user;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setFrequencyAndUnit was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		if (!empty($frequency) && empty($unit)) {
			dol_syslog(get_class($this)."::setFrequencyAndUnit was called on object with params frequency defined but unit not defined", LOG_ERR);
			return -2;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' SET frequency = '.($frequency ? $this->db->escape($frequency) : 'null');
		if (!empty($unit)) {
			$sql .= ', unit_frequency = \''.$this->db->escape($unit).'\'';
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setFrequencyAndUnit", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->frequency = $frequency;
			if (!empty($unit)) {
				$this->unit_frequency = $unit;
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Update the next date of execution
	 *
	 *	@param     	datetime	$date					date of execution
	 *	@param     	int			$increment_nb_gen_done	0 do nothing more, >0 increment nb_gen_done
	 *	@param     	int 	    $notrigger		 		Disable the trigger
	 *	@return		int									Return integer <0 if KO, >0 if OK
	 */
	public function setNextDate($date, $increment_nb_gen_done = 0, $notrigger = 0)
	{
		global $user;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setNextDate was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET date_when = ".($date ? "'".$this->db->idate($date)."'" : "null");
		if ($increment_nb_gen_done > 0) {
			$sql .= ', nb_gen_done = nb_gen_done + 1';
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setNextDate", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->date_when = $date;
			if ($increment_nb_gen_done > 0) {
				$this->nb_gen_done++;
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Update the maximum period
	 *
	 *	@param     	int		$nb		number of maximum period
	 *	@param     	int 	$notrigger Disable the trigger
	 *	@return		int				Return integer <0 if KO, >0 if OK
	 */
	public function setMaxPeriod($nb, $notrigger = 0)
	{
		global $user;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setMaxPeriod was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		if (empty($nb)) {
			$nb = 0;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' SET nb_gen_max = '.((int) $nb);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setMaxPeriod", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->nb_gen_max = $nb;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Update the auto validate flag of invoice
	 *
	 *	@param     	int		$validate		0 to create in draft, 1 to create and validate invoice
	 *	@param     	int 	$notrigger 		Disable the trigger
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function setAutoValidate($validate, $notrigger = 0)
	{
		global $user;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setAutoValidate was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' SET auto_validate = '.((int) $validate);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setAutoValidate", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->auto_validate = $validate;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Update the auto generate documents
	 *
	 *	@param     	int		$validate		0 no document, 1 to generate document
	 *	@param     	int 	$notrigger 		Disable the trigger
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function setGeneratePdf($validate, $notrigger = 0)
	{
		global $user;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setGeneratePdf was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' SET generate_pdf = '.((int) $validate);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setGeneratePdf", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->generate_pdf = $validate;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Update the model for documents
	 *
	 *  @param     	string		$model		model of document generator
	 *	@param     	int 	$notrigger 		Disable the trigger
	 *  @return		int						Return integer <0 if KO, >0 if OK
	 */
	public function setModelPdf($model, $notrigger = 0)
	{
		global $user;
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setModelPdf was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET modelpdf = '".$this->db->escape($model)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setModelPdf", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->model_pdf = $model;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILLREC_MODIFY', $user);
				if ($result < 0) {
					return $result;
				}
				// End call triggers
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}
}



/**
 *	Class to manage invoice lines of templates.
 *  Saved into database table llx_facturedet_rec
 */
class FactureLigneRec extends CommonInvoiceLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'facturedetrec';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facturedet_rec';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'facturerec';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_facture';

	//! From llx_facturedet_rec
	//! Id facture
	public $fk_facture;
	//! Id parent line
	public $fk_parent_line;

	public $fk_product_fournisseur_price;
	public $fk_fournprice; // For backward compatibility

	public $rang;
	//public $situation_percent;	// Not supported on recurring invoice line

	public $desc;
	public $description;

	public $fk_product_type; // Use instead product_type

	public $fk_contract_line;


	/**
	 * 	Delete line in database
	 *
	 *  @param		User	$user		Object user
	 *  @param		int		$notrigger	Disable triggers
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Call triggers
				$result = $this->call_trigger('LINEBILLREC_DELETE', $user);
				if ($result < 0) {
					$error++;
				} // Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		}

		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE rowid='.((int) $this->id);

			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Get line of template invoice
	 *
	 *	@param		int 	$rowid		Id of invoice
	 *	@return     int         		1 if OK, < 0 if KO
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT l.rowid, l.fk_facture, l.fk_parent_line, l.fk_product, l.product_type, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx,';
		$sql .= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise, l.remise_percent, l.subprice,';
		$sql .= ' l.date_start_fill, l.date_end_fill, l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
		$sql .= ' l.rang, l.special_code,';
		$sql .= ' l.fk_unit, l.fk_contract_line,';
		$sql .= ' l.import_key, l.fk_multicurrency,';
		$sql .= ' l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql .= ' l.buy_price_ht, l.fk_product_fournisseur_price,';
		$sql .= ' l.fk_user_author, l.fk_user_modif,';
		$sql .= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet_rec as l';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql .= ' WHERE l.rowid = '.((int) $rowid);
		$sql .= ' ORDER BY l.rang';

		dol_syslog('FactureRec::fetch', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			$this->id				= $objp->rowid;
			$this->fk_facture		= $objp->fk_facture;
			$this->fk_parent_line	= $objp->fk_parent_line;
			$this->label            = $objp->custom_label; // Label line
			$this->desc             = $objp->description; // Description line
			$this->description      = $objp->description; // Description line
			$this->product_type     = $objp->product_type; // Type of line
			$this->ref              = $objp->product_ref; // Ref product
			$this->product_ref      = $objp->product_ref; // Ref product
			$this->libelle          = $objp->product_label; // deprecated
			$this->product_label = $objp->product_label; // Label product
			$this->product_desc     = $objp->product_desc; // Description product
			$this->fk_product_type  = $objp->fk_product_type; // Type of product
			$this->qty              = $objp->qty;
			$this->price = $objp->price;
			$this->subprice         = $objp->subprice;
			$this->vat_src_code     = $objp->vat_src_code;
			$this->tva_tx           = $objp->tva_tx;
			$this->localtax1_tx     = $objp->localtax1_tx;
			$this->localtax2_tx     = $objp->localtax2_tx;
			$this->localtax1_type   = $objp->localtax1_type;
			$this->localtax2_type   = $objp->localtax2_type;
			$this->remise_percent   = $objp->remise_percent;
			//$this->fk_remise_except = $objp->fk_remise_except;
			$this->fk_product       = $objp->fk_product;
			$this->date_start_fill  = $objp->date_start_fill;
			$this->date_end_fill    = $objp->date_end_fill;
			$this->info_bits        = $objp->info_bits;
			$this->total_ht         = $objp->total_ht;
			$this->total_tva        = $objp->total_tva;
			$this->total_ttc        = $objp->total_ttc;

			$this->rang = $objp->rang;
			$this->special_code = $objp->special_code;
			$this->fk_unit          = $objp->fk_unit;
			$this->fk_contract_line = $objp->fk_contract_line;
			$this->import_key = $objp->import_key;
			$this->fk_multicurrency = $objp->fk_multicurrency;
			$this->multicurrency_code = $objp->multicurrency_code;
			$this->multicurrency_subprice = $objp->multicurrency_subprice;
			$this->multicurrency_total_ht = $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva = $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

			$this->buy_price_ht = $objp->buy_price_ht;

			$this->fk_product_fournisseur_price = $objp->fk_product_fournisseur_price;
			$this->fk_user_author = $objp->fk_user_author;
			$this->fk_user_modif = $objp->fk_user_modif;

			$this->db->free($result);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -3;
		}
	}


	/**
	 * 	Update a line to invoice_rec.
	 *
	 *  @param		User	$user					User
	 *  @param		int		$notrigger				No trigger
	 *	@return    	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		$sql = "UPDATE ".MAIN_DB_PREFIX."facturedet_rec SET";
		$sql .= " fk_facture = ".((int) $this->fk_facture);
		$sql .= ", fk_parent_line=".($this->fk_parent_line > 0 ? $this->fk_parent_line : "null");
		$sql .= ", label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= ", description='".$this->db->escape($this->desc)."'";
		$sql .= ", price=".price2num($this->price);
		$sql .= ", qty=".price2num($this->qty);
		$sql .= ", tva_tx=".price2num($this->tva_tx);
		$sql .= ", vat_src_code='".$this->db->escape($this->vat_src_code)."'";
		$sql .= ", localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", fk_product=".($this->fk_product > 0 ? $this->fk_product : "null");
		$sql .= ", product_type=".((int) $this->product_type);
		$sql .= ", remise_percent=".price2num($this->remise_percent);
		$sql .= ", subprice=".price2num($this->subprice);
		$sql .= ", info_bits=".price2num($this->info_bits);
		$sql .= ", date_start_fill=".(int) $this->date_start_fill;
		$sql .= ", date_end_fill=".(int) $this->date_end_fill;
		if (empty($this->skip_update_total)) {
			$sql .= ", total_ht=".price2num($this->total_ht);
			$sql .= ", total_tva=".price2num($this->total_tva);
			$sql .= ", total_localtax1=".price2num($this->total_localtax1);
			$sql .= ", total_localtax2=".price2num($this->total_localtax2);
			$sql .= ", total_ttc=".price2num($this->total_ttc);
		}
		$sql .= ", rang=".((int) $this->rang);
		$sql .= ", special_code=".((int) $this->special_code);
		$sql .= ", fk_unit=".($this->fk_unit ? "'".$this->db->escape($this->fk_unit)."'" : "null");
		$sql .= ", fk_contract_line=".($this->fk_contract_line ? $this->fk_contract_line : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEBILLREC_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if ($error) {
				$this->db->rollback();
				return -2;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}
}
