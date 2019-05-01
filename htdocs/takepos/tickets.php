<?php
/* 
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
 *  \file       htdocs/takepos/tickets.php
 *  \ingroup    facture
 *  \brief      Tickets list
 */

$res=@include("../main.inc.php");                    // For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
	if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
	


require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->facture->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';



$langs->load("bills");
$langs->load("users");

$action		= (GETPOST('action') ? GETPOST('action') : 'view');
$cancel     = GETPOST('cancel');
$backtopage = GETPOST('backtopage','alpha');
$confirm	= GETPOST('confirm');
$socid		= GETPOST('socid','int');


/*
 *  View
 */

$form = new Form($db);


$title=$langs->trans("Tickets");

//V20:
$arrayofcss=array('/takepos/css/style.css');	//V20
top_htmlhead($head,$langs->trans("takepos"),0,0,$arrayofjs,$arrayofcss);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


        dol_htmloutput_mesg(is_numeric($error)?'':$error, $errors, 'error');

        /*
         * View
         */
        if (!empty($object->id)) $res=$object->fetch_optionals($object->id,$extralabels);
        

        //V20: Normal Wiev
        
        print load_fiche_titre($langs->trans("TicketList"),$linkback,'title_companies.png');
        
 		dol_fiche_head('');
        

        dol_htmloutput_errors($error,$errors);
		
        
        
        print '<div class="fichecenter">';
      
   
       		
		//V20: Draft tickets
		/*
		 *   Last invoices
		 */
		if (! empty($conf->facture->enabled))
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
			
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
			$sql.= " WHERE facnumber LIKE '%(PROV-POS-%'";
						
			$sql.= " ORDER BY f.datef DESC, f.datec DESC";
	
			$resql=$db->query($sql);
			if ($resql)
			{
				$var=true;
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num > 0)       print '<table class="noborder" width="100%">';
				
	
				while ($i < $num && $i < $MAXLIST)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;
					print "<tr ".$bc[$var].">";
					print '<td class="nowrap">';
					print $objp->facnumber;
					print '</td>';
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
			//V20: Action buttons	
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




// End of page
llxFooter();
$db->close();
