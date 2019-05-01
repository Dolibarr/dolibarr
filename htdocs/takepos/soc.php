<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2013  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019		JC Prieto				<jcprieto@virtual20.com>
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
 *  \file       htdocs/tpvcommande/societe/soc.php
 *  \ingroup    societe
 *  \brief      Third party card page
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
	if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
	
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/takepos/lib/takepos.lib.php';	//V20

$langs->load('takepos@takepos');

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");
if (! empty($conf->categorie->enabled)) $langs->load("categories");
if (! empty($conf->incoterm->enabled)) $langs->load("incoterm");
if (! empty($conf->notification->enabled)) $langs->load("mails");

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action') ? GETPOST('action') : 'view');
$cancel     = GETPOST('cancel');
$backtopage = GETPOST('backtopage','alpha');
$confirm	= GETPOST('confirm');
$socid		= GETPOST('socid','int');
//V20:
$orderid	= GETPOST('orderid', 'int');
$prospect	= GETPOST('type','alpha');	

if ($user->societe_id) $socid=$user->societe_id;
if (empty($socid) && $action == 'view') $action='create';

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('thirdpartycard','globalcard'));


// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($socid);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('thirdparty', 'card', $canvas);
}


$place = GETPOST('place', 'int');
if ($place=="") $place="0";
$idcustomer = GETPOST('idcustomer', 'int');
$_GET['optioncss'] = 'print';


/*
 * Actions
 */

if ($action=="cancel") {
    
    ?>
    <script>
    parent.$.colorbox.close();
    parent.$("#poslines").load("invoice.php?place="+<?php print $place;?>, function() {
        parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
    });
    </script>
    <?php
    exit;
}

if ($action=="new") {
    $sql="UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$idcustomer." where facnumber='(PROV-POS-".$place.")'";
    $resql = $db->query($sql);
    load_ticket($place,$facid);
   
    ?>
    <script>
    parent.$.colorbox.close();
    parent.$("#poslines").load("invoice.php?place="+<?php print $place;?>, function() {
        parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
    });
    </script>
    <?php
    exit;
}


// Security check
//V20:$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);


/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if($action=='Anular')		header('Location: soc.php?action=cancel');	//V20
	
    if ($cancel)
    {
        $action='';
        if (! empty($backtopage))
        {
            header("Location: ".$backtopage);
            exit;
        }    
    }

	if ($action == 'confirm_merge' && $confirm == 'yes')
	{
		$object->fetch($socid);

		$errors = 0;
		$soc_origin_id = GETPOST('soc_origin', 'int');
		$soc_origin = new Societe($db);

		if ($soc_origin_id < 1)
		{
			$langs->load('errors');
			$langs->load('companies');
			setEventMessages($langs->trans('ErrorProdIdIsMandatory', $langs->trans('MergeOriginThirdparty')), null, 'errors');
		}
		else
		{

			if (!$errors && $soc_origin->fetch($soc_origin_id) < 1)
			{
				setEventMessages($langs->trans('ErrorRecordNotFound'), null, 'errors');
				$errors++;
			}

			if (!$errors)
			{
				$db->begin();

				$objects = array(
					'Adherent' => '/adherents/class/adherent.class.php',
					'Societe' => '/societe/class/societe.class.php',
					'Bookmark' => '/bookmarks/class/bookmark.class.php',
					'Categorie' => '/categories/class/categorie.class.php',
					'ActionComm' => '/comm/action/class/actioncomm.class.php',
					'Propal' => '/comm/propal/class/propal.class.php',
					'Commande' => '/commande/class/commande.class.php',
					'Facture' => '/compta/facture/class/facture.class.php',
					'FactureRec' => '/compta/facture/class/facture-rec.class.php',
					'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
					'Contact' => '/contact/class/contact.class.php',
					'Contrat' => '/contrat/class/contrat.class.php',
					'Expedition' => '/expedition/class/expedition.class.php',
					'Fichinter' => '/fichinter/class/fichinter.class.php',
					'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
					'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
					'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
					'Livraison' => '/livraison/class/livraison.class.php',
					'Product' => '/product/class/product.class.php',
					'Project' => '/projet/class/project.class.php',
					'User' => '/user/class/user.class.php',
				);

				//First, all core objects must update their tables
				foreach ($objects as $object_name => $object_file)
				{
					require_once DOL_DOCUMENT_ROOT.$object_file;

					if (!$errors && !$object_name::replaceThirdparty($db, $soc_origin->id, $object->id))
					{
						$errors++;
						setEventMessages($db->lasterror(), null, 'errors');
					}
				}

				//External modules should update their ones too
				if (!$errors)
				{
					$reshook = $hookmanager->executeHooks('replaceThirdparty', array(
						'soc_origin' => $soc_origin->id,
						'soc_dest' => $object->id
					), $soc_dest, $action);

					if ($reshook < 0)
					{
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						$errors++;
					}
				}

				if (!$errors)
				{
					//We finally remove the old thirdparty
					if ($soc_origin->delete($soc_origin->id, $user) < 1)
					{
						$errors++;
					}
				}

				if (!$errors)
				{
					setEventMessages($langs->trans('ThirdpartiesMergeSuccess'), null, 'mesgs');
					$db->commit();
				} 
				else 
				{
				    $langs->load("errors");
					setEventMessages($langs->trans('ErrorsThirdpartyMerge'), null, 'errors');
					$db->rollback();
				}
			}
		}
	}

    if (GETPOST('getcustomercode'))
    {
        // We defined value code_client
        $_POST["code_client"]="Acompleter";
    }

    if (GETPOST('getsuppliercode'))
    {
        // We defined value code_fournisseur
        $_POST["code_fournisseur"]="Acompleter";
    }

    if($action=='set_localtax1')
    {
    	//obtidre selected del combobox
    	$value=GETPOST('lt1');
    	$object->fetch($socid);
    	$res=$object->setValueFrom('localtax1_value', $value);
    }
    if($action=='set_localtax2')
    {
    	//obtidre selected del combobox
    	$value=GETPOST('lt2');
    	$object->fetch($socid);
    	$res=$object->setValueFrom('localtax2_value', $value);
    }

    // Add new or update third party
    if ((! GETPOST('getcustomercode') && ! GETPOST('getsuppliercode')) && ($action == 'add' || $action == 'update') && $user->rights->societe->creer)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        if (! GETPOST('name'))
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdPartyName")), null, 'errors');
            $error++;
            $action='create';
        }
        if (GETPOST('client') < 0)
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProspectCustomer")), null, 'errors');
            $error++;
            $action='create';
        }
        if (GETPOST('fournisseur') < 0)
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
            $error++;
            $action='create';
        }
        
        
        if ($action == 'update')
        {
        	$ret=$object->fetch($socid);
			$object->oldcopy = clone $object;
        }
		else $object->canvas=$canvas;

//V20: Edit and creation
//Only checking of parameters used.

        if (GETPOST("private") == 1)
        {
            $object->particulier       = GETPOST("private");

            $object->name              = dolGetFirstLastname(GETPOST('firstname','alpha'),GETPOST('name','alpha'));
            $object->civility_id       = GETPOST('civility_id');	// Note: civility id is a code, not an int
            // Add non official properties
            $object->name_bis          = GETPOST('name','alpha');
            $object->firstname         = GETPOST('firstname','alpha');
        }
        else
        {
            $object->name              = GETPOST('name', 'alpha');
	        $object->name_alias   = GETPOST('name_alias');
        }
		
        $object->address               = GETPOST('address');
        $object->zip                   = GETPOST('zipcode', 'alpha');
        $object->town                  = GETPOST('town', 'alpha');
        $object->country_id            = GETPOST('country_id', 'int');
        $object->state_id              = GETPOST('state_id', 'int');
        //$object->skype                 = GETPOST('skype', 'alpha');
        $object->phone                 = GETPOST('phone', 'alpha');
        $object->fax                   = GETPOST('fax','alpha');
        $object->email                 = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        //$object->url                   = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->idprof1               = GETPOST('idprof1', 'alpha');
        //$object->idprof2               = GETPOST('idprof2', 'alpha');
        //$object->idprof3               = GETPOST('idprof3', 'alpha');
        //$object->idprof4               = GETPOST('idprof4', 'alpha');
        //$object->idprof5               = GETPOST('idprof5', 'alpha');
        //$object->idprof6               = GETPOST('idprof6', 'alpha');
        //$object->prefix_comm           = GETPOST('prefix_comm', 'alpha');
        $object->code_client           = GETPOST('code_client', 'alpha');
        //$object->code_fournisseur      = GETPOST('code_fournisseur', 'alpha');
        //$object->capital               = GETPOST('capital', 'alpha');
        //$object->barcode               BRUJO HOSTELERIA S.L= GETPOST('barcode', 'alpha');

        //$object->tva_intra             = GETPOST('tva_intra', 'alpha');
        //$object->tva_assuj             = GETPOST('assujtva_value', 'alpha');
        //$object->status                = GETPOST('status', 'alpha');

        // Local Taxes
        //$object->localtax1_assuj       = GETPOST('localtax1assuj_value', 'alpha');
        //$object->localtax2_assuj       = GETPOST('localtax2assuj_value', 'alpha');

        //$object->localtax1_value	   = GETPOST('lt1', 'alpha');
        //$object->localtax2_value	   = GETPOST('lt2', 'alpha');

        $object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
        //$object->effectif_id           = GETPOST('effectif_id', 'int');
        //$object->typent_id             = GETPOST('typent_id');

        $object->client                = GETPOST('client', 'int');
        //$object->fournisseur           = GETPOST('fournisseur', 'int');

        $object->commercial_id         = GETPOST('commercial_id', 'int');
        //$object->default_lang          = GETPOST('default_lang');

        // Webservices url/key
        //$object->webservices_url       = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
        //$object->webservices_key       = GETPOST('webservices_key', 'san_alpha');

        $object->mode_reglement_id       = GETPOST('mode_reglement_id','int');	//v20
        
		

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			$object->fk_incoterms 		   = GETPOST('incoterm_id', 'int');
			$object->location_incoterms    = GETPOST('location_incoterms', 'alpha');
		}
		
		// Multicurrency
		if (!empty($conf->multicurrency->enabled))
		{
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
		}
		
        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0)
		{
			 $error++;
			 $action = ($action=='add'?'create':'edit');
		}

        if (GETPOST('deletephoto')) $object->logo = '';
        else if (! empty($_FILES['photo']['name'])) $object->logo = dol_sanitizeFileName($_FILES['photo']['name']);

        // Check parameters
        if (! GETPOST("cancel"))
        {
            if (! empty($object->email) && ! isValidEMail($object->email))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadEMail",$object->email);
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->url) && ! isValidUrl($object->url))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->url);
                $action = ($action=='add'?'create':'edit');
            }
            if ($object->fournisseur && ! $conf->fournisseur->enabled)
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorSupplierModuleNotEnabled");
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->webservices_url)) {
                //Check if has transport, without any the soap client will give error
                if (strpos($object->webservices_url, "http") === false)
                {
                    $object->webservices_url = "http://".$object->webservices_url;
                }
                if (! isValidUrl($object->webservices_url)) {
                    $langs->load("errors");
                    $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->webservices_url);
                    $action = ($action=='add'?'create':'edit');
                }
            }

            // We set country_id, country_code and country for the selected country
            $object->country_id=GETPOST('country_id')!=''?GETPOST('country_id'):$mysoc->country_id;
            if ($object->country_id)
            {
            	$tmparray=getCountry($object->country_id,'all');
            	$object->country_code=$tmparray['code'];
            	$object->country=$tmparray['label'];
            }
            
            //V20: Check mode pay
	        if ($prospect!='p' && empty($object->mode_reglement_id))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("Empty_mode_regl");
                $action = ($action=='add'?'create':'edit');
            }
         	//V20: Check CIF y NIF (idprof1)
         	if($prospect!='p')	//No prospect
         	{
		     	if (empty($object->idprof1))
		     	{
		        	setEventMessages($langs->trans("Empty_idprof"), null, 'errors');
		            $error++;
		            $action=($action=='add'?'create':'edit');
		        }
		        elseif ($object->id_prof_check(1,$object) <= 0)
	            {
		         	setEventMessages($langs->trans("Bad_idprof"), null, 'errors');
		            $error++;
		            $action=($action=='add'?'create':'edit');
		        }
	
	            // Check for duplicate or mandatory prof id
	            // Only for companies
		        if (!($object->particulier || $private))
	        	{
		        	for ($i = 1; $i <= 6; $i++)
		        	{
		        	    $slabel="idprof".$i;
		    			$_POST[$slabel]=trim($_POST[$slabel]);
		        	    $vallabel=$_POST[$slabel];
		        		if ($vallabel && $object->id_prof_verifiable($i))
						{
							if($object->id_prof_exists($i,$vallabel,$object->id))
							{
								$langs->load("errors");
		                		$error++; $errors[] = $langs->transcountry('ProfId'.$i, $object->country_code)." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel);
		                		$action = (($action=='add'||$action=='create')?'create':'edit');
							}
						}
	
	            		// Check for mandatory prof id (but only if country is than than ours)
						if ($mysoc->country_id > 0 && $object->country_id == $mysoc->country_id)
	            		{
	    					$idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
	    					if (! $vallabel && ! empty($conf->global->$idprof_mandatory))
	    					{
	    						$langs->load("errors");
	    						$error++;
	    						$errors[] = $langs->trans("ErrorProdIdIsMandatory", $langs->transcountry('ProfId'.$i, $object->country_code));
	    						$action = (($action=='add'||$action=='create')?'create':'edit');
	    					}
	            		}
		        	}
	        	}
         	}
        }

        if (! $error)
        {
            if ($action == 'add')
            {
                $db->begin();

               //V20: If no code we auto generate.
               if(empty($object->code_client))	$object->code_client='auto';
                
                if (empty($object->client))      $object->code_client='';
                if (empty($object->fournisseur)) $object->code_fournisseur='';

                $result = $object->create($user);
				if ($result >= 0)
                {
                    if ($object->particulier)
                    {
                        dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
                        $result=$object->create_individual($user);
                        if (! $result >= 0)
                        {
                            $error=$object->error; $errors=$object->errors;
                        }
                    }
                    
                    //V20: Bank
			        $account = new CompanyBankAccount($db);
			        $account->socid           = $object->id;
			        
			        $account->bank            = GETPOST("bank");
			        $account->label           = GETPOST("banklabel");
			        //$account->courant         = GETPOST("courant");
			        //$account->clos            = GETPOST("clos");
			        $account->code_banque     = GETPOST("code_banque");
			        $account->code_guichet    = GETPOST("code_guichet");
			        $account->number          = GETPOST("number");
			        $account->cle_rib         = GETPOST("cle_rib");
			        //$account->bic             = GETPOST("bic");
			        $account->iban            = GETPOST("iban");
			        //$account->domiciliation   = GETPOST("domiciliation"];
			        //$account->proprio         = GETPOST("proprio"];
			        //$account->owner_address   = GETPOST("owner_address"];
			        //$account->frstrecur       = GETPOST('frstrecur');
			        $account->datec			 = dol_now();
					$account->status          = 1;
					
			        //$db->begin();
			        $result = $account->create($user);
			        if ($result>0)	$result = $account->update($user);
			        if (! $result)                	setEventMessages($account->error, $account->errors, 'errors');
			        else	$db->commit();
                    
                 
					// Customer categories association
					$custcats = GETPOST( 'custcats', 'array' );
					$object->setCategories($custcats, 'customer');

					// Supplier categories association
					$suppcats = GETPOST('suppcats', 'array');
					$object->setCategories($suppcats, 'supplier');
					
					
                // Gestion du logo de la société
                }
                else
				{
					
					if($result == -3) {
						$duplicate_code_error = true;
						$object->code_fournisseur = null;
						$object->code_client = null;
					}
					
                    $error=$object->error; $errors=$object->errors;
                    
                    print var_dump($object->client);
                	print var_dump($object->code_client);
                    print var_dump($result);
                    print var_dump($errors);
                    print var_dump($duplicate_code_error);
                    print var_dump($prospect);
                    print var_dump($action);
                }

                if ($result >= 0)
                {
                    $db->commit();

                	if (! empty($backtopage))
                	{
               		    header("Location: ".$backtopage);
                    	exit;
                	}
                	else
                	{
						//V20: Go back
                		$url='soc.php?action=new&idcustomer='.$object->id.'&place='.$place;
                		
                		header("Location: ".$url);
                    	exit;
                	}
                }
                else
                {
                    $db->rollback();
                    $action='create';
                }
            }

            if ($action == 'update')
            {
                if (GETPOST("cancel"))
                {
                	if (! empty($backtopage))
                	{
               		    header("Location: ".$backtopage);
                    	exit;
                	}
                	else
                	{
               		    header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                    	exit;
                	}
                }

                // To not set code if third party is not concerned. But if it had values, we keep them.
                if (empty($object->client) && empty($object->oldcopy->code_client))          $object->code_client='';
                if (empty($object->fournisseur)&& empty($object->oldcopy->code_fournisseur)) $object->code_fournisseur='';
                //var_dump($object);exit;

                $result = $object->update($socid, $user, 1, $object->oldcopy->codeclient_modifiable(), $object->oldcopy->codefournisseur_modifiable(), 'update', 0);
                if ($result <=  0)
                {
                    $error = $object->error; $errors = $object->errors;
                }

				// Customer categories association
				$categories = GETPOST( 'custcats', 'array' );
				$object->setCategories($categories, 'customer');

				// Supplier categories association
				$categories = GETPOST('suppcats', 'array');
				$object->setCategories($categories, 'supplier');

                // Logo/Photo save
                $dir     = $conf->societe->multidir_output[$object->entity]."/".$object->id."/logos";
                $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
                if (GETPOST('deletephoto') && $object->photo)
                {
                    $fileimg=$dir.'/'.$object->logo;
                    $dirthumbs=$dir.'/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }
                if ($file_OK)
                {
                    if (image_format_supported($_FILES['photo']['name']) > 0)
                    {
                        dol_mkdir($dir);

                        if (@is_dir($dir))
                        {
                            $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                            $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                            if (! $result > 0)
                            {
                                $errors[] = "ErrorFailedToSaveFile";
                            }
                            else
                            {
                                // Create small thumbs for company (Ratio is near 16/9)
                                // Used on logon for example
                                $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                                // Create mini thumbs for company (Ratio is near 16/9)
                                // Used on menu or for setup page for example
                                $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                            }
                        }
                    }
                    else
					{
                        $errors[] = "ErrorBadImageFormat";
                    }
                }
                else
              {
					switch($_FILES['photo']['error'])
					{
					    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
					    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
					      $errors[] = "ErrorFileSizeTooLarge";
					      break;
      					case 3: //uploaded file was only partially uploaded
					      $errors[] = "ErrorFilePartiallyUploaded";
					      break;
					}
                }
                // Gestion du logo de la société


                // Update linked member
                if (! $error && $object->fk_soc > 0)
                {

                	$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
                	$sql.= " SET fk_soc = NULL WHERE fk_soc = " . $id;
                	if (! $object->db->query($sql))
                	{
                		$error++;
                		$object->error .= $object->db->lasterror();
                	}
                }

                if (! $error && ! count($errors))
                {
                    if (! empty($backtopage))
                	{
               		    header("Location: ".$backtopage);
                    	exit;
                	}
                	else
                	{
               		    header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                    	exit;
                	}
                }
                else
                {
                    $object->id = $socid;
                    $action= "edit";
                }
            }
        }
    }

    // Delete third party
    if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->societe->supprimer)
    {
        $object->fetch($socid);
        $result = $object->delete($socid, $user);

        if ($result > 0)
        {
            header("Location: ".DOL_URL_ROOT."/societe/list.php?delsoc=".urlencode($object->name));
            exit;
        }
        else
        {
            $langs->load("errors");
            $error=$langs->trans($object->error); $errors = $object->errors;
            $action='';
        }
    }

    // Set parent company
    if ($action == 'set_thirdparty' && $user->rights->societe->creer)
    {
    	$object->fetch($socid);
    	$result = $object->set_parent(GETPOST('editparentcompany','int'));
    }

    // Set incoterm
    if ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
    {
    	$object->fetch($socid);
    	$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
    }

    // Actions to send emails
    $id=$socid;
    $actiontypecode='AC_OTH_AUTO';
    $trigger_name='COMPANY_SENTBYMAIL';
    $paramname='socid';
    $mode='emailfromthirdparty';
    include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

    // Actions to build doc
    $id = $socid;
    $upload_dir = $conf->societe->dir_output;
    $permissioncreate=$user->rights->societe->creer;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}



/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

if ($socid > 0 && empty($object->id))
{
    $result=$object->fetch($socid);
	if ($result <= 0) dol_print_error('',$object->error);
}

$title=$langs->trans("ThirdParty");


$arrayofcss=array('/takepos/css/style.css');	//V20
top_htmlhead($head,$langs->trans("takepos"),0,0,$arrayofjs,$arrayofcss);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
   	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
    $objcanvas->display_canvas($action);							// Show template
}
else
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------
    if ($action == 'create')
    {
        /*
         *  Creation
         */
		$private=GETPOST("private","int");
		if (! empty($conf->global->MAIN_THIRDPARTY_CREATION_INDIVIDUAL) && ! isset($_GET['private']) && ! isset($_POST['private'])) $private=1;
    	if (empty($private)) $private=0;

        // Load object modCodeTiers
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
        $modCodeClient = new $module;
        // Load object modCodeFournisseur
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
        $modCodeFournisseur = new $module;

        // Define if customer/prospect or supplier status is set or not
        if (GETPOST("type")!='f')
        {
            $object->client=-1;
            if (! empty($conf->global->THIRDPARTY_CUSTOMERPROSPECT_BY_DEFAULT))  { $object->client=3; }
        }
        if (GETPOST("type")=='c')  { $object->client=3; }   // Customer
        if (GETPOST("type")=='p')  { $object->client=2; }	// Prospect
        if (! empty($conf->fournisseur->enabled) && (GETPOST("type")=='f' || (GETPOST("type")=='' && ! empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT))))  { $object->fournisseur=1; }

        $object->name				= GETPOST('name', 'alpha');
        $object->firstname			= GETPOST('firstname', 'alpha');
        $object->particulier		= $private;
        $object->prefix_comm		= GETPOST('prefix_comm');
        $object->client				= GETPOST('client')?GETPOST('client'):$object->client;
        
        if(empty($duplicate_code_error)) {
	        $object->code_client		= GETPOST('code_client', 'alpha');
	        $object->fournisseur		= GETPOST('fournisseur')?GETPOST('fournisseur'):$object->fournisseur;
        }		else {
			setEventMessages($langs->trans('NewCustomerSupplierCodeProposed'),'', 'warnings');
		}
		
        $object->code_fournisseur	= GETPOST('code_fournisseur', 'alpha');
        $object->address			= GETPOST('address', 'alpha');
        $object->zip				= GETPOST('zipcode', 'alpha');
        $object->town				= GETPOST('town', 'alpha');
        $object->state_id			= GETPOST('state_id', 'int');
        $object->skype				= GETPOST('skype', 'alpha');
        $object->phone				= GETPOST('phone', 'alpha');
        $object->fax				= GETPOST('fax', 'alpha');
        $object->email				= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        $object->url				= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->capital			= GETPOST('capital', 'alpha');
        $object->barcode			= GETPOST('barcode', 'alpha');
        $object->idprof1			= GETPOST('idprof1', 'alpha');
        $object->idprof2			= GETPOST('idprof2', 'alpha');
        $object->idprof3			= GETPOST('idprof3', 'alpha');
        $object->idprof4			= GETPOST('idprof4', 'alpha');
        $object->idprof5			= GETPOST('idprof5', 'alpha');
        $object->idprof6			= GETPOST('idprof6', 'alpha');
        $object->typent_id			= GETPOST('typent_id', 'int');
        $object->effectif_id		= GETPOST('effectif_id', 'int');
        $object->civility_id		= GETPOST('civility_id', 'int');

        $object->tva_assuj			= GETPOST('assujtva_value', 'int');
        $object->status				= GETPOST('status', 'int');

        //Local Taxes
        $object->localtax1_assuj	= GETPOST('localtax1assuj_value', 'int');
        $object->localtax2_assuj	= GETPOST('localtax2assuj_value', 'int');

        $object->localtax1_value	=GETPOST('lt1', 'int');
        $object->localtax2_value	=GETPOST('lt2', 'int');

        $object->tva_intra			= GETPOST('tva_intra', 'alpha');

        $object->commercial_id		= GETPOST('commercial_id', 'int');
        $object->default_lang		= GETPOST('default_lang');

        $object->logo = (isset($_FILES['photo'])?dol_sanitizeFileName($_FILES['photo']['name']):'');
        
        
        // Gestion du logo de la société
        $dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos";
        $file_OK = (isset($_FILES['photo'])?is_uploaded_file($_FILES['photo']['tmp_name']):false);
        if ($file_OK)
        {
            if (image_format_supported($_FILES['photo']['name']))
            {
                dol_mkdir($dir);

                if (@is_dir($dir))
                {
                    $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                    $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                    if (! $result > 0)
                    {
                        $errors[] = "ErrorFailedToSaveFile";
                    }
                    else
                    {
                        // Create thumbs
                        $object->addThumbs($newfile);
                    }
                }
            }
        }

        // We set country_id, country_code and country for the selected country
        $object->country_id=GETPOST('country_id')?GETPOST('country_id'):$mysoc->country_id;
        if ($object->country_id)
        {
            $tmparray=getCountry($object->country_id,'all');
            $object->country_code=$tmparray['code'];
            $object->country=$tmparray['label'];
        }
        $object->forme_juridique_code=GETPOST('forme_juridique_code');
        /* Show create form */

        $linkback=$user->login.' <br> '.$zona_comercial['name'];
        print load_fiche_titre($langs->trans("NewCustomer"),$linkback,'title_companies.png');

        if (! empty($conf->use_javascript_ajax))
        {
            print "\n".'<script type="text/javascript">';
            print '$(document).ready(function () {
						id_te_private=8;
                        id_ef15=1;
                        is_private='.$private.';
						if (is_private) {
							$(".individualline").show();
						} else {
							$(".individualline").hide();
						}
                        $("#radiocompany").click(function() {
                        	$(".individualline").hide();
                        	$("#typent_id").val(0);
							$("#name_alias").show();
                        	$("#effectif_id").val(0);
                        	$("#TypeName").html(document.formsoc.ThirdPartyName.value);
                        	document.formsoc.private.value=0;
                        });
                        $("#radioprivate").click(function() {
                        	$(".individualline").show();
                        	$("#typent_id").val(id_te_private);
							$("#name_alias").hide();
                        	$("#effectif_id").val(id_ef15);
                        	$("#TypeName").html(document.formsoc.LastName.value);
                        	document.formsoc.private.value=1;
                        });
                        $("#selectcountry_id").change(function() {
                        	document.formsoc.action.value="create";
                        	document.formsoc.submit();
                        });
                     });';
            print '</script>'."\n";

            
        }

        dol_htmloutput_mesg(is_numeric($error)?'':$error, $errors, 'error');

        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';
 		print '<input type="hidden" name="place" value="'.$place.'">';	//V20
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="private" value='.$object->particulier.'>';
        print '<input type="hidden" name="type" value='.GETPOST("type").'>';
        print '<input type="hidden" name="LastName" value="'.$langs->trans('LastName').'">';
        print '<input type="hidden" name="ThirdPartyName" value="'.$langs->trans('ThirdPartyName').'">';
        if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

        dol_fiche_head(null, 'card', '', 0, '');

        print '<table class="border" width="100%">';

        // Name, firstname
	    print '<tr><td class="titlefieldcreate">';
        if ($object->particulier || $private)
        {
	        print '<span id="TypeName" class="fieldrequired">'.$langs->trans('LastName','name').'</span>';
        }
        else
		{
			print '<span span id="TypeName" class="fieldrequired">'.fieldLabel('ThirdPartyName','name').'</span>';
        }
	    print '</td><td'.(empty($conf->global->SOCIETE_USEPREFIX)?' colspan="3"':'').'>';
	    print '<input type="text" size="60" maxlength="128" name="name" id="name" value="'.$object->name.'" autofocus="autofocus"></td>';
	    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	    {
		    print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" maxlength="5" name="prefix_comm" value="'.$object->prefix_comm.'"></td>';
	    }
	    print '</tr>';

	   
        // If javascript on, we show option individual
        if ($conf->use_javascript_ajax)
        {
            print '<tr class="individualline"><td>'.fieldLabel('FirstName','firstname').'</td>';
	        print '<td colspan="3"><input type="text" size="60" name="firstname" id="firstname" value="'.$object->firstname.'"></td>';
            print '</tr>';
            print '<tr class="individualline"><td>'.fieldLabel('UserTitle','civility_id').'</td><td colspan="3">';
            print $formcompany->select_civility($object->civility_id).'</td>';
            print '</tr>';
        }

        // Prospect/Customer
        //V20:
        if($prospect=='p')	print '<input type="hidden" name="client" value="2">';	//Prospect = Posible cliente
	    else{ 	
	    	print '<input type="hidden" name="client" value="1">';					// Customer =Cliente
		  
	 		//V20: Current customer
	        print '<input type="hidden" name="status" value="1">';
	
	        // Address
	        print '<tr><td class="tdtop">'.fieldLabel('Address','address').'</td>';
		    print '<td colspan="3"><textarea name="address" id="address" class="quatrevingtpercent" rows="'._ROWS_2.'" wrap="soft">';
	        print $object->address;
	        print '</textarea></td></tr>';
	
	        // Zip / Town
	        print '<tr><td>'.fieldLabel('Zip','zipcode').'</td><td>';
	        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','state_id'),6);
	        print '</td></tr>';

			print '<tr><td>'.fieldLabel('Town','town').'</td><td>';
	        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','state_id'),50);
	        print '</td></tr>';
	
	        // Country
	        print '<tr><td width="25%">'.fieldLabel('Country','selectcountry_id').'</td><td colspan="3" class="maxwidthonsmartphone">';
	        print $form->select_country((GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id));
	        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	        print '</td></tr>';
	
	        // State
	        if (empty($conf->global->SOCIETE_DISABLE_STATE))
	        {
	            print '<tr><td>'.fieldLabel('State','state_id').'</td><td colspan="3" class="maxwidthonsmartphone">';
	            if ($object->country_id) print $formcompany->select_state($object->state_id,$object->country_code);
	            else print $countrynotdefined;
	            print '</td></tr>';
	        }
	
	        // Email web
	        print '<tr><td>'.fieldLabel('EMail','email').(! empty($conf->global->SOCIETE_MAIL_REQUIRED)?'*':'').'</td>';
		    print '<td colspan="3"><input type="text" name="email" id="email" size="32" value="'.$object->email.'"></td></tr>';
	        
	        // Skype
	        if (! empty($conf->skype->enabled))
	        {
	            print '<tr><td>'.fieldLabel('Skype','skype').'</td>';
		        print '<td colspan="3"><input type="text" name="skype" id="skype" size="32" value="'.$object->skype.'"></td></tr>';
	        }
	
	        // Phone / Fax
	        print '<tr><td>'.fieldLabel('Phone','phone').'</td>';
		    print '<td><input type="text" name="phone" id="phone" value="'.$object->phone.'"></td>';
	        //print '<td>'.fieldLabel('Fax','fax').'</td>';
		    //print '<td><input type="text" name="fax" id="fax" value="'.$object->fax.'"></td>';
		    print '</tr>';
	
	        // Prof ids
	        //V20:
		    $key='idprof1';
		    print '<tr>';
		    $idprof_mandatory ='SOCIETE_IDPROF1_MANDATORY';
		    print '<td>'.fieldLabel($langs->transcountry('ProfId1',$object->country_code),$key, (empty($conf->global->$idprof_mandatory)?0:1)).'</td><td>';
		    print $formcompany->get_input_id_prof(1, $key, $object->$key, $object->country_code);
		    print '</td>';
		    print '</tr>';
	
	        // Assujeti TVA
	        //V20:
	        print '<input type="hidden" name="assujtva_value" value="1">';
	
	        // Mode de reglement par defaut. V20: Forma de pago
			print '<tr><td class="titlefield fieldrequired">';
			print $langs->trans('PaymentMode');
			print '</td><td>';
			$form->select_types_paiements('LIQ','mode_reglement_id');
			print "</td>";
			print '</tr>';
     
	    }
	    
        print '</table>';
		
		 //V20: Asigna comercial
        print '<input type="hidden" name="commercial_id" value="'.(! empty($object->commercial_id)?$object->commercial_id:$user->id).'">';


        dol_fiche_end();

        print '<div class="center">';
        print '<input type="submit" class="button" name="create" value="'.$langs->trans('AddThirdParty').'">';
        
        print '&nbsp <input type="submit" class="button" name="action" value="'.$langs->trans('Anular').'">';	//V20
        print '<input type="hidden" name="place" value="'.$place.'">';	//V20: Para TPV
        
        if ($backtopage)
        {
            print ' &nbsp; ';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
        }
        print '</div>'."\n";

        print '</form>'."\n";
    }
    elseif ($action == 'edit')
    {
        /*
         * Edition
         */

        //print load_fiche_titre($langs->trans("EditCompany"));

        if ($socid)
        {
            $res=$object->fetch_optionals($object->id,$extralabels);
            //if ($res < 0) { dol_print_error($db); exit; }

	        $head = societe_prepare_head($object);

            // Load object modCodeTiers
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
            // We verified if the tag prefix is used
            if ($modCodeClient->code_auto)
            {
                $prefixCustomerIsUsed = $modCodeClient->verif_prefixIsUsed();
            }
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
            $modCodeFournisseur = new $module($db);
            // On verifie si la balise prefix est utilisee
            if ($modCodeFournisseur->code_auto)
            {
                $prefixSupplierIsUsed = $modCodeFournisseur->verif_prefixIsUsed();
            }

			$object->oldcopy = clone $object;

            if (GETPOST('name'))
            {
                // We overwrite with values if posted
                $object->name					= GETPOST('name', 'alpha');
                $object->prefix_comm			= GETPOST('prefix_comm', 'alpha');
                $object->client					= GETPOST('client', 'int');
                $object->code_client			= GETPOST('code_client', 'alpha');
                $object->fournisseur			= GETPOST('fournisseur', 'int');
                $object->code_fournisseur		= GETPOST('code_fournisseur', 'alpha');
                $object->address				= GETPOST('address', 'alpha');
                $object->zip					= GETPOST('zipcode', 'alpha');
                $object->town					= GETPOST('town', 'alpha');
                
                $object->country_id				= GETPOST('country_id')?GETPOST('country_id', 'int'):$mysoc->country_id;
                $object->state_id				= GETPOST('state_id', 'int');
                $object->skype					= GETPOST('skype', 'alpha');
                $object->phone					= GETPOST('phone', 'alpha');
                $object->fax					= GETPOST('fax', 'alpha');
                $object->email					= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
                $object->url					= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
                $object->capital				= GETPOST('capital', 'alpha');
                $object->idprof1				= GETPOST('idprof1', 'alpha');
                $object->idprof2				= GETPOST('idprof2', 'alpha');
                $object->idprof3				= GETPOST('idprof3', 'alpha');
                $object->idprof4				= GETPOST('idprof4', 'alpha');
                $object->idprof5				= GETPOST('idprof5', 'alpha');
                $object->idprof6				= GETPOST('idprof6', 'alpha');
                $object->typent_id				= GETPOST('typent_id', 'int');
                $object->effectif_id			= GETPOST('effectif_id', 'int');
                $object->barcode				= GETPOST('barcode', 'alpha');
                $object->forme_juridique_code	= GETPOST('forme_juridique_code', 'int');
                $object->default_lang			= GETPOST('default_lang', 'alpha');

                $object->tva_assuj				= GETPOST('assujtva_value', 'int');
                $object->tva_intra				= GETPOST('tva_intra', 'alpha');
                $object->status					= GETPOST('status', 'int');

                // Webservices url/key
                $object->webservices_url        = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
                $object->webservices_key        = GETPOST('webservices_key', 'san_alpha');

				//Incoterms
				if (!empty($conf->incoterm->enabled))
				{
					$object->fk_incoterms			= GETPOST('incoterm_id', 'int');
					$object->location_incoterms		= GETPOST('lcoation_incoterms', 'alpha');
				}

                //Local Taxes
                $object->localtax1_assuj		= GETPOST('localtax1assuj_value');
                $object->localtax2_assuj		= GETPOST('localtax2assuj_value');

                $object->localtax1_value		=GETPOST('lt1');
                $object->localtax2_value		=GETPOST('lt2');

                // We set country_id, and country_code label of the chosen country
                if ($object->country_id > 0)
                {
                	$tmparray=getCountry($object->country_id,'all');
                    $object->country_code	= $tmparray['code'];
                    $object->country		= $tmparray['label'];
                }
                
            }

            dol_htmloutput_errors($error,$errors);

            if($object->localtax1_assuj==0){
            	$sub=0;
            }else{$sub=1;}
            if($object->localtax2_assuj==0){
            	$sub2=0;
            }else{$sub2=1;}


            print "\n".'<script type="text/javascript">';
            print '$(document).ready(function () {
    			var val='.$sub.';
    			var val2='.$sub2.';
    			if("#localtax1assuj_value".value==undefined){
    				if(val==1){
    					$(".cblt1").show();
    				}else{
    					$(".cblt1").hide();
    				}
    			}
    			if("#localtax2assuj_value".value==undefined){
    				if(val2==1){
    					$(".cblt2").show();
    				}else{
    					$(".cblt2").hide();
    				}
    			}
    			$("#localtax1assuj_value").change(function() {
               		var value=document.getElementById("localtax1assuj_value").value;
    				if(value==1){
    					$(".cblt1").show();
    				}else{
    					$(".cblt1").hide();
    				}
    			});
    			$("#localtax2assuj_value").change(function() {
    				var value=document.getElementById("localtax2assuj_value").value;
    				if(value==1){
    					$(".cblt2").show();
    				}else{
    					$(".cblt2").hide();
    				}
    			});

               });';
            print '</script>'."\n";


            if ($conf->use_javascript_ajax)
            {
                print "\n".'<script type="text/javascript" language="javascript">';
                print '$(document).ready(function () {
                			$("#selectcountry_id").change(function() {
                				document.formsoc.action.value="edit";
                				document.formsoc.submit();
                			});
                       })';
                print '</script>'."\n";
            }

            print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post" name="formsoc">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="socid" value="'.$object->id.'">';
            print '<input type="hidden" name="orderid" value="'.$orderid.'">';
            if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

			//V20: Edit view
			//dol_fiche_head($head, 'card', $langs->trans("ThirdParty"),0,'company');
            print load_fiche_titre($langs->trans("Customer"),$linkback,'title_companies.png');
            dol_fiche_head('');
            
          
            print '<table class="border" width="100%">';

            // Ref/ID
			if (! empty($conf->global->MAIN_SHOW_TECHNICAL_ID))
			{
		        print '<tr><td>'.$langs->trans("ID").'</td><td colspan="3">';
            	print $object->ref;
            	print '</td></tr>';
			}
			
            // Name
            print '<tr><td class="titlefield">'.fieldLabel('ThirdPartyName','name',1).'</td>';
	        print '<td colspan="3"><input type="text" size="60" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($object->name).'" autofocus="autofocus"></td></tr>';

	        // Alias names (commercial, trademark or alias names)
	        print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
	        print '<td colspan="3"><input type="text" size="60" name="name_alias" id="name_alias_input" value="'.dol_escape_htmltag($object->name_alias).'"></td></tr>';

            // Prospect/Customer
            print '<input type="hidden" name="client" value="1" id="customerprospect">';
            //print '<input type="hidden" name="code_client" id="customer_code"  value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
            
 
            // Status
            print '<input type="hidden" name="status" value="1">';

            // Address
            print '<tr><td class="tdtop">'.fieldLabel('Address','address').'</td>';
	        print '<td colspan="3"><textarea name="address" id="address" class="quatrevingtpercent" rows="3" wrap="soft">';
            print $object->address;
            print '</textarea></td></tr>';

            // Zip / Town
            print '<tr><td>'.fieldLabel('Zip','zipcode').'</td><td>';
            print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
            print '</td><td>'.fieldLabel('Town','town').'</td><td>';
            print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
            print '</td></tr>';

            // Country
            print '<tr><td>'.fieldLabel('Country','selectcounty_id').'</td><td colspan="3">';
            print $form->select_country((GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id),'country_id');
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            print '</td></tr>';

            // State
            if (empty($conf->global->SOCIETE_DISABLE_STATE))
            {
                print '<tr><td>'.fieldLabel('State','state_id').'</td><td colspan="3">';
                print $formcompany->select_state($object->state_id,$object->country_code);
                print '</td></tr>';
            }

            // EMail / Web
            print '<tr><td>'.fieldLabel('EMail','email',(! empty($conf->global->SOCIETE_MAIL_REQUIRED))).'</td>';
	        print '<td colspan="3"><input type="text" name="email" id="email" size="32" value="'.$object->email.'"></td></tr>';

            // Phone / Fax
            print '<tr><td>'.fieldLabel('Phone','phone').'</td>';
	        print '<td><input type="text" name="phone" id="phone" value="'.$object->phone.'"></td>';
            print '<td>'.fieldLabel('Fax','fax').'</td>';
	        print '<td><input type="text" name="fax" id="fax" value="'.$object->fax.'"></td></tr>';

            // Prof ids
            //V20:
	        $key='idprof1';
	        print '<tr>';
	        $idprof_mandatory ='SOCIETE_IDPROF1_MANDATORY';
	        print '<td>'.fieldLabel($langs->transcountry('ProfId1',$object->country_code),$key, (empty($conf->global->$idprof_mandatory)?0:1)).'</td><td>';
	        print $formcompany->get_input_id_prof(1, $key, $object->$key, $object->country_code);
	        print '</td>';
	        print '</tr>';

            //V20: Recargo de Equivalencia
             print '<tr><td>'.fieldLabel($langs->transcountry("LocalTax1IsUsed",$mysoc->country_code),'localtax1assuj_value').'</td><td>';
             print $form->selectyesno('localtax1assuj_value',$object->localtax1_assuj,1);
             print '</td></tr>';

            // Juridical type
            print '<tr><td>'.fieldLabel('JuridicalStatus','forme_juridique_code').'</td><td class="maxwidthonsmartphone" colspan="3">';
            print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
            print '</td></tr>';

            
            // Mode de reglement par defaut. V20: Forma de pago
			print '<tr><td class="titlefield fieldrequired">';
			print $langs->trans('PaymentMode');
			print '</td><td>';
			$form->select_types_paiements($object->mode_reglement_id,'mode_reglement_id');
			print "</td>";
			print '</tr>';
			
            //V20: Cuenta Bancaria
            //
            $account = new CompanyBankAccount($db);
            $account->fetch(0,$object->id);
            print '<input type="hidden" name="banklabel" value="Principal">';
            print '<tr><td >'.$langs->trans("BankName").'</td>';
            print '<td colspan="3"><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';
            // Show fields of bank account
            print '<tr><td>'.$langs->trans('BankAccountNumber').'</td><td>';
            foreach ($account->getFieldsToShow() as $val) {
            	if ($val == 'BankCode') {
            		$name = 'code_banque';
            		$size = 8;
            		$content = $account->code_banque;
            	} elseif ($val == 'DeskCode') {
            		$name = 'code_guichet';
            		$size = 8;
            		$content = $account->code_guichet;
            	} elseif ($val == 'BankAccountNumber') {
            		$name = 'number';
            		$size = 18;
            		$content = $account->number;
            	} elseif ($val == 'BankAccountNumberKey') {
            		$name = 'cle_rib';
            		$size = 3;
            		$content = $account->cle_rib;
            	}
            	
            	//print '<td>'.$langs->trans($val).'</td>';
            	print '<input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.$content.'">';
            	
            }print '</td></tr>';
            
            // IBAN
            print '<tr><td class="titlefield ">'.$langs->trans("IBAN").'</td>';
            print '<td colspan="3"><input size="30" type="text" name="iban" value="'.$account->iban.'"></td></tr>';
            
            print '</table>';

	        dol_fiche_end();

            print '<div align="center">';
            print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
            print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
            print '</div>';

            print '</form>';
        }
    }
    else
    {
        /*
         * View
         */
        if (!empty($object->id)) $res=$object->fetch_optionals($object->id,$extralabels);
        //if ($res < 0) { dol_print_error($db); exit; }

        //V20: Normal view
        
        print load_fiche_titre($langs->trans("Customer").': '.$object->name.' ('.$object->name_alias.')',$linkback,'title_companies.png');
        //print '<td class="refid">'.$object->name.' ('.$object->name_alias.')</td>';
 		dol_fiche_head('');
        //$head = societe_prepare_head($object);
        //dol_fiche_head($head, 'card', $langs->trans("ThirdParty"),0,'company');

        // Confirm delete third party
        if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)))
        {
            print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("DeleteACompany"), $langs->trans("ConfirmDeleteCompany"), "confirm_delete", '', 0, "action-delete");
        }

	    if ($action == 'merge')
	    {
		    $formquestion = array(
			    array(
				    'name' => 'soc_origin',
			    	'label' => $langs->trans('MergeOriginThirdparty'),
				    'type' => 'other',
				    'value' => $form->select_company('', 'soc_origin', 's.rowid != '.$object->id, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth200')
			    )
		    );

		    print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("MergeThirdparties"), $langs->trans("ConfirmMergeThirdparties"), "confirm_merge", $formquestion, 'no', 1, 190);
	    }

        dol_htmloutput_errors($error,$errors);
		
        
        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';
        

        // Customer code
        if ($object->client)
        {
            print '<tr><td>';
            print $langs->trans('CustomerCode').'</td><td>';
            print $object->code_client;
            if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
            print '</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Prof ids
        //V20:
        $key='idprof1';
        print '<tr>';
        $idprof_mandatory ='SOCIETE_IDPROF1_MANDATORY';
        print '<td>'.fieldLabel($langs->transcountry('ProfId1',$object->country_code),$key, (empty($conf->global->$idprof_mandatory)?0:1)).'</td><td>';
        print $object->$key;
        if ($object->$key)
        {
        	if ($object->id_prof_check($i,$object) > 0) print ' &nbsp; '.$object->id_prof_url(1,$object);
        	else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td>';
        print '</tr>';
        
        //V20: Poblacion/Provincia
        print '<tr>';
        print '<td>'.$langs->trans('State').'</td><td>'.$object->town.' ('.$object->state.')</td>';
        print '</tr>';

        print '</table>';
        
        print '</div>';
        print '<div class="fichehalfright"><div class="ficheaddleft">';
       
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';
        
        //Forma de pago. V20
        $form->load_cache_types_paiements();
        print '<tr><td class="titlefield">'.$langs->trans('PaymentMode').'</td><td>'.$form->cache_types_paiements[$object->mode_reglement_id]['label'].'</td></tr>';

        // Ban
        if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
        {
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans('RIB');
            print '</td>';
            print '<td align="right">';
            //if ($user->rights->societe->creer) print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'">'.img_edit().'</a>';
            //else print '&nbsp;';
            print '</td></tr></table>';
            print '</td>';
            print '<td colspan="3">';
            print $object->display_rib();
            print '</td></tr>';
        }

        // Sales representative
        include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

        // Module Adherent
        if (! empty($conf->adherent->enabled))
        {
            $langs->load("members");
            print '<tr><td class="tdtop">'.$langs->trans("LinkedToDolibarrMember").'</td>';
            print '<td colspan="3">';
            $adh=new Adherent($db);
            $result=$adh->fetch('','',$object->id);
            if ($result > 0)
            {
                $adh->ref=$adh->getFullName($langs);
                print $adh->getNomUrl(1);
            }
            else
            {
                print $langs->trans("ThirdpartyNotLinkedToMember");
            }
            print '</td>';
            print "</tr>\n";
        }

        // Webservices url/key
        if (!empty($conf->syncsupplierwebservices->enabled)) {
            print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td>'.dol_print_url($object->webservices_url).'</td>';
            print '<td class="nowrap">'.$langs->trans('WebServiceKey').'</td><td>'.$object->webservices_key.'</td></tr>';
        }

       		
		//V20: Facturas pendientes de pago
		/*
		 *   Last invoices
		 */
		if (! empty($conf->facture->enabled) && $object->id >0)
		{
			$facturestatic = new Facture($db);
			//$MAXLIST=$conf->global->MAIN_SIZE_SHORTLISTE_LIMIT;
			$MAXLIST=10;
	
	        $sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount';
	        $sql.= ', f.total as total_ht';
	        $sql.= ', f.tva as total_tva';
	        $sql.= ', f.total_ttc';
			$sql.= ', f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut';
			$sql.= ', s.nom, s.rowid as socid';
			$sql.= ', SUM(pf.amount) as am';
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
			$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
			//if(!$user->rights->tpvcommande->allfact)	$sql.= " AND (f.fk_statut = 1 OR f.fk_statut = 0)";
			if(!$user->rights->tpvcommande->allfact)	$sql.= " AND f.paye = 0";
			$sql.= " AND f.entity = ".$conf->entity;
			$sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.tva, f.total_ttc,';
			$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';
			$sql.= ' s.nom, s.rowid';
			$sql.= " ORDER BY f.datef ASC, f.datec ASC";
	
			$resql=$db->query($sql);
			if ($resql)
			{
				$var=true;
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num > 0)
				{
			        print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre">';
					//V20: 
					if(!$user->rights->tpvcommande->allfact){
						print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("UnpaidBills").' ('.$num.')</td>'; 

					}else{
						print '<td colspan="5"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td>'; 
						print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$object->id.'">'.$langs->trans("AllBills").' <span class="badge">'.$num.'</span></a></td>';
		                //print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
						
					}print '</tr></table></td>';
					print '</tr>';
				}
	
				while ($i < $num && $i < $MAXLIST)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;
					print "<tr ".$bc[$var].">";
					print '<td class="nowrap">';
					$facturestatic->id = $objp->facid;
					$facturestatic->ref = $objp->facnumber;
					$facturestatic->type = $objp->type;
	                $facturestatic->total_ht = $objp->total_ht;
	                $facturestatic->total_tva = $objp->total_tva;
	                $facturestatic->total_ttc = $objp->total_ttc;
	                //V20: 
					//print $facturestatic->getNomUrl(1);
	                print '<a href="'.DOL_URL_ROOT.'/tpvcommande/compta/facture.php?facid='.$objp->facid.'&orderid='.$orderid.'">'.$objp->facnumber.'</a>';
	               
					print '</td>';
					if ($objp->df > 0)
					{
						print '<td align="right" width="80px">'.dol_print_date($db->jdate($objp->df),'day').'</td>';
					}
					else
					{
						print '<td align="right"><b>!!!</b></td>';
					}
					print '<td align="right" style="min-width: 60px">';
					print price($objp->total_ttc, '', $langs, 0, 0, -1, $conf->currency);
					print '</td>';
					
					//V20: Deuda pendiente
					//TODO: Revisar correctamente la manera de revisar la deuda.
					print '<td align="right" style="min-width: 60px">';
					print 'Deuda: '.price($facturestatic->total_ttc-$facturestatic->getSommePaiement(), '', $langs, 0, 0, -1, $conf->currency);
					print '</td>';
					
	
					if (! empty($conf->global->MAIN_SHOW_PRICE_WITH_TAX_IN_SUMMARIES))
					{
	    				print '<td align="right" style="min-width: 60px">';
	    				print price($objp->total_ttc);
	    				print '</td>';
					}
					
					print '<td align="right" class="nowrap" style="min-width: 60px">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am)).'</td>';
					print "</tr>\n";
					$i++;
				}
				$db->free($resql);
	
				if ($num > 0) print "</table>";
			}
			else
			{
				dol_print_error($db);
			}
		}

		print '</table>';
		print '</div>';
		
        print '</div></div>';
        print '<div style="clear:both"></div>';
        
        dol_fiche_end();


        /*
         *  Actions
         */
        print '<div class="tabsAction">'."\n";

		$parameters=array();
		$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook))
		{
			$at_least_one_email_contact = false;
			$TContact = $object->contact_array_objects();
			foreach ($TContact as &$contact)
			{
				if (!empty($contact->email)) 
				{
					$at_least_one_email_contact = true;
					break;
				}
			}
			//V20: Actions buttons
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/tpvcommande/list.php?orderid='. $orderid .'&amp;action=buymore">' . $langs->trans('buymore') . '</a></div>';
			
			

	        if ($user->rights->societe->creer)
	        {
	            print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?orderid='. $orderid .'&socid='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
	        }

		}

        print '</div>'."\n";

        //Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}
		if ($action == 'presend')
		{
			/*
			 * Affiche formulaire mail
			*/

			// By default if $action=='presend'
			$titreform='SendMail';
			$topicmail='';
			$action='send';
			$modelmail='thirdparty';

			//print '<br>';
			print load_fiche_titre($langs->trans($titreform));

			dol_fiche_head();
			
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
				$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->default_lang;

			// Cree l'objet formulaire mail
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->trackid='thi'.$object->id;
			if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
			{
				include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'thi'.$object->id);
			}
			$formmail->withfrom=1;
			$formmail->withtopic=1;
			$liste=array();
			foreach ($object->thirdparty_and_contact_email_array(1) as $key=>$value) $liste[$key]=$value;
			$formmail->withto=GETPOST('sendto')?GETPOST('sendto'):$liste;
			$formmail->withtofree=0;
			$formmail->withtocc=$liste;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withfile=2;
			$formmail->withbody=1;
			$formmail->withdeliveryreceipt=1;
			$formmail->withcancel=1;
			// Tableau des substitutions
			//$formmail->setSubstitFromObject($object);
			$formmail->substit['__THIRDPARTY_NAME__']=$object->name;
			$formmail->substit['__SIGNATURE__']=$user->signature;
			$formmail->substit['__PERSONALIZED__']='';
			$formmail->substit['__CONTACTCIVNAME__']='';

			//Find the good contact adress
			

			// Tableau des parametres complementaires du post
			$formmail->param['action']=$action;
			$formmail->param['models']=$modelmail;
			$formmail->param['models_id']=GETPOST('modelmailselected','int');
			$formmail->param['socid']=$object->id;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?socid='.$object->id;

			// Init list of files
			if (GETPOST("mode")=='init')
			{
				$formmail->clear_attached_files();
				$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
			}
			print $formmail->get_form();

			dol_fiche_end();
		}
		else
		{

		}

    }

}


// End of page
llxFooter();
$db->close();
