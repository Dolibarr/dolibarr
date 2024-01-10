<?php
/* Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseom.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    datapolicy/class/actions_datapolicy.class.php
 * \ingroup datapolicy
 * \brief   Example hook overload.
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

/**
 * Class ActionsDatapolicy
 */
class ActionsDatapolicy extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * Constructor
	 *
	 *  @param  DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Execute action
	 *
	 * @param   array           $parameters		Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action      	'add', 'update', 'view'
	 * @return  int         					Return integer <0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		$this->resprints = '';

		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array   		        $parameters     Hook metadatas (context, etc...)
	 * @param   Societe|CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string         			$action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     		$hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                     		        Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;

		$langs->load('datapolicy@datapolicy');
		$error = 0; // Error counter

		if (GETPOST('socid') && $parameters['currentcontext'] == 'thirdpartycard' && !empty($object)) {
			$object->fetch(GETPOST('socid'));
		}

		// FIXME Removed hard coded id, use codes
		if ($parameters['currentcontext'] == 'thirdpartycard' && $action == 'anonymiser' && (in_array($object->forme_juridique_code, array(11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005)) || $object->typent_id == 8)) {
			// on verifie si l'objet est utilisé
			if ($object->isObjectUsed(GETPOST('socid'))) {
				$object->name = $langs->trans('ANONYME');
				$object->name_alias = '';
				$object->address = '';
				$object->town = '';
				$object->zip = '';
				$object->phone = '';
				$object->email = '';
				$object->url = '';
				$object->fax = '';
				$object->state = '';
				$object->country = '';
				$object->state_id = '';
				$object->socialnetworks = '';
				$object->country_id = '';
				$object->note_private = dol_concatdesc($object->note_private, $langs->trans('ANONYMISER_AT', dol_print_date(dol_now())));

				if ($object->update($object->id, $user, 0)) {
					// On supprime les contacts associé
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".((int) $object->id);
					$this->db->query($sql);

					setEventMessages($langs->trans('ANONYMISER_SUCCESS'), array());
					header('Location:'.$_SERVER["PHP_SELF"]."?socid=".$object->id);
					exit;
				}
			}
		} elseif ($parameters['currentcontext'] == 'contactcard' && $action == 'send_datapolicy') {
			$object->fetch(GETPOST('id'));

			require_once  DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			require_once  DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicy.class.php';
			DataPolicy::sendMailDataPolicyContact($object);
		} elseif ($parameters['currentcontext'] == 'membercard' && $action == 'send_datapolicy') {
			$object->fetch(GETPOST('id'));
			require_once  DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			require_once  DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicy.class.php';
			DataPolicy::sendMailDataPolicyAdherent($object);
		} elseif ($parameters['currentcontext'] == 'thirdpartycard' && $action == 'send_datapolicy') {
			$object->fetch(GETPOST('socid'));
			require_once  DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			require_once  DOL_DOCUMENT_ROOT.'/datapolicy/class/datapolicy.class.php';
			DataPolicy::sendMailDataPolicyCompany($object);
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	 * addMoreActionsButtons
	 *
	 * @param array 		$parameters		array of parameters
	 * @param Object	 	$object			Object
	 * @param string		$action			Actions
	 * @param HookManager	$hookmanager	Hook manager
	 * @return void
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;
		$langs->load('datapolicy@datapolicy');

		if (getDolGlobalString('DATAPOLICY_ENABLE_EMAILS')) {
			$dialog = '<div id="dialogdatapolicy" style="display:none;" title="'.$langs->trans('DATAPOLICY_PORTABILITE_TITLE').'">';
			$dialog .= '<div class="confirmmessage">'.img_help('', '').' '.$langs->trans('DATAPOLICY_PORTABILITE_CONFIRMATION').'</div>';
			$dialog .= "</div>";
			$dialog .= '<script>
                      $( function() {
                        $("#rpgpdbtn").on("click", function(){
                            var href = $(this).attr("href");
                            $( "#dialogdatapolicy" ).dialog({
                              modal: true,
                              buttons: {
                                "OK": function() {
                                  window.open(href);
                                  $( this ).dialog( "close" );
                                },
                                "' . $langs->trans("Cancel").'": function() {
                                  $( this ).dialog( "close" );
                                }
                              }
                            });


                        return false;
                        });
                      } );
                      </script>';
			echo $dialog;
			// TODO Replace test of hardcoded values
			if (!empty($object->mail) && empty($object->array_options['options_datapolicy_send']) && $parameters['currentcontext'] == 'thirdpartycard' && in_array($object->forme_juridique_code, array(11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005)) || $object->typent_id == 8) {
				echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?socid=".$object->id.'&action=send_datapolicy" title="'.$langs->trans('DATAPOLICY_SEND').'">'.$langs->trans("DATAPOLICY_SEND").'</a></div>';
			} elseif (!empty($object->mail) && empty($object->array_options['options_datapolicy_send']) && $parameters['currentcontext'] == 'membercard') {
				echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?rowid=".$object->id.'&action=send_datapolicy" title="'.$langs->trans('DATAPOLICY_SEND').'">'.$langs->trans("DATAPOLICY_SEND").'</a></div>';
			} elseif (!empty($object->mail) && empty($object->array_options['options_datapolicy_send']) && $parameters['currentcontext'] == 'contactcard') {
				echo '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?id=".$object->id.'&action=send_datapolicy" title="'.$langs->trans('DATAPOLICY_SEND').'">'.$langs->trans("DATAPOLICY_SEND").'</a></div>';
			}
		}
	}

	/**
	 * printCommonFooter
	 *
	 * @param array 		$parameters		array of parameters
	 * @param Object	 	$object			Object
	 * @param string		$action			Actions
	 * @param HookManager	$hookmanager	Hook manager
	 * @return int
	 */
	public function printCommonFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$jsscript = '';
		if ($parameters['currentcontext'] == 'thirdpartycard') {
			if (GETPOST('action') == 'create' || GETPOST('action') == 'edit' || GETPOST('action') == '') {
				$jsscript .= '<script>';
				$jsscript .= "var elementToHide = 'tr.societe_extras_datapolicy_consentement, tr.societe_extras_datapolicy_opposition_traitement, tr.societe_extras_datapolicy_opposition_prospection';".PHP_EOL;
				$jsscript .= "var forme_juridique = [".PHP_EOL;
				$jsscript .= "11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005".PHP_EOL;
				$jsscript .= "];".PHP_EOL;
				$jsscript .= "function hideRgPD() {".PHP_EOL;
				$jsscript .= " if ($('#typent_id').val() == 8 || forme_juridique.indexOf(parseInt($('#forme_juridique_code').val())) > -1) {".PHP_EOL;
				$jsscript .= " console.log(elementToHide);".PHP_EOL;
				$jsscript .= " $('tr.societe_extras_datapolicy_consentement, tr.societe_extras_datapolicy_opposition_traitement, tr.societe_extras_datapolicy_opposition_prospection').show(); } else { $('tr.societe_extras_datapolicy_consentement, tr.societe_extras_datapolicy_opposition_traitement, tr.societe_extras_datapolicy_opposition_prospection').hide(); }}".PHP_EOL;
				$jsscript .= "hideRgPD();".PHP_EOL;
				$jsscript .= "$('#forme_juridique_code, #typent_id').change(function(){ hideRgPD(); });".PHP_EOL;
				$jsscript .= '</script>';
			} elseif (GETPOST('action') == 'confirm_delete' && GETPOST('confirm') == 'yes' && GETPOST('socid') > 0) {
				// La suppression n'a pas été possible
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$societe = new Societe($this->db);
				$societe->fetch(GETPOST('socid'));
				// On vérifie si il est utilisé
				if ((in_array($object->forme_juridique_code, array(11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005)) || $societe->typent_id == 8) && $societe->isObjectUsed(GETPOST('socid'))) {
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
					$form = new Form($this->db);
					echo $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".GETPOST('socid'), substr($langs->trans("DATAPOLICY_POPUP_ANONYME_TITLE"), 0, strlen($langs->trans("DATAPOLICY_POPUP_ANONYME_TITLE")) - 2), $langs->trans("DATAPOLICY_POPUP_ANONYME_TEXTE"), 'anonymiser', '', '', 1);
				}
			}

			if (GETPOST('socid')) {
				/* Removed due to awful harcoded values
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$societe = new Societe($this->db);
				$societe->fetch(GETPOST('socid'));

				if (!empty($object->forme_juridique_code) && !in_array($object->forme_juridique_code, array(11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005)) && $societe->typent_id != 8) {
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
					$jsscript .= '<script>';
					$jsscript .= "var elementToHide = 'td.societe_extras_datapolicy_opposition_traitement, td.societe_extras_datapolicy_opposition_prospection, td.societe_extras_datapolicy_consentement';".PHP_EOL;
					$jsscript .= "$(elementToHide).parent('tr').hide();".PHP_EOL;
					$jsscript .= '</script>';
				}
				*/
			}
		} elseif ($parameters['currentcontext'] == 'contactcard') {
			if (GETPOST('action') == 'create' || GETPOST('action') == 'edit') {
				$jsscript .= '<script>';
				$jsscript .= "$('#options_datapolicy_opposition_traitement, #options_datapolicy_opposition_prospection, input[name=\"options_datapolicy_opposition_traitement\"], input[name=\"options_datapolicy_opposition_prospection\"]').change(function(){
                    if($('#options_datapolicy_opposition_traitement').prop('checked') == true || $('input[name=options_datapolicy_opposition_traitement]').prop('checked') || $('#options_datapolicy_opposition_prospection').prop('checked') || $('input[name=options_datapolicy_opposition_prospection]').prop('checked')) {
                        $('#no_email').val(1);
                    }
                });";
				$jsscript .= '</script>';
			}
		}

		$this->resprints = $jsscript;

		return 0;
	}
}
