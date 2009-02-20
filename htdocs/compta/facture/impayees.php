<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/compta/facture/impayees.php
 *		\ingroup    facture
 *		\brief      Page to list and build liste of unpayed invoices
 *		\version    $Revision$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/fpdf/fpdfi/fpdi.php");


$langs->load("bills");

$facid = isset($_GET["facid"])?$_GET["facid"]:'';
$option = $_REQUEST["option"];

$diroutputpdf=$conf->facture->dir_output . '/unpayed/temp';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'facture',$facid,'');


/*
 * Action
 */

if ($_POST["action"] == "builddoc" && $user->rights->facture->lire)
{
	if (is_array($_POST['toGenerate']))
	{

		$factures = dol_dir_list($conf->facture->dir_output,'all',1,implode('\.pdf|',$_POST['toGenerate']).'\.pdf','\.meta$|\.png','date',SORT_DESC) ;

		// liste les fichiers
		$files = array() ;
		$factures_bak = $factures ;
		foreach($_POST['toGenerate'] as $basename){
			foreach($factures as $facture){
				if(strstr($facture["name"],$basename)){
					$files[] = $conf->facture->dir_output.'/'.$basename.'/'.$facture["name"] ;
				}
			}
		}

		// Create empty PDF
		$pdf=new FPDI('P','mm','A4');
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);
		//$pdf->SetCompression(false);

		//$pdf->Open();
		//$pdf->AddPage();
		//$title=$langs->trans("BillsCustomersUnpayed");
		//if ($option=='late') $title=$langs->trans("BillsCustomersUnpayed");
		//$pdf->MultiCell(100, 3, $title, 0, 'J');

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
		create_exdir($diroutputpdf);

		// enregistre le fichier pdf concatene
		$filename=strtolower(sanitizeFileName($langs->transnoentities("Unpayed")));
		if ($option=='late') $filename.='_'.strtolower(sanitizeFileName($langs->transnoentities("Late")));
		if ($pagecount)
		{
			$file=$diroutputpdf.'/'.$filename.'_'.dol_print_date(mktime(),'dayhourlog').'.pdf';
			$pdf->Output($file);
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
		$mesg='<div class="error">'.$langs->trans('UnpayedNotChecked').'</div>' ;
	}
}





/*
 * View
 */

$title=$langs->trans("BillsCustomersUnpayed");
if ($option=='late') $title=$langs->trans("BillsCustomersUnpayed");

llxHeader('',$title);

$html = new Form($db);
$formfile = new FormFile($db);
?><script type="text/javascript">
<!--
	function checkall(checked){
		var checkboxes = [];
		checkboxes = $$('input').each(function(e){ if(e.type == 'checkbox') checkboxes.push(e) });
		checkboxes.each(function(e){ e.checked = checked });
	}
 -->
</script>
<?php


/***************************************************************************
*                                                                         *
*                      Mode Liste                                         *
*                                                                         *
***************************************************************************/

$now=gmmktime();

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT s.nom, s.rowid as socid";
$sql.= ", f.facnumber,f.increment,f.total as total_ht,f.total_ttc";
$sql.= ", ".$db->pdate("f.datef")." as df, ".$db->pdate("f.date_lim_reglement")." as datelimite";
$sql.= ", f.paye as paye, f.rowid as facid, f.fk_statut";
$sql.= ", sum(pf.amount) as am";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ",".MAIN_DB_PREFIX."facture as f";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
$sql.= " WHERE f.fk_soc = s.rowid";
$sql.= " AND f.type in (0,1) AND f.fk_statut = 1";
$sql.= " AND f.paye = 0";
if ($option == 'late')
{
	$sql.=" AND f.date_lim_reglement < ".$db->idate(mktime());
}
if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;

if ($_GET["filtre"])
{
	$filtrearr = split(",", $_GET["filtre"]);
	foreach ($filtrearr as $fil)
	{
		$filt = split(":", $fil);
		$sql .= " AND " . $filt[0] . " = " . $filt[1];
	}
}

if ($_REQUEST["search_ref"])
{
	$sql .= " AND f.facnumber like '%".$_REQUEST["search_ref"]."%'";
}

if ($_REQUEST["search_societe"])
{
	$sql .= " AND s.nom like '%".$_REQUEST["search_societe"]."%'";
}

if ($_REQUEST["search_montant_ht"])
{
	$sql .= " AND f.total = '".$_REQUEST["search_montant_ht"]."'";
}

if ($_REQUEST["search_montant_ttc"])
{
	$sql .= " AND f.total_ttc = '".$_REQUEST["search_montant_ttc"]."'";
}

if (strlen($_POST["sf_ref"]) > 0)
{
	$sql .= " AND f.facnumber like '%".$_POST["sf_ref"] . "%'";
}
$sql.= " GROUP BY f.facnumber";

$sql.= " ORDER BY ";
$listfield=split(',',$sortfield);
foreach ($listfield as $key => $value) $sql.=$listfield[$key]." ".$sortorder.",";
$sql.= " f.facnumber DESC";

//$sql .= $db->plimit($limit+1,$offset);

$result = $db->query($sql);

if ($result)
{
	$num = $db->num_rows($result);

	if ($socid)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
	}

	$param="";
	$param.=($socid?"&amp;socid=".$socid:"");
	$param.=($option?"&amp;option=".$option:"");
	if ($_REQUEST["search_ref"])         $param.='&amp;search_ref='.urlencode($_REQUEST["search_ref"]);
	if ($_REQUEST["search_societe"])     $param.='&amp;search_societe='.urlencode($_REQUEST["search_societe"]);
	if ($_REQUEST["search_montant_ht"])  $param.='&amp;search_montant_ht='.urlencode($_REQUEST["search_montant_ht"]);
	if ($_REQUEST["search_montant_ttc"]) $param.='&amp;search_montant_ttc='.urlencode($_REQUEST["search_montant_ttc"]);
	if ($_REQUEST["late"])               $param.='&amp;late='.urlencode($_REQUEST["search_late"]);

	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=eregi_replace('&amp;','&',$param);

	$titre=($socid?$langs->trans("BillsCustomersUnpayedForCompany",$soc->nom):$langs->trans("BillsCustomersUnpayed"));
	if ($option == 'late') $titre.=' ('.$langs->trans("Late").')';
	else $titre.=' ('.$langs->trans("All").')';

	$link='';
	if (empty($option)) $link='<a href="'.$_SERVER["PHP_SELF"].'?option=late">'.$langs->trans("ShowUnpayedLateOnly").'</a>';
	elseif ($option == 'late') $link='<a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("ShowUnpayedAll").'</a>';
	print_fiche_titre($titre,$link);
	//print_barre_liste($titre,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',0);	// We don't want pagination on this page

	if ($mesg) print $mesg;

	$i = 0;
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Generate"),$_SERVER["PHP_SELF"],"","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye,am","",$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_REQUEST["search_ref"].'"></td>';
	print '<td class="liste_titre" align="center"><input type="checkbox" onclick="checkall(this.checked);"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" name="search_societe" value="'.$_REQUEST["search_societe"].'">';
	print '</td><td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_REQUEST["search_montant_ht"].'">';
	print '</td><td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_REQUEST["search_montant_ttc"].'">';
	print '</td><td class="liste_titre" colspan="2" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '</td>';
	print "</tr>\n";
	print '</form>';


	$facturestatic=new Facture($db);


	if ($num > 0)
	{
		$var=True;
		$total_ht=0;
		$total_ttc=0;
		$total_payed=0;

		print '<form id="form_generate_pdf" method="post" action="'.$_SERVER["PHP_SELF"].'?sortfield='. $_GET['sortfield'] .'&sortorder='. $_GET['sortorder'] .'">';

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);

			$var=!$var;

			print "<tr $bc[$var]>";
			$class = "impayee";

			print '<td nowrap="nowrap">';

			$facturestatic->id=$objp->facid;
			$facturestatic->ref=$objp->facnumber;
			$facturestatic->type=$objp->type;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $facturestatic->getNomUrl(1);
			print '</td>';

			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($objp->datelimite < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1) print img_warning($langs->trans("Late"));
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';

			$filename=sanitizeFileName($objp->facnumber);
			$filedir=$conf->facture->dir_output . '/' . sanitizeFileName($objp->facnumber);
			$foundpdf=$formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','','','',1,$param);
			print '</td></tr></table>';

			// Checkbox
			print '<td align="center">';
			if ($foundpdf) print '<input id="cb'.$objp->facid.'" type="checkbox" name="toGenerate[]" value="'.$objp->facnumber.'">';
			else print '&nbsp;';
			print '</td>' ;

			print "</td>\n";

			print "<td nowrap align=\"center\">".dol_print_date($objp->df)."</td>\n";
			print "<td nowrap align=\"center\">".dol_print_date($objp->datelimite)."</td>\n";

			print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,32).'</a></td>';

			print "<td align=\"right\">".price($objp->total_ht)."</td>";
			print "<td align=\"right\">".price($objp->total_ttc)."</td>";
			print "<td align=\"right\">".price($objp->am)."</td>";

			// Affiche statut de la facture
			print '<td align="right" nowrap="nowrap">';
			print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$objp->am);
			print '</td>';

			print "</tr>\n";
			$total_ht+=$objp->total_ht;
			$total_ttc+=$objp->total_ttc;
			$total_payed+=$objp->am;

			$i++;
		}

		print '<tr class="liste_total">';
		print "<td colspan=\"5\" align=\"left\">".$langs->trans("Total").": </td>";
		print "<td align=\"right\"><b>".price($total_ht)."</b></td>";
		print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td align=\"right\"><b>".price($total_payed)."</b></td>";
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";


	/*
	 * Show list of available documents
	 */
	$filedir=$diroutputpdf;
	if ($_REQUEST["search_ref"])         print '<input type="hidden" name="search_ref" value="'.$_REQUEST["search_ref"].'">';
	if ($_REQUEST["search_societe"])     print '<input type="hidden" name="search_societe" value="'.$_REQUEST["search_societe"].'">';
	if ($_REQUEST["search_montant_ht"])  print '<input type="hidden" name="search_montant_ht" value="'.$_REQUEST["search_montant_ht"].'">';
	if ($_REQUEST["search_montant_ttc"]) print '<input type="hidden" name="search_montant_ttc" value="'.$_REQUEST["search_montant_ttc"].'">';
	if ($_REQUEST["late"])               print '<input type="hidden" name="late" value="'.$_REQUEST["late"].'">';
	$genallowed=$user->rights->facture->lire;
	$delallowed=$user->rights->facture->lire;

	print '<br>';
	print '<input type="hidden" name="option" value="'.$option.'">';
	$formfile->show_documents('unpayed','',$filedir,$urlsource,$genallowed,$delallowed,'','',0,0,48,1,$param);
	print '</form>';

	$db->free();
}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
