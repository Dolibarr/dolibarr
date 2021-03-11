<?php
/* Copyright (C) 2009-2020	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot		<herve.prot@symeos.com>
 * Copyright (C) 2014		Philippe Grand	<philippe.grand@atoo-net.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/multicompany/actions_multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/multicompany/class/dao_multicompany.class.php', 'DaoMulticompany');
dol_include_once('/multicompany/lib/multicompany.lib.php');

/**
 *	Class Actions of the module multicompany
 */
class ActionsMulticompany
{
	/** @var DoliDB */
	var $db;
	/** @var DaoMulticompany */
	var $dao;

	var $mesg;
	var $error;
	var $errors=array();
	//! Numero de l'erreur
	var $errno = 0;

	var $template_dir;
	var $template;

	var $label;
	var $description;

	var $referent;

	var $sharings=array();
	var $options=array();
	var $entities=array();
	var $dict=array();
	var $tpl=array();

	var $addzero=array();
	var $sharingelements=array();
	var $sharingobjects=array();
	var $sharingdicts=array();

	private $config=array();

	// For Hookmanager return
	var $resprints;
	var $results=array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->addzero = array(
			'user',
			'usergroup',
			'c_email_templates',
			'email_template',
			'default_values'
		);

		// Default sharing elements
		$this->sharingelements = array(
			'thirdparty' => array(
				'type' => 'element',
				'icon' => 'building',
				'active' => true,	// for setEntity() function
				'disable' => true	// disable in options tab
			),
			'member' => array(
				'type' => 'element',
				'icon' => 'address-card',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'member_type' => array(
				'type' => 'element',
				'icon' => 'address-card',
				'display' => '! empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'member' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'product' => array(
				'type' => 'element',
				'icon' => 'cube',
				'tooltip' => 'ProductSharingInfo',
				'enable' => '(! empty($conf->product->enabled) || ! empty($conf->service->enabled))',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'productprice' => array(
				'type' => 'element',
				'icon' => 'money',
				'tooltip' => 'ProductPriceSharingInfo',
				'enable' => '(! empty($conf->product->enabled) || ! empty($conf->service->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'productsupplierprice' => array(
				'type' => 'element',
				'icon' => 'money',
				'enable' => '((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && ! empty($conf->fournisseur->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				),
			),
			'stock' => array(
				'type' => 'element',
				'icon' => 'cubes',
				//'tooltip' => 'SharingStockInfo',
				'enable' => '(! empty($conf->stock->enabled) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))',
				'display' => '! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'product' => array(
						'showhide' => true,
						'del' => true
					)
				)
			),
			'category' => array(
				'type' => 'element',
				'icon' => 'paperclip',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'agenda' => array(
				'type' => 'element',
				'icon' => 'calendar',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'bankaccount' => array(
				'type' => 'element',
				'icon' => 'bank',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'expensereport' => array(
				'type' => 'element',
				'icon' => 'edit',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'holiday' => array(
				'type' => 'element',
				'icon' => 'paper-plane-o',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'project' => array(
				'type' => 'element',
				'icon' => 'code-fork',
				'input' => array(
					'global' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),

			// Object

			'proposal' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',
				'mandatory' => 'thirdparty',
				'enable' => '(! empty($conf->propal->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'proposalnumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				'mandatory' => 'thirdparty',
				'tooltip' => 'ProposalNumberSharingInfo',
				'enable' => '(! empty($conf->propal->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'order' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',		// Font Awesome icon
				'mandatory' => 'thirdparty',
				'enable' => '(! empty($conf->commande->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true,
				//'disable' => true			// Disable if not stable
			),
			'ordernumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				'mandatory' => 'thirdparty',
				'tooltip' => 'OrderNumberSharingInfo',
				'enable' => '(! empty($conf->commande->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				//'disable' => true
			),
			'invoice' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',
				'mandatory' => 'thirdparty',
				'enable' => '(! empty($conf->facture->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				),
				'active' => true
			),
			'invoicenumber' => array(
				'type' => 'objectnumber',
				'icon' => 'cogs',
				'mandatory' => 'thirdparty',
				'tooltip' => 'InvoiceNumberSharingInfo',
				'enable' => '(! empty($conf->facture->enabled) && ! empty($conf->societe->enabled))',
				'display' => '! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED)',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					),
					'thirdparty' => array(
						'showhide' => true,
						'hide' => true,
						'del' => true
					)
				)
			),
			'supplier_proposal' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					)
				),
				'disable' => true
			),
			'supplier_order' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					)
				),
				'disable' => true
			),
			'supplier_invoice' => array(
				'type' => 'object',
				'icon' => 'file-pdf-o',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					)
				),
				'disable' => true
			),
			'intervention' => array(
				'type' => 'object',
				'icon' => 'wrench',
				'input' => array(
					'global' => array(
						'hide' => true,
						'del' => true
					)
				),
				'disable' => true
			)
		);

		// Module name if different of object name (eg $conf->commande->enabled)
		$this->sharingmodulename = array(
			'thirdparty'			=> 'societe',
			'proposal'				=> 'propal',
			'proposalnumber'		=> 'propal',
			'order'					=> 'commande',
			'ordernumber'			=> 'commande',
			'invoice'				=> 'facture',
			'invoicenumber'			=> 'facture',
			'product'				=> (empty($conf->product->enabled) && !empty($conf->service->enabled) ? 'service' : 'product'),
			'productprice'			=> 'product',
			'productsupplierprice'	=> 'product',
			'project'				=> 'projet',
			'member'				=> 'adherent',
			'membertype'			=> 'adherent',
			'member_type'			=> 'adherent',			// deprecated
			//'membertype'			=> 'member_type',		// deprecated
			'intervention'			=> 'ficheinter',
			'category'				=> 'categorie',
			'bankaccount'			=> 'banque',
			'bank_account'			=> 'banque',			// deprecated
			//'bankaccount'			=> 'banque_account',	// deprecated
		);

		$this->sharingdicts = array(
			'c_paiement' => array(
				'societe' => array(
					'mode_reglement',
					'mode_reglement_supplier'
				),
				'propal'				=> 'fk_mode_reglement',
				'commande'				=> 'fk_mode_reglement',
				'facture'				=> 'fk_mode_reglement',
				'facture_rec'			=> 'fk_mode_reglement',
				'commande_fournisseur'	=> 'fk_mode_reglement',
				'facture_fourn'			=> 'fk_mode_reglement',
				'supplier_proposal'		=> 'fk_mode_reglement',
				'chargesociales'		=> 'fk_mode_reglement',
				'don'					=> 'fk_payment',
				'paiement'				=> 'fk_paiement',
				'paiementfourn'			=> 'fk_paiement',
				'paiement_facture'		=> 'fk_paiement',
				'expensereport'			=> 'fk_c_paiement',
				'paiementcharge'		=> 'fk_typepaiement',
				'tva'					=> 'fk_typepayment',
				'payment_various'		=> 'fk_typepayment',
				'payment_salary'		=> 'fk_typepayment',
				'payment_expensereport'	=> 'fk_typepayment',
				'payment_donation'		=> 'fk_typepayment',
				'loan_schedule'			=> 'fk_typepayment',
				'payment_loan'			=> 'fk_typepayment'
			),
			'c_payment_term' => array(
				'societe' => array(
					'cond_reglement',
					'cond_reglement_supplier'
				),
				'propal'				=> 'fk_cond_reglement',
				'commande'				=> 'fk_cond_reglement',
				'facture'				=> 'fk_cond_reglement',
				'facture_rec'			=> 'fk_cond_reglement',
				'commande_fournisseur'	=> 'fk_cond_reglement',
				'facture_fourn'			=> 'fk_cond_reglement',
				'supplier_proposal'		=> 'fk_cond_reglement'
			)
		);

		if (! empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING))
		{
			$externalmodules = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);

			if (is_array($externalmodules) && ! empty($externalmodules))
			{
				foreach($externalmodules as $params)
				{
					if (is_array($params) && ! empty($params))
					{
						if (is_array($params['addzero']) && ! empty($params['addzero'])) {
							array_push($this->addzero, $params['addzero']);
						}

						if (is_array($params['sharingelements']) && ! empty($params['sharingelements'])) {
							$this->sharingelements = array_merge($this->sharingelements, $params['sharingelements']);
						}

						if (is_array($params['sharingmodulename']) && ! empty($params['sharingmodulename']))
						{
							$this->sharingmodulename = array_merge($this->sharingmodulename, $params['sharingmodulename']);
						}
					}
				}
			}
		}
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return	void
	 */
	private function getInstanceDao()
	{
		if (! is_object($this->dao))
		{
			$this->dao = new DaoMulticompany($this->db);
		}
	}


	/**
	 * setHtmlTitle
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 */
	public function setHtmlTitle($parameters=false)
	{
		global $conf;

		if (empty($conf->global->MULTICOMPANY_HIDE_HTML_TITLE)) {
			$this->resprints = ' + multicompany';
		}

		return 0;
	}


	/**
	 * 	Enter description here ...
	 *
	 * 	@param	string	$action		Action type
	 */
	public function doAdminActions(&$action='')
	{
		global $conf, $user, $langs;

		$this->getInstanceDao();

		$id				= GETPOST('id','int');
		$label			= GETPOST('label','alpha');
		$name			= GETPOST('name','alpha');
		$description	= GETPOST('description','alpha');
		$cancel			= GETPOST('cancel', 'alpha');
		$addandstay		= GETPOST('addandstay', 'alpha');
		$cancelandstay	= GETPOST('cancelandstay', 'alpha');
		$updateandstay	= GETPOST('updateandstay', 'alpha');
		$template		= (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? null : GETPOST('template', 'int'));
		$usetemplate	= (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? null : GETPOST('usetemplate', 'int'));
		$visible		= GETPOST('visible', 'int');
		$active			= GETPOST('active', 'int');

		if ($action === 'add' && empty($cancel) && $user->admin && ! $user->entity)
		{
			$error=0;

			if (empty($label))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), 'errors');
				$action = 'create';
			}
			else if (empty($name))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("CompanyName")), 'errors');
				$action = 'create';
			}

			// Verify if label already exist in database
			if (! $error)
			{
				$this->dao->getEntities();
				if (! empty($this->dao->entities))
				{
					foreach($this->dao->entities as $entity)
					{
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if ($error)
					{
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'create';
					}
				}
			}

			if (! $error)
			{
				$this->db->begin();

				$this->dao->label		= $label;
				$this->dao->description	= $description;
				$this->dao->visible		= ((! empty($template) && empty($usetemplate)) ? 2 : ((! empty($visible) || ! empty($conf->global->MULTICOMPANY_VISIBLE_BY_DEFAULT)) ? 1 : 0));
				$this->dao->active		= ((! empty($active) || ! empty($conf->global->MULTICOMPANY_ACTIVE_BY_DEFAULT))?1:0);

				if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
					$this->dao->options['proposalnumber_referent']	= (GETPOST('proposalnumber_referring_entity', 'int') ? GETPOST('proposalnumber_referring_entity', 'int') : null);
					$this->dao->options['invoicenumber_referent']	= (GETPOST('invoicenumber_referring_entity', 'int') ? GETPOST('invoicenumber_referring_entity', 'int') : null);
					foreach ($this->sharingelements as $element => $params) {
						if ((($params['object'] || $params['objectnumber']) && ! isset($params['disable'])) && (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) continue;
						$uppername = strtoupper($element);
						$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
						if (! empty($conf->global->$constname)) {
							$shareentities = GETPOST($element.'_to', 'array');
							$shareentities = array_unique($shareentities); sort($shareentities);
							$this->dao->options['sharings'][$element] = (! empty($shareentities) ? $shareentities : null);
							if (! empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
								$this->dao->options['addtoallother'][$element] = GETPOST('addtoallother_'.$element, 'int');
							}
						}
					}
				}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

				$id = $this->dao->create($user);
				if ($id <= 0)
				{
					$error++;
					$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					setEventMessage($errors, 'errors');
					$action = 'create';
				}

				if (! $error && $id > 0)
				{
					if (! empty($usetemplate) && is_numeric($usetemplate))
					{
						$const = $this->dao->getEntityConfig($usetemplate);
						if (! empty($const))
						{
							// Reload modules
							foreach ($const as $key => $value)
							{
								if (preg_match('/^MAIN\_MODULE\_([0-9A-Z]+)$/', $key, $reg))
								{
									if (! empty($reg[1]))
									{
										$classname = 'mod' . ucfirst(strtolower($reg[1]));

										$res = @include_once DOL_DOCUMENT_ROOT.'/core/modules/'.$classname.'.class.php';

										if ($res) {
											dol_syslog(get_class($this)."::reloadModules template=".$usetemplate." module=".$key." classname=".$classname, LOG_DEBUG);
											$mod=new $classname($this->db);
											$mod->init('forceactivate');
										} else {
											dol_syslog(get_class($this)."::reloadModules template=".$usetemplate." module=".$key." classname=".$classname, LOG_ERR);
										}
									}
								}
							}

							// Overwrite with template
							foreach ($const as $key => $value)
							{
								dolibarr_set_const($this->db, $key, $value, 'chaine', 0, '', $id);
							}
						}
					}

					$country_id		= GETPOST('country_id', 'int');
					$country		= getCountry($country_id, 'all');
					$country_code	= $country['code'];
					$country_label	= $country['label'];

					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST('address', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST('town', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST('zipcode', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST('departement_id', 'int'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST('currency_code', 'alpha'),'chaine',0,'',$id);
					dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST('main_lang_default', 'alpha'),'chaine',0,'',$id);

					if (empty($usetemplate))
					{
						$dir	= "/multicompany/sql/";

						// Load sql init_new_entity.sql file
						$file 	= 'init_new_entity_nocrypt.sql';
						if (! empty($conf->db->dolibarr_main_db_encryption) && ! empty($conf->db->dolibarr_main_db_cryptkey))
						{
							$file = 'init_new_entity.sql';
						}
						$fullpath = dol_buildpath($dir.$file);

						if (file_exists($fullpath))
						{
							$result=run_sql($fullpath,1,$id);
						}

						$dir	= "/multicompany/sql/dict/";

						foreach($this->sharingdicts as $dict => $data)
						{
							// Load sql init_new_entity_dict.sql file
							$file 	= 'init_new_entity_'.$dict.'.sql';
							$fullpath = dol_buildpath($dir.$file);

							if (file_exists($fullpath))
							{
								$result = run_sql($fullpath, 1, $id);
							}
						}
					}
					else
					{
						require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

						$modulesdir = dolGetModulesDirs();

						foreach ($modulesdir as $dir)
						{
							// Load modules attributes in arrays (name, numero, orders) from dir directory
							//print $dir."\n<br>";
							$handle=@opendir(dol_osencode($dir));
							if (is_resource($handle))
							{
								while (($file = readdir($handle))!==false)
								{
									if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
									{
										$modName = substr($file, 0, dol_strlen($file) - 10);

										if ($modName)
										{
											include_once $dir."/".$file;
											$objMod = new $modName($this->db);
											// Load all lang files of module
											if (isset($objMod->langfiles) && is_array($objMod->langfiles))
											{
												foreach($objMod->langfiles as $domain)
												{
													$langs->load($domain);
												}
											}
											// Load all permissions
											if ($objMod->rights_class)
											{
												$ret=$objMod->insert_permissions(0, $id);
											}
										}
									}
								}
							}
						}

						// Add entity to all others entities
						if (! empty($this->dao->options['addtoallother']))
						{
							$this->dao->getEntities(true, array($id), false);
							foreach ($this->dao->entities as $entity)
							{
								if (! is_array($entity->options)) $entity->options = array();
								if (! is_array($entity->options['sharings'])) $entity->options['sharings'] = array();

								foreach($this->dao->options['sharings'] as $element => $values)
								{
									if (! is_array($entity->options['sharings'][$element])) $entity->options['sharings'][$element] = array();

									if ($this->dao->options['addtoallother'][$element] == '1')
									{
										array_push($entity->options['sharings'][$element], (string) $id);
										$entity->update($entity->id, $user);
									}
								}
							}
						}

						$listofgroups = $this->dao->getListOfGroups();
						if (! empty($listofgroups))
						{
							foreach($listofgroups as $groupid)
							{
								// Duplicate usergroup rights
								$ret = $this->duplicateUserGroupRights($groupid, $usetemplate, array($id));
								if ($ret < 0) $error++;

								// Add users to groups if linked with template
								$userslist = $this->dao->getListOfUsersInGroupByTemplate($groupid, $usetemplate);
								if ($userslist < 0) $error++;
								elseif (! empty($userslist))
								{
									foreach($userslist as $usertemplate)
									{
										$result = $usertemplate->SetInGroup($groupid, $id);
										if ($result < 0)
										{
											$error++;
											break;
										}
									}
								}
							}
						}
					}

					if (! $error)
					{
						$this->db->commit();
					}
					else
					{
						$this->db->rollback();
					}

					if (! empty($addandstay))
					{
						header("Location: " . $_SERVER['PHP_SELF'].'?action=edit&id='.$id);
						exit;
					}
				}
				else
				{
					$this->db->rollback();
				}
			}
		}
		else if ($action === 'edit' && $user->admin && ! $user->entity)
		{
			$error=0;

			if (! empty($cancel))
			{
				header("Location: " . $_SERVER['PHP_SELF']);
				exit;
			}

			if ($this->dao->fetch($id) < 0)
			{
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			}
		}
		else if ($action === 'update' && empty($cancel) && $id > 0 && $user->admin && ! $user->entity)
		{
			$error=0;

			if (! empty($cancelandstay))
			{
				$action = 'edit';
				return;
			}

			$ret = $this->dao->fetch($id);
			if ($ret < 0)
			{
				$error++;
				setEventMessage($langs->trans("ErrorEntityIsNotValid"), 'errors');
				$action = '';
			}
			else if (empty($label))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), 'errors');
				$action = 'edit';
			}
			else if (empty($name))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("CompanyName")), 'errors');
				$action = 'edit';
			}

			// Verify if label already exist in database
			if (! $error)
			{
				$this->dao->getEntities();
				if (! empty($this->dao->entities))
				{
					foreach($this->dao->entities as $entity)
					{
						if ($entity->id == $this->dao->id) continue;
						if (strtolower($entity->label) == strtolower($label)) $error++;
					}
					if ($error)
					{
						setEventMessage($langs->trans("ErrorEntityLabelAlreadyExist"), 'errors');
						$action = 'edit';
					}
				}
			}

			if (! $error)
			{
				$this->db->begin();

				$this->dao->label = $label;
				$this->dao->description	= $description;

				if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
					$this->dao->options['proposalnumber_referent']	= (GETPOST('proposalnumber_referring_entity', 'int') ? GETPOST('proposalnumber_referring_entity', 'int') : null);
					$this->dao->options['invoicenumber_referent']	= (GETPOST('invoicenumber_referring_entity', 'int') ? GETPOST('invoicenumber_referring_entity', 'int') : null);
					foreach ($this->sharingelements as $element => $params) {
						if ((($params['object'] || $params['objectnumber']) && ! isset($params['disable'])) && (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) continue;
						$uppername = strtoupper($element);
						$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
						if (! empty($conf->global->$constname)) {
							$shareentities = GETPOST($element.'_to', 'array');
							$shareentities = array_unique($shareentities); sort($shareentities);
							$this->dao->options['sharings'][$element]	= (! empty($shareentities) ? $shareentities : null);
							if (! empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
								$this->dao->options['addtoallother'][$element] = GETPOST('addtoallother_'.$element, 'int');
							}
						}
					}
				}

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element, true);
				$extrafields->setOptionalsFromPost($extralabels, $this->dao);

				$ret = $this->dao->update($this->dao->id, $user);
				if ($ret <= 0)
				{
					$error++;
					$errors=($this->dao->error ? array($this->dao->error) : $this->dao->errors);
					setEventMessage($errors, 'errors');
					$action = 'edit';
				}

				if (! $error && $ret > 0)
				{
					$country_id		= GETPOST('country_id', 'int');
					$country		= getCountry($country_id, 'all');
					$country_code	= $country['code'];
					$country_label	= $country['label'];

					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_COUNTRY", $country_id.':'.$country_code.':'.$country_label,'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_NOM",$name,'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ADDRESS",GETPOST('address', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_TOWN",GETPOST('town', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_ZIP",GETPOST('zipcode', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_INFO_SOCIETE_STATE",GETPOST('departement_id', 'int'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_MONNAIE",GETPOST('currency_code', 'alpha'),'chaine',0,'',$this->dao->id);
					dolibarr_set_const($this->db, "MAIN_LANG_DEFAULT",GETPOST('main_lang_default', 'alpha'),'chaine',0,'',$this->dao->id);

					$this->db->commit();

					if (! empty($updateandstay)) {
						$action = 'edit';
					}
				}
				else
				{
					$this->db->rollback();
				}
			}
		}
	}

	/**
	 * 	Return action of hook
	 * 	@param		object			Linked object
	 */
	public function doActions($parameters=false, &$object, &$action='')
	{
		global $conf, $user;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		// Clear constants cache after company infos update
		if (is_array($currentcontext))
		{
			if ((in_array('admincompany', $currentcontext) || in_array('adminihm', $currentcontext)) && ($action == 'update' || $action == 'updateedit'))
			{
				clearCache($conf->entity);
				clearCache('constants_' . $conf->entity);
			}
			else if ((in_array('groupcard', $currentcontext) || in_array('groupperms', $currentcontext)) && $object->element == 'usergroup')
			{
				global $entity;

				// Users/Groups management only in master entity if transverse mode
				if ($conf->entity > 1 && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					accessforbidden();
				}

				if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$entity=(GETPOST('entity','int') ? GETPOST('entity','int') : $conf->entity);
				} else {
					$entity=(! empty($object->entity) ? $object->entity : $conf->entity);
				}

				// Add/Remove user into group
				if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && in_array('groupcard', $currentcontext) && ($action == 'adduser' || $action =='removeuser') && (! empty($userid) && $userid > 0) && $caneditperms)
				{
					if ($action == 'adduser')
					{
						$entities = GETPOST("entities", "array", 3);

						if (is_array($entities) && ! empty($entities))
						{
							$error=0;

							foreach ($entities as $entity_id)
							{
								$object->fetch($id);
								$object->oldcopy = clone $object;

								$edituser = new User($this->db);
								$edituser->fetch($userid);
								$result=$edituser->SetInGroup($object->id, $entity_id);
								if ($result < 0)
								{
									$error++;
									break;
								}
							}
							if (!$error)
							{
								header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
								exit;
							}
							else
							{
								$this->error = $edituser->error;
								$this->errors = $edituser->errors;
								return -1;
							}
						}
					}
					else if ($action == 'removeuser')
					{
						$object->fetch($id);
						$object->oldcopy = clone $object;

						$edituser = new User($this->db);
						$edituser->fetch($userid);
						$result=$edituser->RemoveFromGroup($object->id, $entity);

						if ($result > 0)
						{
							header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
							exit;
						}
						else
						{
							$this->error = $object->error;
							$this->errors = $object->errors;
							return -1;
						}
					}
					return 1;
				}
			}
			else if ((in_array('usercard', $currentcontext) || in_array('userperms', $currentcontext)) && $object->element == 'user')
			{
				global $entity, $caneditperms;

				// Users/Groups management only in master entity if transverse mode
				if ($conf->entity > 1 && $conf->global->MULTICOMPANY_TRANSVERSE_MODE)
				{
					if (in_array('usercard', $currentcontext) && ($action == 'create' || $action == 'adduserldap')) {
						accessforbidden();
					} else if (in_array('userperms', $currentcontext)) {
						$caneditperms = false;
					}
				}

				if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					if (GETPOSTISSET('entity')) {

						$entity = GETPOST('entity','int');

					} else {

						$entity = $conf->entity;

						// Check usergroup if user not in master entity
						if (in_array('userperms', $currentcontext) && ! empty($user->admin) && empty($user->entity) && $conf->entity == 1)
						{
							require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
							$group = new UserGroup($this->db);
							$ret = $group->listGroupsForUser($object->id, false);
							if (! empty(current($ret)->usergroup_entity)) {
								sort(current($ret)->usergroup_entity);
								if (current($ret)->usergroup_entity[0] > 1) {
									$entity = current($ret)->usergroup_entity[0];
								}
							}
						}
					}
				} else {
					$entity=(! empty($object->entity) ? $object->entity : $conf->entity);
				}

				// Action add usergroup
				if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && in_array('usercard', $currentcontext) && ($action == 'addgroup' || $action == 'removegroup') && (! empty($group) && $group > 0) && $caneditgroup)
				{
					if ($action == 'addgroup')
					{
						$entities = GETPOST("entities", "array", 3);

						if (is_array($entities) && ! empty($entities))
						{
							$error=0;

							foreach ($entities as $entity_id)
							{
								$object->fetch($id);
								$result = $object->SetInGroup($group, $entity_id);
								if ($result < 0)
								{
									$error++;
									break;
								}
							}
							if ($error)
							{
								$this->error = $object->error;
								$this->errors = $object->errors;
								return -1;
							}
							else
							{
								header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
								exit;
							}
						}
					}
					else if ($action == 'removegroup')
					{
						$object->fetch($id);
						$result = $object->RemoveFromGroup($group, $entity);
						if ($result > 0) {
							header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
							exit;
						}
						else
						{
							$this->error = $object->error;
							$this->errors = $object->errors;
							return -1;
						}
					}
					return 1;
				}
			}
			else if (in_array('productcard', $currentcontext) && ($object->element == 'product' || $object->element == 'service'))
			{
				if ($action != 'create' && $action != 'add')
				{
					if ($object->entity != $conf->entity)
					{
						global $usercanread, $usercancreate, $usercandelete;

						/*if (empty($user->rights->multicompany->product->read)) {
							$usercanread = false;
						}*/
						if (empty($user->rights->multicompany->product->write)) {
							$usercancreate = false;
						}
						if (empty($user->rights->multicompany->product->delete)) {
							$usercandelete = false;
						}
					}
				}
			}
			else if (in_array('propalcard', $currentcontext) && $object->element == 'propal')
			{
				if ($action != 'create' && $action != 'add')
				{
					if ($object->entity != $conf->entity)
					{
						global $usercanread, $usercancreate, $usercandelete, $usercanvalidate, $usercansend, $usercanclose;
						global $permissionnote, $permissiondellink, $permissiontoedit;
						global $disableedit, $disablemove, $disableremove;

						$this->getInstanceDao();

						$constants = array(
							'PROPALE_ADDON',
							'PROPALE_SAPHIR_MASK',
							'PROPALE_ADDON_PDF',
							'PROPALE_ADDON_PDF_ODT_PATH'
						);

						if (empty($conf->global->MULTICOMPANY_PROPOSALNUMBER_SHARING_ENABLED) || empty($mc->sharings['proposalnumber'])) // Check if current entity not use the proposalnumber sharing
						{
							foreach ($constants as $constname)
							{
								$res = $this->dao->getEntityConfig($object->entity, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with object entity config
								}
							}
						}
						else
						{
							foreach ($constants as $constname)
							{
								$referent = (! empty($mc->proposalnumber_referent)?$mc->proposalnumber_referent:1);
								$res = $this->dao->getEntityConfig($referent, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with referent config
								}
							}
						}

						// Override $mysoc with data of object entity
						if ($action == 'builddoc')
						{
							global $mysoc;

							$current = $this->dao->getEntityConfig($conf->entity, 'MAIN_INFO_*');
							if (! empty($current))
							{
								foreach($current as $constname => $value)
								{
									unset($conf->global->$constname);
								}
							}

							$other = $this->dao->getEntityConfig($object->entity, 'MAIN_INFO_*');
							if (! empty($other))
							{
								foreach($other as $constname => $value)
								{
									$conf->global->$constname = $value; // override current entity config with referent config
								}
							}

							$mysoc->setMysoc($conf);
						}

						if (empty($user->rights->multicompany->propal->read)) {
							$usercanread = false;
						}
						if (empty($user->rights->multicompany->propal->write)) {
							$usercancreate = false;

							$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
							$permissiondellink = $usercancreate;	// Used by the include of actions_dellink.inc.php
							$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

							// for object lines
							$disableedit = true;
							$disablemove = true;
							$disableremove = true;
						}
						if (empty($user->rights->multicompany->propal->delete)) {
							$usercandelete = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->propal_advance->validate))) {
							$usercanvalidate = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercanread)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->propal_advance->send))) {
							$usercansend = false;
						}
						if (empty($user->rights->multicompany->propal->close)) {
							$usercanclose = false;
						}
					}
				}
			}
			else if (in_array('ordercard', $currentcontext) && $object->element == 'commande')
			{
				if ($action != 'create' && $action != 'add')
				{
					if ($object->entity != $conf->entity)
					{
						global $usercanread, $usercancreate, $usercandelete, $usercanvalidate, $usercansend, $usercanclose, $usercancancel;
						global $permissionnote, $permissiondellink, $permissiontoedit;
						global $disableedit, $disablemove, $disableremove;

						$this->getInstanceDao();

						$constants = array(
							'COMMANDE_ADDON',
							'COMMANDE_SAPHIR_MASK',
							'COMMANDE_ADDON_PDF',
							'COMMANDE_ADDON_PDF_ODT_PATH'
						);

						if (empty($conf->global->MULTICOMPANY_ORDERNUMBER_SHARING_ENABLED) || empty($mc->sharings['ordernumber'])) // Check if current entity not use the proposalnumber sharing
						{
							foreach ($constants as $constname)
							{
								$res = $this->dao->getEntityConfig($object->entity, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with object entity config
								}
							}
						}
						else
						{
							foreach ($constants as $constname)
							{
								$referent = (! empty($mc->ordernumber_referent)?$mc->ordernumber_referent:1);
								$res = $this->dao->getEntityConfig($referent, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with referent config
								}
							}
						}

						// Override $mysoc with data of object entity
						if ($action == 'builddoc')
						{
							global $mysoc;

							$current = $this->dao->getEntityConfig($conf->entity, 'MAIN_INFO_*');
							if (! empty($current))
							{
								foreach($current as $constname => $value)
								{
									unset($conf->global->$constname);
								}
							}

							$other = $this->dao->getEntityConfig($object->entity, 'MAIN_INFO_*');
							if (! empty($other))
							{
								foreach($other as $constname => $value)
								{
									$conf->global->$constname = $value; // override current entity config with referent config
								}
							}

							$mysoc->setMysoc($conf);
						}

						if (empty($user->rights->multicompany->order->read)) {
							$usercanread = false;
						}
						if (empty($user->rights->multicompany->order->write)) {
							$usercancreate = false;

							$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
							$permissiondellink = $usercancreate;	// Used by the include of actions_dellink.inc.php
							$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

							// for object lines
							$disableedit = true;
							$disablemove = true;
							$disableremove = true;
						}
						if (empty($user->rights->multicompany->order->delete)) {
							$usercandelete = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->order_advance->validate))) {
							$usercanvalidate = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->order_advance->cancel))) {
							$usercancancel = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercanread)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->order_advance->send))) {
							$usercansend = false;
						}
						if (empty($user->rights->multicompany->order->close)) {
							$usercanclose = false;
						}
					}
				}
			}
			else if (in_array('invoicecard', $currentcontext) && $object->element == 'facture')
			{
				if ($action != 'create' && $action != 'add')
				{
					if ($object->entity != $conf->entity)
					{
						global $usercanread, $usercancreate, $usercandelete, $usercanvalidate, $usercansend, $usercanreopen, $usercanunvalidate;
						global $permissionnote, $permissiondellink, $permissiontoedit;
						global $disableedit, $disablemove, $disableremove;

						$this->getInstanceDao();

						$constants = array(
							'FACTURE_ADDON',
							'FACTURE_MERCURE_MASK_INVOICE',
							'FACTURE_MERCURE_MASK_CREDIT',
							'FACTURE_MERCURE_MASK_DEPOSIT',
							'FACTURE_MERCURE_MASK_REPLACEMENT',
							'PROPALE_ADDON_PDF',
							'FACTURE_ADDON_PDF_ODT_PATH'
						);

						if (empty($conf->global->MULTICOMPANY_INVOICENUMBER_SHARING_ENABLED) || empty($mc->sharings['invoicenumber'])) // Check if current entity not use the proposalnumber sharing
						{
							foreach ($constants as $constname)
							{
								$res = $this->dao->getEntityConfig($object->entity, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with object entity config
								}
							}
						}
						else
						{
							foreach ($constants as $constname)
							{
								$referent = (! empty($mc->invoicenumber_referent)?$mc->invoicenumber_referent:1);
								$res = $this->dao->getEntityConfig($referent, $constname);
								if (! empty($res[$constname])) {
									$conf->global->$constname = $res[$constname]; // override current entity config with referent config
								}
							}
						}

						// Override $mysoc with data of object entity
						if ($action == 'builddoc')
						{
							global $mysoc;

							$current = $this->dao->getEntityConfig($conf->entity, 'MAIN_INFO_*');
							if (! empty($current))
							{
								foreach($current as $constname => $value)
								{
									unset($conf->global->$constname);
								}
							}

							$other = $this->dao->getEntityConfig($object->entity, 'MAIN_INFO_*');
							if (! empty($other))
							{
								foreach($other as $constname => $value)
								{
									$conf->global->$constname = $value; // override current entity config with referent config
								}
							}

							$mysoc->setMysoc($conf);
						}

						if (empty($user->rights->multicompany->invoice->read)) {
							$usercanread = false;
						}
						if (empty($user->rights->multicompany->invoice->write)) {
							$usercancreate = false;

							$permissionnote = $usercancreate; // Used by the include of actions_setnotes.inc.php
							$permissiondellink = $usercancreate;	// Used by the include of actions_dellink.inc.php
							$permissiontoedit = $usercancreate; // Used by the include of actions_lineupdown.inc.php

							// for object lines
							$disableedit = true;
							$disablemove = true;
							$disableremove = true;
						}
						if (empty($user->rights->multicompany->invoice->delete)) {
							$usercandelete = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->validate))) {
							$usercanvalidate = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercanread)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->send))) {
							$usercansend = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->multicompany->invoice_advance->reopen))) {
							$usercanreopen = false;
						}
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($usercancreate)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->multicompany->invoice_advance->unvalidate))) {
							$usercanunvalidate = false;
						}
					}
				}
			}
		}

		//var_dump($_POST);
/*
		if (empty($_SESSION['dol_tables_list_fk_soc']))
		{
			$_SESSION['dol_tables_list_fk_soc'] = getTablesWithField('fk_soc', array());
		}
		var_dump($_SESSION['dol_tables_list_fk_soc']);
*/
		//$include=false;
		//$exclude=false;
/*
		$exclude = array(
			MAIN_DB_PREFIX . 'user',
			MAIN_DB_PREFIX . 'user_employment',
			MAIN_DB_PREFIX . 'user_param',
			MAIN_DB_PREFIX . 'user_rib',
			MAIN_DB_PREFIX . 'user_rights',
			MAIN_DB_PREFIX . 'usergroup',
			MAIN_DB_PREFIX . 'usergroup_rights',
			MAIN_DB_PREFIX . 'usergroup_user',
			MAIN_DB_PREFIX . 'rights_def',
		);
*/
		//$exclude = '/(const|user|rights\_def)+/';
		//$include = '/(const|user|rights\_def)+/';

		//if (empty($_SESSION['dol_tables_list_entity']))
/*		{
			$_SESSION['dol_tables_list_entity'] = getTablesWithField('entity', $exclude, $include);
		}

		var_dump($_SESSION['dol_tables_list_entity']);
*/
/*
		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && ! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
		{
			if (in_array($contextpage, $this->thirdpartycontextlist) || in_array($contextpage, $this->contactcontextlist))
			{
				if (GETPOST('confirmmassaction') && GETPOST('massaction') == 'modify_entity')
				{
					var_dump($_POST['toselect']);
				}
			}
		}
*/
		return 0;
	}

	/**
	 *
	 */
	public function showLinkedObjectBlock($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		//var_dump($object->linkedObjects);

		foreach($object->linkedObjects as $objecttype => $objects)
		{
			foreach($objects as $key => $tmpobj)
			{
				if (empty($tmpobj->entity)) continue; // for debug

				if ($tmpobj->entity != $conf->entity)
				{
					$element = $objecttype;
					if ($objecttype == 'propal') $element = 'proposal';
					if ($objecttype == 'commande') $element = 'order';
					if ($objecttype == 'facture') $element = 'invoice';

					//var_dump($element);var_dump($mc->sharings[$element]);
					//var_dump($object->linkedObjects[$objecttype][$key]);

					if (! empty($mc->sharings[$element]) && in_array($tmpobj->entity, $mc->sharings[$element]))
					{
						//nothing
					}
					else
					{
						unset($object->linkedObjects[$objecttype][$key]);
					}
				}
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function showLinkToObjectBlock($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$perms = 1;
		$propalperms = 1;
		$orderperms = 1 ;
		$invoiceperms = 1 ;

		if (in_array('propalcard', $currentcontext) && $object->element == 'propal')
		{
			if ($object->entity != $conf->entity)
			{
				$propalperms = ! empty($user->rights->multicompany->propal->write);
			}
		}
		elseif (in_array('ordercard', $currentcontext) && $object->element == 'commande')
		{
			if ($object->entity != $conf->entity)
			{
				$orderperms = ! empty($user->rights->multicompany->order->write);
			}
		}
		elseif (in_array('invoicecard', $currentcontext) && $object->element == 'facture')
		{
			if ($object->entity != $conf->entity)
			{
				$invoiceperms = ! empty($user->rights->multicompany->invoice->write);
			}
		}

		$this->results = array('propal' => array('enabled'=>$conf->propal->enabled, 'perms'=>$propalperms, 'label'=>'LinkToProposal',	'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('propal').')'),
			'order'=>array('enabled'=>$conf->commande->enabled, 'perms'=>$orderperms, 'label'=>'LinkToOrder', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('commande').')'),
			'invoice'=>array('enabled'=>$conf->facture->enabled, 'perms'=>$invoiceperms, 'label'=>'LinkToInvoice', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_client, t.total as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('invoice').')'),
			'contrat'=>array('enabled'=>$conf->contrat->enabled , 'perms'=>$perms, 'label'=>'LinkToContract', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, '' as total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('contract').')'),
			'fichinter'=>array('enabled'=>$conf->ficheinter->enabled, 'perms'=>$perms, 'label'=>'LinkToIntervention', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('intervention').')'),
			'supplier_proposal'=>array('enabled'=>$conf->supplier_proposal->enabled , 'perms'=>$perms, 'label'=>'LinkToSupplierProposal', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, '' as ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."supplier_proposal as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('supplier_proposal').')'),
			'order_supplier'=>array('enabled'=>$conf->supplier_order->enabled , 'perms'=>$perms, 'label'=>'LinkToSupplierOrder', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande_fournisseur as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('commande_fournisseur').')'),
			'invoice_supplier'=>array('enabled'=>$conf->supplier_invoice->enabled , 'perms'=>$perms, 'label'=>'LinkToSupplierInvoice', 'sql'=>"SELECT s.rowid as socid, s.nom as name, s.client, t.rowid, t.ref, t.ref_supplier, t.total_ht FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as t WHERE t.fk_soc = s.rowid AND t.fk_soc IN (".$listofidcompanytoscan.') AND t.entity IN ('.getEntity('facture_fourn').')')
		);

		return 1;
	}

	/**
	 *
	 */
	public function addMoreActionsButtons($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		/*if (in_array('productcard', $currentcontext) && ($object->element == 'product' || $object->element == 'service'))
		{
			if ($object->entity != $conf->entity)
			{
				$user->rights->produit->creer = 0;
				$user->rights->produit->supprimer = 0;
				$user->rights->service->creer = 0;
				$user->rights->service->supprimer = 0;

				//return 1;
			}
		}*/

		return 0;
	}

	/**
	 *
	 */
	public function printUserPasswordField($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) return 0;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany printUserPasswordField -->'."\n";

		if (in_array('usercard', $currentcontext) && $object->element == 'user' && ! empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX) && checkMulticompanyAutentication())
		{
			if ($action == 'create')
			{
				$this->resprints.= '<input size="30" maxsize="32" type="text" name="password" value="" autocomplete="new-password">';
			}
			else if ($action == 'edit')
			{
				if ($caneditpassword)
				{
					$this->resprints.= '<input size="30" maxlength="32" type="password" class="flat" name="password" value="'.$object->pass.'" autocomplete="new-password">';
				}
				else
				{
					$this->resprints.= preg_replace('/./i','*',$object->pass);
				}
			}
			else
			{
				if ($object->pass) $this->resprints.= preg_replace('/./i','*',$object->pass);
				else
				{
					if ($user->admin) $this->resprints.= ($valuetoshow?(' '.$langs->trans("or").' '):'').$langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
					else $this->resprints.= $langs->trans("Hidden");
				}
			}
		}

		$this->resprints.= '<!-- END multicompany printUserPasswordField -->'."\n";

		return 0;
	}

	/**
	 *
	 */
	public function formConfirm($parameters=false, &$object, &$action='')
	{
		global $conf, $langs;
		global $mc, $form;

		if (empty($conf->multicompany->enabled)) return 0;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$return = 0;
		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany formConfirm -->'."\n";

		if (in_array('propalcard', $currentcontext) && $object->element == 'propal')
		{
			if ($action == 'clone' && ! empty($mc->entities['proposal'])) {
				// Create an array for form
				$formquestion = array(
					// 'text' => $langs->trans("ConfirmClone"),
					// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
					// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
					array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=2 OR s.client=3)')),
					array('type' => 'other', 'name' => 'entity', 'label' => $langs->trans("SelectEntity"), 'value' => $mc->select_entities($conf->entity, 'entity', '', false, false, false, explode(',', $mc->entities['proposal'])))
				);
				if (!empty($conf->global->PROPAL_CLONE_DATE_DELIVERY) && !empty($object->date_livraison)) {
					$formquestion[] = array('type' => 'date', 'name' => 'date_delivery', 'label' => $langs->trans("DeliveryDate"), 'value' => $object->date_livraison);
				}
				// Incomplete payment. We ask if reason = discount or other
				$this->resprints.= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmClonePropal', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
				$return++;
			}
		}

		$this->resprints.= '<!-- END multicompany formConfirm -->'."\n";

		return $return;
	}

	/**
	 *
	 */
	public function formObjectOptions($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form;

		if (empty($conf->multicompany->enabled)) return 0;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany formObjectOptions -->'."\n";

		if (in_array('thirdpartycard', $currentcontext) && $object->element == 'societe' && ! empty($user->admin) && empty($user->entity))
		{
			if ($action == 'create')
			{
				$this->resprints.= '<tr><td>'.fieldLabel('Entity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
				$s = $this->select_entities($conf->entity);
				$this->resprints.= $form->textwithpicto($s,$langs->trans("ThirdpartyEntityDesc"),1);
				$this->resprints.= '</td></tr>'."\n";
			}
		}
		else if (in_array('contactcard', $currentcontext) && $object->element == 'contact' && ! empty($user->admin) && empty($user->entity))
		{
			if ($action == 'create' && empty($objsoc))
			{
				$this->resprints.= '<tr><td>'.fieldLabel('Entity','entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
				$s = $this->select_entities($conf->entity);
				$this->resprints.= $form->textwithpicto($s,$langs->trans("ContactEntityDesc"),1);
				$this->resprints.= '</td></tr>'."\n";
			}
		}
		else if (in_array('usercard', $currentcontext) && $object->element == 'user')
		{
			if ($action == 'edit')
			{
				// TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($object->entity);
					$this->resprints.= "</td></tr>\n";
				}
				else
				{
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			}
			else if ($action == 'create')
			{
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
					$this->resprints.= '<td>'.$this->select_entities($conf->entity);
					$this->resprints.= "</td></tr>\n";
				}
				else
				{
					$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			}
			else if ($action != 'adduserldap')
			{
				if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
				{
					$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td><td>';
					if (empty($object->entity))
					{
						$this->resprints.= $langs->trans("AllEntities");
					}
					else
					{
						$this->getInfo($object->entity);
						$this->resprints.= $this->label;
					}
					$this->resprints.= "</td></tr>\n";
				}
			}
		}
		else if ((in_array('propalcard', $currentcontext) && $object->element == 'propal')
			|| (in_array('ordercard', $currentcontext) && $object->element == 'commande')
			|| (in_array('invoicecard', $currentcontext) && $object->element == 'facture')
		)
		{
			if ($action == 'create')
			{
				$this->resprints.= '<tr><td>'.$langs->trans("Entity").'</td>';
				$this->resprints.= '<td>'.$this->select_entities($conf->entity);
				$this->resprints.= "</td></tr>\n";
			}
		}

		$this->resprints.= '<!-- END multicompany formObjectOptions -->'."\n";

		return 0;
	}

	/**
	 *
	 */
	public function formCreateThirdpartyOptions($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form;

		if (empty($conf->multicompany->enabled)) return 0;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		//echo 'OK';

		//$this->resprints = 'OK';

		return 0;
	}

	/**
	 *
	 */
	public function formAddUserToGroup($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $form, $mc, $exclude;

		if (empty($conf->multicompany->enabled)) return 0;

		$langs->load('multicompany@multicompany');

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = '';

		if (is_array($currentcontext))
		{
			if (in_array('usercard', $currentcontext) && $object->element == 'user')
			{
				if ($action != 'edit' && $action != 'presend' && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
				{
					$this->resprints = "\n".'<!-- BEGIN multicompany formAddUserToGroup -->'."\n";

					if (! empty($groupslist))
					{
						$exclude=array();
					}

					if ($caneditgroup)
					{
						$this->resprints.= '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
						$this->resprints.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
						$this->resprints.= '<input type="hidden" name="action" value="addgroup" />';
					}
					$this->resprints.= '<table class="noborder centpercent">'."\n";

					$this->resprints.= '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans("Groups").'</th>'."\n";
					if (! empty($user->admin))
					{
						$this->resprints.= '<th class="liste_titre">'.$langs->trans("Entity").'</th>';
					}
					$this->resprints.= '<th class="liste_titre" align="right">';
					if ($caneditgroup && empty($user->entity))
					{
						// Users/Groups management only in master entity if transverse mode
						if ($conf->entity == 1)
						{
							$this->resprints.= $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
							$this->resprints.= ' &nbsp; ';
							if ($conf->entity == 1)
							{
								$entities = $this->getEntitiesList();
								$this->resprints.= $form->multiselectarray('entities', $entities, GETPOST('entities', 'array'), '', 0, '', 0, '20%');
							}
							else
							{
								$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
							}
							$this->resprints.= '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
						}
					}
					$this->resprints.= '</th></tr>'."\n";

					/*
					 * Groups assigned to user
					 */
					if (! empty($groupslist))
					{
						foreach($groupslist as $group)
						{
							$this->resprints.= '<tr class="oddeven">';
							$this->resprints.= '<td>';
							if ($caneditgroup)
							{
								$this->resprints.= '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
							}
							else
							{
								$this->resprints.= img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
							}
							$this->resprints.= '</td>';
							if (! empty($user->admin))
							{
								$this->resprints.= '<td class="valeur">';
								if (! empty($group->usergroup_entity))
								{
									$nb=0;
									foreach($group->usergroup_entity as $group_entity)
									{
										$mc->getInfo($group_entity);
										if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $mc->visible == 2) continue;

										$this->resprints.= '<span class="multicompany-entity-container">';
										if ($mc->visible == 2) {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fas fa-clone multicompany-button-template" title="'.$langs->trans("TemplateOfEntity").'"></span>';
										} else {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fas fa-globe multicompany-button-template" title="'.$langs->trans("Entity").'"></span>';
										}
										$this->resprints.= $mc->label . (empty($mc->active) ? ' ('.$langs->trans('Disabled').')' : ($mc->visible == 2 ? ' ('.$langs->trans('Template').')' : (empty($mc->visible) ? ' ('.$langs->trans('Hidden').')' : '')) );
										if ($conf->entity == 1 && empty($user->entity)) {
											$this->resprints.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group_entity.'">';
											$this->resprints.= img_picto($langs->trans("RemoveFromGroup"), 'unlink');
											$this->resprints.= '</a>';
										}
										$this->resprints.= '</span>';
									}
								}
							}
							$this->resprints.= '<td align="right">&nbsp;</td></tr>'."\n";
						}
					}
					else
					{
						$this->resprints.= '<tr '.$bc[false].'><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
					}

					$this->resprints.= "</table>";

					if ($caneditgroup)
					{
						$this->resprints.= '</form>';
					}

					$this->resprints.= '<!-- END multicompany formAddUserToGroup -->'."\n";

					return 1;
				}
			}
			else if (in_array('groupcard', $currentcontext) && $object->element == 'usergroup')
			{
				if ($action != 'edit' && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
				{
					$this->resprints = "\n".'<!-- BEGIN multicompany formAddUserToGroup -->'."\n";

					if (! empty($object->members))
					{
						$exclude=array();
					}

					if ($caneditperms && empty($user->entity))
					{
						$this->resprints.= '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
						$this->resprints.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						$this->resprints.= '<input type="hidden" name="action" value="adduser">';
						$this->resprints.= '<table class="noborder" width="100%">'."\n";
						$this->resprints.= '<tr class="liste_titre"><td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
						$this->resprints.= '<th class="liste_titre">';
						$this->resprints.= $form->select_dolusers('', 'user', 1, $exclude, 0, '', '', $object->entity);
						$this->resprints.= ' &nbsp; ';
						if ($conf->entity == 1)
						{
							$entities = $this->getEntitiesList(false, false, false, (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) ? false : true));
							$this->resprints.= $form->multiselectarray('entities', $entities, GETPOST('entities', 'array'), '', 0, '', 0, '20%');
						}
						else
						{
							$this->resprints.= '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
						}
						$this->resprints.= '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
						$this->resprints.= '</th></tr>'."\n";
						$this->resprints.= '</table></form>'."\n";
						$this->resprints.= '<br>';
					}

					/*
					 * Group members
					 */
					$this->resprints.= '<table class="noborder" width="100%">';
					$this->resprints.= '<tr class="liste_titre">';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Login").'</td>';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
					$this->resprints.= '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
					if ($conf->entity == 1)
					{
						$this->resprints.= '<td class="liste_titre">'.$langs->trans("Entity").'</td>';
					}
					$this->resprints.= '<td class="liste_titre" width="5" align="center">'.$langs->trans("Status").'</td>';
					$this->resprints.= '<td class="liste_titre" width="5" align="right">&nbsp;</td>';
					$this->resprints.= "</tr>\n";

					if (! empty($object->members))
					{
						foreach($object->members as $useringroup)
						{
							$this->resprints.= '<tr class="oddeven">';
							$this->resprints.= '<td>';
							$this->resprints.= $useringroup->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
							if ($useringroup->admin  && ! $useringroup->entity) $this->resprints.= img_picto($langs->trans("SuperAdministrator"),'redstar');
							else if ($useringroup->admin) $this->resprints.= img_picto($langs->trans("Administrator"),'star');
							$this->resprints.= '</td>';
							$this->resprints.= '<td>'.$useringroup->lastname.'</td>';
							$this->resprints.= '<td>'.$useringroup->firstname.'</td>';
							if ($conf->entity == 1 && ! empty($user->admin))
							{
								$this->resprints.= '<td class="valeur">';
								if (! empty($useringroup->usergroup_entity))
								{
									foreach($useringroup->usergroup_entity as $group_entity)
									{
										$mc->getInfo($group_entity);
										if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $mc->visible == 2) continue;

										$this->resprints.= '<span class="multicompany-entity-container">';
										if ($mc->visible == 2) {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fas fa-clone multicompany-button-template" title="'.$langs->trans("TemplateOfEntity").'"></span>';
										} else {
											$this->resprints.= '<span id="template_' . $mc->id . '" class="fas fa-globe multicompany-button-template" title="'.$langs->trans("Entity").'"></span>';
										}
										$this->resprints.= $mc->label . (empty($mc->active) ? ' ('.$langs->trans('Disabled').')' : ($mc->visible == 2 ? ' ('.$langs->trans('Template').')' : (empty($mc->visible) ? ' ('.$langs->trans('Hidden').')' : '')) );
										if (empty($user->entity)) {
											$this->resprints.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$useringroup->id.'&amp;entity='.$group_entity.'">';
											$this->resprints.= img_picto($langs->trans("RemoveFromGroup"), 'unlink');
											$this->resprints.= '</a>';
										}
										$this->resprints.= '</span>';
									}
								}
								$this->resprints.= '</td>';
							}
							$this->resprints.= '<td align="center">'.$useringroup->getLibStatut(3).'</td>';
							$this->resprints.= '<td align="right">';
							$this->resprints.= "-";
							$this->resprints.= "</td></tr>\n";
						}
					}
					else
					{
						$this->resprints.= '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
					}
					$this->resprints.= "</table>";

					$this->resprints.= '<!-- END multicompany formAddUserToGroup -->'."\n";

					return 1;
				}
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function moreHtmlRef($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		$this->resprints = "\n".'<!-- BEGIN multicompany moreHtmlRef -->'."\n";

		// if global sharings is enabled
		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
		    $this->getInfo(! empty($object->entity) ? $object->entity : $conf->entity);

			// if third party sharing is enabled (is mandatory for some sharings)
			if (! empty($conf->societe->enabled) && ! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if ($object->element == 'societe')
				{
					if (in_array('thirdpartycard', $currentcontext))
					{
						if ($action != 'create' && $action != 'edit')
						{
							if ($object->isObjectUsed($object->id) === 0 && ((! empty($user->admin) && ! $user->entity) || ! empty($user->rights->multicompany->thirdparty->write)))
							{
								$selectEntities = $this->getModifyEntityDialog('thirdparty', 'modifyEntity', $object);

								if (! empty($selectEntities)) {
									$this->resprints.= '<div id="modify-entity-thirdparty" class="refidno modify-entity multicompany-entity-card-container" data-tooltip="'.$langs->trans('ThirdpartyModifyEntity').'" data-tooltip-position="bottom">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
									$this->resprints.= '</div>';
									$this->resprints.= $selectEntities;
								} else {
									$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("NoOtherEntityAvailable").'" data-tooltip-position="bottom">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
									$this->resprints.= '</div>';
								}
							}
							else
							{
								$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("ModifyEntityNotAllowed").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
								$this->resprints.= '</div>';
							}
						}
					}
					else
					{
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= '</div>';
					}
				}
				else if ($object->element == 'contact')
				{
					if (in_array('contactcard', $currentcontext))
					{
						if ($action != 'create' && $action != 'edit')
						{
							if (empty($object->socid) && ((! empty($user->admin) && ! $user->entity) || ! empty($user->rights->multicompany->contact->write)))
							{
								$selectEntities = $this->getModifyEntityDialog('contact', 'modifyEntity', $object);

								if (! empty($selectEntities)) {
									$this->resprints.= '<div id="modify-entity-contact" class="refidno modify-entity multicompany-entity-card-container" data-tooltip="'.$langs->trans('ContactModifyEntity').'" data-tooltip-position="bottom">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
									$this->resprints.= '</div>';
									$this->resprints.= $selectEntities;
								} else {
									$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("NoOtherEntityAvailable").'" data-tooltip-position="bottom">';
									$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
									$this->resprints.= '</div>';
								}
							}
							else
							{
								$this->resprints.= '<div class="refidno modify-entity-disabled multicompany-entity-card-container" data-tooltip="'.$langs->trans("ModifyEntityNotAllowed").'" data-tooltip-position="bottom">';
								$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
								$this->resprints.= '</div>';
							}
						}
					}
					else
					{
						$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
						$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
						$this->resprints.= '</div>';
					}
				}
			}

			if (((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && $object->element == 'product' && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
				|| (! empty($conf->propal->enabled) && $object->element == 'propal' && in_array('propalcard', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				|| (! empty($conf->commande->enabled) && $object->element == 'commande' && in_array('ordercard', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				|| (! empty($conf->facture->enabled) && $object->element == 'facture' && in_array('invoicecard', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
			    || (! empty($conf->stock->enabled) && $object->element == 'stock' && in_array('warehousecard', $currentcontext) && ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && ! empty($mc->sharings['stock']))
			)
			{
				$this->resprints.= '<div class="refidno multicompany-entity-card-container">';
				$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
				$this->resprints.= '</div>';
			}
		}

		if ($object->element == 'user' || $object->element == 'usergroup')
		{
			if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && ! empty($user->admin) && empty($user->entity))
			{
				$this->getInfo($object->entity);
				$this->resprints.= '<br><div class="refidno multicompany-entity-card-container">';
				$this->resprints.= '<span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$this->label.'</span>';
				$this->resprints.= '</div>';
			}
		}

		$this->resprints.= '<!-- END multicompany moreHtmlRef -->'."\n";

		return 0;
	}

	/**
	 *
	 */
	public function moreHtmlStatus($parameters=false, &$object, &$action='')
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		//$this->resprints.= 'OK';

		return 0;
	}

	/**
	 *
	 */
	public function printUserListWhere($parameters=false)
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$this->resprints = '';

		if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			if (! empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
				$this->resprints.= " WHERE u.entity IS NOT NULL"; // Show all users
			} else {
				$this->resprints.= ",".MAIN_DB_PREFIX."usergroup_user as ug";
				$this->resprints.= " WHERE ((ug.fk_user = u.rowid";
				$this->resprints.= " AND ug.entity IN (".getEntity('usergroup')."))";
				$this->resprints.= " OR u.entity = 0)"; // Show always superadmin
			}
			return 1;
		}

		return 0;
	}

	/**
	 *
	 * @return number
	 */
	public function addMoreMassActions($parameters=false)
	{
		global $conf, $user, $langs;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			// name="massaction"
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext))
				{
					$langs->load('multicompany@multicompany');
					$this->resprints = '<option value="modify_entity" disabled="disabled">'.$langs->trans('ModifyEntity').'</option>';
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @return number
	 */
	public function printFieldListSelect($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			// Thirdparty sharing is mandatory to share document (propal, etc...)
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext))
				{
					//if (! empty($arrayfields['s.entity']['checked']))
					{
						$this->resprints = ", s.entity";
					}
				}
				else if (in_array('contactlist', $currentcontext))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", p.entity";
					}
				}
				else if (in_array('propallist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", p.entity";
					}
				}
				else if (in_array('orderlist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", c.entity";
					}
				}
				else if (in_array('invoicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = ", f.entity";
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = ", p.entity";
				}
			}
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListWhere($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext))
				{
					//if (! empty($arrayfields['s.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						if ($search_entity > 0)
						{
							$this->resprints = " AND s.entity = " . $search_entity;
						}
					}
				}
				else if (in_array('contactlist', $currentcontext))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						if ($search_entity > 0)
						{
							$this->resprints = " AND p.entity = " . $search_entity;
						}
					}
				}
				else if (in_array('propallist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						if ($search_entity > 0)
						{
							$this->resprints = " AND p.entity = " . $search_entity;
						}
					}
				}
				else if (in_array('orderlist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						if ($search_entity > 0)
						{
							$this->resprints = " AND c.entity = " . $search_entity;
						}
					}
				}
				else if (in_array('invoicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
				{
					//if (! empty($arrayfields['f.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						if ($search_entity > 0)
						{
							$this->resprints = " AND f.entity = " . $search_entity;
						}
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$search_entity = GETPOST('search_entity','int');

					if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
					{
						$search_entity = '';
					}

					if ($search_entity > 0)
					{
						$this->resprints = " AND p.entity = " . $search_entity;
					}
				}
            }
            if (in_array('stocklist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && ! empty($mc->sharings['stock']))
            {
                //if (! empty($arrayfields['p.entity']['checked']))
                {
                    $search_entity = GETPOST('search_entity','int');

                    if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
                    {
                        $search_entity = '';
                    }

                    if ($search_entity > 0)
                    {
                        $this->resprints = " AND t.entity = " . $search_entity;
                    }
                }
            }
        }

        return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListOption($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext))
				{
					//if (! empty($arrayfields['s.entity']['checked']) || ! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['thirdparty']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				}
				else if (in_array('propallist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['proposal']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				}
				else if (in_array('orderlist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['order']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				}
				else if (in_array('invoicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
				{
					//if (! empty($arrayfields['f.entity']['checked']))
					{
						$search_entity = GETPOST('search_entity','int');

						if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
						{
							$search_entity = '';
						}

						// Entity
						$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
						$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['invoice']),'','minwidth100imp');
						$this->resprints.= '</td>';
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$search_entity = GETPOST('search_entity','int');

					if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
					{
						$search_entity = '';
					}

					// Entity
					$this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
					$this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['product']),'','minwidth100imp');
					$this->resprints.= '</td>';
				}
            }
            if (in_array('stocklist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && ! empty($mc->sharings['stock']))
            {
                //if (! empty($arrayfields['p.entity']['checked']))
                {
                    $search_entity = GETPOST('search_entity','int');

                    if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
                    {
                        $search_entity = '';
                    }

                    // Entity
                    $this->resprints = '<td class="liste_titre maxwidthonsmartphone" align="center">';
                    $this->resprints.= $this->select_entities($search_entity,'search_entity','',false,false,true,explode(",", $mc->entities['stock']),'','minwidth100imp');
                    $this->resprints.= '</td>';
                }
            }
		}

		return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListTitle($parameters=false)
	{
		global $conf;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext))
				{
					//if (! empty($arrayfields['s.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"s.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
				else if (in_array('contactlist', $currentcontext))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
				else if (in_array('propallist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
				else if (in_array('orderlist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"c.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
				else if (in_array('invoicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
				{
					//if (! empty($arrayfields['f.entity']['checked']))
					{
						$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"f.entity","",$param,'align="center"',$sortfield,$sortorder);
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"p.entity","",$param,'align="center"',$sortfield,$sortorder);
				}
			}

			if (in_array('stocklist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && ! empty($mc->sharings['stock']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$this->resprints = getTitleFieldOfList('Entity',0,$_SERVER["PHP_SELF"],"t.entity","",$param,'align="center"',$sortfield,$sortorder);
				}
			}
		}

        return 0;
	}

	/**
	 *
	 * @param boolean $parameters
	 * @return number
	 */
	public function printFieldListValue($parameters=false)
	{
		global $conf;
		global $totalarray;
		global $mc;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			if (! empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) && ! empty($mc->sharings['thirdparty']))
			{
				if (in_array('thirdpartylist', $currentcontext) || in_array('contactlist', $currentcontext))
				{
					//if (! empty($arrayfields['s.entity']['checked']) || ! empty($arrayfields['p.entity']['checked']))
					{
						$this->getInfo($obj->entity);
						$this->resprints = '<td align="center">'.$this->label."</td>\n";
					}
				}
				else if (in_array('propallist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PROPOSAL_SHARING_ENABLED) && ! empty($mc->sharings['proposal']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->getInfo($obj->entity);
						$this->resprints = '<td align="center">'.$this->label."</td>\n";
						if (! $i) $totalarray['nbfield']++;
					}
				}
				else if (in_array('orderlist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_ORDER_SHARING_ENABLED) && ! empty($mc->sharings['order']))
				{
					//if (! empty($arrayfields['p.entity']['checked']))
					{
						$this->getInfo($obj->entity);
						$this->resprints = '<td align="center">'.$this->label."</td>\n";
						if (! $i) $totalarray['nbfield']++;
					}
				}
				else if (in_array('invoicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED) && ! empty($mc->sharings['invoice']))
				{
					//if (! empty($arrayfields['f.entity']['checked']))
					{
						$this->getInfo($obj->entity);
						$this->resprints = '<td align="center">'.$this->label."</td>\n";
						if (! $i) $totalarray['nbfield']++;
					}
				}
			}

			if (in_array('productservicelist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) && ! empty($mc->sharings['product']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$this->getInfo($obj->entity);
					$this->resprints = '<td align="center">'.$this->label."</td>\n";
				}
			}

			if (in_array('stocklist', $currentcontext) && ! empty($conf->global->MULTICOMPANY_STOCK_SHARING_ENABLED) && ! empty($mc->sharings['stock']))
			{
				//if (! empty($arrayfields['p.entity']['checked']))
				{
					$this->getInfo($obj->entity);
					$this->resprints = '<td align="center">'.$this->label."</td>\n";
				}
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function insertExtraHeader($parameters=false, &$object, &$action='')
	{
		global $conf, $user, $langs;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($user->admin) && empty($user->entity) && $conf->entity == 1 && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			if (in_array('userperms', $currentcontext) || in_array('groupperms', $currentcontext))
			{
				$this->getInstanceDao();

				if ($object->element == 'user')
				{
					$aEntities=array_keys($permsgroupbyentity);

					// Check usergroup if user not in master entity
					if (empty($aEntities) || ! array_key_exists(1, $permsgroupbyentity))
					{
						require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
						$aEntities=array();
						$group = new UserGroup($this->db);
						$ret = $group->listGroupsForUser($object->id, false);
						if (! empty($ret)) {
							foreach($ret as $groupid => $data)
							{
								$aEntities = array_merge($aEntities, $data->usergroup_entity);
							}
							sort($aEntities);
						}
					}

					if (! empty($aEntities))
					{
						$entity = (GETPOST('entity', 'int')?GETPOST('entity', 'int'):$aEntities[0]);
						$head = entity_prepare_head($object, $aEntities);
						$title = $langs->trans("Entities");
						dol_fiche_head($head, $entity, $title, 1, 'multicompany@multicompany');
					}
					else
					{
						print get_htmloutput_mesg(img_warning('default') . ' ' . $langs->trans("ErrorLinkUserGroupEntity"), '', 'mc-upgrade-alert', 1);
					}
				}
				else if ($object->element == 'usergroup')
				{
					$this->dao->getEntities();
					$aEntities=array();

					foreach ($this->dao->entities as $objEntity)
					{
						$aEntities[] = $objEntity->id;
					}

					$entity = (GETPOST('entity', 'int')?GETPOST('entity', 'int'):$aEntities[0]);
					$head = entity_prepare_head($object, $aEntities);
					$title = $langs->trans("Entities");
					dol_fiche_head($head, $entity, $title, 1, 'multicompany@multicompany');

					if (! empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT)) {
						print '
							<div id="dialog-duplicate" title="'.$langs->trans('DuplicateRights').'" class="hideobject">
								<p>'.img_info().' '.$langs->transnoentities('DuplicateRightsInfo').'</p>
								<p>'.$langs->transnoentities('SelectRightsOfEntityToDuplicate').' '.$this->select_entities('', 'template', '', false, false, false, false, '', 'minwidth200imp', true, true).'</p>
								<p>'.$langs->transnoentities('SelectEntitiesToOverride').'</p>
								<p><div class="bootstrap-iso">
									<div class="row">
							           	<div class="col-sm-5">
							           		<div class="multiselect-selected-title"><span class="fa fa-globe"></span><span class="multiselect-selected-title-text">'.$langs->transnoentities("EntitiesSelected").'</span></div>
							           		'.$this->multiselectEntitiesToOverride('overrideentities', true).'
							           	</div>
										<div class="col-xs-2 multiselect-menu">
											<!-- <button type="button" id="multiselect_overrideentities_undo" class="btn btn-primary btn-block">'.$langs->trans("Undo").'</button> -->
											<button type="button" id="multiselect_overrideentities_leftAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-backward"></i></button>
											<button type="button" id="multiselect_overrideentities_leftSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-left"></i></button>
											<button type="button" id="multiselect_overrideentities_rightSelected" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-chevron-right"></i></button>
											<button type="button" id="multiselect_overrideentities_rightAll" class="btn btn-block multiselect-menu-btn-color"><i class="glyphicon glyphicon-forward"></i></button>
											<!-- <button type="button" id="multiselect_overrideentities_redo" class="btn btn-warning btn-block">'.$langs->trans("Redo").'</button> -->
										</div>
										<div class="col-xs-5">
											<div class="multiselect-available-title"><span class="fa fa-globe"></span><span class="multiselect-available-title-text">'.$langs->transnoentities("EntitiesAvailable").'</span></div>
											'.$this->multiselectEntitiesToOverride('overrideentities', false).'
										</div>
									</div>
								</div></p>
							</div>
							<script type="text/javascript">
							(function(){
								var newlink = document.createElement("link");
								newlink.type = "text/css";
								newlink.rel = "stylesheet";
								newlink.href = "'.dol_buildpath("/multicompany/inc/multiselect/css/bootstrap-iso.min.css", 1).'";
			  					(document.getElementsByTagName("head")[0]||document.getElementsByTagName("body")[0]).appendChild(newlink);
								var newscript = document.createElement("script");
								newscript.type = "text/javascript";
								newscript.async = true;
								newscript.src = "'.dol_buildpath("/multicompany/inc/multiselect/js/multiselect.min.js", 1).'";
			  					(document.getElementsByTagName("head")[0]||document.getElementsByTagName("body")[0]).appendChild(newscript);
							})();
							$(document).ready(function() {
								$( "span.multicompany-button-clonerights" ).parent().css( "background", "none" ).css( "border", "none" );
								$( "span.multicompany-button-clonerights" ).parent().parent().css( "float", "right" ).css( "padding-top", "15px" );
								$(".fichecenter").on("click", "#clonerights", function() {
									$("#dialog-duplicate").dialog({
										resizable: false,
										height: 450,
										width: 700,
										modal: true,
										open: function() {
											$(".ui-dialog-buttonset > button:first").button("disable");
											$(".ui-dialog-buttonset > button:last").focus();
										},
										buttons: {
											"'.$langs->transnoentities('Validate').'": function() {
												$(this).dialog("close");
												var selections = [];
												$("#multiselect_overrideentities_to option").each(function() {
													selections.push( $(this).val() );
												});
												//console.log(selections);
												$.post( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
													"action": "duplicateUserGroupRights",
													"id": "'.$object->id.'",
													"template": $("#template").val(),
													"entities": JSON.stringify(selections)
													},
													function (result) {
														if (result.status == "success") {
															$.jnotify("'.$langs->transnoentities("ConfirmedDuplicateRights").'", "ok");
														} else {
															$.jnotify("'.$langs->transnoentities("ErrorDuplicateRights").'", "error", true);
														}
													}
												);
											},
											"'.$langs->transnoentities('Cancel').'": function() {
												$(this).dialog("close");
											}
										}
									});
								});
								$("#multiselect_overrideentities").multiselect({
									keepRenderingSort: true,
									right: "#multiselect_to_overrideentities",
							        rightAll: "#multiselect_overrideentities_leftAll",
							        rightSelected: "#multiselect_overrideentities_leftSelected",
							        leftSelected: "#multiselect_overrideentities_rightSelected",
							        leftAll: "#multiselect_overrideentities_rightAll",
							        search: {
							            left: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->transnoentities("Search")."...".'" />\',
							            right: \'<input type="text" name="q" class="form-control" placeholder="'.$langs->transnoentities("Search")."...".'" />\',
							        },
							        fireSearch: function(value) {
							            return value.length > 2;
							        },
							        afterMoveToRight: function($left, $right, $options) {
										$(".ui-dialog-buttonset > button:first").button("enable");
										$("#multiselect_overrideentities").html( $("#multiselect_overrideentities option").sort(function(x, y) {
								            return $(x).val() < $(y).val() ? -1 : 1;
								        }));
									},
									afterMoveToLeft: function($left, $right, $options) {
										if ($("#multiselect_overrideentities_to option").length == 0) {
											$(".ui-dialog-buttonset > button:first").button("disable");
										}
										$("#multiselect_overrideentities").html( $("#multiselect_overrideentities option").sort(function(x, y) {
								            return $(x).val() < $(y).val() ? -1 : 1;
								        }));
									}
								});
							});
							</script>
						';
					}
				}

				// Check if advanced perms is enabled for current object entity
				$res = $this->dao->getEntityConfig($entity, 'MAIN_USE_ADVANCED_PERMS');
				if (empty($res['MAIN_USE_ADVANCED_PERMS'])) {
					unset($conf->global->MAIN_USE_ADVANCED_PERMS);
				}
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function insertExtraFooter($parameters=false, &$object, &$action='')
	{
		global $conf, $user;

		if (empty($conf->multicompany->enabled)) return 0;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$$key=$value;
			}
		}

		$currentcontext = explode(':', $parameters['context']);

		if (! empty($user->admin) && empty($user->entity) && $conf->entity == 1 && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			if (in_array('userperms', $currentcontext) || in_array('groupperms', $currentcontext))
			{
				// Restore advanced perms if enabled for current entity
				$this->getInstanceDao();
				$res = $this->dao->getEntityConfig($conf->entity, 'MAIN_USE_ADVANCED_PERMS');
				if (! empty($res['MAIN_USE_ADVANCED_PERMS'])) {
					$conf->global->MAIN_USE_ADVANCED_PERMS = $res['MAIN_USE_ADVANCED_PERMS'];
				}
			}
		}

		return 0;
	}

	/**
	 *	Return combo list of entities.
	 *
	 *	@param	int		$selected	Preselected entity
	 *	@param	int		$htmlname	Name
	 *	@param	string	$option		Option
	 *	@param	boolean	$login		If use in login page or not
	 *  @param	boolean $exclude	Exclude
	 *  @param	boolean	$emptyvalue Emptyvalue
	 *  @param	boolean	$only		Only
	 *  @param	string	$all		Add 'All entities' value in combo list
	 *  @param	string	$cssclass	specific css class. eg 'minwidth150imp mycssclass'
	 *  @param	bool	$ajax		Enable ajax combobox
	 *  @param	bool	$template	Show template of entities
	 *	@return	string
	 */
	public function select_entities($selected = '', $htmlname = 'entity', $option = '', $login = false, $exclude = false, $emptyvalue = false, $only = false, $all = '', $cssclass = 'minwidth150imp', $ajax = true, $template = false)
	{
		global $conf, $user, $langs;

		$this->getInstanceDao();

		$this->dao->getEntities($login, $exclude);

		$out = '';

		if (is_array($this->dao->entities) && ! empty($this->dao->entities))
		{
			$out.= '<select class="flat maxwidth200 multicompany_select '.$cssclass.'" id="'.$htmlname.'" name="'.$htmlname.'"'.$option.'>';

			if ($emptyvalue)
				$out.= '<option value="-1">&nbsp;</option>';

			if ($all)
				$out.= '<option value="0">'.$langs->trans("AllEntities").'</option>';

			foreach ($this->dao->entities as $entity)
			{
				if (empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT) && $entity->visible == 2) continue;
				if ($template === 'only' && $entity->visible != 2) continue;
				if (! $template && $entity->visible == 2) continue;

				if ($entity->active == 1 && ($entity->visible == 1 || ($user->admin && ! $user->entity)))
				{
					if (is_array($only) && ! empty($only) && ! in_array($entity->id, $only)) continue;
					if (! empty($user->login) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && ! empty($user->entity) && $this->checkRight($user->id, $entity->id) < 0) continue;

					$out.= '<option value="'.$entity->id.'"';
					if ($selected == $entity->id) {
						$out.= ' selected="selected"';
					}
					$out.= '>';
					$out.= $entity->label;
					if (empty($entity->visible)) {
						$out.= ' ('.$langs->trans('Hidden').')';
					} elseif ($entity->visible == 2) {
						$out.= ' ('.$langs->trans('Template').')';
					}
					$out.= '</option>';
				}
			}

			$out.= '</select>';

			// Make select dynamic
			if ($ajax) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out.= ajax_combobox($htmlname);
			}
		}

		return $out;
	}

	/**
	 *	Return multiselect list of entities.
	 *
	 *	@param	string			$htmlname			Name of select
	 *	@param	DaoMulticompany	$current			Current entity to manage
	 *	@param	bool			$onlyselected		true: show only selected, false: hide only selected
	 *	@return	string
	 */
	public function multiselect_entities($htmlname, $current, $onlyselected=false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$selectname = ($onlyselected ? $htmlname.'_to[]' : 'from[]');
		$selectid = ($onlyselected ? 'multiselect_shared_'.$htmlname.'_to' : 'multiselect_shared_'.$htmlname);

		$return = '<select name="'.$selectname.'" id="'.$selectid.'" class="form-control multiselect-select" size="6" multiple="multiple">';
		if (is_array($this->dao->entities))
		{
			foreach ($this->dao->entities as $entity)
			{
				if ($entity->visible == 2) continue;
				if (is_object($current) && $current->id != $entity->id && $entity->active == 1)
				{
					if ((! $onlyselected && (empty($current->options['sharings'][$htmlname]) || ! in_array($entity->id, $current->options['sharings'][$htmlname])))		// All unselected
						|| ($onlyselected && is_array($current->options['sharings'][$htmlname]) && in_array($entity->id, $current->options['sharings'][$htmlname])))	// All selected
					{
						$return.= '<option class="oddeven multiselect-option" value="'.$entity->id.'">';
						$return.= $entity->label;
						if (empty($entity->visible))
						{
							$return.= ' ('.$langs->trans('Hidden').')';
						}
						$return.= '</option>';
					}
				}
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *	Return multiselect list of entities to override.
	 *
	 *	@param	string			$htmlname			Name of select
	 *	@param	bool			$onlyselected		true: show only selected, false: hide only selected
	 *	@return	string
	 */
	public function multiselectEntitiesToOverride($htmlname, $onlyselected=false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$selectname = ($onlyselected ? $htmlname.'_to[]' : 'from[]');
		$selectid = ($onlyselected ? 'multiselect_'.$htmlname.'_to' : 'multiselect_'.$htmlname);

		$out = '<select name="'.$selectname.'" id="'.$selectid.'" class="form-control multiselect-select" size="6" multiple="multiple">';
		if (!$onlyselected && is_array($this->dao->entities))
		{
			foreach ($this->dao->entities as $entity)
			{
				if ($entity->active == 1)
				{
					$out.= '<option class="oddeven multiselect-option" value="'.$entity->id.'">';
					$out.= $entity->label;
					if (empty($entity->visible)) {
						$out.= ' ('.$langs->trans('Hidden').')';
					} elseif ($entity->visible == 2) {
						$out.= ' ('.$langs->trans('Template').')';
					}
					$out.= '</option>';
				}
			}
		}
		$out.= '</select>';

		return $out;
	}

	/**
	 *	Return multiselect list of entities.
	 *
	 *	@param	string	$htmlname	Name of select
	 *	@param	array	$selected	Entities already selected
	 *	@param	string	$option		Option
	 *	@return	string
	 */
	public function multiSelectEntities($htmlname, $selected=null, $option=null)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities();

		$return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
		if (is_array($this->dao->entities))
		{
			foreach ($this->dao->entities as $entity)
			{
				if ($entity->visible == 2) continue;

				$return.= '<option value="'.$entity->id.'" ';
				if (is_array($selected) && in_array($entity->id, $selected))
				{
					$return.= 'selected="selected"';
				}
				$return.= '>';
				$return.= $entity->label;
				if (empty($entity->visible))
				{
					$return.= ' ('.$langs->trans('Hidden').')';
				}
				$return.= '</option>';
			}
		}
		$return.= '</select>';

		return $return;
	}

	/**
	 *    Switch to another entity.
	 *
	 *    @param int $id        User id
	 *    @param int $entity    Entity id
	 *    @return int
	 */
	public function checkRight($id, $entity)
	{
		global $user;

		$this->getInstanceDao();

		if ($this->dao->fetch($entity) > 0)
		{
			// Controle des droits sur le changement
			if ($this->dao->verifyRight($entity, $id) || $user->admin)
			{
				return 1;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 *    Switch to another entity.
	 *    @param    int $id Id of the destination entity
	 *    @param    int $userid
	 *    @return int
	 */
	public function switchEntity($id, $userid=null)
	{
		global $conf, $user;

		$this->getInstanceDao();

		if (!empty($userid))
		{
			$user=new User($this->db);
			$user->fetch($userid);
		}

		if ($this->dao->fetch($id) > 0 && ! empty($this->dao->active)) // check if the entity is still active
		{
			// Controle des droits sur le changement
			if (!empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX)
			|| (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $this->dao->verifyRight($id, $user->id))
			|| $user->admin)
			{
				$_SESSION['dol_entity'] = $id;
				//$conf = new Conf(); FIXME some constants disappear
				$conf->entity = $id;
				$conf->setValues($this->db);
				return 1;
			}
			else
			{
				//var_dump($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX);
				//var_dump($conf->global->MULTICOMPANY_TRANSVERSE_MODE);
				//var_dump($this->dao->verifyRight($id, $user->id));
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}

	/**
	 * 	Get entity info
	 * 	@param	int $id	Object id
	 */
	public function getInfo($id)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id);

		$this->id				= $this->dao->id;
		$this->label			= $this->dao->label;
		$this->country_id		= $this->dao->country_id;
		$this->country_code		= $this->dao->country_code;
		$this->currency_code	= $this->dao->currency_code;
		$this->language_code	= $this->dao->language_code;
		$this->description		= $this->dao->description;
		$this->options			= $this->dao->options;
		$this->active			= $this->dao->active;
		$this->visible			= $this->dao->visible;
	}

	/**
	 *    Get action title
	 *    @param string $action Type of action
	 *    @return string
	 */
	public function getTitle($action='')
	{
		global $langs;

		if ($action == 'create') return $langs->trans("AddEntity");
		else if ($action == 'edit') return $langs->trans("EditEntity");
		else return $langs->trans("EntitiesManagement");
	}


	/**
	 *    Assigne les valeurs pour les templates
	 *    @param string $action     Type of action
	 */
	public function assign_values($action='view')
	{
		global $conf, $langs, $user;
		global $form, $formcompany, $formadmin;

		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

		$this->tpl['extrafields'] = new ExtraFields($this->db);
		// fetch optionals attributes and labels
		$this->tpl['extralabels'] = $this->tpl['extrafields']->fetch_name_optionals_label('entity');

		$this->getInstanceDao();

		$this->template_dir = dol_buildpath('/multicompany/admin/tpl/');
		$this->template = 'list.tpl.php';

		if ($action == 'create' || $action == 'edit')
		{
			$this->template = 'card.tpl.php';

			if ($action == 'edit' && GETPOSTISSET('id')) {
				$ret = $this->dao->fetch(GETPOST('id', 'int'));
			}

			if (! empty($conf->global->MULTICOMPANY_TEMPLATE_MANAGEMENT))
			{
				$templatevalue = (GETPOSTISSET('template') ? GETPOST('template', 'int') : ($this->dao->visible === '2' ? 1 : 0));

				if ($action == 'create') {
					$this->tpl['template'] = $form->selectyesno('template', $templatevalue, 1);
					$this->tpl['select_template'] = $this->select_entities('','usetemplate','',false,false,true,'','','minwidth200imp',true,'only');
				} elseif ($templatevalue === 1) {
					$this->tpl['template'] = $templatevalue;
				}
			}

			// action
			$this->tpl['action'] = $action;

			// id
			$this->tpl['id'] = (GETPOSTISSET('id')?GETPOST('id', 'int'):null);

			// Label
			$this->tpl['label'] = (GETPOSTISSET('label')?GETPOST('label', 'alpha'):$this->dao->label);

			// Description
			$this->tpl['description'] = (GETPOSTISSET('description')?GETPOST('description', 'alpha'):$this->dao->description);

			// Company name
			$this->tpl['name'] = (GETPOSTISSET('name')?GETPOST('name', 'alpha'):$this->dao->name);

			// Address
			$this->tpl['address'] = (GETPOSTISSET('address')?GETPOST('address', 'alpha'):$this->dao->address);

			// Zip
            $this->tpl['select_zip'] = $formcompany->select_ziptown((GETPOSTISSET('zipcode')?GETPOST('zipcode', 'alpha'):$this->dao->zip),'zipcode',array('town','selectcountry_id','departement_id'),6);

            // Town
            $this->tpl['select_town'] = $formcompany->select_ziptown((GETPOSTISSET('town')?GETPOST('town', 'alpha'):$this->dao->town),'town',array('zipcode','selectcountry_id','departement_id'),40);

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);


			// We define country_id
			if (GETPOSTISSET('country_id'))
			{
				$country_id = GETPOST('country_id', 'int');
			}
			else if (! empty($this->dao->country_id))
			{
				$country_id = $this->dao->country_id;
			}
			else if (! empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY))
			{
				$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
				$country_id = $tmp[0];
			}
			else
			{
				$country_id = 0;
			}

			$this->tpl['select_country']	= $form->select_country($country_id,'country_id');
			$this->tpl['select_state']		= $formcompany->select_state((GETPOSTISSET('departement_id')?GETPOST('departement_id', 'int'):$this->dao->state_id),$country_id,'departement_id');
			$this->tpl['select_currency']	= $form->selectCurrency((GETPOSTISSET('currency_code')?GETPOST('currency_code', 'alpha'):($this->dao->currency_code?$this->dao->currency_code:$conf->currency)),"currency_code");
			$this->tpl['select_language']	= $formadmin->select_language((GETPOSTISSET('main_lang_default')?GETPOST('main_lang_default', 'alpha'):($this->dao->language_code?$this->dao->language_code:$conf->global->MAIN_LANG_DEFAULT)),'main_lang_default',1);

			if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED)) {
				foreach ($this->sharingelements as $element => $params) {
					if ((($params['object'] || $params['objectnumber']) && ! isset($params['disable'])) && (empty($conf->societe->enabled) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))) continue;
					$uppername = strtoupper($element);
					$constname = 'MULTICOMPANY_' . $uppername . '_SHARING_ENABLED';
					if (! empty($conf->global->$constname)) {
						$this->tpl['multiselect_from_' . $element]	= $this->multiselect_entities($element, $this->dao, false);
						$this->tpl['multiselect_to_' . $element]	= $this->multiselect_entities($element, $this->dao, true);
						if ($element == 'proposalnumber' || $element == 'invoicenumber') {
							$this->tpl['select_'.$element.'_entity'] = $this->select_entities($this->dao->options[$element.'_referent'], $element.'_referring_entity');
						}
						$addtoallother = (GETPOSTISSET('addtoallother_' . $element) ? GETPOST('addtoallother_' . $element, 'int') : $this->dao->options['addtoallother'][$element]);
						$this->tpl['addtoallother_' . $element] = $form->selectyesno('addtoallother_' . $element, $addtoallother, 1);
					}
				}
			}
		}
	}

	/**
	 *    Display the template
	 */
	public function display()
	{
		global $conf, $langs;
		global $form, $object;

		include $this->template_dir.$this->template;
	}

	/**
	 * 	Set values of global conf for multicompany
	 *
	 * 	@param	Conf		$conf	Object conf
	 * 	@return void
	 */
	public function setValues(&$conf)
	{
		if (! empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		{
			$this->getInstanceDao();
			$this->dao->fetch($conf->entity);

			$this->sharings					= $this->dao->options['sharings'];
			//$this->referent					= $this->dao->options['referent'];
			$this->proposalnumber_referent	= (isset($this->dao->options['proposalnumber_referent'])?$this->dao->options['proposalnumber_referent']:'');
			$this->invoicenumber_referent	= (isset($this->dao->options['invoicenumber_referent'])?$this->dao->options['invoicenumber_referent']:'');

			// Load shared elements
			$this->loadSharedElements();

			// Define output dir for others entities
			$this->setMultiOutputDir($conf);
		}

		if (! empty($this->sharingdicts))
		{
			foreach($this->sharingdicts as $dict => $data)
			{
				$constname = 'MULTICOMPANY_'.strtoupper($dict).'_SHARING_DISABLED';
				if (! empty($conf->global->$constname)) {
					$this->dict[$dict] = true;
				}
			}
		}
	}

	/**
	 *	Set status of an entity
	 *
	 *	@param	int		$id			Id of entity
	 *	@param	string	$type		Type of status (visible or active)
	 *	@param	string	$value		Value of status (0: disable, 1: enable)
	 *	@return	int
	 */
	public function setStatus($id, $type='active', $value)
	{
		global $user;

		if (! empty($user->admin) && ! $user->entity) {
			$this->getInstanceDao();
			return $this->dao->setEntity($id, $type, $value);
		}
		else {
			return -1;
		}
	}

	/**
	 *	Delete an entity
	 *
	 *	@param	int	$id		Id of entity
	 *	@return	int
	 */
	public function deleteEntity($id)
	{
		global $user;

		if (! empty($user->admin) && ! $user->entity && $id != 1) {
			$this->getInstanceDao();
			return $this->dao->delete($id);
		}
		else {
			return -1;
		}
	}

	/**
	 * 	Get list of entity id to use.
	 *
	 * 	@param	string	$element		Current element
	 *									'societe', 'socpeople', 'actioncomm', 'agenda', 'resource',
	 *									'product', 'productprice', 'stock',
	 *									'propal', 'supplier_proposal', 'invoice', 'facture_fourn', 'payment_various',
	 *									'categorie', 'bank_account', 'bank_account', 'adherent', 'user',
	 *									'commande', 'commande_fournisseur', 'expedition', 'intervention', 'survey',
	 *									'contract', 'tax', 'expensereport', 'holiday', 'multicurrency', 'project',
	 *									'email_template', 'event', 'donation'
	 *									'c_paiement', 'c_payment_term', ...
	 * 	@param	int		$shared			0=Return id of current entity only,
	 * 									1=Return id of current entity + shared entities (default)
	 *  @param	object	$currentobject	Current object if needed
	 * 	@return	mixed					Entity id(s) to use ( eg. entity IN ('.getEntity(elementname).')' )
	 */
	public function getEntity($element=false, $shared=1, $currentobject=null)
	{
		global $conf, $user;

		$element = str_replace(MAIN_DB_PREFIX, '', $element);

		if (in_array($element, $this->addzero))
		{
			if ($element == 'user' && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				return '0,1';				// In transverse mode all users except superadmin and groups are in entity 1
			} else {
				if ($element == 'usergroup' && $conf->entity == 1 && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && !empty($user->admin) && empty($user->entity))
				{
					return '0,'.implode(',', array_keys($this->getEntitiesList(true, false, true))); // Superadmin can always edit groups of another entities
				}

				return '0,'.$conf->entity;
			}
		}

		// Sharing dictionnaries
		if (array_key_exists($element, $this->sharingdicts))
		{
			if (! empty($this->dict[$element])) {
				return $conf->entity;
			} else {
				return 1; // Master entity
			}
		}

		// Check object entity when number sharing is disabled
		if (is_object($currentobject) &&
			(($element == 'proposalnumber' && empty($conf->global->MULTICOMPANY_PROPOSALNUMBER_SHARING_ENABLED))
			|| ($element == 'ordernumber' && empty($conf->global->MULTICOMPANY_ORDERNUMBER_SHARING_ENABLED))
			|| ($element == 'invoicenumber' && empty($conf->global->MULTICOMPANY_INVOICENUMBER_SHARING_ENABLED))
			)
		)
		{
			// Use object entity ID
			$entity = ((isset($currentobject->entity) && is_numeric($currentobject->entity)) ? $currentobject->entity : $conf->entity);
			return $entity;
		}

		$elementkey = $element;
		if ($element == 'societe' || $element == 'socpeople' || $element == 'contact') {
			$elementkey = 'thirdparty';
		}
		if ($element == 'adherent')			$elementkey = 'member';
		if ($element == 'bank_account')		$elementkey = 'bankaccount';
		if ($element == 'adherent_type')	$elementkey = 'member_type';
		if ($element == 'categorie')		$elementkey	= 'category';
		if ($element == 'propal')			$elementkey = 'proposal';
		if ($element == 'commande')			$elementkey = 'order';
		if ($element == 'facture')			$elementkey = 'invoice';

		if (! empty($element) && ! empty($this->entities[$elementkey]))
		{
			if (! empty($shared))
			{
				return $this->entities[$elementkey];
			}
			else if (! empty($this->sharings['referent']))
			{
				if ($element == 'societe') return $this->sharings['referent'];
			}
		}

		return $conf->entity;
	}

	/**
	 * 	Set entity id to use when to create an object
	 *
	 * 	@param	object	$currentobject	Current object
	 * 	@return	int						Entity id to use
	 */
	public function setEntity($currentobject)
	{
		global $conf;

		$entity = $conf->entity;

		if (is_object($currentobject) && ! empty($currentobject->element))
		{
			$element = array_search($currentobject->element, $this->sharingmodulename);
			$element = (! empty($element) ? $element : $currentobject->element);
			$constname = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';
			$newentity = $entity;

			if (isset($this->sharingelements[$element])
				&& isset($this->sharingelements[$element]['active'])
				&& ! empty($conf->global->$constname)
				)
			{
				if (GETPOSTISSET('entity') && GETPOST('entity', 'int', 2))
				{
					$newentity = GETPOST('entity', 'int', 2);
				}
				else if (isset($currentobject->entity) && is_numeric($currentobject->entity))
				{
					$newentity = $currentobject->entity;
				}

				if (isset($this->sharings[$element]) && in_array($newentity, $this->sharings[$element]))
				{
					$entity = $newentity;
				}
			}
			else
			{
				$entity = (($currentobject->id > 0 && $currentobject->entity > 0) ? $currentobject->entity : $conf->entity);
			}
		}

		return $entity;
	}

	/**
	 * 	Get entities list
	 *
	 *	@param		int		$login			If use in login page or not
	 *	@param		array	$exclude		Entity ids to exclude
	 *	@param		bool	$onlyactive		sort only active entities
	 *	@param		bool	$showtemplate	Show or not templates
	 * 	@return		array					Array of entities (id => label)
	 */
	public function getEntitiesList($login = false, $exclude = false, $onlyactive = false, $showtemplate = false)
	{
		global $langs;

		$this->getInstanceDao();
		$this->dao->getEntities($login, $exclude, $onlyactive);

		$entities=array();

		foreach ($this->dao->entities as $entity)
		{
			if (empty($showtemplate) && $entity->visible == 2) continue;
			$entities[$entity->id] = dol_html_entity_decode($entity->label, null) . (empty($entity->active) ? ' ('.$langs->transnoentities('Disabled').')' : ($entity->visible == 2 ? ' ('.$langs->transnoentities('Template').')' : (empty($entity->visible) ? ' ('.$langs->transnoentities('Hidden').')' : '')) );
		}

		return $entities;
	}

	/**
	 * 	Set object documents directory to use
	 *
	 *	@param	Conf	$conf		Object Conf
	 * 	@return	void
	 */
	public function setMultiOutputDir(&$conf)
	{
		if (! empty($this->entities))
		{
			foreach($this->entities as $element => $shares)
			{
				if ($element == 'thirdparty')	$element = 'societe';
				elseif ($element == 'member')		$element = 'adherent';
				elseif ($element == 'proposal')		$element = 'propal';
				elseif ($element == 'order')		$element = 'commande';
				elseif ($element == 'invoice')		$element = 'facture';
				elseif ($element == 'intervention')	$element = 'ficheinter';

				if (! empty($conf->$element->enabled) && isset($conf->$element->multidir_output) && isset($conf->$element->multidir_temp))
				{
					$elementpath=$element;
					if ($element == 'product')	$elementpath='produit';
					elseif ($element == 'category')	$elementpath='categorie';
					elseif ($element == 'propal')	$elementpath='propale';

					$entities = explode(",", $shares);
					$dir_output = array();
					$dir_temp = array();
					foreach($entities as $entity)
					{
						if (! array_key_exists($entity, $conf->$element->multidir_output))
						{
							$path = ($entity > 1 ? "/".$entity : '');

							$dir_output[$entity] 	= DOL_DATA_ROOT.$path."/".$elementpath;
							$dir_temp[$entity] 		= DOL_DATA_ROOT.$path."/".$elementpath."/temp";

							$conf->$element->multidir_output += $dir_output;
							$conf->$element->multidir_temp += $dir_temp;
						}

						if (in_array($element, array('propal', 'commande', 'facture')) && isset($conf->mycompany->multidir_output))
						{
							if (! array_key_exists($entity, $conf->mycompany->multidir_output))
							{
								$path = ($entity > 1 ? "/".$entity : '');

								$dir_output[$entity] 	= DOL_DATA_ROOT.$path."/mycompany";
								$dir_temp[$entity] 		= DOL_DATA_ROOT.$path."/mycompany/temp";

								$conf->mycompany->multidir_output += $dir_output;
								$conf->mycompany->multidir_temp += $dir_temp;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	public function printTopRightMenu($parameters=false)
	{
		echo $this->getTopRightMenu();

		return 0;
	}

	/**
	 *
	 */
	public function printBugtrackInfo($parameters=false)
	{
		global $conf;

		if (! empty($conf->multicompany->enabled)) {
			$this->resprints = urlencode("- **Multicompany**: " . $conf->global->MULTICOMPANY_MAIN_VERSION . "\n");
		}

		return 0;
	}

	/**
	 * @param bool $parameters
	 * @return int
	 */
	/*public function afterLogin($parameters=false)
	{
		global $conf;

		return 0;
	}*/

	/**
	 * @param bool $parameters
	 * @return int
	 */
	public function updateSession($parameters=false)
	{
		global $conf;

		// Switch to another entity
		if (! empty($conf->multicompany->enabled) && GETPOST('action','aZ') == 'switchentity')
		{
			if ($this->switchEntity(GETPOST('entity','int')) > 0)
			{
				header("Location: ".DOL_URL_ROOT.'/');
				exit;
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function getLoginPageOptions($parameters=false)
	{
		global $conf, $langs;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key => $value)
			{
				$$key=$value;
			}
		}

		// Entity combobox
		if (empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX))
		{
			if (empty($entity)) $entity=1;
			$lastentity=(! empty($conf->global->MULTICOMPANY_FORCE_ENTITY)?$conf->global->MULTICOMPANY_FORCE_ENTITY:$entity);
			$currentcontext = explode(':', $parameters['context']);

			if (in_array('cashdeskloginpage', $currentcontext))
			{
				$select_entity = $this->select_entities($lastentity, 'entity', ' tabindex="3"', true, false, false, false, '', 'minwidth100imp');

				$tableformat = '<tr>';
				$tableformat.= '<td class="label1">'.$langs->trans("Entity").'</td>';
				$tableformat.= '<td>'.$select_entity.'</td>';
				$tableformat.= '</tr>';

				$this->resprints = $tableformat;
			}
			else
			{
				$select_entity = $this->select_entities($lastentity, 'entity', ' tabindex="3"', true, false, false, false, '', 'login-entity minwidth180');

				$divformat = "\n".'<div class="trinputlogin multicompany-trinputlogin">'."\n";
				$divformat.= '<div class="tagtd nowrap text-align-left valignmiddle tdinputlogin">'."\n";
				$divformat.= '<span class="fa fa-globe">'.$select_entity.'</span>'."\n";
				$divformat.= '</div></div>';

				if (! empty($conf->global->MULTICOMPANY_LOGIN_LOGO_BY_ENTITY)) {
					$divformat.= '
					<script type="text/javascript">
					$(document).ready(function() {
						var entityId = $("#entity").val();
						if (entityId > 1) {
							checkEntity();
						}
						$( "#login_right" ).on("change", "#entity", function() {
							checkEntity();
						});
						function checkEntity() {
							$.get( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
								action: \'getEntityLogo\',
								id: $("#entity").val()
							},
							function(result) {
								if (result.status == "success") {
									if (result.unsplashimg != "") {
										var xhr;
										var _orgAjax = jQuery.ajaxSettings.xhr;
										jQuery.ajaxSettings.xhr = function () {
										  xhr = _orgAjax();
										  return xhr;
										};
										$.ajax(result.unsplashimg, {
											success: function() {
												$("body").css("background-color", "#ccc");
												changeImgBg(xhr.responseURL);
												changeImgLogo(result.urllogo);
											},
											error: function() {
												if (result.bgimg != "") {
													changeImgBg(result.bgimg);
												}
												changeImgLogo(result.urllogo);
											}
										});
									} else if (result.bgimg != "") {
										changeImgBg(result.bgimg);
										changeImgLogo(result.urllogo);
									} else {
										changeImgLogo(result.urllogo);
									}
								}
							});
						};
						function changeImgBg(bgimg) {
							$("body").fadeTo(300,0.01, function() {
								$("body").css("background-image", "url(" + bgimg + ")");
							}).fadeTo(300,1);
						};
						function changeImgLogo(urllogo) {
							$("#img_logo").fadeTo(400,0.01, function() {
								$("#img_logo").attr("src", urllogo);
							}).fadeTo(400,1);
						};
					});
					</script>';
				}

				$this->resprints = $divformat;
			}
		}

		return 0;
	}

	/**
	 *
	 */
	public function getPasswordForgottenPageOptions($parameters=false)
	{
		return $this->getLoginPageOptions($parameters);
	}

	/**
	 * Add all entities default dictionnaries in database
	 */
	public function addAllEntitiesDefaultDicts()
	{
		if (! empty($this->sharingdicts))
		{
			$this->getInstanceDao();
			$this->dao->getEntities();

			$dir	= "/multicompany/sql/dict/";

			foreach($this->sharingdicts as $dict => $data)
			{
				// Load sql init_new_entity_dict.sql file
				$file 	= 'init_new_entity_'.$dict.'.sql';
				$fullpath = dol_buildpath($dir.$file);

				if (file_exists($fullpath))
				{
					foreach ($this->dao->entities as $entity)
					{
						if ($entity->id == 1) continue;

						$result=run_sql($fullpath,1,$entity->id);
					}
				}
			}
		}
	}

	/**
	 *  Load shared elements
	 *
	 *  @return void
	 */
	private function loadSharedElements()
	{
		global $conf;

		if (! empty($this->sharings))
		{
			$this->getInstanceDao();

			foreach($this->sharings as $element => $ids)
			{
				$modulesharingenabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';

				$module = ((isset($this->sharingmodulename[$element]) && !empty($this->sharingmodulename[$element])) ? $this->sharingmodulename[$element] : $element);

				if (! empty($conf->$module->enabled) && ! empty($conf->global->$modulesharingenabled))
				{
					$entities=array();

					if (! empty($this->referent))
					{
						// Load configuration of referent entity
						$this->config = $this->dao->getEntityConfig($this->referent);
						$this->setConstant($conf, $element);
					}

					if (! empty($ids))
					{
						foreach ($ids as $id)
						{
							$ret=$this->dao->fetch($id);
							if ($ret > 0 && $this->dao->active)
							{
								$entities[] = $id;
							}
						}

						$this->entities[$element] = (! empty($entities) ? implode(",", $entities) : 0);
						$this->entities[$element].= ','.$conf->entity;
					}
				}
			}
		}
		//var_dump($this->entities);
	}

	/**
	 * 	Get modify entity dialog
	 */
	private function getModifyEntityDialog($htmlname, $action, $object)
	{
		global $langs;

		$langs->loadLangs(array('errors','multicompany@multicompany'));

		$selectEntities = $this->select_entities('', 'entity' . $htmlname, '', false, array($object->entity));

		$out = '';

		if (! empty($selectEntities)) {

			$out.= '<!-- BEGIN MULTICOMPANY AJAX TEMPLATE -->';

			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#modify-entity-'.$htmlname.'" ).click(function() {
					$( "#dialog-modify-'.$htmlname.'" ).dialog({
						modal: true,
						resizable: false,
						width: 400,
						height: 200,
						open: function() {
							$(".ui-dialog-buttonset > button:last").focus();
						},
						buttons: {
							\''.$langs->trans('Validate').'\': function() {
								$.get( "'.dol_buildpath('/multicompany/core/ajax/functions.php',1).'", {
									action: \''.$action.'\',
									element: \''.$object->element.'\',
									fk_element: \''.$object->id.'\',
									id: $( "#entity'.$htmlname.'" ).val()
								},
								function(result) {
									if (result.status == "success") {
										$.jnotify("'.$langs->trans(ucfirst($htmlname) . "ModifyEntitySuccess").'", "ok");
										$( "#dialog-modify-'.$htmlname.'" ).dialog( "close" );
										window.location.href = "'.$_SERVER["PHP_SELF"].'?'.($htmlname == 'thirdparty'?'socid':'id').'='.$object->id.'";
									} else {
										$.jnotify("'.$langs->trans("Error" . ucfirst($htmlname) . "ModifyEntity").'", "error", true);
										if (result.error) {
											if (result.error == "ErrorCustomerCodeAlreadyUsed") {
												$.jnotify("'.$langs->trans("ErrorCustomerCodeAlreadyUsed").'", "error", true);
											}
										}
									}
								});
							},
							\''.$langs->trans('Cancel').'\': function() {
								$(this).dialog( "close" );
							}
						}
					});
				});
			});
			</script>';

			$out.= '<div id="dialog-modify-' . $htmlname . '" class="hideobject" title="' . $langs->trans(ucfirst($htmlname) . 'ModifyEntity') . '">'."\n";
			$out.= '<p>' . img_warning() . ' ' . $langs->trans(ucfirst($htmlname) . 'ModifyEntityDescription') . '</p>'."\n";
			$out.= '<div>' . $langs->trans('SelectAnEntity');
			$out.= $selectEntities . '</div>'."\n";
			$out.= '</div>'."\n";

			$out.= '<!-- END MULTICOMPANY AJAX TEMPLATE -->';
		}

		return $out;
	}

	/**
	 * 	Show entity info
	 */
	private function getTopRightMenu()
	{
		global $conf, $user, $langs;

		$langs->loadLangs(array('languages','admin','multicompany@multicompany'));

		$out='';

		if (! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) || ! empty($user->admin))
		{
			if (($conf->global->MAIN_THEME === 'eldy' && empty($conf->global->MULTICOMPANY_DROPDOWN_MENU_DISABLED) && ! GETPOSTISSET('theme')) || (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) === 'eldy'))
			{
				$out.= $this->getDropdownMenu();
			}
			else
			{
				$form=new Form($this->db);

				$this->getInfo($conf->entity);

				$selectEntities = $this->select_entities('', 'changeentity', '', false, array($conf->entity), false, false, '', 'minwidth200imp', true, true);

				$text ='<span class="fa fa-globe atoplogin switchentity'.(($conf->global->MAIN_THEME === 'eldy' && ! GETPOSTISSET('theme')) ? '' : ' padding-top3').'">';
				if (($conf->global->MAIN_THEME === 'eldy' && empty($conf->global->MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL) && ! GETPOSTISSET('theme')) || (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) === 'eldy')) {
					$text.= '<span class="topmenu-mc-label">'.$this->label.'</span>';
				}
				$text.= '</span>';

				if ($cache = getCache('country_' . $this->country_id)) {
					$country = $cache;
				} else {
					$country = getCountry($this->country_id);
					setCache('country_' . $this->country_id, $country);
				}
				$imgCountry=picto_from_langcode($this->country_code, 'class="multicompany-flag-country"');
				$imgLang=picto_from_langcode($this->language_code, 'class="multicompany-flag-language"');

				$htmltext ='<u>'.$langs->trans("Entity").'</u>'."\n";
				$htmltext.= '<br>';
				$htmltext.='<br><b>'.$langs->trans("Label").'</b>: '.$this->label."\n";
				$htmltext.='<br><b>'.$langs->trans("Country").'</b>: '. ($imgCountry?$imgCountry.' ':'') . $country."\n";
				$htmltext.='<br><b>'.$langs->trans("Currency").'</b>: '. currency_name($this->currency_code) . ' (' . $langs->getCurrencySymbol($this->currency_code) . ')'."\n";
				$htmltext.='<br><b>'.$langs->trans("Language").'</b>: '. ($imgLang?$imgLang.' ':'') . ($this->language_code=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$this->language_code));
				if (! empty($this->description)) $htmltext.='<br><b>'.$langs->trans("Description").'</b>: '.$this->description."\n";
				$htmltext.= '<br><br>';

				$out.= $form->textwithtooltip('', $htmltext, 2, 1, $text, 'login_block_elem multicompany_block', 2);

				if (! empty($selectEntities)) {
					$out.= '
					<script type="text/javascript">
					$(document).ready(function() {
						$(".switchentity").click(function() {
							$( "#dialog-switchentity" ).dialog({
								modal: true,
								width: '.($conf->dol_optimize_smallscreen ? 300 : 400).',
								buttons: {
									\''.$langs->trans('Ok').'\': function() {
										$.get("'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
											action: \'switchEntity\',
											id: $( "#changeentity" ).val()
										},
										function(content) {
											$("#dialog-switchentity").dialog( "close" );
											var url = window.location.pathname;
											var queryString = window.location.href.split("?")[1];
											if (queryString) {
												var params = parseQueryString(queryString);
												delete params.action;
												delete params.switchentityautoopen;
												url = url + "?" + jQuery.param(params);
											}
											location.href=url;
										});
									},
									\''.$langs->trans('Cancel').'\': function() {
										$(this).dialog( "close" );
									}
								}
							});
						});
						var parseQueryString = function( queryString ) {
							var params = {}, queries, temp, i, l;
							// Split into key/value pairs
							queries = queryString.split("&");
							// Convert the array of strings into an object
							for ( i = 0, l = queries.length; i < l; i++ ) {
								temp = queries[i].split("=");
								temp[1] = temp[1].replace(/#builddoc/gi, "");
								params[temp[0]] = temp[1];
							}
							return params;
						};
					';
					if (GETPOST('switchentityautoopen','int'))
					{
						$out.='$(".switchentity").click();'."\n";
					}
					$out.= '
				});
				</script>';

					$out.= '<div id="dialog-switchentity" class="hideobject" title="'.$langs->trans('SwitchToAnotherEntity').'">'."\n";
					$out.= '<br>'.$langs->trans('SelectAnEntity');
					$out.= $selectEntities;
					$out.= '</div>'."\n";
				} else {

				}
			}
		}

		if (($level = checkMultiCompanyVersion()) === -2)
		{
			$msg = get_htmloutput_mesg(img_warning('default') . ' ' . $langs->trans("MultiCompanyUpgradeIsNeeded"), '', 'mc-upgrade-alert', 1);
			$out.= '
			<script type="text/javascript">
			$(document).ready(function() {
				$( "#id-right .fiche" ).before( \'' . $msg . '\' );
			});
			</script>';
		}

		$this->resprints = $out;
	}

	/**
	 *
	 * @return string
	 */
	private function getDropdownMenu()
	{
		global $conf, $user, $langs;

		$this->getInfo($conf->entity);

		if ($cache = getCache('country_' . $this->country_id)) {
			$country = $cache;
		} else {
			$country = getCountry($this->country_id);
			setCache('country_' . $this->country_id, $country);
		}
		$imgCountry=picto_from_langcode($this->country_code, 'class="multicompany-flag-country"');
		$imgLang=picto_from_langcode($this->language_code, 'class="multicompany-flag-language"');

		$dropdownBody = '';
		$dropdownBody.= '<span id="topmenumcmoreinfo-btn"><i class="fa fa-caret-right"></i> '.$langs->trans("ShowMoreInfos").'</span>';
		$dropdownBody.= '<div id="topmenumcmoreinfo" >';
		$dropdownBody.= '<br><u>'.$langs->trans("Entity").'</u>'."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Label").'</b>: '.$this->label."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Country").'</b>: '. ($imgCountry?$imgCountry.' ':'') . $country."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Currency").'</b>: '. currency_name($this->currency_code) . ' (' . $langs->getCurrencySymbol($this->currency_code) . ')'."\n";
		$dropdownBody.= '<br><b>'.$langs->trans("Language").'</b>: '. ($imgLang?$imgLang.' ':'') . ($this->language_code=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$this->language_code));
		if (! empty($this->description)) $dropdownBody.= '<br><b>'.$langs->trans("Description").'</b>: '.$this->description."\n";
		$dropdownBody.= '</div>';

		$selectEntities = $this->select_entities('', 'changeentity', '', false, array($conf->entity), false, false, '', 'minwidth200imp', true, true);

		$entitySwitchLink ='<div id="switchentity-menu" class="button-top-menu-dropdown'.(empty($selectEntities) ? ' button-not-allowed' : '').'"><i class="fa fa-random"></i> '.$langs->trans("SwitchEntity").'</div>';
		$entityConfigLink ='<a class="button-top-menu-dropdown" href="'.dol_buildpath('multicompany/admin/multicompany.php', 1).'?action=edit&id='.$conf->entity.'"><i class="fa fa-cogs"></i>  '.$langs->trans("Setup").'</a>';

		$out = '<div class="inline-block nowrap">';
		$out.= '<div class="inline-block login_block_elem login_block_elem_name float-left" style="padding: 0px;">';

		$out.= '<div id="topmenu-mc-dropdown" class="atoplogin mcdropdown mc-menu">';
		$out.= '<span class="fa fa-globe atoplogin mc-dropdown-toggle" data-toggle="mcdropdown" id="mc-dropdown-icon">';

		if (empty($conf->global->MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL)) {
			$out .= '<span class="topmenu-mc-label">' . $this->label . '</span>';
		}
		$out.= '<span class="fa fa-chevron-down padding-left5" id="mc-dropdown-icon-down"></span>';
		$out.= '<span class="fa fa-chevron-up padding-left5 hidden" id="mc-dropdown-icon-up"></span>';
		$out.= '</span>';

		$out.= '<div class="mc-dropdown-menu">';

		$out.= '<div class="mc-header">';
		$out.= '<div class="fa fa-globe dropdown-mc-image"></div>';

		$out .= '<br>';
		if (! empty($conf->global->MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL)) {
			$out .= '<br><span class="topmenu-mc-header-label">' . $this->label . '</span>';
		}

		if (! empty($selectEntities)) {
			$out.= '<br>'.$langs->trans('SelectAnEntity');
			$out.= $selectEntities;
		} else {
			$out.= '<br><br>'.$langs->trans('NoOtherEntityAvailable');
		}

		$out.= '</div>';

		$out.= '<div class="mc-body">'.$dropdownBody.'</div>';

		$out.= '<div class="mc-footer">';
		$out.= '<div class="pull-left">';
		if (! empty($user->admin) && empty($user->entity)) {
			$out.= $entityConfigLink;
		}
		$out.= '</div>';

		$out.= '<div class="pull-right">';
		$out.= $entitySwitchLink;
		$out.= '</div>';

		$out.= '<div style="clear:both;"></div>';

		$out.= '</div>';
		$out.= '</div>';
		$out.= '</div>';

		$out.= '</div></div>';

		$out.= '
		<script type="text/javascript">
		$(document).ready(function() {
			$(document).on("click", function(event) {
				if (!$(event.target).closest("#topmenu-mc-dropdown").length) {
					// Hide the menus.
					$("#topmenu-mc-dropdown").removeClass("open");
					$("#mc-dropdown-icon-down").show();
					$("#mc-dropdown-icon-up").hide();
				}
			});
			$("#tmenu_tooltip").css("padding-right",parseFloat($("#tmenu_tooltip").css("padding-right")) + $("#topmenu-mc-dropdown").width());
			$("#topmenu-mc-dropdown .mc-dropdown-toggle").on("click", function(event) {
				$("#topmenu-mc-dropdown").toggleClass("open");
				$("#mc-dropdown-icon-down").toggle();
				$("#mc-dropdown-icon-up").toggle();
			});
			$("#topmenumcmoreinfo-btn").on("click", function() {
				$("#topmenumcmoreinfo").slideToggle();
			});
			$("#switchentity-menu").on("click",function() {
				$.get("'.dol_buildpath('/multicompany/core/ajax/functions.php', 1).'", {
					action: \'switchEntity\',
					id: $("#changeentity").val()
				},
				function(content) {
					var url = window.location.pathname;
					var queryString = window.location.href.split("?")[1];
					if (queryString) {
						var params = parseQueryString(queryString);
						delete params.action;
						delete params.switchentityautoopen;
						url = url + "?" + jQuery.param(params);
					}
					location.href=url;
				});
			});
			var parseQueryString = function( queryString ) {
				var params = {}, queries, temp, i, l;
				// Split into key/value pairs
				queries = queryString.split("&");
				// Convert the array of strings into an object
				for ( i = 0, l = queries.length; i < l; i++ ) {
					temp = queries[i].split("=");
					temp[1] = temp[1].replace(/#builddoc/gi, "");
					params[temp[0]] = temp[1];
				}
				return params;
			};
		';
		if (GETPOST('switchentityautoopen','int'))
		{
			$out.='$("#switchentity").click();'."\n";
		}
		$out.= '
		});
		</script>';

		return $out;
	}

	/**
	 *	Set parameters with referent entity
	 *
	 * @param Conf $conf
	 * @param string $element
	 */
	public function setConstant(&$conf, $element)
	{
		if (! empty($this->config))
		{
			$constants=array();

			if ($element == 'thirdparty')
			{
				$constants = array(
						'SOCIETE_CODECLIENT_ADDON',
						'COMPANY_ELEPHANT_MASK_CUSTOMER',
						'COMPANY_ELEPHANT_MASK_SUPPLIER',
						'SOCIETE_IDPROF1_UNIQUE',
						'SOCIETE_IDPROF2_UNIQUE',
						'SOCIETE_IDPROF3_UNIQUE',
						'SOCIETE_IDPROF4_UNIQUE'
				);
			}

			if (! empty($constants))
			{
				foreach($constants as $name)
				{
					if (! empty($this->config[$name])) $conf->global->$name = $this->config[$name];
				}
			}
		}
	}

	/**
	 *
	 * @param int $groupid
	 * @param int $template
	 * @param array $entities
	 * @return number
	 */
	public function duplicateUserGroupRights($groupid, $template, $entities)
	{
		require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

		$error=0;

		dol_syslog(get_class($this)."::duplicateUserGroupRights groupid=".$groupid." template=".$template." entities=".implode(",", $entities), LOG_DEBUG);

		$groupstatic = new UserGroup($this->db);
		$ret = $groupstatic->fetch($groupid, '', false);
		if ($ret > 0)
		{
			$this->getInstanceDao();
			$permsgroupbyentity = $this->dao->getGroupRightsByEntity($groupid, $template);

			if (! empty($entities))
			{
				foreach($entities as $entity)
				{
					if ($error > 0) break;

					$ret = $groupstatic->delrights('', 'allmodules', '', $entity);
					if ($ret < 0) {
						$error++;
						break;
					}

					foreach($permsgroupbyentity as $rid)
					{
						$ret = $groupstatic->addrights($rid, '', '', $entity);
						if ($ret < 0) {
							$error++;
							break;
						}
					}
				}

				if (!$error) {
					return 1;
				} else {
					return -1;
				}

			} else {
				return -2;
			}

		} else {
			return -3;
		}
	}

}
