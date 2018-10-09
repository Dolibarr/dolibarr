<?php
/* Copyright (C) 2018 Nicolas ZABOURI <info@inovea-conseil.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    gdpr/class/actions_gdpr.class.php
 * \ingroup gdpr
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class Actionsgdpr
 */
class Actionsgdpr
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
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	function getNomUrl($parameters,&$object,&$action)
	{
		global $db,$langs,$conf,$user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;
        $langs->load('gdpr@gdpr');
		$error = 0; // Error counter

        if (GETPOST('socid') && $parameters['currentcontext'] == 'thirdpartycard' ) {
            $object->fetch(GETPOST('socid'));
        }

        if ($parameters['currentcontext'] == 'thirdpartycard' && $action == 'anonymiser' && ($object->forme_juridique_code == 11 ||$object->forme_juridique_code == 12 || $object->forme_juridique_code == 13 || $object->forme_juridique_code == 15 || $object->forme_juridique_code == 17 || $object->forme_juridique_code == 18 ||$object->forme_juridique_code == 19 ||$object->forme_juridique_code == 35 ||$object->forme_juridique_code == 60 ||$object->forme_juridique_code == 200 ||$object->forme_juridique_code == 311 ||$object->forme_juridique_code == 312 ||$object->forme_juridique_code == 316 ||$object->forme_juridique_code == 401 ||$object->forme_juridique_code == 600 ||$object->forme_juridique_code == 700 ||$object->forme_juridique_code == 1005 ||$object->typent_id == 8)) {
            // on verifie si l'objet est utilisé
            if ($object->isObjectUsed(GETPOST('socid'))) {
                $object->name = $langs->trans('ANONYME');
                $object->name_bis = '';
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
                $object->skype = '';
                $object->country_id = '';
                $object->note_private = $object->note_private . '<br/>' . $langs->trans('ANONYMISER_AT', dol_print_date(time()));

                if ($object->update($object->id, $user, 0)) {

                    // On supprime les contacts associé
                    $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = " . $object->id;
                    $this->db->query($sql);

                    setEventMessages($langs->trans('ANONYMISER_SUCCESS'), array());
                    header('Location:' . $_SERVER["PHP_SELF"] . "?socid=" . $object->id);
                }


            }
        } elseif ($parameters['currentcontext'] == 'thirdpartycard' && $action == 'gdpr_portabilite') {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=gdpr_portabilite.csv');
            header('Pragma: no-cache');
            $object->fetch(GETPOST('socid'));
            echo 'Name;Fistname;Civility;Thirdparty;Function;Address;Zipcode;City;Department;Country;Email;Pro phone;Perso phone;mobile phone;Instant message;Birthday;' . PHP_EOL;
            echo $object->name .';';
            echo ';';
            echo ';';
            echo ';';
            echo ';';
            echo $object->address .';';
            echo $object->zip .';';
            echo $object->town .';';
            echo $object->state .';';
            echo $object->country .';';
            echo $object->email .';';
            echo $object->phone .';';
            echo ';';
            echo ';';
            echo $object->skype .';';
            echo ';';
            exit;
        } elseif ($parameters['currentcontext'] == 'membercard' && $action == 'gdpr_portabilite') {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=gdpr_portabilite.csv');
            header('Pragma: no-cache');
            $soc = $object->fetch_thirdparty();

            echo 'Name;Fistname;Civility;Thirdparty;Function;Address;Zipcode;City;Department;Country;Email;Pro phone;Perso phone;mobile phone;Instant message;Birthday;' . PHP_EOL;
            echo $object->lastname .';';
            echo $object->firstname .';';
            echo $object->getCivilityLabel() .';';
            echo ($soc != -1 ? $object->thirdparty->name : '') .';';
            echo ';';
            echo $object->address .';';
            echo $object->zip .';';
            echo $object->town .';';
            echo $object->state .';';
            echo $object->country .';';
            echo $object->email .';';
            echo $object->phone .';';
            echo $object->phone_perso .';';
            echo $object->phone_mobile .';';
            echo $object->skype .';';
            echo dol_print_date($object->birth) .';';
            exit;
        } elseif ($parameters['currentcontext'] == 'contactcard' && $action == 'gdpr_portabilite') {
            $object->fetch(GETPOST('id'));
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=gdpr_portabilite.csv');
            header('Pragma: no-cache');
            $soc = $object->fetch_thirdparty();
            echo 'Name;Fistname;Civility;Thirdparty;Function;Address;Zipcode;City;Department;Country;Email;Pro phone;Perso phone;mobile phone;Instant message;Birthday;' . PHP_EOL;
            echo $object->lastname .';';
            echo $object->firstname .';';
            echo $object->getCivilityLabel() .';';
            echo ($soc != -1 ? $object->thirdparty->name : '') .';';
            echo $object->poste .';';
            echo $object->address .';';
            echo $object->zip .';';
            echo $object->town .';';
            echo $object->state .';';
            echo $object->country .';';
            echo $object->email .';';
            echo $object->phone_pro .';';
            echo $object->phone_perso .';';
            echo $object->phone_mobile .';';
            echo $object->jabberid .';';
            echo dol_print_date($object->birth) .';';
            exit;
        }


        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))	    // do something only for the context 'somecontext1' or 'somecontext2'
	    {
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them, or update database depending on $action and $_POST values.
		}

		if (! $error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
	        foreach($parameters['toselect'] as $objectid)
	        {
	            // Do action on each object id

	        }
	    }

	    if (! $error) {
	        $this->results = array('myreturn' => 999);
	        $this->resprints = 'A text to show';
	        return 0; // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameter s     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $conf, $user, $langs;

	    $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
	    if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
	    {
	        $this->resprints = '<option value="0"'.($disabled?' disabled="disabled"':'').'>'.$langs->trans("gdprMassAction").'</option>';
	    }

	    if (! $error) {
	        return 0; // or return 1 to replace standard code
	    } else {
	        $this->errors[] = 'Error message';
	        return -1;
	    }
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs=$langs;

		$ret=0; $deltemp=array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{

		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters		Array of parameters
	 * @param   Object	$pdfhandler   	PDF builder handler
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	<0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs=$langs;

		$ret=0; $deltemp=array();
		dol_syslog(get_class($this).'::executeHooks action='.$action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1','somecontext2')))		// do something only for the context 'somecontext1' or 'somecontext2'
		{

		}

		return $ret;
	}

    function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;
        $langs->load('gdpr@gdpr');

        $dialog = '<div id="dialoggdpr" style="display:none;" title="'.$langs->trans('RGPD_PORTABILITE_TITLE').'">';
        $dialog .= '<div class="confirmmessage">'.img_help('','').' '.$langs->trans('RGPD_PORTABILITE_CONFIRMATION') . '</div>';
        $dialog .= "</div>";
        $dialog .= '<script>
                  $( function() {
                    $("#rpgpdbtn").on("click", function(){
                        var href = $(this).attr("href");
                        $( "#dialoggdpr" ).dialog({
                          modal: true,
                          buttons: {
                            "OK": function() {
                              window.open(href);
                              $( this ).dialog( "close" );
                            },
                            "'.$langs->trans('Cancel').'": function() {
                              $( this ).dialog( "close" );
                            }
                          }
                        });
                        
                    
                    return false;
                    });
                  } );
                  </script>';
        echo $dialog;
        if ($parameters['currentcontext'] == 'thirdpartycard' && $object->forme_juridique_code == 11 ||$object->forme_juridique_code == 12 || $object->forme_juridique_code == 13 || $object->forme_juridique_code == 15 || $object->forme_juridique_code == 17 || $object->forme_juridique_code == 18 ||$object->forme_juridique_code == 19 ||$object->forme_juridique_code == 35 ||$object->forme_juridique_code == 60 ||$object->forme_juridique_code == 200 ||$object->forme_juridique_code == 311 ||$object->forme_juridique_code == 312 ||$object->forme_juridique_code == 316 ||$object->forme_juridique_code == 401 ||$object->forme_juridique_code == 600 ||$object->forme_juridique_code == 700 ||$object->forme_juridique_code == 1005 || $object->typent_id == 8) {
            echo '<div class="inline-block divButAction"><a target="_blank" id="rpgpdbtn" class="butAction" href="'.$_SERVER["PHP_SELF"]."?socid=".$object->id.'&action=gdpr_portabilite" title="'.$langs->trans('RGPD_PORTABILITE_TITLE').'">'.$langs->trans("RGPD_PORTABILITE").'</a></div>';
        } elseif ($parameters['currentcontext'] == 'membercard') {
            echo '<div class="inline-block divButAction"><a target="_blank" id="rpgpdbtn" class="butAction" href="'.$_SERVER["PHP_SELF"]."?rowid=".$object->id.'&action=gdpr_portabilite" title="'.$langs->trans('RGPD_PORTABILITE_TITLE').'">'.$langs->trans("RGPD_PORTABILITE").'</a></div>';
        } elseif ($parameters['currentcontext'] == 'contactcard') {
            echo '<div class="inline-block divButAction"><a target="_blank" id="rpgpdbtn" class="butAction" href="'.$_SERVER["PHP_SELF"]."?id=".$object->id.'&action=gdpr_portabilite" title="'.$langs->trans('RGPD_PORTABILITE_TITLE').'">'.$langs->trans("RGPD_PORTABILITE").'</a></div>';
        }

    }

	function printCommonFooter($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $user, $langs;

        $jsscript = '';
        if ($parameters['currentcontext'] == 'thirdpartycard')	{
            if (GETPOST('action') == 'create' || GETPOST('action') == 'edit' || GETPOST('action') == '') {
                $jsscript .= '<script>';
                $jsscript .= "var elementToHide = 'tr.societe_extras_gdpr_consentement, tr.societe_extras_gdpr_opposition_traitement, tr.societe_extras_gdpr_opposition_prospection';" . PHP_EOL;
                $jsscript .= "var forme_juridique = [" . PHP_EOL;
                $jsscript .= "11, 12, 13, 15, 17, 18, 19, 35, 60, 200, 311, 312, 316, 401, 600, 700, 1005" . PHP_EOL;
                $jsscript .= "];" . PHP_EOL;
                $jsscript .= "function hideRgPD() { if ($('#typent_id').val() == 8 || forme_juridique.indexOf(parseInt($('#forme_juridique_code').val())) > -1) { console.log(elementToHide); $('tr.societe_extras_gdpr_consentement, tr.societe_extras_gdpr_opposition_traitement, tr.societe_extras_gdpr_opposition_prospection').show(); } else { $('tr.societe_extras_gdpr_consentement, tr.societe_extras_gdpr_opposition_traitement, tr.societe_extras_gdpr_opposition_prospection').hide(); }}" . PHP_EOL;
                $jsscript .= "hideRgPD();" . PHP_EOL;
                $jsscript .= "$('#forme_juridique_code, #typent_id').change(function(){ hideRgPD(); });" . PHP_EOL;
                $jsscript .= '</script>';
            } elseif (GETPOST('action') == 'confirm_delete' && GETPOST('confirm') == 'yes' && GETPOST('socid') > 0) {

                // La suppression n'a pas été possible
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $societe = new Societe($this->db);
                $societe->fetch(GETPOST('socid'));
                // On vérifie si il est utilisé
                if (($societe->forme_juridique_code == 11 ||$societe->forme_juridique_code == 12 || $societe->forme_juridique_code == 13 || $societe->forme_juridique_code == 15 || $societe->forme_juridique_code == 17 || $societe->forme_juridique_code == 18 ||$societe->forme_juridique_code == 19 ||$societe->forme_juridique_code == 35 ||$societe->forme_juridique_code == 60 ||$societe->forme_juridique_code == 200 ||$societe->forme_juridique_code == 311 ||$societe->forme_juridique_code == 312 ||$societe->forme_juridique_code == 316 ||$societe->forme_juridique_code == 401 ||$societe->forme_juridique_code == 600 ||$societe->forme_juridique_code == 700 ||$societe->forme_juridique_code == 1005 ||$societe->typent_id == 8) && $societe->isObjectUsed(GETPOST('socid'))) {

                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                    $form = new Form($this->db);
                    echo $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".GETPOST('socid'), substr($langs->trans("RGPD_POPUP_ANONYME_TITLE"), 0, strlen($langs->trans("RGPD_POPUP_ANONYME_TITLE"))-2), $langs->trans("RGPD_POPUP_ANONYME_TEXTE"), 'anonymiser', '', '', 1);
                }

            }

            if (GETPOST('socid')) {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $societe = new Societe($this->db);
                $societe->fetch(GETPOST('socid'));
                
                if ($societe->forme_juridique_code != 11 && $societe->forme_juridique_code != 12 && $societe->forme_juridique_code != 13 && $societe->forme_juridique_code != 15 && $societe->forme_juridique_code != 17 && $societe->forme_juridique_code != 18 &&$societe->forme_juridique_code != 19 &&$societe->forme_juridique_code != 35 &&$societe->forme_juridique_code != 60 &&$societe->forme_juridique_code != 200 &&$societe->forme_juridique_code != 311 &&$societe->forme_juridique_code != 312 &&$societe->forme_juridique_code != 316 &&$societe->forme_juridique_code != 401 &&$societe->forme_juridique_code != 600 &&$societe->forme_juridique_code != 700 &&$societe->forme_juridique_code != 1005 &&$societe->typent_id != 8) {

                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
                    $jsscript .= '<script>';
                    $jsscript .= "var elementToHide = 'td.societe_extras_gdpr_opposition_traitement, td.societe_extras_gdpr_opposition_prospection, td.societe_extras_gdpr_consentement';" . PHP_EOL;
                    $jsscript .= "$(elementToHide).parent('tr').hide();" . PHP_EOL;
                    $jsscript .= '</script>';
                }

            }
        } elseif ($parameters['currentcontext'] == 'contactcard') {
            if (GETPOST('action') == 'create' || GETPOST('action') == 'edit' ) {
                $jsscript .= '<script>';
                $jsscript .= "$('#options_gdpr_opposition_traitement, #options_gdpr_opposition_prospection, input[name=\"options_gdpr_opposition_traitement\"], input[name=\"options_gdpr_opposition_prospection\"]').change(function(){
                    if($('#options_gdpr_opposition_traitement').prop('checked') == true || $('input[name=options_gdpr_opposition_traitement]').prop('checked') || $('#options_gdpr_opposition_prospection').prop('checked') || $('input[name=options_gdpr_opposition_prospection]').prop('checked')) {
                        $('#no_email').val(1);
                    }
                });";
                $jsscript .= '</script>';
            }
        }

        echo $jsscript;
    }

}
