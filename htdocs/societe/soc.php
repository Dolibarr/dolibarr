<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2013  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *  \file       htdocs/societe/soc.php
 *  \ingroup    societe
 *  \brief      Third party card page
 */

require '../main.inc.php';
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

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);



/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
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
			setEventMessage($langs->trans('ErrorProdIdIsMandatory', $langs->trans('MergeOriginThirdparty')), 'errors');
		}
		else
		{

			if (!$errors && $soc_origin->fetch($soc_origin_id) < 1)
			{
				setEventMessage($langs->trans('ErrorRecordNotFound'), 'errors');
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
					setEventMessage($langs->trans('ThirdpartiesMergeSuccess'));
					$db->commit();
				} else {
					setEventMessage($langs->trans('ErrorsThirdpartyMerge'), 'errors');
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
    if ((! GETPOST('getcustomercode') && ! GETPOST('getsuppliercode'))
    && ($action == 'add' || $action == 'update') && $user->rights->societe->creer)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        if ($action == 'update')
        {
        	$ret=$object->fetch($socid);
			$object->oldcopy = clone $object;
        }
		else $object->canvas=$canvas;

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

        $object->address               = GETPOST('address', 'alpha');
        $object->zip                   = GETPOST('zipcode', 'alpha');
        $object->town                  = GETPOST('town', 'alpha');
        $object->country_id            = GETPOST('country_id', 'int');
        $object->state_id              = GETPOST('state_id', 'int');
        $object->skype                 = GETPOST('skype', 'alpha');
        $object->phone                 = GETPOST('phone', 'alpha');
        $object->fax                   = GETPOST('fax','alpha');
        $object->email                 = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        $object->url                   = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->idprof1               = GETPOST('idprof1', 'alpha');
        $object->idprof2               = GETPOST('idprof2', 'alpha');
        $object->idprof3               = GETPOST('idprof3', 'alpha');
        $object->idprof4               = GETPOST('idprof4', 'alpha');
        $object->idprof5               = GETPOST('idprof5', 'alpha');
        $object->idprof6               = GETPOST('idprof6', 'alpha');
        $object->prefix_comm           = GETPOST('prefix_comm', 'alpha');
        $object->code_client           = GETPOST('code_client', 'alpha');
        $object->code_fournisseur      = GETPOST('code_fournisseur', 'alpha');
        $object->capital               = GETPOST('capital', 'alpha');
        $object->barcode               = GETPOST('barcode', 'alpha');

        $object->tva_intra             = GETPOST('tva_intra', 'alpha');
        $object->tva_assuj             = GETPOST('assujtva_value', 'alpha');
        $object->status                = GETPOST('status', 'alpha');

        // Local Taxes
        $object->localtax1_assuj       = GETPOST('localtax1assuj_value', 'alpha');
        $object->localtax2_assuj       = GETPOST('localtax2assuj_value', 'alpha');

        $object->localtax1_value	   = GETPOST('lt1', 'alpha');
        $object->localtax2_value	   = GETPOST('lt2', 'alpha');

        $object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
        $object->effectif_id           = GETPOST('effectif_id', 'int');
        $object->typent_id             = GETPOST('typent_id');

        $object->client                = GETPOST('client', 'int');
        $object->fournisseur           = GETPOST('fournisseur', 'int');

        $object->commercial_id         = GETPOST('commercial_id', 'int');
        $object->default_lang          = GETPOST('default_lang');

        // Webservices url/key
        $object->webservices_url       = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->webservices_key       = GETPOST('webservices_key', 'san_alpha');

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			$object->fk_incoterms 		   = GETPOST('incoterm_id', 'int');
			$object->location_incoterms    = GETPOST('location_incoterms', 'alpha');
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

            // Check for duplicate or mandatory prof id
            // Only for companies
	        if (!($object->particulier || $private))
        	{
	        	for ($i = 1; $i < 5; $i++)
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

        if (! $error)
        {
            if ($action == 'add')
            {
                $db->begin();

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

					// Customer categories association
					$custcats = GETPOST( 'custcats', 'array' );
					$object->setCategories($custcats, 'customer');

					// Supplier categories association
					$suppcats = GETPOST('suppcats', 'array');
					$object->setCategories($suppcats, 'supplier');

                    // Logo/Photo save
                    $dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos/";
                    $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
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
                                    // Create small thumbs for company (Ratio is near 16/9)
                                    // Used on logon for example
                                    $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                                    // Create mini thumbs for company (Ratio is near 16/9)
                                    // Used on menu or for setup page for example
                                    $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                                }
                            }
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
                }
                else
				{
                    $error=$object->error; $errors=$object->errors;
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
                    	$url=$_SERVER["PHP_SELF"]."?socid=".$object->id;
                    	if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url=DOL_URL_ROOT."/comm/card.php?socid=".$object->id;
                    	else if ($object->fournisseur == 1) $url=DOL_URL_ROOT."/fourn/card.php?socid=".$object->id;

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
                if ($file_OK)
                {
                    if (GETPOST('deletephoto'))
                    {
                        $fileimg=$dir.'/'.$object->logo;
                        $dirthumbs=$dir.'/thumbs';
                        dol_delete_file($fileimg);
                        dol_delete_dir_recursive($dirthumbs);
                    }

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
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name;
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

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
		if (! empty($conf->global->MAIN_THIRPARTY_CREATION_INDIVIDUAL) && ! isset($_GET['private']) && ! isset($_POST['private'])) $private=1;
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
        if (GETPOST("type")!='f' && empty($conf->global->THIRDPARTY_NOTCUSTOMERPROSPECT_BY_DEFAULT))  { $object->client=3; }
        if (GETPOST("type")=='c')  { $object->client=1; }
        if (GETPOST("type")=='p')  { $object->client=2; }
        if (! empty($conf->fournisseur->enabled) && (GETPOST("type")=='f' || (GETPOST("type")=='' && empty($conf->global->THIRDPARTY_NOTSUPPLIER_BY_DEFAULT))))  { $object->fournisseur=1; }

        $object->name				= GETPOST('name', 'alpha');
        $object->firstname			= GETPOST('firstname', 'alpha');
        $object->particulier		= $private;
        $object->prefix_comm		= GETPOST('prefix_comm');
        $object->client				= GETPOST('client')?GETPOST('client'):$object->client;
        $object->code_client		= GETPOST('code_client', 'alpha');
        $object->fournisseur		= GETPOST('fournisseur')?GETPOST('fournisseur'):$object->fournisseur;
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
        $object->capital			= GETPOST('capital', 'int');
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
                        // Create small thumbs for company (Ratio is near 16/9)
                        // Used on logon for example
                        $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                        // Create mini thumbs for company (Ratio is near 16/9)
                        // Used on menu or for setup page for example
                        $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
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

        $linkback="";
        print load_fiche_titre($langs->trans("NewThirdParty"),$linkback,'title_companies.png');

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

            print '<div id="selectthirdpartytype">';
            print '<div class="hideonsmartphone float">';
            print $langs->trans("ThirdPartyType").': &nbsp; &nbsp; ';
            print '</div>';
	        print '<label for="radiocompany">';
            print '<input type="radio" id="radiocompany" class="flat" name="private"  value="0"'.($private?'':' checked').'>';
	        print '&nbsp;';
            print $langs->trans("Company/Fundation");
	        print '</label>';
            print ' &nbsp; &nbsp; ';
	        print '<label for="radioprivate">';
            $text ='<input type="radio" id="radioprivate" class="flat" name="private" value="1"'.($private?' checked':'').'>';
	        $text.='&nbsp;';
	        $text.= $langs->trans("Individual");
	        $htmltext=$langs->trans("ToCreateContactWithSameName");
	        print $form->textwithpicto($text, $htmltext, 1, 'help', '', 0, 3);
            print '</label>';
            print '</div>';
            print "<br>\n";
        }

        dol_htmloutput_mesg(is_numeric($error)?'':$error, $errors, 'error');

        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';

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
	    print '<tr><td>';
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
	        print '<td><input type="text" size="60" name="firstname" id="firstname" value="'.$object->firstname.'"></td>';
            print '<td colspan=2>&nbsp;</td></tr>';
            print '<tr class="individualline"><td>'.fieldLabel('UserTitle','civility_id').'</td><td>';
            print $formcompany->select_civility($object->civility_id).'</td>';
            print '<td colspan=2>&nbsp;</td></tr>';
        }

        // Prospect/Customer
        print '<tr><td width="25%">'.fieldLabel('ProspectCustomer','customerprospect',1).'</td>';
	    print '<td width="25%" class="maxwidthonsmartphone"><select class="flat" name="client" id="customerprospect">';
        $selected=isset($_POST['client'])?GETPOST('client'):$object->client;
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2"'.($selected==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="3"'.($selected==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($selected==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.($selected==0?' selected':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
        print '</select></td>';

        print '<td width="25%">'.fieldLabel('CustomerCode','customer_code').'</td><td width="25%">';
        print '<table class="nobordernopadding"><tr><td>';
        $tmpcode=$object->code_client;
        if (empty($tmpcode) && ! empty($modCodeClient->code_auto)) $tmpcode=$modCodeClient->getNextValue($object,0);
        print '<input type="text" name="code_client" id="customer_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
        print '</td><td>';
        $s=$modCodeClient->getToolTip($langs,$object,0);
        print $form->textwithpicto('',$s,1);
        print '</td></tr></table>';
        print '</td></tr>';

        if (! empty($conf->fournisseur->enabled) && ! empty($user->rights->fournisseur->lire))
        {
            // Supplier
            print '<tr>';
            print '<td>'.fieldLabel('Supplier','fournisseur',1).'</td><td>';
            print $form->selectyesno("fournisseur",(isset($_POST['fournisseur'])?GETPOST('fournisseur'):$object->fournisseur),1);
            print '</td>';
            print '<td>'.fieldLabel('SupplierCode','supplier_code').'</td><td>';
            print '<table class="nobordernopadding"><tr><td>';
            $tmpcode=$object->code_fournisseur;
            if (empty($tmpcode) && ! empty($modCodeFournisseur->code_auto)) $tmpcode=$modCodeFournisseur->getNextValue($object,1);
            print '<input type="text" name="code_fournisseur" id="supplier_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
            print '</td><td>';
            $s=$modCodeFournisseur->getToolTip($langs,$object,1);
            print $form->textwithpicto('',$s,1);
            print '</td></tr></table>';
            print '</td></tr>';
        }

        // Status
        print '<tr><td>'.fieldLabel('Status','status').'</td><td colspan="3">';
        print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),1);
        print '</td></tr>';

        // Barcode
        if (! empty($conf->barcode->enabled))
        {
            print '<tr><td>'.fieldLabel('Gencod','barcode').'</td>';
	        print '<td colspan="3"><input type="text" name="barcode" id="barcode" value="'.$object->barcode.'">';
            print '</td></tr>';
        }

        // Alias names (commercial, trademark or alias names)
        print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
	    print '<td colspan="3"><input type="text" name="name_alias" id="name_alias_input" value="'.$object->name_alias.'" size="32"></td></tr>';

        // Address
        print '<tr><td class="tdtop">'.fieldLabel('Address','address').'</td>';
	    print '<td colspan="3"><textarea name="address" id="address" cols="80" rows="'._ROWS_2.'" wrap="soft">';
        print $object->address;
        print '</textarea></td></tr>';

        // Zip / Town
        print '<tr><td>'.fieldLabel('Zip','zipcode').'</td><td>';
        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','state_id'),6);
        print '</td><td>'.fieldLabel('Town','town').'</td><td>';
        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','state_id'));
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
        print '<tr><td>'.fieldLabel('Web','url').'</td>';
	    print '<td colspan="3"><input type="text" name="url" id="url" size="32" value="'.$object->url.'"></td></tr>';

        // Skype
        if (! empty($conf->skype->enabled))
        {
            print '<tr><td>'.fieldLabel('Skype','skype').'</td>';
	        print '<td colspan="3"><input type="text" name="skype" id="skype" size="32" value="'.$object->skype.'"></td></tr>';
        }

        // Phone / Fax
        print '<tr><td>'.fieldLabel('Phone','phone').'</td>';
	    print '<td><input type="text" name="phone" id="phone" value="'.$object->phone.'"></td>';
        print '<td>'.fieldLabel('Fax','fax').'</td>';
	    print '<td><input type="text" name="fax" id="fax" value="'.$object->fax.'"></td></tr>';

        // Prof ids
        $i=1; $j=0;
        while ($i <= 6)
        {
            $idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
            if ($idprof!='-')
            {
	            $key='idprof'.$i;

                if (($j % 2) == 0) print '<tr>';

                $idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
               	if(empty($conf->global->$idprof_mandatory))
                	print '<td>'.fieldLabel($idprof,$key).'</td><td>';
                else
                    print '<td>'.fieldLabel($idprof,$key,1).'</td><td>';

                print $formcompany->get_input_id_prof($i,$key,$object->$key,$object->country_code);
                print '</td>';
                if (($j % 2) == 1) print '</tr>';
                $j++;
            }
            $i++;
        }
        if ($j % 2 == 1) print '<td colspan="2"></td></tr>';

        // Assujeti TVA
        print '<tr><td>'.fieldLabel('VATIsUsed','assujtva_value').'</td>';
        print '<td>';
        print $form->selectyesno('assujtva_value',1,1);     // Assujeti par defaut en creation
        print '</td>';
        print '<td class="nowrap">'.fieldLabel('VATIntra','intra_vat').'</td>';
        print '<td class="nowrap">';
        $s = '<input type="text" class="flat" name="tva_intra" id="intra_vat" size="12" maxlength="20" value="'.$object->tva_intra.'">';

        if (empty($conf->global->MAIN_DISABLEVATCHECK))
        {
            $s.=' ';

            if (! empty($conf->use_javascript_ajax))
            {
                print "\n";
                print '<script language="JavaScript" type="text/javascript">';
                print "function CheckVAT(a) {\n";
                print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,300);\n";
                print "}\n";
                print '</script>';
                print "\n";
                $s.='<a href="#" class="hideonsmartphone" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
                $s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
            }
            else
            {
                $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$object->country_id).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
            }
        }
        print $s;
        print '</td>';
        print '</tr>';

        // Type - Size
        print '<tr><td>'.fieldLabel('ThirdPartyType','typent_id').'</td><td>'."\n";
        print $form->selectarray("typent_id", $formcompany->typent_array(0), $object->typent_id, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
        if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td>';
        print '<td>'.fieldLabel('Staff','effectif_id').'</td><td>';
        print $form->selectarray("effectif_id", $formcompany->effectif_array(0), $object->effectif_id);
        if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td></tr>';

        // Legal Form
        print '<tr><td>'.fieldLabel('JuridicalStatus','forme_juridique_code').'</td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        if ($object->country_id)
        {
            print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';

        // Capital
        print '<tr><td>'.fieldLabel('Capital','capital').'</td>';
	    print '<td colspan="3"><input type="text" name="capital" id="capital" size="10" value="'.$object->capital.'"> ';
        print '<span class="hideonsmartphone">'.$langs->trans("Currency".$conf->currency).'</span></td></tr>';

        // Local Taxes
        //TODO: Place into a function to control showing by country or study better option
        if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td>';
            print $form->selectyesno('localtax1assuj_value',0,1);
            print '</td><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td>';
            print $form->selectyesno('localtax2assuj_value',0,1);
            print '</td></tr>';

        }
        elseif($mysoc->localtax1_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print $form->selectyesno('localtax1assuj_value',0,1);
            print '</td><tr>';
        }
        elseif($mysoc->localtax2_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print $form->selectyesno('localtax2assuj_value',0,1);
            print '</td><tr>';
        }

        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            print '<tr><td>'.fieldLabel('DefaultLang','default_lang').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
            print $formadmin->select_language(($object->default_lang?$object->default_lang:$conf->global->MAIN_LANG_DEFAULT),'default_lang',0,0,1);
            print '</td>';
            print '</tr>';
        }

        if ($user->rights->societe->client->voir)
        {
            // Assign a Name
            print '<tr>';
            print '<td>'.fieldLabel('AllocateCommercial','commercial_id').'</td>';
            print '<td colspan="3" class="maxwidthonsmartphone">';
            $form->select_dolusers((! empty($object->commercial_id)?$object->commercial_id:$user->id),'commercial_id',1); // Add current user by default
            print '</td></tr>';
        }

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			print '<tr>';
			print '<td>'.fieldLabel('IncotermLabel','incoterm_id').'</td>';
	        print '<td colspan="3" class="maxwidthonsmartphone">';
	        print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''));
			print '</td></tr>';
		}

		// Categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			$langs->load('categories');

			// Customer
			if ($object->prospect || $object->client) {
				print '<tr><td class="toptd">' . fieldLabel('CustomersCategoriesShort', 'custcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
				print $form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}

			// Supplier
			if ($object->fournisseur) {
				print '<tr><td class="toptd">' . fieldLabel('SuppliersCategoriesShort', 'suppcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, 'parent', null, null, 1);
				print $form->multiselectarray('suppcats', $cate_arbo, GETPOST('suppcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}
		}

        // Other attributes
        $parameters=array('colspan' => ' colspan="3"', 'colspanvalue' => '3');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields,'edit');
        }

        // Ajout du logo
        print '<tr class="hideonsmartphone">';
        print '<td>'.fieldLabel('Logo','photoinput').'</td>';
        print '<td colspan="3">';
        print '<input class="flat" type="file" name="photo" id="photoinput" />';
        print '</td>';
        print '</tr>';

        print '</table>'."\n";

        dol_fiche_end();

        print '<div class="center">';
        print '<input type="submit" class="button" name="submit" value="'.$langs->trans('AddThirdParty').'">';
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
                $object->capital				= GETPOST('capital', 'int');
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
            if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';


            dol_fiche_head($head, 'card', $langs->trans("ThirdParty"),0,'company');


            print '<table class="border" width="100%">';

            // Name
            print '<tr><td>'.fieldLabel('ThirdPartyName','name',1).'</td>';
	        print '<td colspan="3"><input type="text" size="60" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($object->name).'" autofocus="autofocus"></td></tr>';

	        // Alias names (commercial, trademark or alias names)
	        print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
	        print '<td colspan="3"><input type="text" name="name_alias" id="name_alias_input" value="'.dol_escape_htmltag($object->name_alias).'" size="32"></td></tr>';

            // Prefix
            if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
            {
                print '<tr><td>'.fieldLabel('Prefix','prefix').'</td><td colspan="3">';
                // It does not change the prefix mode using the auto numbering prefix
                if (($prefixCustomerIsUsed || $prefixSupplierIsUsed) && $object->prefix_comm)
                {
                    print '<input type="hidden" name="prefix_comm" value="'.dol_escape_htmltag($object->prefix_comm).'">';
                    print $object->prefix_comm;
                }
                else
                {
                    print '<input type="text" size="5" maxlength="5" name="prefix_comm" id="prefix" value="'.dol_escape_htmltag($object->prefix_comm).'">';
                }
                print '</td>';
            }

            // Prospect/Customer
            print '<tr><td width="25%">'.fieldLabel('ProspectCustomer','customerprospect',1).'</td>';
	        print '<td width="25%"><select class="flat" name="client" id="customerprospect">';
            if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2"'.($object->client==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
            if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="3"'.($object->client==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
            if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($object->client==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
            print '<option value="0"'.($object->client==0?' selected':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
            print '</select></td>';
            print '<td width="25%">'.fieldLabel('CustomerCode','customer_code').'</td><td width="25%">';

            print '<table class="nobordernopadding"><tr><td>';
            if ((!$object->code_client || $object->code_client == -1) && $modCodeClient->code_auto)
            {
                $tmpcode=$object->code_client;
                if (empty($tmpcode) && ! empty($object->oldcopy->code_client)) $tmpcode=$object->oldcopy->code_client; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
                if (empty($tmpcode) && ! empty($modCodeClient->code_auto)) $tmpcode=$modCodeClient->getNextValue($object,0);
                print '<input type="text" name="code_client" id="customer_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
            }
            else if ($object->codeclient_modifiable())
            {
                print '<input type="text" name="code_client" id="customer_code" size="16" value="'.$object->code_client.'" maxlength="15">';
            }
            else
            {
                print $object->code_client;
                print '<input type="hidden" name="code_client" value="'.$object->code_client.'">';
            }
            print '</td><td>';
            $s=$modCodeClient->getToolTip($langs,$object,0);
            print $form->textwithpicto('',$s,1);
            print '</td></tr></table>';

            print '</td></tr>';

            // Supplier
            if (! empty($conf->fournisseur->enabled) && ! empty($user->rights->fournisseur->lire))
            {
                print '<tr>';
                print '<td>'.fieldLabel('Supplier','fournisseur',1).'</td><td>';
                print $form->selectyesno("fournisseur",$object->fournisseur,1);
                print '</td>';
                print '<td>'.fieldLabel('SupplierCode','supplier_code').'</td><td>';

                print '<table class="nobordernopadding"><tr><td>';
                if ((!$object->code_fournisseur || $object->code_fournisseur == -1) && $modCodeFournisseur->code_auto)
                {
                    $tmpcode=$object->code_fournisseur;
                    if (empty($tmpcode) && ! empty($object->oldcopy->code_fournisseur)) $tmpcode=$object->oldcopy->code_fournisseur; // When there is an error to update a thirdparty, the number for supplier and customer code is kept to old value.
                    if (empty($tmpcode) && ! empty($modCodeFournisseur->code_auto)) $tmpcode=$modCodeFournisseur->getNextValue($object,1);
                    print '<input type="text" name="code_fournisseur" id="supplier_code" size="16" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
                }
                else if ($object->codefournisseur_modifiable())
                {
                    print '<input type="text" name="code_fournisseur" id="supplier_code" size="16" value="'.$object->code_fournisseur.'" maxlength="15">';
                }
                else
              {
                    print $object->code_fournisseur;
                    print '<input type="hidden" name="code_fournisseur" value="'.$object->code_fournisseur.'">';
                }
                print '</td><td>';
                $s=$modCodeFournisseur->getToolTip($langs,$object,1);
                print $form->textwithpicto('',$s,1);
                print '</td></tr></table>';

                print '</td></tr>';
            }

            // Barcode
            if (! empty($conf->barcode->enabled))
            {
                print '<tr><td class="tdtop">'.fieldLabel('Gencod','barcode').'</td>';
	            print '<td colspan="3"><input type="text" name="barcode" id="barcode" value="'.$object->barcode.'">';
                print '</td></tr>';
            }

            // Status
            print '<tr><td>'.fieldLabel('Status','status').'</td><td colspan="3">';
            print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$object->status);
            print '</td></tr>';

            // Address
            print '<tr><td class="tdtop">'.fieldLabel('Address','address').'</td>';
	        print '<td colspan="3"><textarea name="address" id="address" cols="80" rows="3" wrap="soft">';
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
            print '<tr><td>'.fieldLabel('Web','url').'</td>';
	        print '<td colspan="3"><input type="text" name="url" id="url" size="32" value="'.$object->url.'"></td></tr>';

            // Skype
            if (! empty($conf->skype->enabled))
            {
                print '<tr><td>'.fieldLabel('Skype','skype').'</td>';
	            print '<td colspan="3"><input type="text" name="skype" id="skype" size="32" value="'.$object->skype.'"></td></tr>';
            }

            // Phone / Fax
            print '<tr><td>'.fieldLabel('Phone','phone').'</td>';
	        print '<td><input type="text" name="phone" id="phone" value="'.$object->phone.'"></td>';
            print '<td>'.fieldLabel('Fax','fax').'</td>';
	        print '<td><input type="text" name="fax" id="fax" value="'.$object->fax.'"></td></tr>';

            // Prof ids
            $i=1; $j=0;
            while ($i <= 6)
            {
                $idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
                if ($idprof!='-')
                {
	                $key='idprof'.$i;

	                if (($j % 2) == 0) print '<tr>';

	                $idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
	                if(empty($conf->global->$idprof_mandatory))
	                    print '<td>'.fieldLabel($idprof,$key).'</td><td>';
                    else
	                    print '<td><span class="fieldrequired">'.fieldLabel($idprof,$key).'</td><td>';

	                print $formcompany->get_input_id_prof($i,$key,$object->$key,$object->country_code);
                    print '</td>';
                    if (($j % 2) == 1) print '</tr>';
                    $j++;
                }
                $i++;
            }
            if ($j % 2 == 1) print '<td colspan="2"></td></tr>';

            // VAT payers
            print '<tr><td>'.fieldLabel('VATIsUsed','assujtva_value').'</td><td>';
            print $form->selectyesno('assujtva_value',$object->tva_assuj,1);
            print '</td>';

            // VAT Code
            print '<td>'.fieldLabel('VATIntra','intra_vat').'</td>';
            print '<td>';
            $s ='<input type="text" class="flat" name="tva_intra" id="intra_vat" size="12" maxlength="20" value="'.$object->tva_intra.'">';

            if (empty($conf->global->MAIN_DISABLEVATCHECK))
            {
                $s.=' &nbsp; ';

                if ($conf->use_javascript_ajax)
                {
                    print "\n";
                    print '<script language="JavaScript" type="text/javascript">';
                    print "function CheckVAT(a) {\n";
                    print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
                    print "}\n";
                    print '</script>';
                    print "\n";
                    $s.='<a href="#" class="hideonsmartphone" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
                    $s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
                }
                else
                {
                    $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$object->country_id).'" class="hideonsmartphone" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
                }
            }
            print $s;
            print '</td>';
            print '</tr>';

            // Local Taxes
            //TODO: Place into a function to control showing by country or study better option
        	if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
            {
                print '<tr><td>'.fieldLabel($langs->transcountry("LocalTax1IsUsed",$mysoc->country_code),'localtax1assuj_value').'</td><td>';
                print $form->selectyesno('localtax1assuj_value',$object->localtax1_assuj,1);
                if(! isOnlyOneLocalTax(1))
                {
                	print '<span class="cblt1">     '.$langs->transcountry("Type",$mysoc->country_code).': ';
                	$formcompany->select_localtax(1,$object->localtax1_value, "lt1");
                	print '</span>';
                }

                print '</td><td>'.fieldLabel($langs->transcountry("LocalTax2IsUsed",$mysoc->country_code),'localtax2assuj_value').'</td><td>';
                print $form->selectyesno('localtax2assuj_value',$object->localtax2_assuj,1);
	            if  (! isOnlyOneLocalTax(2))
	            {
	            		print '<span class="cblt2">     '.$langs->transcountry("Type",$mysoc->country_code).': ';
	                	$formcompany->select_localtax(2,$object->localtax2_value, "lt2");
                		print '</span>';
                }
                print '</td></tr>';

            }
            elseif($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj!="1")
            {
                print '<tr><td>'.fieldLabel($langs->transcountry("LocalTax1IsUsed",$mysoc->country_code),'localtax1assuj_value').'</td><td colspan="3">';
                print $form->selectyesno('localtax1assuj_value',$object->localtax1_assuj,1);
                if(! isOnlyOneLocalTax(1))
                {
                	print '<span class="cblt1">     '.$langs->transcountry("Type",$mysoc->country_code).': ';
	                $formcompany->select_localtax(1,$object->localtax1_value, "lt1");
                	print '</span>';
                }
                print '</td></tr>';

            }
            elseif($mysoc->localtax2_assuj=="1" && $mysoc->localtax1_assuj!="1")
            {
                print '<tr><td>'.fieldLabel($langs->transcountry("LocalTax2IsUsed",$mysoc->country_code),'localtax2assuj_value').'</td><td colspan="3">';
                print $form->selectyesno('localtax2assuj_value',$object->localtax2_assuj,1);
                if(! isOnlyOneLocalTax(2))
                {
                	print '<span class="cblt2">     '.$langs->transcountry("Type",$mysoc->country_code).': ';
                	$formcompany->select_localtax(2,$object->localtax2_value, "lt2");
                	print '</span>';
                }
                print '</td></tr>';
            }

            // Type - Size
            print '<tr><td>'.fieldLabel('ThirdPartyType','typent_id').'</td><td>';
            print $form->selectarray("typent_id",$formcompany->typent_array(0), $object->typent_id, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            print '</td>';
            print '<td>'.fieldLabel('Staff','effectif_id').'</td><td>';
            print $form->selectarray("effectif_id",$formcompany->effectif_array(0), $object->effectif_id);
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            print '</td></tr>';

            // Juridical type
            print '<tr><td>'.fieldLabel('JuridicalStatus','forme_juridique_code').'</td><td colspan="3">';
            print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
            print '</td></tr>';

            // Capital
            print '<tr><td>'.fieldLabel('Capital','capital').'</td>';
	        print '<td colspan="3"><input type="text" name="capital" id="capital" size="10" value="'.$object->capital.'"><font class="hideonsmartphone">'.$langs->trans("Currency".$conf->currency).'</font></td></tr>';

            // Default language
            if (! empty($conf->global->MAIN_MULTILANGS))
            {
                print '<tr><td>'.fieldLabel('DefaultLang','default_lang').'</td><td colspan="3">'."\n";
                print $formadmin->select_language($object->default_lang,'default_lang',0,0,1);
                print '</td>';
                print '</tr>';
            }

			// Categories
			if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
			{
				// Customer
				if ($object->prospect || $object->client) {
					print '<tr><td>' . fieldLabel('CustomersCategoriesShort', 'custcats') . '</td>';
					print '<td colspan="3">';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, null, null, null, 1);
					$c = new Categorie($db);
					$cats = $c->containing($object->id, Categorie::TYPE_CUSTOMER);
					foreach ($cats as $cat) {
						$arrayselected[] = $cat->id;
					}
					print $form->multiselectarray('custcats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
					print "</td></tr>";
				}

				// Supplier
				if ($object->fournisseur) {
					print '<tr><td>' . fieldLabel('SuppliersCategoriesShort', 'suppcats') . '</td>';
					print '<td colspan="3">';
					$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, null, null, null, 1);
					$c = new Categorie($db);
					$cats = $c->containing($object->id, Categorie::TYPE_SUPPLIER);
					foreach ($cats as $cat) {
						$arrayselected[] = $cat->id;
					}
					print $form->multiselectarray('suppcats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
					print "</td></tr>";
				}
			}

            // Other attributes
            $parameters=array('colspan' => ' colspan="3"', 'colspanvalue' => '3');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields,'edit');
            }

            // Webservices url/key
            if (!empty($conf->syncsupplierwebservices->enabled)) {
                print '<tr><td>'.fieldLabel('WebServiceURL','webservices_url').'</td>';
                print '<td><input type="text" name="webservices_url" id="webservices_url" size="32" value="'.$object->webservices_url.'"></td>';
                print '<td>'.fieldLabel('WebServiceKey','webservices_key').'</td>';
                print '<td><input type="text" name="webservices_key" id="webservices_key" size="32" value="'.$object->webservices_key.'"></td></tr>';
            }

			// Incoterms
			if (!empty($conf->incoterm->enabled))
			{
				print '<tr>';
				print '<td>'.fieldLabel('IncotermLabel','incoterm_id').'</td>';
	            print '<td colspan="3" class="maxwidthonsmartphone">';
	            print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''));
				print '</td></tr>';
			}

            // Logo
            print '<tr class="hideonsmartphone">';
            print '<td>'.fieldLabel('Logo','photoinput').'</td>';
            print '<td colspan="3">';
            if ($object->logo) print $form->showphoto('societe',$object);
            $caneditfield=1;
            if ($caneditfield)
            {
                if ($object->logo) print "<br>\n";
                print '<table class="nobordernopadding">';
                if ($object->logo) print '<tr><td><input type="checkbox" class="flat" name="deletephoto photodelete" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
                //print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
                print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
                print '</table>';
            }
            print '</td>';
            print '</tr>';

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
        $res=$object->fetch_optionals($object->id,$extralabels);
        //if ($res < 0) { dol_print_error($db); exit; }


        $head = societe_prepare_head($object);

        dol_fiche_head($head, 'card', $langs->trans("ThirdParty"),0,'company');

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
				    'value' => $form->select_company('', 'soc_origin', 's.rowid != '.$object->id, 1, 0, 0, array(), 0, 'minwidth200')
			    )
		    );

		    print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id, $langs->trans("MergeThirdparties"), $langs->trans("ConfirmMergeThirdparties"), "confirm_merge", $formquestion, 'no', 1, 190);
	    }

        dol_htmloutput_errors($error,$errors);

        $showlogo=$object->logo;
        $showbarcode=empty($conf->barcode->enabled)?0:1;
        if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;

        print '<table class="border" width="100%">';

        // Ref
        /*
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
        print '<td colspan="2">';
        print $fuser->id;
        print '</td>';
        print '</tr>';
        */

        // Name
        print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
        print '<td colspan="3">';
        print $form->showrefnav($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        print '</td>';
        print '</tr>';

	    // Alias names (commercial, trademark or alias names)
	    print '<tr><td>'.$langs->trans('AliasNames').'</td><td colspan="3">';
	    print $object->name_alias;
	    print "</td></tr>";

        // Logo+barcode
        $rowspan=6;
        if (! empty($conf->global->SOCIETE_USEPREFIX)) $rowspan++;
        if (! empty($object->client)) $rowspan++;
        if (! empty($conf->fournisseur->enabled) && $object->fournisseur && ! empty($user->rights->fournisseur->lire)) $rowspan++;
        if (! empty($conf->barcode->enabled)) $rowspan++;
        if (empty($conf->global->SOCIETE_DISABLE_STATE)) $rowspan++;
        $htmllogobar='';
        if ($showlogo || $showbarcode)
        {
            $htmllogobar.='<td rowspan="'.$rowspan.'" style="text-align: center;" width="25%">';
            if ($showlogo)   $htmllogobar.=$form->showphoto('societe',$object);
            if ($showlogo && $showbarcode) $htmllogobar.='<br><br>';
            if ($showbarcode) $htmllogobar.=$form->showbarcode($object);
            $htmllogobar.='</td>';
        }

        // Prefix
        if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
        {
            print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">'.$object->prefix_comm.'</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Customer code
        if ($object->client)
        {
            print '<tr><td>';
            print $langs->trans('CustomerCode').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
            print $object->code_client;
            if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
            print '</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Supplier code
        if (! empty($conf->fournisseur->enabled) && $object->fournisseur && ! empty($user->rights->fournisseur->lire))
        {
            print '<tr><td>';
            print $langs->trans('SupplierCode').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
            print $object->code_fournisseur;
            if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
            print '</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Barcode
        if (! empty($conf->barcode->enabled))
        {
            print '<tr><td>';
            print $langs->trans('Gencod').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">'.$object->barcode;
            print '</td>';
            print $htmllogobar; $htmllogobar='';
            print '</tr>';
        }

        // Status
        print '<tr><td>'.$langs->trans("Status").'</td>';
        print '<td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        if (! empty($conf->use_javascript_ajax) && $user->rights->societe->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
            print ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
        } else {
            print $object->getLibStatut(2);
        }
        print '</td>';
        print $htmllogobar; $htmllogobar='';
        print '</tr>';

        // Address
        print '<tr><td class="tdtop">'.$langs->trans('Address').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        dol_print_address($object->address,'gmap','thirdparty',$object->id);
        print '</td></tr>';

        // Zip / Town
        print '<tr><td width="25%">'.$langs->trans('Zip').' / '.$langs->trans("Town").'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        print $object->zip.($object->zip && $object->town?" / ":"").$object->town;
        print '</td>';
        print '</tr>';

        // Country
        print '<tr><td>'.$langs->trans("Country").'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'" class="nowrap">';
    	if (! empty($object->country_code))
    	{
           	//$img=picto_from_langcode($object->country_code);
           	$img='';
           	if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
           	else print ($img?$img.' ':'').$object->country;
    	}
        print '</td></tr>';

        // State
        if (empty($conf->global->SOCIETE_DISABLE_STATE)) print '<tr><td>'.$langs->trans('State').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">'.$object->state.'</td>';

        // EMail
        print '<tr><td>'.$langs->trans('EMail').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        print dol_print_email($object->email,0,$object->id,'AC_EMAIL');
        print '</td></tr>';

        // Web
        print '<tr><td>'.$langs->trans('Web').'</td><td colspan="'.(2+(($showlogo || $showbarcode)?0:1)).'">';
        print dol_print_url($object->url);
        print '</td></tr>';

        // Skype
        if (! empty($conf->skype->enabled))
        {
            print '<tr><td>'.$langs->trans('Skype').'</td><td colspan="3">';
            print dol_print_skype($object->skype,0,$object->id,'AC_SKYPE');
            print '</td></tr>';
        }

        // Phone / Fax
        print '<tr><td>'.$langs->trans('Phone').'</td><td style="min-width: 25%;">'.dol_print_phone($object->phone,$object->country_code,0,$object->id,'AC_TEL').'</td>';
        print '<td>'.$langs->trans('Fax').'</td><td style="min-width: 25%;">'.dol_print_phone($object->fax,$object->country_code,0,$object->id,'AC_FAX').'</td></tr>';

        // Prof ids
        $i=1; $j=0;
        while ($i <= 6)
        {
            $idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
            if ($idprof!='-')
            {
                if (($j % 2) == 0) print '<tr>';
                print '<td>'.$idprof.'</td><td>';
                $key='idprof'.$i;
                print $object->$key;
                if ($object->$key)
                {
                    if ($object->id_prof_check($i,$object) > 0) print ' &nbsp; '.$object->id_prof_url($i,$object);
                    else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
                }
                print '</td>';
                if (($j % 2) == 1) print '</tr>';
                $j++;
            }
            $i++;
        }
        if ($j % 2 == 1)  print '<td colspan="2"></td></tr>';

        // VAT payers
        print '<tr><td>';
        print $langs->trans('VATIsUsed');
        print '</td><td>';
        print yn($object->tva_assuj);
        print '</td>';

        // VAT Code
        print '<td class="nowrap">'.$langs->trans('VATIntra').'</td><td>';
        if ($object->tva_intra)
        {
            $s='';
            $s.=$object->tva_intra;
            $s.='<input type="hidden" id="tva_intra" name="tva_intra" size="12" maxlength="20" value="'.$object->tva_intra.'">';

            if (empty($conf->global->MAIN_DISABLEVATCHECK))
            {
                $s.=' &nbsp; ';

                if ($conf->use_javascript_ajax)
                {
                    print "\n";
                    print '<script language="JavaScript" type="text/javascript">';
                    print "function CheckVAT(a) {\n";
                    print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,285);\n";
                    print "}\n";
                    print '</script>';
                    print "\n";
                    $s.='<a href="#" class="hideonsmartphone" onclick="javascript: CheckVAT( $(\'#tva_intra\').val() );">'.$langs->trans("VATIntraCheck").'</a>';
                    $s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
                }
                else
                {
                    $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$object->country_id).'" class="hideonsmartphone" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
                }
            }
            print $s;
        }
        else
        {
            print '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        // Local Taxes
        //TODO: Place into a function to control showing by country or study better option
        if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td>';
            print yn($object->localtax1_assuj);
            print '</td><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td>';
            print yn($object->localtax2_assuj);
            print '</td></tr>';

            if($object->localtax1_assuj=="1" && (! isOnlyOneLocalTax(1)))
            {
            	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
            	print '<input type="hidden" name="action" value="set_localtax1">';
            	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            	print '<tr><td>'.$langs->transcountry("TypeLocaltax1", $mysoc->country_code).' <a href="'.$_SERVER["PHP_SELF"].'?action=editRE&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</td>';
            	if($action == 'editRE')
            	{
            		print '<td align="left">';
            		$formcompany->select_localtax(1,$object->localtax1_value, "lt1");
            		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            	}
            	else
            	{
            		print '<td>'.$object->localtax1_value.'</td>';
            	}
            	print '</tr></form>';
            }
            if($object->localtax2_assuj=="1" && (! isOnlyOneLocalTax(2)))
            {
            	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
            	print '<input type="hidden" name="action" value="set_localtax2">';
            	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            	print '<tr><td>'.$langs->transcountry("TypeLocaltax2", $mysoc->country_code).'<a href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</td>';
            	if($action == 'editIRPF'){
            		print '<td align="left">';
            		$formcompany->select_localtax(2,$object->localtax2_value, "lt2");
            		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            	}else{
            		print '<td>'.$object->localtax2_value.'</td>';
            	}
            	print '</tr></form>';
            }
        }
        elseif($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj!="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print yn($object->localtax1_assuj);
            print '</td><tr>';
            if($object->localtax1_assuj=="1" && (! isOnlyOneLocalTax(1)))
            {
            	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
            	print '<input type="hidden" name="action" value="set_localtax1">';
            	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            	print '<tr><td> '.$langs->transcountry("TypeLocaltax1", $mysoc->country_code).'<a href="'.$_SERVER["PHP_SELF"].'?action=editRE&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</td>';
            	if($action == 'editRE'){
            		print '<td align="left">';
            		$formcompany->select_localtax(1,$object->localtax1_value, "lt1");
            		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            	}else{
            		print '<td>'.$object->localtax1_value.'</td>';
            	}
            	print '</tr></form>';

            }
        }
        elseif($mysoc->localtax2_assuj=="1" && $mysoc->localtax1_assuj!="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print yn($object->localtax2_assuj);
            print '</td><tr>';
            if($object->localtax2_assuj=="1" && (! isOnlyOneLocalTax(2)))
            {

            	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'">';
            	print '<input type="hidden" name="action" value="set_localtax2">';
            	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            	print '<tr><td> '.$langs->transcountry("TypeLocaltax2", $mysoc->country_code).' <a href="'.$_SERVER["PHP_SELF"].'?action=editIRPF&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</td>';
            	if($action == 'editIRPF'){
            		print '<td align="left">';
            		$formcompany->select_localtax(2,$object->localtax2_value, "lt2");
            		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            	}else{
            		print '<td>'.$object->localtax2_value.'</td>';
            	}
            	print '</tr></form>';

            }
        }
/*
        if ($mysoc->country_code=='ES' && $mysoc->localtax2_assuj!="1" && ! empty($conf->fournisseur->enabled) && $object->fournisseur==1)
        {
        	print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print yn($object->localtax2_assuj);
            print '</td><tr>';
        }
*/
        // Type + Staff
        $arr = $formcompany->typent_array(1);
        $object->typent= $arr[$object->typent_code];
        print '<tr><td>'.$langs->trans("ThirdPartyType").'</td><td>'.$object->typent.'</td><td>'.$langs->trans("Staff").'</td><td>'.$object->effectif.'</td></tr>';

        // Legal
        print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$object->forme_juridique.'</td></tr>';

        // Capital
        print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3">';
        if ($object->capital) print price($object->capital,'',$langs,0,-1,-1, $conf->currency);
        else print '&nbsp;';
        print '</td></tr>';

        // Default language
        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
            print '<tr><td>'.$langs->trans("DefaultLang").'</td><td colspan="3">';
            //$s=picto_from_langcode($object->default_lang);
            //print ($s?$s.' ':'');
            $langs->load("languages");
            $labellang = ($object->default_lang?$langs->trans('Language_'.$object->default_lang):'');
            print $labellang;
            print '</td></tr>';
        }

		// Tags / categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			// Customer
			if ($object->prospect || $object->client) {
				print '<tr><td>' . $langs->trans("CustomersCategoriesShort") . '</td>';
				print '<td colspan="3">';
				print $form->showCategories($object->id, 'customer', 1);
				print "</td></tr>";
			}

			// Supplier
			if ($object->fournisseur) {
				print '<tr><td>' . $langs->trans("SuppliersCategoriesShort") . '</td>';
				print '<td colspan="3">';
				print $form->showCategories($object->id, 'supplier', 1);
				print "</td></tr>";
			}
		}

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans('IncotermLabel');
            print '<td><td align="right">';
            if ($user->rights->societe->creer) print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
            else print '&nbsp;';
            print '</td></tr></table>';
            print '</td>';
            print '<td colspan="3">';
			if ($action != 'editincoterm')
			{
				print $form->textwithpicto($object->display_incoterms(), $object->libelle_incoterms, 1);
			}
			else
			{
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''), $_SERVER['PHP_SELF'].'?socid='.$object->id);
			}
            print '</td></tr>';
		}

        // Other attributes
        $parameters=array('socid'=>$socid, 'colspan' => ' colspan="3"', 'colspanvalue' => '3');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields);
        }

        // Ban
        if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
        {
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans('RIB');
            print '<td><td align="right">';
            if ($user->rights->societe->creer) print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'">'.img_edit().'</a>';
            else print '&nbsp;';
            print '</td></tr></table>';
            print '</td>';
            print '<td colspan="3">';
            print $object->display_rib();
            print '</td></tr>';
        }

        // Parent company
        if (empty($conf->global->SOCIETE_DISABLE_PARENTCOMPANY))
        {
        	// Payment term
        	print '<tr><td>';
        	print '<table class="nobordernopadding" width="100%"><tr><td>';
        	print $langs->trans('ParentCompany');
        	print '</td>';
        	if ($action != 'editparentcompany') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editparentcompany&amp;socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'),1).'</a></td>';
        	print '</tr></table>';
        	print '</td><td colspan="3">';
        	if ($action == 'editparentcompany')
        	{
        		$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->parent,'editparentcompany','s.rowid <> '.$object->id,1);
        	}
        	else
        	{
        		$form->form_thirdparty($_SERVER['PHP_SELF'].'?socid='.$object->id,$object->parent,'none','s.rowid <> '.$object->id,1);
        	}
        	print '</td>';
        	print '</tr>';
        }

        // Sales representative
        include DOL_DOCUMENT_ROOT.'/societe/tpl/linesalesrepresentative.tpl.php';

        // Module Adherent
        if (! empty($conf->adherent->enabled))
        {
            $langs->load("members");
            print '<tr><td width="25%" class="tdtop">'.$langs->trans("LinkedToDolibarrMember").'</td>';
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

        print '</table>';

        dol_fiche_end();


        /*
         *  Actions
         */
        print '<div class="tabsAction">'."\n";

		$parameters=array();
		$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook))
		{
	        if (! empty($object->email))
	        {
	        	$langs->load("mails");
	        	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendMail').'</a></div>';
	        }
	        else
			{
	        	$langs->load("mails");
	       		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans('SendMail').'</a></div>';
	        }

	        if ($user->rights->societe->creer)
	        {
	            print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>'."\n";
	        }

	        if ($user->rights->societe->supprimer)
	        {
	        	print '<div class="inline-block divButAction"><a class="butActionDelete" href="soc.php?action=merge&socid='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("MergeThirdparties")).'">'.$langs->trans('Merge').'</a></div>';
	        }

	        if ($user->rights->societe->supprimer)
	        {
	            if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))	// We can't use preloaded confirm form with jmobile
	            {
	                print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
	            }
	            else
				{
	                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a></div>'."\n";
	            }
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

			print '<br>';
			print load_fiche_titre($langs->trans($titreform));

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
			if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
			{
				$formmail->trackid='thi'.$object->id;
			}
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
			$formmail->substit['__SIGNATURE__']=$user->signature;
			$formmail->substit['__PERSONALIZED__']='';
			$formmail->substit['__CONTACTCIVNAME__']='';

			//Find the good contact adress
			/*
			$custcontact='';
			$contactarr=array();
			$contactarr=$object->liste_contact(-1,'external');

			if (is_array($contactarr) && count($contactarr)>0)
			{
			foreach($contactarr as $contact)
			{
			if ($contact['libelle']==$langs->trans('TypeContact_facture_external_BILLING')) {

			require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

			$contactstatic=new Contact($db);
			$contactstatic->fetch($contact['id']);
			$custcontact=$contactstatic->getFullName($langs,1);
			}
			}

			if (!empty($custcontact)) {
			$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
			}*/


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

			print '<br>';
		}
		else
		{

	        if (empty($conf->global->SOCIETE_DISABLE_BUILDDOC))
	        {
				print '<div class="fichecenter"><div class="fichehalfleft">';
	            print '<a name="builddoc"></a>'; // ancre

	            /*
	             * Documents generes
	             */
	            $filedir=$conf->societe->multidir_output[$object->entity].'/'.$object->id;
	            $urlsource=$_SERVER["PHP_SELF"]."?socid=".$object->id;
	            $genallowed=$user->rights->societe->creer;
	            $delallowed=$user->rights->societe->supprimer;

	            $var=true;

	            print $formfile->showdocuments('company', $object->id, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 0, 0, 0, 28, 0, '', 0, '', $object->default_lang);

				print '</div><div class="fichehalfright"><div class="ficheaddleft">';


				print '</div></div></div>';

	            print '<br>';
	        }

	        print '<div class="fichecenter"><br></div>';

	        // Subsidiaries list
	        $result=show_subsidiaries($conf,$langs,$db,$object);

	        // Contacts list
	        if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
	        {
	            $result=show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	        }

	        // Addresses list
	        if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT))
	        {
	        	$result=show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	        }

	        // Projects list
	        $result=show_projects($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
		}
    }

}


// End of page
llxFooter();
$db->close();
