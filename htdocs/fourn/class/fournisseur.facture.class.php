<?php
/* Copyright (C) 2002-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2019	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2015-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016-2023	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		Nick Fragoulis
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
 *  \file       htdocs/fourn/class/fournisseur.facture.class.php
 *  \ingroup    fournisseur,facture
 *  \brief      File of class to manage suppliers invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.ligne.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}

/**
 *	Class to manage suppliers invoices
 */
class FactureFournisseur extends CommonInvoice
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'invoice_supplier';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facture_fourn';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'facture_fourn_det';

	/**
	 * @var string	Name of class line
	 */
	public $class_element_line = 'SupplierInvoiceLine';
	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_facture_fourn';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'supplier_invoice';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string Ref supplier
	 */
	public $ref_supplier;

	/**
	 * @var string 	Label of invoice
	 * @deprecated	Use $label
	 */
	public $libelle;
	/**
	 * @var string Label of invoice
	 */
	public $label;

	//Check constants for types
	public $type = self::TYPE_STANDARD;

	/**
	 * Supplier invoice status
	 * @var int
	 * @deprecated
	 * @see $status
	 */
	public $statut;

	/**
	 * Supplier invoice status
	 * @var int
	 * @see FactureFournisseur::STATUS_DRAFT, FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_PAID, FactureFournisseur::STATUS_ABANDONED
	 */
	public $status;

	/**
	 * Supplier invoice status
	 * @var int
	 * @deprecated
	 * @see $status
	 */
	public $fk_statut;

	/**
	 * Set to 1 if the invoice is completely paid, otherwise is 0
	 * @var int<0,1>
	 * @deprecated Use $paid
	 */
	public $paye;
	/**
	 * Set to 1 if the invoice is completely paid, otherwise is 0
	 * @var int<0,1>
	 */
	public $paid;

	/**
	 * @var int
	 * @deprecated	Use $user_creation_id
	 */
	public $author;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Max payment date (date_echeance)
	 *
	 * @var integer
	 */
	public $date_echeance;

	/**
	 * @var float
	 * @deprecated See $total_ttc, $total_ht, $total_tva
	 */
	public $amount = 0;
	/**
	 * @var float
	 * @deprecated
	 */
	public $remise = 0;

	/**
	 * @var float tva
	 * @deprecated Use $total_tva
	 */
	public $tva;

	// Warning: Do not set default value into property definition. it must stay null.
	// For example to avoid to have substitution done when object is generic and not yet defined.
	/** @var ?string */
	public $localtax1;
	/** @var ?string */
	public $localtax2;
	/** @var float */
	public $total_ht;
	/** @var float */
	public $total_tva;
	/** @var float */
	public $total_localtax1;
	/** @var float */
	public $total_localtax2;
	/** @var float */
	public $total_ttc;

	/**
	 * @deprecated
	 * @see $note_private, $note_public
	 * @var string
	 */
	public $note;
	/** @var string */
	public $note_private;
	/** @var string */
	public $note_public;
	/** @var int */
	public $propalid;

	/**
	 * @var int ID
	 */
	public $fk_account;		// default bank account

	/**
	 * @var int Transport mode id
	 */
	public $transport_mode_id;

	/**
	 * @var int<0,1>  VAT reverse charge can be used on the invoice
	 */
	public $vat_reverse_charge;

	/**
	 * @var array<string,string>  (Encoded as JSON in database)
	 */
	public $extraparams = array();

	/**
	 * Invoice lines
	 * @var CommonInvoiceLine[]
	 */
	public $lines = array();

	/**
	 * @deprecated
	 * @var ?Fournisseur
	 */
	public $fournisseur;

	//! id of source invoice if replacement invoice or credit note
	/**
	 * @var int ID
	 */
	public $fk_facture_source;

	/** @var int */
	public $fac_rec;
	/** @var int */
	public $fk_fac_rec_source;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 15),
		'ref_supplier' => array('type' => 'varchar(255)', 'label' => 'RefSupplier', 'enabled' => 1, 'visible' => -1, 'position' => 20),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 25, 'index' => 1),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 30),
		'type' => array('type' => 'smallint(6)', 'label' => 'Type', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
		'subtype' => array('type' => 'smallint(6)', 'label' => 'InvoiceSubtype', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 36),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'notnull' => 1, 'position' => 40),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 45),
		'datef' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => -1, 'position' => 50),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 55),
		'libelle' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'paye' => array('type' => 'smallint(6)', 'label' => 'Paye', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 65),
		'amount' => array('type' => 'double(24,8)', 'label' => 'Amount', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 70),
		'remise' => array('type' => 'double(24,8)', 'label' => 'Discount', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'close_code' => array('type' => 'varchar(16)', 'label' => 'CloseCode', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'close_note' => array('type' => 'varchar(128)', 'label' => 'CloseNote', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'tva' => array('type' => 'double(24,8)', 'label' => 'Tva', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'Localtax1', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'Localtax2', 'enabled' => 1, 'visible' => -1, 'position' => 100),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => -1, 'position' => 105),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'TotalVAT', 'enabled' => 1, 'visible' => -1, 'position' => 110),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'TotalTTC', 'enabled' => 1, 'visible' => -1, 'position' => 115),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -1, 'position' => 125),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 130),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'position' => 135),
		'fk_facture_source' => array('type' => 'integer', 'label' => 'Fk facture source', 'enabled' => 1, 'visible' => -1, 'position' => 140),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label' => 'Project', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 145),
		'fk_account' => array('type' => 'integer', 'label' => 'Account', 'enabled' => 'isModEnabled("bank")', 'visible' => -1, 'position' => 150),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'PaymentTerm', 'enabled' => 1, 'visible' => -1, 'position' => 155),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'PaymentMode', 'enabled' => 1, 'visible' => -1, 'position' => 160),
		'date_lim_reglement' => array('type' => 'date', 'label' => 'DateLimReglement', 'enabled' => 1, 'visible' => -1, 'position' => 165),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 170),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 175),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'ModelPdf', 'enabled' => 1, 'visible' => 0, 'position' => 180),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 190),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'IncotermCode', 'enabled' => 1, 'visible' => -1, 'position' => 195),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'IncotermLocation', 'enabled' => 1, 'visible' => -1, 'position' => 200),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'MulticurrencyId', 'enabled' => 1, 'visible' => -1, 'position' => 205),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'MulticurrencyCode', 'enabled' => 1, 'visible' => -1, 'position' => 210),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyRate', 'enabled' => 1, 'visible' => -1, 'position' => 215),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyTotalHT', 'enabled' => 1, 'visible' => -1, 'position' => 220),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyTotalVAT', 'enabled' => 1, 'visible' => -1, 'position' => 225),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyTotalTTC', 'enabled' => 1, 'visible' => -1, 'position' => 230),
		'date_pointoftax' => array('type' => 'date', 'label' => 'Date pointoftax', 'enabled' => 1, 'visible' => -1, 'position' => 235),
		'date_valid' => array('type' => 'date', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 240),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'Last main doc', 'enabled' => 1, 'visible' => -1, 'position' => 245),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 500),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 900),
	);


	/**
	 * @var int Id User modifying
	 */
	public $fk_user_valid;

	/**
	 * Standard invoice
	 */
	const TYPE_STANDARD = 0;

	/**
	 * Replacement invoice
	 */
	const TYPE_REPLACEMENT = 1;

	/**
	 * Credit note invoice
	 */
	const TYPE_CREDIT_NOTE = 2;

	/**
	 * Deposit invoice
	 */
	const TYPE_DEPOSIT = 3;

	/**
	 * Draft
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated (need to be paid)
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Classified paid.
	 * If paid partially, $this->close_code can be:
	 * - CLOSECODE_DISCOUNTVAT
	 * - CLOSECODE_BADCREDIT
	 * If paid completely, this->close_code will be null
	 */
	const STATUS_CLOSED = 2;

	/**
	 * Classified abandoned and no payment done.
	 * $this->close_code can be:
	 * - CLOSECODE_BADCREDIT
	 * - CLOSECODE_ABANDONED
	 * - CLOSECODE_REPLACED
	 */
	const STATUS_ABANDONED = 3;

	const CLOSECODE_DISCOUNTVAT = 'discount_vat';
	const CLOSECODE_BADCREDIT = 'badsupplier';
	const CLOSECODE_ABANDONED = 'abandon';
	const CLOSECODE_REPLACED = 'replaced';

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
	}

	/**
	 *    Create supplier invoice into database
	 *
	 *    @param      User		$user       user object that creates
	 *    @return     int    	     		Id invoice created if OK, < 0 if KO
	 */
	public function create($user)
	{
		global $langs, $conf, $hookmanager;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->ref_supplier)) {
			$this->ref_supplier = trim($this->ref_supplier);
		}
		if (empty($this->type)) {
			$this->type = self::TYPE_STANDARD;
		}
		if (empty($this->date)) {
			$this->date = $now;
		}

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $this->date);
		} else {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		$this->db->begin();

		// Defaults
		$originaldatewhen = 0;
		$nextdatewhen = 0;
		$previousdaynextdatewhen = 0;
		$_facrec = null;

		// Create invoice from a template recurring invoice
		if ($this->fac_rec > 0) {
			$this->fk_fac_rec_source = $this->fac_rec;

			require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture-rec.class.php';
			$_facrec = new FactureFournisseurRec($this->db);
			$result = $_facrec->fetch($this->fac_rec);
			$result = $_facrec->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0); // This load $_facrec->linkedObjectsIds

			// Define some dates
			if (!empty($_facrec->frequency)) {
				$originaldatewhen = $_facrec->date_when;
				$nextdatewhen = dol_time_plus_duree($originaldatewhen, $_facrec->frequency, $_facrec->unit_frequency);
				$previousdaynextdatewhen = dol_time_plus_duree($nextdatewhen, -1, 'd');
				$this->socid = $_facrec->socid;
			}

			$this->entity = $_facrec->entity; // Invoice created in same entity than template

			// Fields coming from GUI
			// @TODO Value of template should be used as default value on the form on the GUI, and we should here always use the value from GUI
			// set by posted page with $object->xxx = ... and this section should be removed.
			$this->fk_project = GETPOSTINT('projectid') > 0 ? (GETPOSTINT('projectid')) : $_facrec->fk_project;
			$this->note_public = GETPOST('note_public', 'restricthtml') ? GETPOST('note_public', 'restricthtml') : $_facrec->note_public;
			$this->note_private = GETPOST('note_private', 'restricthtml') ? GETPOST('note_private', 'restricthtml') : $_facrec->note_private;
			$this->model_pdf = GETPOST('model', 'alpha') ? GETPOST('model', 'alpha') : $_facrec->model_pdf;
			$this->cond_reglement_id = GETPOSTINT('cond_reglement_id') > 0 ? (GETPOSTINT('cond_reglement_id')) : $_facrec->cond_reglement_id;
			$this->mode_reglement_id = GETPOSTINT('mode_reglement_id') > 0 ? (GETPOSTINT('mode_reglement_id')) : $_facrec->mode_reglement_id;
			$this->fk_account = GETPOST('fk_account') > 0 ? ((int) GETPOST('fk_account')) : $_facrec->fk_account;

			// Set here to have this defined for substitution into notes, should be recalculated after adding lines to get same result
			$this->total_ht = $_facrec->total_ht;
			$this->total_ttc = $_facrec->total_ttc;

			// Fields always coming from template
			$this->fk_incoterms = $_facrec->fk_incoterms;
			$this->location_incoterms = $_facrec->location_incoterms;

			// Clean parameters
			if (! $this->type) {
				$this->type = self::TYPE_STANDARD;
			}
			$this->note_public = trim($this->note_public);
			$this->note_private = trim($this->note_private);
			$this->note_private = dol_concatdesc($this->note_private, $langs->trans("GeneratedFromRecurringInvoice", $_facrec->title));

			$this->array_options = $_facrec->array_options;

			if (! $this->mode_reglement_id) {
				$this->mode_reglement_id = 0;
			}
			$this->status = self::STATUS_DRAFT;
			$this->statut = self::STATUS_DRAFT;	// deprecated

			$this->linked_objects = $_facrec->linkedObjectsIds;
			// We do not add link to template invoice or next invoice will be linked to all generated invoices
			//$this->linked_objects['facturerec'][0] = $this->fac_rec;

			$forceduedate = $this->calculate_date_lim_reglement();

			// For recurring invoices, update date and number of last generation of recurring template invoice, before inserting new invoice
			if ($_facrec->frequency > 0) {
				$this->ref_supplier = trim($this->ref_supplier . '_' . ($_facrec->nb_gen_done + 1));
				dol_syslog("This is a recurring invoice so we set date_last_gen and next date_when");
				if (empty($_facrec->date_when)) {
					$_facrec->date_when = $now;
				}
				$next_date = $_facrec->getNextDate(); // Calculate next date
				$result = $_facrec->setValueFrom('date_last_gen', $now, '', 0, 'date', '', $user, '');
				//$_facrec->setValueFrom('nb_gen_done', $_facrec->nb_gen_done + 1);		// Not required, +1 already included into setNextDate when second param is 1.
				$result = $_facrec->setNextDate($next_date, 1);
			}

			// Define lang of customer
			$outputlangs = $langs;
			$newlang = '';

			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($this->thirdparty->default_lang)) {
				$newlang = $this->thirdparty->default_lang; // for proposal, order, invoice, ...
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && property_exists($this, 'default_lang') && isset($this->default_lang)) { // @phan-suppress-current-line PhanUndeclaredProperty
				$newlang = $this->default_lang; // for thirdparty  @phan-suppress-current-line PhanUndeclaredProperty
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			} // Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $this);
			$substitutionarray['__INVOICE_PREVIOUS_MONTH__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%m');
			$substitutionarray['__INVOICE_MONTH__'] = dol_print_date($this->date, '%m');
			$substitutionarray['__INVOICE_NEXT_MONTH__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%m');
			$substitutionarray['__INVOICE_PREVIOUS_MONTH_TEXT__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%B');
			$substitutionarray['__INVOICE_MONTH_TEXT__'] = dol_print_date($this->date, '%B');
			$substitutionarray['__INVOICE_NEXT_MONTH_TEXT__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%B');
			$substitutionarray['__INVOICE_PREVIOUS_YEAR__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'y'), '%Y');
			$substitutionarray['__INVOICE_YEAR__'] = dol_print_date($this->date, '%Y');
			$substitutionarray['__INVOICE_NEXT_YEAR__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'y'), '%Y'); // Only for template invoice
			$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__'] = $originaldatewhen ? dol_print_date($originaldatewhen, 'dayhour') : '';
			$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__'] = $nextdatewhen ? dol_print_date($nextdatewhen, 'dayhour') : '';
			$substitutionarray['__INVOICE_PREVIOUS_DATE_NEXT_INVOICE_AFTER_GEN__'] = $previousdaynextdatewhen ? dol_print_date($previousdaynextdatewhen, 'dayhour') : '';
			$substitutionarray['__INVOICE_COUNTER_CURRENT__'] = $_facrec->nb_gen_done;
			$substitutionarray['__INVOICE_COUNTER_MAX__'] = $_facrec->nb_gen_max;

			complete_substitutions_array($substitutionarray, $outputlangs);

			$this->note_public = make_substitutions($this->note_public, $substitutionarray);
			$this->note_private = make_substitutions($this->note_private, $substitutionarray);
		}

		// Define due date if not already defined
		if (!empty($forceduedate)) {
			$this->date_echeance = $forceduedate;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (";
		$sql .= "ref";
		$sql .= ", ref_supplier";
		$sql .= ", ref_ext";
		$sql .= ", entity";
		$sql .= ", type";
		$sql .= ", subtype";
		$sql .= ", libelle";
		$sql .= ", fk_soc";
		$sql .= ", datec";
		$sql .= ", datef";
		$sql .= ", vat_reverse_charge";
		$sql .= ", fk_projet";
		$sql .= ", fk_cond_reglement";
		$sql .= ", fk_mode_reglement";
		$sql .= ", fk_account";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", fk_user_author";
		$sql .= ", date_lim_reglement";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ", fk_facture_source";
		$sql .= ", fk_fac_rec_source";
		$sql .= ")";
		$sql .= " VALUES (";
		$sql .= "'(PROV)'";
		$sql .= ", '".$this->db->escape($this->ref_supplier)."'";
		$sql .= ", '".$this->db->escape($this->ref_ext)."'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", '".$this->db->escape($this->type)."'";
		$sql .= ", ".((int) $this->subtype);
		$sql .= ", '".$this->db->escape(isset($this->label) ? $this->label : (isset($this->libelle) ? $this->libelle : ''))."'";
		$sql .= ", ".((int) $this->socid);
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '".$this->db->idate($this->date)."'";
		$sql .= ", ".($this->vat_reverse_charge != '' ? ((int) $this->db->escape($this->vat_reverse_charge)) : 0);
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", ".($this->cond_reglement_id > 0 ? ((int) $this->cond_reglement_id) : "null");
		$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : "null");
		$sql .= ", ".($this->fk_account > 0 ? ((int) $this->fk_account) : 'NULL');
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", ".((int) $user->id).",";
		$sql .= $this->date_echeance != '' ? "'".$this->db->idate($this->date_echeance)."'" : "null";
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".(float) $this->multicurrency_tx;
		$sql .= ", ".($this->fk_facture_source ? ((int) $this->fk_facture_source) : "null");
		$sql .= ", ".(isset($this->fk_fac_rec_source) ? $this->fk_fac_rec_source : "NULL");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');

			// Update ref with new one
			$this->ref = '(PROV'.$this->id.')';
			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture_fourn SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
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
								dol_print_error($this->db);
								$error++;
							}
						}
					} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
						$origin_id = $tmp_origin_id;
						$ret = $this->add_object_linked($origin, $origin_id);
						if (!$ret) {
							dol_print_error($this->db);
							$error++;
						}
					}
				}
			}

			if (!$error && empty($this->fac_rec) && count($this->lines) && is_object($this->lines[0])) {	// If this->lines is array of InvoiceLines (preferred mode)
				dol_syslog("There is ".count($this->lines)." lines that are invoice lines objects");
				foreach ($this->lines as $i => $val) {
					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn, special_code, fk_remise_except)';
					$sql .= " VALUES (".((int) $this->id).", ".((int) $this->lines[$i]->special_code).", ".($this->lines[$i]->fk_remise_except > 0 ? ((int) $this->lines[$i]->fk_remise_except) : 'NULL').')';

					$resql_insert = $this->db->query($sql);
					if ($resql_insert) {
						$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

						$res = $this->updateline(
							$idligne,
							$this->lines[$i]->desc ? $this->lines[$i]->desc : $this->lines[$i]->description,
							$this->lines[$i]->subprice,
							$this->lines[$i]->tva_tx.($this->lines[$i]->vat_src_code ? ' ('.$this->lines[$i]->vat_src_code.')' : ''),
							$this->lines[$i]->localtax1_tx,
							$this->lines[$i]->localtax2_tx,
							$this->lines[$i]->qty,
							$this->lines[$i]->fk_product,
							'HT',
							(!empty($this->lines[$i]->info_bits) ? $this->lines[$i]->info_bits : ''),
							$this->lines[$i]->product_type,
							$this->lines[$i]->remise_percent,
							0,
							$this->lines[$i]->date_start,
							$this->lines[$i]->date_end,
							$this->lines[$i]->array_options,
							$this->lines[$i]->fk_unit,
							$this->lines[$i]->multicurrency_subprice,
							$this->lines[$i]->ref_supplier
						);
					} else {
						$this->error = $this->db->lasterror();
						$this->db->rollback();
						return -5;
					}
				}
			} elseif (!$error && empty($this->fac_rec)) {   // If this->lines is an array of invoice line arrays
				dol_syslog("There is ".count($this->lines)." lines that are array lines");
				foreach ($this->lines as $i => $val) {
					$line = $this->lines[$i];

					// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
					//if (! is_object($line)) $line=json_decode(json_encode($line), false);  // convert recursively array into object.
					if (!is_object($line)) {
						$line = (object) $line;
					}

					$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn, special_code, fk_remise_except)';
					$sql .= " VALUES (".((int) $this->id).", ".((int) $this->lines[$i]->special_code).", ".($this->lines[$i]->fk_remise_except > 0 ? ((int) $this->lines[$i]->fk_remise_except) : 'NULL').')';

					$resql_insert = $this->db->query($sql);
					if ($resql_insert) {
						$idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

						$this->updateline(
							$idligne,
							$line->desc ? $line->desc : $line->description,
							$line->subprice,
							$line->tva_tx,
							$line->localtax1_tx,
							$line->localtax2_tx,
							$line->qty,
							$line->fk_product,
							'HT',
							(!empty($line->info_bits) ? $line->info_bits : ''),
							$line->product_type,
							$line->remise_percent,
							0,
							$line->date_start,
							$line->date_end,
							$line->array_options,
							$line->fk_unit,
							$line->multicurrency_subprice,
							$line->ref_supplier
						);
					} else {
						$this->error = $this->db->lasterror();
						$this->db->rollback();
						return -5;
					}
				}
			}

			/*
			 * Insert lines of template invoices
			 */
			if (! $error && $this->fac_rec > 0 && $_facrec instanceof FactureFournisseurRec) {
				foreach ($_facrec->lines as $i => $val) {
					if ($_facrec->lines[$i]->fk_product) {
						$prod = new Product($this->db);
						$res = $prod->fetch($_facrec->lines[$i]->fk_product);
					}

					// For line from template invoice, we use data from template invoice
					/*
					$tva_tx = get_default_tva($mysoc,$soc,$prod->id);
					$tva_npr = get_default_npr($mysoc,$soc,$prod->id);
					if (empty($tva_tx)) $tva_npr=0;
					$localtax1_tx=get_localtax($tva_tx,1,$soc,$mysoc,$tva_npr);
					$localtax2_tx=get_localtax($tva_tx,2,$soc,$mysoc,$tva_npr);
					*/
					$tva_tx = $_facrec->lines[$i]->tva_tx . ($_facrec->lines[$i]->vat_src_code ? '(' . $_facrec->lines[$i]->vat_src_code . ')' : '');
					$tva_npr = $_facrec->lines[$i]->info_bits;
					if (empty($tva_tx)) {
						$tva_npr = 0;
					}
					$localtax1_tx = $_facrec->lines[$i]->localtax1_tx;
					$localtax2_tx = $_facrec->lines[$i]->localtax2_tx;

					// $fk_product_fournisseur_price not used and does not exist on line
					// $fk_product_fournisseur_price = empty($_facrec->lines[$i]->fk_product_fournisseur_price) ? null : $_facrec->lines[$i]->fk_product_fournisseur_price;
					$buyprice = empty($_facrec->lines[$i]->buyprice) ? 0 : $_facrec->lines[$i]->buyprice;

					// If buyprice not defined from template invoice, we try to guess the best value
					if (! $buyprice && $_facrec->lines[$i]->fk_product > 0) {
						require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
						$producttmp = new ProductFournisseur($this->db);
						$producttmp->fetch($_facrec->lines[$i]->fk_product);

						// If margin module defined on costprice, we try the costprice
						// If not defined or if module margin defined and pmp and stock module enabled, we try pmp price
						// else we get the best supplier price
						if (getDolGlobalString('MARGIN_TYPE') == 'costprice' && !empty($producttmp->cost_price)) {
							$buyprice = $producttmp->cost_price;
						} elseif (isModEnabled('stock') && (getDolGlobalString('MARGIN_TYPE') == 'costprice' || getDolGlobalString('MARGIN_TYPE') == 'pmp') && !empty($producttmp->pmp)) {
							$buyprice = $producttmp->pmp;
						} else {
							if ($producttmp->find_min_price_product_fournisseur($_facrec->lines[$i]->fk_product) > 0) {
								if ($producttmp->product_fourn_price_id > 0) {
									$buyprice = price2num($producttmp->fourn_unitprice * (1 - $producttmp->fourn_remise_percent / 100) + $producttmp->fourn_remise, 'MU');
								}
							}
						}
					}

					$result_insert = $this->addline(
						$_facrec->lines[$i]->desc ? $_facrec->lines[$i]->desc : $_facrec->lines[$i]->description,
						$_facrec->lines[$i]->pu_ht,
						$tva_tx,
						$localtax1_tx,
						$localtax2_tx,
						$_facrec->lines[$i]->qty,
						$_facrec->lines[$i]->fk_product,
						$_facrec->lines[$i]->remise_percent,
						($_facrec->lines[$i]->date_start == 1 && $this->date) ? $this->date : '',
						($_facrec->lines[$i]->date_end == 1 && $previousdaynextdatewhen) ? $previousdaynextdatewhen : '',
						0,
						$_facrec->lines[$i]->info_bits,
						'HT',
						0,
						$_facrec->lines[$i]->rang,
						0,
						$_facrec->lines[$i]->array_options,
						$_facrec->lines[$i]->fk_unit,
						0,
						0,
						$_facrec->lines[$i]->ref_supplier,
						$_facrec->lines[$i]->special_code,
						0,
						0
					);
					if ($result_insert < 0) {
						$error++;
						$this->error = $this->db->error();
						break;
					}
				}
			}


			// Update total price
			$result = $this->update_price(1);
			if ($result > 0) {
				// Actions on extra fields
				if (!$error) {
					$result = $this->insertExtraFields(); // This also set $this->error or $this->errors if errors are found
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error) {
					// Call trigger
					$result = $this->call_trigger('BILL_SUPPLIER_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					$this->db->commit();
					return $this->id;
				} else {
					$this->db->rollback();
					return -4;
				}
			} else {
				$this->error = $langs->trans('FailedToUpdatePrice');
				$this->db->rollback();
				return -3;
			}
		} else {
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->error = $langs->trans('ErrorRefAlreadyExists');
				$this->db->rollback();
				return -1;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id supplier invoice
	 *  @param	string	$ref		Ref supplier invoice
	 * 	@param	string	$ref_ext	External reference of invoice
	 *  @return int  	   			Return integer <0 if KO, >0 if OK, 0 if not found
	 */
	public function fetch($id = 0, $ref = '', $ref_ext = '')
	{
		if (empty($id) && empty($ref) && empty($ref_ext)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.ref_supplier,";
		$sql .= " t.ref_ext,";
		$sql .= " t.entity,";
		$sql .= " t.type,";
		$sql .= " t.subtype,";
		$sql .= " t.fk_soc,";
		$sql .= " t.datec,";
		$sql .= " t.datef,";
		$sql .= " t.tms,";
		$sql .= " t.libelle as label,";
		$sql .= " t.paye,";
		$sql .= " t.close_code,";
		$sql .= " t.close_note,";
		$sql .= " t.tva,";
		$sql .= " t.localtax1,";
		$sql .= " t.localtax2,";
		$sql .= " t.total_ht,";
		$sql .= " t.total_tva,";
		$sql .= " t.total_ttc,";
		$sql .= " t.fk_statut as status,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_valid,";
		$sql .= " t.fk_facture_source,";
		$sql .= " t.vat_reverse_charge,";
		$sql .= " t.fk_fac_rec_source,";
		$sql .= " t.fk_projet as fk_project,";
		$sql .= " t.fk_cond_reglement,";
		$sql .= " t.fk_account,";
		$sql .= " t.fk_mode_reglement,";
		$sql .= " t.date_lim_reglement,";
		$sql .= " t.note_private,";
		$sql .= " t.note_public,";
		$sql .= " t.model_pdf,";
		$sql .= " t.last_main_doc,";
		$sql .= " t.import_key,";
		$sql .= " t.extraparams,";
		$sql .= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_label, cr.libelle_facture as cond_reglement_doc,";
		$sql .= " p.code as mode_reglement_code, p.libelle as mode_reglement_label,";
		$sql .= ' s.nom as socnom, s.rowid as socid,';
		$sql .= ' t.fk_incoterms, t.location_incoterms,';
		$sql .= " i.libelle as label_incoterms,";
		$sql .= ' t.fk_transport_mode,';
		$sql .= ' t.fk_multicurrency, t.multicurrency_code, t.multicurrency_tx, t.multicurrency_total_ht, t.multicurrency_total_tva, t.multicurrency_total_ttc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as t';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (t.fk_soc = s.rowid)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cr ON t.fk_cond_reglement = cr.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as p ON t.fk_mode_reglement = p.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON t.fk_incoterms = i.rowid';
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} else {
			$sql .= ' WHERE t.entity IN ('.getEntity('supplier_invoice').')'; // Don't use entity if you use rowid
			if ($ref) {
				$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
			}
			if ($ref_ext) {
				$sql .= " AND t.ref_ext = '".$this->db->escape($ref_ext)."'";
			}
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->ref ? $obj->ref : $obj->rowid; // We take rowid if ref is empty for backward compatibility

				$this->ref_supplier         = $obj->ref_supplier;
				$this->ref_ext			    = $obj->ref_ext;
				$this->entity				= $obj->entity;
				$this->type					= empty($obj->type) ? self::TYPE_STANDARD : $obj->type;
				$this->subtype				= (int) $obj->subtype;
				$this->socid				= $obj->fk_soc;
				$this->datec				= $this->db->jdate($obj->datec);
				$this->date					= $this->db->jdate($obj->datef);
				//$this->datep				= $this->db->jdate($obj->datef);
				$this->tms                  = $this->db->jdate($obj->tms);
				$this->libelle              = $obj->label; // deprecated
				$this->label				= $obj->label;
				$this->paye					= $obj->paye;
				$this->paid					= $obj->paye;
				$this->close_code			= $obj->close_code;
				$this->close_note			= $obj->close_note;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ht				= $obj->total_ht;
				$this->total_tva			= $obj->total_tva;
				$this->total_ttc			= $obj->total_ttc;
				$this->status				= $obj->status;
				$this->statut				= $obj->status;	// For backward compatibility
				$this->fk_statut			= $obj->status;	// For backward compatibility
				$this->user_creation_id     = $obj->fk_user_author;
				$this->author				= $obj->fk_user_author;	// deprecated
				$this->user_validation_id   = $obj->fk_user_valid;
				$this->fk_facture_source	= $obj->fk_facture_source;
				$this->vat_reverse_charge	= empty($obj->vat_reverse_charge) ? 0 : 1;
				$this->fk_fac_rec_source	= $obj->fk_fac_rec_source;
				$this->fk_project           = $obj->fk_project;
				$this->cond_reglement_id	= $obj->fk_cond_reglement;
				$this->cond_reglement_code  = $obj->cond_reglement_code;
				$this->cond_reglement       = $obj->cond_reglement_label; // deprecated
				$this->cond_reglement_label = $obj->cond_reglement_label;
				$this->cond_reglement_doc   = $obj->cond_reglement_doc;
				$this->fk_account           = $obj->fk_account;
				$this->mode_reglement_id    = $obj->fk_mode_reglement;
				$this->mode_reglement_code  = $obj->mode_reglement_code;
				$this->mode_reglement       = $obj->mode_reglement_label;
				$this->date_echeance		= $this->db->jdate($obj->date_lim_reglement);
				$this->note                 = $obj->note_private; // deprecated
				$this->note_private			= $obj->note_private;
				$this->note_public          = $obj->note_public;
				$this->model_pdf            = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;
				$this->import_key           = $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;
				$this->transport_mode_id = $obj->fk_transport_mode;

				// Multicurrency
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx = $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva = $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

				$this->extraparams = isset($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

				$this->socid  = $obj->socid;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$result = $this->fetch_lines();
				if ($result < 0) {
					$this->error = $this->db->lasterror();
					return -3;
				}
			} else {
				$this->error = 'Bill with id '.$id.' not found';
				dol_syslog(get_class($this).'::fetch '.$this->error);
				return 0;
			}

			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load this->lines
	 *
	 *  @return     int         1 si ok, < 0 si erreur
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		$this->lines = array();

		$sql = 'SELECT f.rowid, f.ref as ref_supplier, f.description as line_desc, f.date_start, f.date_end, f.pu_ht, f.pu_ttc, f.qty, f.remise_percent, f.vat_src_code, f.tva_tx';
		$sql .= ', f.localtax1_tx, f.localtax2_tx, f.localtax1_type, f.localtax2_type, f.total_localtax1, f.total_localtax2, f.fk_facture_fourn, f.fk_remise_except';
		$sql .= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_product, f.product_type, f.info_bits, f.rang, f.special_code, f.fk_parent_line, f.fk_unit';
		$sql .= ', p.rowid as product_id, p.ref as product_ref, p.label as label, p.barcode as product_barcode, p.description as product_desc';
		$sql .= ', f.fk_code_ventilation, f.fk_multicurrency, f.multicurrency_code, f.multicurrency_subprice, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
		$sql .= ' WHERE fk_facture_fourn='.((int) $this->id);
		$sql .= ' ORDER BY f.rang, f.rowid';

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);

		$resql_rows = $this->db->query($sql);
		if ($resql_rows) {
			$num_rows = $this->db->num_rows($resql_rows);
			if ($num_rows) {
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql_rows);

					$line = new SupplierInvoiceLine($this->db);

					$line->id               = $obj->rowid;
					$line->rowid            = $obj->rowid;
					$line->description      = $obj->line_desc;
					$line->desc             = $obj->line_desc;
					$line->date_start       = $obj->date_start;
					$line->date_end         = $obj->date_end;
					$line->product_ref      = $obj->product_ref;
					$line->ref              = $obj->product_ref;
					$line->ref_supplier		= $obj->ref_supplier;
					$line->libelle			= $obj->label;
					$line->label  			= $obj->label;
					$line->product_barcode  = $obj->product_barcode;
					$line->product_desc		= $obj->product_desc;
					$line->subprice         = $obj->pu_ht;
					$line->pu_ht            = $obj->pu_ht;
					$line->pu_ttc			= $obj->pu_ttc;
					$line->vat_src_code     = $obj->vat_src_code;
					$line->tva_tx			= $obj->tva_tx;
					$line->localtax1_tx		= $obj->localtax1_tx;
					$line->localtax2_tx		= $obj->localtax2_tx;
					$line->localtax1_type	= $obj->localtax1_type;
					$line->localtax2_type	= $obj->localtax2_type;
					$line->qty				= $obj->qty;
					$line->remise_percent   = $obj->remise_percent;
					$line->fk_remise_except = $obj->fk_remise_except;
					//$line->tva			= $obj->total_tva; // deprecated
					$line->total_ht			= $obj->total_ht;
					$line->total_ttc		= $obj->total_ttc;
					$line->total_tva		= $obj->total_tva;
					$line->total_localtax1	= $obj->total_localtax1;
					$line->total_localtax2	= $obj->total_localtax2;
					$line->fk_facture_fourn = $obj->fk_facture_fourn;
					$line->fk_product       = $obj->fk_product;
					$line->product_type		= $obj->product_type;
					$line->product_label	= $obj->label;
					$line->info_bits        = $obj->info_bits;
					$line->fk_parent_line   = $obj->fk_parent_line;
					$line->special_code		= $obj->special_code;
					$line->rang             = $obj->rang;
					$line->fk_unit          = $obj->fk_unit;

					// Accountancy
					$line->fk_accounting_account = $obj->fk_code_ventilation;

					// Multicurrency
					$line->fk_multicurrency = $obj->fk_multicurrency;
					$line->multicurrency_code = $obj->multicurrency_code;
					$line->multicurrency_subprice = $obj->multicurrency_subprice;
					$line->multicurrency_total_ht = $obj->multicurrency_total_ht;
					$line->multicurrency_total_tva = $obj->multicurrency_total_tva;
					$line->multicurrency_total_ttc = $obj->multicurrency_total_ttc;

					// Extra fields
					$line->fetch_optionals();

					$this->lines[$i] = $line;

					$i++;
				}
			}
			$this->db->free($resql_rows);
			return 1;
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this)."::fetch_lines - No lines:{$this->error} Error:{$this->error}", LOG_DEBUG);
			return -3;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user            User that modify
	 *  @param  int		$notrigger       0=launch triggers after, 1=disable triggers
	 *  @return int 			         Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		if (empty($this->type)) {
			$this->type = self::TYPE_STANDARD;
		}
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_supplier)) {
			$this->ref_supplier = trim($this->ref_supplier);
		}
		if (isset($this->ref_ext)) {
			$this->ref_ext = trim($this->ref_ext);
		}
		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->type)) {
			$this->type = (int) $this->type;
		}
		if (isset($this->subtype)) {
			$this->subtype = (int) $this->subtype;
		}
		if (isset($this->socid)) {
			$this->socid = (int) $this->socid;
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->paid)) {
			$this->paid = (int) (bool) $this->paye;
			$this->paye = $this->paid;
		} elseif (isset($this->paye)) {
			$this->paid = (int) (bool) $this->paye;
			$this->paye = $this->paid;
		}
		if (isset($this->close_code)) {
			$this->close_code = trim($this->close_code);
		}
		if (isset($this->close_note)) {
			$this->close_note = trim($this->close_note);
		}
		if (empty($this->total_ht)) {
			$this->total_ht = 0;
		}
		if (empty($this->total_tva)) {
			$this->total_tva = 0;
		}
		if (isset($this->total_ttc)) {
			$this->total_ttc = (float) $this->total_ttc;
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
			$this->statut = $this->status;
		} elseif (isset($this->statut)) {
			$this->status = (int) $this->statut;
			$this->statut = $this->status;
		}
		if (isset($this->author)) {  // TODO: user_creation_id?
			$this->author = (int) $this->author;
		}
		if (isset($this->fk_user_valid)) {
			$this->fk_user_valid = (int) $this->fk_user_valid;
		}
		if (isset($this->fk_facture_source)) {
			$this->fk_facture_source = (int) $this->fk_facture_source;
		}
		if (isset($this->fk_project)) {
			if (empty($this->fk_project)) {
				$this->fk_project = 0;
			} else {
				$this->fk_project = (int) $this->fk_project;
			}
		}
		if (isset($this->cond_reglement_id)) {
			$this->cond_reglement_id = (int) $this->cond_reglement_id;
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
			$this->note = $this->note_private;
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}


		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_supplier=".(isset($this->ref_supplier) ? "'".$this->db->escape($this->ref_supplier)."'" : "null").",";
		$sql .= " ref_ext=".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " entity=".(isset($this->entity) ? ((int) $this->entity) : "null").",";
		$sql .= " type=".(isset($this->type) ? ((int) $this->type) : "null").",";
		$sql .= " subtype=".((int) $this->subtype).",";
		$sql .= " fk_soc=".(isset($this->socid) ? ((int) $this->socid) : "null").",";
		$sql .= " datec=".(dol_strlen((string) $this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " datef=".(dol_strlen((string) $this->date) != 0 ? "'".$this->db->idate($this->date)."'" : 'null').",";
		if (dol_strlen((string) $this->tms) != 0) {
			$sql .= " tms=".(dol_strlen((string) $this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		}
		$sql .= " libelle=".(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " paye=".(isset($this->paid) ? ((int) $this->paid) : "0").",";
		$sql .= " close_code=".(isset($this->close_code) ? "'".$this->db->escape($this->close_code)."'" : "null").",";
		$sql .= " close_note=".(isset($this->close_note) ? "'".$this->db->escape($this->close_note)."'" : "null").",";
		$sql .= " localtax1=".(isset($this->total_localtax1) ? ((float) $this->total_localtax1) : "null").",";
		$sql .= " localtax2=".(isset($this->total_localtax2) ? ((float) $this->total_localtax2) : "null").",";
		$sql .= " total_ht=".(isset($this->total_ht) ? ((float) $this->total_ht) : "null").",";
		$sql .= " total_tva=".(isset($this->total_tva) ? ((float) $this->total_tva) : "null").",";
		$sql .= " total_ttc=".(isset($this->total_ttc) ? ((float) $this->total_ttc) : "null").",";
		$sql .= " fk_statut=".(isset($this->status) ? ((int) $this->status) : (isset($this->statut) ? ((int) $this->statut) : "null")).",";
		$sql .= " fk_user_author=".(isset($this->author) ? ((int) $this->author) : "null").",";
		$sql .= " fk_user_valid=".(isset($this->fk_user_valid) ? ((int) $this->fk_user_valid) : "null").",";
		$sql .= " fk_facture_source=".($this->fk_facture_source ? ((int) $this->fk_facture_source) : "null").",";
		$sql .= " vat_reverse_charge = ".($this->vat_reverse_charge != '' ? ((int) $this->db->escape($this->vat_reverse_charge)) : 0).",";
		$sql .= " fk_projet=".(!empty($this->fk_project) ? ((int) $this->fk_project) : "null").",";
		$sql .= " fk_cond_reglement=".(isset($this->cond_reglement_id) ? ((int) $this->cond_reglement_id) : "null").",";
		$sql .= " date_lim_reglement=".(dol_strlen((string) $this->date_echeance) != 0 ? "'".$this->db->idate($this->date_echeance)."'" : 'null').",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$error++;

			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->errors[] = $langs->trans('ErrorRefAlreadyExists');
			} else {
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILL_SUPPLIER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Add a discount line into an invoice (as an invoice line) using an existing absolute discount (Consume the discount)
	 *
	 *    @param     int	$idremise	Id of absolute discount
	 *    @return    int          		>0 if OK, <0 if KO
	 */
	public function insert_discount($idremise)
	{
		// phpcs:enable
		global $conf, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$this->db->begin();

		$remise = new DiscountAbsolute($this->db);
		$result = $remise->fetch($idremise);

		if ($result > 0) {
			if ($remise->fk_invoice_supplier) {	// Protection against multiple submission
				$this->error = $langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}

			$facligne = new SupplierInvoiceLine($this->db);
			$facligne->fk_facture_fourn = $this->id;
			$facligne->fk_remise_except = $remise->id;
			$facligne->desc = $remise->description; // Description ligne
			$facligne->vat_src_code = $remise->vat_src_code;
			$facligne->tva_tx = $remise->tva_tx;
			$facligne->subprice = -$remise->amount_ht;
			$facligne->fk_product = 0; // Id produit predefini
			$facligne->product_type = 0;
			$facligne->qty = 1;
			$facligne->remise_percent = 0;
			$facligne->rang = -1;
			$facligne->info_bits = 2;

			if (getDolGlobalString('MAIN_ADD_LINE_AT_POSITION')) {
				$facligne->rang = 1;
				$linecount = count($this->lines);
				for ($ii = 1; $ii <= $linecount; $ii++) {
					$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
				}
			}

			// Get buy/cost price of invoice that is source of discount
			if ($remise->fk_invoice_supplier_source > 0) {
				$srcinvoice = new FactureFournisseur($this->db);
				$srcinvoice->fetch($remise->fk_invoice_supplier_source);
				$totalcostpriceofinvoice = 0;
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php'; // TODO Move this into commonobject
				$formmargin = new FormMargin($this->db);
				$arraytmp = $formmargin->getMarginInfosArray($srcinvoice, false);
				$facligne->pa_ht = $arraytmp['pa_total'];
			}

			$facligne->total_ht  = -$remise->amount_ht;
			$facligne->total_tva = -$remise->amount_tva;
			$facligne->total_ttc = -$remise->amount_ttc;

			$facligne->multicurrency_subprice = -$remise->multicurrency_subprice;
			$facligne->multicurrency_total_ht = -$remise->multicurrency_total_ht;
			$facligne->multicurrency_total_tva = -$remise->multicurrency_total_tva;
			$facligne->multicurrency_total_ttc = -$remise->multicurrency_total_ttc;

			$lineid = $facligne->insert();
			if ($lineid > 0) {
				$result = $this->update_price(1);
				if ($result > 0) {
					// Create link between discount and invoice line
					$result = $remise->link_to_invoice($lineid, 0);
					if ($result < 0) {
						$this->error = $remise->error;
						$this->db->rollback();
						return -4;
					}

					$this->db->commit();
					return 1;
				} else {
					$this->error = $facligne->error;
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $facligne->error;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -3;
		}
	}


	/**
	 *	Delete invoice from database
	 *
	 *  @param      User	$user		    User object
	 *	@param	    int		$notrigger	    1=Does not execute triggers, 0= execute triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf;

		$rowid = $this->id;

		dol_syslog("FactureFournisseur::delete rowid=".$rowid, LOG_DEBUG);

		// TODO Test if there is at least on payment. If yes, refuse to delete.

		$error = 0;
		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('BILL_SUPPLIER_DELETE', $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
			// Fin appel triggers
		}

		if (!$error) {
			// If invoice was converted into a discount not yet consumed, we remove discount
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql .= ' WHERE fk_invoice_supplier_source = '.((int) $rowid);
			$sql .= ' AND fk_invoice_supplier_line IS NULL';
			$resql = $this->db->query($sql);

			// If invoice has consumned discounts
			$this->fetch_lines();
			$list_rowid_det = array();
			foreach ($this->lines as $key => $invoiceline) {
				$list_rowid_det[] = $invoiceline->id;
			}

			// Consumned discounts are freed
			if (count($list_rowid_det)) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
				$sql .= ' SET fk_invoice_supplier = NULL, fk_invoice_supplier_line = NULL';
				$sql .= ' WHERE fk_invoice_supplier_line IN ('.$this->db->sanitize(implode(',', $list_rowid_det)).')';

				dol_syslog(get_class($this)."::delete", LOG_DEBUG);
				if (!$this->db->query($sql)) {
					$error++;
				}
			}
		}

		if (!$error) {
			$main = MAIN_DB_PREFIX.'facture_fourn_det';
			$ef = $main."_extrafields";
			$sqlef = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM ".$main." WHERE fk_facture_fourn = ".((int) $rowid).")";
			$resqlef = $this->db->query($sqlef);
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.((int) $rowid);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resqlef && $resql) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.((int) $rowid);
				dol_syslog(get_class($this)."::delete", LOG_DEBUG);
				$resql2 = $this->db->query($sql);
				if (!$resql2) {
					$error++;
				}
			} else {
				$error++;
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
			$this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
			$this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive

			// We remove directory
			if ($conf->fournisseur->facture->dir_output) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				$ref = dol_sanitizeFileName($this->ref);
				$dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$ref;
				$file = $dir."/".$ref.".pdf";
				if (file_exists($file)) {
					if (!dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
						$this->error = 'ErrorFailToDeleteFile';
						$error++;
					}
				}
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);

					if (!$res) {
						$this->error = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
			}
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete $this->id by $user->id", LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -$error;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag invoice as a paid invoice
	 *
	 *	@deprecated
	 *  @see setPaid()
	 *	@param  User	$user       Object user
	 *	@param  string	$close_code	Code indicates whether the class has paid in full while payment is incomplete. Not implemented yet.
	 *	@param  string	$close_note	Comment informs if the class has been paid while payment is incomplete. Not implemented yet.
	 *	@return int         		Return integer <0 si ko, >0 si ok
	 */
	public function set_paid($user, $close_code = '', $close_note = '')
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_paid is deprecated, use setPaid instead", LOG_NOTICE);
		return $this->setPaid($user, $close_code, $close_note);
	}

	/**
	 *  Tag invoice as a paid invoice
	 *
	 *	@param  User	$user       Object user
	 *	@param  string	$close_code	Code indicates whether the class has paid in full while payment is incomplete. Not implemented yet.
	 *	@param  string	$close_note	Comment informs if the class has been paid while payment is incomplete. Not implemented yet.
	 *	@return int<-1,1>     		Return integer <0 si ko, >0 si ok
	 */
	public function setPaid($user, $close_code = '', $close_note = '')
	{
		$error = 0;

		if ($this->paid != 1) {
			$this->db->begin();

			$now = dol_now();

			dol_syslog("FactureFournisseur::setPaid", LOG_DEBUG);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn SET';
			$sql .= ' fk_statut = '.self::STATUS_CLOSED;
			if (!$close_code) {
				$sql .= ', paye=1';
			}
			if ($close_code) {
				$sql .= ", close_code='".$this->db->escape($close_code)."'";
			}
			if ($close_note) {
				$sql .= ", close_note='".$this->db->escape($close_note)."'";
			}
			$sql .= ', fk_user_closing = '.((int) $user->id);
			$sql .= ", date_closing = '".$this->db->idate($now)."'";
			$sql .= ' WHERE rowid = '.((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('BILL_SUPPLIER_PAYED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			} else {
				$error++;
				$this->error = $this->db->error();
				dol_print_error($this->db);
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Tag the invoice as not fully paid + trigger call BILL_UNPAYED
	 *	Function used when a direct debit payment is refused,
	 *	or when the invoice was canceled and reopened.
	 *
	 *	@deprecated
	 *  @see setUnpaid()
	 *	@param      User	$user       Object user that change status
	 *	@return     int         		Return integer <0 si ok, >0 si ok
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_unpaid is deprecated, use setUnpaid instead", LOG_NOTICE);
		return $this->setUnpaid($user);
	}

	/**
	 *	Tag the invoice as not fully paid + trigger call BILL_UNPAYED
	 *	Function used when a direct debit payment is refused,
	 *	or when the invoice was canceled and reopened.
	 *
	 *	@param      User	$user       Object user that change status
	 *	@return     int         		Return integer <0 si ok, >0 si ok
	 */
	public function setUnpaid($user)
	{
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
		$sql .= ' SET paye=0, fk_statut='.self::STATUS_VALIDATED.', close_code=null, close_note=null,';
		$sql .= ' date_closing=null,';
		$sql .= ' fk_user_closing=null';
		$sql .= ' WHERE rowid = '.((int) $this->id);

		dol_syslog(get_class($this)."::set_unpaid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('BILL_SUPPLIER_UNPAYED', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			$this->error = $this->db->error();
			dol_print_error($this->db);
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Tag invoice as canceled, with no payment on it (example for replacement invoice or payment never received) + call trigger BILL_CANCEL
	 *	Warning, if option to decrease stock on invoice was set, this function does not change stock (it might be a cancel because
	 *  of no payment even if merchandises were sent).
	 *
	 *	@param	User	$user        	Object user making change
	 *	@param	string	$close_code		Code of closing invoice (CLOSECODE_REPLACED, CLOSECODE_...)
	 *	@param	string	$close_note		Comment
	 *	@return int         			Return integer <0 if KO, >0 if OK
	 */
	public function setCanceled($user, $close_code = '', $close_note = '')
	{
		dol_syslog(get_class($this)."::setCanceled rowid=".((int) $this->id), LOG_DEBUG);

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn SET';
		$sql .= ' fk_statut='.self::STATUS_ABANDONED;
		if ($close_code) {
			$sql .= ", close_code='".$this->db->escape($close_code)."'";
		}
		if ($close_note) {
			$sql .= ", close_note='".$this->db->escape($close_note)."'";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			// Bound discounts are deducted from the invoice
			// as they have not been used since the invoice is abandoned.
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql .= ' SET fk_invoice_supplier = NULL';
			$sql .= ' WHERE fk_invoice_supplier = '.((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('BILL_SUPPLIER_CANCEL', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers

				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *	Tag invoice as validated + call trigger BILL_VALIDATE
	 *
	 *	@param	User	$user           Object user that validate
	 *	@param  string	$force_number   Reference to force on invoice
	 *	@param	int		$idwarehouse	Id of warehouse for stock change
	 *  @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return int 			        Return integer <0 if KO, =0 if nothing to do, >0 if OK
	 */
	public function validate($user, $force_number = '', $idwarehouse = 0, $notrigger = 0)
	{
		global $mysoc, $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$now = dol_now();

		$error = 0;
		dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number.', idwarehouse='.$idwarehouse);

		// Force to have object complete for checks
		$this->fetch_thirdparty();
		$this->fetch_lines();

		// Check parameters
		if ($this->status > self::STATUS_DRAFT) {	// This is to avoid to validate twice (avoid errors on logs and stock management)
			dol_syslog(get_class($this)."::validate no draft status", LOG_WARNING);
			return 0;
		}
		if (preg_match('/^'.preg_quote($langs->trans("CopyOf").' ').'/', $this->ref_supplier)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldFormat", $langs->transnoentities("RefSupplier")).'. '.$langs->trans('RemoveString', $langs->transnoentitiesnoconv("CopyOf"));
			return -1;
		}
		if (count($this->lines) <= 0) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorObjectMustHaveLinesToBeValidated", $this->ref);
			return -1;
		}

		// Check for mandatory fields in thirdparty (defined into setup)
		if (!empty($this->thirdparty) && is_object($this->thirdparty)) {
			$array_to_check = array('IDPROF1', 'IDPROF2', 'IDPROF3', 'IDPROF4', 'IDPROF5', 'IDPROF6', 'EMAIL', 'ACCOUNTANCY_CODE_SUPPLIER');
			foreach ($array_to_check as $key) {
				$keymin = strtolower($key);
				if ($keymin == 'accountancy_code_supplier') {
					$keymin = 'code_compta_fournisseur';
				}
				if (!property_exists($this->thirdparty, $keymin)) {
					continue;
				}
				$vallabel = $this->thirdparty->$keymin;

				$i = (int) preg_replace('/[^0-9]/', '', $key);
				if ($i > 0) {
					if ($this->thirdparty->isACompany()) {
						// Check for mandatory prof id (but only if country is other than ours)
						if ($mysoc->country_id > 0 && $this->thirdparty->country_id == $mysoc->country_id) {
							$idprof_mandatory = 'SOCIETE_'.$key.'_INVOICE_MANDATORY';
							if (!$vallabel && getDolGlobalString($idprof_mandatory)) {
								$langs->load("errors");
								$this->error = $langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId'.$i, $this->thirdparty->country_code)).' ('.$langs->trans("ForbiddenBySetupRules").') ['.$langs->trans('Company').' : '.$this->thirdparty->name.']';
								dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
								return -1;
							}
						}
					}
				} else {
					if ($key == 'EMAIL') {
						// Check for mandatory
						if (getDolGlobalString('SOCIETE_EMAIL_INVOICE_MANDATORY') && !isValidEmail($this->thirdparty->email)) {
							$langs->load("errors");
							$this->error = $langs->trans("ErrorBadEMail", $this->thirdparty->email).' ('.$langs->trans("ForbiddenBySetupRules").') ['.$langs->trans('Company').' : '.$this->thirdparty->name.']';
							dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
							return -1;
						}
					} elseif ($key == 'ACCOUNTANCY_CODE_SUPPLIER') {
						// Check for mandatory
						if (getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_SUPPLIER_INVOICE_MANDATORY') && empty($this->thirdparty->code_compta_fournisseur)) {
							$langs->load("errors");
							$this->error = $langs->trans("ErrorAccountancyCodeSupplierIsMandatory", $this->thirdparty->name).' ('.$langs->trans("ForbiddenBySetupRules").')';
							dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
							return -1;
						}
					}
				}
			}
		}

		$this->db->begin();

		// Define new ref
		if ($force_number) {
			$num = $force_number;
		} elseif (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef($this->thirdparty);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " SET ref='".$this->db->escape($num)."', fk_statut = 1, fk_user_valid = ".((int) $user->id).", date_valid = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Si on incrémente le produit principal et ses composants à la validation de facture fournisseur
			if (!$error && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_BILL')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$cpt = count($this->lines);
				for ($i = 0; $i < $cpt; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We increase stock for product
						$up_ht_disc = $this->lines[$i]->subprice;
						if (!empty($this->lines[$i]->remise_percent) && !getDolGlobalString('STOCK_EXCLUDE_DISCOUNT_FOR_PMP')) {
							$up_ht_disc = price2num($up_ht_disc * (100 - $this->lines[$i]->remise_percent) / 100, 'MU');
						}
						if ($this->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
							$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("InvoiceValidatedInDolibarr", $num));
						} else {
							$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("InvoiceValidatedInDolibarr", $num));
						}
						if ($result < 0) {
							$this->error = $mouvP->error;
							if (count($mouvP->errors)) {
								$this->errors = $mouvP->errors;
							}
							return -2;
						}
					}
				}
			}

			// Triggers call
			if (!$error && empty($notrigger)) {
				// Call trigger
				$result = $this->call_trigger('BILL_SUPPLIER_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref)) {
					// Now we rename also files into index
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'fournisseur/facture/".get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'fournisseur/facture/".get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'fournisseur/facture/".get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'fournisseur/facture/".get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$dirsource = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$oldref;
					$dirdest = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $this->newref
							$listoffiles = dol_dir_list($conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2, 0, 0, $this, 'invoice_supplier').$this->newref, 'files', 1, '^'.preg_quote($oldref, '/'));
							foreach ($listoffiles as $fileentry) {
								$dirsource = $fileentry['name'];
								$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $this->newref, $dirsource);
								$dirsource = $fileentry['path'].'/'.$dirsource;
								$dirdest = $fileentry['path'].'/'.$dirdest;
								@rename($dirsource, $dirdest);
							}
						}
					}
				}
			}

			// Set new ref and define current status
			if (!$error) {
				$this->ref = $this->newref;
				$this->statut = self::STATUS_VALIDATED;
				$this->status = self::STATUS_VALIDATED;
				//$this->date_validation=$now; this is stored into log table
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *	@param	int		$idwarehouse	Id warehouse to use for stock change.
	 *  @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $idwarehouse = -1, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		if ($this->status == self::STATUS_DRAFT) {
			dol_syslog(__METHOD__." already draft status", LOG_WARNING);
			return 0;
		}

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			// Si on incremente le produit principal et ses composants a la validation de facture fournisseur, on decremente
			if ($result >= 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_BILL')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$cpt = count($this->lines);
				for ($i = 0; $i < $cpt; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We increase stock for product
						if ($this->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
							$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr", $this->ref));
						} else {
							$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr", $this->ref));
						}
					}
				}
			}
			// Triggers call
			if (!$error && empty($notrigger)) {
				// Call trigger
				$result = $this->call_trigger('BILL_SUPPLIER_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
			if ($error == 0) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Adds an invoice line (associated with no predefined product/service)
	 *	The parameters are already supposed to be correct and with final values when calling
	 *	this method. Also, for the VAT rate, it must already have been defined by the caller by
	 *	by the get_default_tva method(vendor_company, buying company, idprod) and the desc must
	 *	already have the right value (the caller has to manage the multilanguage).
	 *
	 *	@param      string      $desc                   Description of the line
	 *	@param      float      $pu                     Unit price (HT or TTC according to price_base_type, > 0 even for credit note)
	 *	@param      float      $txtva                  Force Vat rate to use, -1 for auto.
	 *	@param      float      $txlocaltax1            LocalTax1 Rate
	 *	@param      float      $txlocaltax2            LocalTax2 Rate
	 *	@param      float      $qty                    Quantity
	 *	@param      int         $fk_product             Product/Service ID predefined
	 *	@param      float      $remise_percent         Percentage discount of the line
	 *	@param      int         $date_start             Service start date
	 *	@param      int         $date_end               Service expiry date
	 *	@param      int         $fk_code_ventilation    Accounting breakdown code
	 *	@param      int         $info_bits              Line type bits
	 *	@param      string      $price_base_type        HT or TTC
	 *	@param      int         $type                   Type of line (0=product, 1=service)
	 *	@param      int         $rang                   Position of line
	 *	@param      int         $notrigger              Disable triggers
	 *	@param      array<string,mixed>	$array_options	extrafields array
	 *	@param      int|null    $fk_unit                Code of the unit to use. Null to use the default one
	 *	@param      int         $origin_id              id origin document
	 *	@param      float      $pu_devise              Amount in currency
	 *	@param      string      $ref_supplier           Supplier ref
	 *	@param      int         $special_code           Special code
	 *	@param      int         $fk_parent_line         Parent line id
	 *	@param      int         $fk_remise_except       Id discount used
	 *	@return     int                                 >0 if OK, <0 if KO
	 */
	public function addline($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product = 0, $remise_percent = 0, $date_start = 0, $date_end = 0, $fk_code_ventilation = 0, $info_bits = 0, $price_base_type = 'HT', $type = 0, $rang = -1, $notrigger = 0, $array_options = [], $fk_unit = null, $origin_id = 0, $pu_devise = 0, $ref_supplier = '', $special_code = 0, $fk_parent_line = 0, $fk_remise_except = 0)
	{
		global $langs, $mysoc;

		dol_syslog(get_class($this)."::addline $desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$fk_code_ventilation,$info_bits,$price_base_type,$type,$fk_unit,fk_remise_except=$fk_remise_except", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		if ($this->status == self::STATUS_DRAFT) {
			// Clean parameters
			if (empty($remise_percent)) {
				$remise_percent = 0;
			}
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($info_bits)) {
				$info_bits = 0;
			}
			if (empty($rang)) {
				$rang = 0;
			}
			if (empty($fk_code_ventilation)) {
				$fk_code_ventilation = 0;
			}
			if (empty($txtva)) {
				$txtva = 0;
			}
			if (empty($txlocaltax1)) {
				$txlocaltax1 = 0;
			}
			if (empty($txlocaltax2)) {
				$txlocaltax2 = 0;
			}

			$remise_percent = price2num($remise_percent);
			$qty = price2num($qty);
			$pu = price2num($pu);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
			}
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$this->db->begin();

			if ($fk_product > 0) {
				if (getDolGlobalString('SUPPLIER_INVOICE_WITH_PREDEFINED_PRICES_ONLY')) {
					// Check quantity is enough
					dol_syslog(get_class($this)."::addline we check supplier prices fk_product=".$fk_product." qty=".$qty." ref_supplier=".$ref_supplier);
					$prod = new ProductFournisseur($this->db);
					if ($prod->fetch($fk_product) > 0) {
						$product_type = $prod->type;
						$label = $prod->label;
						$fk_prod_fourn_price = 0;

						// We use 'none' instead of $ref_supplier, because $ref_supplier may not exists anymore. So we will take the first supplier price ok.
						// If we want a dedicated supplier price, we must provide $fk_prod_fourn_price.
						$result = $prod->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', ($this->fk_soc ? $this->fk_soc : $this->socid)); // Search on couple $fk_prod_fourn_price/$qty first, then on triplet $qty/$fk_product/$ref_supplier/$this->fk_soc
						if ($result > 0) {
							if (empty($pu)) {
								$pu = $prod->fourn_pu; // Unit price supplier price set by get_buyprice
							}
							$ref_supplier = $prod->ref_supplier; // Ref supplier price set by get_buyprice
							// is remise percent not keyed but present for the product we add it
							if ($remise_percent == 0 && $prod->remise_percent != 0) {
								$remise_percent = $prod->remise_percent;
							}
						}
						if ($result == 0) {                   // If result == 0, we failed to found the supplier reference price
							$langs->load("errors");
							$this->error = "Ref ".$prod->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline we did not found supplier price, so we can't guess unit price");
							//$pu    = $prod->fourn_pu;     // We do not overwrite unit price
							//$ref   = $prod->ref_fourn;    // We do not overwrite ref supplier price
							return -1;
						}
						if ($result == -1) {
							$langs->load("errors");
							$this->error = "Ref ".$prod->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_DEBUG);
							return -1;
						}
						if ($result < -1) {
							$this->error = $prod->error;
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_ERR);
							return -1;
						}
					} else {
						$this->error = $prod->error;
						$this->db->rollback();
						return -1;
					}
				}
			} else {
				$product_type = $type;
			}

			if (isModEnabled("multicurrency") && $pu_devise > 0) {
				$pu = 0;
			}

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_devise);
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

			// Check parameters
			if ($type < 0) {
				return -1;
			}

			if ($rang < 0) {
				$rangmax = $this->line_max();
				$rang = $rangmax + 1;
			}

			// Insert line
			$supplierinvoiceline = new SupplierInvoiceLine($this->db);

			$supplierinvoiceline->context = $this->context;

			$supplierinvoiceline->fk_facture_fourn = $this->id;
			//$supplierinvoiceline->label=$label;	// deprecated
			$supplierinvoiceline->desc = $desc;
			$supplierinvoiceline->ref_supplier = $ref_supplier;

			$supplierinvoiceline->qty = ($this->type == self::TYPE_CREDIT_NOTE ? abs((float) $qty) : $qty); // For credit note, quantity is always positive and unit price negative
			$supplierinvoiceline->subprice = ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht) : $pu_ht); // For credit note, unit price always negative, always positive otherwise

			$supplierinvoiceline->vat_src_code = $vat_src_code;
			$supplierinvoiceline->tva_tx = $txtva;
			$supplierinvoiceline->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$supplierinvoiceline->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$supplierinvoiceline->localtax1_type = empty($localtaxes_type[0]) ? 0 : $localtaxes_type[0];
			$supplierinvoiceline->localtax2_type = empty($localtaxes_type[2]) ? 0 : $localtaxes_type[2];

			$supplierinvoiceline->total_ht = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ht) : $total_ht); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->total_tva = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_tva) : $total_tva); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->total_localtax1 = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_localtax1) : $total_localtax1); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->total_localtax2 = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_localtax2) : $total_localtax2); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->total_ttc = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ttc) : $total_ttc); // For credit note and if qty is negative, total is negative

			$supplierinvoiceline->fk_product = $fk_product;
			$supplierinvoiceline->product_type = $type;
			$supplierinvoiceline->remise_percent = $remise_percent;
			$supplierinvoiceline->date_start = $date_start;
			$supplierinvoiceline->date_end = $date_end;
			$supplierinvoiceline->fk_code_ventilation = $fk_code_ventilation;
			$supplierinvoiceline->rang = $rang;
			$supplierinvoiceline->info_bits = $info_bits;
			$supplierinvoiceline->fk_remise_except = $fk_remise_except;


			$supplierinvoiceline->special_code = (int) $special_code;
			$supplierinvoiceline->fk_parent_line = $fk_parent_line;
			$supplierinvoiceline->origin = $this->origin;
			$supplierinvoiceline->origin_id = $origin_id;
			$supplierinvoiceline->fk_unit = $fk_unit;

			// Multicurrency
			$supplierinvoiceline->fk_multicurrency = $this->fk_multicurrency;
			$supplierinvoiceline->multicurrency_code = $this->multicurrency_code;
			$supplierinvoiceline->multicurrency_subprice	= ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht_devise) : $pu_ht_devise); // For credit note, unit price always negative, always positive otherwise

			$supplierinvoiceline->multicurrency_total_ht = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ht) : $multicurrency_total_ht); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->multicurrency_total_tva = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_tva) : $multicurrency_total_tva); // For credit note and if qty is negative, total is negative
			$supplierinvoiceline->multicurrency_total_ttc = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ttc) : $multicurrency_total_ttc); // For credit note and if qty is negative, total is negative

			if (is_array($array_options) && count($array_options) > 0) {
				$supplierinvoiceline->array_options = $array_options;
			}

			$result = $supplierinvoiceline->insert($notrigger);
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				} elseif ($rang > 0 && $rang <= count($this->lines)) { // Update all rank of all other lines
					$linecount = count($this->lines);
					for ($ii = $rang; $ii <= $linecount; $ii++) {
						$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
					}
				}

				// Mise a jour information denormalisees au niveau de la facture meme
				$result = $this->update_price(1, 'auto', 0, $this->thirdparty); // The addline method is designed to add line from user input so total calculation with update_price must be done using 'auto' mode.
				if ($result > 0) {
					$this->db->commit();
					return $supplierinvoiceline->id;
				} else {
					$this->error = $this->db->error();
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $supplierinvoiceline->error;
				$this->errors = $supplierinvoiceline->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Update a line detail in the database
	 *
	 * @param	int			$id            		Id of line invoice
	 * @param	string		$desc         		Description of line
	 * @param	float		$pu          		Prix unitaire (HT ou TTC selon price_base_type)
	 * @param	float		$vatrate       		VAT Rate (Can be '8.5', '8.5 (ABC)')
	 * @param	float		$txlocaltax1		LocalTax1 Rate
	 * @param	float		$txlocaltax2		LocalTax2 Rate
	 * @param	float		$qty           		Quantity
	 * @param	int			$idproduct			Id produit
	 * @param	string		$price_base_type	HT or TTC
	 * @param	int			$info_bits			Miscellaneous information of line
	 * @param	int			$type				Type of line (0=product, 1=service)
	 * @param	float		$remise_percent  	Percentage discount of the line
	 * @param	int			$notrigger			Disable triggers
	 * @param	int|string 	$date_start     	Date start of service
	 * @param	int|string  $date_end       	Date end of service
	 * @param	array<string,mixed>	$array_options	extrafields array
	 * @param	?int		$fk_unit 			Code of the unit to use. Null to use the default one
	 * @param	float		$pu_devise			Amount in currency
	 * @param	string		$ref_supplier		Supplier ref
	 * @param	int			$rang				Line rank
	 * @return 	int<-1,1>      					Return integer <0 if KO, >0 if OK
	 */
	public function updateline($id, $desc, $pu, $vatrate, $txlocaltax1 = 0, $txlocaltax2 = 0, $qty = 1, $idproduct = 0, $price_base_type = 'HT', $info_bits = 0, $type = 0, $remise_percent = 0, $notrigger = 0, $date_start = '', $date_end = '', $array_options = [], $fk_unit = null, $pu_devise = 0, $ref_supplier = '', $rang = 0)
	{
		global $mysoc, $langs;

		dol_syslog(get_class($this)."::updateline $id,$desc,$pu,$vatrate,$qty,$idproduct,$price_base_type,$info_bits,$type,$remise_percent,$notrigger,$date_start,$date_end,$fk_unit,$pu_devise,$ref_supplier", LOG_DEBUG);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		$pu = price2num($pu);
		$qty = price2num($qty);
		$remise_percent = (float) price2num($remise_percent);
		$pu_devise = price2num($pu_devise);

		// Check parameters
		//if (! is_numeric($pu) || ! is_numeric($qty)) return -1;
		if ($type < 0) {
			return -1;
		}

		if ($date_start && $date_end && $date_start > $date_end) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorStartDateGreaterEnd');
			return -1;
		}

		// Clean parameters
		if (empty($vatrate)) {
			$vatrate = 0;
		}
		if (empty($txlocaltax1)) {
			$txlocaltax1 = 0;
		}
		if (empty($txlocaltax2)) {
			$txlocaltax2 = 0;
		}

		$txlocaltax1 = (float) price2num($txlocaltax1);
		$txlocaltax2 = (float) price2num($txlocaltax2);

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$localtaxes_type = getLocalTaxesFromRate($vatrate, 0, $mysoc, $this->thirdparty);

		$reg = array();

		// Clean vat code
		$vat_src_code = '';
		if (preg_match('/\((.*)\)/', (string) $vatrate, $reg)) {
			$vat_src_code = $reg[1];
			$vatrate = preg_replace('/\s*\(.*\)/', '', (string) $vatrate); // Remove code into vatrate.
		}

		$tabprice = calcul_price_total($qty, $pu, $remise_percent, $vatrate, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_devise);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$pu_ht  = $tabprice[3];
		$pu_tva = $tabprice[4];
		$pu_ttc = $tabprice[5];
		$total_localtax1 = $tabprice[9];
		$total_localtax2 = $tabprice[10];

		// MultiCurrency
		$multicurrency_total_ht = $tabprice[16];
		$multicurrency_total_tva = $tabprice[17];
		$multicurrency_total_ttc = $tabprice[18];
		$pu_ht_devise = $tabprice[19];

		if (empty($info_bits)) {
			$info_bits = 0;
		}

		//Fetch current line from the database and then clone the object and set it in $oldline property
		$line = new SupplierInvoiceLine($this->db);
		$line->fetch($id);
		$line->fetch_optionals();

		$staticline = clone $line;

		if ($idproduct) {
			$product = new Product($this->db);
			$result = $product->fetch($idproduct);
			$product_type = $product->type;
		} else {
			$idproduct = $staticline->fk_product;
			$product_type = $type;
		}

		$line->oldline = $staticline;
		$line->context = $this->context;

		$line->description = $desc;
		$line->desc = $desc;

		$line->qty = ($this->type == self::TYPE_CREDIT_NOTE ? abs((float) $qty) : $qty); // For credit note, quantity is always positive and unit price negative
		$line->subprice = ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht) : $pu_ht); // For credit note, unit price always negative, always positive otherwise
		$line->pu_ht = $line->subprice;  // deprecated
		$line->pu_ttc = ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ttc) : $pu_ttc); // For credit note, unit price always negative, always positive otherwise

		$line->remise_percent = $remise_percent;
		$line->ref_supplier = $ref_supplier;

		$line->date_start = $date_start;
		$line->date_end = $date_end;

		$line->vat_src_code = $vat_src_code;
		$line->tva_tx = $vatrate;
		$line->localtax1_tx = $txlocaltax1;
		$line->localtax2_tx = $txlocaltax2;
		$line->localtax1_type = empty($localtaxes_type[0]) ? 0 : $localtaxes_type[0];
		$line->localtax2_type = empty($localtaxes_type[2]) ? 0 : $localtaxes_type[2];

		$line->total_ht = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ht) : $total_ht);
		$line->total_tva = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_tva) : $total_tva);
		$line->total_localtax1 = $total_localtax1;
		$line->total_localtax2 = $total_localtax2;
		$line->total_ttc = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ttc) : $total_ttc);

		$line->fk_product = $idproduct;
		$line->product_type = $product_type;
		$line->info_bits = $info_bits;
		$line->fk_unit = $fk_unit;
		$line->rang = $rang;

		if (is_array($array_options) && count($array_options) > 0) {
			// We replace values in this->line->array_options only for entries defined into $array_options
			foreach ($array_options as $key => $value) {
				$line->array_options[$key] = $array_options[$key];
			}
		}

		// Multicurrency
		$line->multicurrency_subprice = $pu_ht_devise;
		$line->multicurrency_total_ht = $multicurrency_total_ht;
		$line->multicurrency_total_tva 	= $multicurrency_total_tva;
		$line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

		$res = $line->update($notrigger);

		if ($res < 1) {
			$this->errors[] = $line->error;
		} else {
			// Update total price into invoice record
			$res = $this->update_price('1', 'auto', 0, $this->thirdparty);
		}

		return $res;
	}

	/**
	 * 	Delete a detail line from database
	 *
	 * 	@param  int		$rowid      	Id of line to delete
	 *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 * 	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function deleteLine($rowid, $notrigger = 0)
	{
		if (!$rowid) {
			$rowid = $this->id;
		}

		$this->db->begin();

		// Free the discount linked to a line of invoice
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
		$sql .= ' SET fk_invoice_supplier_line = NULL';
		$sql .= ' WHERE fk_invoice_supplier_line = '.((int) $rowid);

		dol_syslog(get_class($this)."::deleteline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}

		$line = new SupplierInvoiceLine($this->db);

		if ($line->fetch($rowid) < 1) {
			return -1;
		}

		$res = $line->delete($notrigger);

		if ($res < 1) {
			$this->errors[] = $line->error;
			$this->db->rollback();
			return -3;
		} else {
			$res = $this->update_price(1);

			if ($res > 0) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -4;
			}
		}
	}


	/**
	 *	Loads the info order information into the invoice object
	 *
	 *	@param  int		$id       	Id of the invoice to load
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, datec, tms as datem, ';
		$sql .= ' fk_user_author, fk_user_modif, fk_user_valid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				//$this->date_validation   = $obj->datev; // This field is not available. Should be store into log table and using this function should be replaced with showing content of log (like for supplier orders)
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of replaceable invoices
	 *	Status valid or abandoned for other reason + not paid + no payment + not already replaced
	 *
	 *	@param      int		$socid		Thirdparty id
	 *	@return    	array|int			Table of invoices ('id'=>id, 'ref'=>ref, 'status'=>status, 'paymentornot'=>0/1)
	 *                                  <0 if error
	 */
	public function list_replacable_supplier_invoices($socid = 0)
	{
		// phpcs:enable
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut,";
		$sql .= " ff.rowid as rowidnext";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid = pf.fk_facturefourn";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as ff ON f.rowid = ff.fk_facture_source";
		$sql .= " WHERE (f.fk_statut = ".self::STATUS_VALIDATED." OR (f.fk_statut = ".self::STATUS_ABANDONED." AND f.close_code = '".self::CLOSECODE_ABANDONED."'))";
		$sql .= " AND f.entity = ".$conf->entity;
		$sql .= " AND f.paye = 0"; // Pas classee payee completement
		$sql .= " AND pf.fk_paiementfourn IS NULL"; // Aucun paiement deja fait
		$sql .= " AND ff.fk_statut IS NULL"; // Renvoi vrai si pas facture de replacement
		if ($socid > 0) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		$sql .= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_replacable_supplier_invoices", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$return[$obj->rowid] = array(
					'id' => $obj->rowid,
					'ref' => $obj->ref,
					'status' => $obj->fk_statut
				);
			}
			//print_r($return);
			return $return;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of qualifying invoices for correction by credit note
	 *	Invoices that respect the following rules are returned:
	 *	(validated + payment in progress) or classified (paid in full or paid in part) + not already replaced + not already having
	 *
	 *	@param		int		$socid		Thirdparty id
	 *	@return    	array|int			Table of invoices ($id => array('ref'=>,'paymentornot'=>,'status'=>,'paye'=>)
	 *                                  <0 if error
	 */
	public function list_qualified_avoir_supplier_invoices($socid = 0)
	{
		// phpcs:enable
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut, f.type, f.subtype, f.paye, pf.fk_paiementfourn";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid = pf.fk_facturefourn";
		$sql .= " WHERE f.entity = ".$conf->entity;
		$sql .= " AND f.fk_statut in (".self::STATUS_VALIDATED.",".self::STATUS_CLOSED.")";
		$sql .= " AND NOT EXISTS (SELECT rowid from ".MAIN_DB_PREFIX."facture_fourn as ff WHERE f.rowid = ff.fk_facture_source";
		$sql .= " AND ff.type=".self::TYPE_REPLACEMENT.")";
		$sql .= " AND f.type != ".self::TYPE_CREDIT_NOTE; // Type non 2 si facture non avoir
		if ($socid > 0) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		$sql .= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_qualified_avoir_supplier_invoices", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$qualified = 0;
				if ($obj->fk_statut == self::STATUS_VALIDATED) {
					$qualified = 1;
				}
				if ($obj->fk_statut == self::STATUS_CLOSED) {
					$qualified = 1;
				}
				if ($qualified) {
					$paymentornot = ($obj->fk_paiementfourn ? 1 : 0);
					$return[$obj->rowid] = array('ref' => $obj->ref, 'status' => $obj->fk_statut, 'type' => $obj->type, 'paye' => $obj->paye, 'paymentornot' => $paymentornot);
				}
			}

			return $return;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *	@param      User	$user       Object user
	 *	@return WorkboardResponse|int Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		$sql = 'SELECT ff.rowid, ff.date_lim_reglement as datefin, ff.fk_statut as status, ff.total_ht, ff.total_ttc';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as ff';
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ff.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= ' WHERE ff.paye = 0';
		$sql .= " AND ff.fk_statut IN (".self::STATUS_VALIDATED.")";
		$sql .= " AND ff.entity = ".$conf->entity;
		if ($user->socid) {
			$sql .= ' AND ff.fk_soc = '.((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("bills");
			$now = dol_now();

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->facture->fournisseur->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("SupplierBillsToPay");
			$response->labelShort = $langs->trans("StatusToPay");

			$response->url = DOL_URL_ROOT.'/fourn/facture/list.php?search_status=1&mainmenu=billing&leftmenu=suppliers_bills';
			$response->img = img_object($langs->trans("Bills"), "bill");

			$facturestatic = new FactureFournisseur($this->db);

			while ($obj = $this->db->fetch_object($resql)) {
				$facturestatic->date_echeance = $this->db->jdate($obj->datefin);
				$facturestatic->statut = $obj->status;	// For backward compatibility
				$facturestatic->status = $obj->status;

				$response->nbtodo++;
				$response->total += $obj->total_ht;

				if ($facturestatic->hasDelay()) {
					$response->nbtodolate++;
					$response->url_late = DOL_URL_ROOT.'/fourn/facture/list.php?search_option=late&mainmenu=billing&leftmenu=suppliers_bills';
				}
			}

			$this->db->free($resql);
			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array{moretitle?:string} $params ex option, infologin
	 * @since v18
	 * @return array{picto:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $mysoc;

		$langs->load('bills');

		$datas = [];
		$moretitle = $params['moretitle'] ?? '';

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		$datas['picto'] = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->trans("SupplierInvoice").'</u>';
		if ($this->type == self::TYPE_REPLACEMENT) {
			$datas['picto'] .= '<u class="paddingrightonly">'.$langs->transnoentitiesnoconv("InvoiceReplace").'</u>';
		} elseif ($this->type == self::TYPE_CREDIT_NOTE) {
			$datas['picto'] .= '<u class="paddingrightonly">'.$langs->transnoentitiesnoconv("CreditNote").'</u>';
		} elseif ($this->type == self::TYPE_DEPOSIT) {
			$datas['picto'] .= '<u class="paddingrightonly">'.$langs->transnoentitiesnoconv("Deposit").'</u>';
		}
		if (isset($this->status)) {
			$alreadypaid = -1;
			if (isset($this->totalpaid)) {
				$alreadypaid = $this->totalpaid;
			}

			$datas['picto'] .= ' '.$this->getLibStatut(5, $alreadypaid);
		}
		if ($moretitle) {
			$datas['picto'] .= ' - '.$moretitle;
		}
		if (!empty($this->ref)) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->ref_supplier)) {
			$datas['refsupplier'] = '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_supplier;
		}
		if (!empty($this->label)) {
			$datas['label'] = '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		}
		if (!empty($this->date)) {
			$datas['date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
		}
		if (!empty($this->date_echeance)) {
			$datas['date_echeance'] = '<br><b>'.$langs->trans('DateDue').':</b> '.dol_print_date($this->date_echeance, 'day');
		}
		if (!empty($this->total_ht)) {
			$datas['amountht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_tva)) {
			$datas['totaltva'] = '<br><b>'.$langs->trans('AmountVAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_localtax1) && $this->total_localtax1 != 0) {
			// We keep test != 0 because $this->total_localtax1 can be '0.00000000'
			$datas['amountlt1'] = '<br><b>'.$langs->transcountry('AmountLT1', $mysoc->country_code).':</b> '.price($this->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_localtax2) && $this->total_localtax2 != 0) {
			$datas['amountlt2'] = '<br><b>'.$langs->transcountry('AmountLT2', $mysoc->country_code).':</b> '.price($this->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->revenuestamp)) {
			$datas['amountrevenustamp'] = '<br><b>'.$langs->trans('RevenueStamp').':</b> '.price($this->revenuestamp, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_ttc)) {
			$datas['totalttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
		}
		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option						Where point the link
	 *	@param		int		$max						Max length of shown ref
	 *	@param		int		$short						1=Return just URL
	 *	@param		string	$moretitle					Add more text to title tooltip
	 *  @param	    int   	$notooltip					1=Disable tooltip
	 *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param		int		$addlinktonotes				Add link to show notes
	 * 	@return		string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0)
	{
		global $langs, $conf, $user, $hookmanager;

		$result = '';

		if ($option == 'withdraw') {
			$url = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$this->id.'&type=bank-transfer';
		} elseif ($option == 'document') {
			$url = DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$this->id;
		}

		if ($short) {
			return $url;
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'moretitle' => $moretitle,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$ref = $this->ref;
		if (empty($ref)) {
			$ref = $this->id;
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowSupplierInvoice");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($picto ? $picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($max ? dol_trunc($ref, $max) : $ref);
		}
		$result .= $linkend;

		if ($addlinktonotes) {
			$txttoshow = ($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow) {
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/fourn/facture/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
				$result .= img_picto('', 'note');
				$result .= '</a>';
				$result .= '</span>';
			}
		}
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
	 *      Return next reference of supplier invoice not already used (or last reference)
	 *      according to numbering module defined into constant INVOICE_SUPPLIER_ADDON_NUMBER
	 *
	 *      @param	   Societe		$soc		Thirdparty object
	 *      @param    string		$mode		'next' for next value or 'last' for last value
	 *      @return   string|-1					Returns free reference or last reference, or '' or -1 if error
	 */
	public function getNextNumRef($soc, $mode = 'next')
	{
		global $db, $langs, $conf;
		$langs->load("orders");

		// Clean parameters (if not defined or using deprecated value)
		if (!getDolGlobalString('INVOICE_SUPPLIER_ADDON_NUMBER')) {
			$conf->global->INVOICE_SUPPLIER_ADDON_NUMBER = 'mod_facture_fournisseur_cactus';
		}

		$mybool = false;

		$file = getDolGlobalString('INVOICE_SUPPLIER_ADDON_NUMBER') . ".php";
		$classname = getDolGlobalString('INVOICE_SUPPLIER_ADDON_NUMBER');

		// Include file with class
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

		foreach ($dirmodels as $reldir) {
			$dir = dol_buildpath($reldir."core/modules/supplier_invoice/");

			// Load file with numbering class (if found)
			$mybool = ((bool) @include_once $dir.$file) || $mybool;
		}

		if (!$mybool) {
			dol_print_error(null, "Failed to include file ".$file);
			return '';
		}

		$obj = new $classname();
		'@phan-var-force ModeleNumRefSuppliersInvoices $obj';
		$numref = "";
		$numref = $obj->getNextValue($soc, $this, $mode);

		if ($numref != "") {
			return $numref;
		} else {
			$this->error = $obj->error;
			return -1;
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return int
	 */
	public function initAsSpecimen($option = '')
	{
		global $langs, $conf;
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

		$now = dol_now();

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

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->ref_supplier = 'SUPPLIER_REF_SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = $now;
		$this->date_lim_reglement = $this->date + 3600 * 24 * 30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->multicurrency_tx = 1;
		$this->multicurrency_code = $conf->currency;

		$xnbp = 0;
		if (empty($option) || $option != 'nolines') {
			// Lines
			$nbp = 5;
			while ($xnbp < $nbp) {
				$line = new SupplierInvoiceLine($this->db);
				$line->desc = $langs->trans("Description")." ".$xnbp;
				$line->qty = 1;
				$line->subprice = 100;
				$line->pu_ht = $line->subprice; // the canelle template use pu_ht and not subprice
				$line->price = 100;
				$line->tva_tx = 19.6;
				$line->localtax1_tx = 0;
				$line->localtax2_tx = 0;
				if ($xnbp == 2) {
					$line->total_ht = 50;
					$line->total_ttc = 59.8;
					$line->total_tva = 9.8;
					$line->remise_percent = 50;
				} else {
					$line->total_ht = 100;
					$line->total_ttc = 119.6;
					$line->total_tva = 19.6;
					$line->remise_percent = 0;
				}

				if ($num_prods > 0) {
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
				}
				$line->product_type = 0;

				$this->lines[$xnbp] = $line;

				$this->total_ht       += $line->total_ht;
				$this->total_tva      += $line->total_tva;
				$this->total_ttc      += $line->total_ttc;

				$xnbp++;
			}
		}

		$this->total_ht = $xnbp * 100;
		$this->total_tva = $xnbp * 19.6;
		$this->total_ttc = $xnbp * 119.6;

		return 1;
	}

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @return         int     Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $conf, $user;

		$this->nb = array();

		$clause = "WHERE";

		$sql = "SELECT count(f.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["supplier_invoices"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param      User	$user        	User that clone
	 *	@param      int		$fromid     	Id of object to clone
	 *	@param		int		$invertdetail	Reverse sign of amounts for lines
	 * 	@return		int						New id of clone
	 */
	public function createFromClone(User $user, $fromid, $invertdetail = 0)
	{
		global $conf, $langs;

		$error = 0;

		$object = new FactureFournisseur($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = self::STATUS_DRAFT;	// For backward compatibility
		$object->status = self::STATUS_DRAFT;

		$object->fetch_thirdparty(); // We need it to recalculate VAT localtaxes according to main sale taxes and vendor

		// Clear fields
		$object->ref_supplier       = (empty($this->ref_supplier) ? $langs->trans("CopyOf").' '.$object->ref_supplier : $this->ref_supplier);
		$object->author             = $user->id;  // FIXME? user_validation_id is replacement for author
		$object->user_validation_id = 0;  // FIXME?  user_validation_id is replacement for author
		$object->fk_facture_source  = 0;
		$object->date_creation      = '';
		$object->date_validation    = '';
		$object->date               = (empty($this->date) ? dol_now() : $this->date);
		$object->ref_client         = '';
		$object->close_code         = '';
		$object->close_note         = '';
		if (getDolGlobalInt('MAIN_DONT_KEEP_NOTE_ON_CLONING') == 1) {
			$object->note_private = '';
			$object->note_public = '';
		}

		$object->date_echeance = $object->calculate_date_lim_reglement();

		// Loop on each line of new invoice
		foreach ($object->lines as $i => $line) {
			if (isset($object->lines[$i]->info_bits) && ($object->lines[$i]->info_bits & 0x02) == 0x02) {	// We do not clone line of discounts
				unset($object->lines[$i]);
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$this->errors = $object->errors;
			$error++;
		}

		if (!$error) {
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Create a document onto disk according to template model.
	 *
	 *	@param	    string		$modele			Force template to use ('' to not force)
	 *	@param		Translate	$outputlangs	Object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param   	?array<string,mixed>	$moreparams     Array to provide more information
	 *  @return     int<-1,1>					Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $langs;

		$langs->load("suppliers");
		$outputlangs->load("products");

		// Set the model on the model name to use
		if (empty($modele)) {
			if (getDolGlobalString('INVOICE_SUPPLIER_ADDON_PDF')) {
				$modele = getDolGlobalString('INVOICE_SUPPLIER_ADDON_PDF');
			} else {
				$modele = ''; // No default value. For supplier invoice, we allow to disable all PDF generation
			}
		}

		if (empty($modele)) {
			return 0;
		} else {
			$modelpath = "core/modules/supplier_invoice/doc/";

			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}
	}

	/**
	 * Returns the rights used for this class
	 * @return int
	 */
	public function getRights()
	{
		global $user;

		return $user->hasRight("fournisseur", "facture");
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
			'facture_fourn'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'facture_fourn_det'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Is the payment of the supplier invoice having a delay?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		$now = dol_now();

		if (!$this->date_echeance) {
			return false;
		}

		$status = isset($this->status) ? $this->status : $this->statut;

		return ($status == self::STATUS_VALIDATED) && ($this->date_echeance < ($now - $conf->facture->fournisseur->warning_delay));
	}

	/**
	 * Is credit note used
	 *
	 * @return bool
	 */
	public function isCreditNoteUsed()
	{
		$isUsed = false;

		$sql = "SELECT fk_invoice_supplier FROM ".MAIN_DB_PREFIX."societe_remise_except WHERE fk_invoice_supplier_source = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if (!empty($resql)) {
			$obj = $this->db->fetch_object($resql);
			if (!empty($obj->fk_invoice_supplier)) {
				$isUsed = true;
			}
		}

		return $isUsed;
	}
	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		?array{selected?:int<0,1>}	$arraydata	Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['thirdparty'])) {
			$return .= '<br><span class="info-box-label">'.$arraydata['thirdparty'].'</span>';
		}
		if (property_exists($this, 'date')) {
			$return .= '<br><span class="info-box-label">'.dol_print_date($this->date, 'day').'</span>';
		}
		if (property_exists($this, 'total_ht')) {
			$return .= ' &nbsp; <span class="info-box-label amount" title="'.dol_escape_htmltag($langs->trans("AmountHT")).'">'.price($this->total_ht);
			$return .= ' '.$langs->trans("HT");
			$return .= '</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$alreadypaid = (empty($arraydata['alreadypaid']) ? 0 : $arraydata['alreadypaid']);
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3, $alreadypaid).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 *  Change the option VAT reverse charge
	 *
	 *  @param      int     $vatreversecharge	0 = Off, 1 = On
	 *  @return     int              			1 if OK, 0 if KO
	 */
	public function setVATReverseCharge($vatreversecharge)
	{
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setVATReverseCharge was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		dol_syslog(get_class($this).'::setVATReverseCharge('.$vatreversecharge.')');

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET vat_reverse_charge = ".((int) $vatreversecharge);
		$sql .= " WHERE rowid=".((int) $this->id);

		if ($this->db->query($sql)) {
			$this->vat_reverse_charge = ($vatreversecharge == 0) ? 0 : 1;
			return 1;
		} else {
			dol_syslog(get_class($this).'::setVATReverseCharge Error ', LOG_DEBUG);
			$this->error = $this->db->error();
			return 0;
		}
	}

	/**
	 *  Send reminders by emails for supplier invoices validated that are due.
	 *  CAN BE A CRON TASK
	 *
	 *  @param	int			$nbdays				Delay before due date (or after if delay is negative)
	 *  @param	string		$paymentmode		'' or 'all' by default (no filter), or 'LIQ', 'CHQ', CB', ...
	 *  @param	int|string	$template			Name (or id) of email template (Must be a template of type 'invoice_supplier_send')
	 *  @param	string		$datetouse			'duedate' (default) or 'invoicedate'
	 *  @param	string		$forcerecipient		Force email of recipient (for example to send the email to an accountant supervisor instead of the customer)
	 *  @return int         					0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendEmailsRemindersOnSupplierInvoiceDueDate($nbdays = 0, $paymentmode = 'all', $template = '', $datetouse = 'duedate', $forcerecipient = '')
	{
		global $conf, $langs, $user;

		$this->output = '';
		$this->error = '';
		$nbMailSend = 0;

		$error = 0;
		$errorsMsg = array();

		$langs->load('bills');

		if (!isModEnabled(empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) ? 'fournisseur' : 'supplier_invoice')) {	// Should not happen. If module disabled, cron job should not be visible.
			$this->output .= $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv('Suppliers'));
			return 0;
		}
		if (!in_array($datetouse, array('duedate', 'invoicedate'))) {
			$this->output .= 'Bad value for parameter datetouse. Must be "duedate" or "invoicedate"';
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$formmail = new FormMail($this->db);

		$now = dol_now();
		$tmpidate = dol_get_first_hour(dol_time_plus_duree($now, $nbdays, 'd'), 'gmt');

		$tmpinvoice = new FactureFournisseur($this->db);

		dol_syslog(__METHOD__." start", LOG_INFO);

		// Select all action comm reminder
		$sql = "SELECT rowid as id FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$sql .= ", ".MAIN_DB_PREFIX."c_paiement as cp";
		}
		$sql .= " WHERE f.paye = 0";	// Only unpaid
		$sql .= " AND f.fk_statut = ".self::STATUS_VALIDATED;	// Only validated status
		if ($datetouse == 'invoicedate') {
			$sql .= " AND f.datef = '".$this->db->idate($tmpidate, 'gmt')."'";
		} else {
			$sql .= " AND f.date_lim_reglement = '".$this->db->idate($tmpidate, 'gmt')."'";
		}
		$sql .= " AND f.entity IN (".getEntity('supplier_invoice', 0).")";	// One batch process only one company (no sharing)
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$sql .= " AND f.fk_mode_reglement = cp.id AND cp.code = '".$this->db->escape($paymentmode)."'";
		}
		// TODO Add a filter to check there is no payment started yet
		if ($datetouse == 'invoicedate') {
			$sql .= $this->db->order("datef", "ASC");
		} else {
			$sql .= $this->db->order("date_lim_reglement", "ASC");
		}

		$resql = $this->db->query($sql);

		$stmpidate = dol_print_date($tmpidate, 'day', 'gmt');
		if ($datetouse == 'invoicedate') {
			$this->output .= $langs->transnoentitiesnoconv("SearchValidatedSupplierInvoicesWithDate", $stmpidate);
		} else {
			$this->output .= $langs->transnoentitiesnoconv("SearchUnpaidSupplierInvoicesWithDueDate", $stmpidate);
		}
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$this->output .= ' ('.$langs->transnoentitiesnoconv("PaymentMode").' '.$paymentmode.')';
		}
		$this->output .= '<br>';

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!$error) {
					// Load event
					$res = $tmpinvoice->fetch($obj->id);
					if ($res > 0) {
						$tmpinvoice->fetch_thirdparty();

						$outputlangs = new Translate('', $conf);
						if ($tmpinvoice->thirdparty->default_lang) {
							$outputlangs->setDefaultLang($tmpinvoice->thirdparty->default_lang);
							$outputlangs->loadLangs(array("main", "suppliers"));
						} else {
							$outputlangs = $langs;
						}

						// Select email template according to language of recipient
						$templateId = 0;
						$templateLabel = '';
						if (empty($template) || $template == 'EmailTemplateCode') {
							$templateLabel = '(SendingReminderEmailOnUnpaidSupplierInvoice)';
						} else {
							if (is_numeric($template)) {
								$templateId = $template;
							} else {
								$templateLabel = $template;
							}
						}

						$arraymessage = $formmail->getEMailTemplate($this->db, 'invoice_supplier_send', $user, $outputlangs, $templateId, 1, $templateLabel);
						if (is_numeric($arraymessage) && $arraymessage <= 0) {
							$langs->load("errors");
							$this->output .= $langs->trans('ErrorFailedToFindEmailTemplate', $template);
							return 0;
						}

						// PREPARE EMAIL
						$errormesg = '';

						// Make substitution in email content
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, '', $tmpinvoice);

						complete_substitutions_array($substitutionarray, $outputlangs, $tmpinvoice);

						// Topic
						$sendTopic = make_substitutions(empty($arraymessage->topic) ? $outputlangs->transnoentitiesnoconv('InformationMessage') : $arraymessage->topic, $substitutionarray, $outputlangs, 1);

						// Content
						$content = $outputlangs->transnoentitiesnoconv($arraymessage->content);

						$sendContent = make_substitutions($content, $substitutionarray, $outputlangs, 1);

						// Recipient
						$to = array();
						if ($forcerecipient) {	// If a recipient was forced
							$to = array($forcerecipient);
						} else {
							$res = $tmpinvoice->fetch_thirdparty();
							$recipient = $tmpinvoice->thirdparty;
							if ($res > 0) {
								$tmparraycontact = $tmpinvoice->liste_contact(-1, 'internal', 0, 'SALESREPFOLL');
								if (is_array($tmparraycontact) && count($tmparraycontact) > 0) {
									foreach ($tmparraycontact as $data_email) {
										if (!empty($data_email['email'])) {
											$to[] = $data_email['email'];
										}
									}
								}
								if (empty($to) && !empty($recipient->email)) {
									$to[] = $recipient->email;
								}
								if (empty($to)) {
									$errormesg = "Failed to send remind to thirdparty id=".$tmpinvoice->socid.". No email defined for supplier invoice or customer.";
									$error++;
								}
							} else {
								$errormesg = "Failed to load recipient with thirdparty id=".$tmpinvoice->socid;
								$error++;
							}
						}

						// Sender
						$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
						if (!empty($arraymessage->email_from)) {	// If a sender is defined into template, we use it in priority
							$from = $arraymessage->email_from;
						}
						if (empty($from)) {
							$errormesg = "Failed to get sender into global setup MAIN_MAIL_EMAIL_FROM";
							$error++;
						}

						if (!$error && !empty($to)) {
							$this->db->begin();

							$to = implode(',', $to);
							if (!empty($arraymessage->email_to)) {	// If a recipient is defined into template, we add it
								$to = $to.','.$arraymessage->email_to;
							}

							// Errors Recipient
							$errors_to = $conf->global->MAIN_MAIL_ERRORS_TO;

							$trackid = 'inv'.$tmpinvoice->id;
							$sendcontext = 'standard';

							$email_tocc = '';
							if (!empty($arraymessage->email_tocc)) {	// If a CC is defined into template, we use it
								$email_tocc = $arraymessage->email_tocc;
							}

							$email_tobcc = '';
							if (!empty($arraymessage->email_tobcc)) {	// If a BCC is defined into template, we use it
								$email_tobcc = $arraymessage->email_tobcc;
							}

							// Mail Creation
							$cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, array(), array(), array(), $email_tocc, $email_tobcc, 0, 1, $errors_to, '', $trackid, '', $sendcontext, '');

							// Sending Mail
							if ($cMailFile->sendfile()) {
								$nbMailSend++;

								// Add a line into event table
								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

								// Insert record of emails sent
								$actioncomm = new ActionComm($this->db);

								$actioncomm->type_code = 'AC_OTH_AUTO'; // Event insert into agenda automatically
								$actioncomm->socid = $tmpinvoice->thirdparty->id; // To link to a company
								$actioncomm->contact_id = 0;

								$actioncomm->code = 'AC_EMAIL';
								$actioncomm->label = 'sendEmailsRemindersOnInvoiceDueDateOK (nbdays='.$nbdays.' paymentmode='.$paymentmode.' template='.$template.' datetouse='.$datetouse.' forcerecipient='.$forcerecipient.')';
								$actioncomm->note_private = $sendContent;
								$actioncomm->fk_project = $tmpinvoice->fk_project;
								$actioncomm->datep = dol_now();
								$actioncomm->datef = $actioncomm->datep;
								$actioncomm->percentage = -1; // Not applicable
								$actioncomm->authorid = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is an email (content should be added into note)
								$actioncomm->email_msgid = $cMailFile->msgid;
								$actioncomm->email_subject = $sendTopic;
								$actioncomm->email_from = $from;
								$actioncomm->email_sender = '';
								$actioncomm->email_to = $to;
								//$actioncomm->email_tocc = $sendtocc;
								//$actioncomm->email_tobcc = $sendtobcc;
								//$actioncomm->email_subject = $subject;
								$actioncomm->errors_to = $errors_to;

								$actioncomm->elementtype = 'invoice_supplier';
								$actioncomm->fk_element = $tmpinvoice->id;

								//$actioncomm->extraparams = $extraparams;

								$actioncomm->create($user);
							} else {
								$errormesg = $cMailFile->error.' : '.$to;
								$error++;

								// Add a line into event table
								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

								// Insert record of emails sent
								$actioncomm = new ActionComm($this->db);

								$actioncomm->type_code = 'AC_OTH_AUTO'; // Event insert into agenda automatically
								$actioncomm->socid = $tmpinvoice->thirdparty->id; // To link to a company
								$actioncomm->contact_id = 0;

								$actioncomm->code = 'AC_EMAIL';
								$actioncomm->label = 'sendEmailsRemindersOnInvoiceDueDateKO';
								$actioncomm->note_private = $errormesg;
								$actioncomm->fk_project = $tmpinvoice->fk_project;
								$actioncomm->datep = dol_now();
								$actioncomm->datef = $actioncomm->datep;
								$actioncomm->percentage = -1; // Not applicable
								$actioncomm->authorid = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is an email (content should be added into note)
								$actioncomm->email_msgid = $cMailFile->msgid;
								$actioncomm->email_from = $from;
								$actioncomm->email_sender = '';
								$actioncomm->email_to = $to;
								//$actioncomm->email_tocc = $sendtocc;
								//$actioncomm->email_tobcc = $sendtobcc;
								//$actioncomm->email_subject = $subject;
								$actioncomm->errors_to = $errors_to;

								//$actioncomm->extraparams = $extraparams;

								$actioncomm->create($user);
							}

							$this->db->commit();	// We always commit
						}

						if ($errormesg) {
							$errorsMsg[] = $errormesg;
						}
					} else {
						$errorsMsg[] = 'Failed to fetch record invoice with ID = '.$obj->id;
						$error++;
					}
				}
			}
		} else {
			$error++;
		}

		if (!$error) {
			$this->output .= 'Nb of emails sent : '.$nbMailSend;

			dol_syslog(__METHOD__." end - ".$this->output, LOG_INFO);

			return 0;
		} else {
			$this->error = 'Nb of emails sent : '.$nbMailSend.', '.(empty($errorsMsg) ? $error : implode(', ', $errorsMsg));

			dol_syslog(__METHOD__." end - ".$this->error, LOG_INFO);

			return $error;
		}
	}
}
