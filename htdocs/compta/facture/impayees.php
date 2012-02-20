<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/compta/facture/impayees.php
 *		\ingroup    facture
 *		\brief      Page to list and build liste of unpaid invoices
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');


$langs->load("bills");

$id = (GETPOST('facid','int') ? GETPOST('facid','int') : GETPOST('id','int'));
$action = GETPOST('action','alpha');
$option = GETPOST('option');

$diroutputpdf=$conf->facture->dir_output . '/unpaid/temp';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'facture',$id,'');


/*
 * Action
 */

if ($action == "builddoc" && $user->rights->facture->lire)
{
	if (is_array($_POST['toGenerate']))
	{
	    $arrayofexclusion=array();
	    foreach($_POST['toGenerate'] as $tmppdf) $arrayofexclusion[]=preg_quote($tmppdf.'.pdf','/');
		$factures = dol_dir_list($conf->facture->dir_output,'all',1,implode('|',$arrayofexclusion),'\.meta$|\.png','date',SORT_DESC);

		// liste les fichiers
		$files = array();
		$factures_bak = $factures ;
		foreach($_POST['toGenerate'] as $basename){
			foreach($factures as $facture){
				if(strstr($facture["name"],$basename)){
					$files[] = $conf->facture->dir_output.'/'.$basename.'/'.$facture["name"];
				}
			}
		}

        // Define output language (Here it is not used because we do only merging existing PDF)
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }

        // Create empty PDF
        $pdf=pdf_getInstance();
        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

		// Add all others
		foreach($files as $file)
		{
			// Charge un document PDF depuis un fichier.
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++)
			{
				$tplidx = $pdf->importPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
			}
		}

		// Create output dir if not exists
		dol_mkdir($diroutputpdf);

		// Save merged file
		$filename=strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
		if ($option=='late') $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
		if ($pagecount)
		{
			$file=$diroutputpdf.'/'.$filename.'_'.dol_print_date(mktime(),'dayhourlog').'.pdf';
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			$mesg='<div class="error">'.$langs->trans('NoPDFAvailableForChecked').'</div>';
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans('InvoiceNotChecked').'</div>' ;
	}
}



/*
 * View
 */

$title=$langs->trans("BillsCustomersUnpaid");
if ($option=='late') $title=$langs->trans("BillsCustomersUnpaid");

llxHeader('',$title);

$form = new Form($db);
$formfile = new FormFile($db);

?>
<script type="text/javascript">
$(document).ready(function() {
	$("#checkall").click(function() {
		$(".checkformerge").attr('checked', true);
	});
	$("#checknone").click(function() {
		$(".checkformerge").attr('checked', false);
	});
});
</script>
<?php

/***************************************************************************
 *                                                                         *
 *                      Mode Liste                                         *
 *                                                                         *
 ***************************************************************************/

$now=dol_now();

$search_ref = GETPOST("search_ref");
$search_societe = GETPOST("search_societe");
$search_montant_ht = GETPOST("search_montant_ht");
$search_montant_ttc = GETPOST("search_montant_ttc");
$late = GETPOST("late");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;

$sql = "SELECT s.nom, s.rowid as socid";
$sql.= ", f.facnumber,f.increment,f.total as total_ht,f.total_ttc";
$sql.= ", f.datef as df, f.date_lim_reglement as datelimite";
$sql.= ", f.paye as paye, f.rowid as facid, f.fk_statut, f.type";
$sql.= ", sum(pf.amount) as am";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ",".MAIN_DB_PREFIX."facture as f";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " AND f.type IN (0,1,3) AND f.fk_statut = 1";
$sql.= " AND f.paye = 0";
if ($option == 'late') $sql.=" AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->client->warning_delay)."'";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;
if ($_GET["filtre"])
{
	$filtrearr = explode(",", $_GET["filtre"]);
	foreach ($filtrearr as $fil)
	{
		$filt = explode(":", $fil);
		$sql .= " AND " . $filt[0] . " = " . $filt[1];
	}
}
if ($search_ref)         $sql .= " AND f.facnumber LIKE '%".$search_ref."%'";
if ($search_societe)     $sql .= " AND s.nom LIKE '%".$search_societe."%'";
if ($search_montant_ht)  $sql .= " AND f.total = '".$search_montant_ht."'";
if ($search_montant_ttc) $sql .= " AND f.total_ttc = '".$search_montant_ttc."'";
if (dol_strlen($_POST["sf_ref"]) > 0) $sql .= " AND f.facnumber LIKE '%".$_POST["sf_ref"] . "%'";
$sql.= " GROUP BY f.facnumber,f.increment,f.total,f.total_ttc,f.datef, f.date_lim_reglement,f.paye, f.rowid, f.fk_statut, f.type,s.nom, s.rowid";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " ORDER BY ";
$listfield=explode(',',$sortfield);
foreach ($listfield as $key => $value) $sql.=$listfield[$key]." ".$sortorder.",";
$sql.= " f.facnumber DESC";

//$sql .= $db->plimit($limit+1,$offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
	}

	$param="";
	$param.=($socid?"&amp;socid=".$socid:"");
	$param.=($option?"&amp;option=".$option:"");
	if ($search_ref)         $param.='&amp;search_ref='.urlencode($search_ref);
	if ($search_societe)     $param.='&amp;search_societe='.urlencode($search_societe);
	if ($search_montant_ht)  $param.='&amp;search_montant_ht='.urlencode($search_montant_ht);
	if ($search_montant_ttc) $param.='&amp;search_montant_ttc='.urlencode($search_montant_ttc);
	if ($late)               $param.='&amp;late='.urlencode($late);

	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$titre=($socid?$langs->trans("BillsCustomersUnpaidForCompany",$soc->nom):$langs->trans("BillsCustomersUnpaid"));
	if ($option == 'late') $titre.=' ('.$langs->trans("Late").')';
	else $titre.=' ('.$langs->trans("All").')';

	$link='';
	if (empty($option)) $link='<a href="'.$_SERVER["PHP_SELF"].'?option=late">'.$langs->trans("ShowUnpaidLateOnly").'</a>';
	elseif ($option == 'late') $link='<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("ShowUnpaidAll").'</a>';
	print_fiche_titre($titre,$link);
	//print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',0);	// We don't want pagination on this page

	dol_htmloutput_mesg($mesg);

	$i = 0;
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye,am","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Merge"),$_SERVER["PHP_SELF"],"","",$param,'align="center"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<tr class="liste_titre">';
	// Ref
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="12" name="search_societe" value="'.$search_societe.'">';
	print '</td><td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
	print '</td><td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
	print '</td><td class="liste_titre" colspan="2" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
	print '</td>';
	print "</tr>\n";
	print '</form>';

	if ($num > 0)
	{
		$var=True;
		$total_ht=0;
		$total_ttc=0;
		$total_paid=0;

		$facturestatic=new Facture($db);

		print '<form id="form_generate_pdf" method="POST" action="'.$_SERVER["PHP_SELF"].'?sortfield='. $sortfield .'&sortorder='. $sortorder .'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$date_limit=$db->jdate($objp->datelimite);

			$var=!$var;

			print "<tr $bc[$var]>";
			$classname = "impayee";

			print '<td nowrap="nowrap">';

			$facturestatic->id=$objp->facid;
			$facturestatic->ref=$objp->facnumber;
			$facturestatic->type=$objp->type;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';

			// Ref
			print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
			print $facturestatic->getNomUrl(1);
			print '</td>';

			// Warning picto
			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($date_limit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1) print img_warning($langs->trans("Late"));
			print '</td>';

			// PDF Picto
			print '<td width="16" align="right" class="nobordernopadding">';
            $filename=dol_sanitizeFileName($objp->facnumber);
			$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
			$foundpdf=$formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','',1,'',1,$param);
            print '</td>';

			print '</tr></table>';

			print "</td>\n";

			print '<td nowrap align="center">'.dol_print_date($db->jdate($objp->df),'day').'</td>'."\n";
			print '<td nowrap align="center">'.dol_print_date($db->jdate($objp->datelimite),'day').'</td>'."\n";

			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,28).'</a></td>';

			print '<td align="right">'.price($objp->total_ht).'</td>';
			print '<td align="right">'.price($objp->total_ttc).'</td>';
			print '<td align="right">'.price($objp->am).'</td>';

			// Affiche statut de la facture
			print '<td align="right" nowrap="nowrap">';
			print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);
			print '</td>';

			// Checkbox
			print '<td align="center">';
			if ($foundpdf) print '<input id="cb'.$objp->facid.'" class="flat checkformerge" type="checkbox" name="toGenerate[]" value="'.$objp->facnumber.'">';
			else print '&nbsp;';
			print '</td>' ;

			print "</tr>\n";
			$total_ht+=$objp->total_ht;
			$total_ttc+=$objp->total_ttc;
			$total_paid+=$objp->am;

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="4" align="left">'.$langs->trans("Total").'</td>';
		print '<td align="right"><b>'.price($total_ht).'</b></td>';
		print '<td align="right"><b>'.price($total_ttc).'</b></td>';
		print '<td align="right"><b>'.price($total_paid).'</b></td>';
		print '<td align="center">&nbsp;</td>';
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";

	/*
	 * Show list of available documents
	 */
	$filedir=$diroutputpdf;
	if ($search_ref)         print '<input type="hidden" name="search_ref" value="'.$search_ref.'">';
	if ($search_societe)     print '<input type="hidden" name="search_societe" value="'.$search_societe.'">';
	if ($search_montant_ht)  print '<input type="hidden" name="search_montant_ht" value="'.$search_montant_ht.'">';
	if ($search_montant_ttc) print '<input type="hidden" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
	if ($late)               print '<input type="hidden" name="late" value="'.$late.'">';
	$genallowed=$user->rights->facture->lire;
	$delallowed=$user->rights->facture->lire;

	print '<br>';
	print '<input type="hidden" name="option" value="'.$option.'">';
	$formfile->show_documents('unpaid','',$filedir,$urlsource,$genallowed,$delallowed,'',1,0,0,48,1,$param,$langs->trans("PDFMerge"),$langs->trans("PDFMerge"));
	print '</form>';

	$db->free($resql);
}
else dol_print_error($db,'');


llxFooter();

if (is_object($db)) $db->close();
?>
