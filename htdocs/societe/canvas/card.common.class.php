<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/societe/canvas/card.common.class.php
 *	\ingroup    thirparty
 *	\brief      Fichier de la classe Thirdparty card controller (common)
 *	\version    $Id$
 */

/**
 *	\class      CardCommon
 *	\brief      Classe permettant la gestion des tiers par defaut
 */
class CardCommon
{
	var $db;
	
	//! Numero d'erreur Plage 1280-1535
	var $errno = 0;
	//! Template container
	var $tpl = array();
	//! Object container
	var $object;
	//! Canvas
	var $canvas;

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function CardCommon($DB)
	{
		$this->db = $DB;
	}
	
    /**
     *    Assigne les valeurs par defaut pour le canvas
     *    @param      action     Type of template
     */
    function assign_values($action='')
    {
        global $conf, $langs, $user, $mysoc, $canvas;
        global $form, $formadmin, $formcompany;

        if ($_GET["type"]=='f')  		{ $this->object->fournisseur=1; }
        if ($_GET["type"]=='c')  		{ $this->object->client=1; }
        if ($_GET["type"]=='p')  		{ $this->object->client=2; }
        if ($_GET["type"]=='cp') 		{ $this->object->client=3; }
        if ($_REQUEST["private"]==1) 	{ $this->object->particulier=1;	}

        foreach($this->object as $key => $value)
        {
            $this->tpl[$key] = $value;
        }

        if ($action == 'create' || $action == 'edit')
        {
        	// Chargement ajax
            $this->tpl['ajax_select_thirdpartytype'] = $this->ajax_selectThirdPartyType($canvas);
            $this->tpl['ajax_select_country'] = $this->ajax_selectCountry($action,$canvas);

            // Load object modCodeClient
            $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
            if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
            if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            {
                $module = substr($module, 0, dol_strlen($module)-4);
            }
            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
            $modCodeClient = new $module;
            $this->tpl['auto_customercode'] = $modCodeClient->code_auto;
            // We verified if the tag prefix is used
            if ($modCodeClient->code_auto) $this->tpl['prefix_customercode'] = $modCodeClient->verif_prefixIsUsed();

            // Load object modCodeFournisseur
            $module=$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
            if (! $module) $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
            if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
            {
                $module = substr($module, 0, dol_strlen($module)-4);
            }
            require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
            $modCodeFournisseur = new $module;
            $this->tpl['auto_suppliercode'] = $modCodeFournisseur->code_auto;
            // We verified if the tag prefix is used
            if ($modCodeFournisseur->code_auto) $this->tpl['prefix_suppliercode'] = $modCodeFournisseur->verif_prefixIsUsed();

            // TODO create a function
            $this->tpl['select_customertype'] = '<select class="flat" name="client">';
            $this->tpl['select_customertype'].= '<option value="2"'.($this->object->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
            $this->tpl['select_customertype'].= '<option value="3"'.($this->object->client==3?' selected="true"':'').'>'.$langs->trans('ProspectCustomer').'</option>';
            $this->tpl['select_customertype'].= '<option value="1"'.($this->object->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
            $this->tpl['select_customertype'].= '<option value="0"'.($this->object->client==0?' selected="true"':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
            $this->tpl['select_customertype'].= '</select>';

            // Customer
            $this->tpl['customercode'] = $this->object->code_client;
            if ((!$this->object->code_client || $this->object->code_client == -1) && $modCodeClient->code_auto) $this->tpl['customercode'] = $modCodeClient->getNextValue($this->object,0);
            $this->tpl['ismodifiable_customercode'] = $this->object->codeclient_modifiable();
            $s=$modCodeClient->getToolTip($langs,$this->object,0);
            $this->tpl['help_customercode'] = $form->textwithpicto('',$s,1);

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

            if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) $this->tpl['autofilltownfromzip'] = '<input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(\''.DOL_URL_ROOT.'\',cp.value,ville,pays_id,departement_id)">';

            // Country
            $this->tpl['select_country'] = $form->select_country($this->object->pays_id,'pays_id');
            $countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

            if ($user->admin) $this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);

            // State
            if ($this->object->pays_id) $this->tpl['select_state'] = $formcompany->select_state($this->object->departement_id,$this->object->pays_code);
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

        if ($action == 'view')
        {
        	$this->tpl['showrefnav'] 		= $form->showrefnav($this->object,'socid','',($user->societe_id?0:1),'rowid','nom');

            $this->tpl['checkcustomercode'] = $this->object->check_codeclient();
            $this->tpl['checksuppliercode'] = $this->object->check_codefournisseur();
            $this->tpl['address'] 			= dol_nl2br($this->object->address);

            $img=picto_from_langcode($this->pays_code);
            if ($this->object->isInEEC()) $this->tpl['country'] = $form->textwithpicto(($img?$img.' ':'').$this->object->pays,$langs->trans("CountryIsInEEC"),1,0);
            $this->tpl['country'] = ($img?$img.' ':'').$this->pays;

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
                require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
                //$s=picto_from_langcode($this->default_lang);
                //print ($s?$s.' ':'');
                $langs->load("languages");
                $this->tpl['default_lang'] = ($this->default_lang?$langs->trans('Language_'.$this->object->default_lang):'');
            }

            $this->tpl['image_edit']	= img_edit();

            $this->tpl['display_rib']	= $this->object->display_rib();

            // Sales representatives
            // TODO move in business class
            $sql = "SELECT count(sc.rowid) as nb";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE sc.fk_soc =".$this->object->id;
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $num = $this->db->num_rows($resql);
                $obj = $this->db->fetch_object($resql);
                $this->tpl['sales_representatives'] = $obj->nb?($obj->nb):$langs->trans("NoSalesRepresentativeAffected");
            }
            else
            {
                dol_print_error($this->db);
            }

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
     *    Assigne les valeurs POST dans l'objet
     */
    function assign_post()
    {
        global $langs, $mysoc;

        $this->object->id					=	$_POST["socid"];
        $this->object->nom					=	$_POST["nom"];
        $this->object->prefix_comm			=	$_POST["prefix_comm"];
        $this->object->client				=	$_POST["client"];
        $this->object->code_client			=	$_POST["code_client"];
        $this->object->fournisseur			=	$_POST["fournisseur"];
        $this->object->code_fournisseur		=	$_POST["code_fournisseur"];
        $this->object->adresse				=	$_POST["adresse"]; // TODO obsolete
        $this->object->address				=	$_POST["adresse"];
        $this->object->cp					=	$_POST["cp"];
        $this->object->ville				=	$_POST["ville"];
        $this->object->pays_id				=	$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->pays_id;
        $this->object->departement_id		=	$_POST["departement_id"];
        $this->object->tel					=	$_POST["tel"];
        $this->object->fax					=	$_POST["fax"];
        $this->object->email				=	$_POST["email"];
        $this->object->url					=	$_POST["url"];
        $this->object->capital				=	$_POST["capital"];
        $this->object->siren				=	$_POST["idprof1"];
        $this->object->siret				=	$_POST["idprof2"];
        $this->object->ape					=	$_POST["idprof3"];
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
        // TODO move in business class
        if ($this->object->pays_id)
        {
            $sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_pays WHERE rowid = ".$this->object->pays_id;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj = $this->db->fetch_object($resql);
            }
            else
            {
                dol_print_error($this->db);
            }
            $this->object->pays_code	=	$obj->code;
            $this->object->pays			=	$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
        }
    }
    
    /**
     *    Load data control
     */
    function loadControl($socid)
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
    	&& ($_POST["action"] == 'add' || $_POST["action"] == 'update') && $user->rights->societe->creer)
    	{
    		require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    		$error=0;
    		
    		if ($_POST["action"] == 'update')
    		{	
    			// Load properties of company
    			$this->object->fetch($socid);
    		}
    		
    		if ($_REQUEST["private"] == 1)
    		{
    			$this->object->particulier           = $_REQUEST["private"];
    			
    			$this->object->nom                   = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($_POST["prenom"].' '.$_POST["nom"]):trim($_POST["nom"].' '.$_POST["prenom"]);
    			$this->object->nom_particulier       = $_POST["nom"];
    			$this->object->prenom                = $_POST["prenom"];
    			$this->object->civilite_id           = $_POST["civilite_id"];
    		}
    		else
    		{
    			$this->object->nom                   = $_POST["nom"];
    		}
    		
    		$this->object->address					= $_POST["adresse"];
    		$this->object->adresse					= $_POST["adresse"]; // TODO obsolete
    		$this->object->cp						= $_POST["cp"];
    		$this->object->ville					= $_POST["ville"];
    		$this->object->pays_id					= $_POST["pays_id"];
    		$this->object->departement_id			= $_POST["departement_id"];
    		$this->object->tel						= $_POST["tel"];
    		$this->object->fax						= $_POST["fax"];
    		$this->object->email					= trim($_POST["email"]);
    		$this->object->url						= $_POST["url"];
    		$this->object->siren					= $_POST["idprof1"];
    		$this->object->siret					= $_POST["idprof2"];
    		$this->object->ape 						= $_POST["idprof3"];
    		$this->object->idprof4 					= $_POST["idprof4"];
    		$this->object->prefix_comm				= $_POST["prefix_comm"];
    		$this->object->code_client				= $_POST["code_client"];
    		$this->object->code_fournisseur			= $_POST["code_fournisseur"];
    		$this->object->capital					= $_POST["capital"];
    		$this->object->gencod					= $_POST["gencod"];
    		$this->object->canvas					= $_REQUEST["canvas"];

        	$this->object->tva_assuj				= $_POST["assujtva_value"];
        	
        	// Local Taxes
        	$this->object->localtax1_assuj			= $_POST["localtax1assuj_value"];
        	$this->object->localtax2_assuj			= $_POST["localtax2assuj_value"];
        	$this->object->tva_intra				= $_POST["tva_intra"];
        	
        	$this->object->forme_juridique_code  	= $_POST["forme_juridique_code"];
        	$this->object->effectif_id           	= $_POST["effectif_id"];
        	if ($_REQUEST["private"] == 1)
        	{
        		$this->object->typent_id			= 8; // TODO predict another method if the field "special" change of rowid
        	}
        	else
        	{
        		$this->object->typent_id			= $_POST["typent_id"];
        	}
        	$this->object->client					= $_POST["client"];
        	$this->object->fournisseur				= $_POST["fournisseur"];
        	$this->object->fournisseur_categorie 	= $_POST["fournisseur_categorie"];
        	
        	$this->object->commercial_id			= $_POST["commercial_id"];
        	$this->object->default_lang				= $_POST["default_lang"];
        	
        	// Check parameters
        	if (empty($_POST["cancel"]))
        	{
        		if (! empty($this->object->email) && ! isValidEMail($this->object->email))
        		{
        			$error = 1;
        			$langs->load("errors");
        			$this->object->error = $langs->trans("ErrorBadEMail",$this->object->email);
        			$_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        		}
        		if (! empty($this->object->url) && ! isValidUrl($this->object->url))
        		{
        			$error = 1;
        			$langs->load("errors");
        			$this->object->error = $langs->trans("ErrorBadUrl",$this->object->url);
        			$_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        		}
        		if ($this->object->fournisseur && ! $conf->fournisseur->enabled)
        		{
        			$error = 1;
        			$langs->load("errors");
        			$this->object->error = $langs->trans("ErrorSupplierModuleNotEnabled");
        			$_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        		}
        	}
        	
        	if (! $error)
        	{
        		if ($_POST["action"] == 'add')
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
        					
        					$contact->civilite_id 	= $this->object->civilite_id;
        					$contact->name			= $this->object->nom_particulier;
        					$contact->firstname		= $this->object->prenom;
        					$contact->address		= $this->object->address;
        					$contact->cp			= $this->object->cp;
        					$contact->ville			= $this->object->ville;
        					$contact->fk_pays		= $this->object->fk_pays;
        					$contact->socid			= $this->object->id;				// fk_soc
        					$contact->status		= 1;
        					$contact->email			= $this->object->email;
        					$contact->priv			= 0;
        					
        					$result=$contact->create($user);
        				}
        			}
        			else
        			{
        				$mesg=$this->object->error;
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
        				
        				$langs->load("errors");
        				$mesg=$langs->trans($this->object->error);
        				$_GET["action"]='create';
        			}
        		}
        		
        		if ($_POST["action"] == 'update')
        		{
        			if ($_POST["cancel"])
        			{
        				Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
        				exit;
        			}
        			
        			$oldsoccanvas = new Canvas($this->db);
        			$oldsoccanvas->getCanvas('thirparty','card',$this->canvas);
        			$result=$oldsoccanvas->fetch($socid);
        			
        			// To not set code if third party is not concerned. But if it had values, we keep them.
        			if (empty($this->object->client) && empty($oldsoccanvas->control->object->code_client))				$this->object->code_client='';
        			if (empty($this->object->fournisseur)&& empty($oldsoccanvas->control->object->code_fournisseur)) 	$this->object->code_fournisseur='';
        			//var_dump($soccanvas);exit;
        			
        			$result = $this->object->update($socid,$user,1,$oldsoccanvas->control->object->codeclient_modifiable(),$oldsoccanvas->control->object->codefournisseur_modifiable());
        			if ($result >= 0)
        			{
        				Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
        				exit;
        			}
        			else
        			{
        				$this->object->id = $socid;
        				$reload = 0;
        				
        				$mesg = $this->object->error;
        				$_GET["action"]= "edit";
        			}
        		}
        	}
        }
        
        if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes' && $user->rights->societe->supprimer)
        {
        	$this->object->fetch($socid);

        	$result = $this->object->delete($socid);
        	
        	if ($result >= 0)
        	{
        		Header("Location: ".DOL_URL_ROOT."/societe/societe.php?delsoc=".$this->object->nom."");
        	    exit;
        	}
        	else
        	{
        		$reload = 0;
        		$langs->load("errors");
        		$mesg=$langs->trans($this->object->error);
        		$_GET["action"]='';
        	}
        }
        
        /*
         * Generate document
         */
        if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
        {
        	if (is_numeric($_REQUEST['model']))
        	{
        		$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Model"));
        	}
        	else
        	{
        		require_once(DOL_DOCUMENT_ROOT.'/includes/modules/societe/modules_societe.class.php');
        		
        		$this->object->fetch($socid);
        		$this->object->fetch_thirdparty();
        		
            	/*if ($_REQUEST['model'])
             	{
             		$fac->setDocModel($user, $_REQUEST['model']);
             	}
             	*/
        		
        		// Define output language
        		$outputlangs = $langs;
        		$newlang='';
        		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
        		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$this->object->default_lang;
        		if (! empty($newlang))
        		{
        			$outputlangs = new Translate("",$conf);
        			$outputlangs->setDefaultLang($newlang);
        		}
        		$result=thirdparty_doc_create($this->db, $this->object->id, '', $_REQUEST['model'], $outputlangs);
        		if ($result <= 0)
        		{
        			dol_print_error($this->db,$result);
        			exit;
        		}
        		else
        		{
        			Header ('Location: '.$_SERVER["PHP_SELF"].'?socid='.$this->object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        			exit;
        		}
        	}
        }
    }

    /**
     *
     */
    function ajax_selectThirdPartyType($canvas)
    {
        global $conf, $langs;

        $out='';

        if ($conf->use_javascript_ajax)
        {
            $out.= "\n".'<script type="text/javascript" language="javascript">'."\n";
            $out.= 'jQuery(document).ready(function () {
		              jQuery("#radiocompany").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="'.$canvas.'";
                            document.formsoc.private.value=0;
                            document.formsoc.submit();
		              });
		               jQuery("#radioprivate").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="'.$canvas.'";
                            document.formsoc.private.value=1;
                            document.formsoc.submit();
                      });
		          });';
            $out.= '</script>'."\n";

            $out.= "<br>\n";
            $out.= $langs->trans("ThirdPartyType").': &nbsp; ';
            $out.= '<input type="radio" id="radiocompany" class="flat" name="private" value="0"'.(! $_REQUEST["private"]?' checked="true"':'');
            $out.= '> '.$langs->trans("Company/Fundation");
            $out.= ' &nbsp; &nbsp; ';
            $out.= '<input type="radio" id="radioprivate" class="flat" name="private" value="1"'.(! $_REQUEST["private"]?'':' checked="true"');
            $out.= '> '.$langs->trans("Individual");
            $out.= ' ('.$langs->trans("ToCreateContactWithSameName").')';
            $out.= "<br>\n";
            $out.= "<br>\n";
        }

        return $out;
    }

    /**
     *
     */
    function ajax_selectCountry($action,$canvas)
    {
        global $conf;

        $out='';

        if ($conf->use_javascript_ajax)
        {
            $out.= "\n".'<script type="text/javascript" language="javascript">'."\n";
            $out.= 'jQuery(document).ready(function () {
                        jQuery("#selectpays_id").change(function() {
                            document.formsoc.action.value="'.$action.'";
                            document.formsoc.canvas.value="'.$canvas.'";
                            document.formsoc.submit();
                        });
                   })';
            $out.= '</script>'."\n";
        }

        return $out;
    }

}

?>