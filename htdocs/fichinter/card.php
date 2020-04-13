<?php
/* Copyright (C) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2018  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2014-2018  Charlene Benke          <charlies@patas-monkey.com>
 * Copyright (C) 2015-2016  Abbes Bahfir            <bafbes@gmail.com>
 * Copyright (C) 2018 		Philippe Grand       	<philippe.grand@atoo-net.com>
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
 *	\file       htdocs/fichinter/card.php
 *	\brief      Page of intervention
 *	\ingroup    ficheinter
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (!empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if ($conf->contrat->enabled)
{
	require_once DOL_DOCUMENT_ROOT."/core/class/html.formcontract.class.php";
	require_once DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php";
}
if (!empty($conf->global->FICHEINTER_ADDON) && is_readable(DOL_DOCUMENT_ROOT."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.".php"))
{
	require_once DOL_DOCUMENT_ROOT."/core/modules/fichinter/mod_".$conf->global->FICHEINTER_ADDON.'.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'interventions'));

$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$contratid = GETPOST('contratid', 'int');
$action		= GETPOST('action', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$mesg		= GETPOST('msg', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ?GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility
$note_public = GETPOST('note_public', 'none');
$lineid = GETPOST('line_id', 'int');

//PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('interventioncard', 'globalcard'));

$object = new Fichinter($db);
$extrafields = new ExtraFields($db);

$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref))
{
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) $ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error('', $object->error);
}

$permissionnote = $user->rights->ficheinter->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->ficheinter->creer; // Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->ficheinter->creer)
	{
		if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers'))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		}
		else
		{
			if ($object->id > 0)
			{
				// Because createFromClone modifies the object, we must clone it so that we can restore it later
				$orig = clone $object;

				$result = $object->createFromClone($user, $socid);
				if ($result > 0)
				{
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action = '';
				}
			}
		}
	}

	if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->ficheinter->creer)
	{
		$result = $object->setValid($user);

		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
				if (!empty($newlang))
				{
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->modelpdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}

	elseif ($action == 'confirm_modify' && $confirm == 'yes' && $user->rights->ficheinter->creer)
	{
		$result = $object->setDraft($user);
		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
				if (!empty($newlang))
				{
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->modelpdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}

	elseif ($action == 'add' && $user->rights->ficheinter->creer)
	{
	    $object->socid = $socid;
	    $object->duration = GETPOST('duration', 'int');
	    $object->fk_project		= GETPOST('projectid', 'int');
	    $object->fk_contrat		= GETPOST('contratid', 'int');
	    $object->author = $user->id;
	    $object->description	= GETPOST('description', 'none');
	    $object->ref = $ref;
	    $object->modelpdf = GETPOST('model', 'alpha');
	    $object->note_private = GETPOST('note_private', 'none');
	    $object->note_public = GETPOST('note_public', 'none');

		if ($object->socid > 0)
		{
			// If creation from another object of another module (Example: origin=propal, originid=1)
			if (!empty($origin) && !empty($originid))
			{
				// Parse element/subelement (ex: project_task)
				$element = $subelement = $_POST['origin'];
				if (preg_match('/^([^_]+)_([^_]+)/i', $_POST['origin'], $regs))
				{
					$element = $regs[1];
					$subelement = $regs[2];
				}

				// For compatibility
				if ($element == 'order') {
					$element = $subelement = 'commande';
				}
				if ($element == 'propal') {
					$element = 'comm/propal'; $subelement = 'propal';
				}
				if ($element == 'contract') {
					$element = $subelement = 'contrat';
				}

				$object->origin    = $origin;
				$object->origin_id = $originid;

				// Possibility to add external linked objects with hooks
				$object->linked_objects[$object->origin] = $object->origin_id;
				if (is_array($_POST['other_linked_objects']) && !empty($_POST['other_linked_objects']))
				{
					$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
				}

				// Extrafields
				$extrafields = new ExtraFields($db);
				$array_options = $extrafields->getOptionalsFromPost($object->table_element);

		        $object->array_options = $array_options;

				$id = $object->create($user);

				if ($id > 0)
				{
					dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

					$classname = ucfirst($subelement);
					$srcobject = new $classname($db);

					dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
					$result = $srcobject->fetch($object->origin_id);
					if ($result > 0)
					{
						$srcobject->fetch_thirdparty();
						$lines = $srcobject->lines;
						if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
						{
							$srcobject->fetch_lines();
							$lines = $srcobject->lines;
						}

						$fk_parent_line = 0;
						$num = count($lines);

						for ($i = 0; $i < $num; $i++)
						{
							$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : Product::TYPE_PRODUCT);

							if ($product_type == Product::TYPE_SERVICE || !empty($conf->global->FICHINTER_PRINT_PRODUCTS)) { //only services except if config includes products
								$duration = 3600; // Default to one hour

								// Predefined products & services
								if ($lines[$i]->fk_product > 0)
								{
									$prod = new Product($db);
									$prod->id = $lines[$i]->fk_product;

									// Define output language
									if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
										$prod->getMultiLangs();
										// We show if duration is present on service (so we get it)
										$prod->fetch($lines[$i]->fk_product);
										$outputlangs = $langs;
										$newlang = '';
										if (empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
										if (empty($newlang)) $newlang = $srcobject->thirdparty->default_lang;
										if (!empty($newlang)) {
											$outputlangs = new Translate("", $conf);
											$outputlangs->setDefaultLang($newlang);
										}
										$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["libelle"])) ? $prod->multilangs[$outputlangs->defaultlang]["libelle"] : $lines[$i]->product_label;
									} else {
										$prod->fetch($lines[$i]->fk_product);
										$label = $lines[$i]->product_label;
									}

									if ($prod->duration_value && $conf->global->FICHINTER_USE_SERVICE_DURATION) {
										switch ($prod->duration_unit) {
											default:
											case 'h':
												$mult = 3600;
												break;
											case 'd':
												$mult = 3600 * 24;
												break;
											case 'w':
												$mult = 3600 * 24 * 7;
												break;
											case 'm':
												$mult = (int) 3600 * 24 * (365 / 12); // Average month duration
												break;
											case 'y':
												$mult = 3600 * 24 * 365;
												break;
										}
										$duration = $prod->duration_value * $mult * $lines[$i]->qty;
									}

									$desc = $lines[$i]->product_ref;
									$desc .= ' - ';
									$desc .= $label;
									$desc .= '<br>';
								}
								// Common part (predefined or free line)
								$desc .= dol_htmlentitiesbr($lines[$i]->desc);
								$desc .= '<br>';
								$desc .= ' ('.$langs->trans('Quantity').': '.$lines[$i]->qty.')';

								$timearray = dol_getdate(mktime());
								$date_intervention = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);

								if ($product_type == Product::TYPE_PRODUCT) {
									$duration = 0;
								}

								$predef = '';

								// Extrafields
								$extrafields->fetch_name_optionals_label($object->table_element_line);
								$array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);

								$result = $object->addline(
									$user,
			                        $id,
			                        $desc,
						            $date_intervention,
	                 				$duration,
	                 				$array_options
			                    );

								if ($result < 0)
								{
									$error++;
									break;
								}
							}
						}
		            }
		            else
		            {
		                $mesg = $srcobject->error;
		                $error++;
		            }
		        }
		        else
		        {
		            $mesg = $object->error;
		            $error++;
		        }
		    }
		    else
		    {
		    	// Fill array 'array_options' with data from add form
		    	$ret = $extrafields->setOptionalsFromPost(null, $object);
		    	if ($ret < 0) {
		    		$error++;
		    		$action = 'create';
		    	}

		    	if (!$error)
		    	{
		    		// Extrafields
		    		$array_options = $extrafields->getOptionalsFromPost($object->table_element);

		    		$object->array_options = $array_options;

		    		$result = $object->create($user);
		    		if ($result > 0)
		    		{
		    			$id = $result; // Force raffraichissement sur fiche venant d'etre cree
		    		}
		    		else
		    		{
		    			$langs->load("errors");
		    			setEventMessages($object->error, $object->errors, 'errors');
		    			$action = 'create';
		    		}
		    	}
	        }
	    }
	    else
	    {
	    	$mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdParty")).'</div>';
	        $action = 'create';
	    }
	}

	elseif ($action == 'update' && $user->rights->ficheinter->creer)
	{
		$object->socid = $socid;
		$object->fk_project		= GETPOST('projectid', 'int');
		$object->fk_contrat		= GETPOST('contratid', 'int');
		$object->author = $user->id;
		$object->description	= GETPOST('description', 'alpha');
		$object->ref = $ref;

		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Set into a project
	elseif ($action == 'classin' && $user->rights->ficheinter->creer)
	{
		$result = $object->setProject(GETPOST('projectid', 'int'));
		if ($result < 0) dol_print_error($db, $object->error);
	}

	// Set into a contract
	elseif ($action == 'setcontract' && $user->rights->contrat->creer)
	{
		$result = $object->set_contrat($user, GETPOST('contratid', 'int'));
		if ($result < 0) dol_print_error($db, $object->error);
	}

	elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->ficheinter->supprimer)
	{
		$result = $object->delete($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		header('Location: '.DOL_URL_ROOT.'/fichinter/list.php?leftmenu=ficheinter&restore_lastsearch_values=1');
		exit;
	}

	elseif ($action == 'setdescription' && $user->rights->ficheinter->creer)
	{
		$result = $object->set_description($user, GETPOST('description'));
		if ($result < 0) dol_print_error($db, $object->error);
	}

	// Add line
	elseif ($action == "addline" && $user->rights->ficheinter->creer)
	{
		if (!GETPOST('np_desc', 'none') && empty($conf->global->FICHINTER_EMPTY_LINE_DESC))
 		{
			$mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")).'</div>';
			$error++;
		}
		if (empty($conf->global->FICHINTER_WITHOUT_DURATION) && !GETPOST('durationhour', 'int') && !GETPOST('durationmin', 'int'))
		{
			$mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Duration")).'</div>';
			$error++;
		}
		if (empty($conf->global->FICHINTER_WITHOUT_DURATION) && GETPOST('durationhour', 'int') >= 24 && GETPOST('durationmin', 'int') > 0)
		{
			$mesg = '<div class="error">'.$langs->trans("ErrorValueTooHigh").'</div>';
			$error++;
		}
		if (!$error)
		{
			$db->begin();

			$desc = GETPOST('np_desc', 'none');
			$date_intervention = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
			$duration = empty($conf->global->FICHINTER_WITHOUT_DURATION) ?convertTime2Seconds(GETPOST('durationhour', 'int'), GETPOST('durationmin', 'int')) : 0;


			// Extrafields
			$extrafields->fetch_name_optionals_label($object->table_element_line);
			$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);

        	$result = $object->addline(
				$user,
	            $id,
	            $desc,
	            $date_intervention,
	            $duration,
	            $array_options
	        );

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
			if (!empty($newlang))
			{
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			if ($result >= 0)
			{
				$db->commit();

				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) fichinter_create($db, $object, $object->modelpdf, $outputlangs);
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			}
			else
			{
				$mesg = $object->error;
				$db->rollback();
			}
		}
	}

	// Classify Billed
	elseif ($action == 'classifybilled' && $user->rights->ficheinter->creer)
	{
		$result = $object->setStatut(2);
		if ($result > 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Classify unbilled
	elseif ($action == 'classifyunbilled' && $user->rights->ficheinter->creer)
	{
		$result = $object->setStatut(1);
		if ($result > 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			$mesg = '<div class="error">'.$object->error.'</div>';
		}
	}

	// Classify Done
	elseif ($action == 'classifydone' && $user->rights->ficheinter->creer)
	{
	    $result = $object->setStatut(3);
	    if ($result > 0)
	    {
	        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	        exit;
	    }
	    else
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	/*
	 *  Mise a jour d'une ligne d'intervention
	 */
	elseif ($action == 'updateline' && $user->rights->ficheinter->creer && GETPOST('save', 'alpha') == $langs->trans("Save")) {
		$objectline = new FichinterLigne($db);
		if ($objectline->fetch($lineid) <= 0)
		{
			dol_print_error($db);
			exit;
		}

		if ($object->fetch($objectline->fk_fichinter) <= 0)
		{
			dol_print_error($db);
			exit;
		}
		$object->fetch_thirdparty();

		$desc = GETPOST('np_desc');
		$date_inter = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
		$duration = convertTime2Seconds(GETPOST('durationhour', 'int'), GETPOST('durationmin', 'int'));

	    $objectline->datei = $date_inter;
	    $objectline->desc = $desc;
	    $objectline->duration = $duration;

		// Extrafields
		$extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		$objectline->array_options = $array_options;

		$result = $objectline->update($user);
	    if ($result < 0)
	    {
	        dol_print_error($db);
	        exit;
	    }

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
		if (!empty($newlang))
		{
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) fichinter_create($db, $object, $object->modelpdf, $outputlangs);

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}

	/*
	 *  Supprime une ligne d'intervention AVEC confirmation
	*/
	elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->ficheinter->creer)
	{
		$objectline = new FichinterLigne($db);
		if ($objectline->fetch($lineid) <= 0)
		{
			dol_print_error($db);
			exit;
		}
		$result = $objectline->deleteline($user);

		if ($object->fetch($objectline->fk_fichinter) <= 0)
		{
			dol_print_error($db);
			exit;
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
		if (!empty($newlang))
		{
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) fichinter_create($db, $object, $object->modelpdf, $outputlangs);
	}

	/*
	 * Set position of lines
	 */
	elseif ($action == 'up' && $user->rights->ficheinter->creer)
	{
		$object->line_up($lineid);

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
		if (!empty($newlang))
		{
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) fichinter_create($db, $object, $object->modelpdf, $outputlangs);

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$lineid);
		exit;
	}

	elseif ($action == 'down' && $user->rights->ficheinter->creer)
	{
		$object->line_down($lineid);

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
		if (!empty($newlang))
		{
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) fichinter_create($db, $object, $object->modelpdf, $outputlangs);

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$lineid);
		exit;
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'FICHINTER_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_FICHINTER_TO';
	$trackid = 'int'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->ficheinter->dir_output;
	$permissiontoadd = $user->rights->ficheinter->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'none'));
		if ($ret < 0) $error++;

		if (!$error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('INTERVENTION_MODIFY');
			if ($result < 0)
			{
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->ficheinter->creer)
	{
		if ($action == 'addcontact')
		{
			if ($result > 0 && $id > 0)
			{
				$contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
				$result = $object->add_contact($contactid, GETPOST('type', 'int'), GETPOST('source', 'alpha'));
			}

			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else
			{
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$langs->load("errors");
					$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
				}
				else
				{
					$mesg = '<div class="error">'.$object->error.'</div>';
				}
			}
		}

		// bascule du statut d'un contact
		elseif ($action == 'swapstatut')
		{
			$result = $object->swapContactStatus(GETPOST('ligne', 'int'));
		}

		// Efface un contact
		elseif ($action == 'deletecontact')
		{
			$result = $object->delete_contact(GETPOST('lineid', 'int'));

			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else {
				dol_print_error($db);
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
if ($conf->contrat->enabled) $formcontract = new FormContract($db);
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

llxHeader('', $langs->trans("Intervention"));

if ($action == 'create')
{
	// Create new intervention

	$soc = new Societe($db);

	print load_fiche_titre($langs->trans("AddIntervention"), '', 'title_commercial');

	dol_htmloutput_mesg($mesg);

	if ($socid) $res = $soc->fetch($socid);

	if (GETPOST('origin', 'alphanohtml') && GETPOST('originid', 'int'))
	{
		// Parse element/subelement (ex: project_task)
		$regs = array();
		$element = $subelement = GETPOST('origin', 'alphanohtml');
		if (preg_match('/^([^_]+)_([^_]+)/i', GETPOST('origin', 'alphanohtml'), $regs))
		{
			$element = $regs[1];
			$subelement = $regs[2];
		}

        if ($element == 'project')
        {
            $projectid = GETPOST('originid', 'int');
        }
        else
		{
            // For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal'; $subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}

			dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch(GETPOST('originid'));
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
			{
				$objectsrc->fetch_lines();
				$lines = $objectsrc->lines;
			}
			$objectsrc->fetch_thirdparty();

			$projectid = (!empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');

			$soc = $objectsrc->thirdparty;

			$note_private = (!empty($objectsrc->note) ? $objectsrc->note : (!empty($objectsrc->note_private) ? $objectsrc->note_private : GETPOST('note_private', 'none')));
			$note_public = (!empty($objectsrc->note_public) ? $objectsrc->note_public : GETPOST('note_public', 'none'));

			// Object source contacts list
			$srccontactslist = $objectsrc->liste_contact(-1, 'external', 1);
		}
	}
	else {
		$projectid = GETPOST('projectid', 'int');
	}

	if (!$conf->global->FICHEINTER_ADDON)
	{
		dol_print_error($db, $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined"));
		exit;
	}

	$object->date = dol_now();

	$obj = $conf->global->FICHEINTER_ADDON;
	$obj = "mod_".$obj;

	//$modFicheinter = new $obj;
	//$numpr = $modFicheinter->getNextValue($soc, $object);

	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);

		print '<form name="fichinter" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="socid" value='.$soc->id.'>';
		print '<input type="hidden" name="action" value="add">';

		dol_fiche_head('');

		print '<table class="border centpercent">';

		print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("ThirdParty").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

		// Ref
		print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

		// Description (must be a textarea and not html must be allowed (used in list view)
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
		print '<td>';
		print '<textarea name="description" class="quatrevingtpercent" rows="'.ROWS_3.'">'.GETPOST('description').'</textarea>';
		print '</td></tr>';

		// Project
		if (!empty($conf->projet->enabled))
		{
			$formproject = new FormProjets($db);

			$langs->load("project");

            print '<tr><td>'.$langs->trans("Project").'</td><td>';
            /* Fix: If a project must be linked to any companies (suppliers or not), project must be not be set as limited to customer but must be not linked to any particular thirdparty
            if ($societe->fournisseur==1)
            	$numprojet=select_projects(-1,$_POST["projectid"],'projectid');
            else
            	$numprojet=select_projects($societe->id,$_POST["projectid"],'projectid');
            	*/
            $numprojet = $formproject->select_projects($soc->id, $projectid, 'projectid');
            if ($numprojet == 0)
            {
                print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
            }
            print '</td></tr>';
        }

		// Contract
		if ($conf->contrat->enabled)
		{
			$langs->load("contracts");
			print '<tr><td>'.$langs->trans("Contract").'</td><td>';
			$numcontrat = $formcontract->select_contract($soc->id, GETPOST('contratid', 'int'), 'contratid', 0, 1);
			if ($numcontrat == 0)
			{
				print ' &nbsp; <a href="'.DOL_URL_ROOT.'/contrat/card.php?socid='.$soc->id.'&action=create"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddContract").'"></span></a>';
			}
			print '</td></tr>';
		}

        // Model
        print '<tr>';
        print '<td>'.$langs->trans("DefaultModel").'</td>';
        print '<td>';
        $liste = ModelePDFFicheinter::liste_modeles($db);
        print $form->selectarray('model', $liste, $conf->global->FICHEINTER_ADDON_PDF);
        print "</td></tr>";

        // Public note
        print '<tr>';
        print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
        print '<td>';
        $doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        print $doleditor->Create(1);
        //print '<textarea name="note_public" cols="80" rows="'.ROWS_3.'">'.$note_public.'</textarea>';
        print '</td></tr>';

        // Private note
        if (empty($user->socid))
        {
        	print '<tr>';
        	print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
        	print '<td>';
        	$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        	print $doleditor->Create(1);
        	//print '<textarea name="note_private" cols="80" rows="'.ROWS_3.'">'.$note_private.'</textarea>';
        	print '</td></tr>';
        }

        // Other attributes
        $parameters = array();
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook))
		{
			print $object->showOptionals($extrafields, 'edit');
		}

        // Show link to origin object
        if (!empty($origin) && !empty($originid) && is_object($objectsrc))
        {
        	$newclassname = $classname;
        	if ($newclassname == 'Propal') $newclassname = 'CommercialProposal';
        	print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';

        	// Amount
        	/* Hide amount because we only copy services so amount may differ than source
        	print '<tr><td>' . $langs->trans('AmountHT') . '</td><td>' . price($objectsrc->total_ht) . '</td></tr>';
        	print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td>' . price($objectsrc->total_tva) . "</td></tr>";
        	if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1 RE
        	{
        		print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax1) . "</td></tr>";
        	}

        	if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2 IRPF
        	{
        		print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td>' . price($objectsrc->total_localtax2) . "</td></tr>";
        	}

        	print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td>' . price($objectsrc->total_ttc) . "</td></tr>";

        	if (!empty($conf->multicurrency->enabled))
        	{
        		print '<tr><td>' . $langs->trans('MulticurrencyAmountHT') . '</td><td>' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
        		print '<tr><td>' . $langs->trans('MulticurrencyAmountVAT') . '</td><td>' . price($objectsrc->multicurrency_total_tva) . "</td></tr>";
        		print '<tr><td>' . $langs->trans('MulticurrencyAmountTTC') . '</td><td>' . price($objectsrc->multicurrency_total_ttc) . "</td></tr>";
        	}
        	*/
        }

        print '</table>';

	    if (is_object($objectsrc))
	    {
	        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
	        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';
		} elseif ($origin == 'project' && !empty($projectid)) {
			print '<input type="hidden" name="projectid" value="'.$projectid.'">';
		}

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
    	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    	print '<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		print '</div>';

		print '</form>';

		// Show origin lines
		if (!empty($origin) && !empty($originid) && is_object($objectsrc)) {
			$title = $langs->trans('Services');
			print load_fiche_titre($title);

			print '<table class="noborder centpercent">';

			$objectsrc->printOriginLinesList(empty($conf->global->FICHINTER_PRINT_PRODUCTS) ? 'services' : ''); // Show only service, except if option FICHINTER_PRINT_PRODUCTS is on

			print '</table>';
		}
	}
	else
	{
		print '<form name="fichinter" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		dol_fiche_head('');

		if (is_object($objectsrc))
		{
			print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
			print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';
		} elseif ($origin == 'project' && !empty($projectid)) {
			print '<input type="hidden" name="projectid" value="'.$projectid.'">';
		}
		print '<table class="border centpercent">';
		print '<tr><td class="fieldrequired">'.$langs->trans("ThirdParty").'</td><td>';
		print $form->select_company('', 'socid', '', 'SelectThirdParty', 1, 0, null, 0, 'minwidth300');
		print '</td></tr>';
		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateDraftIntervention").'">';
    	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    	print '<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		print '</div>';

		print '</form>';
	}
}
elseif ($id > 0 || !empty($ref))
{
	/*
	 * Affichage en mode visu
 	 */

	$object->fetch($id, $ref);
	$object->fetch_thirdparty();

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	dol_htmloutput_mesg($mesg);

	$head = fichinter_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("InterventionCard"), -1, 'intervention');

	$formconfirm = '';

	// Confirm deletion of intervention
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteIntervention'), $langs->trans('ConfirmDeleteIntervention'), 'confirm_delete', '', 0, 1);
	}

	// Confirm validation
	if ($action == 'validate')
	{
		// on verifie si l'objet est en numerotation provisoire
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV')
		{
			$numref = $object->getNextNumRef($soc);
			if (empty($numref))
			{
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		else
		{
			$numref = $object->ref;
		}
		$text = $langs->trans('ConfirmValidateIntervention', $numref);

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateIntervention'), $text, 'confirm_validate', '', 1, 1);
	}

	// Confirm back to draft
	if ($action == 'modify')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ModifyIntervention'), $langs->trans('ConfirmModifyIntervention'), 'confirm_modify', '', 0, 1);
	}

	// Confirm deletion of line
	if ($action == 'ask_deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&line_id='.$lineid, $langs->trans('DeleteInterventionLine'), $langs->trans('ConfirmDeleteInterventionLine'), 'confirm_deleteline', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
							// 'text' => $langs->trans("ConfirmClone"),
							// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
							// 1),
							// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
							// => 1),
							array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '', '', 0, 0, null, 0, 'minwidth200')));
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneIntervention', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	if (!$formconfirm)
	{
		$parameters = array('formConfirm' => $formconfirm, 'lineid'=>$lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Intervention card
	$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


	$morehtmlref = '<div class="refidno">';
	// Ref customer
	//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->fichinter->creer, 'string', '', 0, 1);
	//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->fichinter->creer, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
	// Project
	if (!empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref .= '<br>'.$langs->trans('Project').' ';
	    if ($user->rights->ficheinter->creer)
	    {
            if ($action != 'classify') {
                $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            }
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
	    } else {
	        if (!empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
	            $morehtmlref .= $proj->ref;
	            $morehtmlref .= '</a>';
	        } else {
	            $morehtmlref .= '';
	        }
	    }
	}
	$morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield" width="100%">';

	if (!empty($conf->global->FICHINTER_USE_PLANNED_AND_DONE_DATES))
	{
		// Date Start
		print '<tr><td class="titlefield">'.$langs->trans("Dateo").'</td>';
		print '<td>';
		print $object->dateo ? dol_print_date($object->dateo, 'daytext') : '&nbsp;';
		print '</td>';
		print '</tr>';

		// Date End
		print '<tr><td>'.$langs->trans("Datee").'</td>';
		print '<td>';
		print $object->datee ? dol_print_date($object->datee, 'daytext') : '&nbsp;';
		print '</td>';
		print '</tr>';

		// Date Terminate/close
		print '<tr><td>'.$langs->trans("Datet").'</td>';
		print '<td>';
		print $object->datet ? dol_print_date($object->datet, 'daytext') : '&nbsp;';
		print '</td>';
		print '</tr>';
	}

	// Description (must be a textarea and not html must be allowed (used in list view)
	print '<tr><td class="titlefield">';
	print $form->editfieldkey("Description", 'description', $object->description, $object, $user->rights->ficheinter->creer, 'textarea');
	print '</td><td>';
	print $form->editfieldval("Description", 'description', $object->description, $object, $user->rights->ficheinter->creer, 'textarea:8:80');
	print '</td>';
	print '</tr>';

	// Contract
	if ($conf->contrat->enabled)
	{
		$langs->load('contracts');
		print '<tr>';
		print '<td>';

		print '<table class="nobordernopadding centpercent"><tr><td>';
		print $langs->trans('Contract');
		print '</td>';
		if ($action != 'contrat')
		{
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=contrat&amp;id='.$object->id.'">';
			print img_edit($langs->trans('SetContract'), 1);
			print '</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'contrat')
		{
			$formcontract = new Formcontract($db);
			$formcontract->formSelectContract($_SERVER["PHP_SELF"].'?id='.$object->id, $object->socid, $object->fk_contrat, 'contratid', 0, 1);
		}
		else
		{
			if ($object->fk_contrat)
            {
                $contratstatic = new Contrat($db);
                $contratstatic->fetch($object->fk_contrat);
                //print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$selected.'">'.$projet->title.'</a>';
                print $contratstatic->getNomUrl(0, '', 1);
            }
            else
            {
                print "&nbsp;";
            }
		}
		print '</td>';
		print '</tr>';
	}

    // Other attributes
    $cols = 2;
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

    print '</table>';

    print '</div>';
    print '<div class="fichehalfright">';
    print '<div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield centpercent">';

    if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
    {
        // Duration
        print '<tr><td class="titlefield">'.$langs->trans("TotalDuration").'</td>';
        print '<td>'.convertSecondToTime($object->duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY).'</td>';
        print '</tr>';
    }

	print "</table>";

	print '</div>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div><br>';


	if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	// Line of interventions
 	if (empty($conf->global->FICHINTER_DISABLE_DETAILS))
 	{
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" name="addinter" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		if ($action == 'editline')
		{
			print '<input type="hidden" name="action" value="updateline">';
			print '<input type="hidden" name="line_id" value="'.GETPOST('line_id', 'int').'">';
		}
		else
		{
			print '<input type="hidden" name="action" value="addline">';
		}

		// Intervention lines
		$sql = 'SELECT ft.rowid, ft.description, ft.fk_fichinter, ft.duree, ft.rang,';
		$sql .= ' ft.date as date_intervention';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql .= ' WHERE ft.fk_fichinter = '.$object->id;
		if (!empty($conf->global->FICHINTER_HIDE_EMPTY_DURATION))
			$sql .= ' AND ft.duree <> 0';
		$sql .= ' ORDER BY ft.rang ASC, ft.date ASC, ft.rowid';

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				print '<br>';
				print '<table class="noborder centpercent">';

				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans('Description').'</td>';
				print '<td class="liste_titre center">'.$langs->trans('Date').'</td>';
				print '<td class="liste_titre right">'.(empty($conf->global->FICHINTER_WITHOUT_DURATION) ? $langs->trans('Duration') : '').'</td>';
				print '<td class="liste_titre">&nbsp;</td>';
				print '<td class="liste_titre">&nbsp;</td>';
				print '<td class="liste_titre">&nbsp;</td>';
				print "</tr>\n";
			}
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);


				// Ligne en mode visu
				if ($action != 'editline' || GETPOST('line_id', 'int') != $objp->rowid)
				{
					print '<tr class="oddeven">';
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					print dol_htmlentitiesbr($objp->description);

					// Date
					print '<td class="center" width="150">'.(empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR) ?dol_print_date($db->jdate($objp->date_intervention), 'dayhour') : dol_print_date($db->jdate($objp->date_intervention), 'day')).'</td>';

					// Duration
					print '<td class="right" width="150">'.(empty($conf->global->FICHINTER_WITHOUT_DURATION) ?convertSecondToTime($objp->duree) : '').'</td>';

					print "</td>\n";


					// Icone d'edition et suppression
					if ($object->statut == 0 && $user->rights->ficheinter->creer)
					{
						print '<td class="center">';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;line_id='.$objp->rowid.'#'.$objp->rowid.'">';
						print img_edit();
						print '</a>';
						print '</td>';
						print '<td class="center">';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=ask_deleteline&amp;line_id='.$objp->rowid.'">';
						print img_delete();
						print '</a></td>';
						print '<td class="center">';
						if ($num > 1)
						{
							if ($i > 0)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=up&amp;line_id='.$objp->rowid.'">';
								print img_up();
								print '</a>';
							}
							if ($i < $num - 1)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=down&amp;line_id='.$objp->rowid.'">';
								print img_down();
								print '</a>';
							}
						}
						print '</td>';
					}
					else
					{
						print '<td colspan="3">&nbsp;</td>';
					}

					print '</tr>';

					$line = new FichinterLigne($db);
					$line->fetch($objp->rowid);

					$extrafields->fetch_name_optionals_label($line->table_element);

					$line->fetch_optionals();

					print $line->showOptionals($extrafields, 'view', array('colspan'=>5));
				}

				// Line in update mode
				if ($object->statut == 0 && $action == 'editline' && $user->rights->ficheinter->creer && GETPOST('line_id', 'int') == $objp->rowid)
				{
					print '<tr class="oddeven">';
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Editeur wysiwyg
					require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
					$doleditor = new DolEditor('np_desc', $objp->description, '', 164, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_2, '90%');
					$doleditor->Create();
					print '</td>';

					// Date d'intervention
					print '<td class="center nowrap">';
                    if (!empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR)) {
                        print $form->selectDate($db->jdate($objp->date_intervention), 'di', 0, 0, 0, "date_intervention");
                    } else {
                        print $form->selectDate($db->jdate($objp->date_intervention), 'di', 1, 1, 0, "date_intervention");
                    }
					print '</td>';

                    // Duration
                    print '<td class="right">';
                    if (empty($conf->global->FICHINTER_WITHOUT_DURATION)) {
                        $selectmode = 'select';
                        if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION))
                            $selectmode = 'text';
                        $form->select_duration('duration', $objp->duree, 0, $selectmode);
                    }
                    print '</td>';

                    print '<td class="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
					print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
					print '</tr>'."\n";

					$line = new FichinterLigne($db);
					$line->fetch($objp->rowid);

					$extrafields->fetch_name_optionals_label($line->table_element);
					$line->fetch_optionals();

					print $line->showOptionals($extrafields, 'edit', array('colspan'=>5));
				}

				$i++;
			}

			$db->free($resql);

			// Add new line
			if ($object->statut == 0 && $user->rights->ficheinter->creer && $action <> 'editline' && empty($conf->global->FICHINTER_DISABLE_DETAILS))
			{
				if (!$num)
				{
				    print '<br><table class="noborder centpercent">';

    				print '<tr class="liste_titre">';
    				print '<td>';
    				print '<a name="add"></a>'; // ancre
    				print $langs->trans('Description').'</td>';
    				print '<td class="center">'.$langs->trans('Date').'</td>';
    				print '<td class="right">'.(empty($conf->global->FICHINTER_WITHOUT_DURATION) ? $langs->trans('Duration') : '').'</td>';
      				print '<td colspan="3">&nbsp;</td>';
    				print "</tr>\n";
				}

				print '<tr class="oddeven">'."\n";
                print '<td>';
                // editeur wysiwyg
                if (empty($conf->global->FICHINTER_EMPTY_LINE_DESC)) {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor = new DolEditor('np_desc', GETPOST('np_desc', 'alpha'), '', 100, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_DETAILS, ROWS_2, '90%');
                    $doleditor->Create();
                }
                print '</td>';

                // Date intervention
                print '<td class="center nowrap">';
				$now = dol_now();
				$timearray = dol_getdate($now);
				if (!GETPOST('diday', 'int')) {
                    $timewithnohour = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
                } else {
                    $timewithnohour = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
                }
                if (!empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR)) {
                    print $form->selectDate($timewithnohour, 'di', 0, 0, 0, "addinter");
                } else {
                    print $form->selectDate($timewithnohour, 'di', 1, 1, 0, "addinter");
                }
				print '</td>';

                // Duration
                print '<td class="right">';
                if (empty($conf->global->FICHINTER_WITHOUT_DURATION)) {
                    $selectmode = 'select';
                    if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION)) {
                        $selectmode = 'text';
                    }
                    $form->select_duration('duration', (!GETPOST('durationhour', 'int') && !GETPOST('durationmin', 'int')) ? 3600 : (60 * 60 * GETPOST('durationhour', 'int') + 60 * GETPOST('durationmin', 'int')), 0, $selectmode);
                }
                print '</td>';

                print '<td class="center" valign="middle" colspan="3"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addline"></td>';
				print '</tr>';

				//Line extrafield

				$lineadd = new FichinterLigne($db);

				$extrafields->fetch_name_optionals_label($lineadd->table_element);

				print $lineadd->showOptionals($extrafields, 'edit', array('colspan'=>5));

				if (!$num) print '</table>';
			}

			if ($num) print '</table>';
		}
		else
		{
			dol_print_error($db);
		}

		print '</form>'."\n";
 	}

	dol_fiche_end();

	print "\n";


	/*
	 * Actions buttons
	 */

	print '<div class="tabsAction">';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		                                                                                          // modified by hook
	if (empty($reshook))
	{
		if ($user->socid == 0)
		{
            if ($action != 'editdescription' && ($action != 'presend')) {
                // Validate
                if ($object->statut == Fichinter::STATUS_DRAFT && (count($object->lines) > 0 || !empty($conf->global->FICHINTER_DISABLE_DETAILS))) {
                    if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->creer) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->ficheinter_advance->validate)) {
                        print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=validate"';
                        print '>'.$langs->trans("Validate").'</a></div>';
                    }
                }

                // Modify
                if ($object->statut == Fichinter::STATUS_VALIDATED && ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->creer) || (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && $user->rights->ficheinter->ficheinter_advance->unvalidate)))
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="card.php?id='.$object->id.'&action=modify">';
					if (empty($conf->global->FICHINTER_DISABLE_DETAILS)) print $langs->trans("Modify");
					else print $langs->trans("SetToDraft");
					print '</a></div>';
				}

				// Send
				if (empty($user->socid)) {
					if ($object->statut > Fichinter::STATUS_DRAFT)
					{
						if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->ficheinter->ficheinter_advance->send)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
						}
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans('SendMail').'</a></div>';
					}
				}

				// create intervention model
				if ($conf->global->MAIN_FEATURES_LEVEL >= 2 && $object->statut == Fichinter::STATUS_DRAFT && $user->rights->ficheinter->creer && (count($object->lines) > 0)) {
					print '<div class="inline-block divButAction">';
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/card-rec.php?id='.$object->id.'&action=create">'.$langs->trans("ChangeIntoRepeatableIntervention").'</a>';
					print '</div>';
				}

				// Proposal
				if ($conf->service->enabled && !empty($conf->propal->enabled) && $object->statut > Fichinter::STATUS_DRAFT)
				{
					$langs->load("propal");
					if ($object->statut < Fichinter::STATUS_BILLED)
					{
						if ($user->rights->propal->creer) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddProp").'</a></div>';
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("AddProp").'</a></div>';
					}
				}

				// Invoicing
				if (!empty($conf->facture->enabled) && $object->statut > Fichinter::STATUS_DRAFT)
				{
					$langs->load("bills");
					if ($object->statut < Fichinter::STATUS_BILLED)
					{
						if ($user->rights->facture->creer) print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddBill").'</a></div>';
						else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("AddBill").'</a></div>';
					}

					if (!empty($conf->global->FICHINTER_CLASSIFY_BILLED))    // Option deprecated. In a future, billed must be managed with a dedicated field to 0 or 1
					{
						if ($object->statut != Fichinter::STATUS_BILLED)
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("InterventionClassifyBilled").'</a></div>';
						}
						else
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifyunbilled">'.$langs->trans("InterventionClassifyUnBilled").'</a></div>';
						}
					}
				}

				// Done
            	if (empty($conf->global->FICHINTER_CLASSIFY_BILLED) && $object->statut > Fichinter::STATUS_DRAFT && $object->statut < Fichinter::STATUS_CLOSED)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifydone">'.$langs->trans("InterventionClassifyDone").'</a></div>';
				}

				// Clone
				if ($user->rights->ficheinter->creer) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=ficheinter">'.$langs->trans("ToClone").'</a></div>';
				}

				// Delete
				if (($object->statut == Fichinter::STATUS_DRAFT && $user->rights->ficheinter->creer) || $user->rights->ficheinter->supprimer)
				{
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
					print '>'.$langs->trans('Delete').'</a></div>';
				}
			}
		}
	}

	print '</div>';

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';

		/*
		 * Built documents
		 */
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->ficheinter->dir_output."/".$filename;
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = $user->rights->ficheinter->lire;
		$delallowed = $user->rights->ficheinter->creer;
		print $formfile->showdocuments('ficheinter', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('fichinter'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'fichinter', $socid, 1);

		print '</div></div></div>';
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'fichinter_send';
	$defaulttopic = 'SendInterventionRef';
	$diroutput = $conf->ficheinter->dir_output;
	$trackid = 'int'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


llxFooter();

$db->close();
