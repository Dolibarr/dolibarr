<?php
/* Copyright (C) 2002-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2018	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2020	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2013		Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2018	Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2014-2022	Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2015-2016	Abbes Bahfir				<bafbes@gmail.com>
 * Copyright (C) 2018-2022	Philippe Grand				<philippe.grand@atoo-net.com>
 * Copyright (C) 2020-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2023       Benjamin Grembi				<benjamin@oarces.fr>
 * Copyright (C) 2023-2024	William Mead				<william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/fichinter/card_actions.php
 *	\brief      Actions of intervention
 *	\ingroup    ficheinter
 */

// Actions

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/fichinter/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/fichinter/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				// Because createFromClone modifies the object, we must clone it so that we can restore it later
				$orig = clone $object;

				$result = $object->createFromClone($user, $socid);
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action = '';
				}
			}
		}
	}

	if ($action == 'confirm_validate' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->setValid($user);

		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->model_pdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'confirm_sign' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->setSignedStatus($user, GETPOSTINT('signed_status'), 0, 'FICHINTER_MODIFY');
		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->model_pdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'confirm_unsign' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->setSignedStatus($user, $object::SIGNED_STATUSES['STATUS_NO_SIGNATURE'], 0, 'FICHINTER_MODIFY');
		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->model_pdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'confirm_modify' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->setDraft($user);
		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->model_pdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'confirm_done' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->setClose($user);

		if ($result >= 0) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$result = fichinter_create($db, $object, (!GETPOST('model', 'alpha')) ? $object->model_pdf : GETPOST('model', 'alpha'), $outputlangs);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'add' && $user->hasRight('ficheinter', 'creer')) {
		$selectedLines = GETPOST('toselect', 'array');
		$object->socid = $socid;
		$object->duration = GETPOSTINT('duration');
		$object->fk_project = GETPOSTINT('projectid');
		$object->fk_contrat = GETPOSTINT('contratid');
		$object->user_author_id = $user->id;
		$object->description = GETPOST('description', 'restricthtml');
		$object->ref = $ref;
		$object->ref_client = $ref_client;
		$object->model_pdf = GETPOST('model', 'alpha');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		$object->note_public = GETPOST('note_public', 'restricthtml');

		if ($object->socid > 0) {
			// If creation from another object of another module (Example: origin=propal, originid=1)
			if (!empty($origin) && !empty($originid)) {
				// Parse element/subelement (ex: project_task)
				$regs = array();
				$element = $subelement = GETPOST('origin', 'alphanohtml');
				if (preg_match('/^([^_]+)_([^_]+)/i', GETPOST('origin', 'alphanohtml'), $regs)) {
					$element = $regs[1];
					$subelement = $regs[2];
				}

				// For compatibility
				if ($element == 'order') {
					$element = $subelement = 'commande';
				}
				if ($element == 'propal') {
					$element = 'comm/propal';
					$subelement = 'propal';
				}
				if ($element == 'contract') {
					$element = $subelement = 'contrat';
				}

				$object->origin    = $origin;
				$object->origin_id = $originid;

				// Possibility to add external linked objects with hooks
				$object->linked_objects[$object->origin] = $object->origin_id;
				if (GETPOSTISARRAY('other_linked_objects')) {
					$object->linked_objects = array_merge($object->linked_objects, GETPOST('other_linked_objects', 'array:int'));
				}

				// Extrafields

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
					$action = 'create';
				}
				//$array_options = $extrafields->getOptionalsFromPost($object->table_element);

				//$object->array_options = $array_options;

				$id = $object->create($user);

				if ($id > 0) {
					dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

					$classname = ucfirst($subelement);
					$srcobject = new $classname($db);

					dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
					$result = $srcobject->fetch($object->origin_id);
					if ($result > 0) {
						$srcobject->fetch_thirdparty();
						$lines = $srcobject->lines;
						if (empty($lines) && method_exists($srcobject, 'fetch_lines')) {
							$srcobject->fetch_lines();
							$lines = $srcobject->lines;
						}

						if (is_array($lines)) {
							$num = count($lines);

							for ($i = 0; $i < $num; $i++) {
								if (!in_array($lines[$i]->id, $selectedLines)) {
									continue; // Skip unselected lines
								}

								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : Product::TYPE_PRODUCT);

								if ($product_type == Product::TYPE_SERVICE || getDolGlobalString('FICHINTER_PRINT_PRODUCTS')) { //only services except if config includes products
									$duration = 3600; // Default to one hour
									$desc = '';
									// Predefined products & services
									if ($lines[$i]->fk_product > 0) {
										$prod = new Product($db);
										$prod->id = $lines[$i]->fk_product;

										// Define output language
										if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
											$prod->getMultiLangs();
											// We show if duration is present on service (so we get it)
											$prod->fetch($lines[$i]->fk_product);
											$outputlangs = $langs;
											$newlang = '';
											if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
												$newlang = GETPOST('lang_id', 'aZ09');
											}
											if (empty($newlang)) {
												$newlang = $srcobject->thirdparty->default_lang;
											}
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
													$mult = (int) (3600 * 24 * (365 / 12)); // Average month duration
													break;
												case 'y':
													$mult = 3600 * 24 * 365;
													break;
											}
											$duration = (int) $prod->duration_value * $mult * $lines[$i]->qty;
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

									$timearray = dol_getdate(dol_now());
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

									if ($result < 0) {
										$error++;
										break;
									}
								}
							}
						}
					} else {
						$langs->load("errors");
						setEventMessages($srcobject->error, $srcobject->errors, 'errors');
						$action = 'create';
						$error++;
					}
				} else {
					$langs->load("errors");
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'create';
					$error++;
				}
			} else {
				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
					$action = 'create';
				}

				if (!$error) {
					// Extrafields
					$array_options = $extrafields->getOptionalsFromPost($object->table_element);

					$object->array_options = $array_options;

					$result = $object->create($user);
					if ($result > 0) {
						$id = $result; // Force raffraichissement sur fiche venant d'etre cree
					} else {
						$langs->load("errors");
						setEventMessages($object->error, $object->errors, 'errors');
						$action = 'create';
						$error++;
					}
				}
			}
		} else {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdParty"));
			$action = 'create';
			$error++;
		}
	} elseif ($action == 'update' && $user->hasRight('ficheinter', 'creer')) {
		$object->socid = $socid;
		$object->fk_project = GETPOSTINT('projectid');
		$object->fk_contrat = GETPOSTINT('contratid');
		$object->user_author_id = $user->id;
		$object->description = GETPOST('description', 'restricthtml');
		$object->ref = $ref;
		$object->ref_client = $ref_client;

		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'classin' && $user->hasRight('ficheinter', 'creer')) {
		// Set into a project
		$result = $object->setProject(GETPOSTINT('projectid'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setcontract' && $user->hasRight('contrat', 'creer')) {
		// Set into a contract
		$result = $object->set_contrat($user, GETPOSTINT('contratid'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == 'setref_client' && $user->hasRight('ficheinter', 'creer')) {
		// Positionne ref client
		$result = $object->setRefClient($user, GETPOST('ref_client', 'alpha'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('ficheinter', 'supprimer')) {
		$result = $object->delete($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		header('Location: '.DOL_URL_ROOT.'/fichinter/list.php?leftmenu=ficheinter&restore_lastsearch_values=1');
		exit;
	} elseif ($action == 'setdescription' && $user->hasRight('ficheinter', 'creer')) {
		$result = $object->set_description($user, GETPOST('description'));
		if ($result < 0) {
			dol_print_error($db, $object->error);
		}
	} elseif ($action == "addline" && $user->hasRight('ficheinter', 'creer')) {
		// Add line
		if (!GETPOST('np_desc', 'restricthtml') && !getDolGlobalString('FICHINTER_EMPTY_LINE_DESC')) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description"));
			$error++;
		}
		if (!getDolGlobalString('FICHINTER_WITHOUT_DURATION') && !GETPOSTINT('durationhour') && !GETPOSTINT('durationmin')) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Duration"));
			$error++;
		}
		if (!getDolGlobalString('FICHINTER_WITHOUT_DURATION') && GETPOSTINT('durationhour') >= 24 && GETPOSTINT('durationmin') > 0) {
			$mesg = $langs->trans("ErrorValueTooHigh");
			$error++;
		}
		if (!$error) {
			$db->begin();

			$desc = GETPOST('np_desc', 'restricthtml');
			$date_intervention = dol_mktime(GETPOSTINT('dihour'), GETPOSTINT('dimin'), 0, GETPOSTINT('dimonth'), GETPOSTINT('diday'), GETPOSTINT('diyear'));
			$duration = !getDolGlobalString('FICHINTER_WITHOUT_DURATION') ? convertTime2Seconds(GETPOSTINT('durationhour'), GETPOSTINT('durationmin')) : 0;

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
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
				$newlang = $object->thirdparty->default_lang;
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			if ($result >= 0) {
				$db->commit();

				if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
					fichinter_create($db, $object, $object->model_pdf, $outputlangs);
				}
				header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			} else {
				$mesg = $object->error;
				$db->rollback();
			}
		}
	} elseif ($action == 'classifybilled' && $user->hasRight('ficheinter', 'creer')) {
		// Classify Billed
		$result = $object->setStatut(Fichinter::STATUS_BILLED);
		if ($result > 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'classifyunbilled' && $user->hasRight('ficheinter', 'creer')) {
		// Classify unbilled
		$result = $object->setStatut(Fichinter::STATUS_VALIDATED);
		if ($result > 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'confirm_reopen' && $user->hasRight('ficheinter', 'creer')) {
		// Reopen
		$result = $object->setStatut(Fichinter::STATUS_VALIDATED);
		if ($result > 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$mesg = $object->error;
		}
	} elseif ($action == 'updateline' && $user->hasRight('ficheinter', 'creer') && GETPOST('save', 'alpha')) {
		// Mise a jour d'une ligne d'intervention
		$objectline = new FichinterLigne($db);
		if ($objectline->fetch($lineid) <= 0) {
			dol_print_error($db);
			exit;
		}

		if ($object->fetch($objectline->fk_fichinter) <= 0) {
			dol_print_error($db);
			exit;
		}
		$object->fetch_thirdparty();

		$desc = GETPOST('np_desc', 'restricthtml');
		$date_inter = dol_mktime(GETPOSTINT('dihour'), GETPOSTINT('dimin'), 0, GETPOSTINT('dimonth'), GETPOSTINT('diday'), GETPOSTINT('diyear'));
		$duration = convertTime2Seconds(GETPOSTINT('durationhour'), GETPOSTINT('durationmin'));

		$objectline->date = $date_inter;
		$objectline->desc = $desc;
		$objectline->duration = $duration;

		// Extrafields
		$extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		if (is_array($array_options)) {
			$objectline->array_options = array_merge($objectline->array_options, $array_options);
		}

		$result = $objectline->update($user);
		if ($result < 0) {
			dol_print_error($db);
			exit;
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			fichinter_create($db, $object, $object->model_pdf, $outputlangs);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	} elseif ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->hasRight('ficheinter', 'creer')) {
		// Supprime une ligne d'intervention AVEC confirmation
		$objectline = new FichinterLigne($db);
		if ($objectline->fetch($lineid) <= 0) {
			dol_print_error($db);
			exit;
		}
		$result = $objectline->deleteLine($user);

		if ($object->fetch($objectline->fk_fichinter) <= 0) {
			dol_print_error($db);
			exit;
		}

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			fichinter_create($db, $object, $object->model_pdf, $outputlangs);
		}
	} elseif ($action == 'up' && $user->hasRight('ficheinter', 'creer')) {
		// Set position of lines
		$object->line_up($lineid);

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			fichinter_create($db, $object, $object->model_pdf, $outputlangs);
		}

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'#'.$lineid);
		exit;
	} elseif ($action == 'down' && $user->hasRight('ficheinter', 'creer')) {
		$object->line_down($lineid);

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			fichinter_create($db, $object, $object->model_pdf, $outputlangs);
		}

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
	$permissiontoadd = $user->hasRight('ficheinter', 'creer');
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'update_extras' && $user->hasRight('ficheinter', 'creer')) {
		$object->oldcopy = dol_clone($object, 2);
		$attribute_name = GETPOST('attribute', 'restricthtml');

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->updateExtraField($attribute_name, 'INTERVENTION_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	if (getDolGlobalString('MAIN_DISABLE_CONTACTS_TAB') && $user->hasRight('ficheinter', 'creer')) {
		if ($action == 'addcontact') {
			if ($result > 0 && $id > 0) {
				$contactid = (GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
				$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
				$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));
			}

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$langs->load("errors");
					$mesg = $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
				} else {
					$mesg = $object->error;
				}
			}
		} elseif ($action == 'swapstatut') {
			// bascule du statut d'un contact
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} elseif ($action == 'deletecontact') {
			// Efface un contact
			$result = $object->delete_contact(GETPOSTINT('lineid'));

			if ($result >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				dol_print_error($db);
			}
		}
	}
}
