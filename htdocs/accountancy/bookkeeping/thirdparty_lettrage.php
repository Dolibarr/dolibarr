<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013      Florian Henry	      <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 *
 */

/**
 * \file    accounting/bookkeeping/thirdparty_lettrage.php
 * \ingroup Advanced accountancy
 * \brief   Onglet de gestion de parametrages des ventilations
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

$action=GETPOST('action','aZ09');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if ($sortorder == "") $sortorder = "ASC";
if ($sortfield == "") $sortfield = "bk.rowid";

$search_year = GETPOST ( "search_year" );

// Security check
$socid = GETPOST("socid",'int');
// if ($user->societe_id) $socid=$user->societe_id;

$object = new Societe($db);
$object->id = $socid;
$object->fetch($socid);


$form = new Form($db);
$BookKeeping = new lettering($db);
$formaccounting = new FormAccounting($db);


/*
 * Action
 */

if ($action == 'lettering') {

	$result =  $BookKeeping->updatelettrage($_POST['ids']);

// 	var_dump($result);
	if( $result < 0 ){
		setEventMessages('', $BookKeeping->errors, 'errors' );
		$error++;

	}
}

if ($action == 'autolettrage') {

	$result = $BookKeeping->LettrageTiers($socid);

	if( $result < 0 ){
							setEventMessages('', $BookKeeping->errors, 'errors' );
							$error++;

						}

}


llxHeader ( '', 'Compta - Grand Livre' );


	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($object);

	dol_htmloutput_mesg(is_numeric($error)?'':$error, $errors, 'error');

	dol_fiche_head($head, 'TabAccounting', $langs->trans("ThirdParty"),0,'company');




	print '<table width="100%" class="border">';
	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td><td width="70%" colspan="3">';
	$object->next_prev_filter="te.fournisseur = 1";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }


		print '<tr>';
        print '<td class="nowrap">'.$langs->trans("CustomerCode"). '</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td>';
        print '</tr>';

		$langs->load('compta');
        print '<tr>';
        print '<td>';
        print $form->editfieldkey("CustomerAccountancyCode",'customeraccountancycode',$object->code_compta,$object,$user->rights->societe->creer);
        print '</td><td colspan="3">';
        print $form->editfieldval("CustomerAccountancyCode",'customeraccountancycode',$object->code_compta,$object,$user->rights->societe->creer);
        print '</td>';
        print '</tr>';


	// Address
	print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">';
	dol_print_address($object->address,'gmap','thirdparty',$object->id);
	print '</td></tr>';

	// Zip / Town
	print '<tr><td class="nowrap">'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town.'</td>';
	print '</tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	//$img=picto_from_langcode($object->country_code);
	$img='';
	if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
	else print ($img?$img.' ':'').$object->country;
	print '</td></tr>';

	print '</table>';





	$sql = "SELECT bk.rowid, bk.doc_date, bk.doc_type, bk.doc_ref, bk.code_tiers, bk.numero_compte , bk.label_compte, bk.debit , bk.credit, bk.montant , bk.sens , bk.code_journal , bk.piece_num, bk.lettering_code ";
	$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as bk";
	$sql .= " WHERE (bk.code_tiers =  '" . $object->code_compta . "' AND bk.numero_compte = '" .$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER. "' )" ;



	if (dol_strlen ( $search_year  )) {
		$sql .= " AND ( bk.doc_date BETWEEN  '" . $search_year . "-0-0' AND  '" . ($search_year+1). "-0-0' )";
	}


	$sql .= " ORDER BY bk.lettering_code ASC, bk.doc_date ASC" ;//. $db->plimit ( $conf->liste_limit + 1, $offset );

// 	echo $sql;
// 	dol_syslog ( "bookkeping:liste:create sql=" . $sql, LOG_DEBUG );
	$resql = $db->query ( $sql );
	if ($resql) {
		$num = $db->num_rows ( $resql );
		$i = 0;


		print '<form name="add" action="?socid='.$object->id.'" method="POST">';
		print '<input type="hidden" name="action" value="lettering">';
		print '<input type="hidden" name="socid" value="'.$object->id.'">';

		print "<table class=\"noborder\" width=\"100%\">";
		print '<tr class="liste_titre">';
			print '<td></td>';
		print_liste_field_titre ( $langs->trans ( "Doctype" ), "liste.php", "bk.doc_type" );
		print_liste_field_titre ( $langs->trans ( "Docdate" ), "liste.php", "bk.doc_date" );
		print_liste_field_titre ( $langs->trans ( "Docref" ), "liste.php", "bk.doc_ref" );
// 		print_liste_field_titre ( $langs->trans ( "Numerocompte" ), "liste.php", "bk.numero_compte" );
// 		print_liste_field_titre ( $langs->trans ( "Code_tiers" ), "liste.php", "bk.code_tiers" );
		print_liste_field_titre ( $langs->trans ( "Labelcompte" ), "liste.php", "bk_label_compte" );
		print_liste_field_titre ( $langs->trans ( "Debit" ), "liste.php", "bk.debit" );
		print_liste_field_titre ( $langs->trans ( "Credit" ), "liste.php", "bk.credit" );
//		print_liste_field_titre ( $langs->trans ( "Amount" ), "liste.php", "bk.montant" );
//		print_liste_field_titre ( $langs->trans ( "Sens" ), "liste.php", "bk.sens" );
		print_liste_field_titre ( $langs->trans ( "Codejournal" ), "liste.php", "bk.code_journal" );
		print '<td></td>';
		print '<td></td>';
		print "</tr>\n";

		print '<tr class="liste_titre">';
		print '<form action="" method="GET">';
		print '<input type="hidden" name="socid" value="' . $_GET ["socid"] . '">';
		print '<td><input type="text" name="search_doc_type" value="' . $_GET ["search_doc_type"] . '"></td>';
		print '<td><input type="text" name="search_year" value="' . $_GET ["search_year"] . '"></td>';
		print '<td><input type="text" name="search_doc_refe" value="' . $_GET ["search_doc_ref"] . '"></td>';
// 		print '<td><input type="text" name="search_compte" value="' . $_GET ["search_compte"] . '"></td>';
// 		print '<td><input type="text" name="search_tiers" value="' . $_GET ["search_tiers"] . '"></td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
//		print '<td>&nbsp;</td>';
//		print '<td>&nbsp;</td>';
		print '<td align="right">';
		print '<input type="image" class="liste_titre" name="button_search" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '">';
		print '</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '<td>&nbsp;</td>';
		print '</form>';
		print '</tr>';

		$var = false;

		$debit = 0;
		$credit = 0;
		$solde = 0;
		$tmp = '';
		while ( $i < $num ) {
			$obj = $db->fetch_object ( $resql );

			if($tmp !=$obj->lettering_code || empty($tmp) )
				$tmp =$obj->lettering_code;

			if($tmp !=$obj->lettering_code || empty($obj->lettering_code))
				$var = ! $var;


			$debit+= $obj->debit;
			$credit+= $obj->credit;

			$solde+=($obj->credit-$obj->debit);
			print "<tr $bc[$var]>";

			print '<td>' . $obj->rowid . '</td>';
			if(empty($obj->lettering_code)){
				print '<td><a href="'.dol_buildpath('/accountancy/bookkeeping/card.php', 1).'?piece_num=' . $obj->piece_num . '">';
				print img_edit ();
				print '</a>&nbsp;' . $obj->doc_type . '</td>' . "\n";
			}
			else
				print '<td>'.$obj->doc_type . '</td>' . "\n";



			print '<td>' . dol_print_date ( $db->jdate ( $obj->doc_date ), 'day' ) . '</td>';
			print '<td>' . $obj->doc_ref . '</td>';
// 			print '<td>' . $obj->numero_compte . '</td>';
// 			print '<td>' . $obj->code_tiers . '</td>';
			print '<td>' . $obj->label_compte . '</td>';
			print '<td>' . $obj->debit . '</td>';
			print '<td>' . $obj->credit . '</td>';
//			print '<td>' . $obj->montant . '</td>';
//			print '<td>' . $obj->sens . '</td>';
			print '<td>' . $obj->code_journal . '</td>';
			print '<td>' . round($solde, 2) . '</td>';

			if(empty($obj->lettering_code)){
				print '<td><input type="checkbox" name="ids[]" value="' . $obj->rowid . '" /></td>';
			}
			else
				print '<td>' . $obj->lettering_code . '</td>';

			print "</tr>\n";

			$i ++;




		}

			print '<tr class="oddeven">';

			print '<td colspan="4">Mouvement totaux</td>' . "\n";
			print '<td></td>';
	//		print '<td></td>';
	//		print '<td></td>';
			print '<td><strong>' . $debit . '</strong></td>';
			print '<td><strong>' . $credit . '</strong></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

			print "<tr $bc[$var]>";
			print '<td colspan="5">Solde Comptable</td>' . "\n";
	//		print '<td></td>';
	//		print '<td></td>';
			print '<td></td>';
			print '<td><strong>' . ($credit-$debit) . '</strong></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td>&nbsp;</td>';
			print "</tr>\n";

		print "</table>";

		print '<input class="butAction" type="submit" value="lettering">';
		print '<a class="butAction" href="?socid='.$object->id.'&action=autolettrage">auto lettering</a>';
		print "</form>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}


// End of page
llxFooter();
$db->close();

