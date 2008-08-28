<?php
/* Copyright (C) 2008      Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file       htdocs/compta/facture/retardspaiement.php
		\ingroup    facture
		\brief      Page generation PDF factures clients impayées
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/fpdf/fpdfi/fpdi.php");

$langs->load("bills");

// Security check
$facid = isset($_GET["facid"])?$_GET["facid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture',$facid,'');


/*
 * View
 */

$diroutputpdf=$conf->facture->dir_output . '/impayes/temp';

llxHeader('',$langs->trans("BillsLate"));

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
$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

if ($user->rights->facture->lire)
{
	

	if ($_POST["action"] == "generate_pdf"){
		if(is_array($_POST['toGenerate']))
		{
			
			$factures = dol_dir_list($conf->facture->dir_output,'all',1,implode('\.pdf|',$_POST['toGenerate']).'\.pdf','\.meta$|\.png','date',SORT_DESC) ;
	
			// liste les fichiers
			$files = array() ;
			$factures_bak = $factures ;
			foreach($_POST['toGenerate'] as $basename){
				foreach($factures as $facture){
					if(strstr($facture["name"],$basename)){
						$files[] = DOL_DATA_ROOT . '/facture/' . $basename . '/' . $facture["name"] ;
					}
				}
			}
			
			// génère le PDF à partir de tous les autres fichiers
			$pdf=new FPDI();
			foreach($files as $file){
				// Charge un document PDF depuis un fichier.
				$pagecount = $pdf->setSourceFile($file); 
	            for ($i = 1; $i <= $pagecount; $i++) { 
	                 $tplidx = $pdf->ImportPage($i); 
	                 $s = $pdf->getTemplatesize($tplidx); 
	                 $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L'); 
	                 $pdf->useTemplate($tplidx); 
	            } 
			}
			
			// vérifie que le chemin d'accès est bien accessible
			create_exdir($diroutputpdf);
			
			// enregistre le fichier pdf concaténé
			$pdf->Output($diroutputpdf.'/impayes'.dolibarr_date('YmdHis',time()).'.pdf');
			
		} else {
			print '<div class="error">'.$langs->trans('UnpayedNotChecked').'</div>' ;
		}
	}
	
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
	$sql.= " AND f.paye = 0 AND f.fk_statut = 1 AND f.date_lim_reglement < NOW()";
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

	if ($_GET["search_ref"])
	{
		$sql .= " AND f.facnumber like '%".$_GET["search_ref"]."%'";
	}

	if ($_GET["search_societe"])
	{
		$sql .= " AND s.nom like '%".$_GET["search_societe"]."%'";
	}

	if ($_GET["search_montant_ht"])
	{
		$sql .= " AND f.total = '".$_GET["search_montant_ht"]."'";
	}

	if ($_GET["search_montant_ttc"])
	{
		$sql .= " AND f.total_ttc = '".$_GET["search_montant_ttc"]."'";
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

		$titre=($socid?$langs->trans("BillsCustomersUnpayedForCompany",$soc->nom):$langs->trans("BillsLate"));
		print_barre_liste($titre,$page,"retardspaiement.php","&amp;socid=$socid",$sortfield,$sortorder,'',0);	// We don't want pagination on this page
		$i = 0;
		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';

		print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socid=$socid","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Generate"),$_SERVER["PHP_SELF"],"");
		print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socid=$socid",'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","","&amp;socid=$socid",'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;socid=$socid","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","","&amp;socid=$socid",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","","&amp;socid=$socid",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","","&amp;socid=$socid",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye,am","","&amp;socid=$socid",'align="right"',$sortfield,$sortorder);
		print "</tr>\n";

		// Lignes des champs de filtre
		print '<form method="get" action="retardspaiement.php">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" valign="right">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET["search_ref"].'"></td>';
		print '<td class="liste_titre" align="center"><input type="checkbox" onclick="checkall(this.checked);"></td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_societe" value="'.$_GET["search_societe"].'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET["search_montant_ht"].'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET["search_montant_ttc"].'">';
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

			print '<form id="form_generate_pdf" method="post" action="retardspaiement.php?sortfield='. $_GET['sortfield'] .'&sortorder='. $_GET['sortorder'] .'">';
			print '<input type="hidden" name="action" value="generate_pdf">';
		
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
				if ($objp->datelimite < (time() - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1) print img_warning($langs->trans("Late"));
				print '</td>';
				
				print '<td width="16" align="right" class="nobordernopadding">';
					
				$filename=sanitize_string($objp->facnumber);
				$filedir=$conf->facture->dir_output . '/' . sanitize_string($objp->facnumber);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->facid;
				$formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','','','',1);
				
				print '</td></tr></table>';
				
				print '<td align="center"><input id="cb'.$objp->facid.'" type="checkbox" name="toGenerate[]" value="'.$objp->facnumber.'"></td>' ;
				
				print "</td>\n";

				print "<td nowrap align=\"center\">".dolibarr_print_date($objp->df)."</td>\n";
				print "<td nowrap align=\"center\">".dolibarr_print_date($objp->datelimite)."</td>\n";

				print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,32).'</a></td>';

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
		 * Gestion des documents générés
		 */
		$filedir=$diroutputpdf;
		$urlsource=$_SERVER['PHP_SELF'].'?facid='.$fac->id;
		$genallowed=$user->rights->facture->creer;
		$delallowed=$user->rights->facture->supprimer;

		//class="pair" ou "impair" pour les tr
		$var = false ; $bc = array("pair","impair") ;
		
		// liste des fichier
		$file_list=dol_dir_list($filedir,'files',0,'impayes.*','\.meta$','date',SORT_DESC);;

		print '<br>' ;
		print_titre($langs->trans("Documents"));
		print '<table class="border" width="70%">';
			print '<tr class="liste_titre">' ;
				print '<td align="center">'.$langs->trans("File").'</td><td align="center">'.$langs->trans("Size").'</td><td align="center">'.$langs->trans("Date").'</td><td align="center"><input type="submit" class="button" value="'.$langs->trans("Generate").'"></td>' ;
			print '</tr>' ;
			// Pour chaque fichier on affiche une ligne
			foreach($file_list as $file){
				$filepath = $filedir."/".$file["name"] ;
				$var = !$var ;
				print '<tr class="'.$bc[$var].'">' ;
					// Nom du fichier
					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=impayes&amp;file='.urlencode($filepath).'">'.img_pdf($file["name"],2)."&nbsp;".$file["name"].'</td>' ;
					// Taille
					print '<td align="center">'.filesize($filepath). ' bytes</td>' ;
					// Date
					print '<td align="center">'.dolibarr_print_date(filemtime($filepath),'dayhour').'</td>' ;
					// Suppression
					print '<td align="center"><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&amp;modulepart=impayes&amp;file='.urlencode($filepath).'&amp;urlsource='.urlencode($urlsource).'">'.img_delete().'</a></td>' ;
				print '</tr>' ;
			}
			
		print '</table>';
						
		print '</form>';
		
		$db->free();
		
		
	}
	else
	{
		dolibarr_print_error($db);
	}

}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
