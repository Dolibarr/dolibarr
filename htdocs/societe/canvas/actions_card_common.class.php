<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *	\file       htdocs/societe/canvas/actions_card_common.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (common)
 */

/**
 *	Classe permettant la gestion des tiers par defaut
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
	 *
	 * 	@return	int		0
	 *  @deprecated		Using getInstanceDao should not be used.
	 */
	private function getInstanceDao()
	{
		dol_syslog(__METHOD__ . " is deprecated", LOG_WARNING);

		if (! is_object($this->object))
		{
			$modelclassfile = dol_buildpath('/'.$this->dirmodule.'/canvas/'.$this->canvas.'/dao_'.$this->targetmodule.'_'.$this->canvas.'.class.php');
	        if (file_exists($modelclassfile))
	        {
	            // Include dataservice class (model)
	            $ret = require_once $modelclassfile;
	            if ($ret)
	            {
	            	// Instantiate dataservice class (model)
	            	$modelclassname = 'Dao'.ucfirst($this->targetmodule).ucfirst($this->canvas);
	            	$this->object = new $modelclassname($this->db);
	            }
	        }
		}
    	return 0;
	}

	/**
     *  Get object from id or ref and save it into this->object
	 *
     *  @param		int		$id			Object id
     *  @param		ref		$ref		Object ref
     *  @return		object				Object loaded
     */
    protected function getObject($id,$ref='')
    {
    	//$ret = $this->getInstanceDao();

    	$object = new Societe($this->db);
    	if (! empty($id) || ! empty($ref)) $object->fetch($id,$ref);
    	$this->object = $object;
    }

    /**
     *  doActions of a canvas is not the doActions of the hook
     *  @deprecated Use the doActions of hooks instead of this.
     *
     *	@param	int		$action	Action code
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
            require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
            $error=0;

            if (GETPOST("private") == 1)
            {
                $this->object->particulier		= GETPOST("private");

                $this->object->name				= dolGetFirstLastname(GETPOST('firstname','alpha'),GETPOST('lastname','alpha'));
                $this->object->civility_id		= $_POST["civility_id"];
                // Add non official properties
                $this->object->name_bis        	= $_POST["lastname"];
                $this->object->firstname		= $_POST["firstname"];
            }
            else
            {
                $this->object->name				= $_POST["nom"];
            }

            $this->object->address				= $_POST["adresse"];
            $this->object->zip					= $_POST["zipcode"];
            $this->object->town					= $_POST["town"];
            $this->object->country_id			= $_POST["country_id"];
            $this->object->state_id				= $_POST["state_id"];
            $this->object->phone					= $_POST["tel"];
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
            $this->object->barcode				= $_POST["barcode"];
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
                $this->object->typent_id		= dol_getIdFromCode($db,'TE_PRIVATE','c_typent');
            }
            else
            {
                $this->object->typent_id		= $_POST["typent_id"];
            }
            $this->object->client				= $_POST["client"];
            $this->object->fournisseur			= $_POST["fournisseur"];

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
                            dol_syslog(get_class($this)."::doActions This thirdparty is a personal people",LOG_DEBUG);
                            $contact=new Contact($this->db);

                            $contact->civility_id   = $this->object->civility_id;
                            $contact->name          = $this->object->name_bis;
                            $contact->firstname     = $this->object->firstname;
                            $contact->address       = $this->object->address;
                            $contact->zip           = $this->object->zip;
                            $contact->town          = $this->object->town;
                            $contact->country_id    = $this->object->country_id;
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
                            header("Location: ".DOL_URL_ROOT."/comm/card.php?socid=".$this->object->id);
                            return;
                        }
                        else
                        {
                            if (  $this->object->fournisseur == 1 )
                            {
                                header("Location: ".DOL_URL_ROOT."/fourn/card.php?socid=".$this->object->id);
                                return;
                            }
                            else
                            {
                                header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                                return;
                            }
                        }
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
                        header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                        exit;
                    }

					$oldsoccanvas = clone $this->object;

                    // To avoid setting code if third party is not concerned. But if it had values, we keep them.
                    if (empty($this->object->client) && empty($oldsoccanvas->code_client))             $this->object->code_client='';
                    if (empty($this->object->fournisseur) && empty($oldsoccanvas->code_fournisseur))    $this->object->code_fournisseur='';

                    $result = $this->object->update($this->object->id, $user, 1, $oldsoccanvas->codeclient_modifiable(), $oldsoccanvas->codefournisseur_modifiable());
                    if ($result >= 0)
                    {
                        header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$this->object->id);
                        exit;
                    }
                    else
                    {
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
                header("Location: ".DOL_URL_ROOT."/societe/list.php?delsoc=".$this->object->name."");
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
                require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';

                $this->object->fetch_thirdparty();

                // Define output language
                $outputlangs = $langs;
                $newlang='';
                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
                if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$this->object->default_lang;
                if (! empty($newlang))
                {
                    $outputlangs = new Translate("",$conf);
                    $outputlangs->setDefaultLang($newlang);
                }
                $result=thirdparty_doc_create($this->db, $this->object->id, '', GETPOST('model','alpha'), $outputlangs);
                if ($result <= 0)
                {
                    dol_print_error($this->db,$result);
                    exit;
                }
            }
        }
    }

	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
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
        if (is_array($GLOBALS['errors'])) $this->tpl['error'] = get_htmloutput_mesg('',$GLOBALS['errors'],'error');

        if ($action == 'create')
        {
        	if ($conf->use_javascript_ajax)
			{
				$this->tpl['ajax_selecttype'] = "\n".'<script type="text/javascript" language="javascript">
				$(document).ready(function () {
		              $("#radiocompany").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="company";
                            document.formsoc.private.value=0;
                            document.formsoc.submit();
		              });
		               $("#radioprivate").click(function() {
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
				$(document).ready(function () {
						$("#selectcountry_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});
					})
				</script>'."\n";
			}

            // Load object modCodeClient
            $module=(! empty($conf->global->SOCIETE_CODECLIENT_ADDON)?$conf->global->SOCIETE_CODECLIENT_ADDON:'mod_codeclient_leopard');
            if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            {
                $module = substr($module, 0, dol_strlen($module)-4);
            }
            $dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
            foreach ($dirsociete as $dirroot)
            {
                $res=dol_include_once($dirroot.$module.'.php');
                if ($res) break;
            }
            $modCodeClient = new $module($db);
            $this->tpl['auto_customercode'] = $modCodeClient->code_auto;
            // We verified if the tag prefix is used
            if ($modCodeClient->code_auto) $this->tpl['prefix_customercode'] = $modCodeClient->verif_prefixIsUsed();

            // TODO create a function
            $this->tpl['select_customertype'] = Form::selectarray('client', array(
                0 => $langs->trans('NorProspectNorCustomer'),
                1 => $langs->trans('Customer'),
                2 => $langs->trans('Prospect'),
                3 => $langs->trans('ProspectCustomer')
            ), $this->object->client);

            // Customer
            $this->tpl['customercode'] = $this->object->code_client;
            if ((!$this->object->code_client || $this->object->code_client == -1) && $modCodeClient->code_auto) $this->tpl['customercode'] = $modCodeClient->getNextValue($this->object,0);
            $this->tpl['ismodifiable_customercode'] = $this->object->codeclient_modifiable();
            $s=$modCodeClient->getToolTip($langs,$this->object,0);
            $this->tpl['help_customercode'] = $form->textwithpicto('',$s,1);

            if (! empty($conf->fournisseur->enabled))
            {
            	$this->tpl['supplier_enabled'] = 1;

            	// Load object modCodeFournisseur
            	$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
            	if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            	{
            		$module = substr($module, 0, dol_strlen($module)-4);
            	}
                $dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
                foreach ($dirsociete as $dirroot)
                {
                    $res=dol_include_once($dirroot.$module.'.php');
                    if ($res) break;
                }
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
            }

            // Zip
            $this->tpl['select_zip'] = $formcompany->select_ziptown($this->object->zip,'zipcode',array('town','selectcountry_id','state_id'),6);

            // Town
            $this->tpl['select_town'] = $formcompany->select_ziptown($this->object->town,'town',array('zipcode','selectcountry_id','state_id'));

            // Country
            $this->object->country_id = ($this->object->country_id ? $this->object->country_id : $mysoc->country_id);
            $this->object->country_code = ($this->object->country_code ? $this->object->country_code : $mysoc->country_code);
            $this->tpl['select_country'] = $form->select_country($this->object->country_id,'country_id');
            $countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

            // State
            if ($this->object->country_id) $this->tpl['select_state'] = $formcompany->select_state($this->object->state_id,$this->object->country_code);
            else $this->tpl['select_state'] = $countrynotdefined;

            // Language
            if (! empty($conf->global->MAIN_MULTILANGS)) $this->tpl['select_lang'] = $formadmin->select_language(($this->object->default_lang?$this->object->default_lang:$conf->global->MAIN_LANG_DEFAULT),'default_lang',0,0,1);

            // VAT
            $this->tpl['yn_assujtva'] = $form->selectyesno('assujtva_value',$this->tpl['tva_assuj'],1);	// Assujeti par defaut en creation

            // Select users
            $this->tpl['select_users'] = $form->select_dolusers($this->object->commercial_id, 'commercial_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');

            // Local Tax
            // TODO mettre dans une classe propre au pays
            if($mysoc->country_code=='ES')
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

            $this->tpl['showhead']=dol_get_fiche_head($head, 'card', '', 0, 'company');
            $this->tpl['showend']=dol_get_fiche_end();

            $this->tpl['showrefnav'] 		= $form->showrefnav($this->object,'socid','',($user->societe_id?0:1),'rowid','nom');

            $this->tpl['checkcustomercode'] = $this->object->check_codeclient();
            $this->tpl['checksuppliercode'] = $this->object->check_codefournisseur();
            $this->tpl['address'] 			= dol_nl2br($this->object->address);

            $img=picto_from_langcode($this->object->country_code);
            if ($this->object->isInEEC()) $this->tpl['country'] = $form->textwithpicto(($img?$img.' ':'').$this->object->country,$langs->trans("CountryIsInEEC"),1,0);
            $this->tpl['country'] = ($img?$img.' ':'').$this->object->country;

            $this->tpl['phone'] 	= dol_print_phone($this->object->phone,$this->object->country_code,0,$this->object->id,'AC_TEL');
            $this->tpl['fax'] 		= dol_print_phone($this->object->fax,$this->object->country_code,0,$this->object->id,'AC_FAX');
            $this->tpl['email'] 	= dol_print_email($this->object->email,0,$this->object->id,'AC_EMAIL');
            $this->tpl['url'] 		= dol_print_url($this->object->url);

            $this->tpl['tva_assuj']		= yn($this->object->tva_assuj);

            // Third party type
            $arr = $formcompany->typent_array(1);
            $this->tpl['typent'] = $arr[$this->object->typent_code];

            if (! empty($conf->global->MAIN_MULTILANGS))
            {
                require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
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
            	$userstatic=new User($this->db);
            	$i=0;
            	foreach($listsalesrepresentatives as $val)
            	{
            		$userstatic->id=$val['id'];
            		$userstatic->lastname=$val['name'];
            		$userstatic->firstname=$val['firstname'];
            		$this->tpl['sales_representatives'].= $userstatic->getNomUrl(1);
            		$i++;
            		if ($i < $nbofsalesrepresentative) $this->tpl['sales_representatives'].= ', ';
            	}
            }
            else $this->tpl['sales_representatives'].= $langs->trans("NoSalesRepresentativeAffected");

            // Linked member
            if (! empty($conf->adherent->enabled))
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
                    $this->tpl['linked_member'] = $langs->trans("ThirdpartyNotLinkedToMember");
                }
            }

            // Local Tax
            // TODO mettre dans une classe propre au pays
            if($mysoc->country_code=='ES')
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
        $this->object->country_id			=	$_POST["country_id"]?$_POST["country_id"]:$mysoc->country_id;
        $this->object->state_id		        =	$_POST["state_id"];
        $this->object->phone					=	$_POST["tel"];
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
        $this->object->barcode				=	$_POST["barcode"];
        $this->object->forme_juridique_code	=	$_POST["forme_juridique_code"];
        $this->object->default_lang			=	$_POST["default_lang"];
        $this->object->commercial_id		=	$_POST["commercial_id"];

        $this->object->tva_assuj 			= 	$_POST["assujtva_value"]?$_POST["assujtva_value"]:1;
        $this->object->tva_intra			=	$_POST["tva_intra"];

        //Local Taxes
        $this->object->localtax1_assuj		= 	$_POST["localtax1assuj_value"];
        $this->object->localtax2_assuj		= 	$_POST["localtax2assuj_value"];

        // We set country_id, and country_code label of the chosen country
        if ($this->object->country_id)
        {
            $tmparray=getCountry($this->object->country_id,'all',$this->db,$langs,0);
            $this->object->country_code	=	$tmparray['code'];
            $this->object->country_label=	$tmparray['label'];
        }
    }

}
