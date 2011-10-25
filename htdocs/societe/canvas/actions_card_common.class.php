<?php
/* Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/canvas/actions_card_common.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (common)
 */

/**
 *	\class      ActionsCardCommon
 *	\brief      Classe permettant la gestion des tiers par defaut
 */
abstract class ActionsCardCommon
{
    var $db;
    var $dirmodule;
    var $targetmodule;
    var $canvas;
    var $card;

	//! Template container
	var $tpl = array();
	//! Object container
	var $object;
	//! Error string
	var $error;
	//! Error array
	var $errors=array();
	

	/**
	 * 	Instantiation of DAO class
	 *  TODO This method is useless
	 *
	 * 	@return	void
	 */
	protected function getInstanceDao()
	{
		if (! is_object($this->object))
		{
			$modelclassfile = dol_buildpath('/'.$this->dirmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php');
	        if (file_exists($modelclassfile))
	        {
	            // Include dataservice class (model)
	            $ret = require_once($modelclassfile);
	            if ($ret)
	            {
	            	// Instantiate dataservice class (model)
	            	$modelclassname = 'Dao'.ucfirst($this->targetmodule).ucfirst($this->canvas);
	            	$this->object = new $modelclassname($this->db);
	            }
	        }
		}
	}

	/**
     *  Get object from id or ref and save it into this->object
	 *
     *  @param		int			Object id
     *  @param		ref			Object ref
     *  @return		object		Object loaded
     */
    protected function getObject($id,$ref='')
    {
    	$ret = $this->getInstanceDao();

    	if (is_object($this->object) && method_exists($this->object,'fetch'))
    	{
    		if (! empty($id) || ! empty($ref)) $this->object->fetch($id,$ref);
    	}
    	else
    	{
    	    // TODO Keep only this part of code. Previous in this method is useless
    		$object = new Societe($this->db);
    		if (! empty($id) || ! empty($ref)) $object->fetch($id,$ref);
            $this->object = $object;
    	}
    }

    /**
     *	Load data control
     *
     *	@param	int		$socid		Id of third party
     *	@return	void
     */
    function doActions(&$action)
    {
        global $conf, $user, $langs;

        if ($_POST["getcustomercode"])
        {
            // We defined value code_client
            $_POST["code_client"]="Acompleter";
        }

        if ($_POST["getsuppliercode"])
        {
            // We defined value code_fournisseur
            $_POST["code_fournisseur"]="Acompleter";
        }

        // Add new third party
        if ((! $_POST["getcustomercode"] && ! $_POST["getsuppliercode"])
        && ($action == 'add' || $action == 'update'))
        {
            require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
            $error=0;

            if (GETPOST("private") == 1)
            {
                $this->object->particulier		= GETPOST("private");

                $this->object->nom				= empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($_POST["prenom"].' '.$_POST["nom"]):trim($_POST["nom"].' '.$_POST["prenom"]);
                $this->object->nom_particulier	= $_POST["nom"];
                $this->object->prenom			= $_POST["prenom"];
                $this->object->civilite_id		= $_POST["civilite_id"];
            }
            else
            {
                $this->object->name				= $_POST["nom"];
            }

            $this->object->address				= $_POST["adresse"];
            $this->object->zip					= $_POST["zipcode"];
            $this->object->town					= $_POST["town"];
            $this->object->country_id			= $_POST["pays_id"];
            $this->object->state_id				= $_POST["departement_id"];
            $this->object->tel					= $_POST["tel"];
            $this->object->fax					= $_POST["fax"];
            $this->object->email				= trim($_POST["email"]);
            $this->object->url					= $_POST["url"];
            $this->object->idprof1				= $_POST["idprof1"];
            $this->object->idprof2				= $_POST["idprof2"];
            $this->object->idprof3				= $_POST["idprof3"];
            $this->object->idprof4				= $_POST["idprof4"];
            $this->object->prefix_comm			= $_POST["prefix_comm"];
            $this->object->code_client			= $_POST["code_client"];
            $this->object->code_fournisseur		= $_POST["code_fournisseur"];
            $this->object->capital				= $_POST["capital"];
            $this->object->gencod				= $_POST["gencod"];
            $this->object->canvas				= GETPOST("canvas");

            $this->object->tva_assuj			= $_POST["assujtva_value"];

            // Local Taxes
            $this->object->localtax1_assuj		= $_POST["localtax1assuj_value"];
            $this->object->localtax2_assuj		= $_POST["localtax2assuj_value"];
            $this->object->tva_intra			= $_POST["tva_intra"];

            $this->object->forme_juridique_code	= $_POST["forme_juridique_code"];
            $this->object->effectif_id			= $_POST["effectif_id"];
            if (GETPOST("private") == 1)
            {
                $this->object->typent_id		= 8; // TODO predict another method if the field "special" change of rowid
            }
            else
            {
                $this->object->typent_id		= $_POST["typent_id"];
            }
            $this->object->client				= $_POST["client"];
            $this->object->fournisseur			= $_POST["fournisseur"];
            $this->object->fournisseur_categorie	= $_POST["fournisseur_categorie"];

            $this->object->commercial_id		= $_POST["commercial_id"];
            $this->object->default_lang			= $_POST["default_lang"];

            // Check parameters
            if (empty($_POST["cancel"]))
            {
                if (! empty($this->object->email) && ! isValidEMail($this->object->email))
                {
                    $error = 1;
                    $langs->load("errors");
                    $this->error = $langs->trans("ErrorBadEMail",$this->object->email);
                    $action = ($action == 'add' ? 'create' : 'edit');
                }
                if (! empty($this->object->url) && ! isValidUrl($this->object->url))
                {
                    $error = 1;
                    $langs->load("errors");
                    $this->error = $langs->trans("ErrorBadUrl",$this->object->url);
                    $action = ($action == 'add' ? 'create' : 'edit');
                }
                if ($this->object->fournisseur && ! $conf->fournisseur->enabled)
                {
                    $error = 1;
                    $langs->load("errors");
                    $this->error = $langs->trans("ErrorSupplierModuleNotEnabled");
                    $action = ($action == 'add' ? 'create' : 'edit');
                }
            }

            if (! $error)
            {
                if ($action == 'add')
                {
                    $this->db->begin();

                    if (empty($this->object->client))      $this->object->code_client='';
                    if (empty($this->object->fournisseur)) $this->object->code_fournisseur='';

                    $result = $this->object->create($user);
                    if ($result >= 0)
                    {
                        if ($this->object->particulier)
                        {
                            dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
                            $contact=new Contact($this->db);

                            $contact->civilite_id   = $this->object->civilite_id;
                            $contact->name          = $this->object->nom_particulier;
                            $contact->firstname     = $this->object->prenom;
                            $contact->address       = $this->object->address;
                            $contact->cp            = $this->object->cp;
                            $contact->ville         = $this->object->ville;
                            $contact->fk_pays       = $this->object->fk_pays;
                            $contact->socid         = $this->object->id;                // fk_soc
                            $contact->status        = 1;
                            $contact->email         = $this->object->email;
                            $contact->priv          = 0;

                            $result=$contact->create($user);
                        }
                    }
                    else
                    {
                        $this->errors=$this->object->errors;
                    }

                    if ($result >= 0)
                    {
                        $this->db->commit();

                        if ( $this->object->client == 1 )
                        {
                            Header("Location: ".DOL_URL_ROOT."/comm/fiche.php?socid=".$this->object->id);
                            return;
                        }
                        else
                        {
                            if (  $this->object->fournisseur == 1 )
                            {
                                Header("Location: ".DOL_URL_ROOT."/fourn/fiche.php?socid=".$this->object->id);
                                return;
                            }
                            else
                            {
                                Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                                return;
                            }
                        }
                        exit;
                    }
                    else
                    {
                        $this->db->rollback();

                        $this->errors=$this->object->errors;
                        $action = 'create';
                    }
                }

                if ($action == 'update')
                {
                    if ($_POST["cancel"])
                    {
                        Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                        exit;
                    }

                    $oldsoccanvas = dol_clone($this->object);

                    // To avoid setting code if third party is not concerned. But if it had values, we keep them.
                    if (empty($this->object->client) && empty($oldsoccanvas->code_client))             $this->object->code_client='';
                    if (empty($this->object->fournisseur) && empty($oldsoccanvas->code_fournisseur))    $this->object->code_fournisseur='';

                    $result = $this->object->update($this->object->id, $user, 1, $oldsoccanvas->codeclient_modifiable(), $oldsoccanvas->codefournisseur_modifiable());
                    if ($result >= 0)
                    {
                        Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                        exit;
                    }
                    else
                    {
                        $this->object->id = $this->object->id;
                        $reload = 0;
                        $this->errors = $this->object->errors;
                        $action = "edit";
                    }
                }
            }
        }

        if ($action == 'confirm_delete' && GETPOST("confirm") == 'yes')
        {
            $result = $this->object->delete($this->object->id);

            if ($result >= 0)
            {
                Header("Location: ".DOL_URL_ROOT."/societe/societe.php?delsoc=".$this->object->nom."");
                exit;
            }
            else
            {
                $reload = 0;
                $this->errors=$this->object->errors;
                $action = '';
            }
        }

        /*
         * Generate document
         */
        if ($action == 'builddoc')    // En get ou en post
        {
            if (is_numeric(GETPOST('model')))
            {
                $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Model"));
            }
            else
            {
                require_once(DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php');

                $this->object->fetch_thirdparty();

                // Define output language
                $outputlangs = $langs;
                $newlang='';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id') ) $newlang=GETPOST('lang_id');
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$this->object->default_lang;
                if (! empty($newlang))
                {
                    $outputlangs = new Translate("",$conf);
                    $outputlangs->setDefaultLang($newlang);
                }
                $result=thirdparty_doc_create($this->db, $this->object->id, '', GETPOST('model'), $outputlangs);
                if ($result <= 0)
                {
                    dol_print_error($this->db,$result);
                    exit;
                }
                else
                {
                    Header('Location: '.$_SERVER["PHP_SELF"].'?socid='.$this->object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
                    exit;
                }
            }
        }
    }

	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	&$action    Type of action
	 *    @param	string	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
     */
    function assign_values(&$action, $id=0, $ref='')
    {
        global $conf, $langs, $user, $mysoc, $canvas;
        global $form, $formadmin, $formcompany;

        if ($action == 'add' || $action == 'update') $this->assign_post($action);

        if ($_GET["type"]=='f')  		{ $this->object->fournisseur=1; }
        if ($_GET["type"]=='c')  		{ $this->object->client=1; }
        if ($_GET["type"]=='p')  		{ $this->object->client=2; }
        if ($_GET["type"]=='cp') 		{ $this->object->client=3; }
        if ($_REQUEST["private"]==1) 	{ $this->object->particulier=1;	}

        foreach($this->object as $key => $value)
        {
            $this->tpl[$key] = $value;
        }

        $this->tpl['error'] = get_htmloutput_errors($this->object->error,$this->object->errors);

        if ($action == 'create')
        {
        	if ($conf->use_javascript_ajax)
			{
				$this->tpl['ajax_selecttype'] = "\n".'<script type="text/javascript" language="javascript">
				jQuery(document).ready(function () {
		              jQuery("#radiocompany").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="company";
                            document.formsoc.private.value=0;
                            document.formsoc.submit();
		              });
		               jQuery("#radioprivate").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="individual";
                            document.formsoc.private.value=1;
                            document.formsoc.submit();
                      });
		          });
                </script>'."\n";
			}
        }

        if ($action == 'create' || $action == 'edit')
        {
        	if ($conf->use_javascript_ajax)
			{
				$this->tpl['ajax_selectcountry'] = "\n".'<script type="text/javascript" language="javascript">
				jQuery(document).ready(function () {
						jQuery("#selectpays_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});
					})
				</script>'."\n";
			}

            // Load object modCodeClient
            $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
            if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
            if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            {
                $module = substr($module, 0, dol_strlen($module)-4);
            }
            require_once(DOL_DOCUMENT_ROOT ."/core/modules/societe/".$module.".php");
            $modCodeClient = new $module;
            $this->tpl['auto_customercode'] = $modCodeClient->code_auto;
            // We verified if the tag prefix is used
            if ($modCodeClient->code_auto) $this->tpl['prefix_customercode'] = $modCodeClient->verif_prefixIsUsed();

            // TODO create a function
            $this->tpl['select_customertype'] = '<select class="flat" name="client">';
            $this->tpl['select_customertype'].= '<option value="2"'.($this->object->client==2?' selected="selected"':'').'>'.$langs->trans('Prospect').'</option>';
            $this->tpl['select_customertype'].= '<option value="3"'.($this->object->client==3?' selected="selected"':'').'>'.$langs->trans('ProspectCustomer').'</option>';
            $this->tpl['select_customertype'].= '<option value="1"'.($this->object->client==1?' selected="selected"':'').'>'.$langs->trans('Customer').'</option>';
            $this->tpl['select_customertype'].= '<option value="0"'.($this->object->client==0?' selected="selected"':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
            $this->tpl['select_customertype'].= '</select>';

            // Customer
            $this->tpl['customercode'] = $this->object->code_client;
            if ((!$this->object->code_client || $this->object->code_client == -1) && $modCodeClient->code_auto) $this->tpl['customercode'] = $modCodeClient->getNextValue($this->object,0);
            $this->tpl['ismodifiable_customercode'] = $this->object->codeclient_modifiable();
            $s=$modCodeClient->getToolTip($langs,$this->object,0);
            $this->tpl['help_customercode'] = $form->textwithpicto('',$s,1);

            if ($conf->fournisseur->enabled)
            {
            	$this->tpl['supplier_enabled'] = 1;

            	// Load object modCodeFournisseur
            	$module=$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
            	if (! $module) $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
            	if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            	{
            		$module = substr($module, 0, dol_strlen($module)-4);
            	}
            	require_once(DOL_DOCUMENT_ROOT ."/core/modules/societe/".$module.".php");
            	$modCodeFournisseur = new $module;
            	$this->tpl['auto_suppliercode'] = $modCodeFournisseur->code_auto;
            	// We verified if the tag prefix is used
            	if ($modCodeFournisseur->code_auto) $this->tpl['prefix_suppliercode'] = $modCodeFournisseur->verif_prefixIsUsed();

            	// Supplier
            	$this->tpl['yn_supplier'] = $form->selectyesno("fournisseur",$this->object->fournisseur,1);
            	$this->tpl['suppliercode'] = $this->object->code_fournisseur;
            	if ((!$this->object->code_fournisseur || $this->object->code_fournisseur == -1) && $modCodeFournisseur->code_auto) $this->tpl['suppliercode'] = $modCodeFournisseur->getNextValue($this->object,1);
            	$this->tpl['ismodifiable_suppliercode'] = $this->object->codefournisseur_modifiable();
            	$s=$modCodeFournisseur->getToolTip($langs,$this->object,1);
            	$this->tpl['help_suppliercode'] = $form->textwithpicto('',$s,1);

            	$this->object->LoadSupplierCateg();
            	$this->tpl['suppliercategory'] = $this->object->SupplierCategories;
            	$this->tpl['select_suppliercategory'] = $form->selectarray("fournisseur_categorie",$this->object->SupplierCategories,$_POST["fournisseur_categorie"],1);
            }

            // Zip
            $this->tpl['select_zip'] = $formcompany->select_ziptown($this->object->zip,'zipcode',array('town','selectpays_id','departement_id'),6);

            // Town
            $this->tpl['select_town'] = $formcompany->select_ziptown($this->object->town,'town',array('zipcode','selectpays_id','departement_id'));

            // Country
            $this->tpl['select_country'] = $form->select_country($this->object->pays_id,'pays_id');
            $countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);

            // State
            if ($this->object->pays_id) $this->tpl['select_state'] = $formcompany->select_state($this->object->state_id,$this->object->pays_code);
            else $this->tpl['select_state'] = $countrynotdefined;

            // Language
            if ($conf->global->MAIN_MULTILANGS) $this->tpl['select_lang'] = $formadmin->select_language(($this->object->default_lang?$this->object->default_lang:$conf->global->MAIN_LANG_DEFAULT),'default_lang',0,0,1);

            // VAT
            $this->tpl['yn_assujtva'] = $form->selectyesno('assujtva_value',$this->tpl['tva_assuj'],1);	// Assujeti par defaut en creation

            // Select users
            $this->tpl['select_users'] = $form->select_dolusers($this->object->commercial_id,'commercial_id',1);

            // Local Tax
            // TODO mettre dans une classe propre au pays
            if($mysoc->pays_code=='ES')
            {
                $this->tpl['localtax'] = '';

                if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td><td>';
                    $this->tpl['localtax'].= $form->selectyesno('localtax1assuj_value',$this->object->localtax1_assuj,1);
                    $this->tpl['localtax'].= '</td><td>'.$langs->trans("LocalTax2IsUsedES").'</td><td>';
                    $this->tpl['localtax'].= $form->selectyesno('localtax2assuj_value',$this->object->localtax1_assuj,1);
                    $this->tpl['localtax'].= '</td></tr>';
                }
                elseif($mysoc->localtax1_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td><td colspan="3">';
                    $this->tpl['localtax'].= $form->selectyesno('localtax1assuj_value',$this->object->localtax1_assuj,1);
                    $this->tpl['localtax'].= '</td><tr>';
                }
                elseif($mysoc->localtax2_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax2IsUsedES").'</td><td colspan="3">';
                    $this->tpl['localtax'].= $form->selectyesno('localtax2assuj_value',$this->object->localtax1_assuj,1);
                    $this->tpl['localtax'].= '</td><tr>';
                }
            }

        }
        else
        {
            $head = societe_prepare_head($this->object);

            $this->tpl['showhead']=dol_get_fiche_head($head, 'card', $title, 0, 'company');
            $this->tpl['showend']=dol_get_fiche_end();

            $this->tpl['showrefnav'] 		= $form->showrefnav($this->object,'socid','',($user->societe_id?0:1),'rowid','nom');

            $this->tpl['checkcustomercode'] = $this->object->check_codeclient();
            $this->tpl['checksuppliercode'] = $this->object->check_codefournisseur();
            $this->tpl['address'] 			= dol_nl2br($this->object->address);

            $img=picto_from_langcode($this->object->pays_code);
            if ($this->object->isInEEC()) $this->tpl['country'] = $form->textwithpicto(($img?$img.' ':'').$this->object->country,$langs->trans("CountryIsInEEC"),1,0);
            $this->tpl['country'] = ($img?$img.' ':'').$this->object->country;

            $this->tpl['phone'] 	= dol_print_phone($this->object->tel,$this->object->pays_code,0,$this->object->id,'AC_TEL');
            $this->tpl['fax'] 		= dol_print_phone($this->object->fax,$this->object->pays_code,0,$this->object->id,'AC_FAX');
            $this->tpl['email'] 	= dol_print_email($this->object->email,0,$this->object->id,'AC_EMAIL');
            $this->tpl['url'] 		= dol_print_url($this->object->url);

            $this->tpl['tva_assuj']		= yn($this->object->tva_assuj);

            // Third party type
            $arr = $formcompany->typent_array(1);
            $this->tpl['typent'] = $arr[$this->object->typent_code];

            if ($conf->global->MAIN_MULTILANGS)
            {
                require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
                //$s=picto_from_langcode($this->default_lang);
                //print ($s?$s.' ':'');
                $langs->load("languages");
                $this->tpl['default_lang'] = ($this->default_lang?$langs->trans('Language_'.$this->object->default_lang):'');
            }

            $this->tpl['image_edit']	= img_edit();

            $this->tpl['display_rib']	= $this->object->display_rib();

            // Sales representatives
            $this->tpl['sales_representatives'] = '';
            $listsalesrepresentatives=$this->object->getSalesRepresentatives($user);
            $nbofsalesrepresentative=count($listsalesrepresentatives);
            if ($nbofsalesrepresentative > 3)   // We print only number
            {
            	$this->tpl['sales_representatives'].= '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$this->object->id.'">';
            	$this->tpl['sales_representatives'].= $nbofsalesrepresentative;
            	$this->tpl['sales_representatives'].= '</a>';
            }
            else if ($nbofsalesrepresentative > 0)
            {
            	$userstatic=new User($db);
            	$i=0;
            	foreach($listsalesrepresentatives as $val)
            	{
            		$userstatic->id=$val['id'];
            		$userstatic->nom=$val['name'];
            		$userstatic->prenom=$val['firstname'];
            		$this->tpl['sales_representatives'].= $userstatic->getNomUrl(1);
            		$i++;
            		if ($i < $nbofsalesrepresentative) $this->tpl['sales_representatives'].= ', ';
            	}
            }
            else $this->tpl['sales_representatives'].= $langs->trans("NoSalesRepresentativeAffected");

            // Linked member
            if ($conf->adherent->enabled)
            {
                $langs->load("members");
                $adh=new Adherent($this->db);
                $result=$adh->fetch('','',$this->object->id);
                if ($result > 0)
                {
                    $adh->ref=$adh->getFullName($langs);
                    $this->tpl['linked_member'] = $adh->getNomUrl(1);
                }
                else
                {
                    $this->tpl['linked_member'] = $langs->trans("UserNotLinkedToMember");
                }
            }

            // Local Tax
            // TODO mettre dans une classe propre au pays
            if($mysoc->pays_code=='ES')
            {
                $this->tpl['localtax'] = '';

                if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td>';
                    $this->tpl['localtax'].= '<td>'.yn($this->object->localtax1_assuj).'</td>';
                    $this->tpl['localtax'].= '<td>'.$langs->trans("LocalTax2IsUsedES").'</td>';
                    $this->tpl['localtax'].= '<td>'.yn($this->object->localtax2_assuj).'</td></tr>';
                }
                elseif($mysoc->localtax1_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td>';
                    $this->tpl['localtax'].= '<td colspan="3">'.yn($this->object->localtax1_assuj).'</td></tr>';
                }
                elseif($mysoc->localtax2_assuj=="1")
                {
                    $this->tpl['localtax'].= '<tr><td>'.$langs->trans("LocalTax2IsUsedES").'</td>';
                    $this->tpl['localtax'].= '<td colspan="3">'.yn($this->object->localtax2_assuj).'</td></tr>';
                }
            }
        }
    }

    /**
     *  Assign POST values into object
     *
     *	@param		string		$action		Action string
     *  @return		string					HTML output
     */
    private function assign_post($action)
    {
        global $langs, $mysoc;

        $this->object->id					=	$_POST["socid"];
        $this->object->name					=	$_POST["nom"];
        $this->object->prefix_comm			=	$_POST["prefix_comm"];
        $this->object->client				=	$_POST["client"];
        $this->object->code_client			=	$_POST["code_client"];
        $this->object->fournisseur			=	$_POST["fournisseur"];
        $this->object->code_fournisseur		=	$_POST["code_fournisseur"];
        $this->object->address				=	$_POST["adresse"];
        $this->object->zip					=	$_POST["zipcode"];
        $this->object->town					=	$_POST["town"];
        $this->object->country_id			=	$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->country_id;
        $this->object->state_id		        =	$_POST["departement_id"];
        $this->object->tel					=	$_POST["tel"];
        $this->object->fax					=	$_POST["fax"];
        $this->object->email				=	$_POST["email"];
        $this->object->url					=	$_POST["url"];
        $this->object->capital				=	$_POST["capital"];
        $this->object->idprof1				=	$_POST["idprof1"];
        $this->object->idprof2				=	$_POST["idprof2"];
        $this->object->idprof3				=	$_POST["idprof3"];
        $this->object->idprof4				=	$_POST["idprof4"];
        $this->object->typent_id			=	$_POST["typent_id"];
        $this->object->effectif_id			=	$_POST["effectif_id"];
        $this->object->gencod				=	$_POST["gencod"];
        $this->object->forme_juridique_code	=	$_POST["forme_juridique_code"];
        $this->object->default_lang			=	$_POST["default_lang"];
        $this->object->commercial_id		=	$_POST["commercial_id"];

        $this->object->tva_assuj 			= 	$_POST["assujtva_value"]?$_POST["assujtva_value"]:1;
        $this->object->tva_intra			=	$_POST["tva_intra"];

        //Local Taxes
        $this->object->localtax1_assuj		= 	$_POST["localtax1assuj_value"];
        $this->object->localtax2_assuj		= 	$_POST["localtax2assuj_value"];

        // We set pays_id, and pays_code label of the chosen country
        if ($this->object->country_id)
        {
            $tmparray=getCountry($this->object->country_id,'all',$this->db,$langs,0);
            $this->object->country_code	=	$tmparray['code'];
            $this->object->country_label=	$tmparray['label'];
        }
    }

}

?>